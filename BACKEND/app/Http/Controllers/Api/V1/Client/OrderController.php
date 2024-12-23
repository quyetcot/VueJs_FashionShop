<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Events\OrderStatusUpdated;
use Carbon\Carbon;
use App\Models\Cart;
use App\Models\User;
use App\Models\Order;
use App\Mail\OtpEmail;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\CartItem;
use App\Jobs\SendOtpEmail;
use App\Models\VoucherLog;
use App\Models\OrderDetail;
use App\Models\VoucherMeta;
use App\Models\VoucherUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Notifications\OrderConfirmationNotification;
use App\Http\Controllers\API\V1\Service\PaymentController;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            if (auth('sanctum')->check()) {
                $user_id = auth('sanctum')->id();
                // Get all orders for the authenticated user, including order details
                $orders = Order::query()
                    ->where('user_id', $user_id)
                    ->with([
                        'orderDetails',
                        'paymentMethod',
                        'returnRequests' 
                        // => function ($query) {
                        //     $query->where('status', '!=', 'canceled')->latest('id'); // Điều kiện loại bỏ "canceled" và sắp xếp theo id mới nhất
                        // }
                    ])
                    ->latest('id')
                    ->get();



                return response()->json($orders, 200);
            } else {
                return response()->json(['message' => 'User not authenticated'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        try {
            $data = $request->validated(); // Lấy dữ liệu đã xác thực
            // Kiểm tra xem người dùng có muốn mua ngay hay không
            $isImmediatePurchase = isset($data['product_id']) && isset($data['quantity']);
            $isCartPurchase = isset($data['cart_item_ids']) && is_array($data['cart_item_ids']) && count($data['cart_item_ids']) > 0;
            // Nếu cả hai trường hợp đều không đúng thì trả về lỗi
            if (!$isImmediatePurchase && !$isCartPurchase || $isImmediatePurchase && $isCartPurchase) {
                return response()->json(['message' => 'Phải chọn mua ngay hoặc mua từ giỏ hàng.'], Response::HTTP_BAD_REQUEST);
            }
            $user = $this->getUser($data);
            $response = DB::transaction(function () use ($data, $user, $isImmediatePurchase, $isCartPurchase) {
                // Tạo đơn hàng
                $order = $this->createOrder($data, $user);
                broadcast(new OrderStatusUpdated($order))->toOthers();
                $totalQuantity = 0;
                $totalPrice = 0.00;
                $errors = [];
                if ($isImmediatePurchase) {
                    list($quantity, $price, $errors) = $this->addImmediatePurchase($data, $order);
                    $totalQuantity += $quantity;
                    $totalPrice += $price;
                }

                if ($isCartPurchase) {
                    if (auth('sanctum')->check()) {
                        list($quantity, $price, $errors) = $this->addCartItemsToOrder($data, $user, $order);
                        $totalQuantity += $quantity;
                        $totalPrice += $price;
                    } else {
                        DB::rollBack();
                        return response()->json(['message' => 'Vui lòng đăng nhập để mua hàng từ giỏ hàng.'], Response::HTTP_UNAUTHORIZED);
                    }
                }
                // Áp dụng voucher nếu có
                if (isset($data['voucher_code']) && auth('sanctum')->check()) {
                    $voucher_result = $this->applyVoucher($data['voucher_code'], $order->orderDetails, $order->id);
                    if (isset($voucher_result['error'])) {
                        return response()->json(['message' => $voucher_result['error']], Response::HTTP_BAD_REQUEST);
                    }
                    $totalPrice -= $voucher_result['total_discount'];
                    $voucher = $voucher_result['voucher'];
                    // Cập nhật voucher_id và voucher_discount cho order
                    $order->update([
                        'voucher_id' => $voucher->id,
                        'voucher_discount' => $voucher_result['voucher_discount'],
                    ]);
                    // Cập nhật discount cho từng order detail
                    foreach ($voucher_result['eligible_products'] as $product) {
                        // Giả sử bạn có thể xác định variant_id từ sản phẩm
                        $variantId = isset($product['product_variant_id']) ? $product['product_variant_id'] : null;
                        // Tìm kiếm orderDetail dựa trên product_id và variant_id (nếu có)
                        $orderDetail = $order->orderDetails()
                            ->where('product_id', $product['product_id'])
                            ->where('product_variant_id', $variantId)
                            ->first();
                        // dd($orderDetail);
                        if ($orderDetail) {
                            // Cập nhật discount
                            $orderDetail->update([
                                'discount' => $product['voucher_discount'] ?? 0,
                            ]);
                        }
                    }
                    $voucher->increment('used_count', 1);
                }
                // Cập nhật tổng số lượng và tổng tiền cho đơn hàng
                $order->update([
                    'total_quantity' => $totalQuantity,
                    'total' => $totalPrice,
                ]);
                if (count($errors)) {
                    // dd($errors);
                    // Kiểm tra nếu có lỗi trong 'out_of_stock' hoặc 'insufficient_stock'
                    $hasOutOfStockError = !empty($errors['out_of_stock']);
                    $hasInsufficientStockError = !empty($errors['insufficient_stock']);

                    if ($hasOutOfStockError || $hasInsufficientStockError) {
                        DB::rollBack(); // Rollback nếu có lỗi
                        if ($isCartPurchase) {

                            // Lấy lại giỏ hàng
                            $cart = Cart::query()
                                ->where('user_id', $user['id'])
                                ->with('cartitems.product', 'cartitems.productvariant.attributes')
                                ->first();

                            foreach ($cart->cartitems as $key => $cartItem) {
                                // Kiểm tra nếu sản phẩm hết hàng
                                // dd($errors['insufficient_stock'][$key]['cart_id']);
                                if ($hasOutOfStockError) {
                                    foreach ($errors['out_of_stock'] as $error) {
                                        // dd($error['cart_id']); 
                                        // Nếu sản phẩm hết hàng, xóa sản phẩm khỏi giỏ hàng
                                        if ($error['cart_id'] == $cartItem->id) {
                                            $cartItem->delete();
                                        }
                                    }
                                }
                                // Kiểm tra nếu số lượng yêu cầu lớn hơn số lượng có sẵn
                                if ($hasInsufficientStockError) {
                                    foreach ($errors['insufficient_stock'] as $error) {
                                        // dd($error['cart_id']); 
                                        // Nếu sản phẩm hết hàng, xóa sản phẩm khỏi giỏ hàng
                                        if ($error['cart_id'] == $cartItem->id) {
                                            $availableQuantity = $cartItem->productvariant ? $cartItem->productvariant->quantity : $cartItem->product->quantity;
                                            $cartItem->update(['quantity' => $availableQuantity]); // Cập nhật lại số lượng
                                        }
                                    }
                                    // Nếu không đủ số lượng, cập nhật lại số lượng trong giỏ hàng

                                }
                            }
                        }
                        // Trả về các lỗi
                        return response()->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
                    }
                }

                if (!auth('sanctum')->check()) {
                    // Gửi notification cho người dùng với email nhận thông báo
                    Notification::route('mail', $order->user_email)
                        ->notify(new OrderConfirmationNotification($order, $order->user_email, $order->orderDetails));
                    // Gửi email xác nhận đơn hàng cho khách hàng
                }

                // Thực hiện thanh toán nếu chọn phương thức online (VNPay)
                if ($data['payment_method_id'] == 2) {
                    $payment = new PaymentController();
                    $response = $payment->createPayment($order);

                    // Chuyển hướng người dùng đến trang thanh toán
                    return response()->json(['payment_url' => $response['payment_url']], Response::HTTP_OK);
                }
                return response()->json($order->load('orderDetails')->toArray(), Response::HTTP_CREATED);
            });
            return $response;
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    // Hàm lấy thông tin người dùng
    protected function getUser($data)
    {
        if (auth('sanctum')->check()) {
            $user_id = auth('sanctum')->id();
            $user = User::findOrFail($user_id)->only(['id', 'name', 'email', 'address', 'phone_number']);
        } else {
            $user = [
                'id' => null,
                'name' => $data['ship_user_name'],
                'email' => $data['ship_user_email'],
                'address' => $data['ship_user_address'],
                'phone_number' => $data['ship_user_phonenumber'],
            ];
        }
        return $user;
    }

    // Hàm tạo đơn hàng
    protected function createOrder($data, $user)
    {
        return Order::create([
            'user_id' => $user['id'],
            'payment_method_id' => $data['payment_method_id'],
            'total_quantity' => 0,
            'total' => 0.00,
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'user_phonenumber' => $user['phone_number'],
            'user_address' => $user['address'],
            'user_note' => $data['user_note'] ?? '',
            'ship_user_name' => $data['ship_user_name'],
            'ship_user_phonenumber' => $data['ship_user_phonenumber'],
            'ship_user_address' => $data['ship_user_address'] . ', ' .
                $data['xa'] . ', ' .
                $data['huyen'] . ', ' .
                $data['tinh'],
            'shipping_method' => $data['shipping_method'],
            'shipping_fee' => $data['shipping_fee'],
            'voucher_id' => null,
            'voucher_discount' => 0,
        ]);
    }

    // Hàm thêm sản phẩm mua ngay vào đơn hàng
    protected function addImmediatePurchase($data, $order)
    {
        $product = Product::findOrFail($data['product_id']);
        $productPrice = $product->price_sale > 0 ? $product->price_sale : $product->price_regular;
        $quantity = $data['quantity'];

        $errors = [
            'out_of_stock' => [], // Lỗi sản phẩm hết hàng
            'insufficient_stock' => [], // Lỗi số lượng không đủ
        ];

        // Kiểm tra nếu sản phẩm có biến thể
        if ($product->type == 1) {
            $variant = ProductVariant::with('attributes')->findOrFail($data['product_variant_id']);
            $variantPrice = $variant->price_sale;
            // $productPrice = $variant->price_sale > 0 ? $variant->price_sale : $variant->price_regular;
            $productPrice = $variantPrice;
            // Kiểm tra tồn kho của biến thể
            if ($variant->quantity == 0) {
                $errors['out_of_stock'][] = [
                    'message' => "Sản phẩm {$product->name} đã hết hàng.",
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                ];
                // return ['errors' => $errors];
            }

            if ($quantity > $variant->quantity && $variant->quantity !== 0) {
                $quantity = $variant->quantity; // Giới hạn số lượng
                $errors['insufficient_stock'][] = [
                    'message' => "Số lượng sản phẩm {$product->name} không đủ. Bạn chỉ có thể mua tối đa {$variant->quantity} sản phẩm.",
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                ];
            }

            // Cập nhật số lượng tồn kho
            $variant->decrement('quantity', $quantity);

            // Lưu thông tin thuộc tính
            $attributes = [];
            foreach ($variant->attributes as $attribute) {
                $attributes[$attribute->name] = $attribute->pivot->value;
            }
        } else {
            // Nếu không có biến thể
            if ($product->quantity == 0) {
                $errors['out_of_stock'][] = [
                    'message' => "Sản phẩm $product->name hiện đã hết hàng. Vui lòng kiểm tra và xác nhận lại đơn hàng.",
                    'product_id' => $product->id,
                ];
                // return ['errors' => $errors];
            }

            if ($quantity > $product->quantity && $product->quantity !== 0) {
                $quantity = $product->quantity; // Giới hạn số lượng
                $errors['insufficient_stock'][] = [
                    'message' => "Số lượng sản phẩm {$product->name} không đủ. Bạn chỉ có thể mua tối đa {$product->quantity} sản phẩm.",
                    'product_id' => $product->id,
                ];
            }

            // Cập nhật số lượng tồn kho
            $product->decrement('quantity', $quantity);

            $attributes = null;
        }

        // Tạo chi tiết đơn hàng
        $this->createOrderDetail($order, $product, $data['product_variant_id'] ?? null, $productPrice, $quantity, $attributes);

        return [$quantity, $productPrice * $quantity, $errors];
    }
    // Hàm thêm sản phẩm từ giỏ hàng vào đơn hàng
    protected function addCartItemsToOrder($data, $user, $order)
    {
        $cartItemIds = $data['cart_item_ids'];
        $errors = [
            'out_of_stock' => [], // Lỗi sản phẩm hết hàng
            'insufficient_stock' => [], // Lỗi số lượng không đủ
        ];
        // $quantities = $data['quantityOfCart'];
        $cart = Cart::query()
            ->where('user_id', $user['id'])
            ->with('cartitems.product', 'cartitems.productvariant.attributes')
            ->first();
        if (!$cart || $cart->cartitems->isEmpty()) {
            return response()->json(['message' => 'Giỏ hàng trống, vui lòng thêm sản phẩm vào giỏ hàng trước khi thanh toán.'], 400);
        }
        $totalQuantity = 0;
        $totalPrice = 0.00;
        $validCartItemFound = false;
        foreach ($cart->cartitems as $cartItem) {
            if (in_array($cartItem->id, $cartItemIds)) {
                $product = $cartItem->product;
                $variant = $cartItem->productvariant;
                $validCartItemFound = true;
                $quantity = $cartItem->quantity;

                // Kiểm tra số lượng tồn kho của biến thể (nếu có)
                if ($variant) {
                    $availableQuantity = $variant->quantity;
                    // $productPrice = $variant->price_sale > 0 ? $variant->price_sale : $variant->price_regular;
                    $productPrice = $variant->price_sale;
                } else { // Kiểm tra số lượng tồn kho của sản phẩm nếu không có biến thể
                    $availableQuantity = $product->quantity;
                    $productPrice = $product->price_sale > 0 ? $product->price_sale : $product->price_regular;
                }
                // Nếu không còn sản phẩm trong kho
                if ($availableQuantity == 0) {
                    $errors['out_of_stock'][] = [
                        'message' => "Sản phẩm $product->name hiện đã hết hàng và hệ thống đã tự động loại bỏ khỏi giỏ hàng của bạn. Vui lòng kiểm tra và xác nhận lại đơn hàng.",
                        'product_id' => $product->id,
                        'cart_id' => $cartItem->id,
                    ];
                    // $cartItem->delete();
                    continue; // Bỏ qua sản phẩm này
                }
                // Nếu số lượng yêu cầu mua lớn hơn số lượng tồn kho, điều chỉnh số lượng và thông báo
                if ($quantity > $availableQuantity) {
                    $quantity = $availableQuantity; // Giảm số lượng về tối đa có thể mua
                    $errors['insufficient_stock'][] = [
                        'message' => "Số lượng sản phẩm $product->name trong kho không đủ. Bạn chỉ có thể mua tối đa $availableQuantity sản phẩm.",
                        'product_id' => $product->id,
                        'cart_id' => $cartItem->id,
                    ];
                    // $cartItem->update([
                    //     'quantity' => $availableQuantity
                    // ]);
                    // Bạn có thể lưu thông báo này vào session hoặc trả về cho người dùng để hiển thị
                }
                // Tạo chi tiết đơn hàng
                $attributes = $variant ? $variant->attributes->pluck('pivot.value', 'name')->toArray() : null;
                $this->createOrderDetail($order, $product, $variant->id ?? null, $productPrice, $quantity, $attributes);

                $totalQuantity += $quantity;
                $totalPrice += $productPrice * $quantity;

                // Giảm số lượng tồn kho
                if ($variant) {
                    $variant->decrement('quantity', $quantity);
                } else {
                    $product->decrement('quantity', $quantity);
                }
            }
        }

        // Nếu không tìm thấy sản phẩm hợp lệ trong giỏ hàng
        if (!$validCartItemFound) { // Rollback nếu có lỗi
            return response()->json([
                'message' => 'Không có sản phẩm nào trong giỏ hàng phù hợp với yêu cầu của bạn.',
            ], 400);
        }

        // Xóa các sản phẩm đã mua trong giỏ hàng
        CartItem::whereIn('id', $cartItemIds)->delete();
        return [$totalQuantity, $totalPrice, $errors];
    }

    // Hàm tạo chi tiết đơn hàng
    protected function createOrderDetail($order, $product, $variantId, $price, $quantity, $attributes)
    {
        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
            'product_name' => $product->name,
            'product_img' => $product->img_thumbnail,
            'attributes' => $attributes,
            'quantity' => $quantity,
            'price' => $price,
            'total_price' => $price * $quantity,
            'discount' => 0,
        ]);
    }

    // Hàm áp dụng voucher
    protected function applyVoucher($voucher_code, $order_items, $order_id)
    {
        $user_id = auth('sanctum')->id();
        $voucher = Voucher::where('code', $voucher_code)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();
        $voucher->increment('used_count');
        $voucher_metas = VoucherMeta::where('voucher_id', $voucher->id)->pluck('meta_value', 'meta_key')->toArray();
        $eligible_products = [];
        $ineligible_products = [];
        $sub_total = 0;

        foreach ($order_items as $item) {
            $product_id = $item['product']->id;
            $category_id = $item['product']->category_id;
            $is_eligible = true;
            $reasons = [];

            // 1. Kiểm tra sản phẩm có bị loại trừ không (luôn ưu tiên)
            if (isset($voucher_metas['_voucher_exclude_product_ids'])) {
                $excluded_product_ids = json_decode($voucher_metas['_voucher_exclude_product_ids'], true);
                if (in_array($product_id, $excluded_product_ids)) {
                    $is_eligible = false;
                    $reason[] = 'Sản phẩm ' . $item['product']->name . ' nằm trong danh sách bị loại trừ khỏi voucher.';
                }
            }
            // 2. Kiểm tra sản phẩm có được áp dụng không (ưu tiên thứ 2)
            if ($is_eligible && isset($voucher_metas['_voucher_product_ids']) && !isset($voucher_metas['_voucher_category_ids'])) {
                $allowed_product_ids = json_decode($voucher_metas['_voucher_product_ids'], true);
                if (in_array($product_id, $allowed_product_ids)) {
                    $reason[] = 'Sản phẩm ' . $item['product']->name . ' đã được áp mã giảm giá';
                    $item['reason'] = implode(' ', $reason);
                    $eligible_products[] = $item;
                    $sub_total += $item['total_price'];
                    continue;
                } else {
                    $is_eligible = false;
                    $reason[] = 'Sản phẩm ' . $item['product']->name . 'không nằm trong danh sách áp dụng voucher.';
                }
            }
            if ($is_eligible && isset($voucher_metas['_voucher_product_ids']) && isset($voucher_metas['_voucher_category_ids'])) {
                $allowed_product_ids = json_decode($voucher_metas['_voucher_product_ids'], true);
                if (in_array($product_id, $allowed_product_ids)) {
                    $reason[] = 'Sản phẩm ' . $item['product']->name . ' đã được áp mã giảm giá';
                    $item['reason'] = implode(' ', $reason);
                    $eligible_products[] = $item;
                    $sub_total += $item['total_price'];
                    continue;
                }
            }
            // 3. Nếu sản phẩm không được áp dụng rõ ràng, kiểm tra theo danh mục
            if ($is_eligible && isset($voucher_metas['_voucher_exclude_category_ids'])) {
                $excluded_category_ids = json_decode($voucher_metas['_voucher_exclude_category_ids'], true);
                if (in_array($category_id, $excluded_category_ids)) {
                    $is_eligible = false;
                    $reason[] = 'Danh mục của sản phẩm ' . $item['product']->name . ' bị loại trừ khỏi voucher.';
                }
            }

            if ($is_eligible && isset($voucher_metas['_voucher_category_ids'])) {
                $allowed_category_ids = json_decode($voucher_metas['_voucher_category_ids'], true);
                if (!in_array($category_id, $allowed_category_ids)) {
                    $is_eligible = false;
                    $reason[] = 'Danh mục của sản phẩm ' . $item['product']->name . ' không nằm trong danh mục được áp dụng voucher.';
                }
            }
            // Phân loại sản phẩm dựa trên tính hợp lệ
            if ($is_eligible) {
                $eligible_products[] = $item;
                $sub_total += $item['total_price'];
            } else {
                $item['reason'] = implode(' ', $reasons);
                $ineligible_products[] = $item;
            }
        }
        // Kiểm tra giá trị tối thiểu của đơn hàng
        if (isset($voucher_metas['_voucher_min_order_value']) && $sub_total < $voucher_metas['_voucher_min_order_value']) {
            return [
                'error' => "Tổng giá trị đơn hàng phải lớn hơn " . $voucher_metas['_voucher_min_order_value'] . " để áp dụng voucher này.",
                'ineligible_products' => $ineligible_products,
            ];
        }

        $voucher_discount = $this->calculateDiscount($voucher, $sub_total, $voucher_metas, $eligible_products);

        // Lưu thông tin vào `voucher_log`
        VoucherLog::create([
            'voucher_id' => $voucher->id,
            'user_id' => $user_id,
            'order_id' => $order_id,
            'action' => 'used'
        ]);

        // Lưu vào `voucher_user` hoặc cập nhật số lần sử dụng
        $voucherUser = VoucherUser::where('user_id', $user_id)
            ->where('voucher_id', $voucher->id)
            ->first();

        if ($voucherUser) {
            $voucherUser->increment('usage_count');
        } else {
            VoucherUser::create([
                'user_id' => $user_id,
                'voucher_id' => $voucher->id,
                'usage_count' => 1,
            ]);
        }
        return [
            'voucher' => $voucher,
            'voucher_discount' => $voucher_discount['total_discount'],
            'voucher_description' => $voucher_discount['voucher_description'],
            'eligible_products' => $eligible_products,
            'ineligible_products' => $ineligible_products,
            'total_discount' => $voucher_discount['total_discount'],
            'sub_total_after_discount' => $sub_total - $voucher_discount['total_discount'],
        ];
    }
    // Hàm kiểm tra điều kiện sản phẩm để áp dụng voucher
    // protected function checkEligibility($item, $voucher_metas)
    // {
    //     $product_id = $item['product']->id;
    //     $category_id = $item['product']->category_id;
    //     $is_eligible = true;
    //     $reasons = [];

    //     // 1. Kiểm tra sản phẩm có bị loại trừ không (luôn ưu tiên)
    //     if (isset($voucher_metas['_voucher_exclude_product_ids'])) {
    //         $excluded_product_ids = json_decode($voucher_metas['_voucher_exclude_product_ids'], true);
    //         if (in_array($product_id, $excluded_product_ids)) {
    //             $is_eligible = false;
    //             $reason[] = 'Sản phẩm ' . $item['product']->name . ' nằm trong danh sách bị loại trừ khỏi voucher.';
    //         }
    //     }
    //     // 2. Nếu sản phẩm không được áp dụng rõ ràng, kiểm tra theo danh mục
    //     if ($is_eligible) {
    //         if (isset($voucher_metas['_voucher_exclude_category_ids'])) {
    //             $excluded_category_ids = json_decode($voucher_metas['_voucher_exclude_category_ids'], true);
    //             if (in_array($category_id, $excluded_category_ids)) {
    //                 $is_eligible = false;
    //                 $reason[] = 'Danh mục của sản phẩm ' . $item['product']->name . ' bị loại trừ khỏi voucher.';
    //             }
    //         }

    //         if ($is_eligible && isset($voucher_metas['_voucher_category_ids'])) {
    //             $allowed_category_ids = json_decode($voucher_metas['_voucher_category_ids'], true);
    //             if (!in_array($category_id, $allowed_category_ids)) {
    //                 $is_eligible = false;
    //                 $reason[] = 'Danh mục của sản phẩm ' . $item['product']->name . ' không nằm trong danh mục được áp dụng voucher.';
    //             }
    //         }
    //     }
    //     // 3. Kiểm tra sản phẩm có được áp dụng không (ưu tiên trước danh mục)
    //     if (isset($voucher_metas['_voucher_product_ids'])) {
    //         $allowed_product_ids = json_decode($voucher_metas['_voucher_product_ids'], true);
    //         if (in_array($product_id, $allowed_product_ids)) {
    //             $is_eligible = true; // Đảm bảo sản phẩm được ưu tiên áp dụng
    //             $reason = []; // Xóa lý do trước đó vì sản phẩm hợp lệ
    //         }
    //     }
    //     return [
    //         'status' => $is_eligible,
    //         'reason' => implode(' ', $reasons),
    //     ];
    // }

    // Hàm tính toán giảm giá
    protected function calculateDiscount($voucher, $sub_total, $voucher_metas, $eligible_products)
    {
        $voucher_discount = 0;
        $voucher_description = '';

        if (isset($voucher_metas['_voucher_applies_to_total']) && $voucher_metas['_voucher_applies_to_total']) {
            if ($voucher->discount_type == 'percent') {
                $voucher_discount = ($voucher->discount_value / 100) * $sub_total;
                $voucher_description = "{$voucher->discount_value} percent";
                if (isset($voucher_metas['_voucher_max_discount_amount']) && $voucher_metas['_voucher_max_discount_amount']) {
                    if ($voucher_metas['_voucher_max_discount_amount'] < $voucher_discount) {
                        $voucher_discount = $voucher_metas['_voucher_max_discount_amount'];
                    }
                }
            } elseif ($voucher->discount_type == 'fixed') {
                $voucher_discount = min($voucher->discount_value, $sub_total);
                $voucher_description = "{$voucher->discount_value} fixed";
            }
            return ['total_discount' => $voucher_discount, 'voucher_description' => $voucher_description];
        }

        foreach ($eligible_products as $item) {
            $item_discount = $voucher->discount_type == 'percent'
                ? ($voucher->discount_value / 100) * $item['total_price']
                : min($voucher->discount_value, $item['total_price']);
            $voucher_discount += $item_discount;
            $item['voucher_discount'] = $item_discount;
            $item['price_after_discount'] = $item['total_price'] - $item_discount;
        }

        return ['total_discount' => $voucher_discount, 'voucher_description' => "{$voucher->discount_value} " . $voucher->discount_type];
    }
    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        try {
            if (!auth('sanctum')->check()) {
                return response()->json(['message' => 'Người dùng chưa được xác thực'], 401);
            }

            $user_id = auth('sanctum')->id();

            // Kiểm tra xem đơn hàng có thuộc về người dùng đã xác thực không
            if ($order->user_id !== $user_id) {
                return response()->json(['message' => 'Không có quyền truy cập'], 403);
            }
            // Thông tin chi tiết đơn hàng
            $order->load(['orderDetails']);



            // Kiểm tra kết quả
            // dd($orderArray);


            // Trả về dữ liệu đơn hàng cùng với chi tiết dưới dạng JSON
            return response()->json([
                'order' => $order,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Đã xảy ra lỗi khi lấy thông tin đơn hàng', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */

    /**public function update(UpdateOrderRequest $request, Order $order)
    {
        if (!auth('sanctum')->check()) {
            return response()->json(['message' => 'Không có quyền truy cập'], 403);
        }

        $user_id = auth('sanctum')->id();

        // Kiểm tra quyền sở hữu
        if ($order->user_id !== $user_id) {
            return response()->json(['message' => 'Không có quyền truy cập'], 403);
        }

        // Kiểm tra trạng thái không hợp lệ
        if (in_array($order->order_status, [Order::STATUS_CANCELED, Order::STATUS_COMPLETED])) {
            return response()->json(['message' => 'Đơn hàng không thể cập nhật vì đã hoàn thành hoặc đã bị hủy.'], 400);
        }
        // Kiểm tra trạng thái đơn hàng
        if ($order->order_status === Order::STATUS_COMPLETED) {
            return response()->json(['message' => 'Đơn hàng đã hoàn thành.'], 400);
        }

        $order_status = $request->input('order_status');

        // Xử lý các trạng thái
        switch ($order_status) {
            case Order::STATUS_CANCELED:
                if (!in_array($order->order_status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])) {
                    return response()->json([
                        'message' => 'Chỉ có thể hủy đơn hàng khi đơn hàng đang ở trạng thái Đang chờ xác nhận hoặc Đã xác nhận.'
                    ], 400);
                }

                $user_note = $request->input('user_note');
                $this->handleOrderCancellation($order, $user_note);

                // Cập nhật trạng thái voucher nếu cần
                $voucher_logs = VoucherLog::query()
                    ->where('user_id', $user_id)
                    ->where('order_id', $order->id)
                    ->first();

                if ($voucher_logs) {
                    $voucher_logs->update(['action' => 'reverted']);
                }
                $order->order_status = $order_status;
                break;

            case Order::STATUS_COMPLETED:
                if ($order->order_status !== Order::STATUS_SUCCESS) {
                    return response()->json([
                        'message' => 'Chỉ có thể hoàn thành đơn hàng khi đơn hàng đang ở trạng thái giao hàng thành công.'
                    ], 400);
                }
                $order->order_status = $order_status;
                break;

            default:
                return response()->json(['message' => 'Trạng thái không hợp lệ.'], 400);
        }
        if ($order_status === Order::STATUS_COMPLETED) {
            if (!in_array($order->order_status, [Order::STATUS_SUCCESS])) {
                return response()->json([
                    'message' => 'Chỉ có thể hủy đơn hàng khi đơn hàng đang ở trạng thái giao hàng thành công.'
                ], 400);
            }
            $order->order_status = $order_status;
        }
        $order->save();

        return response()->json([
            'message' => 'Trạng thái đơn hàng đã được cập nhật thành công.',
            'order' => $order->load('orderDetails'),
        ]);
    }
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        try {
            if (!auth('sanctum')->check()) {
                return response()->json(['message' => 'Không có quyền truy cập'], 403);
            }

            $user_id = auth('sanctum')->id();

            // Kiểm tra quyền sở hữu
            if ($order->user_id !== $user_id) {
                return response()->json(['message' => 'Không có quyền truy cập'], 403);
            }

            // Kiểm tra trạng thái không hợp lệ
            if (in_array($order->order_status, [Order::STATUS_CANCELED, Order::STATUS_COMPLETED])) {
                return response()->json(['message' => 'Đơn hàng không thể cập nhật vì đã hoàn thành hoặc đã bị hủy.'], 400);
            }

            $order_status = $request->input('order_status');

            // Xử lý các trạng thái
            switch ($order_status) {
                case Order::STATUS_CANCELED:
                    if (!in_array($order->order_status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])) {
                        return response()->json([
                            'message' => 'Chỉ có thể hủy đơn hàng khi đơn hàng đang ở trạng thái Đang chờ xác nhận hoặc Đã xác nhận.'
                        ], 400);
                    }

                    $user_note = $request->input('user_note');
                    $this->handleOrderCancellation($order, $user_note);

                    // Cập nhật trạng thái voucher nếu cần
                    $voucher_logs = VoucherLog::query()
                        ->where('user_id', $user_id)
                        ->where('order_id', $order->id)
                        ->first();

                    if ($voucher_logs) {
                        $voucher_logs->update(['action' => 'reverted']);
                    }
                    $order->order_status = $order_status;
                    break;

                case Order::STATUS_COMPLETED:
                    if ($order->order_status !== Order::STATUS_SUCCESS) {
                        return response()->json([
                            'message' => 'Chỉ có thể hoàn thành đơn hàng khi đơn hàng đang ở trạng thái giao hàng thành công.'
                        ], 400);
                    }
                    $order->order_status = $order_status;
                    break;

                default:
                    return response()->json(['message' => 'Trạng thái không hợp lệ.'], 400);
            }

            $order->save();

            broadcast(new OrderStatusUpdated($order))->toOthers();

            return response()->json([
                'message' => 'Trạng thái đơn hàng đã được cập nhật thành công.',
                'order' => $order->load('orderDetails'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Đã xảy ra lỗi khi lấy thông tin đơn hàng', 'error' => $e->getMessage()], 500);
        }
    }
    protected function handleOrderCancellation(Order $order, string $user_note)
    {
        // Lưu lý do hủy vào ghi chú
        $order->return_notes = $user_note;
        // Trả lại số lượng sản phẩm về kho
        foreach ($order->orderDetails as $detail) {
            // Kiểm tra nếu là sản phẩm có biến thể
            if ($detail->product_variant_id) {
                $variant = ProductVariant::find($detail->product_variant_id);
                if ($variant) {
                    $variant->increment('quantity', $detail->quantity);
                }
            } else {
                // Nếu là sản phẩm đơn
                $product = Product::find($detail->product_id);
                if ($product) {
                    $product->increment('quantity', $detail->quantity);
                }
            }
        }
    }
    public function searchOrder(Request $request)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'type' => 'required|in:phoneNumber,email',
            'contact' => ['required', function ($attribute, $value, $fail) use ($request) {
                if ($request->type === 'phoneNumber') {
                    // Validate số điện thoại Việt Nam
                    if (!preg_match('/^(0|\+84)[3|5|7|8|9][0-9]{8}$/', $value)) {
                        $fail('Số điện thoại không hợp lệ.');
                    }
                } elseif ($request->type === 'email') {
                    // Validate email
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('Email không hợp lệ.');
                    }
                }
            }],
            'order_code' => 'required_if:type,email|string',
        ]);

        // Nếu type là email, tìm đơn hàng với order_code và email
        if ($request->type == "email") {
            $order = Order::where('order_code', $request->order_code)
                ->where('user_email', $request->contact)
                ->first();
        }

        // Nếu type là phoneNumber, tìm đơn hàng với số điện thoại
        if ($request->type == "phoneNumber") {
            $order = Order::query()->with('orderDetails')
                ->where('ship_user_phonenumber', $request->contact)->latest('id')
                ->get();
        }
        // Nếu không tìm thấy đơn hàng
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy đơn hàng, thông tin bạn cung cấp không đúng!',
            ], 400);
        }

        // Nếu type là email, kiểm tra OTP
        if ($request->type == "email") {
            // Kiểm tra xem người dùng đã yêu cầu OTP trước đó chưa
            $existingOtp = DB::table('order_otp_verifications')
                ->where('contact', $request->contact)
                ->orderBy('expires_at', 'desc')
                ->first();

            // Nếu đã có OTP trước đó và OTP chưa hết hạn
            if ($existingOtp && Carbon::parse($existingOtp->expires_at)->isFuture()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bạn đã yêu cầu mã OTP trước đó. Vui lòng đợi cho đến khi mã OTP cũ hết hiệu lực.',
                ], 400);
            }

            // Tạo mã OTP ngẫu nhiên
            $otpCode = rand(100000, 999999);
            $otpExpiresAt = Carbon::now()->addMinutes(3); // Mã OTP hết hạn sau 3 phút

            // Lưu mã OTP và thời gian hết hạn vào bảng order_otp_verifications
            DB::table('order_otp_verifications')->insert([
                'order_code' => $request->order_code ?? '',
                'contact' => $request->contact,
                'otp' => $otpCode,
                'expires_at' => $otpExpiresAt,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Đẩy việc gửi email OTP vào queue
            SendOtpEmail::dispatch($request->contact, $otpCode);

            return response()->json([
                'status' => 'success',
                'message' => 'Mã OTP đã được gửi đến email của bạn.',
            ], 200);
        }

        // Trả về thông tin đơn hàng nếu không cần xác minh OTP
        return response()->json([
            'status' => 'success',
            'order' => $order,
        ], 200);
    }
    public function verifyOtp(Request $request)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'contact' => 'required|email',
            'order_code' => 'required|string',
            'otp' => 'required|string',
        ]);

        // Kiểm tra mã OTP trong bảng order_otp_verifications
        $otpVerification = DB::table('order_otp_verifications')
            ->where('order_code', $request->order_code)
            ->where('contact', $request->contact)
            ->where('otp', $request->otp)
            ->first();

        // Nếu không tìm thấy OTP hoặc OTP đã hết hạn
        if (!$otpVerification || Carbon::now()->gt(Carbon::parse($otpVerification->expires_at))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.',
            ], 400);
        }

        // Tìm đơn hàng sau khi xác minh OTP thành công
        $order = Order::query()
            ->with('orderDetails')
            ->where("order_code", "=", $otpVerification->order_code)
            ->first();

        // Xác minh OTP thành công
        return response()->json([
            'status' => 'success',
            'order' => $order,
        ], 200);
    }
    // thanh toán lại(chưa xong)
    function handlePayment(Request $request)
    {
        try {
            // Validate orderId là số và tồn tại trong bảng orders
            $request->validate([
                'orderId' => 'required|numeric|exists:orders,id',
            ]);
            $user_id = auth('sanctum')->id() ?? null;

            // Tìm đơn hàng dựa vào ID
            $order = Order::query()
                ->where('user_id', $user_id)
                ->where('id', $request->orderId)
                ->where('payment_method_id', 2)
                ->where('payment_status', Order::PAYMENT_PENDING)
                ->first();
            // Kiểm tra xem đơn hàng có tồn tại không
            if (!$order) {
                return response()->json([
                    'message' => 'Đơn hàng không tồn tại hoặc không thể xử lý thanh toán.'
                ], Response::HTTP_NOT_FOUND);
            }
            // Xử lý thanh toán COD
            $order->update([
                'payment_method_id' => 1, // Cập nhật phương thức thanh toán thành COD
            ]);

            return response()->json([
                'message' => 'Phương thức thanh toán đã được chuyển sang COD.',
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
