<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProduct extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product');
        // dd($productId);
        return [
            'brand_id' => 'required|integer|exists:brands,id',
            'category_id' => 'required|integer|exists:categories,id',
            'tags' => 'required|array|min:1',
            'tags.*' => 'integer|exists:tags,id',
            'galleries' => 'array|min:1',
            'galleries.*.id' => 'integer|exists:product_galleries,id', // Kiểm tra 'id' tồn tại trong bảng galleries
            'galleries.*.image' => 'string', // Kiểm tra 'image' là chuỗi
            "weight" => "required",
            'type' => 'required|integer|in:0,1', // Type chỉ có 2 loại: 0 (simple) và 1 (variant)
            'sku' => 'required|string|max:255|unique:products,sku,' . $productId,
            'name' => 'required|string|max:255|unique:products,name,' . $productId,

            'img_thumbnail' => 'string',

            // Nếu type = 0 thì các trường này là bắt buộc, nếu type = 1 thì không bắt buộc
            'price_regular' => 'numeric|min:0|required_if:type,0',
            'price_sale' => 'numeric|min:0|lte:price_regular|required_if:type,0',
            'quantity' => 'integer|min:1|required_if:type,0',

            'description' => 'required|string',
            'description_title' => 'required|string',

            // Nếu type = 1 thì các thuộc tính, biến thể là bắt buộc
            'attribute_id' => 'required_if:type,1|array|min:1',
            'attribute_id.*' => 'integer|exists:attributes,id',

            'attribute_item_id' => 'required_if:type,1|array',
            'attribute_item_id.*' => 'array|min:1',
            'attribute_item_id.*.*' => 'integer|exists:attribute_items,id',

            'product_variant' => 'required_if:type,1|array|min:1',
            'product_variant.*.attribute_item_id' => 'required_if:type,1|array|min:1',

            // 'product_variant.*.attribute_item_id.*' => 'integer|exists:attribute_items,id',

            'product_variant.*.attribute_item_id.*.id' => 'required|integer|exists:attribute_items,id',
            'product_variant.*.attribute_item_id.*.value' => 'required|string|max:255',

            'product_variant.*.sku' => 'required_if:type,1|string|max:255|distinct',
            'product_variant.*.quantity' => 'integer|min:0|required_if:type,1',
            'product_variant.*.price_regular' => 'numeric|min:0|required_if:type,1',
            'product_variant.*.price_sale' => 'numeric|min:0|lte:product_variant.*.price_regular|required_if:type,1',
            'product_variant.*.image' => 'nullable|string',


        ];
    }

    public function messages(): array
    {
        return [
            'brand_id.required' => 'Vui lòng chọn thương hiệu.',
            'brand_id.exists' => 'Thương hiệu được chọn không tồn tại.',
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục được chọn không tồn tại.',
            'tags.required' => 'Vui lòng chọn ít nhất một thẻ.',
            'tags.*.exists' => 'Thẻ được chọn không tồn tại.',
            'gallery.required' => 'Vui lòng thêm ít nhất một ảnh.',
            'gallery.*.string' => 'Mỗi ảnh trong thư viện phải là chuỗi hợp lệ.',
            'type.required' => 'Vui lòng chọn loại sản phẩm.',
            'type.in' => 'Loại sản phẩm không hợp lệ (chỉ được phép 0 hoặc 1).',
            'sku.required' => 'Vui lòng nhập mã SKU.',
            'sku.unique' => 'Mã SKU đã tồn tại, vui lòng nhập mã khác.',
            'weight.required' => 'Vui lòng nhập khối lượng sản phẩm.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.unique' => 'Tên sản phẩm đã tồn tại, vui lòng chọn tên khác.',
            'img_thumbnail.required' => 'Vui lòng thêm ảnh đại diện của sản phẩm.',
            'price_regular.required_if' => 'Vui lòng nhập giá gốc.',
            'price_regular.numberic' => 'Giá gốc không hợp lệ',
            'price_sale.lte' => 'Giá khuyến mãi phải nhỏ hơn hoặc bằng giá gốc.',
            'quantity.required_if' => 'Vui lòng nhập số lượng.',
            'quantity.integer' => 'Số lượng không hợp lệ.',
            'quantity.min' => 'Số lượng không hợp lệ.',
            'description.required' => 'Vui lòng nhập mô tả sản phẩm.',
            'description_title.required' => 'Vui lòng nhập tiêu đề mô tả.',

            'attribute_id.required_if' => 'Vui lòng chọn thuộc tính khi sản phẩm là loại biến thể.',
            'attribute_id.*.exists' => 'Thuộc tính được chọn không tồn tại.',
            'attribute_item_id.required_if' => 'Vui lòng chọn giá trị thuộc tính khi sản phẩm là loại biến thể.',
            'attribute_item_id.*.*.exists' => 'Giá trị thuộc tính không tồn tại.',
            'product_variant.required_if' => 'Vui lòng thêm ít nhất một biến thể sản phẩm.',
            'product_variant.*.sku.distinct' => 'Mã SKU của mỗi biến thể phải là duy nhất.',
            'product_variant.*.price_sale.lte' => 'Giá khuyến mãi của biến thể phải nhỏ hơn hoặc bằng giá gốc.',
            'price_sale.required_if' => 'Vui lòng nhập giá khuyến mãi.',
            'product_variant.*.quantity.required_if' => "Vui lòng nhập số lượng",
            'product_variant.*.quantity.min' => "Số lượng không hợp lệ",
            'product_variant.*.quantity.integer' => "Số lượng không hợp lệ",
            'product_variant.*.sku.required_if' => 'Vui lòng nhập mã SKU.',



        ];
    }
}
