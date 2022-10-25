<?php
declare (strict_types=1);

namespace app\merchant\controller;


use app\common\model\MemberAccount;
use app\merchant\BaseMerchant;
use app\merchant\middleware\jwtVerification;

class Member extends BaseMerchant
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

    public function getMember()
    {

        $where = [];

        $page = $this->request->param('page/d', 1);

        $limit = $this->request->param('limit/d', 10);

        $agentLine = $this->request->merchant->id;

        $PayOrder = MemberAccount::where([['agent_line', 'like', '%|' . $agentLine . '|%']]);

        $agent = $this->request->param('agent/d', null);

        if (!is_null($agent)){
            $PayOrder = $PayOrder->where([['agent_line', 'like', '%|' . $agent . '|%']]);
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
            $PayOrder[$key]->dashboard;
            $PayOrder[$key]->wallet;
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

    public function saveEdit()
    {
        $param = $this->request->param();
        if (!$param['id']) {
            return error('账户标识不存在!');
        }
        $member = MemberAccount::find($param['id']);
        if (!$member) {
            return error('账户不存在!');
        }
        if (strpos((string)$this->request->merchant->id, $member->agent_line)) {
            return error('无权限!');
        }
        $update = [];

        foreach ($param as $key => $item) {
            if (is_array($item)) {
                $member->$key->save($item);
            } else {
                if ($item == 0 || !empty($item)) {
                    $update[$key] = $item;
                }
            }
        }
        $member->save($update);

        return success('操作成功!');
    }

    public function saveDelete(){
        $param = $this->request->param();
        if (!$param['id']) {
            return error('账户标识不存在!');
        }
        $merchant = MemberAccount::find($param['id']);
        if (!$merchant) {
            return error('账户不存在!');
        }
        if (strpos($param['id'], $merchant->agent_line)) {
            return error('无权限!');
        }
        $merchant->profile()->delete();
        $merchant->wallet()->delete();
        $merchant->dashboard()->delete();
        $merchant->delete();

        return success('操作成功!');

//        }



    }

    public function saveAdd()
    {
        $result = (new \app\common\controller\member\Account())->add($this->request->param('mobile'),$this->request->param('password'),$this->request->merchant->uuid,1);
        if (!$result){
            return error('添加失败!');
        }

        return success('操作成功!');

    }
}
