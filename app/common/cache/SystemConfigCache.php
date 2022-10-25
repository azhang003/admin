<?php
/**
 * Created by : PhpStorm
 * Web: https://www.kaadon.com
 * User: ipioo
 * Date: 2022/1/9 16:46
 */

namespace app\common\cache;


use KaadonAdmin\baseCurd\BaseClass\KaadonCache;

class SystemConfigCache extends KaadonCache
{
    public $config = [
        'CacheKey'   => 'SystemConfigCache',
        'CacheExp'   => 24,
        'CacheStore' => 'redis',
    ];
}