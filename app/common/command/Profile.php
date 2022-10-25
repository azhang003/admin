<?php

namespace app\common\command;

use app\common\model\GameEventBet;
use app\common\model\GameEventList;
use Exception;
use Swoole\Event;
use Swoole\Timer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Profile extends Command
{
    protected function configure()
    {
        $this->setName('Profile')->setDescription("计划任务 统计单期盈亏");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        Timer::tick(5 * 1000, function () {
            var_dump(date('Y-m-d h:i:s'));
            $this->checkBlock();
        });
        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d H:i:s') . '任务结束!');
        Event::wait();
    }

    public function checkBlock()
    {
        $item = GameEventList::where([['open','>','0']])->whereNull('open_profile')->order('id desc')->limit(999)->select();
        if (!empty($item)) {
            $item = $item->toArray();
            foreach ($item as $items) {
                //开启事务操作
                $this->pays($items);
            }
        } else {
            var_dump('暂无场次!');
        }
    }

    public function pays($item)
    {
        try {
            $bet = GameEventBet::where([
                ['list_id', '=', $item['id']],
                ['type', '=', 0],
            ])->sum('money');
            $is_ok = GameEventBet::where([
                ['list_id', '=', $item['id']],
                ['type', '=', 0],
                ['is_ok', '=', 1],
            ])->sum('money');
            $money = ($is_ok*1.95-$bet);
            $bool = GameEventList::where([['id','=',$item['id']]])->update([
                'open_profile' =>$money
            ]);
            if (!$bool){
                var_dump('添加openProfile失败');
            }
        } catch (Exception $e) {
            var_dump($e->getTrace());
        }
    }

}
