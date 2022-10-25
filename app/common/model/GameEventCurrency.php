<?php

namespace app\common\model;

use app\common\controller\member\Redis;
use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;
use think\facade\Cache;

class GameEventCurrency extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig
        = [
            'modelCache'       => '',
            'modelSchema'      => 'id',
            'modelDefaultData' => [],
        ];

    public function gameBet()
    {
        return $this->belongsTo(GameEventBet::class, 'id', 'list_id');
    }

    public static function CurreryAll()
    {
        $resultData = Redis::redis()->get('currery');

        if (empty($resultData)){
            $resultData  = [];
            $CurreryAlls = self::where(
                ['status' => 1]
            )->select();
            foreach ($CurreryAlls as $curreryAll) {
                $resultData[$curreryAll->id] = $curreryAll;
            }
            Redis::redis()->set('currery',json_encode($resultData,true),3600);
        }else{
            $resultData = json_decode($resultData,true);
        }
        return $resultData;
    }
}