<?php

namespace App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // 注册
    public function register(){
        $str = file_get_contents('php://input');
        $data = json_decode($str,true);
        // 验非空
        if(empty($data['name'])){
            error(40001,'用户名必填');
        }
        if(empty($data['email'])){
            error(40001,'邮箱必填');
        }
        if( empty($data['pass1'])){
            error(40001,'密码必填');
        }
        if( empty($data['pass2'])){
            error(40001,'确认密码必填');
        }
        if($data['pass1'] != $data['pass2']){
            error(40001,'两次密码输入不一致');
        }
        // 验唯一
        $res = DB::table('userinfo')->where(['email' => $data['email']])->first();
        if($res){
            error(40002,'该邮箱已经注册过了');
        }
        // 数据处理
        unset($data['pass2']);
        $data['pass1'] = password_hash($data['pass1'],PASSWORD_BCRYPT);
        $id = DB::table('userinfo')->insertGetId($data);
        // 判断提示
        if($id){
            successful(0,'注册成功');
        }else{
            error(40005,'注册失败');
        }
    }

    // 登录
    public function login(){
        $str = file_get_contents("php://input");
        $data = json_decode($str,true);
        // 验非空
        if(empty($data['email'])){
            error(40001,'邮箱账号不能为空');
        }
        if(empty($data['pass'])){
            error(40001,'密码不能为空');
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
                die(['errcode'=>0,'msg' => '登录成功','data' => ['token' => base64_encode($token),'id' => $arr['id']]]);
            }else{
                error(40005,'密码错误');
            }
        }else{
            error(40005,'账号错误');
        }
    }

    // 获取用户信息
    public function getUserInfo(){
        $id = request()->id;
        $userInfo = DB::table('userinfo')->where(['id' => $id])->first();
        if($userInfo){
            $data = json_decode(json_encode($userInfo),true);
            die(success(0,$data));
        }else{
            die(error(50000,'暂时没有数据'));
        }
    }
}
