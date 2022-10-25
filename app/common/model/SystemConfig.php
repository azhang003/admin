<?php
/**
 * Created by : PhpStorm
 * Web: https://www.kaadon.com
 * User: ipioo
 * Date: 2021/6/22 21:31
 */

namespace app\common\model;


use KaadonAdmin\baseCurd\Traits\Model\ModelCurd;

class SystemConfig extends TimeModel
{
    use ModelCurd;

    public static $ModelConfig = [
        'modelCache'       => self::class . 'Cache',
        'modelSchema'      => 'id',
        'modelDefaultData' => [],
    ];

    protected $table = 'ea_system_config';

    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'group'       => 'string',
        'gname'       => 'string',
        'value'       => 'string',
        "create_time" => "timestamp",
        "update_time" => "timestamp",
        "delete_time" => "timestamp",
    ];


    public static function setUpdate(string $group, string $name, array $data)
    {
        $update = self::where('group', $group)->where('gname', $name)->find();
        if (empty($update)) {
            self::setAdd($group, $name, $data);
        } else {

            $update->save([
                'value'       => json_encode($data, true),
                'update_time' => timestamp(),
            ]);
            if (property_exists(self::class, 'ModelConfig') && class_exists(self::$ModelConfig['modelCache'])) {
                (new self::$ModelConfig['modelCache']())->del($group . '-' . $name);
            }
        }
        return $data;

    }


    public static function setAdd(string $group, string $name, array $param)
    {

        $config        = new self();
        $config->group = $group;
        $config->gname = $name;
        $config->value = json_encode($param, true);
        $config->save();

        if (empty($config)) {
            throw new HttpAnomaly('新增配置' . $group . '-' . $name . '失败!');
        }

        return $config;
    }


    public static function setDele(string $group, string $name, $force = false)
    {

        if (!property_exists(self::class, 'ModelConfig') || !array_key_exists('modelSchema', self::$ModelConfig)) {
            throw new TraitsException('Parameter does not exist!');
        }
        $user = self::where('group', $group)->where('gname', $name)->find();
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
            (new self::$ModelConfig['modelCache']())->del($group . '-' . $name);
        }

        return $dele;
    }


    public static function getInfo(string $group, string $name)
    {
        $cache = null;
        $data  = [];

        if (!property_exists(self::class, 'ModelConfig')) {
            throw new TraitsException('Configuration does not exist!');
        }

        if (array_key_exists('modelCache', self::$ModelConfig) && class_exists(self::$ModelConfig['modelCache'])) {
            $cache = new self::$ModelConfig['modelCache']();
            $data  = $cache->get($group . '-' . $name);
        }

        if (empty($data)) {

            $newData = self::where('group', $group)->where('gname', $name)->value('value');

            $data = json_decode($newData, true);

            if (!is_null($cache)) {
                $cache->set($group . '-' . $name, $data);
            }
        }

        return $data;
    }


}






