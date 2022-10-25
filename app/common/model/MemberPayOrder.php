<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class MemberPayOrder extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];

    public function account()
    {
        return $this->belongsTo(MemberAccount::class, 'mid', 'id');
    }
    public function profile()
    {
        return $this->belongsTo(MemberProfile::class, 'mid', 'mid');
    }
    public function userAddress()
    {
        return $this->belongsTo(MemberAddress::class, 'mid', 'mid');
    }
    public function wallet()
    {
        return $this->belongsTo(MemberWallet::class, 'mid', 'mid');
    }
    public function payment()
    {
        return $this->belongsTo(SystemPayment::class, 'pid', 'id');
    }
}