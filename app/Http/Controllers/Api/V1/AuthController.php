<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:120',
        ]);

        $existing = User::where('email', $credentials['email'])->first();
        // Tài khoản tạo / liên kết Google thường không có mật khẩu → web vẫn vào được, API token thì không.
        if ($existing && $existing->getRawOriginal('password') === null) {
            throw ValidationException::withMessages([
                'email' => ['Tài khoản chưa có mật khẩu (thường là đăng ký qua Google). Đăng nhập site chính bằng Google, rồi vào Hồ sơ → đặt mật khẩu để dùng đăng nhập SPA.'],
            ]);
        }

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không đúng.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        if ($user->is_admin) {
            Auth::logout();

            return response()->json([
                'message' => 'Tài khoản admin không dùng đăng nhập token SPA. Dùng trang quản trị web.',
            ], 403);
        }

        $user->tokens()->where('name', 'spa')->delete();
        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
