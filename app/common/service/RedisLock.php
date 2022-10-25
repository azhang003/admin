<?php

namespace app\common\service;


use Kaadon\Lock\Redis;
use think\facade\Env;

/**
 *
 */
class RedisLock
{
    protected $redis;
    protected $keyName;
    protected $expireTime;

    public function __construct($keyName = 'lock_redis',$second = 3){
        $redis = new \Redis();
        $redis->connect((Env::get('JWT.HOSTNAME'))?:'127.0.0.1',6379);
        $redis->auth("123456");
        $redis->select(3);
        $this->redis = $redis;
        $this->keyName = 'redisLock:' . $keyName;
        $this->expireTime = $second;
    }


    /**
     * @return bool|null
     */
    public function lock(){
        $value = $this->redis->get($this->keyName);
        if ($value){
            return false;
        }
        $setnx = $this->redis->setnx($this->keyName,microtime(true));
        if(!$setnx) {
            return false;
        }
        $expire = $this->redis->expire($this->keyName,$this->expireTime);
        if(!$expire) {
            $this->redis->del($this->keyName);
        }
        return true;
    }

    /**
     * @return int
     */
    public function unLock(){
        return $this->redis->del($this->keyName);
    }

}