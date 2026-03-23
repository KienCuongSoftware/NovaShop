<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'label' => $a->label,
                'full_name' => $a->full_name,
                'phone' => $a->phone,
                'full_address' => $a->full_address,
                'lat' => $a->lat !== null ? (float) $a->lat : null,
                'lng' => $a->lng !== null ? (float) $a->lng : null,
                'is_default' => (bool) $a->is_default,
            ]);

        return response()->json(['data' => $addresses]);
    }
}
