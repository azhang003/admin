<?php
declare (strict_types = 1);
namespace app\job;

use app\common\controller\member\QueueError;
use app\common\controller\member\Wallet;
use app\common\model\MemberAccount;
use think\Exception;
use think\queue\Job;
use think\facade\Db;


class game{

    /**
     * 报表
     */
    public function fire(Job $job, $data){
        $isJobDone = $this->doTask($data);
        if ($isJobDone) {
            //如果任务执行成功，记得删除任务
            $job->delete();
        }else{
            //通过这个方法可以检查这个任务已经重试了几次了
            if ($job->attempts() > 3){
//                var_dump('试了3次了');
//                QueueError::setError([
//                    'title' =>'testLevel',
//                    'controller' =>'app\job\level',
//                    'context' =>json_encode($data),
//                ]);
                $job->delete();
                //也可以重新发布这个任务
                //print("<info>Hello Job will be availabe again after 2s."."</info>\n");
            }
        }
    }

    /**
     *
     * task具体执行逻辑
     */
    public function doTask($data){
        $res = $this->agent($data['uid']);
        if ($res) {
//            echo $data['mid']."返利成功";
            return true;
        } else {
            return false;
        }
    }
    public function agent($mid)
    {
        try {
            $member = MemberAccount::where('id', $mid)->find();
            if (!empty($member)) {
                $inviter_line = explode('|', $member->agent_line);
                $count = count($inviter_line);
                if ($count > 2) {
                    $this->agent($mid, ++$type);
                }
                $agent = MemberAccount::where('uuid', $member->inviter)->find();
                if (!empty($agent)) {
                    if ($type < (count($config))) {
                        (new Wallet())->change($agent->id, 9, [
                            4 => [$agent->wallet->eth, $money * $config[$type], $agent->wallet->eth + $money * $config[$type]],
                        ], $mids, $type + 1);
                    } elseif ($agent->level == "4") {
                        (new Wallet())->change($agent->id, 9, [
                            4 => [$agent->wallet->eth, $money * get_config('site', 'setting', 'super'), $agent->wallet->eth + $money * get_config('site', 'setting', 'super')],
                        ], $mids, 0);
                    }
                    $this->invite($agent->id, $money, $type + 1, $mids);
                }
            }
        } catch (Exception $exception) {
            var_dump($exception->getMessage());
        }
        return true;
    }

}
