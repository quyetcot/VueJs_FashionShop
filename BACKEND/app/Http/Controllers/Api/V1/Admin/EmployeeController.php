<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = User::whereIn('role_id', [2, 3])->with('role')
            ->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $employees
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest  $request)
    {

        try {

            $dataEmployee = [
                'name'        => $request->name,
                'phone_number' => $request->phone_number,
                'email'       => $request->email,
                'address'     => $request->address,
                'password'    => bcrypt($request->password),
                'birth_date'  => $request->birth_date,
                'is_active'   => $request->is_active ?? 1,
                'gender'      => $request->gender,
                'role_id'     => $request->role_id,
                'avatar'      => $request->avatar
            ];


            // if ($request->hasFile('avatar')) {
            //     // Lưu avatar vào thư mục avatars
            //     $avatarPath = Storage::put('public/avatars', $request->file('avatar'));
            //     // Tạo URL đầy đủ cho avatar

            //     $dataEmployee['avatar'] = url(Storage::url($avatarPath));

            // }

            $employee = User::query()->create($dataEmployee);

            return response()->json([
                'status'  => 201,
                'success' => true,
                'message' => 'Employee created successfully!',
                'data'    => $employee
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
            $employee = User::with('role')->findOrFail($id);
            return response()->json([
                'massage' => 'Chi tiết nhân viên id = ' . $id,
                'data'    => $employee
            ]);
        } catch (\Throwable $th) {
            if ($th instanceof ModelNotFoundException) {
                return response()->json([
                    'massage' => 'Không tìm thấy nhân viên có id=' . $id,
                ], HttpResponse::HTTP_NOT_FOUND);
            }
            return response()->json([
                'massage' => 'Không tìm thấy nhân viên có id=' . $id,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {
        try {
            $employee = User::findOrFail($id);

            $dataEmployee = [
                'name'         => $request->name,
                'phone_number' => $request->phone_number,
                'email'        => $request->email,
                'address'      => $request->address,
                'password'     => $request->password ? bcrypt($request->password) : $employee->password,
                'birth_date'   => $request->birth_date,
                'is_active'    => $request->is_active ?? $employee->is_active,
                'gender'       => $request->gender,
                'role_id'      => $request->role_id,
                'avatar'       => $request->avatar ?? $employee->avatar //kiểm tra avatar
            ];


            $employee->update($dataEmployee);

            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => 'Employee updated successfully!',
                'data'    => $employee
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
        $employee = User::find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        // Kiểm tra và xóa ảnh nếu tồn tại
        // if ($employee->avatar) {
        //     Storage::delete('public/avatars/' . basename($employee->avatar));
        // }

        // Xóa bản ghi nhân viên
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully',
        ], 200);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) {
            $results = User::whereIn('role_id', [2, 3])->get();
            // $results = User::all();
            return response()->json(['message' => 'Hiển thị tất cả nhân viên.', 'data' => $results]);
        }

        // Tìm kiếm trong cột `name` và `email`
        $results = User::whereIn('role_id', [2, 3])->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('phone_number', 'LIKE', "%{$query}%")
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
