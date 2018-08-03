<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ordersgoods extends Model
{
    //
    protected $fillable=['id','order_id','goods_id','amount','goods_name','goods_img','goods_price'];
}
