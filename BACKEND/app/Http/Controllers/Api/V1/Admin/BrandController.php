<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\BrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function index(Request $request)
    {
        try {
            // Lấy tất cả các thương hiệu và sắp xếp theo ID mới nhất
            $brands = Brand::query()->latest('id')->get();

            // Kiểm tra dữ liệu
            if ($brands->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không có dữ liệu.',
                    'data' => []
                ], Response::HTTP_OK);
            }

            // Trả về dữ liệu với cấu trúc rõ ràng
            return response()->json([
                'status' => true,
                'message' => 'Danh sách thương hiệu được lấy thành công.',
                'data' => [
                    'total' => $brands->count(),
                    'brands' => $brands
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            Log::error('API/V1/Admin/BrandController@index: ', [$ex->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Đã có lỗi nghiêm trọng xảy ra. Vui lòng thử lại sau.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }




    /**
     * Store a newly created resource in storage.
     */
    public function store(BrandRequest $request)
    {
        try {
            $params = $request->all();
            if ($request->has('image')) {
                $params['image'] = $request->input('image');
            }
            $params['slug'] = $this->generateUniqueSlug($params['name']);
            $brand = Brand::create($params);
            return response()->json([
                'data' => new BrandResource($brand),
                'success' => true,
                'message' => 'Brand đã được thêm thành công'
            ], 201);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thêm Brand thất bại!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $brand = Brand::query()->findOrFail($id);
        return new BrandResource($brand);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBrandRequest $request, string $id)
    {
        try {
            // Lấy tất cả các tham số trừ trường 'image' từ request
            $data = $request->except('image');

            // Tìm brand theo id
            $brand = Brand::findOrFail($id);

            // Xử lý ảnh: nếu có ảnh mới, sử dụng giá trị ảnh từ request, nếu không giữ nguyên ảnh cũ
            if ($request->filled('image')) {
                // Lấy chuỗi từ trường 'image' trong request
                $data['image'] = $request->input('image');
            } else {
                // Nếu không có ảnh mới, giữ nguyên giá trị ảnh cũ
                $data['image'] = $brand->image;
            }

            // Nếu tên thay đổi, tạo slug mới
            if ($data['name'] !== $brand->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
            } else {
                $data['slug'] = $brand->slug;
            }

            // Cập nhật bản ghi Brand
            $brand->update($data);

            return response()->json([
                'data' => new BrandResource($brand),
                'success' => true,
                'message' => 'Brand đã được sửa thành công'
            ], 200);

        } catch (ModelNotFoundException $e) {
            // Xử lý lỗi nếu không tìm thấy brand
            return response()->json([
                'message' => 'Brand không tồn tại!'
            ], 404);
        } catch (QueryException $e) {
            // Xử lý lỗi truy vấn cơ sở dữ liệu
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật Brand thất bại!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $brand = Brand::query()->findOrFail($id);



        $brand->delete();


        return response()->json([
            'status' => true,
            'message' => "Xóa brand và ảnh thành công."
        ], 200);
    }
    private function generateUniqueSlug($value, $id = null)
    {
        // Tạo slug từ 'value'
        $slug = Str::slug($value, '-');

        // Kiểm tra slug trùng lặp, bỏ qua chính bản ghi đang được cập nhật (nếu có id)
        $original_slug = $slug;
        $count = 1;

        // Vòng lặp kiểm tra slug có trùng lặp không, nếu có thì thêm số
        while (Brand::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $original_slug . '-' . $count;
            $count++;
        }
        return $slug;
    }

    public function search(Request $request)
    {
        $query = $request->input('query'); // Lấy từ khóa tìm kiếm từ body request

        if (empty($query)) {
            $results = Brand::all();
            return response()->json(['message' => 'Hiển thị tất cả brand.', 'data' => $results]);
        }

        // Tìm kiếm trong cột `name` hoặc các cột khác nếu cần
        $results = Brand::where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('phone_number', 'LIKE', "%{$query}%") // Thêm cột mô tả nếu có
            ->get();

            if ($results->isEmpty()) {
                return response()->json(['message' => 'Không tìm thấy brand.',
                'data' => []
            ],404);
            }

        return response()->json([
            'message' => 'Kết quả tìm kiếm.',
            'data' => $results,
        ]);
    }
}
