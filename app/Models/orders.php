<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class orders extends Model
{
    //
    protected $fillable=['id','user_id','shop_id','sn','province','city','country','address','tel','name','total','status','out_trade_no','created_at','order_id'];

    public function shop()
    {
        return $this->belongsTo(Shops::class,'shop_id','id');
    }
}
