<?php

namespace app\admin\controller\wallet;

use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\controller\member\Wallet;
use app\common\model\MemberLogin;
use app\common\model\MemberPayOrder;
use app\common\model\MemberWallet;
use app\common\model\MerchantProfile;
use think\App;
use think\Exception;
use think\facade\Console;
use think\facade\Db;

/**
 * Class Recharge
 * @package app\admin\controller\wallet
 * @ControllerAnnotation(title="充值管理")
 */
class Recharge extends AdminController
{
    use Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MemberPayOrder();
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
            foreach ($swhere as$key => $item) {
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
                $list[$key]->profile;
                $list[$key]->account;
                $list[$key]->wallet;
                $list[$key]->payment;
                $agent_line = explode('|',$list[$key]->account->agent_line);
                $list[$key]->agent = MerchantProfile::where([['uid','=',$agent_line[count($agent_line)-2]]])->find()->toArray();
                $list[$key]->allagent = MerchantProfile::where([['uid','=',$agent_line[1]]])->find()->toArray();
                $list[$key]->register_ip = MemberLogin::where([['mid','=',$list[$key]->account->id]])->order('id asc')->value('ip').'/'.MemberLogin::where([['mid','=',$list[$key]->account->id]])->order('id asc')->value('address');
                $list[$key]->account->login_ip = $list[$key]->account->login_ip.'/'.MemberLogin::where([['mid','=',$list[$key]->account->id],['ip','=',$list[$key]->account->login_ip]])->value('address');
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
     * @NodeAnotation(title="通过")
     */
    public function adopt($id){
        $row = $this->model->find($id);
        empty($row) ?  $this->error_view('数据不存在'):$row = $row->toArray();
        if ($row['status'] != "0"){
            $this->error_view('该条数据已操作，不可重复操作');
        }
        if ($this->request->isAjax()) {
            $wallet = (new MemberWallet())->where('mid',$row['mid'])->value('cny');
            Db::startTrans();
            try {
                /*执行主体*/
                $this->model->where('id',$id)
                    ->update([
                        'status'=>1,
                    ]);
                /*执行主体*/
                (new Wallet())->change($row['mid'],1,[
                    1 => [$wallet, +$row['number'], $wallet +$row['number']],
                ]);
                /*提交事务*/
                Db::commit();
            } catch (Exception $e) {
                $this->error_view($e->getMessage());
                /*回滚事务操作*/
                Db::rollback();
                $this->error_view('保存失败');
            }
            $this->success_view('充值通过成功!');
        }
    }

    /**
     * @NodeAnotation(title="手动归集")
     */
    public function collectss($number=10){
        $output = Console::call('CollectFromMember',['-c '.$number]);
//        $resultMessage = $output->fetch();
        $this->success_view($output);
    }

    /**
     * @NodeAnotation(title="拒绝")
     */
    public function refuse($id){
        $row = $this->model->find($id);
        empty($row) ?  $this->error_view('数据不存在'):$row = $row->toArray();
        if ($row['status'] != "0"){
            $this->error_view('该条数据已操作，不可重复操作');
        }
        if ($this->request->isAjax()) {
            Db::startTrans();
            try {
                /*执行主体*/
                $this->model->where('id',$id)
                    ->update([
                        'status'=>2,
                    ]);
                /*提交事务*/
                Db::commit();
            } catch (Exception $e) {
                $this->error_view($e->getMessage());
                /*回滚事务操作*/
                Db::rollback();
                $this->error_view('保存失败');
            }
            $this->success_view('充值拒绝成功!');
        }
    }


}