<?php
declare (strict_types=1);

namespace app\merchant\controller;


use app\common\controller\merchant\Account;
use app\common\controller\User;
use app\common\model\MemberAccount;
use app\common\model\MemberPayOrder;
use app\common\model\UserAccount;
use app\common\model\UserProfile;
use app\merchant\BaseMerchant;
use app\merchant\middleware\jwtVerification;

class Payment extends BaseMerchant
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

    public function getPayment()
    {

        $where = [];

        $page = $this->request->param('page/d', 1);

        $limit = $this->request->param('limit/d', 10);

        $agentLine = $this->request->merchant->id;

        $PayOrder = MemberPayOrder::hasWhere('account', [['agent_line', 'like', '%|' . $agentLine . '|%']]);

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
            $where['status'] = $status;
        }
        $PayOrder = $PayOrder->where($where);

        $count = $PayOrder->count();

        $PayOrder = $PayOrder->page($page)
            ->limit($limit)->select();
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


}
