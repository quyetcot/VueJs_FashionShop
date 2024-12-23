<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Events\OrderStatusUpdated;
use App\Events\ReturnItemStatusUpdated;
use App\Events\ReturnRequestStatusUpdate;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ReturnItem;
use App\Models\ReturnLog;
use App\Models\ReturnRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function PHPSTORM_META\map;

class ReturnController extends Controller
{

    public function getUserReturnRequests()
    {
        try {
            $user = auth()->user();

            // Lấy danh sách return_requests của người dùng hiện tại
            $returnRequests = ReturnRequest::with(["order.orderDetails", 'items'])
                ->where('user_id', $user->id)->latest('id')
                ->get()
                ->map(function ($returnRequest) {
                    // Tách thông tin order
                    $order = $returnRequest->order;
        
                    return [
                        'id' => $returnRequest->id,
                        'order_id' => $returnRequest->order_id,
                        'user_id' => $returnRequest->user_id,
                        'reason' => $returnRequest->reason,
                        'total_refund_amount' => $returnRequest->total_refund_amount,
                        'status' => $returnRequest->status,
                        'created_at' => $returnRequest->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $returnRequest->updated_at->format('Y-m-d H:i:s'),
        
                        // Map items mà không lặp lại order
                        'items' => $returnRequest->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'request_id' => $item->return_request_id,
                                'order_detail_id' => $item->order_detail_id,
                                'image' => $item->image,
                                'quantity' => $item->quantity,
                                'refund_amount' => $item->refund_amount,
                                'status' => $item->status,
                            ];
                        }),
        
                        // Gán order và orderDetails ngoài vòng lặp
                        'order' => [
                            'id' => $order->id,
                            'total' => $order->total,
                            'total_quantity' => $order->total_quantity,
                            'order_status' => $order->order_status,
                            'order_code' => $order->order_code,
                            'payment_status' => $order->payment_status,
                            'order_detail' => $order->orderDetails->map(function ($detail) {
                                return [
                                    "id" => $detail->id,
                                    "product_id" => $detail->product_id,
                                    "product_variant_id" => $detail->product_variant_id,
                                    "order_id" => $detail->order_id,
                                    "product_name" => $detail->product_name,
                                    "product_img" => $detail->product_img,
                                    "attributes" => $detail->attributes,
                                    "quantity" => $detail->quantity,
                                    "price" => $detail->price,
                                    "total_price" => $detail->total_price,
                                    "discount" => $detail->discount,
                                    "created_at" => $detail->created_at,
                                    "updated_at" => $detail->updated_at,
                                ];
                            })
                        ],
                    ];
                });

        return response()->json([
            'message' => 'User return requests retrieved successfully.',
            'data' => $returnRequests,
        ]);
    } catch (\Exception $ex) {
        return response()->json([
            'message' => 'Error retrieving return requests: ' . $ex->getMessage(),
        ], 500);
    }
}

    //
    public function createReturnRequest(Request $request)
    {

        // $respone = DB::transaction(function () use ($request) {
        //     // Validate dữ liệu từ client
        //     $validated = $request->validate([
        //         'order_id' => 'required|exists:orders,id',
        //         'items' => 'required|array',
        //         'items.*.order_detail_id' => 'required|exists:order_details,id',
        //         'items.*.quantity' => 'required|integer|min:1',
        //         'reason' => 'required|string',
        //     ]);

        //     // Tạo yêu cầu hoàn trả
        //     $returnRequest = ReturnRequest::create([
        //         'order_id' => $validated['order_id'],
        //         'user_id' => auth()->id(),
        //         'reason' => $validated['reason'],
        //         // 'status' => 'pending',
        //     ]);

        //     // Duyệt qua từng sản phẩm
        //     foreach ($validated['items'] as $item) {
        //         // Lấy thông tin chi tiết sản phẩm từ bảng order_details
        //         $orderDetail = OrderDetail::findOrFail($item['order_detail_id']);

        //         // Kiểm tra số lượng yêu cầu hoàn trả không vượt quá số lượng đã mua
        //         if ($item['quantity'] > $orderDetail->quantity) {
        //             throw new \Exception("Quantity to return for order_detail_id {$item['order_detail_id']} exceeds purchased quantity.");
        //         }

        //         // Tạo danh sách các item yêu cầu hoàn trả
        //         ReturnItem::create([
        //             'return_request_id' => $returnRequest->id,
        //             'order_detail_id' => $item['order_detail_id'],
        //             'quantity' => $item['quantity'],
        //             // 'status' => 'pending',
        //         ]);
        //     }
        //     Order::query()->findOrFail($validated["order_id"])->update([
        //         "order_status" => "Yêu cầu hoàn trả hàng"
        //     ]);

        //     return [
        //         'message' => 'Return request created successfully.',
        //         'return_request' => $returnRequest,
        //     ];
        // });


        try {
            $response = DB::transaction(function () use ($request) {
                // Validate dữ liệu từ client
                $validated = $request->validate([
                    'order_id' => 'required|exists:orders,id',
                    'items' => 'required|array',
                    'items.*.order_detail_id' => 'required|exists:order_details,id',
                    'items.*.quantity' => 'required|integer|min:1',
                    'reason' => 'required|string',
                ]);
                $order=Order::query()->findOrFail($request->input('order_id'));
                if (Carbon::parse($order->created_at)->addDays(3)->isPast()) {
                    return response()->json(['message' => 'Bạn chỉ có thể hoàn trả hàng sau 3 ngày kể từ ngày nhận hàng.'], 400);
                }

                // Tạo yêu cầu hoàn trả
                $returnRequest = ReturnRequest::create([
                    'order_id' => $validated['order_id'],
                    'user_id' => auth()->id(),
                    'reason' => $validated['reason'],
                    'total_refund_amount' => 0, // Sẽ được tính sau
                ]);


                $totalRefundAmount = 0;

                // Duyệt qua từng sản phẩm
                foreach ($validated['items'] as $item) {
                    // Lấy thông tin chi tiết sản phẩm từ bảng order_details
                    $orderDetail = OrderDetail::findOrFail($item['order_detail_id']);

                    // Kiểm tra số lượng yêu cầu hoàn trả không vượt quá số lượng đã mua
                    if ($item['quantity'] > $orderDetail->quantity) {
                        throw new \Exception("Quantity to return for order_detail_id {$item['order_detail_id']} exceeds purchased quantity.");
                    }

                    // Tính toán số tiền hoàn trả cho sản phẩm này
                    $refundForItem = $orderDetail->price * $item['quantity'];
                    $totalRefundAmount += $refundForItem;

                    // Tạo danh sách các item yêu cầu hoàn trả
                    ReturnItem::create([
                        'return_request_id' => $returnRequest->id,
                        'order_detail_id' => $item['order_detail_id'],
                        'quantity' => $item['quantity'],
                        'refund_amount' => $refundForItem,
                    ]);
                }

                // Cập nhật số tiền hoàn lại tổng cộng trong yêu cầu hoàn trả
                $returnRequest->update([
                    'total_refund_amount' => $totalRefundAmount,
                ]);

                // Cập nhật trạng thái đơn hàng
                $order=Order::query()->findOrFail($validated['order_id'])->update([
                    'order_status' => 'Yêu cầu hoàn trả hàng',
                ]);
                broadcast(new OrderStatusUpdated($order))->toOthers();


                return [
                    'message' => 'Return request created successfully.',
                    'return_request' => $returnRequest,
                ];
            });

            return response()->json($response, 201);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage(),
            ], 400);
        }
    }



    public function cancelReturnRequest(Request $request)
    {
        try {
            $user = auth()->user();

            // Validate dữ liệu
            $validated = $request->validate([
                'return_request_id' => 'required|exists:return_requests,id',
                'cancel_items' => 'nullable|array',
                'cancel_items.*' => 'exists:return_items,id',
            ]);

            // Lấy yêu cầu hoàn trả
            $returnRequest = ReturnRequest::findOrFail($validated['return_request_id']);

            // Kiểm tra quyền hủy
            if ($returnRequest->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }

            // Kiểm tra trạng thái yêu cầu hoàn trả
            if ($returnRequest->status !== 'pending') {
                return response()->json([
                    'message' => 'Chỉ những yêu cầu trả hàng đang chờ xử lý mới có thể hủy được.'
                ], 400);
            }

            // Lấy các mục cần hủy
            $itemsToCancel = !empty($validated['cancel_items'])
                ? ReturnItem::whereIn('id', $validated['cancel_items'])
                ->where('return_request_id', $returnRequest->id)
                ->where('status', 'pending') // Chỉ cho phép hủy các mục còn đang pending
                ->get()
                : ReturnItem::where('return_request_id', $returnRequest->id)
                ->where('status', 'pending')
                ->get();

            if ($itemsToCancel->isEmpty()) {
                return response()->json([
                    'message' => 'No items to cancel or items are not in pending status.',
                ], 400);
            }

            // Cập nhật trạng thái từng mục
            $canceledItems = [];
            foreach ($itemsToCancel as $item) {
                $item->update(['status' => 'canceled']);
                $canceledItems[] = $item->id;

                // Ghi log
                ReturnLog::create([
                    'return_request_id' => $returnRequest->id,
                    'user_id' => $user->id,
                    'action' => 'canceled',
                    'comment' => "Khách hàng tên: {$user->name} hủy item ID: {$item->id}",
                ]);
            }

            // Kiểm tra trạng thái toàn bộ yêu cầu
            $remainingItems = ReturnItem::where('return_request_id', $returnRequest->id)
                ->where('status', 'pending')
                ->count();

            if ($remainingItems === 0) {
                // Nếu không còn mục nào ở trạng thái pending, hủy toàn bộ yêu cầu
                $returnRequest->update(['status' => 'canceled']);
                ReturnLog::create([
                    'return_request_id' => $returnRequest->id,
                    'user_id' => $user->id,
                    'action' => 'canceled',
                    'comment' => "Khách hàng tên: {$user->name} đã hủy toàn bộ yêu cầu.",
                ]);
                $order=Order::query()->findOrFail($returnRequest->order_id)->update([
                    'order_status' => "Hoàn thành"
                ]);
                broadcast(new OrderStatusUpdated($order))->toOthers();

                broadcast(new ReturnRequestStatusUpdate($returnRequest))->toOthers();
                

            }
           

            return response()->json([
                'message' => 'Cancellation successful.',
                'canceled_items' => $canceledItems,
                'remaining_items' => $remainingItems,
                'request_status' => $remainingItems === 0 ? 'canceled' : 'partially_canceled',
            ], 200);
            
        } catch (\Exception $ex) {
            return response()->json([
                "message" => "Error: " . $ex->getMessage()
            ], 500);
        }
    }

    public function getUserReturnItem($id)
    {
        try {

            $returnRequests = ReturnRequest::query()
                ->with(['order', 'order.orderDetails', 'items']) // Load quan hệ liên quan
                ->findOrFail($id);

            // Chuyển đổi dữ liệu
            $result = [
                'id' => $returnRequests->id,
                'order_id' => $returnRequests->order_id,
                'user_id' => $returnRequests->user_id,
                'reason' => $returnRequests->reason,
                'status' => $returnRequests->status,

                'total_refund_amount' => $returnRequests->total_refund_amount,
                'created_at' => $returnRequests->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $returnRequests->updated_at->format('Y-m-d H:i:s'),
                'items' => $returnRequests->items->map(function ($item) use ($returnRequests) {
                    // Lấy thông tin chi tiết đơn hàng tương ứng với order_detail_id của item
                    $orderDetail = $returnRequests->order->orderDetails->firstWhere('id', $item->order_detail_id);

                    return [
                        'id' => $item->id,
                        'request_id' => $item->return_request_id,
                        'order_detail_id' => $item->order_detail_id,
                        'image' => $item->image,
                        'quantity' => $item->quantity,
                        'refund_amount' => $item->refund_amount,
                        'status' => $item->status,
                        'order' => [
                            'id' => $returnRequests->order->id,
                            'total' => $returnRequests->order->total,
                            'total_quantity' => $returnRequests->order->total_quantity,
                            'order_status' => $returnRequests->order->order_status,
                            'order_code' => $returnRequests->order->order_code,
                            'payment_status' => $returnRequests->order->payment_status,
                            'order_detail' => $orderDetail ? [
                                "id" => $orderDetail->id,
                                "product_id" => $orderDetail->product_id,
                                "product_variant_id" => $orderDetail->product_variant_id,
                                "order_id" => $orderDetail->order_id,
                                "product_name" => $orderDetail->product_name,
                                "product_img" => $orderDetail->product_img,
                                "attributes" => $orderDetail->attributes,
                                "quantity" => $orderDetail->quantity,
                                "price" => $orderDetail->price,
                                "total_price" => $orderDetail->total_price,
                                "discount" => $orderDetail->discount,
                                "created_at" => $orderDetail->created_at->format('Y-m-d H:i:s'),
                                "updated_at" => $orderDetail->updated_at->format('Y-m-d H:i:s'),
                            ] : null, // Nếu không tìm thấy chi tiết đơn hàng, trả về null
                        ],
                    ];
                }),
            ];


            return response()->json([
                'message' => 'Lấy dữ liệu thành công',
                'data' => $result,
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Error retrieving return requests: ' . $ex->getMessage(),
            ], 500);
        }
    }
}
