<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryChildren;
use App\Models\Comments;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Stringable;

class DataFashionSales extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Category::query()->insert(
        //     [
        //         [
        //             "name" => "Áo",
        //             'slug' => "ao",
        //             'description' => "đẹp",
        //             'parent_id'=>null,
        //             "img_thumbnail" => "test.jpg",


        //             // 'status'=>,
        //         ],
        //         [
        //             "name" => "Áo khoác",
        //             'slug' => "ao-khoac",
        //             'description' => "đẹp",
        //             "parent_id" => 1,
        //             "img_thumbnail" => "test.jpg",

        //         ],
        //         [
        //             "name" => "giày",
        //             'slug' => "giay",
        //             'description' => "đẹp",
        //             'parent_id'=>null,

        //             "img_thumbnail" => "test.jpg",


        //             // 'status'=>,
        //         ],
        //         [
        //             "name" => "giày thể thao",
        //             'slug' => "giay-the-thao",
        //             'description' => "đẹp",
        //             "parent_id" => 3,
        //             "img_thumbnail" => "test.jpg",

        //         ],
        //     ]
        // );

        // THÊM MỚI USER and role
        // Role::query()->insert([
        //     ["name" => "client"],
        //     ["name" => "membership"],
        //     ["name" => "shipper"],
        //     ["name" => "admin"],


        // ]);
        User::query()->insert([
            // [
            //     "role_id" => 1,
            //     'name' => 'khách hàng 1',
            //     'email' => fake()->email(),
            //     'password' => Hash::make("12345"),
            //     "phone_number" => "0987654321",
            //     'address' => "hà nội",
            // ],
            // [
            //     "role_id" => 2,
            //     'name' => 'membership',
            //     'email' => fake()->email(),
            //     'password' => Hash::make("12345"),
            //     "phone_number" => "0987654321",
            //     'address' => "hà nội",
            // ],
            // [
            //     "role_id" => 3,
            //     'name' => 'shipper',
            //     'email' => fake()->email(),
            //     'password' => Hash::make("12345"),
            //     "phone_number" => "0987654321",
            //     'address' => "hà nội",
            // ],
            [
                "role_id" => 4,
                'name' => 'khách hàng 1',
                'email' => "admin@gmail.com",
                'password' => Hash::make("12345"),
                "phone_number" => "0987654321",
                'address' => "hà nội",
            ],

        ]);
        // thêm dữ liệu brands
        // Brand::query()->insert([
        //     [
        //         "name" => "DIOR",
        //         'slug'=>'dior',
        //         'image'=>'test.jpg',
        //         'email'=>'test@gmail.com',
        //         'phone_number'=>"0987654321",
        //         "address" => "hà nội"
        //     ],
        //     [
        //         "name" => "CHANEL",
        //         'slug'=>'CHANEL',
        //         'image'=>'test1.jpg',
        //         'email'=>'test@2gmail.com',
        //         'phone_number'=>"0987654322",
        //         "address" => "hà nội"
        //     ],
        // ]);
    }
}
