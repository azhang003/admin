<?php

namespace app\common\command;


use app\common\model\MemberAccount;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MemberWithdrawOrder;
use app\common\model\MerchantRecord;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;


class SystemSummarize extends Command
{
    protected function configure()
    {
        $this->setName('SystemSummarize')->setDescription("计划任务 每日数据统计");
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
        $Balance['recharge_member'] = MemberPayOrder::where([['status', '=', '1']])->group('mid')->count();
        $Balance['draw_member'] = MemberWithdrawOrder::where([['examine', '=', '1']])->group('mid')->count();
        $Balance['all_member'] = MemberAccount::where([['analog', '=', 0]])->count();
        $Balance['login_member'] = MemberAccount::where([['analog', '=', 0], ['login_time', '>', (time() - 60 * 60)]])->count();
        $Balance['all_transfer'] = abs(MemberRecord::where([['business', '=', 11], ['currency', '=', 1]])->sum('now'));
        $Balance['internal_recharge'] = abs(MerchantRecord::where([['business', '=', "3"]])->sum('now'));
        $Balance['manually_count'] = MemberWithdrawOrder::where([['examine', '=', "0"], ['money', '>', get_config('wallet', 'wallet', 'manually')]])->count();
        $Balance['manually_money'] = abs(MemberWithdrawOrder::where([['examine', '=', "1"], ['status', '=', "1"]])->whereNull('rid')->sum('money'));
        $Balance['all_charge'] = round(abs(MemberRecord::where([['business', '=', 15]])->sum('now')), 5);
        $Balance['recharge_number'] = round(MemberPayOrder::where([['status', '=', '1']])->sum('number'), 5);
        $Balance['all_minging'] = round(abs(MemberRecord::where([['business', '=', 14], ['currency', '=', 1]])->sum('now')), 5);
        $Balance['withdraw_number'] = MemberWithdrawOrder::where([['examine', '=', '1']])->sum('money');
        $Balance['all_team'] = round(abs(MemberRecord::where([['currency', '=', 1], ['business', '=', 13]])->sum('now')), 5);
        $Balance['freeze_money'] = MemberWithdrawOrder::where([['examine', '=', '0']])->sum('money');
        $Balance['freeze_member'] = MemberWithdrawOrder::where([['examine', '=', '0']])->group('mid')->count('mid');
        $all_profit = (abs(MemberRecord::where([['currency', '=', 1], ['business', '=', 4]])->sum('now'))
            - abs(MemberRecord::where([['currency', '=', 1], ['business', '=', 3]])->sum('now')));
        $Balance['all_number'] = MemberWallet::where(1)->sum('cny');
        $Balance['internal_recharges'] = abs(MemberRecord::where([['business', '=', "10"], ['currency', '=', 1]])->sum('now'));
        $Balance['charges'] = abs(MemberRecord::where([['business', '=', "6"], ['currency', '=', 1]])->sum('now'));
        $aaa = $Balance['all_number'] + $Balance['all_charge'] + $Balance['withdraw_number'];
        $bbb = $Balance['recharge_number'] + $Balance['all_minging'] + $Balance['all_team'] + $Balance['internal_recharges'] + $Balance['charges'];
        $Balance['all_profit'] = $aaa - $bbb;
        var_dump($all_profit-$Balance['all_profit']);
        $data = (new \app\common\model\SystemSummarize())->where(1)->find();
        if (empty($data)) {
            (new \app\common\model\SystemSummarize())->save($Balance);
        } else {
            (new \app\common\model\SystemSummarize())->where('id', 211)->save($Balance);
        }
    }

}