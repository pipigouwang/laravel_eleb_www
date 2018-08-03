<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    //
    protected $fillable=['id','user_id','goods_id','amount'];
    public function menu()
    {
        return $this->belongsTo(Menuses::class,'goods_id','id');
    }
}
