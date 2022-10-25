<?php

namespace app\common\controller\member;

use app\common\controller\MemberController;
use app\common\model\MemberAccount;
use app\common\model\MemberDashboard;
use app\common\model\MemberDay;
use app\common\model\MemberIndex;
use app\common\model\MemberRecord;
use app\common\model\MemberTeam;
use app\common\model\MemberWallet;
use app\common\model\MerchantIndex;
use app\common\model\SystemDay;
use app\common\model\SystemSummarize;
use app\common\service\Uuids;
use app\job\queueGame;
use app\job\queueTeam;
use think\Exception;
use think\facade\Db;
use think\facade\Queue;

class Wallet extends MemberController
{
    public function __construct()
    {

    }


    /**
     * 调整余额并增加流水记录
     * @param $username //钱包
     * @param $business //类型
     * @param $data //资金变更数据 ['前','变更,'后']
     * @param $x_uid //来源ID
     * @param $type //
     * @param $time //投注周期
     * @param $bet //投注LIST
     * @param $analog //模拟号/真实
     * @return array|\think\Collection
     * @throws Exception
     */
    public function change($username, $business, $data, $x_uid = null, $type = null, $time = null, $bet = null, $analog = 0)//$type团队级别
    {
        $rows         = [];//流水
        $recordIds = [];
        $MemberWallet = (new MemberWallet)->where(['mid' => $username]);
        // 循环数据
        foreach ($data as $key => $item) {
            // 数据不对
            if (count($item) != 3) {
                throw new Exception("Data format error");
            }
            /**数据有误**/
            if ($item[2] < 0) {
                throw new Exception("The data is wrong!");
            }
            // 保存数据
            $row = [
                'mid'         => $username,
                'currency'    => $key,
                'business'    => $business,
                'before'      => $item[0],
                'now'         => $item[1],
                'after'       => $item[2],
                'create_time' => time(),
            ];
            if (!empty($type)) {
                $row['team'] = $type;
            }
            if (!empty($x_uid)) {
                $row['x_uid'] = $x_uid;
            }
            if (!empty($time)) {
                $row['time'] = $time;
            }
            if (!empty($bet)) {
                $row['bet'] = $bet;
            }
            if ($analog == "0") {
                $rows[] = $row;
                switch ($key) {
                    case 1:
                        $MemberWallet->inc('cny', $item[1]);
                        break;
                    case 2:
                        $MemberWallet->inc('usd', $item[1]);
                        break;
                    case 3:
                        $MemberWallet->inc('usdt', $item[1]);
                        break;
                    case 4:
                        $MemberWallet->inc('eth', $item[1]);
                        break;
                    case 5:
                        $MemberWallet->inc('btc', $item[1]);
                        break;
                }
            }
        }
        $bool = $MemberWallet->update();
        if (empty($bool)) {
            throw new Exception('资金更新失败!');
        }
        $MemberRecord = new MemberRecord();
        $records       = $MemberRecord->saveAll($rows);
        if (count($records) != count($rows)) {
            throw new Exception('记录新增失败!');
        }
        foreach ($records as $rowItem) {
            $recordIds[] = $rowItem->id;
            $taskQueue = [
                'task' => 'queueTeam',
                'data' => [
                    "type"     => 'team',
                    "business" => $rowItem->business,
                    "mid"      => $username,
                    "team"      => $type,
                    "key"      => $rowItem->currency,
                    "money"    => abs($rowItem->now),
                ]
            ];
            queue(queueTeam::class, $taskQueue, 0, $taskQueue['task']);
        }
        return $recordIds;
    }
}