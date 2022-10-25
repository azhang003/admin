<?php

namespace app\job;

use app\common\controller\member\QueueError;
use app\common\controller\member\Wallet;
use app\common\model\GameEventBet;
use app\common\model\MemberWallet;
use think\facade\Db;
use think\queue\Job;

class queueAwardMember
{

    /**
     * @param Job $job
     * @param $data
     * @return void
     * @throws \think\Exception
     */
    public function fire(Job $job, $data)
    {
        if ($this->doHelloJob($data['data'])) {
            $job->delete();
            echo "执行成功删除任务" . $job->attempts() . '\n';
        } else {
            if ($job->attempts() > 2){
                $job->delete();
                QueueError::setError([
                    'title' =>$data['task'],
                    'controller' =>self::class,
                    'context' =>json_encode($data),
                ]);
                echo "执行失败删除任务" . $job->attempts() . '\n';
            }
        }
    }
    /**
     * 派奖
     * @param $uid
     * @param $money
     * @param string $type
     * @param null $time
     * @throws \think\Exception
     */
    private function doHelloJob(array $data)
    {
        $queueData = $data;
        if (!is_array($queueData)){
            return false;
        }
        $Bet = GameEventBet::where([
            ['id','=',$queueData['bet_id']],
            ['is_ok','=',0]]
        )->find();
        if (!$Bet){
            return true;
        }
        $record = json_decode($Bet->record,true);
        if (!isset($record['out'])){
            $record['out'] = [];
        }
        $MemberWallet =  MemberWallet::where('mid', $queueData['mid'])->find();
        switch ($queueData['type']) {
            case 0:
                $wallet = $MemberWallet->cny;
                $cid    = 1;
                break;
            case 1:
                $wallet = $MemberWallet->btc;
                $cid    = 5;
                break;
        }
        Db::startTrans();
        try {
            if ($queueData['bet'] == $queueData['open']) {
                $betMoney = $queueData['money'] * $queueData['odds'];
               $recordnew = (new Wallet())->change($queueData['mid'], 4, [
                    $cid => [$wallet, $betMoney , $wallet + $betMoney],
                ], null, null, $data['list_type'],$queueData['bet_id']);
                $record['out'][] = $recordnew;
                /** BET表变更 **/
                $bool = $Bet->save([
                    'is_ok'     => 1,
                    'open_time' => time(),
                    'remark'    => $data['remark'],
                    'record'    => json_encode($record),
                ]);
                if (!$bool) {
                    throw new \think\Exception('开奖失败!');
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            var_dump(json_encode($e->getTraceAsString()));
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

}