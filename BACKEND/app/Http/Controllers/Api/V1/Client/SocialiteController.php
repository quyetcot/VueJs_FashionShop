<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SocialiteController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        // Kiểm tra nếu người dùng đã có trong hệ thống
        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'role_id' => 1,  // Ví dụ gán role_id là 1
                'password' => bcrypt(Str::random(16)),  // Tạo mật khẩu ngẫu nhiên
                'phone_number' => '0' . rand(100000000, 999999999),
                'address' => 'none',
                'email_verified_at' => Carbon::now()
            ]
        );

        // Đăng nhập người dùng
        Auth::login($user);

        // Tạo token
        $token = $user->createToken($user->id)->plainTextToken;
        // $frontendUrl = "http://localhost:5173/";
        $frontendUrl = "http://localhost:5173/?token=$token";

        // Chuyển hướng người dùng
        return redirect($frontendUrl);


        // return Response::json([
        //     'user' => $user,
        //     'token' => $token
        // ]);
    }

    // public function redirectToFacebook()
    // {
    //     return Socialite::driver('facebook')->redirect();
    // }

    // public function handleFacebookCallback(Request $request)
    // {
    //     $facebookUser = Socialite::driver('facebook')->user();

    //     // Kiểm tra hoặc tạo người dùng từ dữ liệu Facebook
    //     $user = User::firstOrCreate(
    //         ['email' => $facebookUser->getEmail()],
    //         [
    //             'name' => $facebookUser->getName(),
    //             'avatar' => $facebookUser->getAvatar()
    //         ]
    //     );

    //     Auth::login($user);

    //     // Tạo token
    //     $token = $user->createToken('YourAppName')->accessToken;

    //     //Trả về dữ liệu JSON cho Frontend (React)
    //         return Response::json([
    //             'user' => $user,
    //             'token' => $token
    //         ]);
    // }
}
