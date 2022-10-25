<?php


namespace app\admin\controller\merchant;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\controller\member\Account;
use app\common\model\MemberAccount;
use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use think\App;
use think\Request;

/**
 * Class Customers
 * @package app\admin\controller\merchant
 * @ControllerAnnotation(title="用户实名认证")
 */
class MemberProfile extends AdminController
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
        'is_delete',
        'mobile',
        'is_auth',
        'title',
        'type',
    ];

//    protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\common\model\MemberProfile();
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
            $query =   $query->hasWhere('account');
            $where = array_values($where);
            $count = $query
                ->where($where)
                ->count();
            $list  = $query
                ->where($where)
                ->page($page, $limit)
                ->order('MemberAccount.authen desc')
                ->select();
            foreach ($list as $key => $item) {
                $item->account;
                $item->authens = $item->account->authen;
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

    /**
     * @NodeAnotation(title="操作")
     */
    public function adopt(Request $request)
    {
        $row = $this->model->find($request->param('id'));
        empty($row) && $this->error_view('数据不存在');
        try {
            if ($request->param('action') == 1){
                $save = (new MemberAccount())->where([['id','=',$row->mid]])->update(['authen'=>1]);
            }elseif($request->param('action') == 2){
                $save = (new MemberAccount())->where([['id','=',$row->mid]])->update(['authen'=>0]);
            }
        } catch (\Exception $e) {
            $this->error_view($e->getMessage());
        }
        Account::delMemberCache($row->mid);
        $save ? $this->success_view('保存成功') : $this->error_view('保存失败');
    }
    /**
     * @NodeAnotation(title="操作")
     */
    public function agree(Request $request)
    {
        $row = $this->model->where([['id','in',$request->param('id')]])->column('mid');
        empty($row) && $this->error_view('数据不存在');
        try {
            $save = (new MemberAccount())->where([['id','in',$row]])->update(['authen'=>1]);
        } catch (\Exception $e) {
            $this->error_view($e->getMessage());
        }
        $save ? $this->success_view('保存成功') : $this->error_view('保存失败');
    }

}