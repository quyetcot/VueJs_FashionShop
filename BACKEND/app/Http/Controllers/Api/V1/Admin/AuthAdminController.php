<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Response;

class AuthAdminController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Xác thực thông tin đầu vào
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required'
            ]);

            // Tìm người dùng dựa trên email
            $user = User::where('email', $request->input('email'))->first();

            // Kiểm tra thông tin người dùng
            if (
                !$user || !Hash::check($request->input('password'), $user->password)
            ) {
                throw ValidationException::withMessages([
                    'infor' => ['Thông tin tài khoản không đúng.'],
                ]);
            }

            if ($user->role_id == 1) {
                throw ValidationException::withMessages([
                    'role' => ['Tài khoản không có quyền admin.'],
                ]);
            }

            // Tạo token cho người dùng
            $token = $user->createToken($user->id)->plainTextToken;

            return response()->json([
                'token' => $token,
                'message' => 'Đăng nhập thành công'
            ]);
        } catch (\Throwable $th) {
            // Xử lý lỗi xác thực
            if ($th instanceof ValidationException) {
                return response()->json([
                    'errors' => $th->errors()
                ], Response::HTTP_BAD_REQUEST);
            }
            // Xử lý lỗi chung
            return response()->json([
                'errors' => $th->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function logout(Request $request)
    {

        try {

            $request->user()->tokens()->delete();

            return response()->json([
                'message' => 'Logout Sucessfully!!!!'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'errors' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
