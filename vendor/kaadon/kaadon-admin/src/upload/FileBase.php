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


use think\facade\Filesystem;
use think\File;

/**
 * 基类
 * Class Base
 * @package KaadonAdmin\upload
 */
class FileBase
{

    /**
     * 上传配置
     * @var array
     */
    protected $uploadConfig;

    /**
     * 上传文件对象
     * @var object
     */
    protected $isSaveTable = true;

    /**
     * 上传文件对象
     * @var object
     */
    protected $file;

    /**
     * 上传完成的文件路径
     * @var string
     */
    protected $completeFilePath;

    /**
     * 上传完成的文件路径
     * @var string
     */
    protected $staticDomain = null;

    /**
     * 上传完成的文件的URL
     * @var string
     */
    protected $completeFileUrl;

    /**
     * 保存上传文件的数据表
     * @var string
     */
    protected $tableName;

    /**
     * 保存上传文件目录区分
     * @var string
     */
    protected $apiClassPath = 'admin';

    /**
     * 上传类型
     * @var string
     */
    protected $uploadType = 'local';

    /**
     * 设置上传方式
     * @param $value
     * @return $this
     */
    public function setStaticDomain($value = null)
    {
        if (!$value) {
            $value = request()->domain();
        }
        $this->staticDomain = $value;
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
     * 是否保存到数据库
     * @param $value
     * @return $this
     */
    public function setIsSaveTable($value = false)
    {
        $this->isSaveTable = $value;
        return $this;
    }

    /**
     * 设置上传配置
     * @param $value
     * @return $this
     */
    public function setUploadConfig($value)
    {
        $this->uploadConfig = $value;
        return $this;
    }

    /**
     * 设置上传配置
     * @param $value
     * @return $this
     */
    public function setApiClassPath($value)
    {
        $this->apiClassPath = $value;
        return $this;
    }

    /**
     * 设置上传配置
     * @param $value
     * @return $this
     */
    public function setFile($value)
    {
        $this->file = $value;
        return $this;
    }

    /**
     * 设置保存文件数据表
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
     */
    public function save()
    {
        $this->completeFilePath = Filesystem::disk('public')->putFile('upload/' . $this->apiClassPath, $this->file);
        if (empty($this->staticDomain)) {
            $this->staticDomain = $this->uploadConfig?:request()->domain();
        }
        $this->completeFileUrl = $this->staticDomain . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $this->completeFilePath);
    }

    /**
     * 删除保存在本地的文件
     * @return bool|string
     */
    public function rmLocalSave()
    {
        try {
            $rm = unlink($this->completeFilePath);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return $rm;
    }

}