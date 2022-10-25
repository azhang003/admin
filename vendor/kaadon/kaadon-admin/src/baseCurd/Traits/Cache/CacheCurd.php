<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace KaadonAdmin\baseCurd\Traits\Cache;

use think\facade\Cache;

/**
 * 模型复用
 * Trait Curd
 * @package app\admin\traits
 */
trait CacheCurd
{
    /**
     * 默认配置
     * @var array
     */
    public $CacheConfig = [
        'CacheKey' => 'CacheCurd',
        'CacheExp' => 24,
        'CacheTag' => null,
        'CacheStore' => 'redis',
    ];

    /**
     * CacheCurd constructor.
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        if (property_exists(self::class,'config')){
            $this->CacheConfig = array_merge($this->CacheConfig,$this->config);
        }
        if (!empty($config)){
            if (!array_key_exists('CacheTag',$config)){
                $config['CacheTag'] = class_basename(self::class);
            }
            $this->CacheConfig = array_merge($this->CacheConfig,$config);
        }else{
            $this->CacheConfig['CacheTag'] = class_basename(self::class);
        }
    }
    /**
     * 缓存key
     *
     * @param string $cacheKey 用户名
     *
     * @return string
     */
    public function key($cacheKey)
    {

        $key = $this->CacheConfig['CacheTag'] . ':'. $this->CacheConfig['CacheKey'] . ':' . $cacheKey;

        return $key;
    }

    /**
     * 缓存有效时间
     *
     * @param int $expire 有效时间
     *
     * @return int
     */
    public function exp($expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->CacheConfig['CacheExp'] * 60 * 60;
        }

        return $expire;
    }


    /**
     * 缓存写入
     * @param string $cacheKey
     * @param $value
     * @param int $expire
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set(string $cacheKey, $value, $expire = 0)
    {
        $key = self::key($cacheKey);
        $val = $value;
        $exp = $expire ?: self::exp();

        if (!empty($this)) {
            Cache::store($this->CacheConfig['CacheStore'])->tag($this->CacheConfig['CacheTag'])->set($key, $val, $exp);
        }else{
            Cache::store($this->CacheConfig['CacheStore'])->set($key, $val, $exp);
        }

        return $val;
    }

    /**
     * 缓存获取
     *
     * @param string $cacheKey
     *
     * @return array 值
     */
    public function get($cacheKey)
    {
        $key = self::key($cacheKey);
        $res = Cache::store($this->CacheConfig['CacheStore'])->get($key);

        return $res;
    }

    /**
     * 缓存删除
     *
     * @param string $cacheKey
     *
     * @return bool
     */
    public function del($cacheKey)
    {
        $key = self::key($cacheKey);
        $res = Cache::store($this->CacheConfig['CacheStore'])->delete($key);

        return $res;
    }


    /**
     * 缓存Tag删除
     * @return bool
     */
    public function delTag()
    {
        // 清除tag标签的缓存数据
        if (!empty($this->CacheConfig['CacheTag'])){
            Cache::store($this->CacheConfig['CacheStore'])->tag($this->CacheConfig['CacheTag'])->clear();
        }

        return true;
    }
}
