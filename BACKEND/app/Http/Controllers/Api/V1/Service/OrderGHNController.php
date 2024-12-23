<?php

namespace App\Http\Controllers\Api\V1\Service;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderGHNController extends Controller
{
    //
    public function createOrder($order_id)
    // Request $dataRequestOrder
    {
        try {
            $order = Order::query()->findOrFail($order_id)->load(["orderDetails.product"])->toArray();
            // dd($order);
            $dataOrder = $order;


            $totalWeight = 0;

            // Kiểm tra và tính tổng trọng lượng từ order_details
            if (isset($order['order_details']) && is_array($order['order_details'])) {
                foreach ($order['order_details'] as $detail) {
                    if (isset($detail['product']['weight'])) {
                        $totalWeight += (float)$detail['product']['weight']; // Chuyển weight sang số thực để tính toán
                    }
                }
            }

            $api_key = env("API_KEY");
            $shop_id = env("SHOP_ID"); // Thay bằng shop_id thực tế của bạn
            $exportaddress = explode(",", $dataOrder["ship_user_address"]);
            // dd($exportaddress);
            $data = [
                "shop_id" => $shop_id,
                "payment_type_id" => $dataOrder['payment_status'] === "Đã thanh toán" ? 1 : 2,
                "note" => "Hàng dễ vỡ xin nhẹ tay",
                "required_note" => "KHONGCHOXEMHANG",
                "return_phone" => "0988207698",
                "return_address" => "Đan Phượng - Hà Nội",

                "from_phone" => "0988207698",
                "from_address" => "Đan Phượng - Hà Nội",
                "from_ward_name" => "Thượng Mỗ",
                "from_district_name" => "Đan Phượng",
                "from_province_name" => "Hà Nội",
                "to_name" => $dataOrder["ship_user_name"],
                "to_phone" => $dataOrder["ship_user_phonenumber"],
                "to_address" => $dataOrder["ship_user_address"],
                "to_ward_name" => $exportaddress[1],
                "to_district_name" => $exportaddress[2],
                "to_province_name" => $exportaddress[3],
                "cod_amount" => $dataOrder['payment_status'] === "Đã thanh toán" ? null : 300000, //tiền thu hộ người gửi max:50.000.000	

                "weight" => $totalWeight,
                "length" => 1,
                "width" => 19,
                "height" => 10,

                // "service_id" => 0,
                "service_type_id" => 2,

                "items" => []
            ];
            // dd($dataOrder['total']);
            foreach ($dataOrder["order_details"] as  $value) {

                $data["items"][] = [
                    "name" => $value["product_name"],
                    "code" => $value["product"]["sku"],
                    "quantity" => $value["quantity"] ?? null,
                    "price" => (int) $value["price"] ?? null,
                    "image" => $value["product_img"] ?? null
                ];
            }


            // Khởi tạo cURL
            $ch = curl_init();

            // Cấu hình cURL
            curl_setopt($ch, CURLOPT_URL, "https://online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/create");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Token: ' . $api_key,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            // Gửi request và lấy response
            $response = curl_exec($ch);

            // Kiểm tra lỗi cURL
            if (curl_errno($ch)) {
                throw new \Exception('cURL Error: ' . curl_error($ch));
            }

            // Đóng cURL
            curl_close($ch);

            // Decode JSON response
            $responseData = json_decode($response, true);

            // Kiểm tra nếu có lỗi từ API GHN
            if (!isset($responseData['code']) || $responseData['code'] !== 200) {
                throw new \Exception($responseData['message'] ?? 'Lỗi không xác định từ API GHN');
            }

            // Trả về response thành công
            // return response()->json([
            //     "message" => "Tạo đơn hàng thành công bên giao hàng nhanh",
            //     "order_data" => $responseData['data']
            // ], \Illuminate\Http\Response::HTTP_OK);
            return [
                "message" => "Tạo đơn hàng thành công bên giao hàng nhanh",
                "order_data" => $responseData['data']
            ];
        } catch (\Exception $ex) {
            // Trả về lỗi
            return response()->json([
                "message" => $ex->getMessage()
            ], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ghnUpdateOrder(Request $request)
    {
        try {
            $request->validate([
                "order_id" => "required|integer",
                "order_status" => "required"
            ]);

            $statusMap = [
                '1' => 'Đang vận chuyển',
                '2' => 'Giao hàng thành công',
            ];

            $order_id = $request->input('order_id');
            $new_status = $request->input('order_status');

            // Kiểm tra trạng thái mới có hợp lệ không
            if (!isset($statusMap[$new_status])) {
                return response()->json([
                    'message' => 'Invalid order status.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Lấy đơn hàng bằng ID
            $order = Order::query()->findOrFail($order_id);

            // Kiểm tra trạng thái hiện tại
            $current_status = $order->order_status;

            if ($current_status == $statusMap['2']) {
                return response()->json([
                    'message' => 'Không thể cập nhật khi order_status là:Giao hàng thành công.'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($current_status == $statusMap['1'] && $new_status == '1') {
                return response()->json([
                    'message' => 'Đơn hàng của bạn đang ở trạng thái Đang vận chuyển.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Cập nhật trạng thái đơn hàng
            $order->order_status = $statusMap[$new_status];
            $order->save();

            // Trả về phản hồi JSON
            return response()->json([
                'message' => 'Order status updated successfully.',
                'order_id' => $order_id,
                'order_status' => $new_status
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {


            return response()->json([
                'message' => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function ghnGetOrder()
    {
        try {

            $order = Order::query()->with(["orderDetails"])->latest('id')->where('order_status', "Đang vận chuyển")->orWhere('order_status', "Giao hàng thành công")->get();
            // dd($order->toArray());
            return response()->json([
                "order" => $order
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                "message" => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
