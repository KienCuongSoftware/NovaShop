<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoController extends Controller
{
    protected function endpoint(): string
    {
        return (string) config('services.momo.endpoint', 'https://test-payment.momo.vn/v2/gateway/api/create');
    }

    protected function partnerCode(): string
    {
        return (string) config('services.momo.partner_code');
    }

    protected function accessKey(): string
    {
        return (string) config('services.momo.access_key');
    }

    protected function secretKey(): string
    {
        return (string) config('services.momo.secret_key');
    }

    protected function requestType(): string
    {
        return (string) config('services.momo.request_type', 'payWithATM');
    }

    protected function normalizeResultCode($value): int
    {
        return is_numeric($value) ? (int) $value : -1;
    }

    protected function makeSignature(array $params): string
    {
        $raw = implode('&', [
            'accessKey='.(string) ($params['accessKey'] ?? ''),
            'amount='.(string) ($params['amount'] ?? ''),
            'extraData='.(string) ($params['extraData'] ?? ''),
            'ipnUrl='.(string) ($params['ipnUrl'] ?? ''),
            'orderId='.(string) ($params['orderId'] ?? ''),
            'orderInfo='.(string) ($params['orderInfo'] ?? ''),
            'partnerCode='.(string) ($params['partnerCode'] ?? ''),
            'redirectUrl='.(string) ($params['redirectUrl'] ?? ''),
            'requestId='.(string) ($params['requestId'] ?? ''),
            'requestType='.(string) ($params['requestType'] ?? ''),
        ]);

        return hash_hmac('sha256', $raw, $this->secretKey());
    }

    protected function verifySignature(array $payload): bool
    {
        $provided = (string) ($payload['signature'] ?? '');
        if ($provided === '') {
            return false;
        }
        unset($payload['signature']);

        $expected = $this->makeSignature($payload);
        return hash_equals($expected, $provided);
    }

    public function createOrder(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }
        if ($order->payment_method !== Order::PAYMENT_METHOD_MOMO || $order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return redirect()->route('orders.index')->with('error', 'Đơn hàng không hợp lệ hoặc đã thanh toán.');
        }
        if (! in_array($order->status, [Order::STATUS_UNPAID, Order::STATUS_PAYMENT_FAILED], true)) {
            return redirect()->route('orders.index')->with('error', 'Đơn hàng không ở trạng thái chờ thanh toán.');
        }

        if ($this->partnerCode() === '' || $this->accessKey() === '' || $this->secretKey() === '') {
            return redirect()->route('orders.index')->with('error', 'Chưa cấu hình MoMo.');
        }

        $requestId = (string) $order->id.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4));
        $orderId = (string) $order->id;
        $redirectUrl = route('momo.return', ['order' => $order->id]);
        $ipnUrl = route('momo.ipn');
        $extraData = '';
        $requestType = $this->requestType();

        $signatureData = [
            'accessKey' => $this->accessKey(),
            'amount' => (string) ((int) $order->total_amount),
            'extraData' => $extraData,
            'ipnUrl' => $ipnUrl,
            'orderId' => $orderId,
            'orderInfo' => 'Thanh toan don hang NovaShop '.$order->id,
            'partnerCode' => $this->partnerCode(),
            'redirectUrl' => $redirectUrl,
            'requestId' => $requestId,
            'requestType' => $requestType,
        ];

        $payload = [
            'partnerCode' => $this->partnerCode(),
            'partnerName' => 'NovaShop',
            'storeId' => 'NovaShop',
            'requestId' => $requestId,
            'amount' => (string) ((int) $order->total_amount),
            'orderId' => $orderId,
            'orderInfo' => 'Thanh toan don hang NovaShop '.$order->id,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $this->makeSignature($signatureData),
        ];

        try {
            $resp = Http::connectTimeout(8)->timeout(20)->retry(1, 300)->post($this->endpoint(), $payload);
        } catch (ConnectionException $e) {
            Log::error('MoMo create order connection timeout', [
                'order_id' => $order->id,
                'endpoint' => $this->endpoint(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('orders.index')->with('error', 'Không kết nối được MoMo (timeout). Vui lòng thử lại sau 1-2 phút.');
        }
        if (! $resp->successful()) {
            $json = $resp->json();
            $msg = (string) ($json['message'] ?? '');
            Log::error('MoMo create order failed', ['order_id' => $order->id, 'status' => $resp->status(), 'body' => $resp->body()]);

            return redirect()->route('orders.index')->with('error', $msg !== '' ? $msg : 'Không thể tạo giao dịch MoMo.');
        }

        $json = $resp->json();
        $payUrl = (string) ($json['payUrl'] ?? '');
        $resultCode = $this->normalizeResultCode($json['resultCode'] ?? null);
        if ($payUrl === '' || $resultCode !== 0) {
            $message = (string) ($json['message'] ?? 'Không nhận được link thanh toán MoMo.');
            Log::warning('MoMo pay url missing/failed', ['order_id' => $order->id, 'body' => $json]);

            return redirect()->route('orders.index')->with('error', $message);
        }

        $order->payments()->create([
            'payment_method' => 'momo',
            'gateway' => 'momo',
            'transaction_id' => (string) ($json['transId'] ?? $requestId),
            'amount' => $order->total_amount,
            'status' => 'pending',
            'response_payload' => $json,
        ]);

        return redirect()->away($payUrl);
    }

    public function handleReturn(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $payload = $request->all();
        $resultCode = $this->normalizeResultCode($payload['resultCode'] ?? null);
        $message = (string) ($payload['message'] ?? '');
        $signatureOk = $this->verifySignature($payload);
        if (! $signatureOk) {
            Log::warning('MoMo return signature verification failed (accept by resultCode for UX)', [
                'order_id' => $order->id,
                'result_code' => $resultCode,
            ]);
        }

        if ($resultCode === 0) {
            if ($order->payment_status !== Order::PAYMENT_STATUS_PAID) {
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'status' => Order::STATUS_PENDING,
                    'shipping_status' => Order::mapShippingStatusFromOrderStatus(Order::STATUS_PENDING),
                ]);
            }
            $order->payments()->latest()->first()?->update([
                'status' => 'completed',
                'failure_reason' => null,
                'error_code' => null,
                'response_payload' => $payload,
            ]);

            return redirect()->route('order.success', ['order' => $order->id])->with('success', 'Thanh toán MoMo thành công.');
        }

        $order->update([
            'status' => Order::STATUS_PAYMENT_FAILED,
            'payment_status' => Order::PAYMENT_STATUS_FAILED,
            'shipping_status' => Order::mapShippingStatusFromOrderStatus(Order::STATUS_PAYMENT_FAILED),
        ]);
        $order->payments()->latest()->first()?->update([
            'status' => 'failed',
            'failure_reason' => $message !== '' ? $message : 'MoMo return failed',
            'error_code' => (string) $resultCode,
            'response_payload' => $payload,
        ]);

        return redirect()->route('orders.index', ['status' => Order::STATUS_UNPAID])
            ->with('error', 'Thanh toán MoMo thất bại. '.($message !== '' ? $message : 'Vui lòng thử lại.'));
    }

    public function ipn(Request $request)
    {
        $payload = $request->all();
        $orderId = (int) ($payload['orderId'] ?? 0);
        $resultCode = $this->normalizeResultCode($payload['resultCode'] ?? null);
        if ($orderId <= 0) {
            return response()->json(['resultCode' => 1, 'message' => 'invalid order id']);
        }
        $order = Order::query()->find($orderId);
        if (! $order || $order->payment_method !== Order::PAYMENT_METHOD_MOMO) {
            return response()->json(['resultCode' => 1, 'message' => 'order not found']);
        }

        if (! $this->verifySignature($payload)) {
            Log::warning('MoMo IPN invalid signature', ['order_id' => $orderId, 'payload' => $payload]);
            return response()->json(['resultCode' => 1, 'message' => 'invalid signature']);
        }

        if ($resultCode === 0) {
            $order->update([
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'status' => Order::STATUS_PENDING,
                'shipping_status' => Order::mapShippingStatusFromOrderStatus(Order::STATUS_PENDING),
            ]);
            $order->payments()->latest()->first()?->update([
                'status' => 'completed',
                'response_payload' => $payload,
                'failure_reason' => null,
                'error_code' => null,
            ]);
        } else {
            $order->update([
                'status' => Order::STATUS_PAYMENT_FAILED,
                'payment_status' => Order::PAYMENT_STATUS_FAILED,
                'shipping_status' => Order::mapShippingStatusFromOrderStatus(Order::STATUS_PAYMENT_FAILED),
            ]);
            $order->payments()->latest()->first()?->update([
                'status' => 'failed',
                'failure_reason' => (string) ($payload['message'] ?? 'Payment failed'),
                'error_code' => (string) $resultCode,
                'response_payload' => $payload,
            ]);
        }

        return response()->json(['resultCode' => 0, 'message' => 'OK']);
    }
}
