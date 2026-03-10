<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    public function index()
    {
        // Admin không vào được trang welcome — chuyển về trang quản trị
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $products = Product::with('category')->latest()->paginate(12);
        return view('welcome', compact('products'));
    }

    public function search(Request $request)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $q = trim((string) $request->input('q', ''));
        $products = Product::with('category')
            ->when($q !== '', function ($query) use ($q) {
                $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
                $query->where(function ($qry) use ($esc, $q) {
                    $qry->where('name', 'like', $esc . ' %')
                        ->orWhere('name', 'like', '% ' . $esc . ' %')
                        ->orWhere('name', 'like', '% ' . $esc)
                        ->orWhere('name', $q)
                        ->orWhere(function ($sub) use ($esc, $q) {
                            $sub->whereNotNull('description')
                                ->where(function ($d) use ($esc, $q) {
                                    $d->where('description', 'like', $esc . ' %')
                                        ->orWhere('description', 'like', '% ' . $esc . ' %')
                                        ->orWhere('description', 'like', '% ' . $esc)
                                        ->orWhere('description', $q);
                                });
                        });
                });
            })
            ->oldest()
            ->paginate(12)
            ->withQueryString();

        return view('welcome', compact('products', 'q'));
    }
}
