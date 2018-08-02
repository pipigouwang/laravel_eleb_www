<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//商家接口列表
//Route::prefix('api')->group(function(){
//    Route::get('shops',function (){
//        return '[{
//        "id": "s10001",
//        "shop_name": "上沙麦当劳",
//        "shop_img": "http://www.homework.com/images/shop-logo.png",
//        "shop_rating": 4.7,
//        "brand": true,
//        "on_time": true,
//        "fengniao": true,
//        "bao": true,
//        "piao": true,
//        "zhun": true,
//        "start_send": 20,
//        "send_cost": 5,
//        "distance": 637,
//        "estimate_time": 30,
//        "notice": "新店开张，优惠大酬宾！",
//        "discount": "新用户有巨额优惠！"},
//      {
//        "id": "s10003",
//        "shop_name": "有家蛋糕店（下沙店）",
//        "shop_img": "http://www.homework.com/images/shop-logo.png",
//        "shop_rating": 4.4,
//        "brand": false,
//        "on_time": true,
//        "fengniao": false,
//        "bao": true,
//        "piao": false,
//        "zhun": true,
//        "start_send": 80,
//        "send_cost": 0,
//        "distance": 637,
//        "estimate_time": 30,
//        "notice": "新店开张，优惠大酬宾！",
//        "discount": "新用户有巨额优惠！"
//      }
//      ]
//        ';
//    });
//});
Route::prefix('api')->group(function(){
        Route::get('shops','ApiController@getshops');
        //指定商家接口
        Route::get('zhidingshop','ApiController@zhidingshop');
        //验证码短信
        Route::get('sms','ApiController@sms');
        //注册接口
        Route::post('regist','ApiController@regist');
        //登录接口
        Route::post('loginChecks','ApiController@loginCheck');
        //地址列表接口
        Route::get('addressList','ApiController@addressList');
        //指定地址接口
        Route::get('address','ApiController@address');
        //添加地址
        Route::post('addAddress','ApiController@addAddress');
       //修改地址
        Route::post('editAddress','ApiController@editAddress');
        //添加购物车
        Route::post('addCart','ApiController@addCart');
        //获取购物车
        Route::get('cart','ApiController@cart');
        //添加订单(生成订单)
        Route::post('addorder','ApiController@addorder');
        //订单详情
        Route::get('order','ApiController@order');
        //订单列表
        Route::get('orderList','ApiController@orderList');
    //修改密码
    Route::post('changePassword','ApiController@changePassword');
    //忘记密码
    Route::post('forgetPassword','ApiController@forgetPassword');
    });
