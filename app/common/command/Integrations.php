<?php

namespace app\common\command;


use app\common\model\MemberWithdrawOrder;
use app\common\model\SystemConfig;
use Exception;
use Swoole\Event;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Swoole\Timer;
use think\facade\Db;

//
class Integrations extends Command
{
    protected function configure()
    {
        $this->setName('Integrations')->setDescription("计划任务 扫描交易消耗trx");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        Timer::tick(5 * 1000, function () {
            try {
                /*执行主体*/
                $this->Transfer();
            } catch (Exception $e) {
                var_dump($e->getMessage());
                var_dump($e->getTrace());
            }
        });
        /*** 这里写计划任务列表集 END ***/

        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');

        Event::wait();
    }

    public function Transfer()
    {
        var_dump("扫描交易消耗trx:" . date('Y-m-d H:i:s'));
        $waitingAssemblages = MemberWithdrawOrder::where('transfer_status', 0)
            ->where('examine', '=', '1')
            ->whereNotNull('rid')
            ->where('money', '>', 0)
            ->select();
        if (!empty($waitingAssemblages)) {
            foreach ($waitingAssemblages as $waitingAssemblage) {
                $data = file_get_contents("https://apilist.tronscanapi.com/api/transaction-info?hash=".$waitingAssemblage->rid);
                if($data = json_decode($data,true)){
                    (new SystemConfig())->where([['group','=','block'],['gname','=','trx']])
                        ->inc('value',$data['cost']['energy_fee']/1000000)
                        ->update();
                    MemberWithdrawOrder::where('id', $waitingAssemblage->id)->update([
                        'transfer_status'=>1
                    ]);
                }
            }
        }
    }
}