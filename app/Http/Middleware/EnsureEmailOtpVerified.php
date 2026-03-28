<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailOtpVerified
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ($user->is_admin ?? false)) {
            return $next($request);
        }
        if (! $user || $user->email_verified_at) {
            return $next($request);
        }

        if ($request->routeIs('verification.otp.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()
            ->route('verification.otp.notice')
            ->with('error', 'Vui lòng xác thực email bằng mã OTP để tiếp tục.');
    }
}
