<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Attribute;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Http\Requests\Attribute\StoreAttributeRequest;
use App\Http\Requests\Attribute\UpdateAttributeRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attributes = Attribute::query()->latest('id')->get();
        return response()->json($attributes, 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttributeRequest $request)
    {
        try {
            // Lấy toàn bộ dữ liệu từ request
            $data = $request->all();

            // Tạo slug từ name
            $data['slug'] = $this->generateUniqueSlug($data['name']);

            // Tạo attribute mới
            $attribute = Attribute::create($data);

            // Trả về JSON với thông báo thành công và dữ liệu attribute
            return response()->json([
                'message' => 'Thêm Thuộc Tính Thành Công!',
                'data' => $attribute
            ], 201);
        } catch (QueryException $e) {
            // Trả về lỗi nếu có vấn đề trong quá trình thêm mới
            return response()->json([
                'message' => 'Thêm Thuộc Tính Thất Bại!',
                'error' => $e->getMessage() // Tùy chọn: trả về chi tiết lỗi nếu cần
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Tìm attribute theo ID
            $attribute = Attribute::findOrFail($id);
            return response()->json($attribute, 200);
        } catch (ModelNotFoundException $e) {
            // Trả về lỗi 404 nếu không tìm thấy attribute
            return response()->json([
                'message' => 'Thuộc tính Tồn Tại!'
            ], 404);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttributeRequest $request, string $id)
    {
        try {
            $data = $request->all();
            $data['slug'] = Str::slug($data['name'], '-');
            // Tìm attribute theo ID
            $attribute = Attribute::findOrFail($id);
            // Kiểm tra xem giá trị 'name' có thay đổi hay không
            if ($data['name'] !== $attribute->name) {
                // Nếu 'name' thay đổi, tạo slug mới và đảm bảo slug duy nhất
                $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
            } else {
                // Nếu không thay đổi, giữ nguyên slug hiện tại
                $data['slug'] = $attribute->slug;
            }
            // Cập nhật attribute với dữ liệu mới
            $attribute->update($data);

            // Trả về JSON với dữ liệu thuộc tính sau khi cập nhật
            return response()->json([
                'message' => 'Cập Nhật Thuộc Tính Thành Công!',
                'data' => $attribute
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Trả về lỗi 404 nếu không tìm thấy attribute
            return response()->json([
                'message' => 'Thuộc tính Tồn Tại!'
            ], 404);
        } catch (QueryException $e) {
            // Trả về lỗi 500 nếu có vấn đề trong quá trình cập nhật
            return response()->json([
                'message' => 'Cập Nhật Thuộc Tính Thất Bại!',
                'error' => $e->getMessage() // Tùy chọn: trả về chi tiết lỗi
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Tìm attribute theo ID
            $attribute = Attribute::findOrFail($id);

            // Xóa attribute
            $attribute->delete();

            // Trả về JSON với thông báo sau khi xóa thành công
            return response()->json([
                'message' => 'Xóa Thuộc Tính Thành Công!'
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Trả về lỗi 404 nếu không tìm thấy attribute
            return response()->json([
                'message' => 'Thuộc tính Tồn Tại!'
            ], 404);
        } catch (QueryException $e) {
            // Trả về lỗi 500 nếu có vấn đề trong quá trình xóa
            return response()->json([
                'message' => 'Xóa Thuộc Tính Thất Bại!',
                'error' => $e->getMessage() // Tùy chọn: trả về chi tiết lỗi nếu cần
            ], 500);
        }
    }
    private function generateUniqueSlug($value, $id = null)
    {
        // Tạo slug từ 'value'
        $slug = Str::slug($value, '-');

        // Kiểm tra slug trùng lặp, bỏ qua chính bản ghi đang được cập nhật (nếu có id)
        $original_slug = $slug;
        $count = 1;

        // Vòng lặp kiểm tra slug có trùng lặp không, nếu có thì thêm số
        while (Attribute::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $original_slug . '-' . $count;
            $count++;
        }
        return $slug;
    }

    public function search(Request $request)
    {
        $query = $request->input('query'); // Lấy từ khóa tìm kiếm từ body request

        // Kiểm tra xem có từ khóa tìm kiếm không
        if (empty($query)) {
            $results = Attribute::all(); // Nếu không có từ khóa, trả về tất cả sản phẩm
            return response()->json(['message' => 'Hiển thị tất cả thuộc tính.', 'data' => $results]);
        }

        // Tìm kiếm trong cột `name` hoặc các cột khác nếu cần
        $results = Attribute::where('name', 'LIKE', "%{$query}%")
            // ->orWhere('description', 'LIKE', "%{$query}%") // Thêm cột mô tả nếu có
            ->get();

          // Xử lý trường hợp không tìm thấy
          if ($results->isEmpty()) {
            return response()->json([
                'message' => 'Không tìm thấy thuộc tính.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'message' => 'Kết quả tìm kiếm.',
            'data' => $results,
        ]);
    }
}
