<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\API\V1\Service\TryOnService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TryOnController extends Controller
{
    protected $tryOnService;

    public function __construct(TryOnService $tryOnService)
    {
        $this->tryOnService = $tryOnService;
        // dd($this->tryOnService);
    }

    public function tryOn(Request $request)
    {

        // Validate dữ liệu từ frontend
        $request->validate([
            'user_image_url' => 'required|url',  // Kiểm tra URL ảnh người dùng hợp lệ
            'outfit_image_url' => 'required|url' // Kiểm tra URL ảnh bộ đồ hợp lệ
        ]);

        // Lấy URL ảnh từ yêu cầu
        $userImageUrl = $request->input('user_image_url');
        $outfitImageUrl = $request->input('outfit_image_url');

        // Gọi service để thử đồ
        $result = $this->tryOnService->tryOnClothes($userImageUrl, $outfitImageUrl);

        // Trả kết quả về frontend
        return response()->json($result);
    }
}
