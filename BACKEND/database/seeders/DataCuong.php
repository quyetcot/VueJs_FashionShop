<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Tag;
use App\Models\Role;
use App\Models\User;
use App\Models\Brand;
use App\Models\Address;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\VoucherMeta;
use Faker\Factory as Faker;
use App\Models\AttributeItem;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DataCuong extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        // thêm mới attribute
        Attribute::query()->insert(
            [
                [
                    "name" => "size",
                    "slug" => 'size'
                ],
                [
                    "name" => "color",
                    "slug" => 'color'
                ],
                [
                    "name" => "material",
                    "slug" => 'material'
                ],
            ]
        );
        // create attribute_item
        AttributeItem::query()->insert([
            [
                "attribute_id" => 1,
                "value" => "S",
                "slug" => "S",
            ],
            [
                "attribute_id" => 1,
                "value" => "M",
                "slug" => "M",
            ],
            [
                "attribute_id" => 1,
                "value" => "L",
                "slug" => "L",
            ],
            [
                "attribute_id" => 2,
                "value" => "BLUE",
                "slug" => "BLUE",
            ],
            [
                "attribute_id" => 2,
                "value" => "RED",
                "slug" => "RED",
            ],
            [
                "attribute_id" => 2,
                "value" => "BLACK",
                "slug" => "BLACK",
            ],
            [
                "attribute_id" => 3,
                "value" => "COTTON",
                "slug" => "COTTON",
            ],
            [
                "attribute_id" => 3,
                "value" => "KAKI",
                "slug" => "KAKI",
            ],
            [
                "attribute_id" => 3,
                "value" => "JEAN",
                "slug" => "JEAN",
            ],
        ]);
        // CREATE tag
        Tag::query()->insert([
            [
                "name" => "Hot Trend",
                "slug" => "hot-trend",
            ],
            [
                "name" => "Nổi Bật",
                "slug" => "noi-bat",
            ],
            [
                "name" => "Giảm Giá",
                "slug" => "giam-gia",
            ],
            [
                "name" => "Mới Nhất",
                "slug" => "moi-nhat",
            ],
            [
                "name" => "Bán Chạy",
                "slug" => "ban-chay",
            ],
        ]);
        Category::query()->insert(
            [
                [
                    "name" => "Thời trang nam",
                    "slug" => "thoi-trang-nam",
                    "description" => "Các sản phẩm thời trang dành cho nam giới, từ áo quần cho đến giày dép.",
                    "parent_id" => null,
                    "img_thumbnail" => "thoi-trang-nam.jpg",
                ],
                [
                    "name" => "Áo sơ mi nam",
                    "slug" => "ao-so-mi-nam",
                    "description" => "Bộ sưu tập áo sơ mi nam với nhiều kiểu dáng và màu sắc khác nhau.",
                    "parent_id" => 1,
                    "img_thumbnail" => "ao-so-mi-nam.jpg",
                ],
                [
                    "name" => "Áo khoác nam",
                    "slug" => "ao-khoac-nam",
                    "description" => "Các loại áo khoác dành cho nam, bao gồm áo khoác nhẹ và áo khoác mùa đông.",
                    "parent_id" => 1,
                    "img_thumbnail" => "ao-khoac-nam.jpg",
                ],
                [
                    "name" => "Giày dép nam",
                    "slug" => "giay-dep-nam",
                    "description" => "Đa dạng các mẫu giày dép nam, từ giày thể thao đến giày da công sở.",
                    "parent_id" => null,
                    "img_thumbnail" => "giay-dep-nam.jpg",
                ],
                [
                    "name" => "Giày thể thao nam",
                    "slug" => "giay-the-thao-nam",
                    "description" => "Bộ sưu tập giày thể thao nam phù hợp cho mọi hoạt động thể thao và giải trí.",
                    "parent_id" => 4,
                    "img_thumbnail" => "giay-the-thao-nam.jpg",
                ],
                [
                    "name" => "Giày công sở nam",
                    "slug" => "giay-cong-so-nam",
                    "description" => "Giày công sở nam cao cấp, thích hợp cho các buổi họp, gặp gỡ đối tác.",
                    "parent_id" => 4,
                    "img_thumbnail" => "giay-cong-so-nam.jpg",
                ],
            ]
        );

        // THÊM MỚI USER and role
        Role::query()->insert([
            ["name" => "client"],
            ["name" => "membership"],
            ["name" => "shipper"],
            ["name" => "admin"],
        ]);
        User::query()->insert([
            [
                "role_id" => 1,
                'name' => 'khách hàng 1',
                'email' => 'cuongnmph38402@fpt.edu.vn',
                'password' => Hash::make("12345678"),
                "phone_number" => "0987654321",
                'address' => "hà nội",
                'email_verified_at' => now(),
            ]
        ]);
        for ($i = 0; $i < 3; $i++) {
            Address::create([
                'user_id'   => 1,
                'label'     => $faker->randomElement(['Home', 'Office', 'Other']),
                'address'   => $faker->streetAddress,
                'city'      => $faker->city,
                'district'  => $faker->state,
                'ward'      => $faker->streetName,
                'phone'     => $faker->phoneNumber,
                'is_default' => $i === 0, // Địa chỉ đầu tiên là mặc định
            ]);
        }
        // thêm dữ liệu brands
        Brand::query()->insert([
            [
                "name" => "DIOR",
                'slug' => 'dior',
                'image' => 'dior.jpg',
                'email' => 'dior@gmail.com',
                'phone_number' => "0987654321",
                "address" => "hà nội"
            ],
            [
                "name" => "CHANEL",
                'slug' => 'CHANEL',
                'image' => 'CHANEL.jpg',
                'email' => 'CHANEL@2gmail.com',
                'phone_number' => "0987654322",
                "address" => "hà nội"
            ],
        ]);
        PaymentMethod::query()->insert([
            [
                "name" => "COD",
                "description" => "Nhận hàng thanh toán",
            ],
            [
                "name" => "VNPAY",
                "description" => "Thanh toán online",
            ],
        ]);
        // Tạo voucher
        Voucher::query()->insert([
            [
                "id" => 1,
                "title" => "Giảm giá danh mục sản phẩm",
                "description" => "Giảm giá áp dụng cho các danh mục sản phẩm đã chọn.",
                "code" => "CATSALE30",
                "discount_type" => "fixed",
                "discount_value" => "30000.00",
                "start_date" => Carbon::now(),
                "end_date" => Carbon::now()->addMonth(),
                "min_order_value" => "100000.00",
                "usage_limit" => 100,
                "used_count" => 8,
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => 2,
                "title" => "Giảm 10% toàn bộ đơn hàng",
                "description" => "Giảm giá 10% áp dụng cho tổng giá trị đơn hàng.",
                "code" => "SALE10OFF",
                "discount_type" => "percent",
                "discount_value" => "10.00",
                "start_date" => Carbon::now(),
                "end_date" => Carbon::now()->addMonth(),
                "min_order_value" => "0.00",
                "usage_limit" => 200,
                "used_count" => 20,
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => 3,
                "title" => "Giảm giá sản phẩm cụ thể",
                "description" => "Giảm giá áp dụng cho các sản phẩm được chỉ định.",
                "code" => "PROD20OFF",
                "discount_type" => "fixed",
                "discount_value" => "20000.00",
                "start_date" => Carbon::now(),
                "end_date" => Carbon::now()->addMonth(),
                "min_order_value" => "50000.00",
                "usage_limit" => 50,
                "used_count" => 5,
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "id" => 4,
                "title" => "Voucher giới hạn giảm giá tối đa",
                "description" => "Giảm giá áp dụng với giới hạn số tiền giảm tối đa.",
                "code" => "MAX100K",
                "discount_type" => "percent",
                "discount_value" => "15.00",
                "start_date" => "2024-11-10 00:00:00",
                "end_date" => "2024-12-31 23:59:59",
                "min_order_value" => "200000.00",
                "usage_limit" => 150,
                "used_count" => 10,
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);
        VoucherMeta::query()->insert([
            // Voucher cho danh mục sản phẩm
            [
                "voucher_id" => 1,
                "meta_key" => "_voucher_category_ids",
                "meta_value" => json_encode([1, 2, 3]), // Áp dụng cho các danh mục có ID 1, 2, 3
                "created_at" => now(),
                "updated_at" => now(),
            ],
            // Voucher không áp dụng cho danh mục sản phẩm
            [
                "voucher_id" => 1,
                "meta_key" => "_voucher_exclude_category_ids",
                "meta_value" => json_encode([4, 5]), // Loại trừ các danh mục có ID 4, 5
                "created_at" => now(),
                "updated_at" => now(),
            ],
            // Voucher áp dụng cho tổng đơn hàng
            [
                "voucher_id" => 2,
                "meta_key" => "_voucher_applies_to_total",
                "meta_value" => "true", // Áp dụng cho tổng đơn hàng
                "created_at" => now(),
                "updated_at" => now(),
            ],
            // Voucher cho sản phẩm cụ thể
            [
                "voucher_id" => 3,
                "meta_key" => "_voucher_product_ids",
                "meta_value" => json_encode([101, 102, 103]), // Áp dụng cho các sản phẩm có ID 101, 102, 103
                "created_at" => now(),
                "updated_at" => now(),
            ],
            // Voucher không áp dụng cho sản phẩm
            [
                "voucher_id" => 3,
                "meta_key" => "_voucher_exclude_product_ids",
                "meta_value" => json_encode([104, 105]), // Loại trừ các sản phẩm có ID 104, 105
                "created_at" => now(),
                "updated_at" => now(),
            ],
            // Voucher giới hạn số tiền giảm tối đa
            [
                "voucher_id" => 4,
                "meta_key" => "_voucher_max_discount_amount",
                "meta_value" => "100000", // Giảm tối đa 100.000 VNĐ
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);

        // Products: Giày công sở nam (simple), Giày thể thao nam (simple), Áo khoác nam (variant), Áo sơ mi nam (variant)
        // Simple Products
        $simpleProducts = [
            [
                'name' => 'Áo khoác nam',
                'sku' => 'sku-ao-khoac-don',
                'img_thumbnail' => 'aokhoac.png',
                'price_regular' => 300000,
                'price_sale' => 270000,
                'slug' => 'ao-khoac-nam-don',
                'category_id' => 3,
                'weight' => 45
            ],
            [
                'name' => 'Áo sơ mi nam',
                'sku' => 'sku-ao-so-mi-don',
                'img_thumbnail' => 'aosomi.png',
                'price_regular' => 250000,
                'price_sale' => 220000,
                'slug' => 'ao-so-mi-nam-don',
                'category_id' => 2,
                'weight' => 45
            ],
            [
                'name' => 'Giày công sở nam',
                'sku' => 'sku-giay-cong-so',
                'img_thumbnail' => 'giaycongso.png',
                'price_regular' => 500000,
                'price_sale' => 450000,
                'slug' => 'giay-cong-so-nam',
                'category_id' => 6,
                'weight' => 200
            ],
            [
                'name' => 'Giày thể thao nam',
                'sku' => 'sku-giay-the-thao',
                'img_thumbnail' => 'giaythethao.png',
                'price_regular' => 600000,
                'price_sale' => 550000,
                'slug' => 'giay-the-thao-nam',
                'category_id' => 5,
                'weight' => 200
            ],
        ];

        foreach ($simpleProducts as $product) {
            $productId = DB::table('products')->insertGetId([
                'brand_id' => 1,
                'category_id' => $product['category_id'],
                'type' => 0,
                'sku' => $product['sku'],
                'weight' => $product['weight'],
                'name' => $product['name'],
                'views' => 1,
                'img_thumbnail' => $product['img_thumbnail'],
                'price_regular' => $product['price_regular'],
                'price_sale' => $product['price_sale'],
                'quantity' => 100,
                'description' => $product['name'] . ' rất đẹp',
                'description_title' => $product['name'] . ' rất đẹp',
                'status' => 1,
                'is_show_home' => 1,
                'trend' => 1,
                'is_new' => 1,
                'slug' => $product['slug'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert product galleries
            DB::table('product_galleries')->insert([
                'product_id' => $productId,
                'image' => $product['img_thumbnail'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert product tags
            DB::table('product_tags')->insert([
                'product_id' => $productId,
                'tag_id' => 1,
            ]);
        }
        /*
        $variantProducts = [
            [
                'name' => 'Áo khoác nam',
                'sku' => 'sku-ao-khoac',
                'img_thumbnail' => 'aokhoac.png',
                'slug' => 'ao-khoac-nam',
                'variants' => [
                    [
                        'attribute_item_id' => [["id" => 1, "value" => "S"], ["id" => 7, "value" => "Cotton"]],
                        'price_regular' => 300000,
                        'price_sale' => 270000,
                        'quantity' => 5,
                        'sku' => 'sku-ao-khoac-s',
                    ],
                    [
                        'attribute_item_id' => [["id" => 2, "value" => "M"], ["id" => 7, "value" => "Cotton"]],
                        'price_regular' => 300000,
                        'price_sale' => 270000,
                        'quantity' => 5,
                        'sku' => 'sku-ao-khoac-m',
                    ],
                ],
            ],
            [
                'name' => 'Áo sơ mi nam',
                'sku' => 'sku-ao-so-mi',
                'img_thumbnail' => 'aosomi.png',
                'slug' => 'ao-so-mi-nam',
                'variants' => [
                    [
                        'attribute_item_id' => [["id" => 1, "value" => "S"], ["id" => 7, "value" => "Cotton"]],
                        'price_regular' => 250000,
                        'price_sale' => 220000,
                        'quantity' => 5,
                        'sku' => 'sku-ao-so-mi-s',
                    ],
                    [
                        'attribute_item_id' => [["id" => 2, "value" => "M"], ["id" => 7, "value" => "Cotton"]],
                        'price_regular' => 250000,
                        'price_sale' => 220000,
                        'quantity' => 5,
                        'sku' => 'sku-ao-so-mi-m',
                    ],
                ],
            ],
        ];
        foreach ($variantProducts as $product) {
            $productId = DB::table('products')->insertGetId([
                'brand_id' => 1,
                'category_id' => 4,
                'type' => 1,
                'sku' => $product['sku'],
                'name' => $product['name'],
                'views' => 1,
                'img_thumbnail' => $product['img_thumbnail'],
                'price_regular' => null,
                'price_sale' => null,
                'quantity' => null,
                'description' => $product['name'] . ' rất đẹp',
                'description_title' => $product['name'] . ' rất đẹp',
                'status' => 1,
                'is_show_home' => 1,
                'trend' => 1,
                'is_new' => 1,
                'slug' => $product['slug'],
            ]);

            // Insert product galleries
            DB::table('product_galleries')->insert([
                'product_id' => $productId,
                'image' => $product['img_thumbnail'],
            ]);

            // Insert product tags
            DB::table('product_tags')->insert([
                'product_id' => $productId,
                'tag_id' => 1,
            ]);

            foreach ($product['variants'] as $variant) {
                $variantId = DB::table('product_variants')->insertGetId([
                    'product_id' => $productId,
                    'price_regular' => $variant['price_regular'],
                    'price_sale' => $variant['price_sale'],
                    'quantity' => $variant['quantity'],
                    'image' => $product['img_thumbnail'],
                    'sku' => $variant['sku'],
                ]);

                foreach ($variant['attribute_item_id'] as $attributeItem) {
                    DB::table('product_variant_has_attributes')->insert([
                        'product_variant_id' => $variantId,
                        'attribute_item_id' => $attributeItem['id'],
                        'attribute_id' => $attributeItem['id'],
                        'value' => $attributeItem['value'],
                    ]);
                }
            }
        }
    */
    }
}
