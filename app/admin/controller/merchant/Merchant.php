<?php


namespace app\admin\controller\merchant;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\controller\merchant\Account;
use app\common\controller\merchant\Wallet;
use app\common\model\MemberAccount;
use app\common\model\MerchantAccount;
use app\common\model\MerchantRecord;
use app\common\model\MerchantWallet;
use think\App;
use think\Exception;

/**
 * Class Merchant
 * @package app\admin\controller\merchant
 * @ControllerAnnotation(title="商家管理")
 */
class Merchant extends AdminController
{

    use Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MerchantAccount();
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
                $item->index;
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
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = (new Account())->create($post['mobile'], $post['password'],0);
            } catch (\Exception $e) {
                $this->error_view('保存失败:' . $e->getMessage());
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
                $update = [];
                foreach ($post as $key => $item) {
                    if (strpos($key, '_') !== false) {
                        $split                        = explode('_',$key );
                        $update[$split[0]][$split[1]] = $item;
                    } else {
                        if ($key == 'password'){
                            $item = password_hash($item,PASSWORD_DEFAULT);
                        }
                        $update[$key] = $item;
                    }
                }

                foreach ($update as $key => $item) {
                    if (is_array($item)) {
                        $save = $row->$key->save($item);
                    } else {
                        $save = $row->save([$key => $item]);
                    }
                }
            } catch (\Exception $e) {
                $this->error_view($e->getTrace());
            }
            $save ? $this->success_view('保存成功') : $this->error_view('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="用户充值")
     */
    public function charge()
    {
        if ($this->request->param('number')) {
            $post = $this->request->param();
            $number = $post['number'];
            if (!is_numeric($number)){
                $this->error_view('数量类型不正确!');
            }
//            $wallet = $this->model->where('username',$username)->select()->toArray();
            $wallet = MerchantWallet::where('uid',$post['username'])->value('cny');
            if (empty($wallet)){
                $this->error_view('代理不存在!');
            }
            try {
                /*执行主体*/
                (new Wallet())->change($post['username'],3,[
                    1 => [$wallet, $number, $wallet + $number],
                ]);
            } catch (Exception $e) {
                $this->error_view($e->getMessage());
            }
            $this->success_view('充值成功!');
        }
        return $this->fetch();
    }

}