<?php

namespace app\admin\controller;


use app\admin\model\SystemAdmin;
use app\admin\model\SystemQuick;
use app\common\controller\AdminController;
use app\common\model\GameEventBet;
use app\common\model\MemberAccount;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MemberWithdrawOrder;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\model\MerchantRecord;
use app\common\model\SystemDay;
use app\common\model\SystemSummarize;
use app\service\controller\Address;
use think\App;
use think\facade\Env;

class Index extends AdminController
{

    /**
     * 后台主页
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        return $this->fetch('', [
            'admin' => session('admin'),
        ]);
    }

    /**
     * 后台欢迎页
     * @return string
     * @throws \Exception
     */
    public function welcome()
    {
        $quicks = SystemQuick::field('id,title,icon,href')
            ->where(['status' => 1])
            ->order('sort', 'desc')
            ->limit(8)
            ->select();
        $this->assign('quicks', $quicks);
        $mid = MemberAccount::where([['analog', '=', 0]])->column('id');
        $today = SystemDay::where('date', date('Y-m-d'))->find();
        $yesterday = SystemDay::where('id', $today->id - 1)->find();
        $yesterdays = SystemDay::where('id', $today->id - 2)->find();
        $yesterdayss = SystemDay::where('id', $today->id - 3)->find();
        $summarize = SystemSummarize::where(1)->find();
        $data = [
            'all_member'        => [
                "title" => "大前日/前日/昨日/（今日提现人次）",
                "data"  =>
                    $yesterdayss->day_withdraw_member . '/' .
                    $yesterdays->day_withdraw_member . '/' .
                    $yesterday->day_withdraw_member
                    . '/ (' . $today->day_withdraw_member . ')',
            ],
            'login_member'      => [
                "title" => "总用户人数/在线人数",
                "data"  => $summarize->all_member . '/[' . $summarize->login_member . ']',
            ],
            'all_transfer'      => [
                "title" => "总划转",
                "data"  => $summarize->all_transfer,
            ],
            'internal_recharge' => [
                "title" => "内部代理充值",
                "data"  => $summarize->internal_recharge,
            ],
            //            'recharge_member'=>[
            //                "title"=>"总充值人数",
            //                "data"=>MemberPayOrder::where([['mid','in',$mid],['status','=','1']])->group('mid')->count().'/'.MemberPayOrder::where([['mid','in',$mid],['status','=','1']])->group('mid')->count(),
            //            ],
            'recharge_member'   => [
                "title" => "大前日/前日/昨日/(今日充值人次)",
                "data"  =>
                    $yesterdayss->day_recharge_member . '/' .
                    $yesterdays->day_recharge_member . '/' .
                    $yesterday->day_recharge_member
                    . '/ (' . $today->day_recharge_member . ')',
            ],
            'manually'          => [
                "title" => "手动打款比数/累计金额",
                "data"  => $summarize->manually_count
                    . '/[' . $summarize->manually_money . ']',
            ],

            'all_charge'          => [
                "title" => "总手续费",
                "data"  => $summarize->all_charge,
            ],
            'day_charge'          => [
                "title" => "昨日/今日手续费",
                "data"  => $yesterday->day_fee
                    . '/[' . $today->day_fee . ']',
            ],
            'recharge_number'     => [
                "title" => "总充值金额",
                "data"  => $summarize->recharge_number,
            ],
            'day_recharge_number' => [
                "title" => "昨日/今日充值金额",
                "data"  => $yesterday->day_recharge_money
                    . '/[' . $today->day_recharge_money . ']',
            ],

            'all_usdt'            => [
                "title" => "总挖矿支出",
                "data"  => $summarize->all_minging,
            ],
            'day_usdt'            => [
                "title" => "昨日/今日挖矿支出",
                "data"  => $yesterday->day_minging
                    . '/[' . $today->day_minging . ']',
            ],
            'withdraw_number'     => [
                "title" => "总提现金额",
                "data"  => $summarize->withdraw_number,
            ],
            'day_withdraw_number' => [
                "title" => "昨日/今日提现金额",
                "data"  => $yesterday->day_withdraw_money
                    . '/[' . $today->day_withdraw_money . ']',
            ],
            'all_team'            => [
                "title" => "总佣金支出",
                "data"  => $summarize->all_team,
            ],
            'day_team'            => [
                "title" => "昨日/今日佣金支出",
                "data"  => $yesterday->day_team
                    . '/[' . $today->day_team . ']',
            ],
            'freeze_member'       => [
                "title" => "总冻结（金额/人数）",
                "data"  => '(' . $summarize->freeze_money . '/' . $summarize->freeze_member . ')',
            ],
            'day_freeze_member'   => [
                "title" => "今日冻结（金额/人数）",
                "data"  => '(' . $today->day_freeze_money
                    . '/' . $today->day_freeze_member . ')',
            ],
            'all_profit'          => [
                "title" => "总赢亏",
                "data"  => $summarize->all_profit,
            ],
            'day_profit'          => [
                "title" => "昨日/今日总赢亏",
                "data"  => $yesterday->day_profit
                    . '/[' . $today->day_profit . ']',
            ],
            'all_number'          => [
                "title" => "总余额",
                "data"  => $summarize->all_number,
            ],
            'internal_recharges'  => [
                "title" => "代理转入充值",
                "data"  => $summarize->internal_recharges,
            ],
            'charges'             => [
                "title" => "后台用户直接充值",
                "data"  => $summarize->charges,
            ],
        ];
        $total['primary_account_address'] = get_config('wallet', 'wallet', 'collection_address');//主账户地址
        $banlance = (new Address())->balance($total['primary_account_address']);
        $total['balance_of_primary_account'] = empty($banlance['balance']) ? 0 : $banlance['balance'];//主账号余额
        $total['master_account_TRX_balance'] = empty($banlance['TRXBalance']) ? 0 : $banlance['TRXBalance'];//主账号Trx余额
        $total['withdrawal_account_address'] = get_config('wallet', 'wallet', 'withdrawal_address');//提现账户地址
        $withdrawal_banlance = (new Address())->balance($total['withdrawal_account_address']);
        $total['withdrawal_account_balance'] = empty($withdrawal_banlance['balance']) ? 0 : $withdrawal_banlance['balance'];//提现账户余额
        $total['TRX_balance_of_withdrawal_account'] = empty($withdrawal_banlance['TRXBalance']) ? 0 : $withdrawal_banlance['TRXBalance'];//提现账户Trx余额
        $this->assign('data', $data);
        $this->assign('all_member', count($mid));
        $this->assign('address', $total);
        $this->assign('trx', get_config('block', 'trx'));
        return $this->fetch();
    }

    /**
     * 修改管理员信息
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editAdmin()
    {
        $id = session('admin.id');
        $row = (new SystemAdmin())
            ->withoutField('password')
            ->find($id);
        empty($row) && $this->error_view('用户信息不存在');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $this->isDemo && $this->error_view('演示环境下不允许修改');
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $row
                    ->allowField(['head_img', 'phone', 'remark', 'update_time'])
                    ->save($post);
            } catch (\Exception $e) {
                $this->error_view('保存失败');
            }
            $save ? $this->success_view('保存成功') : $this->error_view('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * 修改密码
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editPassword()
    {
        $id = session('admin.id');
        $row = (new SystemAdmin())
            ->withoutField('password')
            ->find($id);
        if (!$row) {
            $this->error_view('用户信息不存在');
        }
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $this->isDemo && $this->error_view('演示环境下不允许修改');
            $rule = [
                'password|登录密码'       => 'require',
                'password_again|确认密码' => 'require',
            ];
            $this->validate($post, $rule);
            if ($post['password'] != $post['password_again']) {
                $this->error_view('两次密码输入不一致');
            }

            try {
                $save = $row->save([
                    'password' => password($post['password']),
                ]);
            } catch (\Exception $e) {
                $this->error_view('保存失败');
            }
            if ($save) {
                $this->success_view('保存成功');
            } else {
                $this->error_view('保存失败');
            }
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

}
