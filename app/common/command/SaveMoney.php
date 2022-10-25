<?php
namespace app\common\command;


use app\common\controller\member\Redis;
use app\common\model\GameEventCurrency;
use app\common\model\MemberAddress;
use app\common\service\Integration;
use Exception;
use Swoole\Event;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Swoole\Timer;
use think\facade\Db;

class SaveMoney extends Command
{
    protected function configure()
    {
        $this->setName('SaveMoney')->setDescription("计划任务 查询余额并显示");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
//        Timer::tick(2 * 1000, function () {
        var_dump(date('Y-m-d h:i:s'));
        $this->checkBlock();
//        });
        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
//        Event::wait();
    }
    public function checkBlock(){
        $redis = Redis::redis();
        $Integration = new Integration();
        $redis->set('gj_balance',$Integration->getTrc20Balance(null, get_config('wallet','wallet','collection_address')));
        $redis->set('gj_TRXBalance',$Integration->getBalance(null, get_config('wallet','wallet','collection_address')));
        $redis->set('tx_balance',$Integration->getTrc20Balance(null, get_config('wallet','wallet','withdrawal_address')));
        $redis->set('tx_TRXBalance',$Integration->getBalance(null, get_config('wallet','wallet','withdrawal_address')));
        $address = MemberAddress::where(1)->select();
        foreach ($address as $value){
            MemberAddress::where([['id','=',$value->id]])->update([
                'money' => $Integration->getTrc20Balance(null, $value->trc_address)
            ]);
        }
        $redis->close();
    }

}
