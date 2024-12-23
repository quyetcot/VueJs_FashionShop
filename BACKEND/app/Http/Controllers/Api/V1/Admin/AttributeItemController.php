<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AttributeItem;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\AttributeItem\StoreAttributeItemRequest;
use App\Http\Requests\AttributeItem\UpdateAttributeItemRequest;

class AttributeItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $attributeItems = AttributeItem::query()->latest('id')->get();
            return response()->json($attributeItems, 200);
        } catch (ModelNotFoundException $e) {
            // Trả về lỗi 404 nếu không tìm thấy attribute
            return response()->json([
                'message' => 'Thuộc tính Tồn Tại!'
            ], 404);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttributeItemRequest $request)
    {
        try {
            // Lấy toàn bộ dữ liệu từ request
            $data = $request->all();
            // Tạo slug từ 'value' và đảm bảo tính duy nhất
            $data['slug'] = $this->generateUniqueSlug($data['value']);

            // Tạo attribute item mới
            $attributeItem = AttributeItem::create($data);

            // Trả về JSON với thông báo thành công và dữ liệu attribute
            return response()->json([
                'message' => 'Thêm Thuộc Tính Thành Công!',
                'data' => $attributeItem
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
            $attributeItem = AttributeItem::findOrFail($id);
            return response()->json($attributeItem, 200);
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
    public function update(UpdateAttributeItemRequest $request, string $id)
    {
        try {
            // Lấy dữ liệu từ request
            $data = $request->all();

            // Tìm attribute item theo ID
            $attributeItem = AttributeItem::findOrFail($id);

            // Kiểm tra xem giá trị 'value' có thay đổi hay không
            if ($data['value'] !== $attributeItem->value) {
                // Nếu 'value' thay đổi, tạo slug mới và đảm bảo slug duy nhất
                $data['slug'] = $this->generateUniqueSlug($data['value'], $id);
            } else {
                // Nếu không thay đổi, giữ nguyên slug hiện tại
                $data['slug'] = $attributeItem->slug;
            }

            // Cập nhật attribute với dữ liệu mới
            $attributeItem->update($data);

            // Trả về JSON với dữ liệu thuộc tính sau khi cập nhật
            return response()->json([
                'message' => 'Cập Nhật Thuộc Tính Thành Công!',
                'data' => $attributeItem
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Trả về lỗi 404 nếu không tìm thấy attribute
            return response()->json([
                'message' => 'Thuộc tính không tồn tại!'
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
            $attributeItem = AttributeItem::findOrFail($id);

            // Xóa attribute
            $attributeItem->delete();

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
    /**
     * Hàm tạo slug duy nhất
     */
    private function generateUniqueSlug($value, $id = null)
    {
        // Tạo slug từ 'value'
        $slug = Str::slug($value, '-');

        // Kiểm tra slug trùng lặp, bỏ qua chính bản ghi đang được cập nhật (nếu có id)
        $original_slug = $slug;
        $count = 1;

        // Vòng lặp kiểm tra slug có trùng lặp không, nếu có thì thêm số
        while (AttributeItem::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $original_slug . '-' . $count;
            $count++;
        }
        return $slug;
    }
}
