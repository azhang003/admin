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
use app\common\model\SystemConfig;
use app\common\service\Integration;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\Model;


class MemberDashboard extends Command
{
    protected function configure()
    {
        $this->setName('dashboard')->setDescription("计划任务 用户实时数据统计");
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
        $Balance['user_withdraw'] = abs(MemberWithdrawOrder::where([['mid', '=', $uid], ['examine', '=', 1]])->sum('money'));
        $Balance['fee'] = abs(MemberWithdrawOrder::where([['mid', '=', $uid], ['examine', '=', 1]])->sum('fee'));
        $Balance['user_withdraw_examine'] = abs(MemberWithdrawOrder::where([['mid', '=', $uid], ['examine', '=', 0]])->sum('money'));
        $Balance['cny'] = MemberWallet::where([['mid', '=', $uid]])->value('cny');
        $Balance['probability'] = MemberRecord::where([['mid', '=', $uid], ['business', '=', 4], ['currency', '=', 1]])->count()
            / (MemberRecord::where([['mid', '=', $uid], ['business', '=', 3], ['currency', '=', 1]])->count() ?: 1);
        $Balance['share'] = abs(MemberRecord::where([['mid', '=', $uid], ['business', '=', 9], ['currency', '=', 4]])->sum('now'));
        $MemberAccount = MemberAccount::where([['id', '=', $uid]])->find();
        $one = MemberAccount::where([['inviter', '=', $MemberAccount->uuid]])->select();//一级
        $where1 = empty($one) ? [] : $one->toArray();
        $two = MemberAccount::where([['inviter', 'in', array_column($where1, 'uuid')]])->select();//二级
        $where2 = empty($two) ? [] : $two->toArray();
        $three = MemberAccount::where([['inviter', 'in', array_column($where2, 'uuid')]])->select();//三级
        $where3 = empty($three) ? [] : $three->toArray();
        $Balance['one_quantity'] = count($where1);//一级人数
        $Balance['two_quantity'] = count($where2);//二级人数
        $Balance['three_quantity'] = count($where3);//三级人数
        $Balance['all_member'] = MemberAccount::where([['inviter_line', 'like', "%|" . $uid . "|%"]])->count();//总人数
        \app\common\model\MemberDashboard::where('mid', $uid)->save($Balance);
    }

    public function waitingWithdraw()
    {
        $config = SystemConfig::where([['group', '=', 'dashboard'], ['gname', '=', 'dashboard']])->find();
        $user = MemberRecord::where([
            ['create_time', '>', $config->value],
        ])->group('mid')->select();
        foreach ($user as $item) {
            $this->adddata($item['mid']);
        }
        (new SystemConfig())->where('id', $config->id)->update(['value' => time()]);
    }
}