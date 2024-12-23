<?php

namespace App\Http\Requests\Order;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
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
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'user_note' => 'nullable|string|max:255',
            'ship_user_name' => 'required|string|max:255',
            'ship_user_phonenumber' => [
                'required',
                'regex:/^0[3|5|7|8|9][0-9]{8}$/',
            ],
            'ship_user_email' => 'required|email|max:255',
            'xa'=> 'required|string|max:255',
            'huyen'=> 'required|string|max:255',
            'tinh'=> 'required|string|max:255',
            'ship_user_address' => 'required|string|max:255',
            'shipping_method' => 'required|string|max:50',
            'shipping_fee' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'product_id' => 'required_without:cart_item_ids|integer|exists:products,id',
            'product_variant_id' => 'required_if:is_variant,true|integer|exists:product_variants,id|nullable',
            'quantity' => 'required_without:cart_item_ids|integer|min:1',
            'cart_item_ids' => 'required_without:product_id|array',
            'cart_item_ids.*' => 'integer|exists:cart_items,id',
            // 'quantityOfCart.*' => 'required_without:product_id|integer|min:1',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
        ];
    }
    public function messages(): array
{
    return [
        'payment_method_id.required' => 'Vui lòng chọn phương thức thanh toán.',
        'payment_method_id.integer' => 'Phương thức thanh toán không hợp lệ.',
        'payment_method_id.exists' => 'Phương thức thanh toán không tồn tại.',

        'user_note.string' => 'Ghi chú phải là văn bản.',
        'user_note.max' => 'Ghi chú không được vượt quá 255 ký tự.',

        'ship_user_name.required' => 'Vui lòng nhập tên người nhận.',
        'ship_user_name.string' => 'Tên người nhận phải là văn bản.',
        'ship_user_name.max' => 'Tên người nhận không được vượt quá 255 ký tự.',

        'ship_user_phonenumber.required' => 'Vui lòng nhập số điện thoại người nhận.',
        'ship_user_phonenumber.regex' => 'Số điện thoại không đúng định dạng.',

        'ship_user_email.required' => 'Vui lòng nhập email người nhận.',
        'ship_user_email.email' => 'Email không đúng định dạng.',
        'ship_user_email.max' => 'Email không được vượt quá 255 ký tự.',

        'xa.required' => 'Vui lòng nhập xã/phường.',
        'huyen.required' => 'Vui lòng nhập quận/huyện.',
        'tinh.required' => 'Vui lòng nhập tỉnh/thành phố.',

        'ship_user_address.required' => 'Vui lòng nhập địa chỉ người nhận.',
        'ship_user_address.string' => 'Địa chỉ phải là văn bản.',
        'ship_user_address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',

        'shipping_method.required' => 'Vui lòng chọn phương thức vận chuyển.',
        'shipping_method.max' => 'Phương thức vận chuyển không được vượt quá 50 ký tự.',

        'shipping_fee.required' => 'Vui lòng nhập phí vận chuyển.',
        'shipping_fee.numeric' => 'Phí vận chuyển phải là số.',
        'shipping_fee.min' => 'Phí vận chuyển không được nhỏ hơn 0.',
        'shipping_fee.regex' => 'Phí vận chuyển không hợp lệ.',

        'product_id.required_without' => 'Vui lòng chọn sản phẩm hoặc giỏ hàng.',
        'product_id.integer' => 'Mã sản phẩm không hợp lệ.',
        'product_id.exists' => 'Sản phẩm không tồn tại.',

        'product_variant_id.required_if' => 'Vui lòng chọn biến thể sản phẩm khi cần.',
        'product_variant_id.integer' => 'Mã biến thể không hợp lệ.',
        'product_variant_id.exists' => 'Biến thể sản phẩm không tồn tại.',

        'quantity.required_without' => 'Vui lòng nhập số lượng sản phẩm.',
        'quantity.integer' => 'Số lượng phải là số nguyên.',
        'quantity.min' => 'Số lượng tối thiểu là 1.',

        'cart_item_ids.required_without' => 'Vui lòng chọn sản phẩm trong giỏ hàng.',
        'cart_item_ids.array' => 'Giỏ hàng không hợp lệ.',
        'cart_item_ids.*.integer' => 'Mã sản phẩm trong giỏ hàng không hợp lệ.',
        'cart_item_ids.*.exists' => 'Sản phẩm trong giỏ hàng không tồn tại.',

        'voucher_code.string' => 'Mã giảm giá phải là văn bản.',
        'voucher_code.exists' => 'Mã giảm giá không hợp lệ.',
    ];
}

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // $this->validateProduct($validator);
            $this->validateVoucher($validator);
        });
    }

    // protected function validateProduct($validator)
    // {
    //     if ($this->has('product_id')) {
    //         $product = Product::find($this->product_id);
    //         if ($product->type) {
    //             if (!$this->filled('product_variant_id')) {
    //                 $validator->errors()->add('product_variant_id', 'Vui lòng chọn biến thể sản phẩm.');
    //             } else {
    //                 $this->validateVariant($validator, $product);
    //             }
    //         } else {
    //             if ($this->filled('product_variant_id')) {
    //                 $validator->errors()->add('product_variant_id', 'Sản phẩm đơn không có biến thể.');
    //             }
    //             if ($this->quantity > $product->quantity) {
    //                 $validator->errors()->add('quantity', 'Số lượng mua vượt quá số lượng tồn kho.');
    //             }
    //         }
    //     }
    // }

    // protected function validateVariant($validator, $product)
    // {
    //     $variant = ProductVariant::find($this->product_variant_id);
    //     if (!$variant || $variant->product_id != $product->id) {
    //         $validator->errors()->add('product_variant_id', 'Biến thể sản phẩm không hợp lệ.');
    //     } elseif ($this->quantity > $variant->quantity) {
    //         $validator->errors()->add('quantity', 'Số lượng mua vượt quá số lượng tồn kho của biến thể.');
    //     }
    // }

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
            $this->verifyUserForVoucher($validator, $voucher);
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
    protected function verifyUserForVoucher($validator, $voucher)
    {
        if (!auth('sanctum')->check()) {
            $validator->errors()->add(
                'voucher_code',
                'Vui lòng đăng nhập để sử dụng voucher.'
            );
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Đặt Hàng Không Thành Công!',
            'errors' => $validator->errors(),
        ], 422));
    }
}
