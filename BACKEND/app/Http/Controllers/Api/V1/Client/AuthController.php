<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;

use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Notifications\ResetPasswordNotification; // Giả định bạn có thông báo này
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function login(Request $request)
    {

        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'Vui lòng nhập địa chỉ email.',
                'email.email'    => 'Địa chỉ email không hợp lệ.',
                'password.required' => 'Vui lòng nhập mật khẩu.',
            ]);

            $user = User::where('email', request('email'))->first();

            if (
                !$user || !Hash::check(request('password'), $user->password)
            ) {
                throw ValidationException::withMessages([
                    'Thông tin tài khoản hoặc mật khẩu không đúng.',
                ]);
            }
            if ($user->role_id === 1) {
                // Kiểm tra xem email đã được xác thực chưa
                if (!$user->hasVerifiedEmail()) {
                    // Gửi lại email xác thực
                    event(new Registered($user));
                    return response()->json([
                        'message' => 'Vui lòng xác thực tài khoản của bạn.  Email xác thực tài khoản đã được gửi đến địa chỉ email của bạn!!'
                    ], 403); // Forbidden
                }
            }

            $token = $user->createToken($user->id)->plainTextToken;

            return response()->json([
                'message' => 'Đăng nhập thành công',
                'token' => $token,
                'data' => $user
            ]);
        } catch (\Throwable $th) {
            if ($th instanceof ValidationException) {
                return response()->json([
                    'errors' => $th->errors()
                ], Response::HTTP_BAD_REQUEST);
            }
            return response()->json([
                'errors' => $th->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function register(UserRequest $request)
    {
        try {
            $dataUser = [
                'name'         => $request->name,
                'phone_number' => $request->phone_number,
                'email'        => $request->email,
                'address'      => $request->address,
                'password'     => bcrypt($request->password),
                'birth_date'   => $request->birth_date,
                'is_active'    => $request->is_active ?? 1,
                'gender'       => $request->gender,
                'role_id'      => $request->role_id ?? 1,
                'avatar'       => $request->avatar
            ];

            $user = User::query()->create($dataUser);
            // $token = $user->createToken($user->id)->plainTextToken;
            event(new Registered($user));

            return response()->json([
                'message' => 'Đăng ký tài khoản thành công, Vui lòng check mail đẻ xác nhận',
                'email'   => $user->email
                // 'token' => $token
            ]);
        } catch (\Throwable $th) {
            if ($th instanceof ValidationException) {
                return response()->json([
                    'errors' => $th->errors()
                ], Response::HTTP_BAD_REQUEST);
            }
            return response()->json([
                'errors' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyEmail($id, $hash)
    {

        try {
            // Tìm người dùng theo ID
            $user = User::find($id);

            // Kiểm tra xem người dùng có tồn tại không
            if (!$user) {
                return response()->json([
                    'message' => 'User not found.'
                ], 404);
            }

            // Kiểm tra hash từ email có khớp với hash được tạo từ địa chỉ email không
            if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
                return response()->json([
                    'message' => 'Invalid verification link.'
                ], 400);
            }

            // Kiểm tra xem email đã được xác thực chưa
            if ($user->hasVerifiedEmail()) {

                return response()->json([
                    'message' => 'Email already verified.'
                ], 400);
                // ->redirect("http://localhost:5173/")


            }

            // Xác thực email
            $user->markEmailAsVerified();
            // Gửi sự kiện đã xác thực email
            event(new Verified($user));
            return redirect("http://localhost:5173/login");
            // return response()->json([
            //     'message' => 'Email has been verified successfully.',
            // ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'errors' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR)->redirect("http://localhost:5173/login");
        }
    }

    public function resendVerifyEmail(Request $request)
    {
        try {

            $email = $request->email;

            if (!$email) {
                return response()->json(['message' => 'Email is required'], 400);
            }
            // Tìm người dùng theo email
            $user = User::where('email', $request->email)->first();

            // Kiểm tra xem người dùng có tồn tại không
            if (!$user) {
                return response()->json([
                    'message' => 'User not found.'
                ], 404); // Not Found
            }

            // Kiểm tra xem email đã được xác thực chưa
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'message' => 'Email already verified'
                ], 200);
            }

            // Gửi lại email xác thực
            // $user->sendEmailVerificationNotification();

            event(new Registered($user));

            return response()->json([
                'message' => 'Verification email resent successfully. Please check your inbox.'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'errors' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sendResetPassWordMail(Request $request)
    {

        try {
            // Xác thực input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Tìm người dùng theo email
            $user = User::where('email', $request->email)->first();

            // Tạo token reset mật khẩu
            $token = Password::createToken($user);

            // $url = route('password.reset', ['token' => str($token)]);
            $url = "http://localhost:5173/password/reset/" . $token;

            // Gửi thông báo qua queue
            Notification::send($user, new ResetPasswordNotification($url, $token));

            return response()->json([
                'message' => 'Email đặt lại mật khẩu đã được gửi thành công. Vui lòng kiểm tra email !',
                'token'   => $token,
                'url'     => $url
            ], 200);
        } catch (\Exception $e) {
            // Handle error when sending email
            return response()->json([
                'message' => 'Gửi email không thành công.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showResetForm($token)
    {

        try {
            // Kiểm tra token, nếu token không hợp lệ, có thể ném ra exception
            if (!$token) {
                throw new Exception('Token không hợp lệ.');
            }

            // Trả về response nếu không có lỗi
            return response()->json([
                'message' => 'Chuyển trang đặt lại mật khẩu.',
                'token' => $token
            ], 200);
        } catch (Exception $e) {
            // Bắt lỗi và trả về response
            return response()->json([
                'error' => 'Đã xảy ra lỗi.',
                'message' => $e->getMessage()
            ], 400); // 400 Bad Request
        }
    }

    public function reset(Request $request)
    {
        try {
            // 1. Validate input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'token' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);

            // 2. Xử lý nếu validate thất bại
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422); // 422 Unprocessable Entity
            }

            // 3. Gọi đến chức năng reset mật khẩu của Laravel
            $response = Password::reset(
                $request->only('email', 'password', 'token'),
                function ($user, $password) {
                    // Cập nhật mật khẩu mới
                    $user->password = bcrypt($password);
                    $user->save();
                }
            );

            // 4. Xử lý kết quả trả về từ reset mật khẩu
            if ($response == Password::PASSWORD_RESET) {
                return response()->json([
                    'message' => 'Mật khẩu đã được đặt lại thành công.'
                ], 200); // 200 OK
            } else {
                return response()->json([
                    'message' => 'Không thể đặt lại mật khẩu. Vui lòng kiểm tra lại thông tin.',
                    'response_code' => $response
                ], 500); // 500 Internal Server Error
            }
        } catch (\Exception $e) {
            // Bắt lỗi và trả về response chi tiết
            return response()->json([
                'error' => 'Đã xảy ra lỗi.',
                'message' => $e->getMessage()
            ], 500); // 500 Internal Server Error
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
