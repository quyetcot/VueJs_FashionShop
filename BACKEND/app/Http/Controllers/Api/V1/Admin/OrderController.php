<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Api\V1\Service\OrderGHNController;
use App\Models\Order;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        try {

            Order::where('order_status', 'Giao hàng thành công')
                ->whereDate('updated_at', '<', now()->subDays(3))
                ->update(['order_status' => 'Hoàn thành']);
            $orders = Order::with('orderDetails')
                ->latest()
                ->get();

            // Trả lại dữ liệu cho frontend
            return response()->json([
                'message' => 'Thông tin đơn hàng',
                'data' => $orders,  // Dữ liệu đơn hàng và chi tiết
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        Log::info('Fetching order with ID: ' . $id);
        try {
            // Lấy thông tin đơn hàng từ bảng Order
            $order = Order::findOrFail($id);
            $orderDetails = $order->orderDetails()->get();
            // Kết hợp thông tin đơn hàng với các chi tiết
            $orderData = [
                'order' => $order,
                'order_details' => $orderDetails,
            ];

            return response()->json($orderData, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Order not found: ' . $e->getMessage());
            return response()->json(['message' => 'Order not found'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // $test=new OrderGHNController();
            // dd($test->createOrder($id));

            $order = Order::findOrFail($id);
            broadcast(new OrderStatusUpdated($order))->toOthers();

            $currentStatus = $order->order_status;
            $newStatus = $request->input('order_status');

            $statusMap = [
                'Đang chờ xác nhận' => 1,
                'Đã xác nhận' => 2,
                'Đã hủy' => 3,
                'Đang vận chuyển' => 4,
                'Giao hàng thành công' => 5,
                'Yêu cầu hoàn trả hàng' => 6,
                'Hoàn trả hàng' => 7,
                'Hoàn thành' => 8,
                'Đã nhận hàng' => 9,

            ];


            if (is_numeric($newStatus) && in_array((int)$newStatus, $statusMap)) {
                $newStatus = array_search((int)$newStatus, $statusMap);
            }

            // Kiểm tra trạng thái đơn hàng
            if (!array_key_exists($newStatus, $statusMap)) {
                return response()->json(['message' => 'Trạng thái không hợp lệ.'], Response::HTTP_BAD_REQUEST);
            }

            if ($currentStatus === 'Đã hủy' || $currentStatus === 'Hoàn thành' || $currentStatus === 'Hoàn trả hàng' || $currentStatus === 'Yêu cầu hoàn trả hàng') {
                return response()->json(['message' => "Không thể thay đổi trạng thái \"$currentStatus\"."], Response::HTTP_BAD_REQUEST);
            }


            if ($currentStatus === 'Đang chờ xác nhận' && !in_array($newStatus, ['Đã xác nhận', 'Đã hủy'])) {
                return response()->json(['message' => 'Trạng thái tiếp theo chỉ có thể là "Đã xác nhận" hoặc "Đã hủy".'], Response::HTTP_BAD_REQUEST);
            }
            if ($currentStatus === 'Đang chờ xác nhận' && $newStatus === 'Đã xác nhận') {
                // Kiểm tra nếu phương thức thanh toán là 2 và payment_status là 'Chưa thanh toán'
                if ($order->payment_method_id == 2 && $order->payment_status == 'Chưa Thanh Toán') {
                    // Cập nhật payment_method_id thành 1
                    $order->payment_method_id = 1;
                
                }
            }
            
            if ($currentStatus === 'Đã xác nhận' && !in_array($newStatus, ['Đang vận chuyển', 'Đã hủy'])) {

                return response()->json(['message' => 'Trạng thái tiếp theo chỉ có thể là "Đang vận chuyển" hoặc "Đã hủy".'], Response::HTTP_BAD_REQUEST);
            }

            if ($currentStatus === 'Đang vận chuyển' && !in_array($newStatus, ['Giao hàng thành công'])) {
                return response()->json(['message' => 'Khi đang vận chuyển, chỉ có thể cập nhật thành "Giao hàng thành công".'], Response::HTTP_BAD_REQUEST);
            }
            if ($currentStatus === 'Giao hàng thành công' && !in_array($newStatus, ['Đã nhận hàng', 'Hoàn thành'])) {
                return response()->json(['message' => 'Sau "Giao hàng thành công", chỉ có thể chuyển sang "Hoàn thành".'], Response::HTTP_BAD_REQUEST);
            }

            // if ($currentStatus === 'Hoàn trả hàng' && $newStatus !== 'Hoàn thành') {
            //     return response()->json(['message' => 'Từ "Hoàn trả hàng", chỉ có thể chuyển sang "Hoàn thành".'], Response::HTTP_BAD_REQUEST);
            // }
            if ($newStatus === 'Đã nhận hàng') {
                $newStatus = 'Hoàn thành';
            }



            // Nếu có trạng thái mới từ request, thực hiện thay đổi trạng thái
            if ($newStatus && $newStatus !== $order->order_status) {
                $order->order_status = $newStatus;
            }
            if ($newStatus === 'Đã hủy') {
                // Gọi logic hoàn lại sản phẩm về kho
                $this->handleOrderCancellation($order, null); // Không cần lý do hủy
            }
            if ($newStatus === 'Giao hàng thành công') {
                $order->payment_status = 'Đã thanh toán';
            }
            $order->save();
            if ($newStatus === 'Đang vận chuyển') {
                $createOrder = new OrderGHNController();
                $createResponse = $createOrder->createOrder($id);  // Gọi hàm tạo đơn hàng giao hàng nhanh với ID đơn hàng
                // return response()->json([
                //     // 'message' => 'Trạng thái đơn hàng được cập nhật thành công. Đơn hàng GHN đã được tạo.',
                //     'message' =>  $createResponse  // Trả về kết quả từ GHN
                // ], Response::HTTP_OK);
                return $createResponse;
            }

            return response()->json(['message' => 'Cập nhật trạng thái đơn hàng thành công.', 'order' => $order], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    protected function handleOrderCancellation(Order $order, ?string $user_note = null)
    {
        // Lưu lý do hủy vào ghi chú, nếu không có thì đặt giá trị mặc định
        $order->return_notes = $user_note ?? 'Không có lý do được cung cấp';
        
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
    
        // Lưu lại trạng thái mới của đơn hàng
        $order->save();
    }
    
    public function searchOrders(Request $request)
    {
        // Danh sách trạng thái hợp lệ
        $validStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_SHIPPING,
            Order::STATUS_SUCCESS,
            Order::STATUS_CANCELED,
            Order::STATUS_RETURNED,
            Order::STATUS_COMPLETED,
        ];

        $request->validate([
            'search' => 'nullable|string|max:255',
            'statuses' => 'nullable|array',
            'statuses.*' => ['string', function ($attribute, $value, $fail) use ($validStatuses) {
                if (!in_array($value, $validStatuses)) {
                    $fail("Giá trị $value của $attribute không hợp lệ.");
                }
            }],
            'filter_type' => 'nullable|string|in:day,week,month,year,range',
            'filter_value' => 'nullable|string',
            'filter_start_date' => 'required_if:filter_type,range|date',
            'filter_end_date' => 'required_if:filter_type,range|date|after_or_equal:filter_start_date',
        ]);

        // Lấy các tham số tìm kiếm
        $searchTerm = $request->input('search');
        $statuses = $request->input('statuses');

        // Tạo query cơ bản
        $orders = Order::query();

        // 1. Lọc theo từ khóa tìm kiếm
        if ($searchTerm) {
            $orders->where(function ($query) use ($searchTerm) {
                $query->where('user_name', 'LIKE', "$searchTerm")
                    ->orWhere('user_email', 'LIKE', "$searchTerm");
            });
            // $orders->ddRawSql();
        }

        // 2. Lọc theo trạng thái đơn hàng
        if ($statuses && is_array($statuses)) {
            $orders->whereIn('order_status', $statuses);
        }

        // 3. Lọc theo ngày tháng
        $applyDateFilter = new StatisticsController();
        $applyDateFilter->applyDateFilter($orders, $request, 'created_at');


        return response()->json([
            'success' => true,
            'data' => $orders->with(["orderDetails"])->get(),
        ], Response::HTTP_OK);
    }
}
