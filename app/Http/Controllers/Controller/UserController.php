<?php

namespace App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // 错误
    public function error($num,$msg){
        $json = [
            'errcode'       =>  $num,
            'msg'           =>  $msg
        ];
        die(json_encode($json,JSON_UNESCAPED_UNICODE));
    }

    // 正确(返回参数)
    public function success($num,$data=[]){
        $json = [
            'errcode'       =>  $num,
            'data'          =>  $data
        ];
        die(json_encode($json,JSON_UNESCAPED_UNICODE));
    }

    // 正确(不返回参数)
    public function successful($num,$msg){
        $json = [
            'errcode'       =>  $num,
            'msg'           =>  $msg,
        ];
        die(json_encode($json,JSON_UNESCAPED_UNICODE));
    }

    // 注册
    public function register(){
        $str = file_get_contents('php://input');
        $data = json_decode($str,true);
        // 验非空
        if(empty($data['name'])){
            $this->error(40001,'用户名必填');
        }
        if(empty($data['email'])){
            $this->error(40001,'邮箱必填');
        }
        if( empty($data['pass1'])){
            $this->error(40001,'密码必填');
        }
        if( empty($data['pass2'])){
            $this->error(40001,'确认密码必填');
        }
        if($data['pass1'] != $data['pass2']){
            $this->error(40001,'两次密码输入不一致');
        }
        // 验唯一
        $res = DB::table('userinfo')->where(['email' => $data['email']])->first();
        if($res){
            $this->error(40002,'该邮箱已经注册过了');
        }
        // 数据处理
        unset($data['pass2']);
        $data['pass1'] = password_hash($data['pass1'],PASSWORD_BCRYPT);
        $id = DB::table('userinfo')->insertGetId($data);
        // 判断提示
        if($id){
            $this->successful(0,'注册成功');
        }else{
            $this->error(40005,'注册失败');
        }
    }

    // 登录
    public function login(){
        $str = file_get_contents("php://input");
        $data = json_decode($str,true);
        // 验非空
        if(empty($data['email'])){
            $this-> error(40001,'邮箱账号不能为空');
        }
        if(empty($data['pass'])){
            $this-> error(40001,'密码不能为空');
        }
        // 验证账号、密码
        $arr = DB::table('userinfo')->where(['email' => $data['email']])->first();
        $arr = json_decode(json_encode($arr),true);
        if($arr){
            if(password_verify($data['pass'],$arr['pass1'])){
                // 生成token
                $key = 'token_'.$arr['id'];
                $token = Redis::get($key);
                if($token){
                }else{
                    $token = substr(md5(time().$arr['id'].Str::random(10).rand(111,999)),5,20);
                    Redis::set($key,$token);
                    Redis::expire($key,604800);
                }
                $this->success(0,'登录成功',['token' => base64_encode($token),'id' => $arr['id']]);
            }else{
                $this->error(40005,'密码错误');
            }
        }else{
            $this->error(40005,'账号错误');
        }
    }

    // 获取用户信息
    public function getUserInfo(){
        $id = request()->id;
        $userInfo = DB::table('userinfo')->where(['id' => $id])->first();
        if($userInfo){
            $data = json_decode(json_encode($userInfo),true);
            die($this->success(0,$data));
        }else{
            die($this->error(50000,'暂时没有数据'));
        }
    }

    // 获取商品信息
    public  function getGoodsInfo(){
        $goodsInfo = DB::table('shop_goods')->limit(5)->get();
        if($goodsInfo){
            die($this->success(0,['goodsinfo' => $goodsInfo]));
        }else{
            die($this->error(50000,'暂时没有数据'));
        }
    }

    // 获取单个商品信息
    public function getGoodsDetail(){
        $id = request()->id;
        if(empty($id)){
            die($this->error(40001,'缺少参数'));
        }
        $goodsInfo = DB::table('shop_goods')->where(['goods_id' => $id])->first();
        if($goodsInfo){
            $goodsimgs = explode('|',rtrim($goodsInfo->goods_imgs,"|"));
            $goodsInfo->goods_imgs = $goodsimgs;
            die($this->success(0,['goodsinfo' => $goodsInfo]));
        }else{
            die($this->error(50000,'暂时没有数据'));
        }
    }

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
                $this->successful(0,'加入购物车成功');
            }else{
                $this->error(40006,'加入购物车失败');
            }
        }else{
            $this->error(40001,'参数错误');
        }
    }

    // 购物车数据
    public function cartInfo(){
        $uid = request()->uid;
        if(empty($uid)){
            die($this->error(40001,'缺少参数'));
        }
        $cartInfo = DB::table('shop_cart')
            ->join('shop_goods','shop_goods.goods_id','=','shop_cart.goods_id')
            ->where(['user_id' => $uid,'cart_status' => 1])
            ->get();
        if($cartInfo){
            $this->success(0,['cartInfo' => $cartInfo]);
        }else{
            $this->error(50000,'购物车空空如也哦');
        }
    }

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
            die($this->error(40001,'缺少参数'));
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
            $this->successful(0,'下单成功');

        }catch ( \Exception $e){
            //如果失败就回滚
            DB::rollback();
            $this->error(60000,$e->getMessage());
        }

    }
}
