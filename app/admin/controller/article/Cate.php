<?php


namespace app\admin\controller\article;


use app\admin\model\MallCate;
use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\model\ArticleCate;
use think\App;

/**
 * Class Cate
 * @package app\admin\controller\article
 * @ControllerAnnotation(title="文章管理")
 */
class Cate extends AdminController
{

    use Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ArticleCate();
    }

}