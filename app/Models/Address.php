<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['id','user_id','city','province','country','address','tel','name','is_default'];
}
