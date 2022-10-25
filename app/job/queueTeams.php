<?php

namespace app\job;

use app\common\controller\member\QueueError;
use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDashboard;
use app\common\model\MemberIndex;
use app\common\model\MemberRecord;
use app\common\model\MemberTeam;
use app\common\model\MemberWallet;
use app\common\model\MerchantIndex;
use app\common\model\SystemSummarize;
use think\Exception;
use think\facade\Db;
use think\queue\Job;

class queueTeams
{
    /**
     * @param Job $job
     * @param $data
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
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
     *
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function doHelloJob(array $data)
    {
        $uid = $data['mid'];
        $uuid = MemberAccount::where('id', $uid)->value('uuid');
        $one = MemberAccount::where([['inviter', '=', $uuid]])->column('uuid');
        $two = MemberAccount::where([['inviter', 'in', $one]])->column('uuid');
        $three = MemberAccount::where([['inviter', 'in', $two]])->column('uuid');
        $data = [
            'first'          => count($one),
            'mid'            => $uid,
            'second'         => count($two),
            'third'          => count($three),
            'first_share'    => MemberRecord::where([
                ['mid', '=', $uid],
                ['business', '=', 9],
                ['team', '=', 1],
                ['currency', '=', 4],
            ])->sum('now'),
            'second_share'   => MemberRecord::where([
                ['mid', '=', $uid],
                ['business', '=', 9],
                ['team', '=', 2],
                ['currency', '=', 4],
            ])->sum('now'),
            'third_share'    => MemberRecord::where([
                ['mid', '=', $uid],
                ['business', '=', 9],
                ['team', '=', 3],
                ['currency', '=', 4],
            ])->sum('now'),
            'vip'            => MemberRecord::where([
                ['mid', '=', $uid],
                ['business', '=', 9],
                ['team', '=', 0],
                ['currency', '=', 4],
            ])->sum('now'),
            'first_receive'  => abs(MemberRecord::where([
                ['mid', '=', $uid],
                ['business', '=', 13],
                ['currency', '=', 1],
                ['team', '=', 1],
            ])->sum('now')),
            'second_receive' => abs(MemberRecord::where([
                ['mid', '=', $uid],
                ['currency', '=', 1],
                ['business', '=', 13],
                ['team', '=', 2],
            ])->sum('now')),
            'third_receive'  => abs(MemberRecord::where([
                ['mid', '=', $uid],
                ['currency', '=', 1],
                ['business', '=', 13],
                ['team', '=', 3],
            ])->sum('now')),
            'vip_receive'    => abs(MemberRecord::where([
                ['mid', '=', $uid],
                ['currency', '=', 1],
                ['business', '=', 13],
                ['team', '=', 0],
            ])->sum('now')),
            'all_receive'    => abs(MemberRecord::where([
                ['mid', '=', $uid],
                ['currency', '=', 1],
                ['business', '=', 13],
            ])->sum('now')),
        ];
        $first_share = $data['first_share']-$data['first_receive'];
        $second_share = $data['second_share']-$data['second_receive'];
        $third_share = $data['third_share']-$data['third_receive'];
        $vip = $data['vip']-$data['vip_receive'];
        if ($first_share<=0){
            $data['first_share'] = $data['first_receive']+MemberRecord::where([
                    ['mid', '=', $uid],
                    ['business', '=', 9],
                    ['team', '=', 1],
                    ['currency', '=', 4],
                    ['create_time', '>', strtotime(date('Y-m-d'))],
                ])->sum('now');
            $first_share = $data['first_share']-$data['first_receive'];
        }
        if ($second_share<=0){
            $data['second_share'] = $data['second_receive']+MemberRecord::where([
                    ['mid', '=', $uid],
                    ['business', '=', 9],
                    ['team', '=', 2],
                    ['currency', '=', 4],
                    ['create_time', '>', strtotime(date('Y-m-d'))],
                ])->sum('now');
            $second_share = $data['second_share']-$data['second_receive'];
        }
        if ($third_share<=0){
            $data['third_share'] = $data['third_receive']+MemberRecord::where([
                    ['mid', '=', $uid],
                    ['business', '=', 9],
                    ['team', '=', 3],
                    ['currency', '=', 4],
                    ['create_time', '>', strtotime(date('Y-m-d'))],
                ])->sum('now');
            $third_share = $data['third_share']-$data['third_receive'];
        }
        if ($vip<=0){
            $data['vip'] = $data['vip_receive']+MemberRecord::where([
                    ['mid', '=', $uid],
                    ['business', '=', 9],
                    ['team', '=', 0],
                    ['currency', '=', 4],
                    ['create_time', '>', strtotime(date('Y-m-d'))],
                ])->sum('now');
            $vip = $data['vip']-$data['vip_receive'];
        }
        $money = $first_share+$second_share+$third_share+$vip;
        $daaaa = MemberTeam::where('mid', $uid)->find();
        if (empty($daaaa)) {
            MemberTeam::insert($data);
        } else {
            MemberTeam::where('mid', $uid)->save($data);
        }
        MemberWallet::where('mid',$uid)->update([
            'eth'=>$money
        ]);
        return true;
    }

}