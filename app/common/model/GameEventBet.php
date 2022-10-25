<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class GameEventBet extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];
    // 设置当前模型的数据库连接
    public function profile()
    {
        return $this->belongsTo(MemberProfile::class, 'mid', 'mid');
    }
    public function index()
    {
        return $this->belongsTo(MemberIndex::class, 'mid', 'mid');
    }
    public function wallet()
    {
        return $this->belongsTo(MemberWallet::class, 'mid', 'mid');
    }
    public function account()
    {
        return $this->belongsTo(MemberAccount::class, 'mid', 'id');
    }
    public function record()
    {
        return $this->belongsTo(MemberRecord::class, 'id', 'bet');
    }

    public function dashboard()
    {
        return $this->belongsTo(MemberDashboard::class, 'mid', 'mid');
    }

    public function gameList()
    {
        return $this->belongsTo(GameEventList::class, 'list_id', 'id');
    }

}