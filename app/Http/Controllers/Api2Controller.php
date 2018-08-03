<?php

namespace App\Http\Controllers;

use App\Models\Shops;
use Illuminate\Http\Request;

class Api2Controller extends Controller
{
    //
    public function getshops()
    {
      $shops=Shops::all();
//      foreach ($shops as &$list){
////      unset($list['created_at'],$list['updated_at']);
////      $list['id']=$shops['id'];
////      $list['shop_name']=$shops['shop_name'];
////      $list['shop_rating']=$shops['shop_rating'];
////      $list['brand']=$shops['brand'];
////     $list['on_time']=$shops['on_time'];
////      $list['bao']=$shops['bao'];
////      $list['fengniao']=$shops['fengniao'];
////      $list['zhun']=$shops['zhun'];
////      $list['start_send']=$shops['start_send'];
////     $list['send_cost']=$shops['send_cost'];
////       $list['notice']=$shops['notice'];
//      $list['estimate_time']=mt_rand(0,500);
//      $list['distance']=mt_rand(0,200);
//      }
        return  json_encode($shops);
    }

}
