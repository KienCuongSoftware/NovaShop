<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Điểm lấy hàng (kho / cửa hàng) - dùng để tính khoảng cách giao hàng
    |--------------------------------------------------------------------------
    */
    'warehouse' => [
        'lat' => (float) env('SHIPPING_WAREHOUSE_LAT', 21.028511),
        'lng' => (float) env('SHIPPING_WAREHOUSE_LNG', 105.854204),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bảng phí ship theo khoảng cách (km)
    | Mỗi mục: [ 'max_km' => km, 'fee' => VNĐ ]
    | Thứ tự tăng dần max_km. Áp dụng fee của tier có max_km >= khoảng cách thực tế.
    |--------------------------------------------------------------------------
    */
    'tiers' => [
        ['max_km' => 3, 'fee' => 15000],
        ['max_km' => 5, 'fee' => 20000],
        ['max_km' => 10, 'fee' => 25000],
        ['max_km' => 20, 'fee' => 35000],
        ['max_km' => 50, 'fee' => 50000],
        ['max_km' => 100, 'fee' => 70000],
        ['max_km' => 9999, 'fee' => 100000],
    ],

    /*
    | Phí mặc định khi không có tọa độ (địa chỉ cũ không có lat/lng)
    */
    'default_fee_when_no_coordinates' => 100000,
];
