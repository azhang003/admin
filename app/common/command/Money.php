<?php

namespace app\common\command;


use app\common\controller\member\Wallet;
use app\common\model\MemberAccount;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class Money extends Command
{
    protected function configure()
    {
        $this->setName('red')->setDescription("计划任务 每日复原虚拟账户");
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

    public function waitingWithdraw()
    {
        //更新余额
        $fictitious = get_config('sizzler', 'sizzler', 'fictitious');
        MemberWallet::where(1)->update(['btc' => $fictitious]);
        $money = explode('|', get_config('wallet', 'wallet', 'give'));
        $record = MemberRecord::where([['create_time', '>', (strtotime(date('Y-m-d'))) - 86400], ['business', '=', 12]])->group('mid')->select();
        foreach ($record as $value) {
            if (!empty($value)) {
                $account = MemberAccount::where('id', $value->mid)->find();
                if (!empty($account)) {
                    $wallet = MemberWallet::where([['mid', '=', $value->mid]])->find();
                    //每日赠送
                    (new Wallet())->change($value->mid, 6, [
                        2 => [$wallet->usd, $money[$account->level], $wallet->usd + $money[$account->level]],
                    ]);
                }
            }
        }
    }
}