<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $addresses = $user->addresses()->orderByDesc('is_default')->orderBy('id')->get();
        return view('user.addresses.index', compact('addresses'));
    }

    public function create()
    {
        return view('user.addresses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ], [
            'address.required' => 'Vui lòng chọn địa chỉ trên bản đồ hoặc tìm kiếm.',
            'lat.required' => 'Vui lòng chọn vị trí trên bản đồ.',
            'lng.required' => 'Vui lòng chọn vị trí trên bản đồ.',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isFirst = $user->addresses()->count() === 0;

        $user->addresses()->create([
            'label' => $validated['label'] ?: null,
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'address_line' => $validated['address'],
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'is_default' => $isFirst,
        ]);

        return redirect()->route('addresses.index')->with('success', 'Đã thêm địa chỉ.');
    }

    public function edit(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }
        return view('user.addresses.edit', compact('address'));
    }

    public function update(Request $request, Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'is_default' => 'boolean',
        ], [
            'address.required' => 'Vui lòng chọn địa chỉ trên bản đồ hoặc tìm kiếm.',
            'lat.required' => 'Vui lòng chọn vị trí trên bản đồ.',
            'lng.required' => 'Vui lòng chọn vị trí trên bản đồ.',
        ]);

        $address->update([
            'label' => $validated['label'] ?: null,
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'address_line' => $validated['address'],
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ]);

        if ($address->is_default) {
            /** @var \App\Models\User $authUser */
            $authUser = Auth::user();
            $authUser->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        return redirect()->route('addresses.index')->with('success', 'Đã cập nhật địa chỉ.');
    }

    public function destroy(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }
        $address->delete();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $first = $user->addresses()->first();
        if ($first && !$user->addresses()->where('is_default', true)->exists()) {
            $first->update(['is_default' => true]);
        }
        return redirect()->route('addresses.index')->with('success', 'Đã xóa địa chỉ.');
    }

    /** Đặt địa chỉ làm mặc định (AJAX). */
    public function setDefault(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        return response()->json(['ok' => true]);
    }
}
