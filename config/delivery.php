<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dự đoán ngày nhận hàng (theo khoảng cách km đã lưu trên đơn)
    |--------------------------------------------------------------------------
    | Công thức gợi ý: xử lý kho (min–max ngày) + vận chuyển ≈ ceil(km / km_per_day),
    | giới hạn max_transit_days, thêm buffer max cho khoảng hiển thị.
    */

    'processing_days_min' => (int) env('DELIVERY_PROCESSING_MIN', 1),
    'processing_days_max' => (int) env('DELIVERY_PROCESSING_MAX', 2),

    /** Số km “ước lượng” cho 1 ngày vận chuyển (càng nhỏ → dự báo càng dài). */
    'km_per_day' => (float) env('DELIVERY_KM_PER_DAY', 45),

    'max_transit_days' => (int) env('DELIVERY_MAX_TRANSIT_DAYS', 8),

    /** Cộng thêm vào ngày cuối của khoảng (dự phòng). */
    'buffer_days' => (int) env('DELIVERY_BUFFER_DAYS', 1),

    /** Km giả định khi chưa có tọa độ (chỉ để hiển thị khoảng ngày trên trang sản phẩm). */
    'preview_assumed_km' => (float) env('DELIVERY_PREVIEW_ASSUMED_KM', 15),
];
