<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReviewImage extends Model
{
    protected $fillable = [
        'product_review_id',
        'path',
        'sort',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class);
    }
}

