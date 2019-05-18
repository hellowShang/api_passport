<?php

namespace App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AlipayController extends Controller
{
    // 支付宝支付订单
    public function pay(Request $request){
        $order_no = $request->order_no;
        $uid  = $request->id;

        // 根据订单号查询当前用户未删除的，没有支付的订单
        $orderInfo = DB::table('shop_order')->where(['user_id' => $uid,'order_no' => $order_no])->first();
        $orderInfo = json_decode(json_encode($orderInfo),true);

        // 判断是否已经支付
        if($orderInfo['pay_status'] != 1){
            die('订单已经支付过了');
        }

        // 判断订单是否已经删除
        if($orderInfo['status'] != 1){
            die('该订单已被删除');
        }

        // 请求支付url
        $pay_url = env('ALIPAY_PATH');
        // 同步通知地址
        $return_url = env('RETURN_URL');
        // 异步通知url
        $notify = env('PAY_NOTIFY_PATH');

        // 请求参数
        $biz_content = [
            'subject'       => '买家订单支付',
            'out_trade_no'  => $order_no,
            'total_amount'  => $orderInfo['order_amount'],
            'product_code'  => 'QUICK_WAP_WAY'
        ];

        // 公共参数
        $data = [
            'app_id'        => env('APPID'),
            'method'        => 'alipay.trade.wap.pay',
            'format'        => 'JSON',
            'charset'       => 'utf-8',
            'sign_type'     => 'RSA2',
            'timestamp'     => date('Y-m-d H:i:s'),
            'version'       => 1.0,
            'return_url'    => $return_url,
            'notify_url'    => $notify,
            'biz_content'   =>  json_encode($biz_content),

        ];

//        'sign'          => '123123',
//            'iu'            => '',
//            'ii'            => [
//            'a'         => 'aaaaa'
//        ]

//        // 剔除sign和空值
//        foreach($data as $k=>$v){
//            if($k = 'sign'){
//                unset($data[$k]);
//            }else if($v = '' || is_array($v)){
////                unset($data[$k]);
//            }
//        }
//        echo "<pre>";print_r($data);echo "</pre>";die;

        // 签名
        $sign = $this->sign($data);
        $data['sign'] = $sign;

        // 拼接url
        $str = '?';
        foreach($data as $k=> $v){
            $str .= $k .'='.urlencode($v).'&';
        }
        $str = rtrim($str,'&');
        $url = $pay_url.$str;

        // 重定向到支付宝页面
        header("Location:".$url);die;
    }

    // 签名
    public function sign($data){
        // 排序
        ksort($data);

        // 拼接
        $joint = $this-> joint($data);

        // 调用签名函数
        $private_key = openssl_get_privatekey('file://'.storage_path('app/keys/private.pem'));
        openssl_sign($joint,$sign,$private_key,OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    // 拼接数据
    public function joint($data){
        $str = '';
        foreach($data as $k => $v){
            // 剔除sign和空值拼接
            if($k != 'sign' && $v != '' && !is_array($v)){
                $str .= $k . "=" . $v . "&";
            }
        }
        return rtrim($str,'&');
    }

    // 同步通知
    public function return_url(){
        echo '支付成功，订单号为：'.$_GET['out_trade_no'].'正在努力发货';
        header('Refresh:3;url=http://127.0.0.1:8848/Hellow world/index.html');
    }

    // 异步通知
    public function notify(){
        // 接收数据
        $str = $_POST;
        $data = "\n".date('Y-m-d H:i:s').json_encode($str)."\n\r";
        is_dir('logs') or mkdir('logs',0777,true);
        file_put_contents('logs/notify.log',$data,FILE_APPEND);

        /*
        if($result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代


            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号

            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];

            if($_POST['trade_status'] == 'TRADE_FINISHED') {

            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success";		//请不要修改或删除

        }else {
            //验证失败
            echo "fail";	//请不要修改或删除

        }
        */
    }
}
