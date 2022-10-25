<?php
declare (strict_types = 1);
namespace app\job;

use app\common\controller\member\QueueError;
use app\common\model\MemberAccount;
use think\Exception;
use think\queue\Job;
use think\facade\Db;


class level{

    /**
     * 报表
     */
    public function fire(Job $job, $data){
        if ($this->doTask($data)) {
            $job->delete();
            echo "执行成功删除任务" . $job->attempts() . '\n';
        } else {
            $job->delete();
            QueueError::setError([
                'title' =>'testLevel',
                'controller' =>'app\job\level',
                'context' =>json_encode($data),
            ]);
            echo "执行失败删除任务" . $job->attempts() . '\n';
        }
    }

    /**
     *
     * task具体执行逻辑
     */
    public function doTask($data){
        MemberAccount::where([['id', '=', $data['mid']]])->inc('cumulative', 1)->update();
        $level = explode('|', get_config('wallet', 'wallet', 'member_day'));
        $member = MemberAccount::where([['id', '=', $data['mid']]])->find();
        foreach ($level as $key => $value) {
            if ($member->cumulative > $value) {
                if ($key > $member->level) {
                    MemberAccount::where([['id', '=', $data['mid']]])->update([
                        'level' => $key
                    ]);
                }
            }
        }
        return true;
    }
}
