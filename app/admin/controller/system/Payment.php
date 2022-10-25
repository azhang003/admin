<?php


namespace app\admin\controller\system;


use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\model\SystemPayment;
use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Payment
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="支付方式")
 */
class Payment extends AdminController
{

    use Curd;

//    提现

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemPayment();
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

//    /**
//     * @NodeAnotation(title="添加")
//     */
//    public function add()
//    {
//        if ($this->request->isPost()) {
//            $post = $this->request->post();
//            $rule = [
//                'mobile|客服登录账号' => 'require|alphaNum|length:6,15',
//                'password|登录密码' => 'require|length:6,32',
//                'mid|隶属商户'      => 'require|number',
//            ];
//            $this->validate($post, $rule);
//
//            $post['password'] = password_hash($post['password'], PASSWORD_DEFAULT);
//
//            try {
//                $save = (new Account())->add($post['mobile'],$post['password'],null,$post['mid']);
//            } catch (\Exception $e) {
//                $this->error_view('保存失败:' . $e->getLine());
//            }
//            $save ? $this->success_view('保存成功') : $this->error_view('保存失败');
//        }
//        return $this->fetch();
//    }

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
            if (array_key_exists("password", $post)) {
                $rule["password"] = "require|length:6,32";
            }
            $this->validate($post, $rule);

            if (array_key_exists("password", $post)) {
                $post["password"] = password_hash($post["password"], PASSWORD_DEFAULT);
            }

            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error_view('保存失败');
            }
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
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error_view('保存失败');
            }
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
        $this->success_view('保存成功');
    }

}