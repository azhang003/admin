<?php


namespace app\admin\controller\game;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\controller\member\Redis;
use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use app\common\model\GameEventList as ListModel;
use app\common\model\GameEventRule;
use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class GameList
 * @package app\admin\controller\game
 * @ControllerAnnotation(title="赛事列表")
 */
class GameList extends AdminController
{
    use Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new GameEventList();
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
            try {
                $query = $this->model;
                list($page, $limit, $where) = $this->buildTableParames();
                $swhere = [];
                if (count($where) > 0) {
                    foreach ($where as $key => &$item) {
                        $a = explode('.', $item[0]);
                        if (count($a) > 1) {
                            if (!array_key_exists($a[0], $swhere)) {
                                $swhere[$a[0]] = [];
                            }
                            $item[0] = $a[1];
                            array_push($swhere[$a[0]], $item);
                            unset($where[$key]);
                        }
                        if ($item[0] == "type") {
                            $item[2] = $item[2] == 2 ? "5m" : "1m";
                        }
                    }
                }
                foreach ($swhere as $key => $item) {
                    $query = $query->hasWhere($key, $item);
                }
                $where = array_values($where);
                $count = $query
                    ->where($where)
                    ->order('begin_time desc')
                    ->count();
                $list = $query
                    ->where($where)
                    ->page($page, $limit)
                    ->order('begin_time desc')
                    ->select();
                $CurreryAll = GameEventCurrency::CurreryAll();
                foreach ($list as $item) {
                    $item->gameCurrery = $CurreryAll[$item->cid];
                }
                $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => $count,
                    'data'  => $list,
                ];
                return json($data);
            } catch (\Exception $exception) {
                var_dump($exception->getMessage());
                var_dump($exception->getTrace());
            }
        }
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error_view('数据不存在');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            try {
                $post['hard'] = 1;
                $save = $row->save($post);
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                $this->error_view('保存失败');
            }
            $save ? $this->success_view('保存成功') : $this->error_view('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

}