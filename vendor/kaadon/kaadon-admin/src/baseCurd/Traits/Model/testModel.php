<?php
/**
 * Created by : PhpStorm
 * Web: https://www.kaadon.com
 * User: ipioo
 * Date: 2021/6/22 22:38
 */

namespace KaadonAdmin\baseCurd\Traits\Model;


class testModel
{
    use ModelCurd;

    public static  $ModelConfig = [
        'modelCache' => self::class.'Cache',
        'modelSchema' => 'id',
        'modelDefaultData' => [],
    ];


}