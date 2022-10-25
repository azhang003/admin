<?php


namespace app\admin\controller\activity;


use app\admin\model\MallCate;
use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\model\ActivityList;
use app\common\model\ArticleCate;
use think\App;

/**
 * Class Cate
 * @package app\admin\controller\article
 * @ControllerAnnotation(title="活动列表管理")
 */
class Cate extends AdminController
{

    use Curd;

    /**
     * 允许修改的字段
     * @var array
     */
    protected $allowModifyFields = [
        'status',
        'sort',
        'remark',
        'number',
        'must',
        'frequency',
        'is_delete',
        'is_auth',
        'type',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ActivityList();
        $this->assign('ArticleType',[
            0=>'充值',1=>'签到',2=>'真实交易次数',3=>'真实交易天数',4=>'模拟交易次数',5=>'模拟交易天数'
        ]);
    }

}