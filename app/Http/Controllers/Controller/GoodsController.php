<?php

namespace App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class GoodsController extends Controller
{
    // 获取商品信息
    public  function getGoodsInfo(){
        $goodsInfo = DB::table('shop_goods')->get();
        if($goodsInfo){
            die(success(0,['goodsinfo' => $goodsInfo]));
        }else{
            die(error(50000,'暂时没有数据'));
        }
    }

    // 获取单个商品信息
    public function getGoodsDetail(){
        $id = request()->id;
        if(empty($id)){
            die(error(40001,'缺少参数'));
        }
        $goodsInfo = DB::table('shop_goods')->where(['goods_id' => $id])->first();
        if($goodsInfo){
            $goodsimgs = explode('|',rtrim($goodsInfo->goods_imgs,"|"));
            $goodsInfo->goods_imgs = $goodsimgs;
            die(success(0,['goodsinfo' => $goodsInfo]));
        }else{
            die(error(50000,'暂时没有数据'));
        }
    }
}
