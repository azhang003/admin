<?php

namespace app\common\command;


use app\common\model\MemberWithdrawOrder;
use app\common\model\UserWithdraw;
use app\common\service\Integration;
use Exception;
use Swoole\Event;
use Swoole\Timer;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class Withdraw extends Command
{
    protected function configure()
    {
        $this->setName('Withdraw')->setDescription("计划任务 用户提现");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        Timer::tick(10 * 1000, function () {
            try {
                /*执行主体*/
                $this->waitingWithdraw();
            } catch (Exception $e) {
                var_dump($e->getMessage());
            };
        });

        /*** 这里写计划任务列表集 END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
        Event::wait();
    }

    public function waitingWithdraw()
    {
        var_dump("轮训执行提现项目:" . date('Y-m-d H:i:s'));
        $address = get_config('wallet', 'wallet', 'withdrawal_address');
        $privateKey = get_config('wallet', 'wallet', 'private_key');
        $waitings = MemberWithdrawOrder::
        where('examine', 1)
            ->where('status', 0)
            ->select();
        $Integration = new Integration();

        if (!$waitings->isEmpty()) {
            $Balance = $Integration->getTrc20Balance(null, $address);
            $TRXBalance = $Integration->getBalance(null, $address);

            if ($TRXBalance <= 8) {
                var_dump('TRX余额不足!请准备充足的TRX余额!');
                return '';
            }
            if ($Balance <= 0) {
                var_dump('USDT余额不足!');
                return '';
            }
            foreach ($waitings as $waiting) {
                $this->Transfer($waiting, $address, $privateKey, $Integration);
            }
        } else {
            var_dump('没有打款项目!');
        }
    }

    public function Transfer($waiting, $address, $privateKey, $Integration)
    {
        $USDT = $waiting['money'];
        $useraddress = $waiting['address'];
        $Balance = $Integration->getTrc20Balance(null, $address);

        if ($Balance <= $USDT) {
            var_dump('向' . $waiting['user_id'] . '打款时USDT余额不足!');
            return '';
        }

        $res = $Integration->trxTransfer($privateKey, $useraddress, $USDT);
        if ($res['result']) {
            MemberWithdrawOrder::where('id', $waiting['id'])
                ->update([
                    'status' => 1,
                    'time'   => time(),
                    'rid'    => $res['txid'],
                    'reason' => "打款成功!",
                ]);
            var_dump('向' . $waiting['id'] . '的地址' . $useraddress . '打款' . $USDT . '成功!');
        } else {
            MemberWithdrawOrder::where('id', $waiting['id'])
                ->update([
                    'reason' => "打款失败!mm ",
                ]);
        }
    }

}