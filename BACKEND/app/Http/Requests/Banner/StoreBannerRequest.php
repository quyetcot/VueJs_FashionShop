<?php

namespace App\Http\Requests\Banner;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Lỗi thêm banner',
            'status' => false,
            'errors' => $validator->errors()
        ], 400));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|min:3|max:255',
            'image' => 'required|string',
            'link' => 'nullable|url|max:255',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            // 'status' => 'required|boolean' // Chấp nhận cả true/false hoặc 0/1
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Vui lòng tải ảnh',
            'start_date.required' => 'Vui lòng chọn ngày bắt đầu',
            'end_date.required' => 'Vui lòng chọn ngày kết thúc',
            'link.url'=> 'Link không hợp lệ'
        ];
    }
}
