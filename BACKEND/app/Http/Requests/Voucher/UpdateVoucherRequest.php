<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVoucherRequest extends FormRequest
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
    public function rules()
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'code' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'min_order_value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'discount_type' => 'in:fixed,percent',
            'discount_value' => [
                'required_if:discount_type,fixed,percent',  // bắt buộc nếu là fixed hoặc percentage
                'numeric',  // phải là số
                'min:0',  // không được nhỏ hơn 0
                function ($attribute, $value, $fail) {
                    if ($this->discount_type == 'percent' && $value > 100) {
                        $fail('The ' . $attribute . ' must not be greater than 100 when discount type is percentage.');
                    }
                }
            ],
            'meta' => 'nullable|array',
            'meta.*.meta_key' => 'required_with:meta.*|string|max:255',
            'meta.*.meta_value' => [
                'required_with:meta.*|string',
                function ($attribute, $value, $fail) {
                    // Kiểm tra xem meta_value có phải là JSON hợp lệ không
                    $decoded = json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail('The ' . $attribute . ' must be a valid JSON string.');
                    }
                },
            ],
        ];
    }

    public function messages()
    {
        return [
            // 'meta.*.meta_key.required_with' => 'The meta key field is required when meta is present.',
            // 'meta.*.meta_value.required_with' => 'The meta value field is required when meta is present.',


            'title.string' => 'Trường tiêu đề phải là chuỗi.',
            'title.max' => 'Trường tiêu đề không được vượt quá 255 ký tự.',
            'description.string' => 'Trường mô tả phải là chuỗi.',
            'description.max' => 'Trường mô tả không được vượt quá 500 ký tự.',
            'code.string' => 'Trường mã phải là chuỗi.',
            'code.max' => 'Trường mã không được vượt quá 100 ký tự.',
            'start_date.date' => 'Ngày bắt đầu phải là một ngày hợp lệ.',
            'start_date.after_or_equal' => 'Ngày bắt đầu phải sau hoặc bằng ngày hôm nay.',
            'start_date.before_or_equal' => 'Ngày bắt đầu phải trước hoặc bằng ngày kết thúc.',
            'end_date.date' => 'Ngày kết thúc phải là một ngày hợp lệ.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'min_order_value.numeric' => 'Giá trị đơn hàng tối thiểu phải là một số.',
            'min_order_value.min' => 'Giá trị đơn hàng tối thiểu không được nhỏ hơn 0.',
            'usage_limit.integer' => 'Giới hạn sử dụng phải là số nguyên.',
            'usage_limit.min' => 'Giới hạn sử dụng phải ít nhất là 1.',
            'is_active.boolean' => 'Trường trạng thái hoạt động phải là true hoặc false.',
            'discount_type.in' => 'Loại giảm giá chỉ được là cố định (fixed) hoặc phần trăm (percent).',
            'discount_value.required_if' => 'Giá trị giảm giá là bắt buộc khi loại giảm giá là cố định hoặc phần trăm.',
            'discount_value.numeric' => 'Giá trị giảm giá phải là một số.',
            'discount_value.min' => 'Giá trị giảm giá không được nhỏ hơn 0.',
            'meta.array' => 'Trường meta phải là một mảng.',
            'meta.*.meta_key.required_with' => 'Trường meta key là bắt buộc khi meta có mặt.',
            'meta.*.meta_key.string' => 'Trường meta key phải là một chuỗi.',
            'meta.*.meta_key.max' => 'Trường meta key không được vượt quá 255 ký tự.',
            'meta.*.meta_value.required_with' => 'Trường meta value là bắt buộc khi meta có mặt.',
            'meta.*.meta_value.string' => 'Trường meta value phải là một chuỗi.',
        ];
    }
}
