<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class ShippingFeeService
{
    /** Bán kính Trái Đất (km). */
    private const EARTH_RADIUS_KM = 6371;

    /**
     * Khoảng cách giữa hai điểm (Haversine), đơn vị km.
     */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);
        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;
        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round((self::EARTH_RADIUS_KM * $c), 2);
    }

    /**
     * Tính phí ship (VNĐ) và khoảng cách (km) từ kho đến địa chỉ giao.
     * Trả về ['fee' => int, 'distance_km' => float].
     * Nếu không có tọa độ: dùng phí mặc định, distance_km = null.
     */
    public static function calculate(?float $deliveryLat, ?float $deliveryLng): array
    {
        $config = Config::get('shipping', []);
        $warehouse = $config['warehouse'] ?? ['lat' => 10.762622, 'lng' => 106.660172];
        $tiers = $config['tiers'] ?? [];
        $defaultFee = (int) ($config['default_fee_when_no_coordinates'] ?? 25000);

        if ($deliveryLat === null || $deliveryLng === null) {
            return ['fee' => $defaultFee, 'distance_km' => null];
        }

        $km = self::distanceKm(
            (float) $warehouse['lat'],
            (float) $warehouse['lng'],
            (float) $deliveryLat,
            (float) $deliveryLng
        );

        $fee = $defaultFee;
        foreach ($tiers as $tier) {
            if ($km <= (float) $tier['max_km']) {
                $fee = (int) $tier['fee'];
                break;
            }
        }

        return ['fee' => $fee, 'distance_km' => $km];
    }
}
