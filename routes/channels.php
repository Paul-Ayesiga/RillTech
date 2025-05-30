<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id && $user->hasRole('super-admin') ;
});


Broadcast::channel('newsletter-subscription', function (User $user) {
    return  $user->hasRole('super-admin') ;
});

Broadcast::channel('user-contacted', function (User $user) {
    return  $user->hasRole('super-admin') ;
});

