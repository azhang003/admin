<?php

namespace app\common\command;


use app\common\controller\member\Redis;
use app\common\model\GameEventBet;
use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use app\common\model\MemberAccount;
use app\common\model\MemberLogin;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberWithdrawOrder;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\model\MerchantDay;
use app\common\model\MerchantProfile;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class MemberTest extends Command
{
    protected function configure()
    {
        $this->setName('MemberTest')->setDescription("计划任务 用户数据统计");
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

    public function waitingWithdraw()
    {
        $user = GameEventList::where('open_price = "0" and open > "0"')->select();
        foreach ($user as $item){
            $range = explode('|', get_config('game', 'game', 'range'));
            $rate  = (rand($range[0], $range[1])) / 100000;
            if ($item->open == 2){
                $item->open_price = $item->remark * (1 + $rate);
            }else{
                $item->open_price = $item->remark * (1 - $rate);
            }
            $item->save();
        }
    }
}