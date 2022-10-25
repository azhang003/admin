<?php

namespace app\common\command;

use app\common\controller\member\Redis;
use app\common\model\GameEventBet;
use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use app\job\queueAward;
use Swoole\Event;
use Swoole\Timer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class commandGameAward extends Command
{
    private $Currery = [];

    protected function configure()
    {
        $this->setName('commandGameAward')->setDescription("计划任务 赛事开奖!");
    }

    protected function execute(Input $input, Output $output)
    {

        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        $this->Currery = GameEventCurrency::CurreryAll();
        /*** 这里写计划任务列表集 START ***/
        Timer::tick(10000, function () {
            $GameEventList = GameEventList::where([
                ['seal_time', '<', time() - 15],
                ['open', '=',0]
            ])->field('id,cid,type,open,seal_time,end_time,open_price,iscontrol')->order('id asc')
                ->limit(60)
                ->select();
            if (!$GameEventList || empty($GameEventList) || $GameEventList->isEmpty()) {
                var_dump('暂无场次!');
                return;
            }
            $this->checkGameEventList($GameEventList);
        });

        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
        Event::wait();
    }

    private function checkGameEventList($GameEventLists)
    {
        foreach ($GameEventLists as $gameEventList) {
            $title = $this->Currery[$gameEventList->cid]['title'];
            /** 获取redis数据 **/
            $redis     = Redis::redis();
            $title = 'kline:' . strtolower(str_replace('/', '', $title)) . '_' . $gameEventList->type . '';
            $klineData = $redis->get($title);
            if (empty($klineData)) {
                var_dump('没有开奖数据1!');
                return;
            }
            $klineData = json_decode($klineData, true);
            if (!array_key_exists('o', $klineData) || !array_key_exists('c', $klineData) || !$klineData['o'] || !$klineData['c']) {
                var_dump('没有开奖数据2!');
                return;
            }
            $updateData = [];
            if (!empty($gameEventList->open_price) && $gameEventList->open_price > 0 ) {
                $remark = $klineData['o'];
                if ($remark == $gameEventList->open_price) {
                    $remark = $klineData['c'];
                }
                $open_price = $gameEventList->open_price;
            } else {
                $open_price               = $klineData['o'];
                $remark                   = $klineData['c'];
                $updateData['open_price'] = round($open_price,8);
            }
            if ($gameEventList->iscontrol > 0) {
                $hero = $gameEventList->iscontrol;
            } else {
                if ($gameEventList->type == "5m" && $gameEventList->cid == "1"){
                    $money = get_config('game','game','btc_total');
                }else{
                    $money = $gameEventList->type == "5m" ? get_config('game', 'game', 'five_total') : get_config('game', 'game', 'total');
                }
                if (get_config('game', 'game', 'open_tu') == "1") {
                    $a_magic = GameEventBet::where([['list_id', '=', $gameEventList->id], ['bet', '=', 1], ['type', '=', 0]])->sum('money') * 1.95;
                    $b_magic = GameEventBet::where([['list_id', '=', $gameEventList->id], ['bet', '=', 2], ['type', '=', 0]])->sum('money') * 1.95;
                    if ($a_magic > $money || $b_magic > $money) {
                        $hero = $a_magic > $b_magic ? 2 : 1;
                    } else {
                        $hero = $remark > $open_price ? 1 : 2;
                    }
                } else {
                    $hero = $remark > $open_price ? 1 : 2;
                }
            }
            /** 统一价格和结果 **/
            $range = explode('|', get_config('game', 'game', 'range'));
            $rate  = (rand($range[0], $range[1])) / 100000;
            if ($hero == "1") {
                $moneys = $remark > $open_price ? $remark : $open_price * (1 + $rate);
            }
            if ($hero == "2") {
                $moneys = $remark < $open_price ? $remark : $open_price * (1 - $rate);
            }

            $updateData['open']   = $hero;
            $updateData['remark'] = round($moneys,8);
            $bool =  GameEventList::where('id',$gameEventList->id)->update($updateData);
            /** 添加控制计算器 **/
            if ($bool){
                $list = $gameEventList;
                $title = GameEventCurrency::CurreryAll()[$list->cid]['title'];
                if ($hero != 0){
                    /** 写入当前期 **/
                    Redis::redis()->set('nowEventList:' . $list->id,
                        json_encode($list->toArray()),
                        ($list->end_time - time()) < 0 ? 5 :($list->end_time - time())
                    );
                    /** 写入控制系统 **/
                    if ($list->end_time + 5 - time() > 0){
                        Redis::redis()->set('iscontrol:' . strtolower(str_replace('/','',$title)) . ':'. $list->type,
                            json_encode([
                                'id' => $list->id,
                                'type' => $hero,// 控涨跌
                                'endtime' => $list->begin_time,
                                'begintime' => $list->end_time,
                                'hard' => 1, // 1 为手动,2为吞吐
                                'remark' => $updateData['remark'], // 0为未开奖  大于0,则需要延时K线回升
                                'title' => $title, // 币种名称对
                                'cycle' => $list->type, // 时间累 1m,5m
                            ]),
                            $list->end_time + 5 - time()
                        );
                    }
                }else{
                    if ($list->end_time + 5 - time() > 0) {
                        Redis::redis()->set('iscontrol:' . strtolower(str_replace('/', '', $title)) . ':' . $list->type,
                            json_encode([
                                'id'        => $list->id,
                                'type'      => $hero,// 控涨跌
                                'endtime'   => $list->begin_time,
                                'begintime' => $list->end_time,
                                'hard'      => 2, // 1 为手动,2为吞吐
                                'remark'    => $updateData['remark'], // 0为未开奖  大于0,则需要延时K线回升
                                'title'     => $title, // 币种名称对
                                'cycle'     => $list->type, // 时间累 1m,5m
                            ]),
                            $list->end_time + 5 - time()
                        );
                    }
                }
                /** 开奖队列待完善--别删除 **/
                $dlaytime = ($list->end_time > time()) ? 0 : (time() - $list->end_time);
                queue(queueAward::class,
                    [
                        'task' => 'queueAward',
                        'data' => [
                            'id'     => $gameEventList->id,
                            'open'   => $hero,
                            'type'   => $gameEventList->type,
                            'remark' => $updateData['remark']
                        ]
                    ],
                    $dlaytime + 1,
                    'queueAward'
                );
            } else {
                var_dump('开奖失败!');
            }
            $redis->close();
        }
    }
}