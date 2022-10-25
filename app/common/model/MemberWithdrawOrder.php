<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class MemberWithdrawOrder extends TimeModel
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

    public function index()
    {
        return $this->belongsTo(MemberIndex::class, 'mid', 'mid');
    }

    public function profile()
    {
        return $this->belongsTo(MemberProfile::class, 'mid', 'mid');
    }
    public function wallet()
    {
        return $this->belongsTo(MemberWallet::class, 'mid', 'mid');
    }

    public function dashboard()
    {
        return $this->belongsTo(MemberDashboard::class, 'mid', 'mid');
    }

    public function payment()
    {
        return $this->belongsTo(MemberPayment::class, 'pid', 'id');
    }

}