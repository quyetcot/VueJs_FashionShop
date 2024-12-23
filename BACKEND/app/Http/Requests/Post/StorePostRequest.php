<?php

namespace App\Http\Requests\Post;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostRequest extends FormRequest
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
            'message' => 'Lỗi thêm bài viết',
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
            'post_name' => 'required|string|min:3|max:255',
            'post_content' => 'required|string|min:10',
            // 'post_view' => 'required|integer|min:0',
            'slug' => 'nullable|string|max:255|unique:posts,slug',
            'img_thumbnail' => 'nullable|string|max:255',  // Thay đổi từ array thành chuỗi ký tự
            // 'description' => 'nullable|string|max:500',
            'status' => 'required|boolean',

            'category_id' => 'nullable|exists:categories,id',
            'featured' => 'required|boolean',
        ];
}
public function messages(): array
    {
        return [
            'post_name.required' => 'Vui lòng nhập tên bài viết',
            'post_name.min' => 'Tên bài viết phải dài hơn 3 kí tự',
            'post_name.max' => 'Tên bài viết phải ngắn hơn 255 kí tự',
            'post_content.required' => 'Vui lòng nhập nội dung bài viết',
            'post_content.min' => 'Tên bài viết phải dài hơn 3 kí tự',

        ];
    }

}
