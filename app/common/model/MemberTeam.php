<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class MemberTeam extends TimeModel
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
    public function index()
    {
        return $this->belongsTo(MemberIndex::class, 'mid', 'mid');
    }

}