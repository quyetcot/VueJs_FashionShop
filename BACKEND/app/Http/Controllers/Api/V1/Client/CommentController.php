<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Comments;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    /**
     * Hiển thị danh sách bình luận.
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
     * Thêm bình luận mới.
     */
  
    
    
   
    public function store(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['message' => 'Bạn cần đăng nhập để có thể bình luận.'], Response::HTTP_UNAUTHORIZED);
    }
    $validated = $request->validate([
        'product_id' => 'required|exists:products,id',
        'content' => 'nullable|string|max:1000',
        'rating' => 'nullable|integer|min:1|max:5',
        'image' => 'nullable|string',
        'parent_id' => 'nullable|exists:comments,id',
        'order_id' => 'required_if:parent_id,null|exists:orders,id', // Chỉ bắt buộc khi là bình luận cha
    ]);

    $parentId = $validated['parent_id'] ?? null;

    // Nếu là bình luận cha, kiểm tra tính hợp lệ của order_id
    if (is_null($parentId)) {
        $order = Order::where('id', $validated['order_id'])
            ->where('user_id', Auth::id())
            ->where('order_status', 'Hoàn thành')
            ->whereHas('orderDetails', function ($query) use ($validated) {
                $query->where('product_id', $validated['product_id']);
            })
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không hợp lệ hoặc không chứa sản phẩm này.'], Response::HTTP_FORBIDDEN);
        }

        // Kiểm tra bình luận cha
        $existingComment = Comments::where('user_id', Auth::id())
            ->where('product_id', $validated['product_id'])
            ->where('order_id', $order->id) // Bình luận cha cho đơn hàng cụ thể
            ->whereNull('parent_id') // Chỉ kiểm tra bình luận cha
            ->exists();

        if ($existingComment) {
            return response()->json([
                'user_has_commented' => true,
                'message' => 'Bạn đã bình luận về sản phẩm này trong đơn hàng này.',
            ], Response::HTTP_FORBIDDEN);
        }
    }

    // Nếu là bình luận con, lấy thông tin từ bình luận cha
    if (!is_null($parentId)) {
        $parentComment = Comments::find($parentId);

        if (!$parentComment) {
            return response()->json(['message' => 'Bình luận cha không tồn tại.'], Response::HTTP_NOT_FOUND);
        }

        // Gán thông tin sản phẩm và đơn hàng từ bình luận cha
        $validated['product_id'] = $parentComment->product_id;
        $validated['order_id'] = $parentComment->order_id;
    }

    // Tạo bình luận mới
    $comment = Comments::create([
        'user_id' => Auth::id(),
        'product_id' => $validated['product_id'],
        'content' => $validated['content'],
        'rating' => $validated['rating'],
        'image' => $validated['image'],
        'parent_id' => $parentId,
        'status' => is_null($parentId), // Chỉ đặt status = true nếu là bình luận cha
        'order_id' => $validated['order_id'] ?? null, // Lưu order_id nếu có
    ]);

    return response()->json(['message' => 'Bình luận của bạn đã được gửi thành công.', 'comment' => $comment], Response::HTTP_CREATED);
}

    /**
     * Hiển thị một bình luận cụ thể.
     */
    public function show(string $id)
    {
        $comment = Comments::with('user', 'product')->findOrFail($id);
        return response()->json($comment, Response::HTTP_OK);
    }

    /**
     * Cập nhật bình luận.
     */
 
    public function update(Request $request, string $id)
{
    $comment = Comments::findOrFail($id);

    if ($comment->user_id !== Auth::id()) {
        return response()->json(['message' => 'Không có quyền chỉnh sửa'], Response::HTTP_FORBIDDEN);
    }
    $validated = $request->validate([
        'content' => 'nullable|string|max:1000',
        'rating' => 'nullable|integer|min:1|max:5',
        'image' => 'nullable|string',
    ]);
    if ($request->has('content')) {
        $comment->content = $validated['content'];
    }
    $comment->rating = $validated['rating'] ?? $comment->rating;

    if ($request->has('image')) {
        $comment->image = $validated['image'];
    }

    // Không cho phép thay đổi parent_id, order_id, hoặc product_id
    // Chỉ lưu các thay đổi hợp lệ
    $comment->save();

    return response()->json(['message' => 'Cập nhật bình luận thành công!', 'comment' => $comment], Response::HTTP_OK);
}

    

    /**
     * Xóa bình luận.
     */
    public function destroy(string $id)
    {
        $comment = Comments::findOrFail($id);
    
        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Không có quyền xóa bình luận'], Response::HTTP_FORBIDDEN);
        }
    
        $this->deleteChildComments($comment);
    
        $comment->delete();
    
        return response()->json(['message' => 'Xóa bình luận thành công!'], Response::HTTP_OK);
    }
    
    private function deleteChildComments($comment)
    {
        foreach ($comment->childrenRecursive as $child) {
            $this->deleteChildComments($child);
            $child->delete();
        }
    }
    
}
