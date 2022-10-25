<?php


namespace app\admin\controller\article;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\model\ArticleCate;
use app\common\model\ArticleList;
use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Article
 * @package app\admin\controller\article
 * @ControllerAnnotation(title="文章管理")
 */
class Article extends AdminController
{
    use Curd;
//    protected $relationSearch = true;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ArticleList();
        $this->assign('ArticleType',ArticleCate::where(1)->select());
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            $query = $this->model;
            list($page, $limit, $where) = $this->buildTableParames();
            $swhere = [];
//            var_dump($where);exit();
            if (count($where) > 0){
                foreach ($where as $key => $item) {
                    $a =  explode('.',$item[0]);
                    if(count($a) > 1){
                        if (!array_key_exists($a[0],$swhere)){
                            $swhere[$a[0]] = [];
                        }
                        $item[0] = $a[1];
                        array_push($swhere[$a[0]],$item);
                        unset($where[$key]);
                    }
                }
            }
            foreach ($swhere as $key => $item) {
                $query =   $query->hasWhere($key, $item);
            }

            $count = $query
                ->where($where)
                ->count();
            $list  = $query
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            foreach ($list as $key => $item) {
                $item->articleCate;
            }
            $data  = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }


}