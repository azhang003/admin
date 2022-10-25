<?php

namespace app\common\command;


use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDay;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MemberWithdrawOrder;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\model\MerchantDay;
use app\common\model\MerchantRecord;
use app\common\service\Integration;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class MerchantIndex extends Command
{
    protected function configure()
    {
        $this->setName('MerchantIndex')->setDescription("计划任务 代理每日数据统计");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        $start = microtime(true);
        try {
            /*执行主体*/
            $this->waitingWithdraw();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        };
        $end = microtime(true);
        var_dump("运行时间>>:" . ($end - $start));
        /*** 这里写计划任务列表集 END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
    }

    public function adddata($uid)
    {
        $item['into'] = abs(MerchantRecord::where([['business', '=', 3], ['uid', '=', $uid]])->sum('now'));
        $item['transfer'] = abs(MerchantRecord::where([['business', '=', 2], ['uid', '=', $uid]])->sum('now'));
        $mid = MemberAccount::where([['agent_line', 'like', "%|" . $uid . "|%"], ['analog', '=', 0]])->column('id');
        $item['uid'] = $uid;
        $item['user'] = count($mid);
        if (empty($mid)){
            $item['game'] = $item['win'] = $item['recharge'] = $item['recharge_member']
                = $item['withdraw'] = $item['withdraw_member'] = $item['all_share'] = $item['in_share']
                = $item['surplus_share'] = $item['ming'] = 0;
        }else{
            $item['game'] = MemberRecord::where([['mid', 'in', $mid], ['business', '=', 3], ['currency', '=', 1]])->count();
            $item['money'] = MemberWallet::where([['mid', 'in', $mid]])->sum('cny');
            $item['win'] = abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 4], ['currency', '=', 1]])->sum('now')) -
                abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 3], ['currency', '=', 1]])->sum('now'));
            $item['recharge'] = round(MemberPayOrder::where([['mid', 'in', $mid], ['status', '=', '1']])->sum('number'), 5);
            $item['recharge_member'] = MemberPayOrder::where([['mid', 'in', $mid], ['status', '=', '1']])->group('mid')->count();
            $item['withdraw'] = MemberWithdrawOrder::where([['mid', 'in', $mid], ['examine', '=', '1']])->sum('money');
            $item['fee'] = MemberWithdrawOrder::where([['mid', 'in', $mid], ['examine', '=', '1']])->sum('fee');
            $item['withdraw_member'] = MemberWithdrawOrder::where([['mid', 'in', $mid], ['examine', '=', '1']])->group('mid')->count();
            $item['all_share'] = abs(MemberRecord::where([['business', '=', 9], ['currency', '=', 4], ['mid', 'in', $mid]])->sum('now'));
            $item['in_share'] = abs(MemberRecord::where([['business', '=', 13], ['currency', '=', 1], ['mid', 'in', $mid]])->sum('now'));
            $item['surplus_share'] = $item['all_share'] - $item['in_share'];
            $item['ming'] = abs(MemberRecord::where([['business', '=', 14], ['currency', '=', 1], ['mid', 'in', $mid]])->sum('now'));
        }
        $daaaa = \app\common\model\MerchantIndex::where('uid', $uid)->find();
        if (empty($daaaa)) {
            \app\common\model\MerchantIndex::insert($item);
        } else {
            \app\common\model\MerchantIndex::where('uid', $uid)->save($item);
        }
    }

    public function waitingWithdraw()
    {
        $user = MerchantAccount::where(1)->column('id');
        foreach ($user as $item) {
            $this->adddata($item);
        }
    }
}