<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    protected function getBaseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    protected function getAccessToken(): ?string
    {
        $base = $this->getBaseUrl();
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');

        if (!$clientId || !$secret) {
            Log::warning('PayPal credentials not configured.');
            return null;
        }

        $response = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        if (!$response->successful()) {
            Log::error('PayPal token error', ['body' => $response->body()]);
            return null;
        }

        return $response->json('access_token');
    }

    public function createOrder(Order $order)
    {
        if ($order->payment_method !== Order::PAYMENT_METHOD_PAYPAL || $order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return redirect()->route('orders.index')->with('error', 'Đơn hàng không hợp lệ hoặc đã thanh toán.');
        }
        if (! in_array($order->status, [Order::STATUS_UNPAID, Order::STATUS_PAYMENT_FAILED], true)) {
            return redirect()->route('orders.index')->with('error', 'Đơn hàng không ở trạng thái chờ thanh toán.');
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return redirect()->route('orders.index')->with('error', 'Không thể kết nối PayPal. Vui lòng thử lại sau.');
        }

        $base = $this->getBaseUrl();
        $returnUrl = url()->route('paypal.success', ['order' => $order->id]);
        $cancelUrl = url()->route('orders.index', ['status' => Order::STATUS_UNPAID]);

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $order->id,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format((float) $order->total_amount / 23000, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'brand_name' => 'NovaShop',
            ],
        ];

        $response = Http::withToken($token)
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post("{$base}/v2/checkout/orders", $payload);

        if (!$response->successful()) {
            Log::error('PayPal create order failed', ['body' => $response->body(), 'order_id' => $order->id]);
            return redirect()->route('orders.index')->with('error', 'Tạo giao dịch PayPal thất bại.');
        }

        $paypalOrderId = $response->json('id');
        $links = $response->json('links', []);
        $approveUrl = null;
        foreach ($links as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                $approveUrl = $link['href'] ?? null;
                break;
            }
        }

        if (!$approveUrl) {
            return redirect()->route('orders.index')->with('error', 'Không nhận được link thanh toán PayPal.');
        }

        $order->payments()->create([
            'payment_method' => 'paypal',
            'gateway' => 'paypal',
            'transaction_id' => $paypalOrderId,
            'amount' => $order->total_amount,
            'status' => 'pending',
            'response_payload' => $response->json(),
        ]);

        return redirect()->away($approveUrl);
    }

    public function success(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return redirect()->route('order.success', ['order' => $order->id]);
        }

        $paypalOrderId = trim((string) $request->query('token', ''));
        if ($paypalOrderId === '') {
            $pendingPayment = $order->payments()->where('status', 'pending')->latest()->first();
            $paypalOrderId = $pendingPayment ? trim((string) $pendingPayment->transaction_id) : '';
        }
        if ($paypalOrderId === '') {
            Log::warning('PayPal success: no token in URL and no pending payment', ['order_id' => $order->id]);
            return redirect()->route('orders.index')->with('error', 'Thiếu thông tin từ PayPal. Vui lòng thử thanh toán lại từ đơn hàng.');
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return redirect()->route('orders.index')->with('error', 'Không thể xác thực thanh toán.');
        }

        $base = $this->getBaseUrl();
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ])
            ->withBody('{}', 'application/json')
            ->post("{$base}/v2/checkout/orders/{$paypalOrderId}/capture");

        if (!$response->successful()) {
            $decoded = $response->json();
            $detail = $decoded['details'][0]['description'] ?? $decoded['message'] ?? null;
            $message = is_string($detail) ? $detail : (is_string($decoded['message'] ?? null) ? $decoded['message'] : 'Vui lòng thử lại.');
            Log::error('PayPal capture failed', [
                'order_id' => $order->id,
                'paypal_order_id' => $paypalOrderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $order->update([
                'status' => Order::STATUS_PAYMENT_FAILED,
                'payment_status' => Order::PAYMENT_STATUS_FAILED,
                'shipping_status' => Order::mapShippingStatusFromOrderStatus(Order::STATUS_PAYMENT_FAILED),
            ]);
            $order->payments()->where('transaction_id', $paypalOrderId)->update([
                'status' => 'failed',
                'failure_reason' => $message,
                'error_code' => (string) $response->status(),
                'response_payload' => $decoded,
            ]);
            return redirect()->route('orders.index', ['status' => Order::STATUS_UNPAID])
                ->with('error', 'Thanh toán PayPal thất bại: ' . $message);
        }

        $order->update([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'status' => Order::STATUS_PENDING,
            'shipping_status' => Order::mapShippingStatusFromOrderStatus(Order::STATUS_PENDING),
        ]);

        $order->payments()->where('transaction_id', $paypalOrderId)->update([
            'status' => 'completed',
            'response_payload' => $response->json(),
            'failure_reason' => null,
            'error_code' => null,
        ]);

        return redirect()->route('order.success', ['order' => $order->id])
            ->with('success', 'Thanh toán thành công.');
    }
}
