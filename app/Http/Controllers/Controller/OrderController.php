<?php

namespace App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // 订单号生成
    public function order_no($uid=0){
        $order_no = $uid.time().rand(11111,99999);
        return $order_no;
    }

    // 订单生成
    public function orderGenerate(){
        $str = request()->goods_id;
        $uid = request()->uid;
        $pay_way = 1;
        // 验证
        if(empty($str) || empty($uid)){
            die(error(40001,'缺少参数'));
        }

        // 判断是一个还是多个
        if(strpos($str,',')){
            $goods_id = explode(',',$str);
        }else{
            $goods_id = $str;
        }

        // 开启事务
        DB::beginTransaction();

        // 捕获异常
        try{
            if(is_array($goods_id)){
                $cartInfo = DB::table('shop_cart')
                    ->join('shop_goods','shop_goods.goods_id','=','shop_cart.goods_id')
                    ->where(['cart_status' => 1,'user_id' => $uid])
                    ->whereIn('shop_cart.goods_id',$goods_id)
                    ->get();
            }else{
                $cartInfo = DB::table('shop_cart')
                    ->join('shop_goods','shop_goods.goods_id','=','shop_cart.goods_id')
                    ->where(['goods_id' => $goods_id,'user_id' => $uid])
                    ->first();
            }
            if(!$cartInfo){
                throw new \Exception('购物车空空如也');
            }

            // 订单信息入库
            $orderInfo = [];
            $cartInfo = json_decode(json_encode($cartInfo),true);

            $order_no = $this->order_no($uid);
            $order_amount = 0;
            foreach($cartInfo as $k => $v){
                $order_amount += $v['buy_number'] * $v['self_price'];
            }
            $orderInfo['order_no'] = $order_no;
            $orderInfo['order_amount'] = $order_amount;
            $orderInfo['user_id'] = $uid;
            $orderInfo['pay_way'] = $pay_way;
            $orderInfo['create_time'] = time();
            $orderInfo['update_time'] = time();
            // 入库
            $order_id = DB::table('shop_order')->insertGetId($orderInfo);
            if(!$order_id){
                throw new \Exception('订单信息写入失败');
            }

            // 订单详情入库
            $orderDetail = [];
            foreach($cartInfo as $k => $v){
                $orderDetail[$k]['order_id'] = $order_id;
                $orderDetail[$k]['goods_id'] = $v['goods_id'];
                $orderDetail[$k]['user_id'] = $uid;
                $orderDetail[$k]['buy_number'] = $v['buy_number'];
                $orderDetail[$k]['self_price'] = $v['self_price'];
                $orderDetail[$k]['goods_name'] = $v['goods_name'];
                $orderDetail[$k]['goods_img'] = $v['goods_img'];
                $orderDetail[$k]['create_time'] = time();
                $orderDetail[$k]['update_time'] = time();
            }

            // 入库
            $res1 = DB::table('shop_order_detail')->insert($orderDetail);
            if(!$res1){
                throw new \Exception('订单详情写入失败');
            }

            // 购物车数据删除(修改状态)
            $orderDetail_goods_id = array_column($orderDetail,'goods_id');
            if(count($orderDetail_goods_id) == 2){
                $res2 = DB::table('shop_cart')->where('user_id',$uid)->whereIn('goods_id',$orderDetail_goods_id)->update(['cart_status' => 2]);
            }else{
                $res2 = DB::table('shop_cart')->where(['goods_id' => $orderDetail_goods_id,'user_id' => $uid])->update(['cart_status' => 2]);
            }

            if(!$res2){
                throw new \Exception('购物车数据删除失败');
            }

            //如果成功就提交
            DB::commit();
            successful(0,'下单成功');

        }catch ( \Exception $e){
            //如果失败就回滚
            DB::rollback();
            error(60000,$e->getMessage());
        }
    }
}
