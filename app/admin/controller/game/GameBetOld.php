<?php


namespace app\admin\controller\game;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\model\GameEventBet;
use app\common\model\GameEventBetOld;
use app\common\model\GameEventList;
use app\common\model\GameEventList as ListModel;
use app\common\model\MemberRecord;
use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class GameBet
 * @package app\admin\controller\game
 * @ControllerAnnotation(title="赛事交易历史")
 */
class GameBetOld extends AdminController
{
    use Curd;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new GameEventBetOld();
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
                    $query = $query->hasWhere($key, $item);
                }
                if (count($swhere) > 0){
                    foreach ($where as $key => &$item) {
                        if ($item[0] == "create_time"){
                            $item[0] = 'GameEventBet.create_time';
                            unset($where[$key]);
                        }
                        if ($item[0] == "type"){
                            $item[0] = 'GameEventList.type';
                            unset($where[$key]);
                        }
                    }
                    $where[] = ['GameEventBet.type','=',0];
                }else{
                    $where[] = ['type','=',0];
                }
                if (count($swhere) > 0){
                    $sort = 'GameEventBet.id desc';
                }else{
                    $sort = 'id desc';
                }
//                $where[] = ['type','=',0];
                $where = array_values($where);
                $count = $query
                    ->where($where)
                    ->order($sort)
                    ->count();
                $list  = $query
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select();
                foreach ($list as $key => $item) {
                    $item->gameList;
                    $item->profile;
                    $item->award = -1;
                    $item->price = MemberRecord::where([
                        ['mid','=',$item->mid],
//                        ['business','=',4],
                        ['currency','=',1],
                        ['create_time','<=',strtotime($item->create_time)],
                    ])->order('id desc')->value('after');
                    $item->game_count = MemberRecord::where([
                        ['mid','=',$item->mid],
                        ['business','=',3],
                        ['currency','=',1],
                        ['create_time','>',strtotime(date('Y-m-d'))],
                    ])->count();
//                    var_dump((new MemberRecord)->getLastSql());
                    if ($item->is_ok == 0 && $item->opentime == 0){
                        $item->award = 0;
                    }
                    if ($item->is_ok == 1){
                        $item->award = 1;
                    }
                }
                $data  = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => $count,
                    'data'  => $list,
                ];
                return json($data);
            }catch (\Exception $exception){
                var_dump($exception->getMessage());
                var_dump($exception->getTrace());
            }
        }
        return $this->fetch();
    }

}