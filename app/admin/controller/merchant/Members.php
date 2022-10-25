<?php


namespace app\admin\controller\merchant;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\controller\member\Account;
use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberLogin;
use app\common\model\MemberWithdrawOrder;
use app\common\model\MerchantProfile;
use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Customers
 * @package app\admin\controller\merchant
 * @ControllerAnnotation(title="客服列表")
 */
class Members extends AdminController
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
        'is_auth',
        'title',
        'type',
    ];

//    protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MemberAccount();
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
            $where = array_values($where);
            $count = $query
                ->where($where)
                ->count();
            $list  = $query
                ->where($where)
                ->page($page, $limit)
//                ->order($this->sort)
                ->order('create_time desc')
                ->select();
            foreach ($list as $key => $item) {
                $item->profile;
                $item->wallet;
                $item->dashboard;
                $item->index;
                if (!empty($item->index)){
                    $item->register_ip = $item->index->register_ip;
                    $item->register_address = $item->index->register_address;
                    $item->login_ip = $item->index->login_ip;
                    $item->login_address = $item->index->login_address;
                }
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
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $rule = [
                'mobile|客服登录账号' => 'require|alphaNum|length:6,15',
                'password|登录密码' => 'require|length:6,32',
                'mid|隶属商户'      => 'require|length:6,32',
            ];
            $this->validate($post, $rule);

            $post['password'] = password_hash($post['password'], PASSWORD_DEFAULT);

            try {
                $save = (new Account())->add($post['mobile'], $post['password'],$post['mid'],'1');
            } catch (\Exception $e) {
                $this->error_view('保存失败:' . $e->getLine());
            }
            $save ? $this->success_view('保存成功') : $this->error_view('保存失败');
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
            $rule = [];
            $this->validate($post, $rule);
            try {
                $update = $post;
                if (!empty($post['password']) && $post['password'] != ""){
                    $update['password'] = password_hash($post['password'],PASSWORD_DEFAULT);
                }else{
                    unset($update['password']);
                }
                $save = $row->save($update);
            } catch (\Exception $e) {
                $this->error_view($e->getMessage());
            }
            Account::delMemberCache($id);
            $save ? $this->success_view('保存成功') : $this->error_view('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="入库")
     */
    public function stock($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error_view('数据不存在');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $rule = [
                "end|增加天数" => "require|number"
            ];
            $this->validate($post, $rule);
            try {
                $post['end'] = $row->end + $post['end'] * 60 * 60 * 24;
                $save        = $row->save($post);
            } catch (\Exception $e) {
                $this->error_view('保存失败');
            }
            Account::delMemberCache($id);
            $save ? $this->success_view('保存成功') : $this->error_view('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $this->checkPostRequest();
        $post = $this->request->post();
        $rule = [
            'id|ID'    => 'require',
            'field|字段' => 'require',
            'value|值'  => 'require',
        ];
        $this->validate($post, $rule);
        $row = $this->model->find($post['id']);
        if (!$row) {
            $this->error_view('数据不存在');
        }
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error_view('该字段不允许修改：' . $post['field']);
        }
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
        } catch (\Exception $e) {
            $this->error_view($e->getMessage());
        }
        Account::delMemberCache($post['id']);
        $this->success_view('保存成功');
    }


}