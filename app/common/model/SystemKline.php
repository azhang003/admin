<?php
/**
 * Created by : PhpStorm
 * Web: https://www.kaadon.com
 * User: ipioo
 * Date: 2021/6/22 21:31
 */

namespace app\common\model;


use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class SystemKline extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => self::class . 'Cache',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];


}






