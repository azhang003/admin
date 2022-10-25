<?php

namespace app\common\controller\merchant;

use app\common\controller\MemberController;
use app\common\model\MemberAccount;
use app\common\model\MemberDashboard;
use app\common\model\MemberProfile;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\model\MerchantIndex;
use app\common\model\MerchantRecord;
use app\common\model\MerchantWallet;
use app\common\service\Uuids;
use think\Exception;
use think\facade\Db;

class Wallet extends MemberController
{
    public function __construct()
    {

    }

    /**
     * @param $username
     * @param $business
     * @param $data
     * @param null $x_uid
     * @param null $type
     * 调整余额并增加流水记录
     * @param null $bet_id
     * @throws \think\Exception
     */
    public function change($username, $business, $data, $x_uid = null, $type = null,$time = null)//$type团队级别
    {
        // 货币配置
        // 循环数据
        foreach ($data as $key => $item) {
            // 数据不对
            if (count($item) != 3) {
                throw new Exception("数据格式错误");
            }
            // 保存数据
            $row = [
                'uid' => $username,
                'currency' => $key,
                'business' => $business,
                'before'   => $item[0],
                'now'      => $item[1],
                'after'    => (string)$item[2],
            ];
            if (!empty($type)){
                $row['team'] = $type;
            }
            if (!empty($x_uid)){
                $row['x_uid'] = $x_uid;
            }
            if (!empty($time)){
                $row['time'] = $time;
            }
            if ($key == "1"){
                $money = abs($item[1]);
                switch ($business){
                    case 2:
                        MerchantIndex::where([['uid','=',$username]])
                            ->inc('transfer',$money)
                            ->update();
                        break;
                    case 3:
                        MerchantIndex::where([['uid','=',$username]])
                            ->inc('into',$money)
                            ->update();
                        break;
                }
            }
            // 添加流水
            MerchantRecord::setAdd($row);
            MerchantWallet::where(['uid' => $username])->save(['cny'=>$item[2]]);
        }
    }

}