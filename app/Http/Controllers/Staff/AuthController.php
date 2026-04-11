<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user) {
            if ($user->is_admin ?? false) {
                return redirect()->route('admin.dashboard');
            }
            if ($user->is_staff ?? false) {
                return redirect()->route('staff.dashboard');
            }

            return redirect()->route('welcome');
        }

        return view('staff.auth.login');
    }

    public function login(Request $request)
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        if ($currentUser) {
            if ($currentUser->is_admin ?? false) {
                return redirect()->route('admin.dashboard');
            }
            if ($currentUser->is_staff ?? false) {
                return redirect()->route('staff.dashboard');
            }

            return redirect()->route('welcome');
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withErrors([
                'email' => 'Email hoặc mật khẩu không đúng.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && ($user->is_blocked ?? false)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Tài khoản đã bị khóa.',
            ])->onlyInput('email');
        }

        if (! $user || (! ($user->is_staff ?? false) && ! ($user->is_admin ?? false))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Tài khoản này không có quyền nhân viên hoặc quản trị.',
            ])->onlyInput('email');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill([
                'email_verified_at' => now(),
                'email_verification_otp' => null,
                'email_verification_otp_expires_at' => null,
            ])->save();
        }

        return redirect()->intended(route('staff.dashboard'))
            ->with('success', 'Đăng nhập nhân viên thành công.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login')
            ->with('success', 'Đã đăng xuất.');
    }
}
