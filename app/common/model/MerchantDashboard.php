<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class MerchantDashboard extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];

    public function profile()
    {
        return $this->belongsTo(MerchantProfile::class, 'uid', 'uid');
    }

    /**
     * @param $uid
     * @param $type
     * @param $money
     * @return bool
     * 团队更新数据
     */
    public static function dashboard($uid,$type,$money){
        $agent = MemberAccount::where('id',$uid)->value('agent_line');
        $uid = explode('|',$agent);
        switch ($type){
            case 1:
                MerchantDashboard::where([['uid' ,'in' ,$uid]])
                    ->inc('team_profit',abs($money))
                    ->inc('day_recharge',abs($money))
                    ->inc('team_recharge',abs($money))
                    ->inc('team_money',abs($money))
                    ->update();
                break;
            case 2:
                MerchantDashboard::where([['uid' ,'in' ,$uid]])
                    ->dec('team_profit',abs($money))
                    ->inc('day_withdraw',abs($money))
                    ->inc('team_withdraw',abs($money))
                    ->inc('team_withdraw_examine',abs($money))
                    ->dec('team_money',abs($money))
                    ->update();
                break;
            case 3:
                MerchantDashboard::where([['uid' ,'in' ,$uid]])
                    ->inc('day_event_profit',abs($money))
                    ->inc('day_event_award',abs($money))
                    ->dec('day_event',abs($money))
                    ->inc('team_money',abs($money))
                    ->update();
                break;
            case 4:
                MerchantDashboard::where([['uid' ,'in' ,$uid]])
                    ->inc('day_event_number',1)
                    ->inc('day_event_money',abs($money))
                    ->inc('day_event',abs($money))
                    ->dec('team_money',abs($money))
                    ->dec('day_event_profit',abs($money))
                    ->update();
                break;
            case 5:
                MerchantDashboard::where([['uid' ,'in' ,$uid]])
                    ->inc('team_profit',abs($money))
                    ->dec('team_withdraw',abs($money))
                    ->dec('team_withdraw_examine',abs($money))
                    ->inc('team_money',abs($money))
                    ->update();
                break;
            case 7:
                MerchantDashboard::where([['uid' ,'in' ,$uid]])
                    ->inc('day_sizzler_profit',abs($money))
                    ->dec('day_sizzler',abs($money))
                    ->inc('team_money',abs($money))
                    ->update();
                break;
            case 8:
                MerchantDashboard::where([['uid' ,'in' ,$uid]])
                    ->inc('day_sizzler_number',1)
                    ->inc('day_sizzler_money',abs($money))
                    ->dec('team_money',abs($money))
                    ->dec('day_sizzler_profit',abs($money))
                    ->inc('day_sizzler',abs($money))
                    ->update();
                break;
        }
    }
}