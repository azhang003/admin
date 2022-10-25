<?php

// +----------------------------------------------------------------------
// | KaadonAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/kaadon/kaadonAdmin
// +----------------------------------------------------------------------

namespace KaadonAdmin\upload;

use KaadonAdmin\upload\driver\Alioss;
use KaadonAdmin\upload\driver\Local;
use KaadonAdmin\upload\driver\Qnoss;
use KaadonAdmin\upload\driver\Txcos;
use think\File;

/**
 * 上传组件
 * Class Uploadfile
 * @package KaadonAdmin\upload
 */
class Uploadfile
{

    /**
     * 当前实例对象
     * @var object
     */
    protected static $instance;

    /**
     * 上传方式
     * @var string
     */
    protected $uploadType = 'local';
    /**
     * 上传方式
     * @var string
     */
    protected $apiClassName = 'admin';

    /**
     * 上传配置文件
     * @var array
     */
    protected $uploadConfig;

    /**
     * 需要上传的文件对象
     * @var File
     */
    protected $file;
    /**
     * 是否需要保存
     * @var File
     */
    protected $save = true;

    /**
     * 保存上传文件的数据表
     * @var string
     */
    protected $tableName = 'system_uploadfile';

    /**
     * 获取对象实例
     * @return Uploadfile|object
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 设置上传对象
     * @param $value
     * @return $this
     */
    public function setFile($value)
    {
        $this->file = $value;
        return $this;
    }
    /**
     * 设置上传对象
     * @param $value
     * @return $this
     */
    public function setApiClassName($value)
    {
        $this->apiClassName = $value;
        return $this;
    }

    /**
     * 设置上传对象
     * @param $value
     * @return $this
     */
    public function isSave($value = true)
    {
        $this->save = $value;
        return $this;
    }

    /**
     * 设置上传文件
     * @param $value
     * @return $this
     */
    public function setUploadConfig($value)
    {
        $this->uploadConfig = $value;
        return $this;
    }

    /**
     * 设置上传方式
     * @param $value
     * @return $this
     */
    public function setUploadType($value)
    {
        $this->uploadType = $value;
        return $this;
    }

    /**
     * 设置保存数据表
     * @param $value
     * @return $this
     */
    public function setTableName($value)
    {
        $this->tableName = $value;
        return $this;
    }

    /**
     * 保存文件
     * @return array|void
     */
    public function save()
    {
        $obj = null;
        if ($this->uploadType == 'local') {
            $obj = new Local();
        } elseif ($this->uploadType == 'alioss') {
            $obj = new Alioss();
        } elseif ($this->uploadType == 'qnoss') {
            $obj = new Qnoss();
        } elseif ($this->uploadType == 'txcos') {
            $obj = new Txcos();
        }
        $obj->setUploadConfig($this->uploadConfig)
            ->setUploadType($this->uploadType)
            ->setTableName($this->tableName)
            ->setFile($this->file)
            ->setApiClassPath($this->apiClassName)
            ->setIsSaveTable($this->save);
        if ($this->uploadType == 'local' && !empty($this->uploadConfig['local_domain'])) {
            $obj->setStaticDomain('//' . $this->uploadConfig['local_domain']);
        }
        $save = $obj->save();
        return $save;
    }
}