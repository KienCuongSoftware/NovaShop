<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CategoryHierarchySeeder extends Seeder
{
    /**
     * Thêm danh mục con cho 10+ danh mục cha, gán đúng từng sản phẩm vào danh mục con tương ứng.
     * Dựa trên tên sản phẩm thực tế trong database.
     */
    public function run(): void
    {
        $this->command->info('Bắt đầu thêm danh mục con và gán sản phẩm...');

        // Chuyển sản phẩm từ danh mục con về danh mục cha trước khi xóa con
        $children = Category::whereNotNull('parent_id')->get();
        foreach ($children as $child) {
            Product::where('category_id', $child->id)->update(['category_id' => $child->parent_id]);
        }
        Category::whereNotNull('parent_id')->delete();

        // Đảm bảo tất cả danh mục cha có parent_id = null
        Category::whereIn('id', [16, 17, 18, 19, 20, 21, 22, 23, 24, 26, 27, 28, 29, 30, 31])->update(['parent_id' => null]);

        $mappings = $this->getProductToSubcategoryMappings();

        foreach ($mappings as $productId => $subcatName) {
            $product = Product::find($productId);
            if (!$product) continue;

            $parentId = $product->category_id;
            $parent = Category::find($parentId);
            if (!$parent) continue;

            $subcat = Category::firstOrCreate(
                ['name' => $subcatName, 'parent_id' => $parentId],
                ['name' => $subcatName, 'parent_id' => $parentId]
            );

            $product->update(['category_id' => $subcat->id]);
        }

        $this->command->info('Hoàn thành. Mỗi sản phẩm đã được gán vào danh mục con phù hợp.');
    }

    /**
     * Map product_id => tên danh mục con, dựa trên tên sản phẩm thực tế.
     */
    protected function getProductToSubcategoryMappings(): array
    {
        return [
            // 16 - Điện thoại & phụ kiện
            26 => 'Giá đỡ & phụ kiện điện thoại',
            27 => 'Ốp lưng điện thoại',
            28 => 'Kính cường lực',
            29 => 'Túi chống nước điện thoại',
            30 => 'Gậy chụp ảnh',
            31 => 'Case tai nghe',
            32 => 'Phụ kiện trang trí',
            33 => 'Phụ kiện sim & sạc',
            34 => 'Dây cáp & sạc',
            35 => 'Đèn selfie',

            // 17 - Thiết bị điện tử
            36 => 'Thiết bị điều khiển',
            37 => 'Loa bluetooth',
            38 => 'Phụ kiện gaming',
            39 => 'Thiết bị điều khiển',
            40 => 'Thiết bị làm mát',
            41 => 'Phụ kiện loa',
            42 => 'Loa bluetooth',
            43 => 'Máy trợ giảng',
            44 => 'Smart TV',
            45 => 'Loa bluetooth',

            // 18 - Máy tính & Laptop
            46 => 'Ổ cứng & lưu trữ',
            47 => 'Màn hình',
            48 => 'Laptop',
            49 => 'Router & mạng',
            50 => 'Ổ cứng & lưu trữ',
            51 => 'Máy chiếu',
            52 => 'Phụ kiện bàn phím chuột',
            53 => 'Bàn phím',
            54 => 'Máy in',
            55 => 'Bảng vẽ đồ họa',

            // 19 - Máy ảnh & máy quay phim
            56 => 'Ống kính',
            57 => 'Camera hành động',
            58 => 'Túi máy ảnh',
            59 => 'Đèn chiếu sáng',
            60 => 'Tủ chống ẩm',
            61 => 'Đèn Flash',
            62 => 'Camera an ninh',
            63 => 'Gimbal',
            64 => 'Đầu ghi hình',
            65 => 'Camera xe máy',

            // 20 - Đồng hồ
            66 => 'Đồng hồ nam',
            67 => 'Đồng hồ nữ',
            68 => 'Đồng hồ nữ',
            69 => 'Đồng hồ nam',
            70 => 'Phụ kiện đồng hồ',
            71 => 'Đồng hồ nữ',
            72 => 'Phụ kiện đồng hồ',
            73 => 'Đồng hồ nữ',
            74 => 'Đồng hồ nữ',
            75 => 'Đồng hồ nữ',

            // 21 - Giày dép nam
            76 => 'Phụ kiện giày dép',
            77 => 'Dép nam',
            78 => 'Phụ kiện giày dép',
            79 => 'Dép nam',
            80 => 'Phụ kiện giày dép',
            81 => 'Phụ kiện giày dép',
            82 => 'Giày thể thao nam',
            83 => 'Phụ kiện giày dép',
            84 => 'Giày bốt nam',
            85 => 'Giày lười nam',

            // 22 - Thiết bị điện gia dụng
            86 => 'Ấm đun nước',
            87 => 'Robot gia dụng',
            88 => 'Máy làm bếp',
            89 => 'Bàn ủi',
            90 => 'Máy xay thịt',
            91 => 'Máy hút bụi',
            92 => 'Máy xay',
            93 => 'Máy xay thịt',
            94 => 'Nồi điện',
            95 => 'Bếp từ',

            // 23 - Thể thao & du lịch
            96 => 'Ô & dù',
            97 => 'Túi du lịch',
            98 => 'Ô & dù',
            99 => 'Túi du lịch',
            100 => 'Dụng cụ thể thao',
            101 => 'Dụng cụ thể thao',
            102 => 'Phụ kiện thể thao',
            103 => 'Túi du lịch',
            104 => 'Gối & phụ kiện du lịch',
            105 => 'Cần câu & phụ kiện',

            // 24 - Ô tô & xe máy & xe đạp
            106 => 'Phụ kiện xe máy',
            107 => 'Phụ kiện mũ bảo hiểm',
            108 => 'Khăn lau ô tô',
            109 => 'Đèn LED xe',
            110 => 'Trang trí nội thất xe',
            111 => 'Phụ kiện ô tô',
            112 => 'Mũ bảo hiểm',
            113 => 'Gá điện thoại xe',
            114 => 'Thảm & phụ kiện ô tô',
            115 => 'Gá điện thoại ô tô',

            // 26 - Thời trang nữ
            116 => 'Tất nữ',
            117 => 'Len sợi',
            118 => 'Áo thun nữ',
            119 => 'Quần nữ',
            120 => 'Tất nữ',
            121 => 'Váy thể thao nữ',
            122 => 'Đồ lót nữ',
            123 => 'Váy nữ',
            124 => 'Đầm nữ',
            125 => 'Đồ thể thao nữ',

            // 27 - Mẹ & bé
            126 => 'Sữa bột công thức',
            127 => 'Ghế ô tô cho bé',
            128 => 'Máy rửa bình sữa',
            129 => 'Máy hút sữa',
            130 => 'Xe đẩy',
            131 => 'Giường ngủ cho bé',
            132 => 'Đồ chơi & kệ để đồ',
            133 => 'Ghế ô tô cho bé',
            134 => 'Nệm chăn gối cho bé',
            135 => 'Gối chống trào ngược',

            // 28 - Nhà cửa & đời sống
            136 => 'Tủ & kệ',
            137 => 'Bàn ghế',
            138 => 'Thảm trải sàn',
            139 => 'Ghế sofa',
            140 => 'Ghế văn phòng',
            141 => 'Thùng rác',
            142 => 'Đồ dùng nhà bếp',
            143 => 'Chảo & nồi',
            144 => 'Đồ trang trí',
            145 => 'Đồ trang trí',

            // 29 - Sắc đẹp
            146 => 'Thiết bị làm đẹp',
            147 => 'Kem dưỡng da',
            148 => 'Nước hoa',
            149 => 'Dụng cụ tóc',
            150 => 'Nước hoa',
            151 => 'Kem dưỡng da',
            152 => 'Thiết bị massage chân',
            153 => 'Kem ủ tóc',
            154 => 'Máy tăm nước',
            155 => 'Trang điểm',

            // 30 - Sức khỏe
            156 => 'Máy massage',
            157 => 'Thực phẩm chức năng',
            158 => 'Đai hỗ trợ lưng',
            159 => 'Thiết bị trị liệu',
            160 => 'Xe lăn',
            161 => 'Khẩu trang',
            162 => 'Bông & vật tư y tế',
            163 => 'Nút bịt tai',
            164 => 'Găng tay y tế',
            165 => 'Phụ kiện cá nhân',

            // 31 - Giày dép nữ
            166 => 'Giày cao gót nữ',
            167 => 'Giày da nữ',
            168 => 'Giày cao gót nữ',
            169 => 'Giày cao gót nữ',
            170 => 'Giày bốt nữ',
            171 => 'Giày cao gót nữ',
            172 => 'Giày đế bằng nữ',
            173 => 'Giày cao gót nữ',
            174 => 'Giày sục nữ',
            175 => 'Giày cao gót nữ',
        ];
    }
}
