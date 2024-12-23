<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comments;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CommentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $comments = Comments::with(['user:id,name,avatar', 'childrenRecursive.user:id,name,avatar'])
            ->whereNull('parent_id')
            ->latest('created_at')
            ->get();
        return response()->json($comments, Response::HTTP_OK);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Tìm bình luận theo ID
            $comment = Comments::findOrFail($id);

            // Nếu bình luận có hình ảnh liên quan, xóa hình ảnh
            if ($comment->image && Storage::disk('public')->exists($comment->image)) {
                Storage::disk('public')->delete($comment->image);
            }

            // Xóa bình luận
            $comment->delete();

            return response()->json([
                'status' => true,
                'message' => "Xóa bình luận thành công."
            ], 200);
        } catch (\Exception $ex) {
            Log::error('API/V1/Admin/CommentsController@destroy: ', [$ex->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Đã có lỗi xảy ra trong quá trình xóa bình luận.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function search(Request $request)
    {
        $query = $request->input('query'); // Lấy từ khóa tìm kiếm từ body request

        if (empty($query)) {
            $results = Comments::all();
            return response()->json([
                'message' => 'Tất cả comment:',
                'data' => $results
            ]);
        }

        // Tìm kiếm trong cột `name` hoặc các cột khác nếu cần
        $results = Comments::where('content', 'LIKE', "%{$query}%")
            ->orWhere('user_id', 'LIKE', "%{$query}%")
            ->orWhere('product_id', 'LIKE', "%{$query}%") // Thêm cột mô tả nếu có
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'Không tìm thấy bình luận.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'message' => 'Kết quả tìm kiếm.',
            'data' => $results,
        ]);
    }
}
