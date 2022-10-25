<?php

namespace app\common\command;


use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
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


class Date extends Command
{
    protected function configure()
    {
        $this->setName('date')->setDescription("计划任务 数据统计");
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
        $time = strtotime(date("Y-m-d", time())) + 7 * 3600;
        $Balance['uid'] = $uid;
        $yedata = MerchantDay::where('uid', $uid)->where('date', date('Y-m-d', (time() - 86400)))->order('id desc')->find();
        $Balance['share'] = MerchantDashboard::where('uid', $uid)->value('share');
        if (!empty($yedata['share'])) {
            $Balance['share'] = $Balance['share'] - $yedata['share'];
        }
        //当日注册
        $mid = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%'], ['create_time', '>', $time], ['analog', '=', 0]])->column('id');
        //所有线下
        $mids = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%'], ['analog', '=', 0]])->column('id');
        $Balance['team_member'] = count($mid);
        $Balance['team_valid_member'] = GameEventBet::where([['mid', 'in', $mid], ['create_time', '>', $time]])->group('mid')->count('id');
        $Balance['team_active'] = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%'], ['login_time', '>', $time], ['analog', '=', 0]])->count();
        $Balance['team_one'] = abs(MemberRecord::where([['mid', 'in', $mids], ['business', '=', 3], ['currency', '=', 1], ['time', '=', '1m'], ['create_time', '>', $time]])->sum('now'))
            - abs(MemberRecord::where([['mid', 'in', $mids], ['time', '=', '1m'], ['business', '=', 4], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now'));
        $Balance['team_five'] = abs(MemberRecord::where([['mid', 'in', $mids], ['business', '=', 3], ['currency', '=', 1], ['time', '=', '5m'], ['create_time', '>', $time]])->sum('now'))
            - abs(MemberRecord::where([['mid', 'in', $mids], ['time', '=', '5m'], ['business', '=', 4], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now'));
        $recharge = MemberPayOrder::where([['mid', 'in', $mids], ['status', '=', 1], ['create_time', '>', $time]])->field('mid,number')->group('mid')->select();
        $Balance['team_first'] = 0;
        $Balance['team_first_member'] = 0;
        foreach ($recharge as $value) {
            $recharges = MemberPayOrder::where([['mid', '=', $value->mid], ['status', '=', 1], ['create_time', '<', strtotime(date('Y-m-d'))]])->count();
            if ($recharges < 1) {
                $Balance['team_first'] += $value->number;
                $Balance['team_first_member']++;
            }
        }
        $Balance['team_recharge'] = abs(MemberRecord::where([['mid', 'in', $mids], ['business', '=', 1], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now'));
        $Balance['team_recharge_member'] = MemberRecord::where([['mid', 'in', $mids], ['business', '=', 1], ['currency', '=', 1], ['create_time', '>', $time]])->group('mid')->count();
        $Balance['team_withdraw'] = abs(MemberWithdrawOrder::where([['mid', 'in', $mids], ['examine', '=', 1], ['create_time', '>', $time]])->sum('money'));
        $Balance['team_withdraw_fee'] = abs(MemberWithdrawOrder::where([['mid', 'in', $mids], ['examine', '=', 1], ['create_time', '>', $time]])->sum('fee'));
        $Balance['team_withdraw_member'] = MemberRecord::where([['mid', 'in', $mids], ['business', '=', 2], ['currency', '=', 1], ['create_time', '>', $time]])->group('mid')->count();
        $Balance['team_transfer'] = abs(MemberRecord::where([['mid', 'in', $mids], ['business', '=', 11], ['create_time', '>', $time]])->sum('now'));
        $Balance['team_record'] = abs(MemberRecord::where([['mid', 'in', $mids], ['business', '=', 3], ['currency', '=', 1], ['create_time', '>', $time]])->sum('now'));
        $Balance['team_five_bet'] = MemberRecord::where([['mid', 'in', $mids], ['business', '=', 3], ['currency', '=', 1], ['create_time', '>', $time]])
            ->group('mid')
            ->having('count(mid)>5')
            ->count();
        $Balance['team_rate'] = empty($mids) ? 0 : $Balance['team_recharge_member'] / count($mids);
        $Balance['team_first_rate'] = empty($mid) ? 0 : $Balance['team_first_member'] / count($mid);
        $second_retention = MemberAccount::where([['id', 'in', $mids], ['create_time', '<', $time - 2 * 86400]])->column('id');
        $three_retention = MemberAccount::where([['id', 'in', $mids], ['create_time', '<', $time - 3 * 86400]])->column('id');
        $seven_retention = MemberAccount::where([['id', 'in', $mids], ['create_time', '<', $time - 7 * 86400]])->column('id');
        $fourteen_retention = MemberAccount::where([['id', 'in', $mids], ['create_time', '<', $time - 14 * 86400]])->column('id');
        $thirty_retention = MemberAccount::where([['id', 'in', $mids], ['create_time', '<', $time - 30 * 86400]])->column('id');
        $Balance['team_second_retention'] = empty($second_retention) ? 0 : (MemberAccount::where([['id', 'in', $second_retention], ['login_time', '>', $time]])->count() / count($second_retention));
        $Balance['team_three_retention'] = empty($three_retention) ? 0 : (MemberAccount::where([['id', 'in', $three_retention], ['login_time', '>', $time]])->count() / count($three_retention));
        $Balance['team_seven_retention'] = empty($seven_retention) ? 0 : (MemberAccount::where([['id', 'in', $seven_retention], ['login_time', '>', $time]])->count() / count($seven_retention));
        $Balance['team_fourteen_retention'] = empty($fourteen_retention) ? 0 : (MemberAccount::where([['id', 'in', $fourteen_retention], ['login_time', '>', $time]])->count() / count($fourteen_retention));
        $Balance['team_thirty_retention'] = empty($thirty_retention) ? 0 : (MemberAccount::where([['id', 'in', $thirty_retention], ['login_time', '>', $time]])->count() / count($thirty_retention));
        $Balance['create_time'] = time();
        //统计
        $Balance['date'] = date('Y-m-d', time());
        $daaaa = MerchantDay::where('uid', $uid)->where('date', $Balance['date'])->find();
        if (empty($daaaa)) {
            MerchantDay::insert($Balance);
        } else {
            MerchantDay::where('uid', $uid)->where('date', $Balance['date'])->save($Balance);
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