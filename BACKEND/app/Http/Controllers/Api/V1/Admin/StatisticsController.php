<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    // bộ lọc
    public function applyDateFilter($query, Request $request, $column)
    {
        // dd($request->all());
        // Lấy filter_type và filter_value từ request
        $filterType = $request->input('filter_type', 'day'); // Mặc định là lọc theo ngày
        $filterValue = $request->input('filter_value', now()->format('Y-m-d')); // Giá trị lọc

        // Các loại filter hợp lệ
        $validFilterTypes = ['day', 'week', 'month', 'year', 'range'];
        if (!in_array($filterType, $validFilterTypes)) {
            throw new \Exception("Filter type không hợp lệ.");
        }

        // Kiểm tra filter_value có tồn tại (ngoại trừ trường hợp range)
        if ($filterType !== 'range' && !$filterValue) {
            throw new \Exception("Thiếu giá trị 'filter_value' cho bộ lọc.");
        }

        switch ($filterType) {
            case 'day':
                // Kiểm tra định dạng Y-m-d
                if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $filterValue)) {
                    throw new \Exception("Giá trị 'filter_value' không hợp lệ cho bộ lọc ngày. Cần định dạng Y-m-d.");
                }
                $query->whereDate($column, $filterValue);
                break;

            case 'week':
                try {
                    // Kiểm tra định dạng Y-m-d
                    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $filterValue)) {
                        throw new \Exception("Giá trị 'filter_value' không hợp lệ cho bộ lọc tuần. Cần định dạng Y-m-d.");
                    }

                    $endOfWeek = Carbon::parse($filterValue)->endOfDay(); // Ngày kết thúc
                    $startOfWeek = $endOfWeek->copy()->subDays(6)->startOfDay(); // 7 ngày trước

                    $query->whereBetween($column, [$startOfWeek, $endOfWeek]);
                } catch (\Exception $e) {
                    throw new \Exception("Giá trị 'filter_value' không hợp lệ cho bộ lọc tuần.");
                }
                break;

            case 'month':
                try {
                    // Chỉ chấp nhận định dạng Y-m
                    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $filterValue)) {
                        throw new \Exception("Giá trị 'filter_value' không hợp lệ cho bộ lọc tháng. Cần định dạng Y-m.");
                    }

                    $date = Carbon::parse($filterValue);
                    $query->whereMonth($column, $date->month)
                        ->whereYear($column, $date->year);
                } catch (\Exception $e) {
                    throw new \Exception("Giá trị 'filter_value' không hợp lệ cho bộ lọc tháng.");
                }
                break;

            case 'year':
                try {
                    // Kiểm tra filter_value là số năm hợp lệ
                    if (!preg_match('/^\d{4}$/', $filterValue)) {
                        throw new \Exception("Giá trị 'filter_value' không hợp lệ cho bộ lọc năm. Cần là số năm (ví dụ: 2024).");
                    }
                    $query->whereYear($column, $filterValue);
                } catch (\Exception $e) {
                    throw new \Exception("Giá trị 'filter_value' không hợp lệ cho bộ lọc năm.");
                }
                break;

            case 'range':
                try {
                    // Kiểm tra filter_start_date và filter_end_date
                    $request->validate([
                        'filter_start_date' => 'required|date',
                        'filter_end_date' => 'required|date|after_or_equal:filter_start_date',
                    ]);

                    $startDate = Carbon::parse($request->input('filter_start_date'))->startOfDay();
                    $endDate = Carbon::parse($request->input('filter_end_date'))->endOfDay();

                    $query->whereBetween($column, [$startDate, $endDate]);
                } catch (\Exception $e) {
                    throw new \Exception("Giá trị ngày bắt đầu và kết thúc không hợp lệ cho bộ lọc range.");
                }
                break;

            default:
                throw new \Exception("Filter type không hợp lệ.");
        }
    }

    // thống kê user
    public function getTotalUsers()
    {
        try {
            // Bắt đầu truy vấn với model User
            $totalUsers = User::query()->where('role_id',1)->count();

            // Áp dụng bộ lọc ngày
            // $this->applyDateFilter($query, $request, 'created_at');

            // Đếm tổng số người dùng
            // $totalUsers = $query->count();

            return  $totalUsers;
        } catch (\Exception $e) {
            // Xử lý lỗi nếu có
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // Thống kê doanh thu
    public function getRevenueStatistics(Request $request)
    {
        try {

            // Khởi tạo truy vấn cơ bản
            // $query = OrderDetail::select(
            //     'order_details.product_id',
            //     'order_details.product_variant_id',
            //     'products.name as product_name',
            //     'products.sku as product_sku',
            //     'product_variants.sku as variant_sku',
            //     DB::raw('SUM(order_details.total_price) as revenue'),
            //     DB::raw('SUM(order_details.quantity) as total_quantity')
            // )
            //     ->leftJoin('products', 'order_details.product_id', '=', 'products.id')
            //     ->leftJoin('product_variants', 'order_details.product_variant_id', '=', 'product_variants.id')
            //     ->groupBy(
            //         'order_details.product_id',
            //         'order_details.product_variant_id',
            //         'products.name',
            //         'products.sku',
            //         'product_variants.sku'
            //     );

            // // Áp dụng bộ lọc ngày tháng nếu có
            // $this->applyDateFilter($query, $request, 'order_details.created_at');

            // // Lấy dữ liệu tổng hợp từ truy vấn
            // $revenueData = $query->get();

            // // Tính tổng doanh thu
            // $totalRevenue = $revenueData->sum('revenue');

            $query = OrderDetail::select(
                'order_details.product_id',
                'order_details.product_variant_id',
                'products.name as product_name',
                'products.sku as product_sku',
                'product_variants.sku as variant_sku',
                DB::raw('SUM(order_details.total_price - IFNULL(return_items.total_refund, 0)) as revenue'), // Tính doanh thu sau hoàn trả
                DB::raw('SUM(order_details.quantity - IFNULL(return_items.total_return_quantity, 0)) as total_quantity') // Tính số lượng thực tế
            )
                ->leftJoin('products', 'order_details.product_id', '=', 'products.id')
                ->leftJoin('product_variants', 'order_details.product_variant_id', '=', 'product_variants.id')
                ->leftJoin(
                    DB::raw('(SELECT 
                                  order_detail_id, 
                                  SUM(quantity) as total_return_quantity, 
                                  SUM(refund_amount) as total_refund 
                              FROM return_items 
                              WHERE status = "approved" 
                              GROUP BY order_detail_id) as return_items'),
                    'order_details.id',
                    '=',
                    'return_items.order_detail_id'
                )
                ->join('orders', 'order_details.order_id', '=', 'orders.id') // Join bảng orders
                ->whereIn('orders.order_status', ['Hoàn thành', 'Hoàn trả hàng']) // Lọc theo điều kiện order_status từ bảng orders
                ->where('orders.payment_status', 'Đã thanh toán')
                ->groupBy(
                    'order_details.product_id',
                    'order_details.product_variant_id',
                    'products.name',
                    'products.sku',
                    'product_variants.sku'
                );

            // Áp dụng bộ lọc ngày tháng nếu có
            $this->applyDateFilter($query, $request, 'order_details.created_at');

            // Lấy dữ liệu tổng hợp từ truy vấn
            $revenueData = $query->get();

            // Tính tổng doanh thu
            $totalRevenue = $revenueData->sum('revenue');


            // Lấy thông tin thuộc tính sản phẩm
            $variantAttributes = DB::table('product_variant_has_attributes')
                ->join('attributes', 'product_variant_has_attributes.attribute_id', '=', 'attributes.id')
                ->join('attribute_items', 'product_variant_has_attributes.attribute_item_id', '=', 'attribute_items.id')
                ->select(
                    'product_variant_has_attributes.product_variant_id',
                    'attributes.name as attribute_name',
                    'attribute_items.value as attribute_value'
                )
                ->get();

            // Ánh xạ thuộc tính theo biến thể sản phẩm
            $attributeMap = [];
            foreach ($variantAttributes as $attr) {
                $attributeMap[$attr->product_variant_id][] = $attr->attribute_name . ': ' . $attr->attribute_value;
            }

            // Chuẩn hóa dữ liệu trả về
            $result = $revenueData->map(function ($item) use ($attributeMap) {
                return [
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'variant_sku' => $item->variant_sku,
                    'attributes' => isset($attributeMap[$item->product_variant_id])
                        ? implode(", ", $attributeMap[$item->product_variant_id])
                        : null,
                    'revenue' => (float)$item->revenue,
                    'total_quantity' => (int)$item->total_quantity,
                ];
            });

            // Trả về kết quả JSON bao gồm tổng doanh thu và dữ liệu chi tiết
            return response()->json([
                'status' => 'success',
                'total_revenue' => $totalRevenue,
                'totalUsers' => $this->getTotalUsers($request),
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            // Xử lý lỗi và trả về phản hồi
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getOrderStatistics(Request $request)
    {
        try {
            // Tạo query cơ bản cho Order
            $ordersQuery = Order::query();

            // Áp dụng bộ lọc thời gian
            $this->applyDateFilter($ordersQuery, $request, 'created_at');

            // Clone query ban đầu để tránh bị ảnh hưởng bởi groupBy
            $orderIdsQuery = clone $ordersQuery;

            // Thống kê số lượng đơn hàng theo trạng thái
            $orderCountsByStatus = $ordersQuery
                ->select('order_status', DB::raw('count(*) as total_orders'))
                ->groupBy('order_status')
                ->get();

            // Lọc số lượng sản phẩm trong các đơn hàng dựa trên bộ lọc thời gian
            $orderIds = $orderIdsQuery->pluck('id'); // Tách riêng query để lấy ID
            // $totalQuantityInOrder = OrderDetail::whereIn('order_id', $orderIds)->sum('quantity');

            // Tính tổng số lượng thực tế trong các đơn hàng
            $totalQuantityInOrder = OrderDetail::whereIn('order_id', $orderIds)
                ->leftJoin(
                    DB::raw('(SELECT 
                  order_detail_id, 
                  SUM(quantity) as total_return_quantity 
              FROM return_items 
              WHERE status = "approved" 
              GROUP BY order_detail_id) as return_items'),
                    'order_details.id',
                    '=',
                    'return_items.order_detail_id'
                )
                ->selectRaw('SUM(order_details.quantity - IFNULL(return_items.total_return_quantity, 0)) as total_quantity')
                ->value('total_quantity');

            // Tạo kết quả trả về
            // dd($orderCountsByStatus->toArray());
            $result = [
                'order_counts_by_status' => $orderCountsByStatus,
                'total_quantity_in_order' => $totalQuantityInOrder,
            ];

            return response()->json($result);
        } catch (\Exception $ex) {
            return response()->json([
                "message" => $ex->getMessage()
            ], 500);
        }
    }

    public function getProductStatistics(Request $request)
    {
        try {
            // Lấy dữ liệu từ request
            $typeFilter = $request->input('type', []); // [0, 1]: 0 - simple, 1 - variant
            $statusFilter = $request->input('status', []); // [1, 2, 3]: 1 - tồn kho, 2 - sắp hết hàng, 3 - bán chạy

            // Ngưỡng để xác định trạng thái sản phẩm
            $bestSellingThreshold = 50; // Sản phẩm bán chạy nếu tổng số bán >= 50
            $lowStockThreshold = 10;   // Sắp hết hàng nếu số lượng còn lại < 10

            $results = [];

            // Kiểm tra nếu typeFilter trống, mặc định lấy cả 2 loại
            if (empty($typeFilter)) {
                $typeFilter = [0, 1];
            }

            // Lọc sản phẩm đơn giản nếu typeFilter chứa 0
            if (in_array(0, $typeFilter)) {
                $simpleProducts = DB::table('products')
                    ->where('type', 0) // Sản phẩm đơn giản
                    ->leftJoin('order_details', 'products.id', '=', 'order_details.product_id')
                    ->select(
                        'products.id',
                        'products.name',
                        'products.sku',
                        DB::raw('IFNULL(SUM(order_details.quantity), 0) as total_sold'),
                        DB::raw('(products.quantity) as remaining_quantity')
                    )
                    ->groupBy(
                        'products.id',
                        'products.name',
                        'products.sku',
                        'products.quantity'
                    )
                    ->get()
                    ->map(function ($product) use ($bestSellingThreshold, $lowStockThreshold, $statusFilter) {
                        // Tính toán trạng thái
                        $statuses = [];
                        $product->total_sold = (int) $product->total_sold;
                        $product->remaining_quantity = (int) $product->remaining_quantity;

                        if ($product->total_sold >= $bestSellingThreshold) {
                            $statuses[] = 'Bán chạy';
                        }
                        if ($product->remaining_quantity < $lowStockThreshold) {
                            $statuses[] = 'Sắp hết hàng';
                        }
                        if (empty($statuses)) {
                            $statuses[] = 'Tồn kho';
                        }

                        // Lọc theo statusFilter nếu có
                        $statusIds = $this->getStatusIds($statuses);
                        if (empty($statusFilter) || !empty(array_intersect($statusFilter, $statusIds))) {
                            $product->status = implode('|', $statuses);
                            return $product;
                        }
                        return null;
                    })
                    ->filter(); // Loại bỏ các sản phẩm không khớp filter

                $results['simple_products'] = $simpleProducts;
            }
            // Lọc sản phẩm có biến thể nếu typeFilter chứa 1
            if (in_array(1, $typeFilter)) {
                $variantProducts = DB::table('products')
                    ->where('products.type', 1) // Sản phẩm có biến thể
                    ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                    ->leftJoin('order_details', 'product_variants.id', '=', 'order_details.product_variant_id')
                    ->select(
                        'products.id as product_id',
                        'products.name as product_name',
                        'product_variants.id as variant_id',
                        'product_variants.sku as variant_sku',
                        'product_variants.quantity as remaining_quantity',
                        DB::raw('IFNULL(SUM(order_details.quantity), 0) as total_sold')
                    )
                    ->groupBy(
                        'products.id',
                        'product_variants.id',
                        'products.name',
                        'product_variants.sku',
                        'product_variants.quantity'
                    )
                    ->get()
                    ->map(function ($variant) use ($bestSellingThreshold, $lowStockThreshold, $statusFilter) {
                        // Lấy các thuộc tính của biến thể
                        $attributes = DB::table('product_variant_has_attributes')
                            ->join('attributes', 'product_variant_has_attributes.attribute_id', '=', 'attributes.id')
                            ->join('attribute_items', 'product_variant_has_attributes.attribute_item_id', '=', 'attribute_items.id')
                            ->where('product_variant_has_attributes.product_variant_id', $variant->variant_id)
                            ->select('attributes.name as attribute_name', 'attribute_items.value as attribute_value')
                            ->get();

                        $variant->attributes = $attributes;

                        // Tính toán trạng thái
                        $statuses = [];
                        $variant->total_sold = (int) $variant->total_sold;
                        $variant->remaining_quantity = (int) $variant->remaining_quantity;

                        if ($variant->total_sold >= $bestSellingThreshold) {
                            $statuses[] = 'Bán chạy';
                        }
                        if ($variant->remaining_quantity < $lowStockThreshold) {
                            $statuses[] = 'Sắp hết hàng';
                        }
                        if (empty($statuses)) {
                            $statuses[] = 'Tồn kho';
                        }

                        // Lọc theo statusFilter nếu có
                        $statusIds = $this->getStatusIds($statuses);
                        if (empty($statusFilter) || !empty(array_intersect($statusFilter, $statusIds))) {
                            $variant->status = implode('|', $statuses);

                            return $variant;
                        }
                        return null;
                    })
                    ->filter() // Loại bỏ các sản phẩm không khớp filter
                    ->values() // Đặt lại chỉ số key để đảm bảo trả về là mảng
                    ->toArray(); // Chuyển đổi Collection thành mảng
                $results['variant_products'] = $variantProducts;
                // dd($results['variant_products']);

            }

            // Trả về kết quả
            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getStatusIds($statuses)
    {
        $statusMap = [
            'Tồn kho' => 1,
            'Sắp hết hàng' => 2,
            'Bán chạy' => 3,
        ];

        return array_map(function ($status) use ($statusMap) {
            return $statusMap[$status] ?? null;
        }, $statuses);
    }
}
