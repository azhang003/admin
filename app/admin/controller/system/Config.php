<?php

// +----------------------------------------------------------------------
// | kaadonAdmin
// +----------------------------------------------------------------------
// | AUTHOR: KAADON@GMAIL.COM
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/kaadon/kaadonAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller\system;


use app\admin\service\TriggerService;
use app\common\controller\AdminController;
use app\common\model\SystemConfig;
use Exception;
use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Config
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="系统配置管理")
 */
class Config extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemConfig();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        return $this->fetch();
    }
    /**
     * @NodeAnotation(title="保存")
     */
    public function save()
    {
        $post = $this->request->post();
        try {
            $group = $post['group'];
            $name  = $post['name'];
            if (empty($group) || empty($name)) {
                $this->error_view('配置为空!');
            }
            //拼接 $group.'_'.$name
            $groupName    = $group . '_' . $name;
            $MethodExists = method_exists(self::class, $groupName);
            if ($MethodExists) {
                $post = $this->$groupName($post);
            }
            unset($post['group']);
            unset($post['name']);


            if ($this->model->getInfo($group, $name)) {
                $this->model->setUpdate($group, $name, $post);
            } else {
                $this->model->setAdd($group, $name, $post);
            }
            if ($group == "wallet"){
                file_get_contents("http://127.0.0.1:7800/hook?access_key=A73iWq3nhI4NyKxTNPS7ezE6V6o29kih2jWA4BDmoRAHESf4");
            }
//            TriggerService::updateMenu();
        } catch (Exception $e) {
            $this->error_view($e->getMessage());
        }
        $this->success_view('保存成功');
    }

    private function upload_default(array $data)
    {
        if (array_key_exists('upload_allow_ext', $data)) {
            $data['upload_allow_ext'] = 'doc,gif,ico,icon,jpg,jpeg,png.webp,mp3,mp4,p12,pem,png,rar';
        }

        return $data;
    }

    private function site_rotation(array $data)
    {
        $data = [
            'rotation' => [
                1 => $data['rotation0'],
                2 => $data['rotation1'],
                3 => $data['rotation2'],
            ],
            'slice'    => $data['slice']
        ];

        return $data;
    }


}