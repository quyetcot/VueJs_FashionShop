<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $clients = User::whereIn('role_id', [1])->with('role')
            ->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $clients
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        try {


            $dataClient = [
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


            $client = User::query()->create($dataClient);

            return response()->json([
                'status'  => 201,
                'success' => true,
                'message' => 'Client created successfully!',
                'data'    => $client
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'Không thành công.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $client = User::with('role')->findOrFail($id);
            return response()->json([
                'message' => 'Chi tiết khách hàng id = ' . $id,
                'data'    => $client
            ]);
        } catch (\Throwable $th) {
            if ($th instanceof ModelNotFoundException) {
                return response()->json([
                    'message' => 'Không tìm thấy khách hàng có id=' . $id,
                ], HttpResponse::HTTP_NOT_FOUND);
            }
            return response()->json([
                'message' => 'Không tìm thấy khách hàng có id=' . $id,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {
        try {
            $client = User::findOrFail($id);

            // Tạo mảng dữ liệu để cập nhật
            $dataClient = [
                'name'         => $request->name,
                'phone_number' => $request->phone_number,
                'email'        => $request->email,
                'address'      => $request->address,
                'password'     => $request->password ? bcrypt($request->password) : $client->password,
                'birth_date'   => $request->birth_date,
                'is_active'    => $request->is_active ?? $client->is_active,
                'gender'       => $request->gender,
                'role_id'      => $request->role_id ?? $client->role_id,
                'avatar'       => $request->avatar ?? $client->avatar //Kiểm tra avatar
            ];

            $client->update($dataClient);

            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => 'Client updated successfully!',
                'data'    => $client
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'Update failed.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = User::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found',
            ], 404);
        }

        // Xóa bản ghi nhân viên
        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully',
        ], 200);
    }


    public function search(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) {
            $results = User::where('role_id', 1)->get();
            return response()->json(['message' => 'Hiển thị tất cả người dùng.', 'data' => $results]);
        }


        $results = User::where('role_id', 1)
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'Không tìm thấy người dùng.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'message' => 'Kết quả tìm kiếm.',
            'data' => $results,
        ]);
    }
}
