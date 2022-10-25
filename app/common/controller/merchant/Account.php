<?php

namespace app\common\controller\merchant;

use app\common\controller\MerchantController;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\model\MerchantProfile;
use app\common\model\MerchantWallet;
use app\common\service\Uuids;
use think\Exception;
use think\facade\Db;

class Account extends MerchantController
{
    public function __construct()
    {
        
    }

    public function create($mobile,$password,$merchant = 0)
    {
        // 启动事务
        Db::startTrans();
        try {
            //添加账户
            $MerchantAccount = new MerchantAccount();
            $account = [
                'uuid' => Uuids::getUuids(1),
                'password' => password_hash($password,PASSWORD_DEFAULT),
                'safeword' => password_hash($password,PASSWORD_DEFAULT),
                'agent' => 0,
                'agent_line' => '0|'
            ];
            if ($merchant != 0){
               $Accountmerchant = MerchantAccount::find($merchant);
                $account['agent_line'] = $Accountmerchant->agent_line . $Accountmerchant->id . '|';
                $account['agent'] = count(explode('|',$account['agent_line'])) - 2;
            }

            $MerchantAccount->save($account);

            //添加钱包
            $MerchantWallet = new MerchantWallet();
            $wallet = [
                'uid' => $MerchantAccount->id,

            ];
            $MerchantWallet->save($wallet);
            //添加资料
            $MerchantProfile = new MerchantProfile();
            $profile = [
                'uid' => $MerchantAccount->id,
                'mobile' => $mobile
            ];
            $MerchantProfile->save($profile);
            //添加仪表盘
            $MerchantDashboard = new MerchantDashboard();
            $dashboard = [
                'uid' => $MerchantAccount->id,
            ];
            $MerchantDashboard->save($dashboard);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            var_dump($e);
            Db::rollback();
            return false;
        }
        return true;
    }
}