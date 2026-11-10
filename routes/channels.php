<?php

use App\Models\Complex;
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

// Laravel matches this name without the private- prefix used by the Pusher client.
// The same active membership policy governs chat access over HTTP and the channel.
Broadcast::channel('group.{complexId}', function ($user, $complexId) {
    $complex = Complex::find($complexId);

    return $complex && $user->can('view', $complex);
});
