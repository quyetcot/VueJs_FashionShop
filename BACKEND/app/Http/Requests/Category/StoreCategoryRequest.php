<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
        // dd(request()->all());
        return [
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'img_thumbnail' => 'nullable',
            'description' => 'nullable|string|max:255',
        ];
    }
        /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.string' => 'Tên danh mục phải là chuỗi ký tự.',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên danh mục này đã tồn tại.',

            'parent_id.integer' => 'ID của danh mục cha phải là một số nguyên.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',


            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.max' => 'Mô tả không được vượt quá 255 ký tự.',
        ];
    }
}
