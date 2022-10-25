<?php
// 应用公共文件

use app\common\controller\member\Redis;
use app\common\model\MemberIpAddress;
use app\common\service\AuthService;


if (!function_exists('redisCacheSet')) {
    /**
     * 设置缓存
     * @param string $name //key
     * @param $value //值
     * @param $expire //过期时间
     * @param $select //redis库
     * @return bool
     */
    function redisCacheSet(string $name, $value, $expire = 3600, $select = 1)
    {
        try {
            $redis = Redis::redis($select);
            $redis->set('cache:' . $name, json_encode($value));
            $redis->expire('cache:' . $name, $expire);
        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }
}

if (!function_exists('redisCacheGet')) {

    /**
     * 获取缓存
     * @param string $name //key
     * @param $select //redis库
     * @return mixed|string
     */
    function redisCacheGet($name, $select = 1)
    {
        $resultData = '';
        $data       = Redis::redis($select)->get('cache:' . $name);
        if ($data) {
            $resultData = json_decode($data, true);
        }
        return $resultData;
    }
}
if (!function_exists('redisCacheDel')) {
    /**
     * 删除缓存
     * @param string $name //key
     * @param $select //redis库
     * @return mixed|string
     */
    function redisCacheDel($name, $select = 1)
    {
        return Redis::redis($select)->del('cache:' . $name);
    }
}

if (!function_exists('get_ip_address')) {
    /**
     * 格式化金额
     * @param $num
     * @return string
     */
    function get_ip_address($ip)
    {
        $MIA     = MemberIpAddress::where('ip', $ip)->find();
        if (!$MIA) {
            try {
                $a       = file_get_contents('https://ipinfo.io/' . $ip . '?token=7e44600b1bd751');
                $let     = json_decode($a, true);
                $country = json_decode(file_get_contents(public_path() . 'country.json'), true);
                if (isset($country[$let['country']])) $let['country'] = $country[$let['country']];
                $address = $let['country'] . '/' . $let['region'] . '/' . $let['city'];
            } catch (\Exception $exception) {
                return null;
            }
            if ($address) {
                $MIA          = new MemberIpAddress();
                $MIA->ip      = $ip;
                $MIA->address = $address;
                $MIA->save();
            }
        } else {
            $address = $MIA->address;
        }
        return $address;
    }
}


if (!function_exists('money_format_bet')) {
    /**
     * 格式化金额
     * @param $num
     * @return string
     */
    function money_format_bet($num)
    {
        $num = floatval($num);
        return number_format($num, 8, '.', '');
    }
}

if (!function_exists('agent_line_array')) {
    /**
     * 拆分上级并反序
     * @param $agent_line
     * @return array|false|string[]
     */
    function agent_line_array($agent_line)
    {
        try {
            $agent_line_array = explode('|', $agent_line);
            foreach ($agent_line_array as $key => $item) {
                if (empty($item)) {
                    unset($agent_line_array[$key]);
                }
            }
            $agent_line_array = array_reverse($agent_line_array);
        } catch (\Exception $exception) {
            return [];
        }
        return $agent_line_array;
    }
}

function object_array($array)
{
    if (is_object($array)) {
        $array = (array)$array;
    }
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}

if (!function_exists('strToUtf8')) {
    function strToUtf8($str)
    {
        $encode = mb_detect_encoding($str, array("ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5'));
        if ($encode == 'UTF-8') {
            return $str;
        } else {
            return mb_convert_encoding($str, 'UTF-8', $encode);
        }
    }
}

/**
 * 成功返回
 *
 * @param array $data 成功数据
 * @param string $msg 成功提示
 * @param integer $code 成功码
 *
 * @return json
 */

if (!function_exists('success')) {
    function success($data = [], $msg = 'success', $code = 200)
    {
        $res['code']    = $code;
        $res['message'] = $msg;
        $res['data']    = $data;
        return json($res, $code);
    }
}
/**
 * 错误返回
 *
 * @param string $msg 错误提示
 * @param array $err 错误数据
 * @param integer $code 错误码
 *
 * @return json
 */
if (!function_exists('error')) {
    function error($msg = 'error', $errcode = 201, $code = 200, $err = [])
    {
        $res['code']    = $errcode;
        $res['message'] = $msg;
        $res['err']     = $err;
        return json($res, $code);
    }
}

if (!function_exists('admin_alias_name')) {


    /**
     * 构建admin别名
     * @return mixed|string
     */
    function admin_alias_name()
    {
        return config('app.admin_alias_name') ?: 'admin';
    }
}


if (!function_exists('__url')) {

    /**
     * 构建URL地址
     * @param string $url
     * @param array $vars
     * @param bool $suffix
     * @param bool $domain
     * @return string
     */
    function __url(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {
        return url($url, $vars, $suffix, $domain)->build();
    }
}

if (!function_exists('get_config')) {

    /**
     * 获取系统配置信息
     * @param string $group
     * @param string $name
     * @param string|null $value
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function get_config(string $group, string $name, string $value = null)
    {

        $data = \app\common\model\SystemConfig::getInfo($group, $name);

        if (!is_null($value)) {
            if (is_array($data) && array_key_exists($value, $data)) {
                $data = $data[$value];
            } else {
                $data = '';
            }
        }

        return $data;
    }
}

if (!function_exists('timestamp')) {
    /**
     * 时间戳
     * @return false|string
     */
    function timestamp()
    {
        return date('Y-m-d H:i:s');
    }
}
if (!function_exists('password')) {

    /**
     * 密码加密算法
     * @param $value 需要加密的值
     * @param $type  加密类型，默认为md5 （md5, hash）
     * @return mixed
     */
    function password($value)
    {
        $value = sha1('blog_') . md5($value) . md5('_encrypt') . sha1($value);
        return sha1($value);
    }

}

if (!function_exists('xdebug')) {

    /**
     * debug调试
     * @param string|array $data 打印信息
     * @param string $type 类型
     * @param string $suffix 文件后缀名
     * @param bool $force
     * @param null $file
     * @deprecated 不建议使用，建议直接使用框架自带的log组件
     */
    function xdebug($data, $type = 'xdebug', $suffix = null, $force = false, $file = null)
    {
        !is_dir(runtime_path() . 'xdebug/') && mkdir(runtime_path() . 'xdebug/');
        if (is_null($file)) {
            $file = is_null($suffix) ? runtime_path() . 'xdebug/' . date('Ymd') . '.txt' : runtime_path() . 'xdebug/' . date('Ymd') . "_{$suffix}" . '.txt';
        }
        file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] " . "========================= {$type} ===========================" . PHP_EOL, FILE_APPEND);
        $str = ((is_string($data) ? $data : (is_array($data) || is_object($data))) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
        $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }
}


if (!function_exists('array_format_key')) {

    /**
     * 二位数组重新组合数据
     * @param $array
     * @param $key
     * @return array
     */
    function array_format_key($array, $key)
    {
        $newArray = [];
        foreach ($array as $vo) {
            $newArray[$vo[$key]] = $vo;
        }
        return $newArray;
    }

}

if (!function_exists('auth')) {

    /**
     * auth权限验证
     * @param $node
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function auth($node = null)
    {
        $authService = new AuthService(session('admin.id'));
        $check       = $authService->checkNode($node);
        return $check;
    }

}