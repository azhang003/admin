<?php

namespace app\common\command\update;

use app\common\controller\member\Redis;
use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use app\common\model\MemberAccount;
use app\job\queueUpdate;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class updateMemberData extends Command
{


    protected function configure()
    {
        $this->setName('commandGameSelect')->setDescription("计划任务 更新会员信息及其他事项!")
            ->addOption('instruction', 'i', Option::VALUE_REQUIRED, '指令不存在', 0)
            ->addOption('mid', 'm', Option::VALUE_REQUIRED, '会员ID', 0);
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        $instruction = $input->getOption('instruction');
        if (empty($instruction)) {
            echo "指令不存在!";
            return;
        }
        /** 指定会员更新佣金 **/
        if ("updateCommission" == $instruction) {
            $mid = $input->getOption('mid');
            if (empty($mid)) {
                echo "会员ID不存在!";
                return false;
            }
            if ($this->$instruction($mid)) {
                echo "执行成功!";
            }
            return;
        }

        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
    }

    public function updateCommission($mid)
    {
        $account = MemberAccount::field('id,agent_line')->where('id', $mid);
//        if ($mid !== "all") {
//            $account->where('id', $mid);
//        }
        $account = $account->find();
        if (!empty($account)) {
            queue(queueUpdate::class, [
                'task' => "updateCommission",
                'data' => [
                    "agent_line" => agent_line_array($account->agent_line),
                    "mid"        => $account->id
                ],
            ], 0, "updateCommission");
        } else {
            var_dump('会员不存在!');
            return false;
        }
        return true;
    }

}