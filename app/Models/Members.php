<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Members extends Authenticatable
{
    //
    protected $fillable=['username','password','tel'];
}
