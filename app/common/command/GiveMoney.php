<?php
namespace app\common\command;


use app\common\controller\member\Wallet;
use app\common\model\MemberAccount;
use app\common\model\MemberRecord;
use app\common\model\MemberTiming;
use app\common\model\MemberWallet;
use Exception;
use Swoole\Event;
use Swoole\Timer;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class GiveMoney extends Command
{
    protected function configure(){
        $this->setName('GiveMoney')->setDescription("计划任务 处理延迟充值订单");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        Timer::tick(5 * 1000, function () {
            var_dump(date('Y-m-d h:i:s'));
            $this->checkBlock();
        });
//        var_dump(date('Y-m-d h:i:s'));
//        $this->checkBlock();
        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d H:i:s') . '任务结束!');
        Event::wait();
    }

    public function checkBlock()
    {
        //更新余额
        $Timing = MemberTiming::where('status',0)->select();
        if (!empty($Timing)){
            $Timing = $Timing->toArray();
            foreach ($Timing as $row){
                if ($row['time'] < time()){
                    $wallet = MemberWallet::where([['mid','=',$row['mid']]])->value('cny');
                    Db::startTrans();
                    try {
                        /*执行主体*/
                        (new Wallet())->change($row['mid'],1,[
                            1 => [$wallet, $row['number'], $wallet + $row['number']],
                        ]);
                        MemberTiming::where('id',$row['id'])->update([
                            'status'=>1
                        ]);
                        /*提交事务*/
                        Db::commit();
                    } catch (Exception $e) {
                        /*回滚事务操作*/
                        Db::rollback();
                        var_dump('---------------------------------------------');
                        var_dump($e->getMessage());
                        var_dump('---------------------------------------------');

                    }

                }
            }
        }


    }
}