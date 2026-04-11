<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, MustVerifyEmail, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'birthday',
        'avatar',
        'avatar_palette_index',
        'password',
        'google_id',
        'is_admin',
        'is_vip',
        'is_blocked',
        'email_verification_otp',
        'email_verification_otp_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_vip' => 'boolean',
            'is_blocked' => 'boolean',
            'birthday' => 'date',
            'email_verification_otp_expires_at' => 'datetime',
            'avatar_palette_index' => 'integer',
        ];
    }

    /**
     * Query-string version for browser/CDN cache busting when name or palette changes.
     */
    public function initialsAvatarVersion(): string
    {
        return substr(hash('sha256', $this->name.'|'.($this->avatar_palette_index ?? '').'|'.$this->id), 0, 12);
    }

    public function initialsAvatarUrl(): string
    {
        return route('avatars.initials', [
            'user' => $this->id,
            'v' => $this->initialsAvatarVersion(),
        ]);
    }

    /**
     * Sinh nhật (năm chỉ để lấy tháng/ngày): hôm nay nằm trong ±$plusMinusDays ngày quanh kỷ niệm trong năm gần nhất.
     */
    public function isWithinBirthdayCouponWindow(int $plusMinusDays): bool
    {
        if ($plusMinusDays < 0 || ! $this->birthday) {
            return false;
        }

        $birth = \Carbon\Carbon::parse($this->birthday)->startOfDay();
        $month = (int) $birth->month;
        $day = (int) $birth->day;
        $today = now()->startOfDay();

        foreach ([(int) $today->year - 1, (int) $today->year, (int) $today->year + 1] as $year) {
            $useDay = $day;
            if ($month === 2 && $day === 29) {
                $useDay = min($day, (int) \Carbon\Carbon::create($year, 2, 1)->endOfMonth()->day);
            }
            $anniversary = \Carbon\Carbon::create($year, $month, $useDay)->startOfDay();
            $from = $anniversary->copy()->subDays($plusMinusDays);
            $to = $anniversary->copy()->addDays($plusMinusDays);
            if ($today->gte($from) && $today->lte($to)) {
                return true;
            }
        }

        return false;
    }

    public function cart(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function productReviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ProductReview::class, 'user_id');
    }

    public function wishlistItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function compareItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CompareItem::class)->orderBy('sort_order');
    }

    public function stockNotificationSubscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockNotificationSubscription::class);
    }

    public function aiChatMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AiChatMessage::class)->orderBy('id');
    }
}
