<?php
declare (strict_types=1);

namespace app\member\controller;

use app\common\model\MemberAccount;
use app\common\model\MemberAddress;
use app\common\model\MemberPayment;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberWithdrawOrder;
use app\common\service\RedisLock;
use app\common\validate\UserValidate;
use app\member\BaseCustomer;
use app\member\middleware\jwtVerification;
use think\App;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Lang;

class Wallet extends BaseCustomer
{
    protected $middleware = [
        jwtVerification::class => [
            'except' => ['']
        ]
    ];

    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 充值
     */
    public function recharge()
    {
        $recharge['address'] = MemberAddress::where([['mid', '=', $this->request->customer->mid]])->value('trc_address');
        $recharge['count'] = MemberPayOrder::where([['mid', '=', $this->request->customer->mid], ['status', '=', 1]])->count();
        return success($recharge);
    }

    /**
     * @return \think\response\Json
     * 充值列表
     */
    public function recharge_list()
    {
        return success(MemberPayOrder::getList([
            'mid' => $this->request->customer->mid
        ], request()->param('page', 1), request()->param('limit', 20)));
    }

    /**
     * @return array|string|string[]|\think\response\Json
     * 用户收款方式列表
     */
    public function payment_list()
    {
        return success(MemberPayment::getList([
            'mid' => $this->request->customer->mid
        ], \request()->param('page', 1), \request()->param('limit', 20)));
    }

    /**
     * @return array|string|string[]|\think\response\Json
     * 提交提现
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withdraw()
    {
        /** 枷锁 **/
        if (!(new RedisLock('withdraw:' . $this->request->customer->mid, 5))->lock()) {
            return error(lang::Get('bv'));
        }

        $MemberAccount = MemberAccount::find($this->request->customer->mid);
        $param = $this->request->param();
        if ($param['amount'] < get_config('wallet', 'wallet', 'withdraw_mix')) {
            return error(lang::Get('aq') . get_config('wallet', 'wallet', 'withdraw_mix'));
        }
        $draw = MemberWithdrawOrder::where([
            ['mid', '=', $this->request->customer->mid],
            ['examine', '=', 0],
        ])->find();
        if (!empty($draw)) {
            return error(lang::Get('an'));
        }
        $param['username'] = $MemberAccount->profile->mobile;
        $is_first = MemberRecord::where([['mid', '=', $this->request->customer->mid], ['business', '=', 2]])->find() ? 1 : 0;
        $sms_true = false;
        if ($MemberAccount->level < 2 || $is_first == 0 || $MemberAccount->authen != 1 || $MemberAccount->probability == 1) {
            $sms_true = true;
        }
        if (get_config('wallet', 'wallet', 'withdraw_sms') == "1" && $sms_true == true) {
            if (empty($param['verify_code'])) {
                return error(lang::Get('sms'));
            }
            try {
                validate(UserValidate::class)
                    ->scene('WithDraws')
                    ->check($param);
            } catch (ValidateException $e) {
                return error($e->getMessage(), $e->getMessage());
            }
        } else {
            try {
                validate(UserValidate::class)
                    ->scene('WithDraw')
                    ->check($param);
            } catch (ValidateException $e) {
                return error($e->getMessage(), $e->getMessage());
            }
        }
        $address = $MemberAccount->dashboard->withdraw_address;
        if (!empty($address) && $address != $param['address']) {
            return error(lang::Get('aaa', [$address]));
        }
        if (get_config('wallet', 'wallet', 'withdraw_mix_authon') < $param['amount'] && $MemberAccount->authen != "1") {
            return error(lang::Get('ag') . 1);
        }
        unset($param['username']);
        Db::startTrans();
        try {
            $charge = explode('|', get_config('wallet', 'wallet', 'max'));
            $amount = $charge[$MemberAccount->level] * $param['amount'];
            if (($param['amount'] + $amount) > $MemberAccount->wallet->cny) {
                return error(lang::Get('ao'));
            }
            (new \app\common\controller\member\Wallet())->change($this->request->customer->mid, 2, [
                1 => [$MemberAccount->wallet->cny, -$param['amount'], ($MemberAccount->wallet->cny - $param['amount'])],
            ]);
            (new \app\common\controller\member\Wallet())->change($this->request->customer->mid, 15, [
                1 => [($MemberAccount->wallet->cny - $param['amount']), -$amount, ($MemberAccount->wallet->cny - $param['amount'] - $amount)],
            ]);
            $param['money'] = $param['amount'];
            $param['befor'] = $MemberAccount->wallet->cny;
            $param['after'] = $MemberAccount->wallet->cny - $param['amount'] - $amount;
            $param['fee'] = $amount;
            $param['mid'] = $this->request->customer->mid;
            $MemberWithdrawOrder = new MemberWithdrawOrder;
            $bool = $MemberWithdrawOrder->save($param);
            if (!$bool) {
                throw new Exception(lang::Get('ag'));
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return error($e->getMessage(), 201, 200, $e->getTrace());
        }
        return success(lang::Get('ap'));
    }

    /**
     * 划转
     */
    public function transfer()
    {
        /** 枷锁 **/
        if (!(new RedisLock('transfer:' . $this->request->customer->mid, 5))->lock()) {
            return error(lang::Get('bv'));
        }
        $MemberAccount = MemberAccount::find($this->request->customer->mid);
        $MemberAccounts = MemberAccount::where(['id' => $this->request->param('mid/s')])->find();
        $param = $this->request->param();
        if ($MemberAccount->level != "4") {
            return error(lang::get('ar'));
        }
        $param['username'] = $MemberAccount->profile->mobile;
        try {
            validate(UserValidate::class)
                ->scene('Pay')
                ->check($param);
        } catch (ValidateException $e) {
            return error($e->getMessage(), $e->getMessage());
        }
        Db::startTrans();
        try {
            if ($param['amount'] > $MemberAccount->wallet->cny) {
                return error(lang::Get('ao'));
            }
            (new \app\common\controller\member\Wallet())->change($this->request->customer->mid, 11, [
                1 => [$MemberAccount->wallet->cny, -$param['amount'], $MemberAccount->wallet->cny - $param['amount']],
            ]);
            (new \app\common\controller\member\Wallet())->change($this->request->param('mid'), 10, [
                1 => [$MemberAccounts->wallet->cny, $param['amount'], $MemberAccounts->wallet->cny + $param['amount']],
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return error($e->getMessage(), 201, 200, $e->getTrace());
        }
        return success(lang::Get('as'));
    }

    /**
     * @return array|string|string[]|\think\response\Json
     * 用户提现列表
     */
    public function withdraw_list()
    {
        return success(MemberWithdrawOrder::getList([
            'mid' => $this->request->customer->mid
        ], \request()->param('page', 1), \request()->param('limit', 20)));
    }

}
