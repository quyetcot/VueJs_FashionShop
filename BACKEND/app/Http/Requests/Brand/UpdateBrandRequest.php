<?php

namespace App\Http\Requests\Brand;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class UpdateBrandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' =>'Lỗi sửa brand',
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
            'name' => 'required|string|min:3|max:255|unique:brands,name,' . $this->route('brand') . ',id',
        'image' => 'nullable', // Cho phép bỏ qua ảnh nếu không upload mới
        'email' => 'required|email|max:255|unique:brands,email,' . $this->route('brand') . ',id',
        'phone_number' => 'required|string|size:10|regex:/^[0-9]{10}$/|unique:brands,phone_number,' . $this->route('brand') . ',id',
        'address' => 'required|string|min:5|max:255',

        ];
    }
}
