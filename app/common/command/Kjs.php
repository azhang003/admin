<?php

namespace app\common\command;


use app\common\controller\member\Wallet;

use app\common\model\GameEventBet;
use app\common\model\GameEventList;
use app\common\model\MemberAccount;
use app\common\model\MemberWallet;
use Exception;
use Swoole\Event;
use Swoole\Timer;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class Kjs extends Command
{
    protected function configure()
    {
        $this->setName('Kjs')->setDescription("计划任务 交易开奖");
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        Timer::tick(10 * 1000, function () {
            var_dump(date('Y-m-d h:i:s'));
            $this->checkBlock();
        });
        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d H:i:s') . '任务结束!');
        Event::wait();
    }

    public function checkBlock()
    {
        $start = microtime(true);
        $items = GameEventBet::where([['is_ok', '=', 0],['cycle','<>','5m'],['end_time','<',time()]])->order('id asc')->limit(100)->select();
        if (!empty($items)) {
            $items = $items->toArray();
            foreach ($items as $item) {
                //开启事务操作
                $this->pays($item);
            }
        } else {
            var_dump('暂无场次!');
        }
        $end = microtime(true);
        var_dump($end-$start);
    }

    public function pays($item)
    {
        $game = GameEventList::where([['id', '=', $item['list_id']], ['open', '>', '0']])->find();
        if (!empty($game)) {
            if ($game->end_time < time()) {
                if ($item['bet'] == $game->open) {
                    // 启动事务
                    Db::startTrans();
                    try {
                        $account = MemberAccount::where([['id', '=', $item['mid']]])->find();
                        if (!empty($account)) {
                            $this->pay($item['mid'], $item['money'] * $item['odds'], $item['type'], $game->type);
                        }
                        /** BET表变更 **/
                        $bool = GameEventBet::where('id', $item['id'])
                            ->update([
                                'is_ok'     => 1,
                                'open_time' => time(),
                                'remark'    => $game->remark
                            ]);
                        if (!$bool) {
                            throw new \think\Exception('开奖失败[1]>>:' . $item['id']);
                        }
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        var_dump($e->getMessage());
                    }
                } else {
                    $bool = GameEventBet::where('id', $item['id'])
                        ->update([
                            'is_ok' => 2,
                            'open_time' => time(),
                            'remark' => $game->remark
                        ]);
                    if (!$bool) {
                        var_dump('开奖失败[2]>>:' . $item['id']);
                    }
                }
            }
        }
    }

    /**
     * 派奖
     * @param $uid
     * @param $money
     * @param string $type
     * @param null $time
     * @throws \think\Exception
     */
    public function pay($uid, $money, $type = "0", $time = null)
    {
        switch ($type) {
            case 0:
                $wallet = MemberWallet::where('mid', $uid)->value('cny');
                $cid    = 1;
                break;
            case 1:
                $wallet = MemberWallet::where('mid', $uid)->value('btc');
                $cid    = 5;
                break;
        }
        (new Wallet())->change($uid, 4, [
            $cid => [$wallet, $money, $wallet + $money],
        ], null, null, $time);
    }
}
