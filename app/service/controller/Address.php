<?php
declare (strict_types=1);

namespace app\service\controller;

use app\common\controller\member\Redis;
use think\Request;

class Address
{
    public function balance($address){
        $redis = Redis::redis();
        if ($address == get_config('wallet','wallet','collection_address')){
            return [
                'balance'  => $redis->get('gj_balance'),
                'TRXBalance'  => $redis->get('gj_TRXBalance'),
            ];
        }else{
            return [
                'balance'  => $redis->get('tx_balance'),
                'TRXBalance'  => $redis->get('tx_TRXBalance'),
            ];
        }
    }
}
