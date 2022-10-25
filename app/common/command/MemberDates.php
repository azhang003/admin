<?php
namespace app\common\command;


use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDay;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\service\Integration;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class MemberDates extends Command
{
    protected function configure(){
        $this->setName('MemberDates')->setDescription("计划任务 用户每日数据统计");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output){
        $output->writeln(date('Y-m-d h:i:s').'任务开始!');
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
        $output->writeln(date('Y-m-d h:i:s').'任务结束!');
    }
    public function adddata($uid){
//        $time = strtotime(date("Y-m-d",time()-86400))+7*3600;
        $time = strtotime(date("Y-m-d",time()))+7*3600;
        $Balance['mid'] = $uid;
        $mids = $uid;
        $Balance['day_bet_count'] = MemberRecord::where([['mid','=',$mids],['business','=',3],['currency','=',1],['create_time','>',$time]])->count();
        $Balance['day_bet'] = abs(MemberRecord::where([['mid','=',$mids],['business','=',3],['currency','=',1],['create_time','>',$time]])->sum('now'));
        $Balance['day_profit'] = abs(MemberRecord::where([['mid','=',$mids],['business','=',4],['currency','=',1],['create_time','>',$time]])->sum('now'))-$Balance['day_bet'];
        $Balance['create_time'] = time();
        $Balance['date'] = date('Y-m-d',time());
        $daaaa = MemberDay::where('mid',$uid)->where('date',$Balance['date'])->find();
        if (empty($daaaa)){
            MemberDay::insert($Balance);
        }else{
            MemberDay::where('mid',$uid)->where('date',$Balance['date'])->save($Balance);
        }
    }

    public function waitingWithdraw(){
        $user = MemberRecord::where('create_time >'.strtotime(date('Y-m-d')))->group('mid')->column('mid');
        foreach ($user as $item){
            $this->adddata($item);
        }
    }

}