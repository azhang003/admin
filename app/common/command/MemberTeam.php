<?php

namespace app\common\command;


use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDay;
use app\common\model\MemberPayOrder;
use app\common\model\MemberProfile;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\service\Integration;
use app\job\queueTeams;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class MemberTeam extends Command
{
    protected function configure()
    {
        $this->setName('MemberTeam')->setDescription("计划任务 用户每日数据统计");
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
        $pushBonusData = [
            'task' => 'queueTeams', //任务
            'data' => [
                "mid"   => $uid, //会员ID
            ]
        ];
        queue(queueTeams::class, $pushBonusData, 0, 'queueTeams');

    }

    public function waitingWithdraw()
    {
//        $user = [794391,766616,901813,559093,780104, 422055, 679093, 859679, 596334, 306266, 874118, 852702, 685332, 894973];
        $user = [901494];
        foreach ($user as $item) {
            $this->adddata($item);
        }
    }
}