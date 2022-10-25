<?php

namespace app\job;

use app\common\controller\member\QueueError;
use app\common\controller\member\Wallet;
use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDashboard;
use app\common\model\MemberDay;
use app\common\model\MemberIndex;
use app\common\model\MemberTeam;
use app\common\model\MemberWallet;
use app\common\model\MerchantIndex;
use app\common\model\SystemDay;
use think\Exception;
use think\facade\Db;
use think\queue\Job;

class queueGame
{
    public function fire(Job $job, array $data)
    {
        if (!array_key_exists("task",$data)){
            echo "task 不存在,删除任务" . $job->attempts() . '\n';
            $job->delete();
        }
        if ($this->doJOb($data)) {
            $job->delete();
            echo "删除任务" . $job->attempts() . '\n';
        } else {
            if ($job->attempts() > 2){
                $job->delete();
                QueueError::setError([
                    'title'      => $data['task'],
                    'controller' => self::class,
                    'context'    => json_encode($data),
                ]);
            }
        }
    }


    private function doJOb(array $data)
    {
        var_dump(json_encode($data));
        try {
            if (array_key_exists('task', $data) && array_key_exists('data', $data) && is_array($data['data'])) {
                $task = $data['task'];
                $bool = self::$task($data['data']);
                return $bool;
            } else {
                return false;
            }
        } catch (\Exception $exception) {
            return false;
        }
    }
    /**
     * 投注分红
     * @param array $data
     * @return void
     */
    private static function pushBonus(array $data)
    {
//        $taskQueue = [
//            'task' => 'pushBonus', //任务
//            'data' => [
//                "mid"   => '10082', //会员ID
//                "money" => $money, //金额
//            ]
//        ];
        if (array_key_exists('mid',$data)){
            $mid = $data['mid'];
        }else{
            var_dump('mid 不存在!');
            return false;
        }
        if (array_key_exists('money',$data)){
            $mooney = $data['money'];
        }else{
            var_dump('money 不存在!');
            return false;
        }
        $share = explode("|",get_config('site', 'setting', 'share'));
        $super = get_config('site', 'setting', 'super');
        $inviters = MemberAccount::where('id',$mid)->value('inviter_line');
        if (empty($inviters)){
            return false;
        }
        $cdata = [];
        $inviter = agent_line_array($inviters);
        foreach ($inviter as $key => $item) {
            if ($key < 3){
                $teammoney = $share[$key] * $mooney;
                $cdata[$item] = [
                    'money' => money_format_bet($teammoney),
                    'team' => $key + 1,
                ];
            }else{
                $account = MemberAccount::where('id',$item)->find();
                if ($account && $account->level == 4){
                    $teammoney = $super * $mooney;
                    $cdata[$item] = [
                        'money' => money_format_bet($teammoney),
                        'team' => 0,
                    ];
                }
            }
        }
        // 启动事务
        Db::startTrans();
        try {
            foreach ($cdata as $key => $cdatum) {
                $MW = MemberWallet::where('mid', $key)->find();
                if(!$MW){
                    continue;
                }
                (new Wallet())->change($key, 9, [
                    4 => [$MW->eth, $cdatum['money'], $MW->eth + $cdatum['money']],
                ], $mid, $cdatum['team']);
//                $money = $cdatum['money'];
//                $username = $key;
//                $agent      = MemberAccount::where('id', $username)->value('agent_line');
//                $agent_line = explode('|', $agent);
//                MerchantIndex::where([['uid', 'in', $agent_line]])
//                    ->inc('all_share', $cdatum['money'])
//                    ->inc('surplus_share', $money)
//                    ->update();
//                switch ($cdatum['team']) {
//                    case 1:
//                        MemberTeam::where([['mid', '=', $username]])
//                            ->inc('first_share', $money)
//                            ->update();
//                        break;
//                    case 2:
//                        MemberTeam::where([['mid', '=', $username]])
//                            ->inc('second_share', $money)
//                            ->update();
//                        break;
//                    case 3:
//                        MemberTeam::where([['mid', '=', $username]])
//                            ->inc('third_share', $money)
//                            ->update();
//                        break;
//                    case 0:
//                        MemberTeam::where([['mid', '=', $username]])
//                            ->inc('vip', $money)
//                            ->update();
//                        break;
//                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            throw new Exception('执行失败!'.$e->getMessage());
            return false;
        }
        return true;
    }


    /**
     * 用户投注
     * @param array $data
     * @return bool
     */
    private static function pushBet(array $data)
    {
//        $taskQueue = [
//            'task' => 'pushBet', //任务
//            'data' => [
//                "type" =>  'award', //类型  投注 开奖
//                "agent_line" => $agent_line, 代理
//                "mid"        => $username, 用户ID
//                "money"      => $money, 金额
//            ]
//        ];
        // 启动事务
        Db::startTrans();
        try {
            if (!is_array($data['agent_line']) || empty($data['agent_line'])) {
                throw new Exception('agent_line 数据有误!' . json_encode($data['agent_line']));
            }
            if (empty($data['mid'])) {
                throw new Exception('mid 数据有误!');
            }
            if (empty($data['money'])) {
                throw new Exception('money 数据有误!');
            }
            if (empty($data['type'] || !in_array($data['type'],['award','bet']))) {
                throw new Exception('type 数据有误!');
            }
            $MerchantIndex = MerchantIndex::where([['uid', 'in', $data['agent_line']]]);
            if ($data['type'] == 'bet') {
                $MerchantIndex->inc('game')
                    ->dec('win', $data['money']);
            } else {
                $MerchantIndex->inc('win', $data['money']);
            }
            $bool = $MerchantIndex->update();
            if (!$bool) {
                throw new Exception('MerchantIndex更新失败!');
            }

            $MemberDashboard = MemberDashboard::where([['mid', '=', $data['mid']]]);
            if ($data['type'] == 'bet') {
                $MemberDashboard->dec('user_profit', $data['money'])
                    ->inc('all_bet', $data['money'])
                    ->inc('game_bet');
            } else {
                $MemberDashboard->inc('user_profit', $data['money'])
                    ->inc('all_prize', $data['money']);
            }
            //死锁
            $bool = $MemberDashboard->update();
            if (!$bool) {
                throw new Exception('MemberDashboard更新失败!');
            }
            $MemberIndex = MemberIndex::where([['mid', '=', $data['mid']]]);
            if ($data['type'] == 'bet') {
                $MemberIndex->dec('win', $data['money'])
                    ->dec('daywin', $data['money'])
                    ->inc('bet_count');
            } else {
                $MemberIndex->inc('win', $data['money'])
                    ->inc('daywin', $data['money']);
            }
            $bool = $MemberIndex->update();
            if (!$bool) {
                throw new Exception('MemberIndex更新失败!');
            }
            //系统更新
            $SystemDay = SystemDay::where('date', date('Y-m-d'))->find();
            if (!$SystemDay) {
                $SystemDay       = new SystemDay();
                $SystemDay->date = date('Y-m-d');
            }
            if ($data['type'] == 'bet') {
                $SystemDay->day_profit -= $data['money'];
            } else {
                $SystemDay->day_profit += $data['money'];
            }
            $bool = $SystemDay->save();
            if (!$bool) {
                throw new Exception('SystemDay更新失败!');
            }

            /** 个人处理 **/
            $MemberDay = MemberDay::where([
                ['mid', '=', $data['mid']],
                ['date', '=', date('Y-m-d')],
            ])->find();
            if (!$MemberDay) {
                $MemberDay       = new MemberDay();
                $MemberDay->mid  = $data['mid'];
                $MemberDay->date = date('Y-m-d');
                $MemberDay->day_profit = 0;
                $MemberDay->day_bet = 0;
            }
            if ($data['type'] == 'bet') {
                $MemberDay->day_profit -= $data['money'];
            } else {
                $MemberDay->day_profit += $data['money'];
            }
            $MemberDay->day_bet       += $data['money'];
            $MemberDay->day_bet_count += 1;
            $bool                     = $MemberDay->save();
            if (!$bool) {
                throw new Exception('MemberDay更新失败!:>>' . json_encode($bool));
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            var_dump(json_encode($e->getMessage()));
            var_dump(json_encode($e->getTraceAsString()));
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

    /**
     * 开奖分发
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function pushAward(array $data){
        GameEventBet::where([['list_id', '=', $data['id']], ['is_ok', '=', 0], ['bet', '<>', $data['open']]])->save([
            'is_ok'     => 2,
            'open_time' => time(),
            'remark'    => $data['remark'],
        ]);
        $GameEventBetItems = GameEventBet::where([['list_id', '=', $data['id']], ['is_ok', '=', 0]])->order('id asc')->select();
        if ($GameEventBetItems && count($GameEventBetItems) > 0) {
            foreach ($GameEventBetItems as $gameEventBetItem) {
                if ($gameEventBetItem->bet == $data['open']) {
                    $queueData = [
                        'task' => 'queueAwardMember', //标识 暂时不使用
                        'data' => [
                            'mid'       => $gameEventBetItem->mid,//会员ID
                            'bet_id'    => $gameEventBetItem->id,//投注ID,
                            'open'      => $data['open'],//当期开奖,
                            'bet'       => $gameEventBetItem->bet,//当期投注涨跌,
                            'remark'    => $data['remark'],//当期开奖价格,
                            'type'      => $gameEventBetItem->type,//当期真是投注还是模拟投注,
                            'odds'      => $gameEventBetItem->odds,//当期赔率,
                            'list_type' => $gameEventBetItem->cycle,
                            'money'     => $gameEventBetItem->money,
                        ]
                    ];
                    queue(queueAwardMember::class,
                        $queueData,
                        31,
                        $queueData['task']
                    );
                }
            }
            return true;
        } else {
            return true;
        }
    }


    /**
     * 投注统计
     * @return bool
     */
    private static function pushBetStatistics(array $data):bool
    {
        //订单信息
        $data = [

        ];
        return true;
    }


    /**
     * @param array $data
     * @return bool
     */
    private static function pushTest(array $data)
    {
        // 启动事务
        Db::startTrans();
        try {
            $bool = MemberWallet::where('mid', $data['mid'])->inc('cny', $data['money'])
                ->update();
            if (!$bool) {
                throw new Exception('资金失败!');
            }
            $bool = MemberWallet::where('mid', $data['mid'])->inc('eth', $data['money'])
                ->update();
            if (!$bool) {
                throw new Exception('佣金失败!');
            }
            var_dump(json_encode($data));
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            var_dump(json_encode($e->getMessage()));

            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;

    }

    /**
     * @param array $data
     * @return bool
     */
    private static function push(array $data)
    {
        // 启动事务
        Db::startTrans();
        try {
            $bool = MemberWallet::where('mid', $data['mid'])->inc('cny', $data['money'])
                ->update();
            if (!$bool) {
                throw new Exception('资金失败!');
            }
            $bool = MemberWallet::where('mid', $data['mid'])->inc('eth', $data['money'])
                ->update();
            if (!$bool) {
                throw new Exception('佣金失败!');
            }
            var_dump(json_encode($data));
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            var_dump(json_encode($e->getMessage()));

            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;

    }

}