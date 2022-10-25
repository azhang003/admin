<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class GameEventTest extends TimeModel
{
    use ModelCurd;
    protected $partition = [
        'now',
        'old'
    ];
    // 定义默认的表后缀（默认查询中文数据）
//    protected $suffix = '_now';


    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];

    // 设置当前模型的数据库连接

    public function gameBet()
    {
        return $this->belongsTo(GameEventBet::class, 'id', 'list_id');
    }

    public function gameCurrery($cid)
    {
        return GameEventCurrency::CurreryAll()[$cid];
    }

}