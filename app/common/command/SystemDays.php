<?php

namespace app\common\command;


use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDay;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberWithdrawOrder;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\model\SystemDay;
use app\common\service\Integration;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class SystemDays extends Command
{
    protected function configure()
    {
        $this->setName('SystemDays')->setDescription("计划任务 每日数据统计");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        try {
            /*执行主体*/
            $this->adddata();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        };
        /*** 这里写计划任务列表集 END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
    }

    public function adddata()
    {
        $day = 86400*0;
        $time = strtotime(date('Y-m-d'))-$day;
        $end_time = strtotime(date('Y-m-d') . ' 24:00:00')-$day;
        $Balance['day_withdraw_member'] = MemberWithdrawOrder::where([['status', '=', '1'], ['create_time', '>', $time], ['create_time', '<', $end_time]])->group('mid')->count();
        $Balance['day_withdraw_money'] = MemberWithdrawOrder::where([['status', '=', '1'], ['create_time', '>', $time], ['create_time', '<', $end_time]])->sum('money');
        $Balance['day_fee'] = MemberWithdrawOrder::where([['status', '=', '1'], ['create_time', '>', $time], ['create_time', '<', $end_time]])->sum('fee');
        $Balance['day_recharge_member'] = MemberPayOrder::where([['status', '=', '1'], ['create_time', '>', $time], ['create_time', '<', $end_time]])->count();
        $Balance['day_recharge_money'] = MemberPayOrder::where([['status', '=', '1'], ['create_time', '>', $time], ['create_time', '<', $end_time]])->sum('number');
        $Balance['day_freeze_money'] = MemberWithdrawOrder::where([['examine', '=', '0'], ['create_time', '>', $time], ['create_time', '<', $end_time]])->sum('money');
        $Balance['day_freeze_member'] = MemberWithdrawOrder::where([['examine', '=', '0'], ['create_time', '>', $time], ['create_time', '<', $end_time]])->group('mid')->count();
        $Balance['day_minging'] = abs(MemberRecord::where([['business', '=', 14], ['currency', '=', 1], ['create_time', '>', $time], ['create_time', '<', $end_time]])->sum('now'));
        $Balance['day_team'] = abs(MemberRecord::where([['business', '=', 13], ['currency', '=', 1], ['create_time', '>', $time], ['create_time', '<', $end_time]])->sum('now'));
        $Balance['day_profit'] = (abs(MemberRecord::where([['currency', '=', 1], ['business', '=', 4], ['create_time', '>', $time], ['create_time', '<', $end_time]])->sum('now'))
            - abs(MemberRecord::where([['currency', '=', 1], ['business', '=', 3], ['create_time', '>', $time], ['create_time', '<', $end_time]])->sum('now')));
        $Balance['create_time'] = time();
        $Balance['date'] = date('Y-m-d', time()-$day);
        $data = (new SystemDay())->where('date',$Balance['date'])->find();
        if(empty($data)){
            (new SystemDay())->save($Balance);
        }else{
            (new SystemDay())->where('date',$Balance['date'])->save($Balance);
        }
    }

}