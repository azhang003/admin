<?php

namespace app\common\command;


use app\common\model\MemberAccount;
use app\common\model\MemberRecord;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDay;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;


class MerchantDate extends Command
{
    protected function configure()
    {
        $this->setName('MerchantDate')->setDescription("计划任务 代理每日数据统计");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        try {
            /*执行主体*/
            $this->waitingWithdraw();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        };
        /*** 这里写计划任务列表集 END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
    }

    public function adddata($uid)
    {
        $Balance['date'] = date('Y-m-d', time());
        $time = strtotime($Balance['date']);
        $Balance['uid'] = $uid;
        $mid = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%']])->column('id');
        if (!empty($mid)) {
            $Balance['team_member'] = count($mid);
            $Balance['team_valid_member'] = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%'], ['create_time', '>', $time]])->count();
            $Balance['share'] = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%'], ['create_time', '>', $time]])->count();
            $Balance['team_active'] = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%'], ['login_time', '>', $time]])->count();
            $Balance['team_one'] = abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 4], ['time', '=', '1m'], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now'))
                - abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 3], ['time', '=', '1m'], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now'));
            $Balance['team_five'] = abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 4], ['time', '=', '5m'], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now'))
                - abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 3], ['time', '=', '5m'], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now'));
            $Balance['team_five_bet'] = MemberRecord::where([['mid', 'in', $mid], ['business', '=', 3], ['currency', '=', 1], ['create_time', '>', $time]])
                ->having('count(mid)>5')
                ->count();
            $Balance['day_bet_count'] = MemberRecord::where([['mid', 'in', $mid], ['business', '=', 3], ['currency', '=', 1], ['create_time', '>', $time]])->count();
            $Balance['day_bet'] = abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 3], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now'));
            $Balance['day_profit'] = abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 4], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now')) - $Balance['day_bet'];
        } else {
            $Balance['team_member'] = $Balance['team_valid_member'] = $Balance['share'] = $Balance['team_active']
                = $Balance['team_one'] = $Balance['day_bet_count'] = $Balance['day_bet'] = 0;
        }
        $Balance['create_time'] = time();
        $daaaa = MerchantDay::where('mid', $uid)->where('date', $Balance['date'])->find();
        if (empty($daaaa)) {
            MerchantDay::insert($Balance);
        } else {
            MerchantDay::where('uid', $uid)->where('date', $Balance['date'])->update($Balance);
        }
    }

    public function waitingWithdraw()
    {
        $user = MerchantAccount::where('status = 1')->select();
        foreach ($user as $item) {
            $this->adddata($item['id']);
        }
    }
}