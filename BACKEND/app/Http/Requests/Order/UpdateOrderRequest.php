<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
    // public function rules(): array
    // {
    //     return [
    //         'order_status' => 'required|string|in:' . Order::STATUS_CANCELED . ',' . Order::STATUS_SUCCESS,
    //         'user_note' => 'required|string|max:255',
    //     ];
    // }
    public function rules(): array
    {
        return [
            'order_status' => 'required|string|in:' . Order::STATUS_CANCELED . ',' . Order::STATUS_COMPLETED,
            'user_note' => 'required_if:order_status,' . Order::STATUS_CANCELED . '|string|max:255',
        ];
    }
}
