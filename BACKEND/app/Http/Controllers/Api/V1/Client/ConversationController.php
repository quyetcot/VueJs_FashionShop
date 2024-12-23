<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        try {
            $user = $request->user();
            $conversations = $user->conversations()->with(['users'])->get();
            // $membership=User::query()->where('role_id',2)->where("id","<>",$user->id)->get();

            return response()->json([
                "message" => "Lấy dữ liệu thành công",
                "conversations" => $conversations,
                // "membership"=>$membership
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                "message" => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        try {
            return DB::transaction(function () use ($request) {
                $request->validate([
                    'recipient_id' => 'required|exists:users,id',
                ]);


                $user = $request->user();
                $recipient_id = $request->recipient_id;

                // Kiểm tra xem đã có cuộc trò chuyện giữa hai người chưa
                $conversation = Conversation::whereHas('users', function ($q) use ($user) {
                    $q->where('id', $user->id);
                })->whereHas('users', function ($q) use ($recipient_id) {
                    $q->where('id', $recipient_id);
                })->first();

                if (!$conversation) {
                    // Tạo cuộc trò chuyện mới
                    $conversation = Conversation::create();
                    $conversation->users()->attach([$user->id, $recipient_id]);
                }

                return response()->json([
                    "message" => "tạo cuộc trò chuyện thành công",
                    "data" => $conversation
                ], Response::HTTP_OK);
            });
        } catch (\Exception $ex) {
            return response()->json([
                "message" => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
        //
    }
    // public function 
}
