<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
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
            'label' => 'string|max:255',
            'address' => 'string|max:255',
            'city' => 'array|max:255',
            'district' => 'array|max:255',
            'ward' => 'array|max:255',
            'phone' => 'string|regex:/^0[0-9]{9}$/',
            'is_default' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'label.required' => 'Vui lòng nhập nhãn cho địa chỉ.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'city.required' => 'Vui lòng nhập thành phố.',
            'district.required' => 'Vui lòng nhập quận/huyện.',
            'ward.required' => 'Vui lòng nhập phường/xã.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải bắt đầu bằng số 0 và có 10 chữ số.',
        ];
    }
}
