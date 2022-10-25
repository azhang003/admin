<?php


namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class ActivityComplete extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];
    public function ActivityList()
    {
        return $this->belongsTo(ActivityList::class, 'list_id', 'id');
    }
    public function profile()
    {
        return $this->belongsTo(MemberProfile::class, 'mid', 'mid');
    }

}