<?php


namespace app\admin\controller\system;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\model\SystemQueue;
use Exception;
use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;

/**
 * Class Payment
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="队列错误")
 */
class Queue extends AdminController
{

    use Curd;

//    提现

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemQueue();
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
            list($page, $limit, $where) = $this->buildTableParames();
            $list = $this->model
                ->getList($where,$page,$limit,'',[],false);
            $data  = [
                'code'  => 0,
                'msg'   => '',
                'count' => $list['count'],
                'data'  => $list['list'],
            ];
            return json($data);
        }
        return $this->fetch();
    }
    /**
     * @NodeAnotation(title="批量重置")
     */
    public function agree(){
//        $param = $this->request->param('id');
        $row = $this->model->where([['type','=',0]])->limit(50000)->select();
        empty($row) ?  $this->error_view('数据不存在'): $row->toArray();
        if ($this->request->isAjax()) {
            Db::startTrans();
            try {
                /*执行主体*/
                foreach ($row as $item){
                    $this->model->where([
                        ['id','in',$item['id']],
                        ['type','=',0],
                    ])->update([
                        'type'=>1,
                        'update_time'=>time(),
                    ]);
                    if ($item['type'] == "0"){
                        \think\facade\Queue::later('1', $item['controller'], json_decode($item['context'],true), $item['title']);
                    }
                }
                /*提交事务*/
                Db::commit();
            } catch (Exception $e) {
                $this->error_view($e->getMessage());
                /*回滚事务操作*/
                Db::rollback();
                $this->error_view('保存失败');
            }
            $this->success_view('提现通过成功!');
        }

    }

}