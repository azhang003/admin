<?php
return [
    // 默认缓存驱动
    'default' => 'redis',
    // 缓存连接方式配置
    'stores'  => [
        'redis' => [
            'host'       => '127.0.0.1',
            'port'       => 6379,
            'password'   => '123456',
            'select'     => 5,
            'timeout'    => 0,
            'expire'     => 3600,
            'persistent' => false,
            'prefix'     => '',
            'tag_prefix' => '',
        ]
        // 更多的缓存连接
    ],
];
