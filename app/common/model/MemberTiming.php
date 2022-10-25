<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class MemberTiming extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'mid',
        'modelDefaultData' => [],
    ];
    public function account()
    {
        return $this->belongsTo(MemberAccount::class, 'mid', 'id');
    }
    public function pay()
    {
        return $this->belongsTo(MemberPayOrder::class, 'pid', 'id');
    }

}