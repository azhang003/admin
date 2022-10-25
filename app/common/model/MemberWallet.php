<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class MemberWallet extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];
    public function profile()
    {
        return $this->belongsTo(MemberProfile::class, 'mid', 'mid');
    }

    public function account()
    {
        return $this->belongsTo(MemberAccount::class, 'mid', 'id');
    }
}