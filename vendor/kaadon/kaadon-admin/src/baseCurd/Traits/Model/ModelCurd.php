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

namespace KaadonAdmin\baseCurd\Traits\Model;


use KaadonAdmin\baseCurd\Traits\TraitsException;

/**
 * 模型复用
 * Trait Curd
 * @package app\admin\traits
 */
trait ModelCurd
{

//    public static  $ModelConfig = [
//        'modelCache' => self::class.'Cache',
//        'modelSchema' => 'id',
//        'modelDefaultData' => [],
//    ];

    public static function getList($where = [], $page = 1, $limit = 10, $field = '', $order = [], $whereOr = false, $relation = null)
    {

        if (empty($order)) {
            $order = ['create_time' => 'desc'];
        }

        if ($whereOr) {
            $count = self::whereOr($where)
                ->count();

            $query = self::field($field)
                ->whereOr($where)
                ->page($page)
                ->limit($limit)
                ->order($order);
        } else {
            $count = self::where($where)
                ->count();
            $query = self::field($field)
                ->where($where)
                ->page($page)
                ->limit($limit)
                ->order($order);
        }

        $originalList = $query->select();
        $list         = $originalList->toArray();
        if (!empty($relation)){
            foreach ($originalList as $key => $item) {
                if (is_string($relation)) {
                    $list[$key][$relation] = $item->$relation;
                }
                if (is_array($relation)) {
                    foreach ($relation as $vo) {
                        $list[$key][$vo] = $item->$vo;
                    }
                }
            }
        }

        $pages = ceil($count / $limit);


        $data['count'] = $count;
        $data['pages'] = $pages;
        $data['page']  = $page;
        $data['limit'] = $limit;
        $data['list']  = $list;

        return $data;
    }

//

    /**
     * @param array $where
     * @param array $data
     * @return array|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function setUpdate(array $where = [], array $data)
    {
        $update = self::where($where)->find();
        if (empty($update)) {
            return false;
        }
        self::where($where)->update($data);
        if (property_exists(self::class, 'ModelConfig') && array_key_exists(self::$ModelConfig['modelSchema'], $where)) {
            if (class_exists(self::$ModelConfig['modelCache'])) {
                (new self::$ModelConfig['modelCache']())->del($update[self::modelSchema()]);
            }
        }

        return $data;

    }


    public static function setAdd(array $param)
    {
        if (!property_exists(self::class, 'ModelConfig') || !array_key_exists('modelSchema', self::$ModelConfig)) {
            throw new TraitsException('Configuration does not exist!');
        }

        if (in_array(self::$ModelConfig['modelSchema'], $param)) {
            $user = self::onlyTrashed()->where(self::$ModelConfig['modelSchema'], $param[self::$ModelConfig['modelSchema']])->find();

            if (!empty($user)) {
                throw new TraitsException('Value already exists!');
            }
        }
        $data = array_merge(self::$ModelConfig['modelDefaultData'], $param);

        $user = self::create($data);

        if (empty($user)) {
            throw new TraitsException('add failed!');
        }

        return $user;
    }


    public static function setDele($username, $force = false)
    {

        if (!property_exists(self::class, 'ModelConfig') || !array_key_exists('modelSchema', self::$ModelConfig)) {
            throw new TraitsException('Parameter does not exist!');
        }
        $user = self::where(self::$ModelConfig['modelSchema'], $username)->find();
        if (empty($user)) {
            throw new TraitsException('Value does not exist!');
        }
        if ($force) {
            $dele = $user->force()->delete();
        } else {
            $dele = $user->delete();
        }

        if (empty($dele)) {
            throw new TraitsException('failed to delete!');
        }
        if (array_key_exists('modelCache', self::$ModelConfig) && class_exists(self::$ModelConfig['modelCache'])) {
            (new self::$ModelConfig['modelCache']())->del($username);
        }

        return $dele;
    }


    public static function getInfo($username)
    {
        $cache = null;
        $data  = [];

        if (!property_exists(self::class, 'ModelConfig')) {
            throw new TraitsException('Configuration does not exist!');
        }

        if (array_key_exists('modelCache', self::$ModelConfig) && class_exists(self::$ModelConfig['modelCache'])) {
            $cache = new self::$ModelConfig['modelCache']();
            $data  = $cache->get($username);
        }

        if (empty($data) && array_key_exists('modelSchema', self::$ModelConfig)) {

            $Account = self::where(self::$ModelConfig['modelSchema'], $username)->find();

            if (empty($Account)) {
                return [];
            }

            $data = $Account->toArray();

            if (!is_null($cache)) {
                $cache->set($username, $data);
            }
        }


        return $data;
    }


}
