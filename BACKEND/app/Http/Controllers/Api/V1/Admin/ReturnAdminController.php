<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Events\OrderStatusUpdated;
use App\Events\ReturnItemStatusUpdated;
use App\Events\ReturnRequestStatusUpdate;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnItem;
use App\Models\ReturnLog;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPSTORM_META\map;

class ReturnAdminController extends Controller
{

    public function getReturnRequests(Request $request)
    {
        try {
            // Lấy danh sách return_request cùng các item, đơn hàng và sản phẩm liên quan
            $returnRequests = ReturnRequest::with([
                'items.orderDetail.product', // Thêm chi tiết sản phẩm từ order_detail
                'order'                      // Thông tin đơn hàng
            ])
                ->orderBy('created_at', 'desc')
                ->get()
                // dd($returnRequests->toArray());

                ->map(function ($returnRequest) {
                    return [
                        'id' => $returnRequest->id,
                        'order_id' => $returnRequest->order_id,
                        'user_name' => $returnRequest->user->name,
                        'reason' => $returnRequest->reason,
                        'status' => $returnRequest->status,
                        'total_refund_amount' => $returnRequest->total_refund_amount,
                        'created_at' => $returnRequest->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $returnRequest->updated_at->format('Y-m-d H:i:s'),

                        'items' => $returnRequest->items->map(function ($item) use ($returnRequest) {
                            return [
                                'id' => $item->id,
                                'request_id' => $item->return_request_id,
                                'order_detail_id' => $item->order_detail_id,
                                'image' => $item->image,
                                'quantity' => $item->quantity,
                                'status' => $item->status,
                                'refund_amount' => $item->refund_amount,
                                'order' => [
                                    'id' => $returnRequest->order->id,
                                    'total' => $returnRequest->order->total,
                                    'total_quantity' => $returnRequest->order->total_quantity,
                                    'order_status' => $returnRequest->order->order_status,
                                    'order_code' => $returnRequest->order->order_code,
                                    'payment_status' => $returnRequest->order->payment_status,

                                    'order_detail' =>
                                    [
                                        "id" => $item->orderDetail->id,
                                        "product_id" => $item->orderDetail->product_id,
                                        "product_variant_id" => $item->orderDetail->product_variant_id,
                                        "order_id" => $item->orderDetail->order_id,
                                        "product_name" => $item->orderDetail->product_name,
                                        "product_img" => $item->orderDetail->product_img,
                                        "attributes" => $item->orderDetail->attributes,
                                        "quantity" => $item->orderDetail->quantity,
                                        "price" => $item->orderDetail->price,
                                        "total_price" => $item->orderDetail->total_price,
                                        "discount" => $item->orderDetail->discount,
                                        "created_at" => $item->orderDetail->created_at,
                                        "updated_at" => $item->orderDetail->updated_at,
                                    ]

                                ],

                            ];
                        }),
                    ];
                });

            return response()->json([
                'message' => 'Return requests retrieved successfully.',
                'data' => $returnRequests,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    public  function showReturnItem($id)
    {
        try {
            // Lấy danh sách return_request cùng các item, đơn hàng và sản phẩm liên quan
            $showReturnItem = ReturnRequest::with([
                'items.orderDetail.product', // Thêm chi tiết sản phẩm từ order_detail
                'order'                      // Thông tin đơn hàng
            ])
                ->findOrFail($id);
            $formattedData = [
                'id' => $showReturnItem->id,
                'order_id' => $showReturnItem->order_id,
                'user_id' => $showReturnItem->user_id,
                'reason' => $showReturnItem->reason,
                'status' => $showReturnItem->status,
                'total_refund_amount' => $showReturnItem->total_refund_amount,
                'created_at' => $showReturnItem->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $showReturnItem->updated_at->format('Y-m-d H:i:s'),
                'items' => $showReturnItem->items->map(function ($item) use ($showReturnItem) {
                    return [
                        'id' => $item->id,
                        'request_id' => $item->return_request_id,
                        'order_detail_id' => $item->order_detail_id,
                        'image' => $item->image,
                        'quantity' => $item->quantity,
                        'status' => $item->status,
                        'refund_amount' => $item->refund_amount,
                        'order' => [
                            'id' => $showReturnItem->order->id,
                            'total' => $showReturnItem->order->total,
                            'total_quantity' => $showReturnItem->order->total_quantity,
                            'order_status' => $showReturnItem->order->order_status,
                            'order_code' => $showReturnItem->order->order_code,
                            'payment_status' => $showReturnItem->order->payment_status,
                            'order_detail' => [
                                "id" => $item->orderDetail->id,
                                "product_id" => $item->orderDetail->product_id,
                                "product_variant_id" => $item->orderDetail->product_variant_id,
                                "order_id" => $item->orderDetail->order_id,
                                "product_name" => $item->orderDetail->product_name,
                                "product_img" => $item->orderDetail->product_img,
                                "attributes" => $item->orderDetail->attributes,
                                "quantity" => $item->orderDetail->quantity,
                                "price" => $item->orderDetail->price,
                                "total_price" => $item->orderDetail->total_price,
                                "discount" => $item->orderDetail->discount,
                                "created_at" => $item->orderDetail->created_at->format('Y-m-d H:i:s'),
                                "updated_at" => $item->orderDetail->updated_at->format('Y-m-d H:i:s'),
                            ]
                        ],
                    ];
                }),
            ];

            return response()->json([
                'message' => 'Lấy dữ liệu thành công',
                'data' => $formattedData,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                "message" => $ex->getMessage()
            ]);
        }
    }

    // public function updateReturnItemStatus(Request $request, $returnItemId)
    // {
    //     try {
    //         DB::transaction(function () use ($request, $returnItemId) {
    //             $user = auth()->user();
    //             // Validate input
    //             $validated = $request->validate([
    //                 'status' => 'required|in:pending,canceled,approved,rejected',
    //                 'reason' => 'nullable|string', // Lý do từ chối chỉ cần khi trạng thái là "rejected" và khi admin thao tác
    //             ]);

    //             // Tìm return_item cần xử lý
    //             $returnItem = ReturnItem::findOrFail($returnItemId);

    //             // Kiểm tra tính hợp lệ của việc chuyển trạng thái
    //             if ($returnItem->status === 'pending' && !in_array($validated['status'], ['canceled', 'approved', 'rejected'])) {
    //                 throw new \Exception('Trạng thái không hợp lệ để chuyển đổi từ pending.');
    //             }

    //             if (in_array($returnItem->status, ['approved', 'rejected', 'canceled']) && $validated['status'] === 'pending') {
    //                 throw new \Exception('Không thể quay lại trạng thái "pending" từ trạng thái hiện tại.');
    //             }

    //             if ($returnItem->status === 'canceled' && in_array($validated['status'], ['approved', 'rejected'])) {
    //                 throw new \Exception('Không thể chuyển trạng thái từ "canceled" sang "approved" hoặc "rejected".');
    //             }

    //             if (($returnItem->status === 'approved' || $returnItem->status === 'rejected') && $validated['status'] === 'canceled') {
    //                 throw new \Exception('Không thể chuyển trạng thái từ "approved" hoặc "rejected" sang "canceled".');
    //             }

    //             if (in_array($returnItem->status, ['approved', 'rejected']) && $validated['status'] !== $returnItem->status) {
    //                 throw new \Exception('Trạng thái "approved" và "rejected" không thể chuyển đổi qua lại.');
    //             }

    //             // Lưu lịch sử vào return_log
    //             $logComment = "Updated status to {$validated['status']}";
    //             if ($validated['status'] === 'rejected' && $validated['reason']) {
    //                 // Chỉ ghi lý do từ chối khi trạng thái là "rejected" và khi admin thao tác
    //                 $logComment .= ". Reason: {$validated['reason']}";
    //             }

    //             ReturnLog::create([
    //                 'return_request_id' => $returnItem->return_request_id,
    //                 'user_id' => $user->id,
    //                 'action' => $validated['status'],
    //                 'comment' => $logComment,
    //             ]);

    //             // Cập nhật trạng thái cho return_item
    //             $returnItem->update([
    //                 'status' => $validated['status'],
    //             ]);

    //             // Kiểm tra xem sản phẩm có phải là biến thể hay không
    //             if ($validated['status'] === 'approved') {
    //                 $orderDetail = OrderDetail::findOrFail($returnItem->order_detail_id);
    //                 $product = Product::findOrFail($orderDetail->product_id);

    //                 // Kiểm tra sản phẩm có biến thể không
    //                 if ($orderDetail->product_variant_id) {
    //                     // Nếu là biến thể sản phẩm, cộng số lượng vào bảng product_variants
    //                     $productVariant = ProductVariant::findOrFail($orderDetail->product_variant_id);
    //                     $productVariant->update([
    //                         'quantity' => $productVariant->quantity + $returnItem->quantity,
    //                     ]);
    //                 } else {
    //                     // Nếu là sản phẩm đơn, cộng số lượng vào bảng products
    //                     $product->update([
    //                         'quantity' => $product->quantity + $returnItem->quantity,
    //                     ]);
    //                 }
    //             }

    //             // Tìm return_request liên quan
    //             $returnRequest = ReturnRequest::findOrFail($returnItem->return_request_id);

    //             // Kiểm tra trạng thái của tất cả return_item
    //             $allItemsProcessed = $returnRequest->items()->where('status', 'pending')->count() === 0;
    //             $allItemsRejected = $returnRequest->items()->where('status', 'approved')->count() === 0;

    //             if ($allItemsProcessed) {
    //                 if ($allItemsRejected) {
    //                     // Nếu tất cả đều bị từ chối
    //                     $returnRequest->update([
    //                         'status' => 'rejected',
    //                     ]);

    //                     $this->updateOrder($returnRequest->id);
    //                 } else {
    //                     // Nếu có ít nhất một item được chấp nhận
    //                     $returnRequest->update([
    //                         'status' => 'completed', // hoặc trạng thái phù hợp
    //                     ]);
    //                     $this->updateOrder($returnRequest->id);
    //                 }
    //             }
    //         });

    //         return response()->json([
    //             'message' => 'Return item status updated successfully.',
    //         ]);
    //     } catch (\Exception $ex) {
    //         return response()->json([
    //             'message' => $ex->getMessage(),
    //         ], 500);
    //     }
    // }

    public function updateReturnItemStatus(Request $request, $returnItemId)
    {
        try {
            DB::transaction(function () use ($request, $returnItemId) {
                $user = auth()->user();

                // Validate input
                $validated = $request->validate([
                    'status' => 'required|in:pending,canceled,approved,rejected',
                    'reason' => 'nullable|string', // Lý do từ chối chỉ cần khi trạng thái là "rejected" và khi admin thao tác
                ]);

                // Tìm return_item cần xử lý
                $returnItem = ReturnItem::findOrFail($returnItemId);

                // Kiểm tra tính hợp lệ của việc chuyển trạng thái
                if ($returnItem->status === 'pending' && !in_array($validated['status'], ['canceled', 'approved', 'rejected'])) {
                    throw new \Exception('Trạng thái không hợp lệ để chuyển đổi từ pending.');
                }

                if (in_array($returnItem->status, ['approved', 'rejected', 'canceled']) && $validated['status'] === 'pending') {
                    throw new \Exception('Không thể quay lại trạng thái "pending" từ trạng thái hiện tại.');
                }

                if ($returnItem->status === 'canceled' && in_array($validated['status'], ['approved', 'rejected'])) {
                    throw new \Exception('Không thể chuyển trạng thái từ "canceled" sang "approved" hoặc "rejected".');
                }

                if (($returnItem->status === 'approved' || $returnItem->status === 'rejected') && $validated['status'] === 'canceled') {
                    throw new \Exception('Không thể chuyển trạng thái từ "approved" hoặc "rejected" sang "canceled".');
                }

                if (in_array($returnItem->status, ['approved', 'rejected']) && $validated['status'] !== $returnItem->status) {
                    throw new \Exception('Trạng thái "approved" và "rejected" không thể chuyển đổi qua lại.');
                }

                // Lưu lịch sử vào return_log
                $logComment = "Quản trị viên tên :{$user->name} đã cập nhật trạng thái thành {$validated['status']}";
                if ($validated['status'] === 'rejected' && $validated['reason']) {
                    // Chỉ ghi lý do từ chối khi trạng thái là "rejected" và khi admin thao tác
                    $logComment .= ". Lí do: {$validated['reason']}";
                }

                ReturnLog::create([
                    'return_request_id' => $returnItem->return_request_id,
                    'user_id' => $user->id,
                    'action' => $validated['status'],
                    'comment' => $logComment,
                ]);

                // Cập nhật trạng thái cho return_item
                $returnItem->update([
                    'status' => $validated['status'],
                ]);

                // Kiểm tra xem sản phẩm có phải là biến thể hay không
                if ($validated['status'] === 'approved') {
                    $orderDetail = OrderDetail::findOrFail($returnItem->order_detail_id);
                    $product = Product::findOrFail($orderDetail->product_id);

                    // Tính toán refund_amount
                    $refundAmount = $orderDetail->price * $returnItem->quantity;

                    // Cập nhật refund_amount cho return_item
                    $returnItem->update([
                        'refund_amount' => $refundAmount,
                    ]);

                    // Kiểm tra sản phẩm có biến thể không
                    if ($orderDetail->product_variant_id) {
                        // Nếu là biến thể sản phẩm, cộng số lượng vào bảng product_variants
                        $productVariant = ProductVariant::findOrFail($orderDetail->product_variant_id);
                        $productVariant->update([
                            'quantity' => $productVariant->quantity + $returnItem->quantity,
                        ]);
                    } else {
                        // Nếu là sản phẩm đơn, cộng số lượng vào bảng products
                        $product->update([
                            'quantity' => $product->quantity + $returnItem->quantity,
                        ]);
                    }
                }

                // Tìm return_request liên quan
                $returnRequest = ReturnRequest::findOrFail($returnItem->return_request_id);

                // Tính toán tổng refund_amount
                $totalRefundAmount = $returnRequest->items()
                    ->where('status', 'approved')
                    ->sum('refund_amount');

                // Cập nhật total_refund_amount
                $returnRequest->update([
                    'total_refund_amount' => $totalRefundAmount,
                ]);

                // Kiểm tra trạng thái của tất cả return_item
                $allItemsProcessed = $returnRequest->items()->where('status', 'pending')->count() === 0;
                $allItemsRejected = $returnRequest->items()->where('status', 'approved')->count() === 0;

                if ($allItemsProcessed) {
                    if ($allItemsRejected) {
                        // Nếu tất cả đều bị từ chối
                        $returnRequest->update([
                            'status' => 'rejected',
                        ]);

                        $this->updateOrder($returnRequest->id);
                    } else {
                        // Nếu có ít nhất một item được chấp nhận
                        $returnRequest->update([
                            'status' => 'completed', // hoặc trạng thái phù hợp
                        ]);
                        $this->updateOrder($returnRequest->id);
                    }
                    // $this->updateOrder($returnRequest->id);
                }
                event(new ReturnItemStatusUpdated([
                    'returnItemId' => $returnItemId,
                    'status' => $validated['status'],
                    'refund_amount' => $returnItem->refund_amount,
                    // 'message' => "Return item status updated to {$validated['status']}."
                ]));
                // broadcast(new ReturnRequestStatusUpdate($returnRequest))->toOthers();
            });

            return response()->json([
                'message' => 'Return item status updated successfully.',
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    public function updateOrder($returnRequestId)
    {
        try {
            return DB::transaction(function () use ($returnRequestId) {

                // Tìm return_request và load các return_items
                $returnRequest = ReturnRequest::query()->findOrFail($returnRequestId)->load(["items"]);
                $order = Order::findOrFail($returnRequest->order_id);

                // Lấy danh sách return_items
                $returnItems = $returnRequest->items;

                // Kiểm tra trạng thái của tất cả return_items
                $allApproved = $returnItems->every(fn($item) => $item->status === 'approved');
                $allRejected = $returnItems->every(fn($item) => $item->status === 'rejected');

                if ($allApproved) {
                    // Nếu tất cả các item được chấp nhận
                    $returnRequest->update(['status' => 'completed']);

                    $order->update([

                        'order_status' => Order::STATUS_RETURNED, // Đặt trạng thái là 'Hoàn trả hàng'
                    ]);

                    broadcast(new OrderStatusUpdated($order))->toOthers();

                    return [
                        'status' => true,
                        'message' => 'Đơn hàng đã đổi sang trạng thái là hoàn trả hàng',
                    ];
                }

                if ($allRejected) {
                    // Nếu tất cả các item bị từ chối
                    $returnRequest->update(['status' => 'rejected']);

                    $order->update([
                        'order_status' => Order::STATUS_COMPLETED, // Đặt trạng thái là 'Hoàn thành'
                    ]);
                    broadcast(new OrderStatusUpdated($order))->toOthers();

                    return [
                        'status' => true,
                        'message' => 'Đơn hàng đã đổi sang trạng thái hoàn thành',
                    ];
                }

                // Nếu có một số item được chấp nhận, một số bị từ chối
                $returnRequest->update(['status' => 'completed']);
                $order->update([
                    'order_status' => Order::STATUS_RETURNED, // Đặt trạng thái là 'Hoàn trả hàng'
                ]);

                broadcast(new OrderStatusUpdated($order))->toOthers();

                return [
                    'status' => true,
                    'message' => 'Đơn hàng đã đổi sang trạng thái là hoàn trả hàng',
                ];
            });
        } catch (\Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage(),
            ], 500);
        }
    }
}
