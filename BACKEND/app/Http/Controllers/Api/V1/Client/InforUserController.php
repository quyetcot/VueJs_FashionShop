<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;

class InforUserController extends Controller
{
    public  function getInforUser()
    {

        try {

            $user =  Auth::user();

            return response()->json([
                'message'  => 'Thông tin người dùng:',
                'InforUser' => $user
            ]);
        } catch (\Throwable $th) {
            if ($th instanceof ModelNotFoundException) {
                return response()->json([
                    'message' => 'Không tìm thông tin khách hàng',
                ], HttpResponse::HTTP_NOT_FOUND);
            }
            return response()->json([
                'message' => 'Không tìm thấy thông tin khách hàng',
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function  updateInforUser(Request $request,) {
        try {
            $user = Auth::user();


             $request->validate([
                'name'         => 'nullable|string|max:255',
                'phone_number' => [
                    'required',
                    'regex:/^0[0-9]{9}$/', // Chỉ chấp nhận số điện thoại gồm đúng 10 chữ số
                    Rule::unique('users')->ignore($user->id,'id'), // Bỏ qua ID hiện tại
                ],
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id,'id'), // Bỏ qua ID hiện tại
                ],
                'address'      => 'nullable|string|max:255',
                'avatar'       => 'nullable',
                'password'     => 'nullable|min:8',
                'birth_date'   => 'nullable|date',
                'is_active'    => 'boolean',
                'gender'       => 'nullable|boolean',
                'role_id'      => 'exists:roles,id',
            ]);


            $dataUser = [
            'name'         => $request->name ?? $user->name,
            'phone_number' => $request->phone_number ?? $user->phone_number,
            'email'        => $request->email ?? $user->email,
            'address'      => $request->address ?? $user->address,
            'password'     => $request->password ? bcrypt($request->password) : $user->password,
            'birth_date'   => $request->birth_date,
            'is_active'    => $request->is_active ?? $user->is_active,
            'gender'       => $request->gender,
            'role_id'      => $request->role_id ?? $user->role_id,
            'avatar'         => $request->avatar ?? $user->avatar //Kiểm tra avatar
            ];
// dd($user);
            $user->update($dataUser);

            return response()->json([
                'message'  => 'Cập nhật thông tin người dùng thành công :vv',
                'dataUser' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'Update failed.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
