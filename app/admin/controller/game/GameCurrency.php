<?php


namespace app\admin\controller\game;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\model\GameEventCurrency;
use think\App;

/**
 * Class GameBet
 * @package app\admin\controller\game
 * @ControllerAnnotation(title="币种")
 */
class GameCurrency extends AdminController
{
    use Curd;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new GameEventCurrency();
    }
    protected $allowModifyFields = [
        'status',
        'sort',
        'remark',
        'a_odds',
        'b_odds',
        'is_delete',
        'is_auth',
        'title',
        'type',
    ];

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
            foreach ($swhere as$key => $item) {
                $query =   $query->hasWhere($key, $item);
            }
            $where = array_values($where);
            $count = $query
                ->where($where)
                ->count();
            $list  = $query
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
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