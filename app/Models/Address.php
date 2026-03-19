<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'full_name',
        'phone',
        'province',
        'district',
        'ward',
        'address_line',
        'lat',
        'lng',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Địa chỉ đầy đủ một dòng (để hiển thị). */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line,
            $this->ward,
            $this->district,
            $this->province,
        ]);

        return implode(', ', $parts) ?: (string) $this->address_line;
    }

    /** Có tọa độ map (dùng cho tính phí ship). */
    public function hasCoordinates(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }
}
