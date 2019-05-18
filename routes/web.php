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

// 支付宝支付
Route::get('alipay','Controller\AlipayController@pay');
// 同步通知
Route::get('alipay/return_url','Controller\AlipayController@return_url');
// 异步回调
Route::post('alipay/notify','Controller\AlipayController@notify');


// 微信支付
Route::get('wechatpay','Controller\WechatController@pay');