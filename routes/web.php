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
    $str = '{"gmt_create":"2019-05-18 16:49:25","charset":"utf-8","seller_email":"ttwvsq1399@sandbox.com","subject":"\u4e70\u5bb6\u8ba2\u5355\u652f\u4ed8","sign":"AqTARvEl1w5fo9eCq2Rmfkk0DD8Wc2g+sxN+S5SWjWbur7jI0hB\/QDLvC3Nj5YTPGU2HEpSPhzEDX9UxnBJWpjmFQhg4NkdxAWPTTOM8d4CwpNA05MuXENBFF7PSTRBqCwFTRJsvw2WQIra375mN0Yk9HXjIYg4bYn9kslpzdgyC5prvUZ7yj4D21f5wEYY5uJQyNpLOGK+E+ammEP6exIl\/Acb4rCC7HXQMcXrth6CgTQOEJprK4h3593xeZu1Tie21Wo5EQpenXcdqPnwZ0Hq0\/GS08HmqdIpwDXC7Imn9F4wbSoDih4ZAjqVUAgXHXPLeMllkUDNrKii6sGpvTQ==","buyer_id":"2088102177593005","invoice_amount":"999.00","notify_id":"2019051800222164927093001000226595","fund_bill_list":"[{\"amount\":\"999.00\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"999.00","app_id":"2016092500595340","buyer_pay_amount":"999.00","sign_type":"RSA2","seller_id":"2088102177248642","gmt_payment":"2019-05-18 16:49:26","notify_time":"2019-05-18 16:49:27","version":"1.0","out_trade_no":"4155816934651343","total_amount":"999.00","trade_no":"2019051822001493001000032434","auth_app_id":"2016092500595340","buyer_logon_id":"rpe***@sandbox.com","point_amount":"0.00"}';
    $data = json_decode($str,true);
    echo "<pre>";print_r($data);echo "</pre>";die;

});

// 注册
Route::post('/user/register','Controller\UserController@register');

// 登录
Route::post('/user/login','Controller\UserController@login');

// 获取用户信息
Route::get('/user/userInfo','Controller\UserController@getUserInfo');

// 获取商品数据
Route::get('goodsInfo','Controller\GoodsController@getGoodsInfo');

// 获取单个商品信息
Route::get('goodsDetail','Controller\GoodsController@getGoodsDetail');

// 加入购物车
Route::post('joinCart','Controller\CartController@joinCart');

// 购物车数据
Route::get('cartInfo','Controller\CartController@cartInfo');

// 订单生成
Route::get('orderGenerate','Controller\OrderController@orderGenerate');

// 支付宝手机支付
Route::get('alipay','Controller\AlipayController@pay');
// 支付宝同步通知
Route::get('alipay/return_url','Controller\AlipayController@return_url');
// 支付宝异步回调
Route::post('alipay/notify','Controller\AlipayController@notify');


// 微信APP支付
Route::get('wechatpay',function(){
    echo $_GET['id'];
    echo $_GET['token'];
    echo $_GET['order_no'];die;
    echo '该商户暂未开通此业务，请见谅，正在跳转支付宝';
});
//Route::get('wechatpay','Controller\WechatController@pay');
//// 微信异步回调
//Route::post('wechat/notify','Controller\WechatController@notify');