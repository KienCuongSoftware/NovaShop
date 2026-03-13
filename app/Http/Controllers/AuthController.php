<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
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
        ]);

        Auth::login($user);

        return redirect()->route('welcome')
            ->with('success', 'Đăng ký thành công.');
    }

    public function showLoginForm()
    {
        return view('auth.login');
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
            Auth::login($user, true);
            return redirect()->intended(route('welcome'))->with('success', 'Đăng nhập thành công.');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update(['google_id' => $googleUser->getId()]);
            if ($googleUser->getAvatar() && !$user->avatar) {
                $this->storeGoogleAvatar($user, $googleUser->getAvatar());
            }
            Auth::login($user, true);
            return redirect()->intended(route('welcome'))->with('success', 'Đăng nhập thành công.');
        }

        $user = User::create([
            'name' => $googleUser->getName() ?: $googleUser->getEmail(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password' => null,
        ]);

        if ($googleUser->getAvatar()) {
            $this->storeGoogleAvatar($user, $googleUser->getAvatar());
        }

        Auth::login($user, true);

        return redirect()->intended(route('welcome'))->with('success', 'Đăng ký và đăng nhập thành công.');
    }

    protected function storeGoogleAvatar(User $user, string $avatarUrl): void
    {
        try {
            $image = file_get_contents($avatarUrl);
            if ($image === false) {
                return;
            }
            $ext = pathinfo(parse_url($avatarUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . ($ext ?: 'jpg');
            $path = 'avatars/' . $filename;
            Storage::disk('public')->put($path, $image);
            $user->update(['avatar' => $path]);
        } catch (\Throwable $e) {
            // Bỏ qua nếu không tải được avatar
        }
    }
}
