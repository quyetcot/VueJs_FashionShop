<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        if ($this->isMethod('post')) {
            return [
                'name'         => 'required|string|max:255',
                'phone_number' => 'required|string|regex:/^0[0-9]{9}$/|unique:users,phone_number',
                'email'        => 'required|email|unique:users,email',
                'address'      => 'required|string|max:255',
                'avatar'       => 'nullable',
                'password'     => 'required|min:8',
                'birth_date'   => 'nullable|date',
                'is_active'    => 'boolean',
                'gender'       => 'nullable|boolean',
                'role_id'      => 'exists:roles,id',
            ];
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            if ($this->route('employee')) {
                $userID = $this->route('employee');
            } elseif ($this->route('client')) {
                $userID = $this->route('client');
            } else {
                $userID = null;
            }


            return [
                'name'         => 'nullable|string|max:255',
                'phone_number' => [
                    'required',
                    'regex:/^0[0-9]{9}$/', // Chỉ chấp nhận số điện thoại gồm đúng 10 chữ số
                    Rule::unique('users')->ignore($userID,'id'), // Bỏ qua ID hiện tại
                ],
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($userID,'id'), // Bỏ qua ID hiện tại
                ],
                'address'      => 'nullable|string|max:255',
                'avatar'       => 'nullable',
                'password'     => 'nullable|min:8',
                'birth_date'   => 'nullable|date',
                'is_active'    => 'boolean',
                'gender'       => 'nullable|boolean',
                'role_id'      => 'exists:roles,id',
            ];
        }

        return [];
    }


    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->messages();

        $response = response()->json([
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST);
        throw new HttpResponseException($response);
    }
    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập Email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được đăng ký.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'phone_number.required' => 'Vui lòng nhập số điện thoại.',
            'phone_number.regex' => 'Số điện thoại không đúng định dạng.',
            'phone_number.unique' => 'Số điện thoại này đã được đăng ký.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
        ];
    }
}
