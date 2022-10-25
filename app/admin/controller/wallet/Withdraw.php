<?php

namespace app\admin\controller\wallet;

use app\admin\traits\Curd;
use app\common\controller\AdminController;
use app\common\controller\member\Wallet;
use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberDashboard;
use app\common\model\MemberLogin;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MemberWithdrawOrder;
use app\common\model\MerchantDashboard;
use app\common\model\MerchantProfile;
use app\common\model\SystemSummarize;
use think\App;
use think\Exception;
use think\facade\Db;

/**
 * Class Withdraw
 * @package app\admin\controller\wallet
 * @ControllerAnnotation(title="提现管理")
 */
class Withdraw extends AdminController
{
    use Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MemberWithdrawOrder();
    }
    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            try {
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
                    $item->profile;
                    $item->account;
                    $item->wallet;
                    $item->payment;
                    $item->agent = $item->index->agent;
                    $item->share = $item->index->share;//分享
                    $item->old_money = abs(MemberRecord::where([
                        ['mid','=',$item->mid],
                        ['currency','=',1],
                        ['business','=',2],
                        ['create_time','<=',strtotime($item['create_time'])]
                    ])->order('id desc')->value('before'));//提现前余额
                    $item->now_money = abs(MemberRecord::where([
                        ['mid','=',$item->mid],
                        ['currency','=',1],
                        ['business','=',2],
                        ['create_time','<=',strtotime($item['create_time'])]
                    ])->order('id desc')->value('after'));//提现前余额
                    $item->allagent = $item->index->register_ip.'/'.$item->index->register_address;
                    $item->register_ip = $item->index->register_ip.'/'.$item->index->register_address;
                    $item->login_ip = $item->index->login_ip.'/'.$item->index->login_address;
                    $item->bet_count = $item->dashboard->game_bet;//游戏局数
                    $item->win = $item->index->win;//净赢
                    $item->bet = $item->dashboard->all_bet;//净赢
                    $item->wins = $item->dashboard->all_prize;//净赢
                    $item->paymaddre = get_config('wallet','wallet','withdrawal_address');//放款地址
//                    var_dump($list[$key]->win);exit();
                }
                $data  = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => $count,
                    'data'  => $list,
                ];
                return json($data);
            }catch (Exception $exception){
                var_dump($exception->getTrace());
            }

        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="通过")
     */
    public function adopt($id){
        $row = $this->model->find($id);
        empty($row) ?  $this->error_view('数据不存在'):$row = $row->toArray();
        if ($row['examine'] != "0"){
            $this->error_view('该条数据已操作，不可重复操作');
        }
        if ($this->request->isAjax()) {
            Db::startTrans();
            try {
                if ($row['money'] > get_config('wallet','wallet','manually')){
                    $this->model->where('id',$id)
                        ->update([
                            'examine'=>1,
                            'status'=>1,
                            'time'=>time(),
                        ]);
                }else{
                    /*执行主体*/
                    $this->model->where('id',$id)
                        ->update([
                            'examine'=>1,
                            'time'=>time(),
                        ]);
                    MemberDashboard::where('mid',$row['mid'])->update([
                        'withdraw_address' => $row['address']
                    ]);
                }
                SystemSummarize::where('id',211)
                    ->dec('freeze_money',$row['money'])
                    ->dec('freeze_member')
                    ->update();

//                $agent_line = (new MemberAccount())->where('id',$row['mid'])->value('agent_line');
//                $agent = explode('|',$agent_line);
                /*执行主体*/
//                MerchantDashboard::where([['uid','in',$agent]])
//                    ->dec('team_withdraw_examine',$row['amount'])
//                    ->update();
//                MemberDashboard::where([['mid','=',$row['mid']]])
//                    ->dec('user_withdraw_examine',$row['amount'])
//                    ->update();
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

    /**
     * @NodeAnotation(title="批量通过")
     */
    public function agree(){
        $param = $this->request->param('id');
        $row = $this->model->where([['id','in',$param]])->select();
        empty($row) ?  $this->error_view('数据不存在'): $row->toArray();
        $manually = get_config('wallet','wallet','manually');
        if ($this->request->isAjax()) {
            Db::startTrans();
            try {
                /*执行主体*/
                $this->model->where([
                    ['id','in',$param],
                    ['examine','=',0],
                    ['money','>',$manually],
                ])->update([
                        'examine'=>1,
                        'status'=>1,
                        'time'=>time(),
                    ]);
                $this->model->where([
                    ['id','in',$param],
                    ['examine','=',0],
                    ['money','<',$manually],
                ])->update([
                        'examine'=>1,
                        'time'=>time(),
                    ]);
                foreach ($param as $item){
                    $draw = $this->model->where('id',$item)->find();
                    $draw->dashboard->withdraw_address = $draw->address;
                    $draw->dashboard->save();
                    SystemSummarize::where('id',211)
                        ->dec('freeze_money',$draw->money)
                        ->dec('freeze_member')
                        ->update();
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

    /**
     * @NodeAnotation(title="批量拒绝")
     */
    public function turn(){
        $param = $this->request->param('id');
        $rows = $this->model->where([
            ['id','in',$param],
            ['examine','=',0],
        ])->select();
        empty($rows) ?  $this->error_view('数据不存在'): $rows->toArray();
        if ($this->request->isAjax()) {
            foreach ($rows as $item){
                Db::startTrans();
                try {
                    $wallet = (new MemberWallet())->where('mid',$item['mid'])->value('cny');
                    /*执行主体*/
                    $this->model->where('id',$item['id'])
                        ->update([
                            'examine'=>2,
                            'confirm'=>session('admin.username'),
                        ]);
                    (new Wallet())->change($item['mid'],5,[
                        1 => [$wallet, +$item['money']+$item['fee'], $wallet +$item['money']+$item['fee']],
                    ]);
                    /*提交事务*/
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                }
            }
            $this->success_view('提现通过成功!');
        }
    }

    /**
     * @NodeAnotation(title="拒绝")
     */
    public function refuse($id){
        $row = $this->model->find($id);
        empty($row) ?  $this->error_view('数据不存在'):$row = $row->toArray();
        if ($row['examine'] != "0"){
            $this->error_view('该条数据已操作，不可重复操作');
        }
        if ($this->request->isAjax()) {
            Db::startTrans();
            $wallet = (new MemberWallet())->where('mid',$row['mid'])->value('cny');
            try {
                /*执行主体*/
                $this->model->where('id',$id)
                    ->update([
                        'examine'=>2,
                        'confirm'=>session('admin.username'),
                    ]);
                (new Wallet())->change($row['mid'],5,[
                    1 => [$wallet, +$row['money']+$row['fee'], $wallet +$row['money']+$row['fee']],
                ]);
                /*提交事务*/
                Db::commit();
            } catch (Exception $e) {
                /*回滚事务操作*/
                Db::rollback();
                $this->error_view($e->getMessage());
            }
            $this->success_view('提现拒绝成功!');
        }
    }


}