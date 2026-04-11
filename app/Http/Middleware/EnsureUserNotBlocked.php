<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if ($user && ($user->is_blocked ?? false)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()->route('admin.login')
                    ->withErrors(['email' => 'Tài khoản đã bị khóa.']);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Tài khoản đã bị khóa.']);
        }

        return $next($request);
    }
}
