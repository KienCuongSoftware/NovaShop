<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountBlockedMail;
use App\Models\User;
use App\Services\UserInitialsAvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $users = User::when($q !== '', function ($query) use ($q) {
            $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
            $query->where('name', 'like', '%'.$esc.'%')
                ->orWhere('email', 'like', '%'.$esc.'%');
        })
            ->oldest()
            ->paginate(7)
            ->withQueryString();
        session(['admin.users.page' => $users->currentPage()]);

        return view('admin.users.index', compact('users', 'q'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'birthday' => 'nullable|date',
            'is_admin' => 'nullable|boolean',
            'is_vip' => 'nullable|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'avatar.image' => 'File phải là hình ảnh.',
            'avatar.max' => 'Kích thước ảnh không được quá 2MB.',
        ]);

        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'is_admin' => (bool) $request->boolean('is_admin'),
            'is_vip' => (bool) $request->boolean('is_vip'),
            'avatar_palette_index' => UserInitialsAvatarService::randomPaletteIndex(),
        ];
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        User::create($data);

        $page = session('admin.users.page', 1);

        return redirect()->route('admin.users.index', ['page' => $page])
            ->with('success', 'Đã thêm người dùng thành công.');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'birthday' => 'nullable|date',
            'is_admin' => 'nullable|boolean',
            'is_vip' => 'nullable|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'avatar.image' => 'File phải là hình ảnh.',
            'avatar.max' => 'Kích thước ảnh không được quá 2MB.',
        ]);

        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'birthday' => $request->filled('birthday') ? $request->input('birthday') : null,
            'is_admin' => (bool) $request->boolean('is_admin'),
            'is_vip' => (bool) $request->boolean('is_vip'),
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        $user->update($data);

        $page = session('admin.users.page', 1);

        return redirect()->route('admin.users.index', ['page' => $page])
            ->with('success', 'Đã cập nhật người dùng thành công.');
    }

    public function toggleBlocked(Request $request, User $user)
    {
        $actor = $request->user();
        if ($actor && $actor->id === $user->id) {
            return back()->with('error', 'Bạn không thể khóa chính mình.');
        }

        $wasBlocked = (bool) $user->is_blocked;
        $user->is_blocked = ! $wasBlocked;
        $user->save();

        if ($user->is_blocked && ! $wasBlocked) {
            $mailOk = false;
            $mailError = null;
            $to = trim((string) $user->email);
            if ($to === '') {
                Log::warning('Account blocked: user has no email', ['user_id' => $user->id]);
                $mailError = 'empty_email';
            } else {
                try {
                    Mail::to($to)->send(new AccountBlockedMail($user->name, $to));
                    $mailOk = true;
                } catch (\Throwable $e) {
                    Log::error('Account blocked email failed', [
                        'user_id' => $user->id,
                        'email' => $to,
                        'message' => $e->getMessage(),
                    ]);
                    $mailError = $e->getMessage();
                }
            }

            $msg = 'Đã chặn tài khoản. Người dùng không thể đăng nhập.';
            if ($mailOk) {
                $driver = (string) config('mail.default');
                $msg .= ' Đã xử lý gửi thông báo tới '.$to.'.';
                if (in_array($driver, ['log', 'array'], true)) {
                    $msg .= ' Bạn đang dùng MAIL_MAILER='.$driver.' — email không đến hộp thư mà chỉ ghi trong storage/logs/laravel.log (hoặc bộ nhớ). Đặt MAIL_MAILER=smtp và cấu hình Gmail/SMTP trong .env để gửi thật.';
                }
            } elseif ($mailError === 'empty_email') {
                $msg .= ' Không gửi được email: tài khoản không có địa chỉ email.';
            } else {
                $msg .= ' Gửi email thất bại. Kiểm tra .env (MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS) và xem chi tiết trong storage/logs/laravel.log.';
            }

            return back()->with('success', $msg);
        }

        return back()->with('success', 'Đã bỏ chặn tài khoản.');
    }
}
