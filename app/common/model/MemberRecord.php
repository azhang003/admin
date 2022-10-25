<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class MemberRecord extends TimeModel
{
    use ModelCurd;
    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];
    public function account()
    {
        return $this->belongsTo(MemberAccount::class, 'mid','id');
    }
    public function profile()
    {
        return $this->belongsTo(MemberProfile::class, 'mid','mid');
    }
    public function dashboard()
    {
        return $this->belongsTo(MemberDashboard::class, 'id', 'mid');
    }

}