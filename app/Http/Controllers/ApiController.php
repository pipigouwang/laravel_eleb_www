<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Members;
use App\Models\Menucategory;
use App\Models\Menuses;
use App\Models\orders;
use App\Models\Ordersgoods;
use App\Models\Oredergoods;
use App\Models\Shops;
use App\Models\Users;
use App\SignatureHelper;
use Illuminate\Support\Facades\Mail;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class ApiController extends Controller
{
    public function getshops()
    {
        $shops = Shops::all();
        foreach ($shops as $list) {
            $list['estimate_time'] = mt_rand(0, 60);
            $list['distance'] = mt_rand(300, 1000);
        }
        return json_encode($shops);
    }
    /*
    * 指定商家接口
    */
    public function zhidingshop(Request $request)
    {
        $shop_id = $request->id;
        $categories = Menucategory::where('shop_id', $shop_id)->first();
        $menucategories = MenuCategory::where('shop_id', $shop_id)->get();
//查询出店铺的菜品分类(如:小肥宅)
        foreach ($menucategories as &$menucategory) {
            $menus = Menuses::where('category_id', $menucategory->id)->get();//找到菜单
            foreach ($menus as &$menu) {
                $goods_id = $menu['id'];
                $menu['goods_id'] = $goods_id;
            }
            $menucategory['goods_list'] = $menus;//保存到接口中的菜单表里
        }
        $shop['commodity'] = $menucategories;
        return json_encode($shop);
    }
    /*
     * 短信发送
     */
    public function sms(Request $request)
    {
        $params = [];
        $code = mt_rand(10000, 50000);
        Redis::set('code', $code);//保存到redis里面
        Redis::expire('code', 120);//设置过期时间
//return Redis::get('code');
        $tel = request()->tel;
// *** 需用户填写部分 ***
// fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAIzkql1XCgLbtv";
        $accessKeySecret = "6zJCGyIDoxi3tFpsV8ZC6onxKUc3VZ";

// fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $tel;

// fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "彭永康";

// fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_140545048";

// fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = Array(
            "code" => $code,
//"product" => "阿里通信"
        );

// fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

// fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";

// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();
// 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
// fixme 选填: 启用https
// ,true
        );
//dd($content);
        return $content;
    }
    /*
     * 登录
     */
    public function loginCheck(Request $request)
    {
//dd($request->username);
//        $this->validate($request,[
//            'username'=>'required',
//            'password'=>'required'
//        ],[
//            'name.required'=>'用户名必填',
//            'password.required'=>'密码必填'
//        ]);
//验证
//     $validator=Validator::make($request->all(),[
//         'username'=>'required',
//         'password'=>'required',
//     ],[
//         'username.required'=>'姓名必填',
//         'password.required'=>'密码必填',
//     ]);
//     if($validator->fails()){
//         return[
//            "status"=>"false",
//             "message"=>$validator->errors()->first()
//         ];
//     }
        $member = Members::where('username',$request->name)->first();
        if($member->status==0){
                     return[
            "status"=>"false",
             "message"=>"登录失败,您的账号为审核通过"
         ];
        }
        if (Auth::attempt([
            'username' => $request->name,
            'password' => $request->password,])
        ) {
            $msg = [
                "status" => "true",
                "message" => "登录成功",
                "user_id" => auth()->user()->id,
                "username" => auth()->user()->username
            ];
            return json_encode($msg);
        } else {
            $msg = [
                "status" => "false",
                "message" => "登录失败,用户名或者密码出错",
            ];
            return json_encode($msg);
        }
    }
    /*
    * 注册号码
    */
    public function regist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'unique:mmbers',
            'password' => 'required',
            'tel' => 'required'
        ], [
            'username.unique' => '管理员已存在',
            'password.required' => '密码必填',
            'tel.requird' => '号码必填'
        ]);
//        $code = Redis::get('code');
//        if ($code !== $request->sms) {
//            return ' {
//"status": "false",
//"message": "验证码错误"
//}';
//        }
        Members::create([
            'username' => $request->username,
            'tel' => $request->tel,
            'password' => bcrypt($request->password)
        ]);
        return ' {
"status": "true",
"message": "注册成功"
}';
    }
    /*
    * 收货地址详情
    */
    public function addressList()
    {
        $adds = Address::where('user_id', Auth::id())->get();
        foreach ($adds as $add) {//循环遍历拿出下级数据
            $add['provence'] = $add['province'];
            $add['area'] = $add['country'];
            $add['detail_address'] = $add['address'];

        }
        return json_encode($adds);
    }
    /*
    * 添加收货地址
    */
    public function addAddress(Request $request)
    {
        Address::create([
            'user_id' => auth()->user()->id,
            'province' => $request->provence,
            'city' => $request->city,
            'country' => $request->area,
            'address' => $request->detail_address,
            'tel' => $request->tel,
            'name' => $request->name,
            'is_defalut' => $request->is_defalut
        ]);
        return '{
"status": "true",
"message": "添加成功"
}';
    }
    /*
    * 修改收货地址
    */
    public function editAddress(Request $request)
    {
        $add = Address::where('id', $request->id)->first();
        $date = [
            'province' => $request->provence,
            'city' => $request->city,
            'country' => $request->area,
            'address' => $request->detail_address,
            'tel' => $request->tel,
            'name' => $request->name,
            'is_defalut' => $request->is_defalut
        ];
        $add->update($date);
        return '{
"status": "true",
"message": "修改成功"
}';
    }
    /*
    *获取指定的收货地址
    */
    public function address(Request $request)
    {
// $id=$request->id;
        $add = Address::where('id', $request->id)->first();
        $add['provence'] = $add['province'];
        $add['area'] = $add['country'];
        $add['detail_address'] = $add['address'];
        return json_encode($add);
    }
    /*
    * 添加购物车
    */
    public function addCart(Request $request)
    {
        $length = count($request->goodsList);//计算出长度然后进行循环
        $data = [];
        for ($i = 0; $i < $length; $i++) {
            $data['goods_id'] = $request->goodsList[$i];
            $data['amount'] = $request->goodsCount[$i];
            Cart::create([
                'user_id' => auth()->user()->id,
                'goods_id' => $data['goods_id'],
                'amount' => $data['amount']
            ]);
        }
        return '{
"status":"true",
"message":"添加成功"
}';
    }
    /*
    * 获取购物车
    */
    public function Cart()
    {
        $ShoppingCart = Cart::where('user_id', Auth::id())->get();
        $cart = [];
        $cart['totalCost'] = 0;
        $goods_list = [];
        $cart['goods_list'] = [];
        foreach ($ShoppingCart as $v) {
            $goods_list['goods_id'] = $v['goods_id'];
            $goods_list['amount'] = $v['amount'];
            $goods = Menuses::find($v['goods_id']);
            $goods_list['goods_price'] = $goods['goods_price'];
            $goods_list['goods_name'] = $goods['goods_name'];
            $goods_list['goods_img'] = $goods['goods_img'];
            $cart['totalCost'] += $goods_list['amount'] * $goods_list['goods_price'];
            $cart['goods_list'][] = $goods_list;
        }
        return json_encode($cart);
    }
//生成订单
    public function addorder(Request $request)
    {

        $carts = Cart::where('user_id', Auth::user()->id)->get();
        $car = Cart::where('user_id', Auth::id())->first();
        $menu = Menuses::where('id', $car->goods_id)->first();
//定义总价格
        $total = 0;
        $shop_id = '';
        foreach ($carts as $cart) {
            $cart->goods_id;
            $menu = Menuses::where('id', $cart->goods_id)->first();
            $shop_id = $menu->shop_id;
            $total += $cart->amount * $menu->goods_price;
        }
        $validator =Validator::make($request->all(), [
            "address_id" => 'required',
        ],[
            "address_id.required" => '地址必填',
        ]);
          if ($validator->fails()) {
              return [
                  'status' => 'false',
                  'message' => $validator->errors()->first()
              ];
          };
        $address = Address::where('id', $request->address_id)->where('user_id', Auth::id())->first();
        if ($address == null) {
            return '{
"status":"false",
"message":"没有地址"
}';
        }
//        DB::transaction(function()use($request){
        $orders = orders::create([
            'user_id' => auth()->user()->id,
            'shop_id' => $shop_id,
            'province' => $address->province,
            'city' => $address->city,
            'country' => $address->country,
            'address' => $address->address,
            'tel' => $address->tel,
            'name' => $address->name,
            'total' => $total,
            'status' => 0,
            'sn' => date("YmdHi", time()) . mt_rand(1000, 20000),
            'out_trade_no' => mt_rand(1000, 20000),
        ]);
        $orders->update([
            'total' => $total
        ]);
        $goods = Cart::where('user_id', Auth::user()->id)->get();
        foreach ($goods as $good) {
            $menu = Menuses::where('id', $good->goods_id)->first();
// var_dump($menu);die;
            Ordersgoods::create([
                'order_id' => $orders->id,
                'goods_id' => $good->goods_id,
                'amount' => $good->amount,
                'goods_name' => $menu->goods_name,
                'goods_img' => $menu->goods_img,
                'goods_price' => $menu->goods_price
            ]);
        }
        //dd($shop_id);
        $shop = Shops::where('id',$shop_id)->first();
        $shopname = $shop->shop_name;
    // dd($shopname);
        //$tel = $address->tel;
        $tel = $address->tel;
        //dd($tel);
// *** 需用户填写部分 ***
// fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAIzkql1XCgLbtv";
        $accessKeySecret = "6zJCGyIDoxi3tFpsV8ZC6onxKUc3VZ";

// fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $tel;

// fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "彭永康";

// fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_141370003";

// fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = Array(
            "name" => $shopname,
//"product" => "阿里通信"
        );

// fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

// fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";

// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();
// 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
// fixme 选填: 启用https
// ,true
        );
//dd($content);
//        Cart::where('user_id', Auth::id())->delete();
//        //查询商家账户发邮件的邮箱
//        $user = Users::where('shop_id',$shop_id)->first();
//        //dd($user);
//        Mail::raw('您有新的外卖订单,请注意商户端查收!!',function ($message) use($user){
//            $message->subject('外卖订单通知提醒');
//            $message->to($user->email);
//        });
        $msg = [
            "status" => "true",
            "message" => "添加成功",
            "order_id" => $orders->id
        ];

        return json_encode($msg);
    }
//订单列表
    public function order(Request $request)
    {
        $id = $request->id;
//var_dump($id);
        $order = orders::where('id', $id)->first();
        $shop = Shops::where('id', $order->shop_id)->first();
        $goods = Ordersgoods::where('order_id', $order->id)->get();
        $goods_list = [];
        $list = [];
        foreach ($goods as $good) {
            $list['goods_id'] = $good->id;
            $list['goods_name'] = $good->goods_name;
            $list['amount'] = $good->amount;
            $list['goods_price'] = $good->goods_price;
            $goods_list[] = $list;
        }
        $orderList = [
            'id' => $order->id,
            'order_code' => $order->sn,
            'order_birth_time' => (string)$order->created_at,
            'order_status' => $order->status == 0 ? '待支付' : '已支付',
            'shop_id' => $shop->id,
            'shop_name' => $shop->shop_name,
            'shop_img' => $shop->shop_img,
            'goods_list' => $goods_list,
            'order_price' => $order->total,
            'order_address' => $order->address
        ];
        return json_encode($orderList);
    }
//订单列表
    public function orderList()
    {
        $orders = orders::where('user_id', auth()->user()->id)->get();
        $data = [];
        foreach ($orders as $key => $order) {
            $order_goods = Ordersgoods::where('order_id', $order->id)->get();
            if ($order->status == 0) {
                $order_status = '待支付';
            } elseif ($order->status == -1) {
                $order_status = '已取消';
            } elseif ($order->status == 1) {
                $order_status = '待发货';
            } elseif ($order->status == 2) {
                $order_status = '待确认';
            } else {
                $order_status = '完成';
            }
            $data[$key]['id'] = $order->id;
            $data[$key]['order_code'] = $order->sn;
            $data[$key]['order_birth_time'] = substr($order->created_at, 0, 16);
            $data[$key]['order_status'] = $order_status;
            $data[$key]['shop_id'] = $order->shop_id;
            $data[$key]['shop_name'] = $order->shop->shop_name;
            $data[$key]['shop_img'] = $order->shop->shop_img;
            $data[$key]['goods_list'] = $order_goods;
            $data[$key]['order_price'] = $order->total;
            $data[$key]['order_address'] = $order->province . $order->city . $order->county . $order->address;
        }
        return $data;
    }
//用户修改密码
    public function changePassword(Request $request)
    {
        $oldpassword = $request->oldPassword;
        $newpassword = $request->newPassword;
        if (!Hash::check($oldpassword, auth()->user()->password)) {
            return [
                "status" => 'false',
                'message' => '原密码错误!',
            ];
        } else {
            Members::where('id', auth()->user()->id)->update([
                'password' => bcrypt($newpassword)
            ]);
            return [
                "status" => 'true',
                'message' => '修改成功!',
            ];
        }
    }
//用户重置密码
    public function forgetPassword(Request $request)
    {
//        $validator = Validator::make($request->all(), [
//            'tel' => 'required',
//            'sms' => 'required',
//            'password' => 'required|min:6',
//        ], [
//            'tel.required' => '电话号码不能为空!',
//            'sms.required' => '验证码不能为空!',
//            'password.required' => '密码不能为空!',
//            'password.min' => '密码不能小于6位!',
//        ]);
//        if ($validator->fails()) {
//            return [
//                'status' => 'false',
//                'message' => $validator->errors()->first()
//            ];
//        }
        $code = Redis::get($request->tel);
        if ($code != $request->sms) {
            return [
                'status' => 'false',
                'message' => '验证码错误或者已经过期!'
            ];
        }
        Members::where('tel', $request->tel)->update([
            'password' => bcrypt($request->password)
        ]);
        return [
            'status' => 'true',
            'message' => '密码修改成功!'
        ];
    }
}
