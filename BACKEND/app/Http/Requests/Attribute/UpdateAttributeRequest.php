<?php

namespace App\Http\Requests\Attribute;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttributeRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:attributes,name,' . $this->route('attribute')
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
        'name.required' => 'Vui lòng nhập tên thuộc tính.',
        'name.string' => 'Tên thuộc tính không hợp lệ.',
        'name.max' => 'Tên thuộc tính không hợp lệ.',
        'name.unique' => 'Tên thuộc tính đã tồn tại.',
    ];
}
}
