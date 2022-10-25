<?php
namespace app\common\command;


use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use Exception;
use GatewayWorker\Lib\Gateway;
use Swoole\Event;
use Swoole\Timer;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class Recovery extends Command
{
    protected function configure()
    {
        $this->setName('Recovery')->setDescription("计划任务 推送数据");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
//        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
//        var_dump(date('Y-m-d h:i:s'));
//        $this->checkBlock();
//        /*** 这里写计划任务列表集sd END ***/
//        $output->writeln(date('Y-m-d H:i:s') . '任务结束!');
        Timer::tick(1 * 1000, function () {
            var_dump(date('Y-m-d H:i:s'));
            $this->checkBlock();
        });
        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d H:i:s') . '任务结束!');
        Event::wait();
    }

    public function checkBlock()
    {
        $list = Gateway::getAllGroupIdList();
        foreach ($list as $value){
            if (!empty(strpos($value,'ame'))){
                $game = explode('_',$value);
                $currery = GameEventCurrency::where('title',strtoupper(str_replace('usdt','/usdt',$game[1])))->find();
                if (!empty($currery)){
                    $games['now'] = GameEventList::where([['cid','=',$currery->id],['type','=',$game[2]],['begin_time','<',time()],['end_time','>',time()]])->find();
                    $games['last'] = GameEventList::where([['cid','=',$currery->id],['type','=',$game[2]],['end_time','<',time()]])->order('id desc')->find();
                    $games['second'] = GameEventList::where([['cid','=',$currery->id],['type','=',$game[2]],['begin_time','>',time()]])->order('id asc')->find();
                    Gateway::sendToGroup($value, json_encode([
                        'type' => 'game',
                        'cid' => $currery->id,
                        'game' => $games,
                    ], true));
                }
            }
        }
        //开启事务操作
    }
}
