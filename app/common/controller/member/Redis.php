<?php

namespace app\common\controller\member;

use app\common\controller\MemberController;
use app\common\model\MemberAccount;
use app\common\model\MemberAddress;
use app\common\model\MemberDashboard;
use app\common\model\MemberLogin;
use app\common\model\MemberProfile;
use app\common\model\MemberWallet;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\service\Uuids;
use think\facade\Env;
use Usdtcloud\TronService\Credential;
use think\facade\Db;

class Redis extends MemberController
{
    public function __construct()
    {

    }

    public static function redis($select = 2){
        $redis = new \Redis();
        $redis->connect((Env::get('JWT.HOSTNAME'))?:'127.0.0.1', 6379);
        $redis->auth('123456');
        $redis->select($select);
        return $redis;
    }

    public static function redis3(){
        $redis = new \Redis();
        $redis->connect((Env::get('JWT.HOSTNAME'))?:'127.0.0.1', 6379);
        $redis->auth('123456');
        $redis->select(3);
        return $redis;
    }
}