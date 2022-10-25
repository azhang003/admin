<?php

namespace app\job;

use app\common\controller\member\QueueError;
use app\common\model\MemberRecord;
use app\common\model\MemberTeam;
use app\common\model\MemberWallet;
use app\common\model\MerchantIndex;
use think\Exception;
use think\facade\Db;
use think\queue\Job;

class queueUpdate
{
    public $ltype
        = [
            1     => [
                'in'  => "first_share",
                'out' => "first_receive",
            ],
            2     => [
                'in'  => "second_share",
                'out' => "second_receive",
            ],
            3     => [
                'in'  => "third_share",
                'out' => "third_receive",
            ],
            0     => [
                'in'  => "vip",
                'out' => "vip_receive",
            ],
            'all' => "all_receive"
        ];
    public function fire(Job $job, array $data)
    {
        if ($this->doJOb($data)) {
            $job->delete();
            echo "执行成功删除任务" . $job->attempts() . '\n';
        } else {
            $job->delete();
            QueueError::setError([
                'title'      => $data['task'],
                'controller' => self::class,
                'context'    => json_encode($data),
            ]);
            echo "执行失败删除任务" . $job->attempts() . '\n';
        }
    }


    private function doJOb(array $data)
    {
        var_dump(json_encode($data));
        try {
            if (array_key_exists('task', $data) && array_key_exists('data', $data) && is_array($data['data'])) {
                $task = $data['task'];
                $bool = $this->$task($data['data']);
                return $bool;
            } else {
                return false;
            }
        } catch (\Exception $exception) {
            var_dump(json_encode($exception->getMessage()));
            return false;
        }
    }

    /** 更正佣金 **/

    public  function updateCommission(array $data)
    {
        // 启动事务
        Db::startTrans();
        try {
            if (isset($data['mid'])){
                $mid = $data['mid'];
            }else{
                throw new Exception('mid不存在!');
            }
            if (isset($data['agent_line']) && is_array($data['agent_line'])){
                $agent_line = $data['agent_line'];
            }else{
                throw new Exception('agent_line不存在!');
            }
            $MemberTeam     = (new MemberTeam())->where('mid', $mid)->find();
            $MemberTeamData = [];
            $eth            = 0;
            $all            = 0;
            $all_share      = 0;
            $tongjis        = $this->tongji($mid);
            foreach ($tongjis as $key => $item) {
                if ($item) {
                    if ($item['news'] < 0) {
                        $bool = MemberRecord::create([
                            'mid'      => $mid,
                            'currency' => 4,
                            "team"     => $key,
                            "business" => 9,
                            'before'   => 0,
                            'now'      => money_format_bet(abs($item['news'])),
                            'after'    => 0,
                            'system'   => 0,
                        ]);
                        if ($bool) {
                            $tongjis[$key]['news'] = 0;
                            $tongjis[$key]['in']   += abs($item['news']);
                        } else {
                            throw new Exception('补足记录失败!');
                        }
                    }
                }
                $all                                       += $item ? money_format_bet($item['out']) : 0;
                $all_share                                 += $item ? money_format_bet($item['in']) : 0;
                $eth                                       += $item ? money_format_bet($item['news']) : 0;
                $MemberTeamData[$this->ltype[$key]['in']]  = abs($item ? money_format_bet($item['in']) : 0);
                $MemberTeamData[$this->ltype[$key]['out']] = abs($item ? money_format_bet($item['out']) : 0);
            }
//            $in_share                            = $all;
//            $surplus_share                       = $eth;
            $MemberTeamData[$this->ltype['all']] = abs($all);
            $MemberTeam->save($MemberTeamData);
            $MemberWallet = MemberWallet::where('mid', $mid)->find();
            if ($MemberWallet->eth !== $eth) {
                $MemberWallet->save([
                    'eth' => money_format_bet($eth)
                ]);
            }
//            MerchantIndex::where([['uid', 'in', $agent_line]])
//                ->inc('in_share', $in_share)
//                ->inc('all_share', $all_share)
//                ->inc('surplus_share', $surplus_share)
//                ->update();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            var_dump(json_encode($e->getMessage()));
            Db::rollback();
            return false;
        }
        return true;
    }


    public function tongji($mid)
    {
        $MR      = new MemberRecord();
        $tongji  = [];
        $sql     = "SELECT SUM(now) AS news,team,mid FROM ea_member_record WHERE mid = " . $mid . " AND currency = 4 GROUP BY team";
        $tongjis = Db::query($sql);
        foreach ($tongjis as $item) {
            $tongji[$item['team']] = $item;
        }
        for ($i = 0; $i <= 3; $i++) {
            isset($tongji[$i]) ?: $tongji[$i] = null;
            if (!empty($tongji[$i])) {
                $tongji[$i]['in']  = $MR->where([
                    'mid'      => $mid,
                    'currency' => 4,
                    "team"     => $i,
                    "business" => 9
                ])->sum('now');
                $tongji[$i]['out'] = $MR->where([
                    'mid'      => $mid,
                    'currency' => 4,
                    "team"     => $i,
                    "business" => 13
                ])->sum('now');
            }
        }
        return $tongji;
    }
    /** 其他 **/
}