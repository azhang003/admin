<?php

namespace app\common\command;


use app\common\controller\member\Redis;
use Exception;
use Swoole\Event;
use Swoole\Timer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Kline1d extends Command
{
    protected function configure()
    {
        $this->setName('Kline1d')->setDescription("计划任务 修改K线");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        Timer::tick(1 * 1000, function () {
            var_dump(date('Y-m-d h:i:s'));
            $this->pays();

        });

        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d H:i:s') . '任务结束!');
        Event::wait();
    }
 /**
     *
     */
    public function pays()
    {
        try {
            $redis = Redis::redis();
            $nowGame = $redis->keys('iscontrol:*');
//            $nowGame = $redis->keys('nowEventList:*');
            foreach ($nowGame as $item){
                $data = json_decode($redis->get($item), true);
                $game = explode(':',$item);
                if (!empty($data)) {
                    $kline = json_decode($redis->get('kline:' . $game['1'] . '_' . $game['2'] . ''), true);
                    $range = explode('|', get_config('game', 'game', 'range'));
                    $rate = (rand($range[0], $range[1])) / 100000;
                    $now_time = ($data['endtime'] - time());
                    $begin_time = (time() - $data['begintime']);
                    if ($data['remark'] > 0 && $begin_time<6 && $begin_time>6) {
                        $kline['o'] = $kline['o'] + ($data['remark'] - $kline['o']) * $begin_time / 5;
                        if ((time() - $data['endtime'])> 0){
                            $kline['t'] = $data['begintime'].'000';
                        }
                    }
                    $close = $kline['c'];
                    if ($now_time < 15 && $now_time>0) {
                        switch ($data['type']) {
                            case 1:
                                $close = $kline['o'] * (1 + $rate);
                                break;
                            case 2:
                                $close = $kline['o'] * (1 - $rate);
                                break;
                        }
                        $close = $kline['c'] + ($close - $kline['c']) * (15 - $now_time) / 15;
                    }
//                    var_dump('---------------------------------------------');
//                    var_dump('---------------------------------------------');
//                    var_dump($close);
//                    var_dump($kline['c']);
//                    var_dump($kline['o']);
//                    var_dump('---------------------------------------------');
//                    var_dump('---------------------------------------------');
                    $kline['c'] = $close;
                    $redis->set('klines:' . $game['1'] . '_' . $game['2'] . '', json_encode($kline));
                }
            }
            /*提交事务*/
            echo "---------- <br>";
            $redis->close();
        } catch (Exception $e) {
            /*回滚事务操作*/
            var_dump('---------------------------------------------');
            var_dump($e->getTrace());
            var_dump($e->getMessage());
            var_dump('---------------------------------------------');
        }
    }
}
