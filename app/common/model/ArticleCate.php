<?php


namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class ArticleCate extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];

}