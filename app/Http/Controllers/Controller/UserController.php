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
}
