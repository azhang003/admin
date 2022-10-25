<?php

namespace app\common\command;

use app\common\controller\member\Redis;
use app\common\model\GameEventBet;
use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use Swoole\Event;
use Swoole\Timer;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class commandGameSelect extends Command
{
    private $Currery = [];
    private $cycle = [
            '1m'  => 60,
            '5m'  => 300,
            '15m' => 900,
            '30m' => 1800,
            '1h'  => 3600,
            '1d'  => 86400,
        ];

    protected function configure()
    {
        $this->setName('commandGameSelect')->setDescription("计划任务 当前开始投注的赛事筛选并分类!")
            ->addOption('cycle', 'c', Option::VALUE_REQUIRED, '周期', 0)
            ->addOption('instruction', 'i', Option::VALUE_REQUIRED, '周期', 'writeEventAll');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        $instruction              = $input->getOption('instruction');
        $this->$instruction();
        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
    }

    public function writeEventAll()
    {
        $start = microtime(true);

        $this->Currery = GameEventCurrency::CurreryAll();
        $redis          = Redis::redis();
        $GameEventLists = GameEventList::field('id,cid,end_time,seal_time,begin_time,type')->order('id asc')
            ->select();
        $end1 = microtime(true);
        var_dump("数据查询完毕". ($end1 - $start));
        foreach ($GameEventLists as $gameEventList) {
            $title       = strtolower(str_replace('/', '', $this->Currery[$gameEventList->cid]['title']));
            $all_key     = 'eventlist_all:' . $title . ':' . $gameEventList->type;
            $sealing_key = 'eventlist_sealing:' . $title . ':' . $gameEventList->type;
            $award_key   = 'eventlist_award:' . $title . ':' . $gameEventList->type;
            $redis->zAdd($all_key, $gameEventList->begin_time, json_encode($gameEventList));
            $redis->zAdd($sealing_key, $gameEventList->seal_time, json_encode($gameEventList));
            $redis->zAdd($award_key, $gameEventList->end_time, json_encode($gameEventList));
        }
        $redis->close();
        $end = microtime(true);
        var_dump("数据写入完毕". ($end - $start));
    }
    public function writeEventAward()
    {
        $start = microtime(true);

        $this->Currery = GameEventCurrency::CurreryAll();
        $redis          = Redis::redis();
        $GameEventLists = GameEventList::field('id,cid,end_time,seal_time,begin_time,type')->order('id asc')
            ->select();
        $end1 = microtime(true);
        var_dump("数据查询完毕". ($end1 - $start));
        foreach ($GameEventLists as $gameEventList) {
            $title       = strtolower(str_replace('/', '', $this->Currery[$gameEventList->cid]['title']));
            $all_key     = 'eventlist_all:' . $title . ':' . $gameEventList->type;
            $sealing_key = 'eventlist_sealing:' . $title . ':' . $gameEventList->type;
            $award_key   = 'eventlist_award:' . $title . ':' . $gameEventList->type;
            $redis->zAdd($all_key, $gameEventList->begin_time, json_encode($gameEventList));
            $redis->zAdd($sealing_key, $gameEventList->seal_time, json_encode($gameEventList));
            $redis->zAdd($award_key, $gameEventList->end_time, json_encode($gameEventList));
        }
        $redis->close();
        $end = microtime(true);
        var_dump("数据写入完毕". ($end - $start));
    }
    /**
     * @param int $start 取最新几期
     * @param int $end 取上几期
     * @param int|null $time
     * @param $type
     * @return array
     */
    public function getType(int $start = 0, int $end = 1, int $time = null, $type = '1m')
    {
        $cycleArr = [
            '1m'  => 60,
            '5m'  => 300,
            '15m' => 900,
            '30m' => 1800,
            '1h'  => 3600,
            '1d'  => 86400,
        ];
        if (!$time){
            $time = time();
        }
        $cycle = $cycleArr[$type];
        $num  = intval($time / $cycle);
        $time = $num * $cycle;
        return [
            'num'  =>  $num,
            'type'  => $type,
            'time' => (string) $time,
            'begin_time' => (string) $time + $start * $cycle,
            'last_rime' =>(string)  $time -  $end * $cycle
        ];
    }

    public function selectOrder($getType)
    {
        var_dump($getType);
        $redisData = Redis::redis()->zRangeByScore('eventlist_all:APE/USDT:1m',$getType['last_rime'],$getType['begin_time']);//ZRANGEBYSCORE
        var_dump($redisData);
    }

}