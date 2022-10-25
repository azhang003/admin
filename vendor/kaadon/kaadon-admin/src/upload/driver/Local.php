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

namespace KaadonAdmin\upload\driver;

use KaadonAdmin\upload\FileBase;
use KaadonAdmin\upload\trigger\SaveDb;

/**
 * 本地上传
 * Class Local
 * @package KaadonAdmin\upload\driver
 */
class Local extends FileBase
{

    /**
     * 重写上传方法
     * @return array|void
     */
    public function save()
    {
        parent::save();
        if ($this->isSaveTable == true){
            SaveDb::trigger($this->tableName, [
                'upload_type'   => $this->uploadType,
                'original_name' => $this->file->getOriginalName(),
                'mime_type'     => $this->file->getOriginalMime(),
                'file_ext'      => strtolower($this->file->getOriginalExtension()),
                'url'           => $this->completeFileUrl,
                'domain'           => $this->completeFileUrl,
                'path'           => $this->completeFilePath,
                'create_time'   => time(),
            ]);
        }

        return [
            'save' => true,
            'msg'  => '上传成功',
            'url'  => $this->completeFileUrl,
        ];
    }

}