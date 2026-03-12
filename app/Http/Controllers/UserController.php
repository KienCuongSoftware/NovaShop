<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::oldest()->paginate(7);
        session(['admin.users.page' => $users->currentPage()]);
        return view('admin.users.index', compact('users'));
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
            'is_admin' => 'nullable|boolean',
        ], [
            'name.required' => 'Vui lòng nhập tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'is_admin' => (bool) $request->boolean('is_admin'),
        ];
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
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_admin' => 'nullable|boolean',
        ], [
            'name.required' => 'Vui lòng nhập tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'is_admin' => (bool) $request->boolean('is_admin'),
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }
        $user->update($data);

        $page = session('admin.users.page', 1);
        return redirect()->route('admin.users.index', ['page' => $page])
            ->with('success', 'Đã cập nhật người dùng thành công.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        $page = session('admin.users.page', 1);
        return redirect()->route('admin.users.index', ['page' => $page])
            ->with('success', 'Đã xóa người dùng thành công.');
    }
}
