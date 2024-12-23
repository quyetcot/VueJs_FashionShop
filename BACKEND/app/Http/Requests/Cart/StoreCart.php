<?php

namespace App\Http\Requests\Cart;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class StoreCart extends FormRequest
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
        return [
            'order_id' => 'required_without_all:product_id,product_variant_id,quantity|integer|exists:orders,id',
            'product_id' => 'required_without:order_id|integer',
            'product_variant_id' => 'nullable|integer',
            'quantity' => 'required_without:order_id|integer|min:1',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->order_id) {
                $product = Product::query()->find($this->product_id);
                if (!$product) {
                    $validator->errors()->add('product_id', 'The selected product id is invalid.');
                    return;
                }
                if ($product->type) {
                    // Đối với sản phẩm có biến thể, product_variant_id là bắt buộc
                    if (!$this->filled('product_variant_id')) {
                        $validator->errors()->add('product_variant_id', 'product_variant_id is required.');
                    } else {

                        $variant = ProductVariant::query()->find($this->product_variant_id);
                        if (!$variant || $variant->product_id != $product->id) {
                            $validator->errors()->add('product_variant_id', 'The selected product variant id is invalid.');
                        } else {
                            // Kiểm tra số lượng
                            if ($this->quantity > $variant->quantity) {
                                $validator->errors()->add('quantity', 'The quantity requested exceeds the available quantity of the product variant.');
                            }
                        }
                    }
                } else {
                    // Đối với sản phẩm đơn, product_variant_id phải không có hoặc null
                    if ($this->filled('product_variant_id')) {
                        $validator->errors()->add('product_variant_id', 'Single product with no variations');
                    }

                    // Kiểm tra số lượng sản phẩm đơn
                    if ($this->quantity > $product->quantity) {
                        $validator->errors()->add('quantity', 'The quantity requested exceeds the available quantity of the product.');
                    }
                }
            }
        });
    }
}
