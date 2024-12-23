<?php

use App\Models\Cart;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
// Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
//     return $user->conversations()->where('conversations.id', $conversationId)->exists();
// });
// Broadcast::channel('cart.{cartId}', function ($user, $cartId) {
   
//     return Cart::where('id', $cartId)->where('user_id', $user->id)->exists();
// });

// Broadcast::channel('orders', function ($user) {
//     return true;
// });


