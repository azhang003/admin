<?php

namespace app\common\command;


use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDay;
use app\common\model\MemberLogin;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberWithdrawOrder;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\model\MerchantProfile;
use app\common\service\Integration;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class MemberIndex extends Command
{
    protected function configure()
    {
        $this->setName('MemberIndex')->setDescription("计划任务 用户数据统计");
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
            var_dump($e->getTrace());
            var_dump($e->getLine());
        };
        /*** 这里写计划任务列表集 END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
    }

    public function adddata($item)
    {
        $aaa['login_ip'] = MemberLogin::where([['mid', '=', $item]])->order('id desc')->value('ip') ?: 0;
        $aaa['login_address'] = MemberLogin::where([['mid', '=', $item]])->order('id desc')->value('address') ?: 0;
        $aaa['repeat'] = MemberLogin::where([['mid', '<>', $item], ['ip', '=', $item['login_ip']]])->order('id asc')->value('ip') ? 1 : 0;
        $aaa['team_count'] = MemberAccount::where([['inviter_line', 'like', "%|" . $item . "|%"]])->count() ?: 0;//团队人数
        $aaa['freeze'] = MemberWithdrawOrder::where([['mid', '=', $item], ['examine', '=', 0]])->sum('money') ?: 0;//提现
        $aaa['daywin'] = abs(MemberRecord::where([['mid', '=', $item], ['business', '=', 4], ['currency', '=', 1], ['create_time', '>', strtotime(date('Y-m-d'))]])->sum('now')) -
        abs(MemberRecord::where([['mid', '=', $item], ['business', '=', 3], ['currency', '=', 1], ['create_time', '>', strtotime(date('Y-m-d'))]])->sum('now')) ?: 0;//净赢
        (new \app\common\model\MemberIndex)->where('mid', $aaa['mid'])->update($aaa);
    }

    public function waitingWithdraw()
    {
        $user = MemberRecord::where('create_time >'.(strtotime(date('Y-m-d'))-86400))->group('mid')->column('mid');
        foreach ($user as $item) {
            $this->adddata($item);
        }
    }
}