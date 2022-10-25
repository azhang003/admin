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


class MemberIndexs extends Command
{
    protected function configure()
    {
        $this->setName('MemberIndexs')->setDescription("计划任务 用户数据统计");
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
        $item = MemberAccount::where('id',$item)->field('id,agent_line,inviter,login_ip')->find()->toArray();
        $agent_line = explode('|', $item['agent_line']);
        $aaa['mid'] = $item['id'];
        $aaa['agent_id'] = MemberAccount::where([['uuid', '=', $item['inviter']]])->value('id') ?: 0;
        $aaa['agent'] = MerchantProfile::where([['uid', '=', $agent_line[count($agent_line) - 2]]])->value('mobile') ?: 0;
        $aaa['allagent'] = MerchantProfile::where([['uid', '=', $agent_line[1]]])->value('mobile') ?: 0;
        $aaa['register_ip'] = MemberLogin::where([['mid', '=', $item['id']]])->order('id asc')->value('ip') ?: 0;
        $aaa['register_address'] = MemberLogin::where([['mid', '=', $item['id']]])->order('id asc')->value('address') ?: 0;
        $aaa['login_ip'] = MemberLogin::where([['mid', '=', $item['id']]])->order('id desc')->value('ip') ?: 0;
        $aaa['login_address'] = MemberLogin::where([['mid', '=', $item['id']]])->order('id desc')->value('address') ?: 0;
        $aaa['repeat'] = MemberLogin::where([['mid', '<>', $item['id']], ['ip', '=', $item['login_ip']]])->order('id asc')->value('ip') ? 1 : 0;
        $aaa['bet_count'] = GameEventBet::where([['mid', '=', $item['id']]])->count() ?: 0;//游戏局数
        $aaa['team_count'] = MemberAccount::where([['inviter_line', 'like', "%|" . $item['id'] . "|%"]])->count() ?: 0;//团队人数
        $aaa['win'] = abs(MemberRecord::where([['mid', '=', $item['id']], ['business', '=', 4], ['currency', '=', 1]])->sum('now')) -
        abs(MemberRecord::where([['mid', '=', $item['id']], ['business', '=', 3], ['currency', '=', 1]])->sum('now')) ?: 0;//净赢
        $aaa['daywin'] = abs(MemberRecord::where([['mid', '=', $item['id']], ['business', '=', 4], ['currency', '=', 1], ['create_time', '>', strtotime(date('Y-m-d'))]])->sum('now')) -
        abs(MemberRecord::where([['mid', '=', $item['id']], ['business', '=', 3], ['currency', '=', 1], ['create_time', '>', strtotime(date('Y-m-d'))]])->sum('now')) ?: 0;//净赢
        $aaa['recharge'] = abs(MemberRecord::where([['mid', '=', $item['id']], ['business', '=', 1]])->sum('now')) ?: 0;//充值
        $aaa['fee'] = abs(MemberRecord::where([['mid', '=', $item['id']], ['business', '=', 15]])->sum('now')) ?: 0;//充值
        $aaa['withdraw'] = MemberWithdrawOrder::where([['mid', '=', $item['id']], ['examine', '=', 1]])->sum('money') ?: 0;//提现
        $aaa['freeze'] = MemberWithdrawOrder::where([['mid', '=', $item['id']], ['examine', '=', 0]])->sum('money') ?: 0;//提现
        $aaa['Transfer_out'] = abs(MemberRecord::where([['mid', '=', $item['id']], ['business', '=', 11]])->sum('now')) ?: 0;//转出
        $aaa['share'] = abs(MemberRecord::where([['mid', '=', $item['id']], ['business', '=', 13], ['currency', '=', 1]])->sum('now')) ?: 0;//分享
        $aaa['ming'] = abs(MemberRecord::where([['mid', '=', $item['id']], ['currency', '=', 1], ['business', '=', 14]])->sum('now')) ?: 0;//挖矿
        $aaa['into'] = abs(MemberRecord::where([['mid', '=', $item['id']], ['business', '=', 10]])->sum('now')) ?: 0;//转入
        $daaaa = \app\common\model\MemberIndex::where('mid', $item['id'])->find();
        if (empty($daaaa)) {
            $aaa['create_time'] = time();
//            var_dump($aaa);
            (new \app\common\model\MemberIndex)->insert($aaa);
        } else {
            (new \app\common\model\MemberIndex)->where('mid', $aaa['mid'])->save($aaa);
        }
    }

    public function waitingWithdraw()
    {
        $user = MemberRecord::where('create_time >'.strtotime(date('Y-m-d')))->group('mid')->column('mid');
        foreach ($user as $item) {
            $this->adddata($item);
        }
    }
}