<?php

namespace app\job;

use app\common\controller\member\QueueError;
use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDashboard;
use app\common\model\MemberIndex;
use app\common\model\MemberTeam;
use app\common\model\MerchantIndex;
use app\common\model\SystemSummarize;
use think\Exception;
use think\facade\Db;
use think\queue\Job;

class queueTeam
{
    /**
     * @param Job $job
     * @param $data
     * @return void
     */
    public function fire(Job $job, $data)
    {
        $job->delete();
        $aaa = $this->doHelloJob($data['data']);
//        var_dump($aaa);
        if ($aaa == true) {
            echo "执行成功删除任务" . $job->attempts() . '\n';
        } else {
            QueueError::setError([
                'title'      => $data['task'],
                'controller' => self::class,
                'context'    => json_encode($data),
            ]);
            echo "执行失败删除任务" . $job->attempts() . '\n';
        }
    }

    /**
     *
     * @param array $data
     * @return bool
     */
    private function doHelloJob(array $data)
    {
        $money = $data['money'];
        $username = $data['mid'];
        $key = $data['key'];
        $agent      = MemberAccount::where('id', $username)->value('agent_line');
        $agent_line = explode('|', $agent);
        $aaa = false;
        if ($key == "1") {
            /**
             * 余额
             * 依据业务增加统计数据
             */
            Db::startTrans();
            try {
                switch ($data['business']) {
                    case 1:
                        $MerchantIndex = MerchantIndex::where([['uid', 'in', $agent_line]])
                            ->inc('recharge', $money)
                            ->inc('recharge_member')
                            ->update();
                        $MemberIndex = MemberIndex::where([['mid', '=', $username]])
                            ->inc('recharge', $money)
                            ->update();
                        $MemberDashboard = MemberDashboard::where([['mid', '=', $username]])
                            ->inc('user_recharge', $money)
                            ->update();
                        if ($MerchantIndex&&$MemberIndex&&$MemberDashboard){
                            $aaa = true;
                        }
                        break;
                    case 2:
                        $MerchantIndex = MerchantIndex::where([['uid', 'in', $agent_line]])
                            ->inc('withdraw', $money)
                            ->inc('withdraw_member')
                            ->update();
                        $MemberIndex = MemberIndex::where([['mid', '=', $username]])
                            ->inc('withdraw', $money)
                            ->update();
                        $SystemSummarize = SystemSummarize::where('id', 211)
                            ->inc('freeze_money', $money)
                            ->inc('freeze_member')
                            ->update();
                        if ($MerchantIndex&&$MemberIndex&&$SystemSummarize){
                            $aaa = true;
                        }
                        break;
                    case 3:
                        $taskQueue = [
                            'task' => 'pushBet',
                            'data' => [
                                "type" =>  'bet',
                                "agent_line" => $agent_line,
                                "mid"        => $username,
                                "money"      => $money,
                            ]
                        ];
                        queue(queueGame::class,$taskQueue,0,'pushBet');
                        $aaa = true;
                        break;
                    case 4:
                        $taskQueue = [
                            'task' => 'pushBet',
                            'data' => [
                                "type" =>  'award',
                                "agent_line" => $agent_line,
                                "mid"        => $username,
                                "money"      => $money,
                            ]
                        ];
                        queue(queueGame::class,$taskQueue,0,'pushBet');
                        $aaa = true;
                        break;

                    case 5:
                        $MemberIndex = MemberIndex::where([['mid', '=', $username]])
                            ->dec('fee', $money)
                            ->dec('withdraw', $money)
                            ->update();
                        if ($MemberIndex){
                            $aaa = true;
                        }
                        break;
                    case 10:
                        $MemberIndex = MemberIndex::where([['mid', '=', $username]])
                            ->inc('into', $money)
                            ->update();
                        $MemberDashboard = MemberDashboard::where([['mid', '=', $username]])
                            ->inc('into', $money)
                            ->update();
                        if ($MemberIndex&&$MemberDashboard){
                            $aaa = true;
                        }
                        break;
                    case 11:
                        $MemberIndex = MemberIndex::where([['mid', '=', $username]])
                            ->inc('Transfer_out', $money)
                            ->update();
                        $MemberDashboard = MemberDashboard::where([['mid', '=', $username]])
                            ->inc('out', $money)
                            ->update();
                        if ($MemberIndex&&$MemberDashboard){
                            $aaa = true;
                        }
                        break;
                    case 12:
                        $MerchantIndex = MerchantIndex::where([['uid', 'in', $agent_line]])
                            ->inc('ming', $money)
                            ->update();
                        $MemberIndex = MemberIndex::where([['mid', '=', $username]])
                            ->inc('ming', $money)
                            ->update();
                        if ($MerchantIndex&&$MemberIndex){
                            $aaa = true;
                        }
                        break;
                    case 13:
                    case 6:
                        echo 666;
                        $aaa = true;
                        break;
                    case 14:
                        $MemberDashboard = MemberDashboard::where([['mid', '=', $username]])
                            ->inc('minging', $money)
                            ->update();
                        if ($MemberDashboard){
                            $aaa = true;
                        }
                        break;
                    case 15:
                        $MemberIndex = MemberIndex::where([['mid', '=', $username]])
                            ->inc('fee', $money)
                            ->update();
                        if ($MemberIndex){
                            $aaa = true;
                        }
                        break;
                    default:
                        break;
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                // 回滚事务
                Db::rollback();
                return false;
            }
        }
        if ($key == "4") {
            /**
             * 分享收益
             * 依据业务增加统计数据
             */
            Db::startTrans();
            try {
                $type = $data['team'];
                $agent      = MemberAccount::where('id', $username)->value('agent_line');
                $agent_line = explode('|', $agent);
                if ($data['business'] == 9) {
                    MerchantIndex::where([['uid', 'in', $agent_line]])
                        ->inc('all_share', $money)
                        ->inc('surplus_share', $money)
                        ->update();
                    switch ($type) {
                        case 1:
                            MemberTeam::where([['mid', '=', $username]])
                                ->inc('first_share', $money)
                                ->update();
                            break;
                        case 2:
                            MemberTeam::where([['mid', '=', $username]])
                                ->inc('second_share', $money)
                                ->update();
                            break;
                        case 3:
                            MemberTeam::where([['mid', '=', $username]])
                                ->inc('third_share', $money)
                                ->update();
                            break;
                        case 0:
                            MemberTeam::where([['mid', '=', $username]])
                                ->inc('vip', $money)
                                ->update();
                            break;
                    }
                }
                if ($data['business'] == 13) {
                    MerchantIndex::where([['uid', 'in', $agent_line]])
                        ->inc('in_share', $money)
                        ->dec('surplus_share', $money)
                        ->update();
                }
                // 提交事务
                Db::commit();
                $aaa = true;
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                // 回滚事务
                Db::rollback();
                return false;
            }
        }
        if ($key != 1 && $key != 4){
            $aaa = true;
        }
        return $aaa;
    }

}