<?php

namespace app\job;

use app\common\controller\member\QueueError;
use app\common\model\GameEventBet;
use think\queue\Job;

class queueAward
{
    /**
     * @param Job $job
     * @param $data
     * @return void
     */
    public function fire(Job $job, $data)
    {
        $job->delete();
        if ($this->doHelloJob($data['data'])) {
            echo "执行成功删除任务" . $job->attempts() . '\n';
        } else {
            QueueError::setError([
                'title'      => $data['task'],
                'controller' => self::class,
                'context'    => json_encode($data),
            ]);
            echo "执行失败删除任务" . $job->attempts() . '\n';
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
    private function doHelloJob(array $data)
    {
        /**  当期未开奖 **/
        GameEventBet::where([['list_id', '=', $data['id']], ['is_ok', '=', 0], ['bet', '<>', $data['open']]])->save([
            'is_ok'     => 2,
            'open_time' => time(),
            'remark'    => $data['remark'],
        ]);
        $GameEventBetItems = GameEventBet::where([['list_id', '=', $data['id']], ['is_ok', '=', 0]])->order('id asc')->select();
        if ($GameEventBetItems && count($GameEventBetItems) > 0) {
            foreach ($GameEventBetItems as $gameEventBetItem) {
                if ($gameEventBetItem->bet == $data['open']) {
                    $queueData = [
                        'task' => 'queueAwardMember', //标识 暂时不使用
                        'data' => [
                            'mid'       => $gameEventBetItem->mid,//会员ID
                            'bet_id'    => $gameEventBetItem->id,//投注ID,
                            'open'      => $data['open'],//当期开奖,
                            'bet'       => $gameEventBetItem->bet,//当期投注涨跌,
                            'remark'    => $data['remark'],//当期开奖价格,
                            'type'      => $gameEventBetItem->type,//当期真是投注还是模拟投注,
                            'odds'      => $gameEventBetItem->odds,//当期赔率,
                            'list_type' => $gameEventBetItem->cycle,
                            'money'     => $gameEventBetItem->money,
                        ]
                    ];
                    queue(queueAwardMember::class,
                        $queueData,
                        31,
                        $queueData['task']
                    );
                }
            }
            return true;
        } else {
            return true;
        }
    }

}