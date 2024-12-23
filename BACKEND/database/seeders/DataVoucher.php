<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherLog;
use App\Models\VoucherMeta;
use App\Models\VoucherUser;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DataVoucher extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Tạo trước USER với ORDER trước khi chạy seeder nhé? OK!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

        // Lấy người dùng đã có sẵn trong bảng users
        $users = User::all();
        // Lấy danh sách các đơn hàng đã có sẵn trong bảng orders
        $orders = Order::all();

        // Tạo dữ liệu cho bảng vouchers
        for ($i = 1; $i <= 10; $i++) {
            $voucher = Voucher::create([
                'title' => 'Voucher ' . $i+1,
                'description' => 'Giảm giá cho sản phẩm',
                'code' => 'VOUCHER' . strtoupper($i),
                'discount_type' => $i % 2 == 0 ? 'percent' : 'fixed',
                'discount_value' => $i * 10,
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(30),
                'min_order_value' => 100000,
                'usage_limit' => 10,
                'used_count' => 1,
                'is_active' => true,
            ]);

            // Tạo meta dữ liệu cho mỗi voucher
            VoucherMeta::create([
                'voucher_id' => $voucher->id,
                'meta_key' => '_voucher_category_ids',
                'meta_value' => json_encode([1, 2, 3]) // Giả sử là ID danh mục sản phẩm
            ]);

            VoucherMeta::create([
                'voucher_id' => $voucher->id,
                'meta_key' => '_voucher_product_ids',
                'meta_value' => json_encode(value: [1, 2]) // Giả sử là ID của sản phẩm cụ thể
            ]);

            // Duyệt qua người dùng có sẵn và tạo log sử dụng voucher
            foreach ($users as $user) {
                VoucherUser::create([
                    'voucher_id' => $voucher->id,
                    'user_id' => $user->id,
                    'usage_count' => rand(1, 3), // Giả sử người dùng sử dụng từ 1 đến 3 lần
                ]);

                // Chọn đơn hàng ngẫu nhiên cho người dùng đó từ danh sách đơn hàng đã có
                $userOrders = $orders->where('user_id', $user->id)->random(rand(1, 2));

                foreach ($userOrders as $order) {
                    // Ghi log sử dụng voucher
                    VoucherLog::create([
                        'voucher_id' => $voucher->id,
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'action' => 'used', // Hoạt động: sử dụng voucher
                    ]);
                }
            }
        }
    }
}
