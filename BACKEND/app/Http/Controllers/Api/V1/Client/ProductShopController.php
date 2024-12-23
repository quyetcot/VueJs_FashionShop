<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ProductShopRequest;
use App\Http\Helper\Product\GetUniqueAttribute;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductShopController extends Controller
{
    // lấy ra tất cả product và biến thể của nó
    public function getAllProduct(ProductShopRequest $request)
    {
        // Lấy tất danh mục
        $allCategory = Category::whereNull('parent_id')->latest('id')->get();
        // Lấy tất cả brands
        $allBrand = Brand::query()->latest('id')->get();
        // lấy ra các thuộc tính
        $attributes = Attribute::with('attributeitems')->get();
        $allAttribute = [];
        // converte dữ liệu cho hằng dễ làm việc
        foreach ($attributes as $attribute) {
            $allAttribute[$attribute->name] = $attribute->attributeitems->toArray();
        }
        $search = $request->input('search'); // Người dùng nhập từ khóa tìm kiếm
        $attributes = $request->input('attributes');
        $minPrice = $request->input('min_price'); // Người dùng nhập giá tối thiểu
        $maxPrice = $request->input('max_price'); // Người dùng nhập giá tối đa
        $categories = $request->input('categorys');
        $brands = $request->input('brands');
        $sale = $request->input('sale');
        $new = $request->input('new');

        $trend = $request->input('trend');
        $sortDirection = $request->input('sortDirection');
        $sortPrice = $request->input('sortPrice');
        $sortAlphaOrder = $request->input('sortAlphaOrder');
        $new = $request->input('new');
        $sale = $request->input('sale');

        // Kiểm tra giá trị sortDirection
        if ($sortDirection && !in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            throw new \InvalidArgumentException('Giá trị sortDirection chỉ có thể là "asc" hoặc "desc".');
        }
        // Kiểm tra giá trị sortAlphaOrder
        if ($sortAlphaOrder && !in_array(strtolower($sortAlphaOrder), ['asc', 'desc'])) {
            throw new \InvalidArgumentException('Giá trị sortAlphaOrder chỉ có thể là "asc" hoặc "desc".');
        }
        try {
            $products = Product::query()
                ->when($new, function ($query, $new) {
                    // Lọc sản phẩm new
                    return $query->where('is_new', 1);
                })
                ->when($trend, function ($query, $new) {
                    // Lọc sản phẩm hot trend
                    return $query->where('is_new', 1);
                })
                ->when($sale, function ($query) {
                    return $query->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('type', 0)
                                ->whereNotNull('price_sale')
                                ->WhereColumn('price_sale', '<', 'price_regular');
                        })
                            ->orWhere(function ($query) {
                                $query->where('type', 1)
                                    ->whereHas('variants', function ($query) {
                                        $query->whereNotNull('price_sale')
                                            ->WhereColumn('price_sale', '<', 'price_regular');
                                    });
                            });
                    });
                })
                ->when($categories, function ($query) use ($categories) {
                    // Lấy tất cả ID danh mục cha và con
                    $categoryIds = Category::whereIn('id', $categories)
                        ->with('allChildren') // Lấy tất cả danh mục con
                        ->get()
                        ->pluck('id') // Lấy ID của các danh mục cha
                        ->toArray();
                    // Gộp các ID danh mục con vào danh sách
                    $subCategoryIds = Category::whereIn('parent_id', $categoryIds)->pluck('id')->toArray();
                    $categoryIds = array_merge($categoryIds, $subCategoryIds);

                    // Lọc sản phẩm theo danh mục
                    return $query->whereIn('category_id', $categoryIds);
                })
                ->when($brands, function ($query) use ($brands) {
                    // Lọc theo danh mục
                    return $query->whereIn('brand_id', $brands);
                })
                // ->when($search, function ($query, $search) {
                //     return $query->where('name', 'like', "%{$search}%");
                // })
                ->when($search, function ($query, $search) {
                    $keywords = explode(' ', $search);

                    return $query->where(function ($q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $q->where(function ($subQuery) use ($keyword) {
                                $subQuery->where('name', 'like', "% {$keyword} %")
                                    ->orWhere('name', 'like', "{$keyword} %")
                                    ->orWhere('name', 'like', "% {$keyword}")
                                    ->orWhere('name', '=', "{$keyword}");
                            });
                        }
                    });
                })

                ->when($attributes, function ($query, $attributes) {
                    foreach ($attributes as $key => $values) {
                        $query->whereHas('variants.attributes', function ($subQuery) use ($key, $values) {
                            $subQuery->where('name', $key) // Lọc theo key (tên thuộc tính)
                                ->whereIn('product_variant_has_attributes.value', $values); // Lọc theo value (danh sách giá trị)
                        });
                    }
                })
                // khoảng giá
                ->when($minPrice || $maxPrice, function ($query) use ($minPrice, $maxPrice) {
                    return $query->where(function ($subQuery) use ($minPrice, $maxPrice) {
                        if (!is_null($minPrice)) {
                            $subQuery->where(function ($q) use ($minPrice) {
                                $q->whereHas('variants', function ($query) use ($minPrice) {
                                    $query->where('price_sale', '>=', $minPrice);
                                })->orWhere('price_sale', '>=', $minPrice);
                            });
                        }
                        if (!is_null($maxPrice)) {
                            $subQuery->where(function ($q) use ($maxPrice) {
                                $q->whereHas('variants', function ($query) use ($maxPrice) {
                                    $query->where('price_sale', '<=', $maxPrice);
                                })->orWhere('price_sale', '<=', $maxPrice);
                            });
                        }
                    });
                })
                ->when($sortPrice, function ($query) use ($sortPrice) {
                    $query->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')
                        ->select('products.*', DB::raw('MAX(product_variants.price_sale) as variant_price_sale')) // Sử dụng hàm MAX()
                        ->groupBy('products.id') // Nhóm theo product id
                        ->orderByRaw("
                            CASE
                                WHEN products.type = 0 THEN products.price_sale
                                WHEN products.type = 1 THEN variant_price_sale
                            END $sortPrice
                        ");
                })
                // Chỉ sắp xếp theo tên nếu có giá trị sortAlphaOrder
                ->when($sortAlphaOrder, function ($query) use ($sortAlphaOrder) {
                    return $query->orderBy('name', $sortAlphaOrder);
                })
                // Chỉ sắp xếp theo ID nếu có giá trị sortDirection
                ->when($sortDirection, function ($query) use ($sortDirection) {
                    return $query->orderBy('id', $sortDirection);
                })
                ->with([
                    "brand",
                    "category",
                    "galleries",
                    "tags",
                    "comments",
                    "variants.attributes"
                ])->get();
            $allProducts = []; // Mảng chứa tất cả sản phẩm và biến thể

            foreach ($products as $product) {
                $discountPercentage = 0;

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

                // Làm tròn phần trăm giảm giá
                $discountPercentage = round($discountPercentage, 1);

                // Tăng số lượt xem của sản phẩm
                // $product->increment('views');

                // Lấy thuộc tính duy nhất từ các biến thể
                $getUniqueAttributes = new GetUniqueAttribute();
                $product->unique_attributes = $getUniqueAttributes->getUniqueAttributes($product["variants"]);

                // Thêm sản phẩm và biến thể vào mảng kết quả
                $allProducts[] = [
                    'product' => $product,
                    'discountPercentage' => $discountPercentage,
                    'getUniqueAttributes' => $getUniqueAttributes->getUniqueAttributes($product["variants"]),
                ];
            }
            // Trả về tất cả sản phẩm sau khi vòng lặp kết thúc
            return response()->json([
                'products' => $allProducts,
                'brands' =>   $allBrand,
                'attributes' =>  $allAttribute,
                'categories' =>  $allCategory,
            ]);
        } catch (ModelNotFoundException $e) {
            // Trả về lỗi 404 nếu không tìm thấy Category
            return response()->json([
                'message' => 'Sản Phẩm Không Tồn Tại!'
            ], 404);
        }
    }
}
