<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Kiểm tra xem người dùng có xác thực hợp lệ
            $addresses = auth()->user()->addresses;

            return response()->json([
                'message' => 'Lấy thông tin thành công!',
                'addresses' => $addresses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->getMessage()
            ]);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAddressRequest $request)
    {
        try {
            // Kiểm tra xem người dùng có xác thực hợp lệ
            $user = auth()->user();

            if ($request->is_default) {
                Address::where('user_id', $user->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
            $dataAddress = [
                'label' => $request->label,
                'address' => $request->address,
                'city' => $request->city,
                'district' => $request->district,
                'ward' => $request->ward,
                'phone' => $request->phone,
                'is_default' => $request->is_default ?? false, // Nếu không có giá trị, mặc định là false
            ];

            // dd($dataAddress);
            // Tạo mới địa chỉ và lưu vào cơ sở dữ liệu
            $address = $user->addresses()->create($dataAddress);

            // Trả về phản hồi thành công
            return response()->json([
                'status' => true,
                'message' => 'Địa chỉ đã được thêm thành công!',
                'address' => $address
            ], 201);
        } catch (\Exception $e) {
            // Xử lý lỗi nếu có
            return response()->json([
                'status' => false,
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            // Kiểm tra xem người dùng có xác thực hợp lệ
            $user = auth()->user();

            // Tìm địa chỉ với ID và thuộc về người dùng đã đăng nhập
            $address = $user->addresses()->find($id);

            // Kiểm tra nếu không tìm thấy địa chỉ
            if (!$address) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy địa chỉ.',
                ], 404);
            }

            // Trả về thông tin chi tiết địa chỉ
            return response()->json([
                'status' => true,
                'message' => 'Lấy thông tin địa chỉ thành công!',
                'address' => $address
            ], 200);
        } catch (\Exception $e) {
            // Xử lý lỗi nếu có
            return response()->json([
                'status' => false,
                'errors' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressRequest $request, $id)
    {
        try {
            // Xác thực người dùng đăng nhập
            $user = auth()->user();

            // Tìm địa chỉ thuộc về người dùng đã đăng nhập và có ID tương ứng
            $address = $user->addresses()->findOrFail($id);

            // Kiểm tra yêu cầu cập nhật `is_default`
            $isDefaultRequest = $request->is_default;

            // Nếu địa chỉ hiện tại là `is_default` và yêu cầu `is_default` là `false`, ngăn cản cập nhật
            if ($address->is_default && !$isDefaultRequest) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không thể bỏ trạng thái mặc định khi địa chỉ này là mặc định hiện tại!'
                ], 400);
            }

            $dataAddress = [
                'label' => $request->label ?? $address->label,
                'address' => $request->address ?? $address->address,
                'city' => $request->city ?? $address->city,
                'district' => $request->district ?? $address->district,
                'ward' => $request->ward ?? $address->ward,
                'phone' => $request->phone ?? $address->phone,
                'is_default' => $request->is_default ?? $address->is_default, // Giữ lại giá trị cũ nếu không có giá trị mới
            ];
            if ($dataAddress['is_default']) {
                // Cập nhật tất cả các địa chỉ khác của người dùng thành `false`
                $user->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
            }

            // Cập nhật thông tin địa chỉ từ request
            $address->update($dataAddress);

            return response()->json([
                'status' => true,
                'message' => 'Địa chỉ đã được cập nhật thành công!',
                'address' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Kiểm tra xem người dùng có xác thực hợp lệ
            $user = auth()->user();

            // Tìm địa chỉ với ID và thuộc về người dùng đã đăng nhập
            $address = $user->addresses()->find($id);

            // Kiểm tra nếu không tìm thấy địa chỉ
            if (!$address) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy địa chỉ.',
                ], 404);
            }

            //Kiểm tra địa chỉ là mặc định
            if ($address->is_default) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không thể xóa địa chỉ mặc định.'
                ], 400);
            }

            // Xóa địa chỉ
            $address->delete();

            // Trả về thông báo thành công
            return response()->json([
                'status' => true,
                'message' => 'Địa chỉ đã được xóa thành công!',
            ], 200);
        } catch (\Exception $e) {
            // Xử lý lỗi nếu có
            return response()->json([
                'status' => false,
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}
