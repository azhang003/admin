<?php
declare (strict_types=1);

namespace app\service\controller;


use app\admin\controller\merchant\Merchant;
use app\common\model\MemberAccount;
use app\common\model\MemberDashboard;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MemberWithdraworder;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use think\facade\Db;

class Service
{
    /**
     * 调试代理仪表盘
     * @return array|string|string[]|\think\response\Json
     */
    public function merchat()
    {
        Db::startTrans();
        try {
            /*执行主体*/
            $uid = MerchantAccount::where(1)->select()->toArray();
            foreach ($uid as $item){
                $mid = MemberAccount::where([['analog','=',0],['agent_line','like',"%|".$item['id']."|%"]])->column('id');
                if (count($mid)>0){
                    MerchantDashboard::where([['uid','=',$item['id']]])
                        ->update([
                            'team_member' => count($mid),
                            'team_profit' => MemberDashboard::where([['mid','in',$mid]])->sum('user_profit'),
                            'team_recharge' => MemberDashboard::where([['mid','in',$mid]])->sum('user_recharge'),
                            'team_withdraw' => MemberDashboard::where([['mid','in',$mid]])->sum('user_withdraw'),
                            'team_withdraw_examine' => MemberDashboard::where([['mid','in',$mid]])->sum('user_withdraw_examine'),
                            'team_money' => MemberWallet::where([['mid','in',$mid]])->sum('cny'),
                        ]);
                }
            }
            // 提交事务
            Db::commit();
            return success("调试完毕");
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return error($e->getMessage());
        }
    }
    /**
     * 清空代理仪表盘
     */
    public function merchat_clear(){
        Db::startTrans();
        try {
            MerchantDashboard::where(1)->update([
                'day_recharge'=>0,
                'day_withdraw'=>0,
                'day_event_money'=>0,
                'day_event_number'=>0,
                'day_event_award'=>0,
                'day_event_profit'=>0,
                'day_sizzler_money'=>0,
                'day_sizzler_number'=>0,
                'day_sizzler_award'=>0,
                'day_sizzler_profit'=>0,
                'day_event'=>0,
                'day_sizzler'=>0,
            ]);
            // 提交事务
            Db::commit();
            return success("调试完毕");
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return error($e->getMessage());
        }
    }
    /**
     * 调试用户仪表盘
     * @return array|string|string[]|\think\response\Json
     */
    public function member(){
        try {
            /*执行主体*/
            $mid = MemberAccount::where([['analog','=',0]])->column('id');
            foreach ($mid as $id){
                $recharge = MemberPayOrder::where([['mid','=',$id],['status','=',1],])->sum('number');
                $money = MemberWallet::where([['mid','=',$id]])->value('cny');
                $user_withdraw = MemberWithdraworder::where([['mid','=',$id],['examine','=',1]])->sum('amount');
                $user_withdraw_examine = MemberWithdraworder::where([['mid','=',$id],['examine','=',0]])->sum('amount');
                $user_event = MemberRecord::where([['mid','=',$id],['business','=',4],])->sum('now')-MemberRecord::where([['mid','=',$id],['business','=',3],])->sum('now');
                $user_sizzler = MemberRecord::where([['mid','=',$id],['business','=',8],])->sum('now')-MemberRecord::where([['mid','=',$id],['business','=',7],])->sum('now');
                MemberDashboard::where([['mid','=',$id]])->update([
                    'user_profit'=>$recharge-$money-$user_withdraw-$user_withdraw_examine,
                    'user_recharge'=>$recharge,
                    'user_withdraw'=>$user_withdraw,
                    'user_withdraw_examine'=>$user_withdraw_examine,
                    'user_event'=>$user_event,
                    'user_sizzler'=>$user_sizzler,
                ]);
            }
            return success("调试完毕");

        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }

    /**
     * 清空用户仪表盘
     */
    public function member_clear(){
        Db::startTrans();
        try {
            $mid = MemberAccount::where([['analog','=',0]])->column('id');
            MemberDashboard::where([['mid','in',$mid]])->update([
                'user_profit'=>0,
                'user_recharge'=>0,
                'user_withdraw'=>0,
                'user_withdraw_examine'=>0,
                'user_event'=>0,
                'user_sizzler'=>0,
                'day_recharge'=>0,
                'day_withdraw'=>0,
                'day_event_money'=>0,
                'day_event_number'=>0,
                'day_event_award'=>0,
                'day_event_profit'=>0,
                'day_sizzler_money'=>0,
                'day_sizzler_number'=>0,
                'day_sizzler_award'=>0,
                'day_sizzler_profit'=>0,
                'day_event'=>0,
                'day_sizzler'=>0,
            ]);
            // 提交事务
            Db::commit();
            return success("清理完成");
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return error($e->getMessage());
        }
    }
}
