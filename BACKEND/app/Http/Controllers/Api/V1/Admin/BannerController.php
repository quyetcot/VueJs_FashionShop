<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Banner\StoreBannerRequest;
use App\Http\Requests\Banner\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $this->updateBannerStatus(); // Cập nhật trạng thái của banner trước khi truy xuất dữ liệu

            $banners = Banner::query()->latest('id')->get();

            if ($banners->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không có dữ liệu.',
                    'data' => []
                ], Response::HTTP_OK);
            }

            return response()->json([
                'status' => true,
                'message' => 'Danh sách banners được lấy thành công.',
                'data' => [
                    'total' => $banners->count(),
                    'banners' => $banners
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            Log::error('API/V1/Admin/BannerController@index: ', [$ex->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Đã có lỗi nghiêm trọng xảy ra. Vui lòng thử lại sau.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBannerRequest $request)
    {
        try {
            $params = $request->all();

            if (!$this->isValidDates($request->input('start_date'), $request->input('end_date'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc, và ngày kết thúc không được nằm trong quá khứ.'
                ], 400);
            }

            $params['status'] = Carbon::parse($params['end_date'])->greaterThanOrEqualTo(Carbon::now());
            //  dd($params['status']);
            if ($request->has('image')) {
                $params['image'] = $request->input('image');
            }

            $banner = Banner::create($params);
            return response()->json([
                'data' => new BannerResource($banner),
                'success' => true,
                'message' => 'Banner đã được thêm thành công'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thêm Banner thất bại!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $banner = Banner::query()->findOrFail($id);
        return new BannerResource($banner);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBannerRequest $request, string $id)
    {
        try {
            $banner = Banner::findOrFail($id);

            if (!$this->isValidDates($request->input('start_date'), $request->input('end_date'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc, và ngày kết thúc không được nằm trong quá khứ.'
                ], 400);
            }

            if ($request->has('image') && !empty($request->input('image'))) {
                $banner->image = $request->input('image');
            }

            $banner->title = $request->input('title');
            $banner->link = $request->input('link');
            $banner->start_date = $request->input('start_date');
            $banner->end_date = $request->input('end_date');
            $banner->status = Carbon::parse($request->input('end_date'))->greaterThanOrEqualTo(Carbon::now());

            $banner->save();

            return response()->json([
                'data' => new BannerResource($banner),
                'success' => true,
                'message' => 'Banner đã được sửa thành công'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Banner không tồn tại!'
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật Banner thất bại!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $banner = Banner::query()->findOrFail($id);
            $banner->delete();
            return response()->json([
                'status' => true,
                'message' => "Xóa banner thành công."
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Banner không tồn tại!'
            ], 404);
        }
    }

    private function updateBannerStatus()
    {
        $currentDate = Carbon::now();

        // Đặt trạng thái của các banner chưa đến ngày bắt đầu
        Banner::query()
            ->where('start_date', '>', $currentDate)
            ->update(['status' => false]);

        // Đặt trạng thái của các banner đang hoạt động
        Banner::query()
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->update(['status' => true]);

        // Đặt trạng thái của các banner đã hết hạn
        Banner::query()
            ->where('end_date', '<', $currentDate)
            ->update(['status' => false]);
    }
    private function isValidDates($start_date, $end_date)
    {
        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);

        return $start->lessThanOrEqualTo($end) && $end->greaterThanOrEqualTo(Carbon::now());
    }

    public function checkBannerValidity(Request $request)
    {
        $currentDate = Carbon::now(); // Lấy ngày hiện tại
        $banners = Banner::query()
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->get(); // Lấy tất cả các banners đang hoạt động

        if ($banners->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Không có banner đang hoạt động.',
                'data' => []
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => true,
            'message' => 'Các banners đang hoạt động trong khoảng thời gian hợp lệ.',
            'data' => $banners
        ], Response::HTTP_OK);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            $results = Banner::all();
            return response()->json(['message' => 'Hiển thị tất cả banner.', 'data' => $results]);
        }

        // Tìm kiếm trong cột `name` hoặc các cột khác nếu cần
        $results = Banner::where('title', 'LIKE', "%{$query}%")
            ->orWhere('id', 'LIKE', "%{$query}%") // Thêm cột mô tả nếu có
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'Không tìm thấy banner.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'message' => 'Kết quả tìm kiếm.',
            'data' => $results,
        ]);
    }
}
