<?php

namespace app\common\model;

use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class MerchantAccount extends TimeModel
{
    use ModelCurd;
    public static $ModelConfig = [
        'modelCache'       => '',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];
    public function profile()
    {
        return $this->belongsTo(MerchantProfile::class, 'id', 'uid');
    }

    public function index()
    {
        return $this->belongsTo(MerchantIndex::class, 'id', 'uid');
    }

    public function dashboard()
    {
        return $this->belongsTo(MerchantDashboard::class, 'id', 'uid');
    }

    public function wallet()
    {
        return $this->belongsTo(MerchantWallet::class, 'id', 'uid');
    }
}