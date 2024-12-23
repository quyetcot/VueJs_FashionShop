<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Helper\Product\GetUniqueAttribute;

class HomeProductController extends Controller
{
    public function getHomeProducts()
    {
        try {
            // Lấy sản phẩm xu hướng (trend) và sản phẩm hiển thị trên trang chủ (is_show_home)
            $trendProducts = Product::query()->with([
                "variants.attributes"
            ])->where([
                ['trend', true],
                ['status', true]
            ])->get();

            $homeShowProducts = Product::query()->with([
                "variants.attributes"
            ])->where([
                ['is_show_home', true],
                ['status', true]
            ])->get();

            // Khởi tạo đối tượng để lấy các thuộc tính độc nhất
            $getUniqueAttributes = new GetUniqueAttribute();

            // Thêm các thuộc tính độc nhất và phần trăm giảm giá cho sản phẩm xu hướng
            foreach ($trendProducts as $key => $product) {
                $trendProducts[$key]['unique_attributes'] = $getUniqueAttributes->getUniqueAttributes($product->variants->toArray());

                // Tính phần trăm giảm giá
                $discountPercentage = $this->calculateDiscountPercentage($product);
                $trendProducts[$key]['discount_percentage'] = $discountPercentage;

                // Tăng lượt xem khi sản phẩm được nhấn
                // $product->increment('views');
            }

            // Thêm các thuộc tính độc nhất và phần trăm giảm giá cho sản phẩm hiển thị trên trang chủ
            foreach ($homeShowProducts as $key => $product) {
                $homeShowProducts[$key]['unique_attributes'] = $getUniqueAttributes->getUniqueAttributes($product->variants->toArray());

                // Tính phần trăm giảm giá
                $discountPercentage = $this->calculateDiscountPercentage($product);
                $homeShowProducts[$key]['discount_percentage'] = $discountPercentage;

                // Tăng lượt xem khi sản phẩm được nhấn
              
            }

            // Lấy danh mục (categories) từ database
            $categories = Category::query()
                ->whereNull('parent_id') // Chỉ lấy danh mục cha (parent categories)
                ->with('children') // Gồm cả danh mục con nếu có
                ->get();

            // Trả về kết quả JSON bao gồm sản phẩm trend, sản phẩm hiển thị trên trang chủ và danh mục
            return response()->json(
                [
                    'trend_products' => $trendProducts,
                    'home_show_products' => $homeShowProducts,
                    'categories' => $categories // Add categories to the response
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $ex) {
            return response()->json(
                [
                    "message" => $ex->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Tính phần trăm giảm giá của một sản phẩm
     *
     * @param Product $product
     * @return float
     */
    private function calculateDiscountPercentage($product)
    {
        $discountPercentage = 0;

        if ($product->type == '0') {
            // Sản phẩm đơn giản (không có biến thể)
            if ($product->price_regular > 0) {
                $discountPercentage = ($product->price_regular - $product->price_sale) / $product->price_regular * 100;
            }
        } else if ($product->variants && $product->variants->isNotEmpty()) {
            // Sản phẩm có biến thể
            $discountPercentage = $product->variants->map(function ($variant) {
                if ($variant->price_regular > 0) {
                    return ($variant->price_regular - $variant->price_sale) / $variant->price_regular * 100;
                }
                return 0;
            })->max(); // Lấy % giảm giá lớn nhất từ các biến thể
        }

        return round($discountPercentage, 1); // Làm tròn 1 chữ số thập phân
    }
}
