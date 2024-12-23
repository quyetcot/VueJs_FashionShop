<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use HTMLPurifier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostController extends Controller
{
    /**
     * Bảo vệ tất cả các phương thức bằng middleware auth.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $posts = Post::latest('id')->get();

            return response()->json([
                'data' => $posts
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Đã có lỗi nghiêm trọng xảy ra.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        try {
            $data = $request->all();
            $data['slug'] = $this->generateUniqueSlug($data['post_name']);
            $data['user_id'] = Auth::id();
            $purifier = new HTMLPurifier();
            $data['post_content'] = $purifier->purify($data['post_content']);
            if ($request->has('img_thumbnail')) {
                $data['img_thumbnail'] = $request->img_thumbnail;
            }
            $post = Post::create($data);

            return response()->json([
                'message' => 'Thêm bài viết thành công!',
                'data' => $post
            ], Response::HTTP_CREATED);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Thêm bài viết thất bại!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $post = Post::findOrFail($id);

            return response()->json([
                'message' => 'Lấy bài viết thành công!',
                'data' => $post
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(UpdatePostRequest $request, $id)
    // {
    //     try {
    //         $post = Post::findOrFail($id);
    //         $data = $request->all();
    //         if ($data['post_name'] !== $post->post_name) {
    //             $data['slug'] = $this->generateUniqueSlug($data['post_name'], $id);
    //         } else {
    //             $data['slug'] = $post->slug;
    //         }
    //         $purifier = new HTMLPurifier();
    //         $data['post_content'] = $purifier->purify($data['post_content']);
    //         if ($request->has('img_thumbnail')) {
    //             $data['img_thumbnail'] = $request->img_thumbnail;
    //         } else {
    //             $data['img_thumbnail'] = $post->img_thumbnail;
    //         }
    //         $post->update($data);

    //         return response()->json([
    //             'message' => 'Cập nhật bài viết thành công!',
    //             'data' => $post
    //         ], Response::HTTP_OK);

    //     } catch (QueryException $e) {
    //         return response()->json([
    //             'message' => 'Cập nhật bài viết thất bại!',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function update(UpdatePostRequest $request, $id)
    {
        try {
            $post = Post::findOrFail($id);
            $data = $request->all();
            if ($data['post_name'] !== $post->post_name) {
                $data['slug'] = $this->generateUniqueSlug($data['post_name'], $id);
            } else {
                $data['slug'] = $post->slug;
            }
            $purifier = new HTMLPurifier();
            $data['post_content'] = $purifier->purify($data['post_content']);
            if ($request->has('img_thumbnail') && !empty($request->img_thumbnail)) {
                $data['img_thumbnail'] = $request->img_thumbnail;
            } else {
                $data['img_thumbnail'] = $post->img_thumbnail;
            }

            $post->update($data);

            return response()->json([
                'message' => 'Cập nhật bài viết thành công!',
                'data' => $post
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Bài viết không tồn tại!',
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Cập nhật bài viết thất bại!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $post = Post::findOrFail($id);
            $post->delete();

            return response()->json([
                'message' => 'Xóa bài viết thành công!'
            ], Response::HTTP_OK);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Xóa bài viết thất bại!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a unique slug for the post.
     */
    private function generateUniqueSlug($value, $id = null)
    {
        $slug = Str::slug($value, '-');
        $original_slug = $slug;
        $count = 1;
        while (Post::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $original_slug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    public function getPostsGroupedByCategory(Request $request)
    {
        try {
            $fields = $request->query('fields', 'id_post_name_img_user_date');

            // 1. Lấy tất cả các danh mục và bài viết thuộc danh mục
            $categories = Category::with('posts.user')->get(); // Lấy cả thông tin người đăng qua mối quan hệ 'user'

            // 2. Chuẩn bị mảng để lưu bài viết phân theo danh mục
            $data = [];

            // 3. Lặp qua các danh mục và nhóm bài viết
            foreach ($categories as $category) {
                // Kiểm tra nếu danh mục có bài viết
                if ($category->posts->count() > 0) {
                    if ($fields === 'id_post_name_img_user_date') {
                        // Trả về id, post_name, img_thumbnail, tên người đăng và ngày đăng
                        $data[$category->name] = $category->posts->map(function ($post) {
                            return [
                                'id' => $post->id,
                                'post_name' => $post->post_name,
                                'img_thumbnail' => $post->img_thumbnail,
                                'user_name' => $post->user->name, // Lấy tên người đăng từ quan hệ user
                                'created_at' => $post->created_at->format('Y-m-d'), // Định dạng ngày đăng
                                'slug' => $post->slug,
                            ];
                        });
                    } else {
                        $data[$category->name] = $category->posts;
                    }
                }
            }

            $uncategorizedPosts = Post::whereNull('category_id')->with('user')->get();

            if ($uncategorizedPosts->count() > 0) {
                if ($fields === 'id_post_name_img_user_date') {
                    $data['Không có danh mục'] = $uncategorizedPosts->map(function ($post) {
                        return [
                            'id' => $post->id,
                            'post_name' => $post->post_name,
                            'img_thumbnail' => $post->img_thumbnail,
                            'user_name' => $post->user->name,
                            'created_at' => $post->created_at->format('Y-m-d'),
                            'slug' => $post->slug,
                        ];
                    });
                } else {
                    $data['Không có danh mục'] = $uncategorizedPosts;
                }
            }
            return response()->json([
                'status' => true,
                'message' => 'Danh sách bài viết theo danh mục và không có danh mục',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể lấy danh sách bài viết',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function search(Request $request)
    {
        $query = $request->input('query'); // Lấy từ khóa tìm kiếm từ body request
        if (empty($query)) {
            $results = Post::all();
            return response()->json([
                'message' => 'Hiển thị tất cả',
                'data' => $results
            ]);
        }

        // Tìm kiếm trong cột `name` hoặc các cột khác nếu cần
        $results = Post::where('post_name', 'LIKE', "%{$query}%")
            ->orWhere('post_content', 'LIKE', "%{$query}%") // Thêm cột mô tả nếu có
            ->get();
        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'Không tìm thấy post.',
                'data' => []
            ], 404);
        }


        return response()->json([
            'message' => 'Kết quả tìm kiếm.',
            'data' => $results,
        ]);
    }
}
