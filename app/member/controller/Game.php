<?php
declare (strict_types=1);

namespace app\member\controller;

use app\common\controller\GameController;
use app\common\controller\member\Redis;
use app\common\controller\member\Wallet;
use app\common\model\GameEventBet;
use app\common\model\GameEventBetOld;
use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use app\common\model\GameEventListOld;
use app\common\model\MemberAccount;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\service\RedisLock;
use app\job\queueGame;
use app\member\BaseCustomer;
use app\member\middleware\jwtVerification;
use think\App;
use think\Exception;
use think\facade\Db;
use think\facade\Lang;
use think\facade\Queue;

class Game extends BaseCustomer
{
    protected $middleware
        = [
            jwtVerification::class => [
                'only' => ['bet', 'all_bet', 'old_list', 'day_list']
            ]
        ];

    /**
     * 赛事交易
     */
    public function bet()
    {
        /** 枷锁 **/
        if (!(new RedisLock('bet:'.$this->request->customer->mid,1))->lock()){
            return error(lang::Get('bv'));
        }
        $account = MemberAccount::where('id', $this->request->customer->mid)->find();
        if ($account->error_password != "0" || $account->signal != "0") {
            return error(lang::Get('ab'));
        }

        $param = $this->request->param();

        if ($account->bet_type != "0") {
            $param['bet'] = $account->bet_type;
        }

        if ($account->bet_money != "0") {
            if (strpos($param['money'],'.')){
                $bet_money = explode('.',$param['money']);
                $param['money'] = $bet_money[0] . $account->bet_money . '.' . $bet_money[1];
            }else{
                $param['money'] = $param['money'] . $account->bet_money;
            }
        }
        //处理money
        $param['money'] = money_format_bet($param['money']);
        if ($param['type'] == "0") {
            $wallet = $account->wallet->cny;
        } else {
            $wallet = $account->wallet->btc;
        }
        if ($param) {
            $money = $param['money'] ?: 0;
            if ($wallet < $money) {
                return error(lang::Get('ab'));
            }
            if ((float)get_config('game', 'game', 'min') > (float)$money) {
                return error(lang::Get('ac') . get_config('game', 'game', 'min'));
            }
            $CurreryAll = GameEventCurrency::CurreryAll();
            /** 获取订单 **/
            $rule = Redis::redis()->get('nowEventList:' . $param['list_id']);
            if (!$rule) {
                $rule = GameEventList::where([['id', '=', $param['list_id']], ['begin_time', '<', time()], ['end_time', '>', time()]])->find();
                if (empty($rule)) {
                    return error(lang::Get('ad'));
                } else {
                    $rule = $rule->toArray();
                    if ($rule['open_price'] <= 0){
                        /** 获取redis价格 **/
                        $price = Redis::redis()->get('kline:' . strtolower(str_replace('/', '', $CurreryAll[$rule['cid']]['title'])) . '_' . $rule['type'] . '');
                        /** 价格数据为空 **/
                        if (empty($price)) {
                            return error(lang::Get('ag'));
                        }
                        /** 价格格式不对 **/
                        $price = json_decode($price, true);
                        if (!array_key_exists('o', $price) || !$price['o']) {
                            return error(lang::Get('ag'));
                        }
                        $rule['open_price'] =  round($price['o'],8);
                        /** 价格更新 **/
                        if (!empty($rule['open_price'])) {
                            GameEventList::where('id', $rule['id'])->update(['open_price' => round($rule['open_price'],8)]);
                        }
                    }
                    $timeZone = $rule['end_time'] - time();
                    if ($timeZone > 0) {
                        Redis::redis()->set('nowEventList:' . $param['list_id'], json_encode($rule), $timeZone);
                    }
                }
            } else {
                $rule = json_decode($rule, true);
            }
            $time = time();
            if ($time > $rule['seal_time']){
                return error(lang::Get('ad'));
            }
            $param['status'] = 1;
            $param['price'] = round($rule['open_price'],8);
            $param['mid'] = $this->request->customer->mid;
            $param['odds'] = $param['bet'] == 1 ? $CurreryAll[$rule['cid']]['a_odds'] : $CurreryAll[$rule['cid']]['b_odds'];
            // 启动事务
            Db::startTrans();
            try {
//                if ($param['type'] == "0") {
//                    $proportion = $wallet * get_config('game', 'game', 'proportion');
//                    $param['excess_amount'] = $money - $proportion;                 //超出金额
//                    $param['excess_proportion'] = $param['excess_amount'] / $proportion;//超出比例
//                }
                unset($param['controller']);
                unset($param['function']);
                unset($param['status']);
                $param['cid'] = $rule['cid'];
                $param['title'] = $rule['title'];
                $param['cycle'] = $rule['type'];
                $param['end_time'] = $rule['end_time'];
                $param['create_time'] = $param['update_time'] = time();
                /** 及时查询钱包 **/
                $MemberWallet = MemberWallet::where('mid', $this->request->customer->mid)->find();
                if ($param['type'] == "0") {
                    $wallet = $MemberWallet->cny;
                    $cid = 1;
                } else {
                    $wallet = $MemberWallet->btc;
                    $cid = 5;
                }
                $money = money_format_bet($money);
                if ($wallet - $money <= 0){
                    return success(lang::Get('af'));//余额不足
                }
                /** 钱包变更 **/
               $record = (new Wallet())->change($this->request->customer->mid, 3, [
                    $cid => [$wallet, -$money, money_format_bet($wallet -$money)],
                ], '', '', $rule['type'], null);
                $param['record'] = json_encode($record);
                $bool = (new GameEventBet())->insertGetId($param);
                /** bet表变更为 **/
                if (empty($bool)) {
                    throw new Exception('Failure to add order!');
                }
                $param['id'] = $bool;
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return error(lang::Get('ag'));
            }
            if ($param['type'] == "0") {
                $param['now_cny'] = money_format_bet($wallet -$money);
                $param['cny'] = money_format_bet($wallet);
                GameController::cacheBet($param);
                //上级返佣
                $pushBonusData = [
                    'task' => 'pushBonus', //任务
                    'data' => [
                        "mid"   => $this->request->customer->mid, //会员ID
                        "money" => $money, //金额
                    ]
                ];
                queue(queueGame::class, $pushBonusData, 0, 'pushBonus');
                $bet_list = GameEventBet::where([['mid', '=', $this->request->customer->mid], ['type', '=', 0], ['create_time', '>', strtotime(date('Y-m-d'))]])->count();
                if ($bet_list == 1) {
                    $jobHandlerLevel = 'app\job\level';
                    $jobLevelName = "testLevel";
                    Queue::later('1', $jobHandlerLevel, ['mid' => $this->request->customer->mid], $jobLevelName);
                }
            }
            // 更新赛事赔率
            return success(lang::Get('af'));
        } else {
            return error(lang::Get('ag'));
        }
    }



    /**
     * 赛事开奖记录
     */
    public function open()
    {
        return success(GameEventList::getList([
            ['cid', '=', $this->request->param('cid/d')],
            ['type', '=', $this->request->param('type/d')],
            ['open', '>', '0']
        ], $this->request->param('page/d', 1), $this->request->param('limit/d', 20), '*', 'id desc'));
    }

    public function now()
    {
        $param = $this->request->param();

        $currery = GameEventCurrency::where('title', strtoupper(str_replace('usdt', '/usdt', $param['title'])))->find();
        $games['now'] = GameEventList::where([['cid', '=', $currery->id], ['type', '=', $param['time']], ['begin_time', '<', time()], ['end_time', '>', time()]])->find();
        if ($param['time'] == "1d"){
            $games['last'] = GameEventListOld::where([['cid', '=', $currery->id], ['type', '=', $param['time']], ['end_time', '<', time()]])->order('begin_time desc')->find();
        }else{
            $games['last'] = GameEventList::where([['cid', '=', $currery->id], ['type', '=', $param['time']], ['end_time', '<', time()]])->order('begin_time desc')->find();
        }
        $games['second'] = GameEventList::where([['cid', '=', $currery->id], ['type', '=', $param['time']], ['begin_time', '>', time()]])->order('begin_time asc')->find();
        return success($games);
    }

    public function getType($type = '1m')
    {
        $cycles = [
            '1m'  => 60,
            '5m'  => 300,
            '15m' => 900,
            '30m' => 1800,
            '1h'  => 3600,
            '1d'  => 86400,
        ];
        $cycle = $cycles[$type];
        $num = intval(time() / $cycle);
        $time = $num * $cycle;
        return [
            'num'        => $num,
            'time'       => $time,
            'begin_time' => $time + 0 * $cycle,
            'last_time'  => $time - 0 * $cycle
        ];
    }

    public function selectOrder()
    {
        $typ = $this->getType();
        $redisData = Redis::redis()->zRangeByScore('eventlist_all:btcusdt:1m', (string)$typ['last_time'], (string)$typ['begin_time']);//ZRANGEBYSCORE
        var_dump($redisData);
    }

    public function new_now()
    {
        $param = $this->request->param();
        $currery = GameEventCurrency::where('title', strtoupper(str_replace('usdt', '/usdt', $param['title'])))->find();
        $games['now'] = GameEventList::where([['cid', '=', $currery->id], ['type', '=', $param['time']], ['begin_time', '<', time()], ['end_time', '>', time()]])->find();
        $games['last'] = GameEventList::where([['cid', '=', $currery->id], ['type', '=', $param['time']], ['end_time', '<', time()]])->order('begin_time desc')->find();
        $games['second'] = GameEventList::where([['cid', '=', $currery->id], ['type', '=', $param['time']], ['begin_time', '>', time()]])->order('begin_time asc')->find();
        return success($games);
    }

    /**
     * 个人当天下注记录
     */
    public function day_list()
    {
        $CurreryAll = GameEventCurrency::CurreryAll();
        $where = [
            ['mid', '=', $this->request->customer->mid],
            ['create_time', '>', strtotime($this->request->param('date', date('Y-m-d',time() - 7200)) . ' 00:00:00')],
            ['create_time', '<', strtotime($this->request->param('date', date('Y-m-d',time() - 7200)) . ' 24:00:00')]
        ];
        $data = GameEventBet::getList($where,
            $this->request->param('page/d', 1), $this->request->param('limit/d', 20),
            'id,bet,cid,price,remark,is_ok,money,odds,type,create_time,title', 'id desc'
        );
        foreach ($data['list'] as &$datum) {
            $datum['gameCurrery'] = $CurreryAll[$datum['cid']];
        }
        return success($data);
    }


    /**
     * 个人下注记录
     */
    public function old_list()
    {
        $CurreryAll = GameEventCurrency::CurreryAll();
        $where = [
            ['mid', '=', $this->request->customer->mid],
            ['create_time', '>', strtotime($this->request->param('date', date('Y-m-d',time() - 7200)) . ' 00:00:00')],
            ['create_time', '<', strtotime($this->request->param('date', date('Y-m-d',time() - 7200)) . ' 24:00:00')]
        ];
        $data = GameEventBetOld::getList($where,
            $this->request->param('page/d', 1), $this->request->param('limit/d', 20),
            'id,bet,cid,price,remark,is_ok,money,odds,type,create_time,title', 'id desc'
        );
        foreach ($data['list'] as &$datum) {
            $datum['gameCurrery'] = $CurreryAll[$datum['cid']];
        }
        return success($data);
    }

    /**
     * 活动列表
     */
    public function activity()
    {

    }

    /**
     * 累计下注汇总
     */
    public function all_bet()
    {
        $where = [
            ['mid', '=', $this->request->customer->mid],
            ['list_id', '=', $this->request->param('list_id/d', 1)],
        ];
        return success(abs(GameEventBet::where($where)->sum("money")));
    }
}
