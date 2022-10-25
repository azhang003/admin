<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class GameEventListOld extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];
//    // 设置当前模型的数据库连接
//    protected $connection = 'mysql_6';

    public function gameBet()
    {
        return $this->belongsTo(GameEventBet::class, 'id', 'list_id');
    }

    public function gameCurrery()
    {
        return $this->belongsTo(GameEventCurrency::class, 'cid', 'id');
    }



}