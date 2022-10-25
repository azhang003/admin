<?php


namespace app\admin\controller\merchant;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\controller\member\Account;
use app\common\model\MemberAccount;
use app\common\model\MerchantProfile;
use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Customers
 * @package app\admin\controller\merchant
 * @ControllerAnnotation(title="登陆信息")
 */
class MemberLogin extends AdminController
{

    use Curd;

//    protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\common\model\MemberLogin();
    }

}