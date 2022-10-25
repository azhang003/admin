<?php
declare (strict_types=1);

namespace app\merchant\controller;


use app\common\controller\merchant\Wallet;
use app\common\controller\User;
use app\common\model\MemberAccount;
use app\common\model\MemberWithdraworder;
use app\common\model\MerchantAccount;
use app\common\model\MerchantWallet;
use app\common\model\UserAccount;
use app\common\model\UserProfile;
use app\merchant\BaseMerchant;
use app\merchant\middleware\jwtVerification;

class Withdraw extends BaseMerchant
{
    protected $middleware
        = [
            jwtVerification::class => [
                'except' => []
            ]
        ];

    public function index()
    {

    }


    public function getWithdraw()
    {

        $where = [];

        $page = $this->request->param('page/d', 1);

        $limit = $this->request->param('limit/d', 10);

        $agentLine = $this->request->merchant->id;

        $PayOrder = MemberWithdraworder::hasWhere('account', [['agent_line', 'like', '%|' . $agentLine . '|%']]);

        $agent = $this->request->param('agent/d', null);
        if (!is_null($agent)){
            $PayOrder = $PayOrder->hasWhere('account', [['agent_line', 'like', '%|' . $agent . '|%']]);
        }

        $profile = $this->request->param('profile');

        if($profile){
            foreach ($profile as $key=> $item) {
                if (empty($item)){
                    unset($profile[$key]);
                }
            }
            if (count($profile) > 0){
                $PayOrder = $PayOrder->hasWhere('profile', $profile);
            }
        }

        $where = [];
        $status = $this->request->param('status/d',null);
        if (!is_null($status)){
            $where['examine'] = $status;
        }
        $PayOrder = $PayOrder->where($where);

        $count = $PayOrder->count();

        $PayOrder = $PayOrder
            ->page($page)
            ->limit($limit)
            ->select();

        foreach ($PayOrder as $key => $item) {
            $PayOrder[$key]->profile;
            $PayOrder[$key]->payment;
        }

        $list = $PayOrder->toArray();
        $pages = ceil($count / $limit);

        $data['count'] = $count;
        $data['pages'] = $pages;
        $data['page']  = $page;
        $data['limit'] = $limit;
        $data['list']  = $list;


        return success($data);
    }
    /**
     * 转入下级会员
     */
    public function give(){
        $user = MemberAccount::where([
            ['id', '=', $this->request->param('id')],
            ['agent_line', 'like', "%".$this->request->merchant->id."%"],
        ])->find();
        if (empty($user)){
            return error('该用户不是你的线下用户');
        }
        $wallet = MerchantWallet::where([
            'uid' => $this->request->merchant->id
        ])->value('cny');
        $number = $this->request->param('money',0);
        if ($wallet < $this->request->param('money')){
            return error('余额不足');
        }
        (new Wallet())->change($this->request->merchant->id,2,[
            1 => [$wallet, -$number, $wallet + -$number],
        ]);
        (new \app\common\controller\member\Wallet())->change($this->request->param('id'),10,[
            1 => [$user->wallet->cny, $number, $user->wallet->cny + $number],
        ]);
        return success('完成');
    }
    public function give_merchant(){
        $user = MerchantAccount::where([
            ['id', '=', $this->request->param('id')],
            ['agent_line', 'like', "%".$this->request->merchant->id."%"],
        ])->find();
        if (empty($user)){
            return error('该用户不是你的线下代理');
        }
        $wallet = MerchantWallet::where([
            'uid' => $this->request->merchant->id
        ])->value('cny');
        $number = $this->request->param('money',0);
        if ($wallet < $this->request->param('money')){
            return error('余额不足');
        }
        (new Wallet())->change($this->request->merchant->id,2,[
            1 => [$wallet, -$number, $wallet + -$number],
        ]);
        (new Wallet())->change($this->request->param('id'),1,[
            1 => [$user->wallet->cny, $number, $user->wallet->cny + $number],
        ]);
        return success('完成');
    }
}
