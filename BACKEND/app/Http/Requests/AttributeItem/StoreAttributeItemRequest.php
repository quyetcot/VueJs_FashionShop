<?php

namespace App\Http\Requests\AttributeItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttributeItemRequest extends FormRequest
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
            'value' => 'required|string|max:255|unique:attribute_items,value',
            'attribute_id' => 'required',
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
            'attribute_id.required' => 'Vui lòng chọn thuộc tính',
            'value.required' => 'Vui lòng nhập giá trị thuộc tính.',
            'value.string' => 'Giá trị thuộc tính không hợp lệ.',
            'value.max' => 'Giá trị thuộc tính không hợp lệ.',
            'value.unique' => 'Giá trị thuộc tính đã tồn tại.',
        ];
    }
}
