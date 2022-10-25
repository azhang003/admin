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
use app\common\model\SystemQueue;
use app\common\service\Uuids;
use Usdtcloud\TronService\Credential;
use think\facade\Db;

class QueueError extends MemberController
{
    public function __construct()
    {

    }

    public static function setError($data)
    {
        $time                = time();
        $data['create_time'] = $time;
        $data['update_time'] = $time;
        $SystemQueue = new SystemQueue();
        $SystemQueue->save($data);
        return true;
    }

}