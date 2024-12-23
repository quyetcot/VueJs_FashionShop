<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\CartItem;
use App\Events\CartEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Cart\StoreCart;
use Symfony\Component\Console\Input\Input;
use App\Http\Helper\Product\GetUniqueAttribute;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user_id = Auth::id();
            $cart = Cart::query()->where('user_id', $user_id)->with([
                "cartitems",
                "cartitems.product",
                "cartitems.productvariant.attributes",
            ])->first();
            if (!$cart) {
                return response()->json([
                    "message" => "Chưa có sản phẩm nào trong giỏ hàng."
                ], Response::HTTP_OK);
            } else {
                $cart->toArray();
            }
            $sub_total = 0;
            foreach ($cart["cartitems"] as  $key =>  $cartitem) {
                $quantity = $cartitem["quantity"];

                if ($cartitem["productvariant"]) {
                    $variant_price = $cartitem["productvariant"]["price_sale"];

                    $cart["cartitems"][$key]["total_price"] = $variant_price * $quantity;
                } else {

                    $product_price = $cartitem["product"]["price_sale"];

                    $cart["cartitems"][$key]["total_price"] = $product_price * $quantity;
                }
                $sub_total += $cart["cartitems"][$key]["total_price"];
            }
            return response()->json([
                "message" => "lấy dữ liệu thành công",
                "cart" => $cart,
                "sub_total" => $sub_total
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json(
                ["message" => $ex->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCart $request)
    {
        try {
            $data = $request->validated();
            $addToCart = isset($data['product_id']);
            $reOrder = isset($data['order_id']);

            if (!$addToCart && !$reOrder) {
                return response()->json(['message' => 'Vui lòng chọn một hành động: thêm sản phẩm vào giỏ hàng hoặc đặt lại đơn hàng.'], Response::HTTP_BAD_REQUEST);
            }
            if ($addToCart && $reOrder) {
                return response()->json(['message' => 'Không thể thực hiện đồng thời hai hành động.'], Response::HTTP_BAD_REQUEST);
            }
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }

            return DB::transaction(function () use ($data, $user) {
                $cart = Cart::firstOrCreate(['user_id' => $user->id]);
                if (isset($data['order_id'])) {
                    $order = Order::with('orderDetails')->findOrFail($data['order_id']);
                    if (Auth::id() !== $order->user_id) {
                        return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
                    }
                    // Kiểm tra trạng thái đơn hàng
                    if (!in_array($order->order_status, [Order::STATUS_CANCELED, Order::STATUS_COMPLETED,Order::STATUS_RETURNED])) {
                        return response()->json(['message' => 'Chỉ có thể thêm sản phẩm từ các đơn hàng đã hoàn thành hoặc đã hủy,hoàn trả hàng.'], Response::HTTP_BAD_REQUEST);
                    }
                    $productIds = $order->orderDetails->pluck('product_id')->toArray();
                    $variantIds = $order->orderDetails->pluck('product_variant_id')->filter()->toArray();

                    $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
                    $variants = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

                    $skippedItems = [];

                    foreach ($order->orderDetails as $item) {
                        $product = $products->get($item->product_id);
                        $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;

                        if (!$product) {
                            $skippedItems[$item->product_id] = [
                                'reason' => 'Sản phẩm không tồn tại'
                            ];
                            continue;
                        }

                        $availableQuantity = $variant ? $variant->quantity : $product->quantity;
                        $quantityToAdd = min($item->quantity, $availableQuantity);
                        if ($quantityToAdd < $item->quantity) {
                            $skippedItems[$item->product_id] = [
                                'reason' => 'Số lượng yêu cầu vượt quá số lượng tồn kho, chỉ thêm được ' . $quantityToAdd . ' sản phẩm'
                            ];
                        }
                        if ($quantityToAdd <= 0) {
                            $skippedItems[$item->product_id] = [
                                'reason' => 'Không đủ tồn kho'
                            ];
                            continue;
                        }

                        $cartItemQuery = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $item->product_id);

                        if ($variant) {
                            $cartItemQuery->where('product_variant_id', $variant->id);
                        } else {
                            $cartItemQuery->whereNull('product_variant_id');
                        }

                        $cartItem = $cartItemQuery->first();

                        if ($cartItem) {
                            $maxQuantityCanBeAdded = $availableQuantity - $cartItem->quantity;
                            $quantityToAdd = min($quantityToAdd, $maxQuantityCanBeAdded);

                            if ($quantityToAdd > 0) {
                                $cartItem->quantity += $quantityToAdd;
                                $cartItem->save();
                            } else {
                                $skippedItems[$item->product_id] = [
                                    'reason' => 'Sản phẩm đã đạt số lượng tối đa trong giỏ hàng'
                                ];
                            }
                        } else {
                            CartItem::create([
                                'cart_id' => $cart->id,
                                'product_id' => $item->product_id,
                                'product_variant_id' => $variant ? $variant->id : null,
                                'quantity' => $quantityToAdd,
                            ]);
                        }
                    }

                    return response()->json([
                        'message' => 'Thêm sản phẩm vào giỏ hàng hoàn tất',
                        'skipped_items' => $skippedItems,
                    ], Response::HTTP_OK);
                } else {
                    // Logic thêm sản phẩm vào giỏ hàng
                    // Lấy thông tin sản phẩm
                    $product = Product::findOrFail($data['product_id']);
                    $variant = null;

                    if ($product->type) {
                        $variant = ProductVariant::findOrFail($data['product_variant_id']);
                    }

                    // Kiểm tra xem sản phẩm (hoặc biến thể) đã có trong giỏ chưa
                    $cartItemQuery = CartItem::where('cart_id', $cart->id)
                        ->where('product_id', $product->id);

                    if ($variant) {
                        $cartItemQuery->where('product_variant_id', $variant->id);
                    } else {
                        $cartItemQuery->whereNull('product_variant_id');
                    }
                    $cartItem = $cartItemQuery->first();
                    // dd($cartItem->toArray());
                    if ($cartItem) {
                        // Kiểm tra lại số lượng có sẵn trước khi cập nhật
                        if ($product->type) {
                            if (($cartItem->quantity + $data['quantity']) > $variant->quantity) {
                                return response()->json(
                                    ['message' => 'Số lượng sản phẩm bạn yêu cầu và số lượng sản phẩm trong giỏ hàng đã vượt quá số lượng có sẵn của biến thể sản phẩm.'],
                                    422
                                );
                            }
                        } else {
                            if (($cartItem->quantity + $data['quantity']) > $product->quantity) {
                                return response()->json(['message' => 'Số lượng yêu cầu và số lượng trong giỏ hàng của bạn đã vượt quá số lượng có sẵn của sản phẩm.'], 422);
                            }
                        }

                        // Cập nhật số lượng và giá
                        $cartItem->quantity += $data['quantity'];

                        $cartItem->save();
                    } else {
                        // Tạo mới mục giỏ hàng
                        $cartItem = CartItem::create([
                            'cart_id' => $cart->id,
                            'product_id' => $product->id,
                            'product_variant_id' => $variant ? $variant->id : null,
                            'quantity' => $data['quantity'],

                        ]);
                    }

                    return response()->json(['message' => 'Thêm vào giỏ hàng thành công'], Response::HTTP_OK);
                }
                // broadcast(new CartEvent($cart->id, $cartItem));
                // // ->toOthers();
            });
        } catch (\Exception $ex) {
            return response()->json(
                [
                    "message" => $ex->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    /*
    public function store2(StoreCart $request)
    {
        try {
            $data = $request->validated();
            $addToCart = isset($data['product_id']) && isset($data['quantity']);
            $reOrder = isset($data['order_id']) && is_array($data['order_id']);

            if (!$addToCart && !$reOrder || $addToCart && $reOrder) {
                return response()->json(['message' => 'Vui lòng chọn một hành động: thêm sản phẩm vào giỏ hàng hoặc đặt lại đơn hàng.'], Response::HTTP_BAD_REQUEST);
            }
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
            return DB::transaction(function () use ($data, $user) {
                $cart = Cart::firstOrCreate(['user_id' => $user->id]);
                // Case 1: Thêm sản phẩm từ đơn hàng
                if ($data->has('order_id')) {
                    $order = Order::with('orderDetails')->findOrFail($data['order_id']);
                    if (Auth::id() !== $order->user_id) {
                        return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
                    }
                    $productIds = $order->orderDetails->pluck('product_id')->toArray();
                    $variantIds = $order->orderDetails->pluck('product_variant_id')->filter()->toArray();

                    $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
                    $variants = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

                    $skippedItems = []; // Lưu danh sách sản phẩm không được thêm vào giỏ hàng

                    foreach ($order->orderDetails as $item) {
                        $product = $products->get($item->product_id);
                        $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;

                        if (!$product) {
                            $skippedItems[] = [
                                'product_id' => $item->product_id,
                                'reason' => 'Sản phẩm không tồn tại'
                            ];
                            continue;
                        }

                        $availableQuantity = $variant ? $variant->quantity : $product->quantity;
                        $quantityToAdd = min($item->quantity, $availableQuantity);

                        if ($quantityToAdd <= 0) {
                            $skippedItems[] = [
                                'product_id' => $item->product_id,
                                'reason' => 'Không đủ tồn kho'
                            ];
                            continue;
                        }

                        $cartItemQuery = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $item->product_id);

                        if ($variant) {
                            $cartItemQuery->where('product_variant_id', $variant->id);
                        } else {
                            $cartItemQuery->whereNull('product_variant_id');
                        }

                        $cartItem = $cartItemQuery->first();

                        if ($cartItem) {
                            $maxQuantityCanBeAdded = $availableQuantity - $cartItem->quantity;
                            $quantityToAdd = min($quantityToAdd, $maxQuantityCanBeAdded);

                            if ($quantityToAdd > 0) {
                                $cartItem->quantity += $quantityToAdd;
                                $cartItem->save();
                            } else {
                                $skippedItems[] = [
                                    'product_id' => $item->product_id,
                                    'reason' => 'Sản phẩm đã đạt số lượng tối đa trong giỏ hàng'
                                ];
                            }
                        } else {
                            CartItem::create([
                                'cart_id' => $cart->id,
                                'product_id' => $item->product_id,
                                'product_variant_id' => $variant ? $variant->id : null,
                                'quantity' => $quantityToAdd,
                            ]);
                        }
                    }

                    return response()->json([
                        'message' => 'Thêm sản phẩm vào giỏ hàng hoàn tất',
                        'skipped_items' => $skippedItems,
                    ], Response::HTTP_OK);
                } else {
                    // Lấy thông tin sản phẩm
                    $product = Product::findOrFail($data->product_id);

                    $variant = null;

                    if ($product->type) {
                        $variant = ProductVariant::findOrFail($data->product_variant_id);
                    }

                    // Kiểm tra xem sản phẩm (hoặc biến thể) đã có trong giỏ chưa
                    $cartItemQuery = CartItem::where('cart_id', $cart->id)
                        ->where('product_id', $product->id);

                    if ($variant) {
                        $cartItemQuery->where('product_variant_id', $variant->id);
                    } else {
                        $cartItemQuery->whereNull('product_variant_id');
                    }
                    $cartItem = $cartItemQuery->first();
                    // dd($cartItem->toArray());
                    if ($cartItem) {
                        // Kiểm tra lại số lượng có sẵn trước khi cập nhật
                        if ($product->type) {
                            if (($cartItem->quantity + $data->quantity) > $variant->quantity) {
                                return response()->json(
                                    ['message' => 'Số lượng sản phẩm bạn yêu cầu và số lượng sản phẩm trong giỏ hàng đã vượt quá số lượng có sẵn của biến thể sản phẩm.'],
                                    422
                                );
                            }
                        } else {
                            if (($cartItem->quantity + $data->quantity) > $product->quantity) {
                                return response()->json(['message' => 'Số lượng yêu cầu và số lượng trong giỏ hàng của bạn đã vượt quá số lượng có sẵn của sản phẩm.'], 422);
                            }
                        }

                        // Cập nhật số lượng và giá
                        $cartItem->quantity += $data->quantity;

                        $cartItem->save();
                    } else {
                        // Tạo mới mục giỏ hàng
                        $cartItem = CartItem::create([
                            'cart_id' => $cart->id,
                            'product_id' => $product->id,
                            'product_variant_id' => $variant ? $variant->id : null,
                            'quantity' => $data->quantity,

                        ]);
                    }
                    // broadcast(new CartEvent($cart->id, $cartItem));
                    // // ->toOthers();
                    return response()->json(['message' => 'Thêm vào giỏ hàng thành công'], Response::HTTP_OK);
                }
            });
        } catch (\Exception $ex) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng hoặc sản phẩm.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Đã xảy ra lỗi: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function store1(StoreCart $request)
    {

        try {
            return DB::transaction(function () use ($request) {


                $user = Auth::user();
                if (!$user) {
                    return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
                }

                $cart = Cart::firstOrCreate(['user_id' => $user->id]);

                // Lấy thông tin sản phẩm
                $product = Product::findOrFail($request->product_id);

                $variant = null;

                if ($product->type) {
                    $variant = ProductVariant::findOrFail($request->product_variant_id);
                }

                // Kiểm tra xem sản phẩm (hoặc biến thể) đã có trong giỏ chưa
                $cartItemQuery = CartItem::where('cart_id', $cart->id)
                    ->where('product_id', $product->id);

                if ($variant) {
                    $cartItemQuery->where('product_variant_id', $variant->id);
                } else {
                    $cartItemQuery->whereNull('product_variant_id');
                }
                $cartItem = $cartItemQuery->first();
                // dd($cartItem->toArray());
                if ($cartItem) {
                    // Kiểm tra lại số lượng có sẵn trước khi cập nhật
                    if ($product->type) {
                        if (($cartItem->quantity + $request->quantity) > $variant->quantity) {
                            return response()->json(
                                ['message' => 'Số lượng sản phẩm bạn yêu cầu và số lượng sản phẩm trong giỏ hàng đã vượt quá số lượng có sẵn của biến thể sản phẩm.'],
                                422
                            );
                        }
                    } else {
                        if (($cartItem->quantity + $request->quantity) > $product->quantity) {
                            return response()->json(['message' => 'Số lượng yêu cầu và số lượng trong giỏ hàng của bạn đã vượt quá số lượng có sẵn của sản phẩm.'], 422);
                        }
                    }

                    // Cập nhật số lượng và giá
                    $cartItem->quantity += $request->quantity;

                    $cartItem->save();
                } else {
                    // Tạo mới mục giỏ hàng
                    $cartItem = CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant ? $variant->id : null,
                        'quantity' => $request->quantity,

                    ]);
                }
                // broadcast(new CartEvent($cart->id, $cartItem));
                // // ->toOthers();
                return response()->json(['message' => 'Thêm vào giỏ hàng thành công'], Response::HTTP_OK);
            });
        } catch (\Exception $ex) {
            return response()->json(
                [
                    "message" => $ex->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
*/
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        try {
            return DB::transaction(function () use ($id) {
                // $user_id = Auth::id();
                $cart_item = CartItem::query()->findOrFail($id)->load(
                    [
                        "cart",
                        "productvariant.attributes",
                        "product.variants.attributes",
                    ]
                )->toArray();


                $product_variant = Product::query()->findOrFail($cart_item['product']['type'] ? $cart_item["productvariant"]["product_id"] : $cart_item['product']['id'])->load(["variants.attributes"])->toArray();
                $getUniqueAttributes = new GetUniqueAttribute();

                return response()->json([
                    "getuniqueattributes" => $getUniqueAttributes->getUniqueAttributes($product_variant["variants"]),
                    "cart_item" => $cart_item
                ], Response::HTTP_OK);
            });
        } catch (\Exception $ex) {
            return response()->json([
                "message" => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     //
    //     try {

    //         return DB::transaction(function () use ($request, $id) {
    //             $request->validate([
    //                 "quantity" => "required|integer|min:1"
    //             ]);
    //             $cart_item = CartItem::query()->findOrFail($id);
    //             $product = Product::query()->findOrFail($cart_item->product_id);
    //             if ($product->quantity < $request->input('quantity') && !$cart_item->product_variant_id) {
    //                 return response()->json([
    //                     "message" => "Số lượng bạn cập nhật đã vượt quá số lượng sản phẩm tồn kho",

    //                 ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //             }

    //             if ($cart_item->product_variant_id) {

    //                 $getUniqueAttributes = new GetUniqueAttribute();
    //                 $product_variant = Product::query()->findOrFail($cart_item->product_id)->load(["variants", "variants.attributes"])->toArray();

    //                 $findVariant = $getUniqueAttributes->findVariantByAttributes($product_variant["variants"], $request->input('product_variant'));
    //                 if ($findVariant["quantity"] < $request->input('quantity')) {
    //                     return response()->json([
    //                         "message" => "Số lượng bạn cập nhật đã vượt quá số lượng sản phẩm tồn kho",

    //                     ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //                 }
    //                 $cart_item->product_variant_id = $findVariant["id"];
    //                 $cart_item->quantity = $request->input("quantity");
    //             } else {
    //                 $cart_item->quantity = $request->input("quantity");
    //             }
    //             $cart_item->save();

    //             return response()->json(["message" => "cập nhật giỏ hàng thành công."], Response::HTTP_OK);
    //         });
    //     } catch (\Exception $ex) {
    //         return response()->json([
    //             "message" => $ex->getMessage()
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function update(Request $request, string $id)
    {
        //
        try {
            return DB::transaction(function () use ($request, $id) {
                $request->validate([
                    "quantity" => "required|integer|min:1",
                    "product_variant" => "nullable|array" // Đảm bảo biến thể được gửi đúng định dạng
                ]);

                $cart_item = CartItem::query()->findOrFail($id);
                $product = Product::query()->findOrFail($cart_item->product_id);

                if ($cart_item->product_variant_id) {
                    // Nếu sản phẩm là biến thể
                    $getUniqueAttributes = new GetUniqueAttribute();
                    $product_variant = Product::query()
                        ->findOrFail($cart_item->product_id)
                        ->load(["variants", "variants.attributes"])
                        ->toArray();

                    $findVariant = $getUniqueAttributes->findVariantByAttributes(
                        $product_variant["variants"],
                        $request->input('product_variant')
                    );

                    // Tìm trong giỏ hàng nếu đã có biến thể này
                    $existingCartItem = CartItem::query()
                        ->where('product_id', $cart_item->product_id)
                        ->where('product_variant_id', $findVariant["id"])
                        ->where('id', '!=', $cart_item->id) // Loại trừ chính nó
                        ->first();

                    // Nếu biến thể mới đã tồn tại, cộng số lượng lại
                    if ($existingCartItem) {
                        $newQuantity = $existingCartItem->quantity + $request->input('quantity');

                        // Kiểm tra số lượng tồn kho
                        if ($findVariant["quantity"] < $newQuantity) {
                            return response()->json([
                                "message" => "Số lượng bạn cập nhật đã vượt quá số lượng sản phẩm tồn kho",
                            ], Response::HTTP_INTERNAL_SERVER_ERROR);
                        }

                        // Cập nhật số lượng biến thể đã tồn tại
                        $existingCartItem->quantity = $newQuantity;
                        $existingCartItem->save();

                        // Xóa mục hiện tại vì đã gộp vào mục khác
                        $cart_item->delete();
                    } else {
                        // Nếu chưa tồn tại, cập nhật mục hiện tại
                        if ($findVariant["quantity"] < $request->input('quantity')) {
                            return response()->json([
                                "message" => "Số lượng bạn cập nhật đã vượt quá số lượng sản phẩm tồn kho",
                            ], Response::HTTP_INTERNAL_SERVER_ERROR);
                        }

                        $cart_item->product_variant_id = $findVariant["id"];
                        $cart_item->quantity = $request->input("quantity");
                        $cart_item->save();
                    }
                } else {
                    // Nếu sản phẩm không phải là biến thể
                    if ($product->quantity < $request->input('quantity')) {
                        return response()->json([
                            "message" => "Số lượng bạn cập nhật đã vượt quá số lượng sản phẩm tồn kho",
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    $cart_item->quantity = $request->input("quantity");
                    $cart_item->save();
                }

                return response()->json(["message" => "Cập nhật giỏ hàng thành công."], Response::HTTP_OK);
            });
        } catch (\Exception $ex) {
            return response()->json([
                "message" => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //

        try {

            return DB::transaction(function () use ($id) {
                $cart_item_ids = json_decode($id);
                foreach ($cart_item_ids as $item_id) {
                    CartItem::query()->findOrFail($item_id)->delete();
                }
                $cart = Cart::query()->with('cartitems')->first()->toArray();
                if (empty($cart["cartitems"])) {

                    Cart::query()->findOrFail($cart["id"])->delete();
                }

                return response()->json(
                    [
                        "message" => "xóa dữ liệu thành công"
                    ]
                );
            });
        } catch (\Exception $ex) {
            return response()->json([
                "message" => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
