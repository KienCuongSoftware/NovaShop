<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListShareItem extends Model
{
    protected $fillable = [
        'list_share_id',
        'product_id',
        'sort_order',
    ];

    public function share(): BelongsTo
    {
        return $this->belongsTo(ListShare::class, 'list_share_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}

