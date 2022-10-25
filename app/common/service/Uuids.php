<?php

namespace app\common\service;



use app\common\model\SystemUuid;

class Uuids extends \Kaadon\Uuid\Uuids
{
    public static function getUuid(int $type = 1){
        $prefix = chr(mt_rand(65, 90));
        do {
            $number = mt_rand(100000000, 999999999);
            $uid    = $prefix . $number;
        } while (!empty(SystemUuid::where('uuid', '=', $uid)->find()));
        $bool = SystemUuid::create([
            'type' => $type,
            'uuid' => $uid,
        ]);
        if (empty($bool)) {
            throw new \Exception("Sorry, the account number generation failed!");
        }
        return $uid;
    }
    public static function getUuids(int $type = 1){
        $prefix = chr(mt_rand(65, 90));
        do {
            $number = mt_rand(10000, 99999);
            $uid    = $prefix . $number;
        } while (!empty(SystemUuid::where('uuid', '=', $uid)->find()));
        $bool = SystemUuid::create([
            'type' => $type,
            'uuid' => $uid,
        ]);
        if (empty($bool)) {
            throw new \Exception("Sorry, the account number generation failed!");
        }
        return $uid;
    }
}