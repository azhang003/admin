<?php

namespace app\common\command;


use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDay;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\service\Integration;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class MemberTeams extends Command
{
    protected function configure()
    {
        $this->setName('MemberTeams')->setDescription("计划任务 团队余额数据");
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
        $one = MemberAccount::where([['inviter_line', 'like', "%|".$uid->id."|%"]])->column('id');
        array_push($one, $uid->id);
        $data = [
            'money'    => MemberWallet::where([
                ['mid', 'in', $one],
            ])->sum('cny'),
            'rechage'    => \app\common\model\MemberIndex::where([
                ['mid', 'in', $one],
            ])->sum('recharge'),
            'withdraw'    => \app\common\model\MemberIndex::where([
                ['mid', 'in', $one],
            ])->sum('withdraw'),
            'profile'    => \app\common\model\MemberIndex::where([
                ['mid', 'in', $one],
            ])->sum('win'),
            'bet_count'    => \app\common\model\MemberIndex::where([
                ['mid', 'in', $one],
            ])->sum('bet_count'),
            'bet_money'    => \app\common\model\MemberDashboard::where([
                ['mid', 'in', $one],
            ])->sum('all_bet'),
        ];
        \app\common\model\MemberTeam::where('mid', $uid->id)->save($data);
    }

    public function waitingWithdraw()
    {
        $user = MemberAccount::where(1)->select();
        foreach ($user as $item) {
           $this->adddata($item);
        }
    }
}