<?php


namespace Kaadon\Jwt;


use think\facade\Cache;

class JwtCache
{
    /**
     * 缓存key
     *
     * @param string $identification 用户名
     *
     * @return string
     */
    public static function key(string $identification, string $Module = null)
    {
        if (is_null($Module)){
            $Module = 'Api';
        }
        $key = 'Jwt:' . $Module . ':'.$identification;

        return $key;
    }

    /**
     * 缓存有效时间
     *
     * @param int $expire 有效时间
     *
     * @return int
     */
    public static function exp($expire = 0)
    {
        if (empty($expire)) {
            $expire = 1 * 24 * 60 * 60;
        }

        return $expire;
    }

    /**
     * 缓存设置
     *
     * @param string $identification 用户id
     * @param array   $admin_user    用户信息
     * @param int $expire        有效时间
     *
     * @return array 用户信息
     */
    public static function set(string $identification, $value, $Module = null, $expire = 0)
    {
        $key = self::key($identification , $Module);
        $val = $value;
        $exp = $expire ?: self::exp();
        Cache::set($key, $val, $exp);

        return $val;
    }

    /**
     * 缓存获取
     *
     * @param string $identification
     *
     * @param null $Module
     * @return array 用户信息
     */
    public static function get(string $identification, $Module = null)
    {
        $key = self::key($identification , $Module);
        $res = Cache::get($key);

        return $res;
    }

    /**
     * 缓存删除
     *
     * @param string $identification
     *
     * @return bool
     */
    public static function del(string $identification, $Module = null)
    {
        $key = self::key($identification , $Module);
        $res = Cache::delete($key);
        return $res;
    }
}