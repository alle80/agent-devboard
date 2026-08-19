<?php

namespace Alle80\Devboard\Tests\Support;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasPushSubscriptions, Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}
