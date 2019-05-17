<?php

namespace App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // 加入购物车
    public function joinCart(){
        $data = json_decode(file_get_contents('php://input'),true);
        if($data){
            $cartInfo = DB::table('shop_cart')->where(['goods_id' => $data['data']['goodsinfo']['goods_id']])->first();
            if($cartInfo){
                $cartInfo = json_decode(json_encode($cartInfo),true);
                $update_data = [
                    'buy_number' => $cartInfo['buy_number'] + 1,
                    'create_time'   => time(),
                    'update_time'   => time(),
                ];
                $res = DB::table('shop_cart')->where(['goods_id' => $cartInfo['goods_id']])->update($update_data);
            }else{
                $cart_data = [
                    'goods_id'      => $data['data']['goodsinfo']['goods_id'],
                    'user_id'       => $data['user_id'],
                    'create_time'   => time(),
                    'update_time'   => time(),
                ];
                $res = DB::table('shop_cart')->insert($cart_data);
            }
            if($res){
                successful(0,'加入购物车成功');
            }else{
                error(40006,'加入购物车失败');
            }
        }else{
            error(40001,'参数错误');
        }
    }

    // 购物车数据
    public function cartInfo(){
        $uid = request()->uid;
        if(empty($uid)){
            die(error(40001,'缺少参数'));
        }
        $cartInfo = DB::table('shop_cart')
            ->join('shop_goods','shop_goods.goods_id','=','shop_cart.goods_id')
            ->where(['user_id' => $uid,'cart_status' => 1])
            ->get();
        if($cartInfo){
            success(0,['cartInfo' => $cartInfo]);
        }else{
            error(50000,'购物车空空如也哦');
        }
    }
}
