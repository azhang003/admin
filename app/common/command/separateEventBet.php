<?php

namespace app\common\command;


use app\common\model\GameEventBet;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class separateEventBet extends Command
{
    protected function configure()
    {
        $this->setName('separateEventBet')->setDescription("计划任务 交易记录分表");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        var_dump(date('Y-m-d h:i:s'));
        $this->checkBlock();
        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
    }

    public function checkBlock()
    {
        /** 检查是否需要分表 **/

        $is_separate = (new GameEventBet())->whereLike('title', '%' . date('Ymd', time() - 86400) . '%')->find();
        if (!$is_separate) {
            var_dump('已经分表了!');
            return;
        }

        /** 备份旧表 **/
        $back_bool = Db::execute("create table ea_game_event_bet_" . date('Ymd', time() - 86400) ." select * from ea_game_event_bet where title LIKE '%".date('Ymd', time() - 86400)."%'");
        if (!$back_bool){
            var_dump('备份失败!');
            return;
        }
        /** 将当日交易插入总表 **/
        $insert_bool = Db::execute("insert into ea_game_event_bet_old select * from ea_game_event_bet_" . date('Ymd', time() - 86400));
        if (!$insert_bool){
            var_dump('数据插入失败!');
            return;
        }
        /** 清除昨日备份旧表 **/
        Db::execute("drop table ea_game_event_bet_" . date('Ymd', time() - 86400 * 2));
//
        /** 清空历史 **/
        $is_delete = (new GameEventBet())->whereLike('title', '%' . date('Ymd', time() - 86400) . '%')->delete();
        if (!$is_delete){
            var_dump('清空历史数据!');
        }

    }
}
