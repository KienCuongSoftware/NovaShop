<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationOtpMail;
use App\Models\User;
use App\Services\UserInitialsAvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showRegistrationForm()
    {
        return view('user.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Vui lòng nhập tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'avatar_palette_index' => UserInitialsAvatarService::randomPaletteIndex(),
        ]);

        Auth::login($user);
        $this->sendOtp($user);

        return redirect()
            ->route('verification.otp.notice')
            ->with('success', 'Đăng ký thành công. Mã OTP đã được gửi đến email của bạn.');
    }

    public function showLoginForm()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user) {
            return ($user->is_admin ?? false)
                ? redirect()->route('admin.dashboard')
                : redirect()->route('welcome');
        }

        return view('user.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
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

            if ($user && ($user->is_admin ?? false)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')
                    ->withErrors(['email' => 'Tài khoản quản trị vui lòng đăng nhập tại trang admin.']);
            }

            if ($user && ! $user->hasVerifiedEmail()) {
                $this->sendOtp($user);

                return redirect()
                    ->route('welcome')
                    ->with('success', 'Đăng nhập thành công. Vui lòng xác thực email để dùng đầy đủ tính năng.');
            }

            return redirect()->intended(route('welcome'))
                ->with('success', 'Đăng nhập thành công.');
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Đã đăng xuất.');
    }

    public function showVerifyOtpForm(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('welcome');
        }

        return view('user.auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('welcome');
        }

        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Vui lòng nhập mã OTP.',
            'otp.digits' => 'Mã OTP gồm đúng 6 chữ số.',
        ]);

        if (! $user->email_verification_otp || ! $user->email_verification_otp_expires_at || now()->gt($user->email_verification_otp_expires_at)) {
            return back()->withErrors([
                'otp' => 'Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.',
            ]);
        }

        if (! hash_equals((string) $user->email_verification_otp, (string) $data['otp'])) {
            return back()->withErrors([
                'otp' => 'Mã OTP không đúng.',
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_otp' => null,
            'email_verification_otp_expires_at' => null,
        ])->save();

        return redirect()->route('welcome')->with('success', 'Xác thực email thành công.');
    }

    public function resendOtp(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('welcome');
        }

        $this->sendOtp($user);

        return back()->with('success', 'Đã gửi lại mã OTP mới.');
    }

    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');

        return $driver->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $googleUser = $driver->stateless()->user();
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::warning('Google OAuth InvalidStateException', ['message' => $e->getMessage()]);

            return redirect()->route('login')->with('error', 'Phiên đăng nhập Google đã hết hạn. Vui lòng thử lại.');
        } catch (\Throwable $e) {
            Log::error('Google OAuth error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->with('error', 'Không thể đăng nhập với Google.');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            if ($user->is_blocked ?? false) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Tài khoản đã bị khóa.',
                ]);
            }
            if (! $user->hasVerifiedEmail()) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            Auth::login($user, true);

            return redirect()->intended(route('welcome'))->with('success', 'Đăng nhập thành công.');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            if ($user->is_blocked ?? false) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Tài khoản đã bị khóa.',
                ]);
            }
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?: now(),
                'email_verification_otp' => null,
                'email_verification_otp_expires_at' => null,
            ])->save();
            if ($googleUser->getAvatar() && ! $user->avatar) {
                $this->storeGoogleAvatar($user, $googleUser->getAvatar());
            }
            Auth::login($user, true);

            return redirect()->intended(route('welcome'))->with('success', 'Đăng nhập thành công.');
        }

        $user = User::create([
            'name' => $googleUser->getName() ?: $googleUser->getEmail(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'email_verified_at' => now(),
            'password' => null,
        ]);

        if ($googleUser->getAvatar()) {
            $this->storeGoogleAvatar($user, $googleUser->getAvatar());
        }

        Auth::login($user, true);

        return redirect()->intended(route('welcome'))->with('success', 'Đăng ký và đăng nhập thành công.');
    }

    protected function sendOtp(User $user): void
    {
        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(10);
        $user->forceFill([
            'email_verification_otp' => $otp,
            'email_verification_otp_expires_at' => $expiresAt,
        ])->save();

        Mail::to($user->email)->send(new EmailVerificationOtpMail(
            name: $user->name,
            otp: $otp,
            expiresAt: $expiresAt->format('H:i d/m/Y'),
        ));
    }

    protected function storeGoogleAvatar(User $user, string $avatarUrl): void
    {
        try {
            $image = file_get_contents($avatarUrl);
            if ($image === false) {
                return;
            }
            $ext = pathinfo(parse_url($avatarUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'avatar_'.$user->id.'_'.time().'.'.($ext ?: 'jpg');
            $path = 'avatars/'.$filename;
            Storage::disk('public')->put($path, $image);
            $user->update(['avatar' => $path]);
        } catch (\Throwable $e) {
            // Bỏ qua nếu không tải được avatar
        }
    }
}
