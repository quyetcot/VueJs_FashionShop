<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Lấy user từ Bearer Token
        $user = auth('sanctum')->user(); // Nếu dùng Sanctum để lấy user từ token

        // Kiểm tra nếu user chưa đăng nhập
        if (!$user) {
            return response()->json(['message' => 'Not logged in'], 401);
        }

        // Kiểm tra vai trò của user, so sánh với id các role cho phép
        if (!in_array($user->role->id, $roles)) {
            return response()->json(['message' => 'Tài khoản không có quyền'], 403);
        }

        // Nếu hợp lệ, tiếp tục xử lý request
        return $next($request);
    }
}
