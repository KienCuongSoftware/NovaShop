<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryLogController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type', '');
        $q = trim((string) $request->query('q', ''));

        $query = InventoryLog::with(['productVariant.product:id,name', 'productVariant.attributeValues.attribute', 'order:id'])
            ->latest();

        if ($type !== '' && in_array($type, ['import', 'export', 'adjust'], true)) {
            $query->where('type', $type);
        }

        if ($q !== '') {
            $esc = str_replace(['%', '_'], ['\\%', '\\_'], $q);
            $query->where(function ($qb) use ($esc, $q) {
                $qb->where('source', 'like', '%'.$esc.'%')
                    ->orWhere('note', 'like', '%'.$esc.'%')
                    ->orWhereHas('productVariant.product', fn ($p) => $p->where('name', 'like', '%'.$esc.'%'));
                if (is_numeric($q)) {
                    $qb->orWhere('order_id', (int) $q);
                }
            });
        }

        $logs = $query->paginate(7)->withQueryString();
        session(['staff.inventory_logs.page' => $logs->currentPage()]);

        return view('staff.inventory_logs.index', compact('logs', 'type', 'q'));
    }
}
