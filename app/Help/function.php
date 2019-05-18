<?php
// 错误
function error($num,$msg){
    $json = [
        'errcode'       =>  $num,
        'msg'           =>  $msg
    ];
    die(json_encode($json,JSON_UNESCAPED_UNICODE));
}

// 正确(返回参数)
function success($num,$data=[]){
    $json = [
        'errcode'       =>  $num,
        'data'          =>  $data
    ];
    die(json_encode($json,JSON_UNESCAPED_UNICODE));
}

// 正确(不返回参数)
function successful($num,$msg){
    $json = [
        'errcode'       =>  $num,
        'msg'           =>  $msg,
    ];
    die(json_encode($json,JSON_UNESCAPED_UNICODE));
}

