<?php


namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class ActivityList extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];

}