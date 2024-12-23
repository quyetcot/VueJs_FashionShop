<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DataOrder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // tạo phương thức thanh toán
        PaymentMethod::query()->insert([
            [
                "name" => "Thanh toán tại của hàng"
            ],
            [
                "name" => "Thanh toán online"
            ],

        ]);
        // tạo mới đơn hàng
        $order= Order::query()->create([
            'user_id' => 1,
            'payment_method_id' => 1,
            'order_status'=>"giao hàng thành công",
            'payment_status'=>1,
            'order_code'=>"mixmatchcode",
            'total_quantity'=>2,
            'total'=>120000,
            'user_name'=>"nguyễn công trang",
            'user_email'=>"nguyencongtrang2k4@gmail.com",
            'user_phonenumber'=>"0988207698",
            'user_address'=>"hà nội",
           
        ]);
        // thêm đơn hàng chi tiết
        OrderDetail::query()->insert([
            [
                'product_id'=>1,
                // 'product_variant_id',
                'order_id'=>$order->id,
                'product_name'=>"sản phẩm đơn",
                'product_img'=>"sp đơn img",
                // 'attributes',
                'quantity'=>2,
                'price'=>11000,
                'total_price'=>22000,
                // 'discount'
            ],
            [
                'product_id'=>2,
                // 'product_variant_id',
                'order_id'=>$order->id,
                'product_name'=>"sản phẩm đơn",
                'product_img'=>"sp đơn img",
                // 'attributes'=>[],
                'quantity'=>2,
                'price'=>11000,
                'total_price'=>22000,
                // 'discount'
            ]
        ]);
    }
}
