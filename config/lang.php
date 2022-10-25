<?php
// +----------------------------------------------------------------------
// | 多语言设置
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // 默认语言
    'default_lang'    => Env::get('lang.default_lang', 'zh'),
    // 允许的语言列表
    'allow_lang_list' => ['zh','en',"es", "fr", "th", "ru", "id", "fa", "vi", "ar", "uz", "kk", "ro",],
    'allow_lang_identification' => [
        'zh'=>'中文',
        'en'=> '英文',
        "es"=> "西班牙",
        "fr"=>"法語",
        "th"=>"泰國",
        "ru"=>"俄語",
        "id"=>"印度尼西亞",
        "fa"=>"波斯語",
        "vi"=>"越南語",
        "ar"=>"阿拉伯語",
        "uz"=>"烏茲別克語",
        "kk"=>"哈薩克語",
        "ro"=>"羅馬尼亞語",
    ],
    // 多语言自动侦测变量名
    'detect_var'      => 'lang',
    // 是否使用Cookie记录
    'use_cookie'      => true,
    // 多语言cookie变量
    'cookie_var'      => 'think_lang',
    // 多语言header变量
    'header_var' => 'thinklang',
    // 扩展语言包
    'extend_list'     => [],
    // Accept-Language转义为对应语言包名称
    'accept_language' => [
        'zh-hans-cn' => 'zh-cn',
    ],
    // 是否支持语言分组
    'allow_group'     => false,
];
