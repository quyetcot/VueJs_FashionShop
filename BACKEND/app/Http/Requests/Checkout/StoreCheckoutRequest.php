<?php

namespace App\Http\Requests\Checkout;

use App\Models\Product;
use App\Models\Voucher;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    public function prepareForValidation()
    {
        // Lấy tất cả dữ liệu từ request
        $data = $this->all();

        // Loại bỏ các trường null hoặc rỗng (''), kể cả trong mảng con
        $filteredData = array_filter($data, function ($value) {
            if (is_array($value)) {
                // Nếu là mảng, loại bỏ các giá trị null/rỗng trong mảng
                return count(array_filter($value, function ($item) {
                    return $item !== null && $item !== '';
                })) > 0;
            }
            // Nếu không phải mảng, loại bỏ null hoặc chuỗi rỗng
            return $value !== null && $value !== '';
        });

        // Ghi đè lại dữ liệu request
        $this->replace($filteredData);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Mua giỏ hàng
            'cart_item_ids' => 'required_without:product_id|array',
            'cart_item_ids.*' => 'integer|exists:cart_items,id', // Kiểm tra từng ID của cart_item_ids
            // Mua ngay
            'product_id' => 'required_without:cart_item_ids|integer|exists:products,id',
            'product_variant_id' => 'required_if:is_variant,true|integer|exists:product_variants,id|nullable', // Yêu cầu nếu là biến thể
            'quantity' => 'required_without:cart_item_ids|integer|min:1',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateProduct($validator);
            $this->validateVoucher($validator);
        });
    }

    protected function validateProduct($validator)
    {
        if ($this->has('product_id')) {
            $product = Product::find($this->product_id);

            if ($product->type) { // Sản phẩm có biến thể
                if (!$this->filled('product_variant_id')) {
                    $validator->errors()->add('product_variant_id', 'Vui lòng chọn biến thể sản phẩm.');
                } else {
                    $this->validateVariant($validator, $product);
                }
            } else { // Sản phẩm đơn
                if ($this->filled('product_variant_id')) {
                    $validator->errors()->add('product_variant_id', 'Sản phẩm đơn không có biến thể.');
                }
                if ($this->quantity > $product->quantity) {
                    $validator->errors()->add('quantity', 'Số lượng mua vượt quá số lượng tồn kho.');
                }
            }
        }
    }

    protected function validateVariant($validator, $product)
    {
        $variant = ProductVariant::find($this->product_variant_id);
        if (!$variant || $variant->product_id != $product->id) {
            $validator->errors()->add('product_variant_id', 'Biến thể sản phẩm không hợp lệ.');
        } elseif ($this->quantity > $variant->quantity) {
            $validator->errors()->add('quantity', 'Số lượng mua vượt quá số lượng tồn kho của biến thể.');
        }
    }

    protected function validateVoucher($validator)
    {
        if ($this->filled('voucher_code')) {
            $voucher = Voucher::where('code', $this->voucher_code)->where('is_active', true)->first();

            if (!$voucher) {
                $validator->errors()->add('voucher_code', 'Voucher không hợp lệ.');
                return;
            }
            $this->checkVoucherDates($validator, $voucher);
            $this->checkVoucherUsage($validator, $voucher);
        }
    }

    protected function checkVoucherDates($validator, $voucher)
    {
        if ($voucher->start_date && $voucher->start_date > now()) {
            $validator->errors()->add('voucher_code', 'Voucher chưa bắt đầu.');
        }

        if ($voucher->end_date && $voucher->end_date < now()) {
            $validator->errors()->add('voucher_code', 'Voucher đã hết hạn.');
        }
    }

    protected function checkVoucherUsage($validator, $voucher)
    {
        if ($voucher->usage_limit && $voucher->used_count >= $voucher->usage_limit) {
            $validator->errors()->add('voucher_code', 'Voucher đã hết lượt sử dụng.');
        }
    }
}
