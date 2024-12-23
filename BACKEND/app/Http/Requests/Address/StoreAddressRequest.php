<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
           'label' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|array|max:255',
            'district' => 'required|array|max:255',
            'ward' => 'required|array|max:255',
            'phone' => 'required|string|regex:/^0[0-9]{9}$/',
            'is_default' => 'boolean', // is_default có thể là true hoặc false
        ];
    }

    public function messages()
    {
        return [
            'label.required' => 'Vui lòng nhập tên địa chỉ.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'city.required' => 'Vui lòng nhập thành phố.',
            'district.required' => 'Vui lòng nhập quận/huyện.',
            'ward.required' => 'Vui lòng nhập phường/xã.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
        ];
    }
}
