<?php
declare (strict_types=1);

namespace app\member\controller;

use app\common\controller\member\Account;
use app\common\model\MemberAccount;
use app\common\model\MemberProfile;
use app\common\validate\UserValidate;
use app\job\queueCheckIp;
use app\member\BaseCustomer;
use app\member\middleware\jwtVerification;
use Exception;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Lang;
use think\response\Json;

class Member extends BaseCustomer
{
    protected $middleware
        = [
            jwtVerification::class => [
                'except' => ['register', 'login','logins', 'index', 'forgotPassword']
            ]
        ];

    public function login()
    {
        $data             = [];
        $data['username'] = $this->request->post('username/s');
        $data['password'] = $this->request->post('password/s');
        $scene = "MobileLogin";
        if (strpos($data['username'],' ')){
            return error(lang('ak'));
        }
        try {
            validate(UserValidate::class)
                ->scene($scene)
                ->check($data);
        } catch (ValidateException $e) {
            return error($e->getMessage(),$e->getMessage());
        }
        try {
            /*执行主体*/
            $customer = MemberProfile::where('mobile', $data['username'])->find();
            $ip = $this->request->header('x-forwarded-for');
            $ip = explode(',',$ip);

            /** 获取会员等你Ip以及记录会员最后登录时间 **/
            $memberLoginCheckIpData = [
                'mid' => $customer->mid,
                'ip' => $ip[0],
                'time' => time()
            ];
            queue(queueCheckIp::class,$memberLoginCheckIpData,0,'memberLoginCheckIp');

            Account::setMemberCache($customer->account->toArray(),$customer->toArray());

            /** 返回token **/
            $token = 'kaadon ' . jwt_create($customer->account->uuid, [
                    'type'       => 'customer',
                    'id'         => $customer->account->id,
                    'ips'         => $ip[0],
                    'agent_line' => $customer->account->agent_line,
                    'analog' => $customer->account->analog,
                    'uuid'       => $customer->account->uuid,
                    'mid'        => $customer->mid,
                ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }

        return success([
            "account" => $customer,
            "token"   => $token,
        ]);
    }
    public function logins()
    {
        $ip = $this->request->header('x-real-ip');
        $ip = explode(',',$ip);
        var_dump($ip[0]);
    }

    public function register()
    {
        $data             = [];
        $data['username'] = $this->request->param('username/s');
        $data['password'] = $this->request->param('password/s');
        $data['verify_code'] = $this->request->param('verify_code/s');
        if (strpos($data['username'],' ')){
            return error(lang('ak'));
        }
        $scene = strpos('@', $data['username']) ? "EmailRegister" : "MobileRegister";
        try {
            validate(UserValidate::class)
                ->scene($scene)
                ->check($data);
        } catch (ValidateException $e) {
            return error($e->getMessage(),$e->getMessage());
        }
        $ip = $this->request->header('x-forwarded-for');
        $ip = explode(',',$ip);
        $address = get_ip_address($ip['0']);
        if ($address){
            if (strpos($address,"CN")){
                /** 禁止中国用户注册 **/
                return error(lang::Get('ak'));
            }
        }
        try {
            $result = (new Account())->add($data['username'], $data['password'], $this->request->param('inviter/s', null),0,$this->request->param('nickname/s'),$this->request->param('safeword/s'),$ip[0],$address);
        } catch (Exception $e) {
            return error($e->getMessage());
        }
        if (!$result) {
            return error(lang::Get('ak'));
        }
        return success(lang::Get('al'));
    }
    public function forgotPassword()
    {
        $data             = [];
        $data['username'] = $this->request->param('username/s');
        $data['password'] = $this->request->param('password/s');
        $data['verify_code'] = $this->request->param('verify_code/s');
        try {
            validate(UserValidate::class)
                ->scene('MobileForget')
                ->check($data);
        } catch (ValidateException $e) {
            return error($e->getMessage());
        }
        try {
            $update = [
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ];
            $mid = MemberProfile::where([['mobile','=',$data['username']]])->value('mid');
            /*执行主体*/
            $result =MemberAccount::setUpdate(['id' => $mid], $update);
        } catch (Exception $e) {
            return error($e->getMessage());
        }
        if (!$result) {
            return error(lang::Get('ah'));
        }

        return success(lang::Get('am'));
    }

    /**
     * 修改登录密码
     * @return Json
     */
    public function updatePassword()
    {
        $data = [
            'username'    => MemberProfile::where('mid', $this->request->customer->mid)->value('mobile'),
            'password'    => $this->request->param('password/s', null),
            'newpassword' => $this->request->param('newpassword/s', null),
        ];
        try {
            validate(UserValidate::class)
                ->scene('UpdatePassword')
                ->check($data);
        } catch (ValidateException $e) {
            return error($e->getError());
        }
        //开启事务操作
        Db::startTrans();
        try {
            $update = [
                'password' => password_hash($data['newpassword'], PASSWORD_DEFAULT),
            ];
            /*执行主体*/
            MemberAccount::setUpdate(['id' => $this->request->customer->mid], $update);
            /*提交事务*/
            Db::commit();
        } catch (\Exception $e) {
            /*回滚事务操作*/
            Db::rollback();
            return error($e->getMessage());
        }
        return success(lang::Get('am'));
    }

    /**
     * 修改交易密码
     * @return Json
     */
    public function updateSafePassword()
    {
        $data = [
            'username'    => MemberProfile::where('mid', $this->request->customer->mid)->value('mobile'),
            'newpassword' => $this->request->param('newpassword/s', null),
            'verify_code' => $this->request->param('verify_code/s', null),
        ];
        try {
            validate(UserValidate::class)
                ->scene('UpdateSafePassword')
                ->check($data);
        } catch (ValidateException $e) {
            return error($e->getError());
        }
        //开启事务操作
        Db::startTrans();
        try {
            $update = [
                'safeword' => password_hash($data['newpassword'], PASSWORD_DEFAULT),
            ];
            /*执行主体*/
            MemberAccount::setUpdate(['id' => $this->request->customer->mid], $update);
            /*提交事务*/
            Db::commit();
        } catch (\Exception $e) {
            /*回滚事务操作*/
            Db::rollback();
            return error($e->getMessage());
        }
        return success(lang::Get('am'));
    }

    public function authen()
    {
        $data = [
            "alipay"   => $this->request->param('alipay/s', null),
            "bank"     => $this->request->param('bank/s', null),
            "bankcard" => $this->request->param('bankcard/s', null),
            "realname" => $this->request->param('realname/s', null),
            "idcard" => $this->request->param('idcard/s', null),
            "wechat"   => $this->request->param('wechat/s', null)
        ];
//        try {
//            validate(UserValidate::class)
//                ->scene('Profile')
//                ->check($data);
//        } catch (ValidateException $e) {
//            return error($e->getError());
//        }
        $data['certificate'] = $this->request->param('card_obverse/s', null).'|'.$this->request->param('card_reverse/s', null).'|'.$this->request->param('card_hand/s', null);
        $MemberAccount = MemberAccount::where('id',$this->request->customer->mid)->find();
        if ($MemberAccount->authen == 1) {
            //实名之后禁止更改姓名
            unset($data['realname']);
        }
        $data['update_time'] = time();
        try {
            $data['mid'] = $this->request->customer->mid;
            $MemberAccount->profile()->where('mid',$this->request->customer->mid)->update($data);
            if ($MemberAccount->authen == "0") {
                $MemberAccount->save(['authen' => 2]);
            }
            return success(lang::Get('am'));
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return error(lang::Get('an'));
        }
    }


}
