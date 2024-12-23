<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Helper\Product\GetUniqueAttribute;


class WishlistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Lấy danh sách sản phẩm yêu thích của người dùng hiện tại
            if (Auth::check()) {
                $user_id = Auth::id();
                // $userId = 1;
                // Query để lấy danh sách các sản phẩm yêu thích cùng với các quan hệ
                $wishlistItems = Wishlist::with([
                    'product.galleries',
                    'product.variants.attributes'
                ])->where('user_id', $user_id)
                    ->get();
                $allProducts = []; // Mảng chứa tất cả sản phẩm và biến thể

                foreach ($wishlistItems as $wishlistItem) {
                    $product = $wishlistItem->product; // Lấy đối tượng sản phẩm từ wishlistItem

                    if ($product) {
                        $discountPercentage  = 0;
                        if ($product->type == '0') {
                            // Sản phẩm đơn giản (không có biến thể)
                            if ($product->price_regular > 0) {
                                // Nếu giá bán = 0, phần trăm giảm giá là 100%
                                $discountPercentage = ($product->price_regular - $product->price_sale) / $product->price_regular * 100;
                            }
                        } else if ($product->variants && $product->variants->isNotEmpty()) {
                            // Sản phẩm có biến thể
                            $validVariants = $product->variants->filter(function ($variant) {
                                // Lọc chỉ lấy những biến thể có giá gốc > 0
                                return $variant->price_regular > 0;
                            });
        
                            if ($validVariants->isNotEmpty()) {
                                // Tính % giảm giá lớn nhất từ những biến thể hợp lệ
                                $discountPercentage = $validVariants->map(function ($variant) {
                                    return ($variant->price_regular - $variant->price_sale) / $variant->price_regular * 100;
                                })->max();
                            }
                        }
                        $discountPercentage  = round($discountPercentage, 1); // Làm tròn 1 chữ số thập phân
                        // $product->increment('views'); // Tăng số lượt xem cho sản phẩm

                        // Khởi tạo đối tượng lấy thuộc tính duy nhất
                        $getUniqueAttributes = new GetUniqueAttribute();
                        $product->unique_attributes=$getUniqueAttributes->getUniqueAttributes($product["variants"]);
                        // Thêm sản phẩm và biến thể vào mảng
                        $allProducts[] = [
                            'wishlist_id' => $wishlistItem->id,
                            'product' => $product,
                            'discountPercentage' => $discountPercentage,
                            'getUniqueAttributes' => $getUniqueAttributes->getUniqueAttributes($product->variants->toArray()),
                        ];
                    }
                }
            } else {
                return response()->json(['message' => 'User not authenticated'], 401);
            }
            // Trả về tất cả sản phẩm sau khi vòng lặp kết thúc
            return response()->json($allProducts);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Không thể lấy danh sách yêu thích', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
            ]);
            if (Auth::check()) {
                $user_id = Auth::id();
                //Lấy id người dùng hiện tại
                // $userId = 1;
                // Kiểm tra nếu sản phẩm đã có trong danh sách yêu thích
                $wishlistItem = Wishlist::where('user_id', $user_id)
                    ->where('product_id', $request->product_id)
                    ->first();

                if (!$wishlistItem) {
                    // Nếu chưa có, thêm sản phẩm vào danh sách yêu thích
                    Wishlist::create([
                        'user_id' => $user_id,
                        'product_id' => $request->product_id,
                    ]);

                    return response()->json(['message' => 'Sản phẩm đã được thêm vào danh sách yêu thích'], 201);
                }
            }
            if (Auth::check()) {
                $user_id = Auth::id();
                // $userId = 1;
                // Kiểm tra nếu sản phẩm đã có trong danh sách yêu thích
                $wishlistItem = Wishlist::where('user_id', $user_id)
                    ->where('product_id', $request->product_id)
                    ->first();

                if (!$wishlistItem) {
                    // Nếu chưa có, thêm sản phẩm vào danh sách yêu thích
                    Wishlist::create([
                        'user_id' => $user_id,
                        'product_id' => $request->product_id,
                    ]);

                    return response()->json(['message' => 'Sản phẩm đã được thêm vào danh sách yêu thích'], 201);
                }

                return response()->json(['message' => 'Sản phẩm đã có trong danh sách yêu thích'], 200);
            } else {
                return response()->json(['message' => 'User not authenticated'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            if (Auth::check()) {
                $user_id = Auth::id();  //Lấy id người dùng hiện tại
                // $userId = 1;

                // Tìm sản phẩm trong danh sách yêu thích theo ID của wishlist
                $wishlistItem = Wishlist::where('user_id', $user_id)->where('id', $id)->first();
                // dd($wishlistItem);
                if ($wishlistItem) {
                    // Nếu tìm thấy, xóa sản phẩm khỏi danh sách yêu thích
                    $wishlistItem->delete();

                    return response()->json(['message' => 'Sản phẩm đã được xóa khỏi danh sách yêu thích'], 200);
                }

                return response()->json(['message' => 'Không tìm thấy sản phẩm trong danh sách yêu thích'], 404);
            } else {
                return response()->json(['message' => 'User not authenticated'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
