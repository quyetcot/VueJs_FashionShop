<?php

namespace App\Http\Controllers\Api\V1\Service;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderPdfController extends Controller
{
    //
    public function exportOrdersPDF(Request $request)
    {
        // Lấy danh sách order_ids từ request (hoặc lấy tất cả nếu không có)
        $orderIds = $request->input('order_ids', null);

        // Lấy danh sách đơn hàng và chi tiết đơn hàng
        $orders = Order::with(['orderDetails'])
            ->when($orderIds, function ($query) use ($orderIds) {
                $query->whereIn('id', $orderIds); // Lọc theo danh sách order_ids
            })
            ->get();
            // dd($orders->toArray());
        if ($orders->isEmpty()) {
            return response()->json(['error' => 'No orders found'], 404);
        }

        // Chuẩn bị dữ liệu cho view
        $data = [
            'orders' => $orders,
        ];

        // Render file PDF
        $pdf = Pdf::loadView('pdfs.orders', $data);

        // Tên file xuất
        $fileName = $orderIds
            ? 'Mix&Match_Selected_Orders_' . now()->format('Y-m-d') . '.pdf'
            : 'Mix&Match_All_Orders_' . now()->format('Y-m-d') . '.pdf';

        // Trả về file PDF dạng download
        return response()->streamDownload(
            fn() => print($pdf->output()),
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }
}
