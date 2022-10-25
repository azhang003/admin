<?php
declare (strict_types=1);

namespace app\merchant\controller;

use app\common\controller\GameController;
use app\common\controller\member\Wallet;
use app\common\controller\merchant\Account;
use app\common\model\GameEventBet;
use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use app\common\model\MemberAccount;
use app\common\model\MemberAddress;
use app\common\model\MemberIndex;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberTeam;
use app\common\model\MemberWallet;
use app\common\model\MemberWithdrawOrder;
use app\common\model\MerchantAccount;
use app\common\model\MerchantDashboard;
use app\common\model\MerchantDay;
use app\common\model\MerchantIndex;
use app\common\model\MerchantProfile;
use app\common\model\MerchantRecord;
use app\common\model\MerchantWallet;
use app\common\service\Integration;
use app\merchant\BaseMerchant;
use app\merchant\middleware\jwtVerification;
use app\merchant\validate\MerchantValidate;
use app\Request;
use app\service\controller\Address;
use think\Exception;
use think\exception\ValidateException;

class Index extends BaseMerchant
{
    protected $middleware
        = [
            jwtVerification::class => [
                'except' => ['register', 'login', 'index']
            ]
        ];

    public function index()
    {
    }

    public function login()
    {
        $data = [];
        $data['type'] = $this->request->post('type/s', null);
        if (empty($data['type'])) {
            return error('登录类型不能为空!');
        }
        $data['password'] = $this->request->post('password/s');

        switch ($data['type']) {
            case 'email':
                $data['email'] = $this->request->post('username/s');
                break;
            case 'mobile':
                $data['mobile'] = $this->request->post('username/s');
                break;
            default:
                return error('请重新提交');
        }
//        var_dump(password_hash('123456',PASSWORD_DEFAULT));
        $data['verify_img_id'] = $this->request->post('verify_img_id/s');
        $data['verify_img_code'] = $this->request->post('verify_img_code/s');

        try {
            validate(MerchantValidate::class)
                ->scene('Login')
                ->check($data);
        } catch (ValidateException $e) {
            return error($e->getMessage());
        }

        try {
            /*执行主体*/
            $profile = MerchantProfile::where($data['type'], $data[$data['type']])->find();

            $token = 'kaadon ' . jwt_create($profile->account->uuid, [
                    'type'       => 'merchant',
                    'id'         => $profile->account->id,
                    'uuid'       => $profile->account->uuid,
                    'agent_line' => $profile->account->agent_line,
                    'agentLv'    => $profile->account->agent
                ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        unset($profile->account->password, $profile->account->safeword);
        return success([
            "profile" => $profile,
            "token"   => $token,
            "webh5"   => get_config('site', 'site', 'webh5')
        ]);
    }

    //业务员id

    public function UserActions(Request $request)
    {
        $status = $request->param('status');
        $id = $request->param('id');
        if (!empty($status)) {
            switch ($status) {
                case 1:
                    $status = MemberAccount::where(['id' => $id])->value('status');
                    if ($status == 1) {
                        $data = MemberAccount::where(['id' => $id])->save(['status' => 0]);
                    } else {
                        $data = MemberAccount::where(['id' => $id])->save(['status' => 1]);
                    }
                    return success($data);
                case 2:
                    $type = MemberAccount::where(['id' => $id])->value('type');
                    if ($type == 1) {
                        $data = MemberAccount::where(['id' => $id])->save(['type' => 0]);
                    } else {
                        $data = MemberAccount::where(['id' => $id])->save(['type' => 1]);
                    }
                    return success($data);
                case 3:
                    $is_super = MemberAccount::where(['id' => $id])->value('status');
                    if ($is_super == 1) {
                        $data = MemberAccount::where(['id' => $id])->save(['status' => 0]);
                    } else {
                        $data = MemberAccount::where(['id' => $id])->save(['status' => 1]);
                    }
                    return success($data);
            }
            \app\common\controller\member\Account::delMemberCache($id);
        }
    }

    //上级id

    public function addInternalNumber(Request $request)
    {
        $result = (new \app\common\controller\member\Account())->add($this->request->param('mobile'), $this->request->param('password'), $this->request->merchant->uuid, 1);
        if (!$result) {
            return error('添加失败!');
        }
        return success('操作成功!');
    }

    //余额

    public function userList(Request $request)
    {
        $page = $request->param('page/d', 1);
        $limit = $request->param('limit/d', 10);
        $mid = $request->param('mid');//玩家ID
        $uid = $request->param('uid');//业务员ID
        $mobile = $request->param('mobile');//手机号
        $user_type = $request->param('user_type');//用户类型
        $registration_ip = $request->param('registration_ip');//注册IP
        $start_time = $request->param('start_time');
        $end_time = $request->param('end_time');
        $Member = (new MemberAccount)->hasWhere('dashboard');
        $where = [];
        $agentLine = $this->request->merchant->id;
        if (!empty($mobile)) {
            $Member = $Member->hasWhere('profile', [['mobile', '=', $mobile]]);
        }
        if (!empty($mid)) {
            $where[] = ['MemberAccount.id', '=', $mid];
        }
        if (!empty($registration_ip)) {
            $where[] = ['MemberAccount.login_ip', '=', $registration_ip];
        }
        if (!empty($uid)) {
            $where[] = ['MemberAccount.agent_line', 'like', "%" . $uid . "%"];
        } else {
            $where[] = ['MemberAccount.agent_line', 'like', "%" . $agentLine . "%"];
        }
        if (!empty($start_time)) {
            $where[] = ['MemberAccount.login_time', '>', $start_time];
        }
        if (!empty($end_time)) {
            $where[] = ['MemberAccount.login_time', '<', $end_time];
        }
        // 1关注用户 2拉黑用户 3重复IP注册 4初代用户 5非初代用户
        if (!empty($user_type)){
            switch ($user_type) {
                case 1:
                    $mids = $this->mid_list(11, $agentLine);
                    break;
                case 2:
                    $mids = $this->mid_list(12, $agentLine);
                    break;
                case 3:
                    $mids = $this->mid_list(5, $agentLine);
                    break;
                case 4:
                    $mids = $this->mid_list(1, $agentLine);
                    break;
                case 5:
                    $mids = $this->mid_list(2, $agentLine);
                    break;
                default:
                    $mids = MemberAccount::where('agent_line', 'like', "%|" . $agentLine . "|%")->column('id');
                    break;
            }
            $where[] = ['MemberAccount.id', 'in', $mids];
        }
        $order_list = [
            'cny'           => 'cny',
            'bets_number'   => 'game_bet',
            'net_win'       => 'user_profit',
            'user_recharge' => 'user_recharge',
            'user_withdraw' => 'user_withdraw',
            'Transfer_out'  => 'out',
            'into'          => 'into',
            'amount_frozen' => 'user_withdraw_examine',
            'dig'           => 'minging',
            'total_revenue' => 'total_revenue',
            'fee'           => 'fee',
        ];
        if (!empty($request->param('sort'))) {
            $order = 'MemberDashboard.' . $order_list[$request->param('sort')] . ' ' . str_replace('ending', '', $request->param('sorts'));
        } else {
            $order = 'MemberDashboard.id desc';
        }
        $data = $Member->where($where)->order($order)->page($page, $limit)->select();
        $count = $Member->where($where)->count();
        foreach ($data as $k => $datum) {
            $Data[$k]['agent_line'] = $datum->index->agent_id;//上级ID
            $Data[$k]['id'] = $datum->id;//玩家ID
            $Data[$k]['user_type'] = $datum->type;//玩家类型 0：正常用户 1：关注用户
            $Data[$k]['mobile'] = $datum->profile->mobile;//手机号
            $Data[$k]['nickname'] = $datum->profile->nickname;//昵称
            $Data[$k]['merchant_nickname'] = $datum->index->agent;//业务员昵称
            $Data[$k]['cny'] = $datum->wallet->cny;//余额
            $Data[$k]['ip'] = $datum->index->register_ip;//ip
            $Data[$k]['repeat'] = $datum->index->repeat;//repeat
            $Data[$k]['address'] = $datum->index->register_address;//ip
            $Data[$k]['now_address'] = $datum->index->login_address;//ip
            $Data[$k]['now_ip'] = $datum->index->login_ip;//ip
            $Data[$k]['bets_number'] = $datum->index->bet_count;//游戏局数
            $Data[$k]['net_win'] = $datum->index->win;//净赢
            $Data[$k]['user_recharge'] = $datum->index->recharge;//充值
            $Data[$k]['user_withdraw'] = $datum->index->withdraw;//提现
            $Data[$k]['Transfer_out'] = $datum->index->Transfer_out;//转出
            $Data[$k]['into'] = $datum->index->into;//转入
            $Data[$k]['amount_frozen'] = $datum->index->freeze;//冻结金额
            $Data[$k]['dig'] = $datum->index->ming;//挖矿
            $Data[$k]['total_revenue'] = $datum->index->share;//总收益
            $Data[$k]['received_income'] = $datum->index->share_receive;//已领取
            $Data[$k]['residual_income'] = $datum->index->share;//剩余收益
            $Data[$k]['create_time'] = $datum->create_time;//创建时间
            $Data[$k]['frozen'] = $datum->status;//封号 1：正常，0：冻结
            $Data[$k]['super_member'] = $datum->is_super;//超级会员 0：正常，1：超级会员
            $Data[$k]['internal'] = $datum->index->into;//内部充值
            $Data[$k]['fee'] = $datum->index->fee;//手续费
        }
        $Datas['page'] = $page;
        $Datas['limit'] = $limit;
        $Datas['count'] = $count;
        $Datas['data']['list'] = empty($Data) ? [] : $Data;
        return success($Datas);
    }

    //游戏局数

    public function mid_list($type, $uid, $id = 1)
    {
        $mid = [];
        switch ($type) {
            case 1:
                //初代用户（直属用户）
                $agent = MerchantAccount::where([['id', '=', $uid]])->find();
                if (!empty($agent)) {
                    $mid = MemberAccount::where([['inviter', '=', $agent->uuid]])->column('id');
                }
                break;
            case 2:
                //非初代用户（非直属用户）
                $agent = MerchantAccount::where([['id', '=', $uid]])->find();
                if (!empty($agent)) {
                    $mid = MemberAccount::where([['inviter', '<>', $agent->uuid]])->column('id');
                }
                break;
            case 3:
                //二级用户
                $mid = $this->mid_list(1, $uid);
                if (count($mid) > 0) {
                    $mid = MemberAccount::where([['agent_line', 'in', $mid]])->column('id');
                }
                break;
            case 4:
                //三级用户
                $mid = $this->mid_list(3, $uid);
                if (count($mid) > 0) {
                    $mid = MemberAccount::where([['agent_line', 'in', $mid]])->column('id');
                }
                break;
            case 5://IP重复用户
                $mid = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%']])
                    ->field('login_ip')
                    ->group('login_ip')
                    ->having('count(login_ip)>2')
                    ->column('id');
                break;
            case 6: //超出比例用户
                $mid = GameEventBet::hasWhere('account', ['agent_line', 'like', '%|' . $uid . '|%'])->where([['list_id', '=', $id ?? 1], ['excess_proportion', '>', 0]])->column('mid');
                break;
            case 7://全部用户
                $mid = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%']])->column('id');
                break;
            case 8://买涨用户
                $mids = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%']])->column('id');
                $mid = GameEventBet::where([['mid', 'in', $mids], ['bet', '=', 1]])->column('mid');
                break;
            case 9://买跌用户
                $mids = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%']])->column('id');
                $mid = GameEventBet::where([['mid', 'in', $mids], ['bet', '=', 2]])->column('mid');
                break;
            case 10://普通用户
                $mid = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%'], ['type', '=', 0]])->column('id');
                break;
            case 11://关注用户
                $mid = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%'], ['type', '=', 1]])->column('id');
                break;
            case 12://拉黑用户，封号用户
                $mid = MemberAccount::where([['agent_line', 'like', '%|' . $uid . '|%'], ['status', '=', 0]])->column('id');
                break;
            default :
                $mid = [];
                break;
        }
        return $mid;
    }

    //净赢

    public function user_total(Request $request)
    {
        $data = MerchantIndex::where('uid', $this->request->merchant->id)->find();
        $total['balance'] = MerchantWallet::where([['uid', '=', $this->request->merchant->id]])->value('cny');//总余额
        $total['total_balance'] = $data->money;//总余额
        $total['total_bets_number'] = $data->game;//总余额
        $total['total_net_win'] = $data->win;//总余额
        $total['total_user_recharge'] = $data->recharge;//总余额
        $total['total_user_withdraw'] = $data->withdraw;//总余额
        $total['total_amount_frozen'] = $data->frozen;//总余额
        $total['total_revenue'] = $data->all_share;//总余额
        $total['total_received_income'] = $data->in_share;//总余额
        $total['total_residual_income'] = $data->surplus_share;//总余额
        $total['total_dig'] = $data->ming;//总挖矿
        $total['total_Transfer_out'] = $data->into;//总挖矿
        $total['total_into'] = abs(MerchantRecord::where([['uid', '=', $this->request->merchant->id], ['business', '=', 1]])->sum('now'));//总挖矿
        $total['fee'] = $data->fee;//总挖矿
        $total['internal'] = $data->into;//总挖矿
        $Datas['total'] = $total;
        return success($Datas);
    }

    //充值，提现，转出，转入,挖矿

    public function plusAmount(Request $request)
    {
        $mid = $this->request->param('mid');
        $money = $this->request->param('money');
        $wallet = MemberWallet::where('mid', $mid)->value('cny');
        (new Wallet())->change($mid, 6, [
            1 => [$wallet, $money, $wallet + $money],
        ]);
        return success('加金额成功');
    }

    //业务员昵称

    public function salesmanData(Request $request)
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $agentLine = $this->request->merchant->id;
        $agents = MerchantAccount::where([['id', '=', $agentLine]])->find()->toArray();
        if ($agents['agent'] == "0") {
            $where = [
                ['agent_line', 'like', '%|' . $agentLine . '|%']
            ];
            $data = MerchantAccount::where($where)->page($page)->limit($limit)->select();
            $count = MerchantAccount::where($where)->count();
        } else {
            $data[] = MerchantAccount::where('id', $agentLine)->find();
            $count = 1;
        }
        foreach ($data as $k => $v) {
            $v->nickname = $v->profile->mobile;//昵称
            $v->count = $v->index->user;//昵称
            $v->bets_number = $v->index->user;//下注人数
            $v->net_win = $v->index->win;//净赢
            $v->user_recharge = $v->index->recharge;//净赢
            $v->recharge_number = $v->index->recharge_member;//净赢
            $v->user_withdraw = $v->index->withdraw;//净赢
            $v->number_withdrawals = $v->index->withdraw_member;//净赢
            $v->Transfer_out = $v->index->transfer;//净赢
            $v->into = $v->index->into;//净赢
            $v->total_revenue = $v->index->all_share;//净赢
            $v->received_income = $v->index->in_share;//充值
            $v->residual_income = $v->total_revenue - $v->received_income;//剩余收益
        }
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['list'] = $data;
        return success($datas);
    }

    //用户列表操作

    public function userGameData()
    {
        try {
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 10);
            $mid = $this->request->param('mid');
//            $start_time = $this->request->param('start_time');
//            $end_time = $this->request->param('end_time');
            $agentLine = $this->request->merchant->id;
            $mids = MemberAccount::where([['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]])->column('id');
//            $GameEventBet = (new GameEventBet())->hasWhere('dashboard');
            $GameEventBet = (new GameEventBet());
            if (!empty($mid)) {
//                $where[] = ['GameEventBet.mid', '=', $mid];
                $GameEventBet = $GameEventBet->where([['mid', '=', $mid]]);
            } else {
//                $where[] = ['GameEventBet.mid', 'in', $mids];
                $GameEventBet = $GameEventBet->hasWhere('account',[['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]])->order('GameEventBet.id desc');
            }
//            if (!empty($start_time)) {
//                $where[] = [['GameEventBet.create_time', '>', $start_time]];
//            }
//            if (!empty($end_time)) {
//                $where[] = [['GameEventBet.create_time', '<', $end_time]];
//            }
//            $order_list = [
//                'bets_number'   => 'MemberDashboard.game_bet',
//                'bet'           => 'GameEventBet.money',
//                'net_win'       => 'MemberDashboard.user_profit',
//                'user_recharge' => 'MemberDashboard.user_recharge',
//                'user_withdraw' => 'MemberDashboard.user_withdraw',
//            ];
//            if (!empty($this->request->param('sort'))) {
//                $order = $order_list[$this->request->param('sort')] . ' ' . str_replace('ending', '', $this->request->param('sorts'));
//            } else {
//                $order = 'GameEventBet.id desc';
//            }
//            $data = $GameEventBet->where($where)->page($page, $limit)->order($order)->select();
            $data = $GameEventBet->page($page, $limit)->select();
            $count = $GameEventBet->count();
            foreach ($data as $k => $datum) {
                $Data[$k]['time'] = $datum->create_time;//日期
                $Data[$k]['mid'] = $datum->mid;//玩家ID
                $Data[$k]['nickname'] = $datum->profile->nickname;//昵称
                $Data[$k]['mobile'] = $datum->profile->mobile;//手机号
                $Data[$k]['uid'] = $datum->index->agent;//业务员ID
                $Data[$k]['bets_number'] = $datum->index->bet_count;//业务员ID
                $Data[$k]['net_win'] = $datum->index->win;//业务员ID
                $Data[$k]['user_recharge'] = $datum->index->recharge;//业务员ID
                $Data[$k]['user_withdraw'] = $datum->index->withdraw;//业务员ID
                $Data[$k]['cny'] = $datum->wallet->cny;//业务员ID
                $Data[$k]['bet'] = $datum->money;//交易金额
            }
            $datas['page'] = $page;
            $datas['limit'] = $limit;
            $datas['count'] = $count;
            $datas['list'] = empty($Data) ? [] : $Data;
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            var_dump($exception->getTrace());
        }
        return success($datas);
    }

    public function see(Request $request)
    {
        $page = $request->param('page/d', 1);
        $limit = $request->param('limit/d', 10);
        $mid = $request->param('mid');
        $sort = $request->param('sort/s','first');
        $sorts = $request->param('sorts/s','desc');
        $type = $request->param('type');//1：一级 2：二级 3三级
        if (!empty($mid) && !empty($type)) {
            $datas = MemberAccount::where(['id' => $mid])->find();
            switch ($type) {
                case 1:
                    $count = MemberAccount::where([['inviter', '=', empty($dat['uuid']) ?: $dat['uuid']]])->count('id');//一级
                    $data = MemberTeam::hasWhere('account',[['inviter', '=', empty($dat['uuid']) ?: $dat['uuid']]])->order($sort.' '.$sorts)->page($page)->limit($limit)->select()->toArray();//三级
                    break;
                case 2:
                    $one_data = MemberAccount::where([['inviter', '=', empty($dat['uuid']) ?: $dat['uuid']]])->field('uuid,id')->select()->toArray();//一级
                    $count = MemberAccount::where([['inviter', 'in', array_column(empty($one_data) ? [] : $one_data, 'uuid')]])->count('id');//二级
                    $data = MemberTeam::hasWhere('account',[['inviter', 'in', array_column(empty($one_data) ? [] : $one_data, 'uuid')]])->order($sort.' '.$sorts)->page($page)->limit($limit)->select()->toArray();//三级
                    break;
                case 3:
                    $one_data = MemberAccount::where([['inviter', '=', empty($dat['uuid']) ?: $dat['uuid']]])->field('uuid,id')->select()->toArray();//一级
                    $two_data = MemberAccount::where([['inviter', 'in', array_column(empty($one_data) ? [] : $one_data, 'uuid')]])->field('uuid,id')->select()->toArray();//二级
                    $where = [['inviter', 'in', array_column(empty($two_data) ? [] : $two_data, 'uuid')]];
                    $count = MemberAccount::where($where)->count('id');//三级8
                    $data = MemberTeam::hasWhere('account',$where)->order($sort.' '.$sorts)->page($page)->limit($limit)->select()->toArray();//三级
                    break;
                default:
            }
        }
        $datasss['page'] = $page;
        $datasss['limit'] = $limit;
        $datasss['count'] = $count??0;
        $datasss['list'] = empty($data) ? [] : $data;
        return success($datasss);
    }

    //用户类型查询

    public function queryMemberData($data)
    {
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $member = MemberTeam::where('mid', $v['id'])->find();
                $data[$k]['one_quantity'] = empty($member->first) ? 0 : $member->first;//一级人数
                $data[$k]['two_quantity'] = empty($member->second) ? 0 : $member->second;//一级人数
                $data[$k]['three_quantity'] = empty($member->third) ? 0 : $member->third;//一级人数
                $data[$k]['one_profit'] = empty($member->first_share) ? 0 : $member->first_share;//一级人数
                $data[$k]['two_profit'] = empty($member->second_share) ? 0 : $member->second_share;//一级人数
                $data[$k]['three_profit'] = empty($member->third_share) ? 0 : $member->third_share;//一级人数
            }
        }
        return $data;
    }

    public function balance($where)
    {
        return MemberWallet::where($where)->sum('cny');
    }

    public function betsNumberv($where)
    {
        return GameEventBet::where($where)->count();
    }

    //内部号加金额

    public function flowingWater($where)
    {
        if (array_key_exists("0", $where)) {
            $where[] = ['currency', '=', 1];
        } else {
            $where['currency'] = 1;
        }
        return MemberRecord::where($where)->sum('now');
    }

    //业务员数据
    public function shareDailyStatistics(Request $request)
    {
        $page = $request->param('page/d', 1);
        $limit = $request->param('limit/d', 10);
        $mid = $request->param('mid');//玩家ID
        $start_time = $request->param('start_time');//开始时间
        $end_time = $request->param('end_time');//结束时间
        $agentLine = $this->request->merchant->id;
        if (!empty($mid)) {
            $where[] = ['MemberAccount.id', '=', $mid];
        } else {
            $where[] = ['MemberAccount.agent_line', 'like', '%|' . $agentLine . '|%'];
        }
        if (!empty($start_time)) {
            $where[] = ['MemberAccount.create_time', '>', $start_time];
        }
        if (!empty($end_time)) {
            $where[] = ['MemberAccount.create_time', '<', $end_time];
        }
        $MemberAccount = MemberAccount::hasWhere('dashboard');
        $order_list = [
            'one_quantity'   => 'MemberDashboard.one_quantity',
            'two_quantity'   => 'MemberDashboard.two_quantity',
            'three_quantity' => 'MemberDashboard.three_quantity',
        ];
        if (!empty($this->request->param('sort'))) {
            $order = $order_list[$this->request->param('sort')] . ' ' . str_replace('ending', '', $this->request->param('sorts'));
        } else {
            $order = 'MemberAccount.id desc';
        }
        $member = $MemberAccount->where($where)->field('MemberAccount.uuid,MemberAccount.id')->order($order)->page($page, $limit)->select();
        $count = $MemberAccount->where($where)->count();
        $member = empty($member) ? [] : $member->toArray();
        foreach ($member as $key => &$value) {
            $membersss = MemberTeam::where('mid', $value['id'])->find();
            $member[$key]['one_quantity'] = empty($membersss->first) ? 0 : $membersss->first;//一级人数
            $member[$key]['two_quantity'] = empty($membersss->second) ? 0 : $membersss->second;//一级人数
            $member[$key]['three_quantity'] = empty($membersss->third) ? 0 : $membersss->third;//一级人数
            $member[$key]['one_profit'] = empty($membersss->first_share) ? 0 : $membersss->first_share;//一级人数
            $member[$key]['two_profit'] = empty($membersss->second_share) ? 0 : $membersss->second_share;//一级人数
            $member[$key]['three_profit'] = empty($membersss->third_share) ? 0 : $membersss->third_share;//一级人数
        }
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['count'] = $count;
        $data['list'] = empty($member) ? [] : $member;
        return success($data);
    }

    //用户游戏数据

    public function gameRecord(Request $request)
    {
        $page = $request->param('page/d', 1);
        $limit = $request->param('limit/d', 10);
        $mid = $request->param('mid');//玩家ID
        $issue_number = $request->param('issue_number');//期号
        $game_type = $request->param('game_type');//游戏类型
        $trading_order = $request->param('trading_order');//交易盘
        $game_results = $request->param('game_results');//游戏结果
        $start_time = $request->param('start_time');//开始时间
        $end_time = $request->param('end_time');//结束时间
        $agentLine = $this->request->merchant->id;
//        $mids = MemberAccount::where([['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]])->column('id');
        $GameEventBet = (new GameEventBet())->hasWhere('account')->hasWhere('gameList');
        $where[] = ['GameEventBet.type', '=', '0'];
        if (!empty($mid)) {
            $where[] = ['GameEventBet.mid', '=', $mid];
        } else {
            $where[] = ['MemberAccount.agent_line', 'like', '%|' . $agentLine . '|%'];
        }
        if (!empty($issue_number)) {
            $where[] = ['GameEventList.title', '=', $issue_number];
        }
        if (!empty($game_type)) {
            $type = ['', '1m', '5m', '15m', '30m', '1h', '1d', '',];
            $where[] = ['GameEventList.type', '=', $type[$game_type]];
        }
        if (!empty($trading_order)) {
            switch ($trading_order) {
                case 1:
                    $where[] = ['GameEventBet.type', '=', '0'];
                    break;
                case 2:
                    $where[] = ['GameEventBet.type', '=', '1'];
                    break;
                default:
            }
        }
        if (!empty($game_results)) {
            switch ($game_results) {
                case 1:
                    $where[] = ['GameEventBet.is_ok', '=', '0'];
                    break;
                case 2:
                    $where[] = ['GameEventBet.is_ok', '=', '1'];
                    break;
                case 3:
                    $where[] = ['GameEventBet.is_ok', '=', '2'];
                    break;
                default:
            }
        }
        if (!empty($start_time)) {
            $where[] = ['GameEventBet.create_time', '>', $start_time];
        }
        if (!empty($end_time)) {
            $where[] = ['GameEventBet.create_time', '<', $end_time];
        }
        $order_list = [
            'open_prize' => 'GameEventBet.price',
            'money'      => 'GameEventBet.money',
        ];
        switch ($this->request->param('sort')) {
            case 'winLose':
                $where[] = ['GameEventBet.is_ok', '=', 1];
                $order_list['winLose'] = 'GameEventBet.money';
                break;
            case 'net_win':
                $where[] = ['GameEventBet.is_ok', '=', 2];
                $order_list['net_win'] = 'GameEventBet.money';
                break;
        }
        if (!empty($this->request->param('sort'))) {
            $order = $order_list[$this->request->param('sort')] . ' ' . str_replace('ending', '', $this->request->param('sorts'));
        } else {
            $order = 'GameEventBet.id desc';
        }
//        var_dump($where);
        $data = $GameEventBet->where($where)->order($order)->page($page, $limit)->select();
//        var_dump($GameEventBet->getLastSql());
        $count = $GameEventBet->where($where)->count();
        foreach ($data as $key => $datum) {
            $data[$key]->profile;
            $data[$key]->wallet;
            $data[$key]->gameList;
        }
        $data = $data->toArray();
        foreach ($data as $k => $v) {
            $Data[$k]['mid'] = $v['mid'];//玩家ID
            $Data[$k]['balanceBefore'] = MemberRecord::where([['create_time', '=', strtotime($v['create_time'])], ['mid', '=', $v['mid']]])->value('before');//变动前余额
            $Data[$k]['issue_number'] = $v['gameList']['title'];//期号
            $Data[$k]['bettingTray'] = $v['gameList']['type'];//押注盘 	0:1分钟；1：5分钟
            $Data[$k]['open_prize'] = $v['price'];//开奖时价格
            $Data[$k]['bettingCurrency'] = GameEventCurrency::where(['id' => $v['gameList']['cid']])->value('title');//交易币种
            $Data[$k]['result'] = $v['is_ok'];//开奖结果  0:未开奖；1:中奖;2:不中奖
            $Data[$k]['money'] = $v['money'];//交易
            $Data[$k]['bet'] = empty($v['bet']) ? 0 : $v['bet'];//押注 1涨 2跌
            $Data[$k]['create_time'] = $v['create_time'];//创建时间
            $Data[$k]['ids'] = $v['id'];//创建时间
            switch ($Data[$k]['result']) {
                case 0:
                    $Data[$k]['winLose'] = 0;//输赢
                    $Data[$k]['net_win'] = 0;//净赢
                    break;
                case 1:
                    $Data[$k]['winLose'] = $Data[$k]['money'] * $v['odds'];//输赢
                    $Data[$k]['net_win'] = $Data[$k]['winLose'] - $Data[$k]['money'];//净赢
                    break;
                case 2:
                    $Data[$k]['winLose'] = '-' . ($Data[$k]['money']);//输赢
                    $Data[$k]['net_win'] = '-' . $Data[$k]['money'];//净赢
                    break;
                default:
            }
            $Data[$k]['postBalance'] = $Data[$k]['balanceBefore'] + $Data[$k]['net_win'];//变动前余额
        }
        $mid = $GameEventBet->where($where)->page($page, $limit)->column('mid');
        $profitLoss = $this->netWin([['business', '=', 4], ['mid', 'in', $mid]], [['business', '=', 8], ['mid', 'in', $mid]], [['business', '=', 3], ['mid', 'in', $mid]], [['business', '=', 7], ['mid', 'in', $mid]]);
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['player_profit_loss'] = $profitLoss;//玩家盈亏
        $datas['data'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    //查询直属，二级，三级数量和收益

    public function netWin($where1, $where2, $where3, $where4)
    {
        return abs((new MemberRecord())->where($where1)->where('currency', 1)->sum('now')) - abs((new MemberRecord())->where($where3)->where('currency', 1)->sum('now'));//净赢
    }

    //每日统计点击查看

    public function rechargeWithdrawalRecord(Request $request)
    {
        $page = $request->param('page/d', 1);
        $limit = $request->param('limit/d', 10);
        $agentLine = $this->request->merchant->id;
        $mid = $request->param('mid');//玩家ID
        $into_id = $request->param('into_id');//划入玩家ID
        $fullLift_type = $request->param('fullLift_type');//充提类型
        $whether_First_recharge = $request->param('whether_First_recharge');//是否首充
        $start_time = $request->param('start_time');//开始时间
        $end_time = $request->param('end_time');//结束时间
        $MemberRecord = (new MemberRecord());
        $mids = MemberAccount::where([['agent_line', 'like', '%' . $agentLine . '%']])->column('id');
        $where = [];
        if (!empty($mid)) {
            $where[] = ['mid', '=', $mid];
        } else {
            $where[] = ['mid', 'in', $mids];
        }
        if (!empty($into_id)) {
            $where[] = ['mid', '=', $into_id];
        }
        $where[] = ['currency', '=', 1];
        if (!empty($fullLift_type)) {
            $where[] = ['business', '=', $fullLift_type];
        } else {
            $where[] = ['business', 'in', [1, 2]];
        }
        if (!empty($start_time)) {
            $where[] = ['create_time', '>', $start_time];
        }
        if (!empty($end_time)) {
            $where[] = ['create_time', '<', $end_time];
        }
        $order_list = [
            'amountMoney' => 'now',
            'create_time' => 'create_time',
        ];
        if ($this->request->param('sort')) {
            $order = $order_list[$this->request->param('sort')] . ' ' . str_replace('ending', '', $this->request->param('sorts'));
        } else {
            $order = 'id desc';
        }
        if (!empty($whether_First_recharge)) {
            switch ($whether_First_recharge) {
                case 1:
                    $where[] = ['business', '=', 1];
                    $data = $MemberRecord->where($where)->order('id', 'asc')->limit(1)->select();
                    $count = $data->count();
                    break;
                case 2:
                    $data = $MemberRecord->where($where)->page($page, $limit)->order($order)->select();
                    $count = $MemberRecord->where($where)->count();
                    break;
                default:
            }
        } else {
            $data = $MemberRecord->where($where)->page($page, $limit)->order($order)->select();
            $count = $MemberRecord->where($where)->count();
        }
        foreach ($data as $key => $v) {
            $data[$key]->profile;
            $data[$key]->account;
        }
        $data = $data->toArray();
        foreach ($data as $k => $v) {
            $Data[$k]['mid'] = $v['mid'];//玩家ID
            $Data[$k]['nickname'] = $v['profile']['nickname'];//昵称
            $agent_line = MemberAccount::where(['id' => $v['mid']])->field('agent_line')->find()->toArray();
            $uid = $this->agentLine($agent_line['agent_line']);
            $Data[$k]['merchant_nickname'] = $this->salesmanNickname(['uid' => $uid]);//所属业务员
            $Data[$k]['mobile'] = $v['profile']['mobile'];//手机号
            $Data[$k]['uid'] = MemberAccount::where(['uuid' => $v['account']['inviter']])->value('id');//上级ID
            $Data[$k]['type'] = $v['business'];//类型 1：充值，2：提现，3：一分钟交易，4：一分钟开奖，5：提现退回，6：后台充值,7：五分钟交易，8：五分钟开奖,9：团队返利,10：转入,11：转出,12:挖矿，13：领取团队收益，14：领取挖矿收益
            if ($Data[$k]['type'] == 1 || $Data[$k]['type'] == 10) {//类型是充值订单号
                $Data[$k]['order_number'] = MemberPayOrder::where(['mid' => $v['mid']])->value('rid');
            }
            if ($Data[$k]['type'] == 2 || $Data[$k]['type'] == 11) {//类型是提现订单号
                $Data[$k]['order_number'] = MemberWithdraworder::where(['mid' => $v['mid']])->value('rid');
            }
            $Data[$k]['balanceBefore'] = $v['before'];//变动前余额
            $Data[$k]['postBalance'] = $v['after'];//变动后余额
            $Data[$k]['amountMoney'] = $v['now'];//金额
            $Data[$k]['create_time'] = $v['create_time'];//创建时间
        }
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['total_recharge'] = $this->flowingWater([['mid', 'in', $mids], ['business', '=', 1]]);//总充值
        $datas['total_withdrawal'] = MemberWithdrawOrder::where([['mid', 'in', $mids], ['examine', '=', 1]])->sum('money');//总提现
        $datas['total_transfer'] = abs(MerchantRecord::where([['uid', '=', $this->request->merchant->id], ['business', '=', 2]])->sum('now'));//总划转
        $datas['total_mining'] = $this->flowingWater([['mid', 'in', $mids], ['business', '=', 14]]);//总挖矿
        $datas['data'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    //分享每日统计

    public function agentLine($agent_line)
    {
        $agent_line = explode('|', $agent_line);
        return $agent_line[count($agent_line) - 2];
    }

    //游戏记录

    public function salesmanNickname($where)
    {
        return (new MerchantProfile)->where($where)->value('mobile');
    }

    //充提记录

    public function dataStatistics(Request $request)
    {
        $page = $request->param('page/d', 1);
        $limit = $request->param('limit/d', 10);
        $agent = $request->param('agent');
        $start_time = $request->param('start_time');
        $end_time = $request->param('end_time');
        $agentLine = $this->request->merchant->id;
        if ($this->request->merchant->agentLv == "0") {
            $uid = MerchantAccount::where([['agent_line', 'like', '%|' . $agentLine . '|%']])->column('id');
        } else {
            $uid = [$agentLine];
        }
        $MerchantDay = (new MerchantDay());
        $where = [['uid', 'in', $uid]];
        if (!empty($agent)) {
            switch ($agent) {
                case 1:
                    $uid = MerchantAccount::where([['id', 'in', $uid], ['agent', '=', 0]])->column('id');
                    $where[] = [['uid', 'in', $uid]];
                    break;
                case 2:
                    $uid = MerchantAccount::where([['id', 'in', $uid], ['agent', '=', 1]])->column('id');
                    $where[] = [['uid', 'in', $uid]];
                    break;
                case 3:
                    $uid = MerchantAccount::where([['id', 'in', $uid], ['agent', '=', 2]])->column('id');
                    $where[] = [['uid', 'in', $uid]];
                    break;
                default:
            }
        }
        if (!empty($start_time)) {
            $where[] = ['create_time', '>', $start_time];
        }
        if (!empty($end_time)) {
            $where[] = ['create_time', '<', $end_time];
        }
        $order_list = [
            'total_registration'  => 'team_member',
            'number_shares'       => 'share',
            'valid_registration'  => 'team_valid_member',
            'daily_living'        => 'team_active',
            'one_set_net_win'     => 'team_one',
            'quintet_net_win'     => 'team_five',
            'recharge'            => 'team_recharge',
            'total_running_water' => 'team_record',
            'users_bet_five'      => 'team_five_bet',
            'first_impulse'       => 'team_first',
        ];
        if (!empty($request->param('sort'))) {
            $order = $order_list[$request->param('sort')] . ' ' . str_replace('ending', '', $request->param('sorts'));
        } else {
            $order = 'id desc';
        }
        $data = $MerchantDay->where($where)->page($page, $limit)->order($order)->select();
        $count = $MerchantDay->where($where)->count();
        $data = $data->toArray();
        try {
            foreach ($data as $k => $v) {
                $Data[$k]['date'] = $v['create_time'];//日期
                $Data[$k]['total_generation'] = MerchantAccount::where([['id', '=', $v['uid']]])->value('agent');//总代
                $Data[$k]['uid'] = $v['uid'];//业务员ID
                $Data[$k]['total_registration'] = $v['team_member'];//总注册
                $Data[$k]['number_shares'] = $v['share'];//分享数量
                $Data[$k]['valid_registration'] = $v['team_valid_member'];//有效注册
                $Data[$k]['daily_living'] = $v['team_active'];//日活
                $Data[$k]['one_set_net_win'] = $v['team_one'];//一分盘净赢
                $Data[$k]['quintet_net_win'] = $v['team_five'];//五分盘净赢
                $Data[$k]['recharge'] = $v['team_recharge'];//充值金额
                $Data[$k]['recharge_number'] = $v['team_recharge_member'];//充值人数
                $Data[$k]['withdrawal'] = $v['team_withdraw'];//提现金额
                $Data[$k]['withdrawal_number'] = $v['team_withdraw_member'];//提现人数
                $Data[$k]['ervice_charge'] = $v['team_withdraw_fee'];//手续费
                $Data[$k]['transfer'] = $v['team_transfer'];//划转
                $Data[$k]['total_running_water'] = $v['team_record'];//总流水
                $Data[$k]['users_bet_five'] = $v['team_five_bet'];//下注5次以上用户
                $Data[$k]['first_impulse'] = $v['team_first'];//首充
                $Data[$k]['number_first_impulse'] = $v['team_first_member'];//首充人数
                $Data[$k]['rate'] = $v['team_rate'];//付费率
                $Data[$k]['first_day_rate'] = $v['team_first_rate'];//首日付费率
                $Data[$k]['secondary_retention'] = $v['team_second_retention'];//次留
                $Data[$k]['three_stay'] = $v['team_three_retention'];//三留
                $Data[$k]['seven_stay'] = $v['team_seven_retention'];//七留
                $Data[$k]['fourteen_stay'] = $v['team_fourteen_retention'];//十四留
                $Data[$k]['thirty_stay'] = $v['team_thirty_retention'];//三十留
            }
            $datas['page'] = $page;
            $datas['limit'] = $limit;
            $datas['count'] = $count;
            $datas['data'] = empty($Data) ? [] : $Data;
            return success($datas);
        } catch (Exception $exception) {
            var_dump($exception->getMessage());
        }

    }

    //数据统计

    public function singleControlLottery()
    {
        $cid = $this->request->param('cid');//币种
        $type = $this->request->param('type');//游戏类型
        $title = $this->request->param('title');//期数
        $status = $this->request->param('status');//1涨2跌
        if (!empty($cid) && !empty($type) && !empty($title) && !empty($status)) {
            switch ($type) {
                case 1:
                    $type = 0;
                    break;
                case 2:
                    $type = 1;
                    break;
            }
            $time = GameEventList::where(['cid' => $cid, 'title' => $title, 'type' => $type, 'open' => 0])->field('begin_time,end_time')->find();
            if (!empty($time)) {
                if ($time['end_time'] - $time['begin_time'] <= 3 && $time['end_time'] - $time['begin_time'] != 0) {
                    switch ($status) {
                        case 1:
                            $data = GameEventList::where(['cid' => $cid, 'title' => $title, 'type' => $type])->save(['open' => 1]);
                            break;
                        case 2:
                            $data = GameEventList::where(['cid' => $cid, 'title' => $title, 'type' => $type])->save(['open' => 2]);
                            break;
                    }
                }
            }
        }
        return success(empty($data) ? '没有数据' : $data);
    }

    //控制开奖

    public function getPosition($ip)
    {
        $url = "http://whois.pconline.com.cn/jsFunction.jsp?ip=" . $ip;
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        $info = iconv('GB2312', 'UTF-8', $output); //因为是js调用 所以接收到的信息为字符串，注意编码格式
        //php 截取特定字符前面的内容
        $aa = substr($info, 0, strrpos($info, "省"));
        //PHP 截取特定字符串后面的内容
        $bb = substr($aa, strripos($aa, "'") + 1);
        return $bb;
    }

    public function currentPrizeStatistics()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 30);
        $sort = $this->request->param('sort/s', 'cny');
        $desc = $this->request->param('desc/s', 3);
        $id = $this->request->param('id/d');//list_id
        $list = (new GameController($id))->getAllBets()->getAllMembersBets();
        $status = $this->request->param('status');//用户类型
        if (!empty($status)) {
            switch ($status) {
                case 1://正常用户
                    $mid = $this->mid_list(10, $this->request->merchant->id);
                    break;
                case 2://关注用户
                    $mid = $this->mid_list(11, $this->request->merchant->id);
                    break;
                case 3://超出比例用户
                    $mid = $this->mid_list(6, $this->request->merchant->id, $id);
                    break;
                case 4://IP重复用户
                    $mid = $this->mid_list(5, $this->request->merchant->id);
                    break;
                case 5://全部用户
                    $mid = $this->mid_list(7, $this->request->merchant->id);
                    break;
                case 6://买涨用户
                    $mid = $this->mid_list(8, $this->request->merchant->id);
                    break;
                case 7://买跌用户
                    $mid = $this->mid_list(9, $this->request->merchant->id);
                    break;
                case 8://直属用户
                    $mid = $this->mid_list(1, $this->request->merchant->id);
                    break;
            }
            foreach ($list as $key => $value) {
                if (!in_array($value['mid'], $mid)) {
                    unset($list[$key]);
                }
            }
        }
//            define ('SORT_ASC', 4);
//
//            /**
//             * SORT_DESC is used with
//             * array_multisort to sort in descending order.
//             * @link https://php.net/manual/en/array.constants.php
//             */
//            define ('SORT_DESC', 3);
        $GameBetData = [];
        $count = 0;
        if (!empty($list)) {
            if(!empty($sort)){
                $edit = array_column($list, $sort);
            }else{
                $edit = array_column($list, 'money');
            }
            array_multisort($edit, $desc=="ascending"?4:3, $list);
            $GameBetData = array_slice($list, ($page - 1) * $limit, $limit);
            if (!empty($GameBetData)) {
                foreach ($GameBetData as &$v) {
                    $account = MemberAccount::where(['id' => $v['mid']])->field('id,type')->find();
                    $account->index;
                    $v['account'] = $account;//业务员昵称
//                        $v['cny'] = $account->wallet->cny;//业务员昵称
                }
            }
            $count = count($list);
        }
        $open_list = explode('|', get_config('game', 'game', 'open'));
        $datas['page'] = $page;
        $datas['open'] = in_array($this->request->merchant->id, $open_list) ? 1 : 0;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['data']['current_period'] = empty($titleData->title) ? '' : $titleData->title;//当前期数
        $datas['data']['current_bets'] = get_config('game', 'game', 'totals');//当前下注数
        $datas['data']['proportion'] = get_config('game', 'game', 'proportion');//比例
        $datas['data']['list'] = !empty($GameBetData) ? array_values($GameBetData) : [];
        return success($datas);
//        if (!empty($Data)){
//            if (\request()->param('sort')){
//                $key = array_column($Data,\request()->param('sort'));
//                $order = [
//                    'descending' =>'SORT_DESC',
//                    'ascending' =>'SORT_ASC',
//                ];
//                array_multisort($key,$order[\request()->param('sorts')],$Data);
//            }else{
//                $key = array_column($Data,'bet_amount');
//                array_multisort($key,SORT_DESC,$Data);
//            }
//        }

    }

    //当期开奖统计

    public function a_array_unique($array)
    {
        return array_values($array);
    }

    /**
     * @param \app\Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 当期游戏列表数据
     */
    public function now_gamelist()
    {
        $cid = $this->request->param('cid/d', 1);
        $where = [['begin_time', '<', time()], ['end_time', '>', time()]];
        $where[] = ['cid', '=', $cid];
        $type = $this->request->param('type');;//类型
        if (!empty($type)) {
            switch ($type) {
                case 1:
                    $where[] = ['type', '=', "1m"];
                    break;
                case 2:
                    $where[] = ['type', '=', "5m"];
                    break;
            }
        }
        $data['game'] = (new GameEventList())->where($where)->field('title,id,cid,begin_time,end_time,open,type,iscontrol')->find();//赛事列表数据
        $data['last'] = (new GameEventList())->where([
            ['begin_time', '<', $data['game']->begin_time],
            ['type', '=', $data['game']->type],
            ['cid', '=', $data['game']->cid],
        ])->order('begin_time desc')->find();//赛事列表数据
        return success($data);
    }

    public function Currency()
    {
        $data['currency'] = GameEventCurrency::CurreryAll();//币种ID和名称
        return success($data);
    }

    /**
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException 当期游戏汇总数据
     */
    public function now_list()
    {
        $id = $this->request->param('id/d', 1);
        $data['list'] = (new GameController($id))->getAllBets()->getTypeBetsDetail();
        return success($data);
    }

    public function last_list()
    {
        $id = $this->request->param('id/d', 1);
        $data['list'] = (new GameController($id))->getAllBets()->getTypeBetsDetail();
        return success($data);
    }

    public function totalStatistics(Request $request)
    {
        $page = $request->param('page/d', 1);
        $limit = $request->param('limit/d', 10);
        $mid = $request->param('mid');//玩家ID
        $type = $request->param('type');//玩家级别
        $id = $request->param('id');//玩家id
        $agentLine = $this->request->merchant->id;
        $where = [];
        if (!empty($mid)) {
            $where[] = ['MemberAccount.id', '=', $mid];
        } elseif (!empty($type) && !empty($id)) {
            $dat = MemberAccount::where(['id' => $id])->find()->toArray();
            switch ($type) {
                case 1:
                    $mid_data = MemberAccount::where([['inviter', '=', empty($dat['uuid']) ?: $dat['uuid']]])->column('id');//一级
                    break;
                case 2:
                    $one_data = MemberAccount::where([['inviter', '=', empty($dat['uuid']) ?: $dat['uuid']]])->column('uuid');//一级
                    $mid_data = MemberAccount::where([['inviter', 'in', $one_data]])->column('id');//二级
                    break;
                case 3:
                    $one_data = MemberAccount::where([['inviter', '=', empty($dat['uuid']) ?: $dat['uuid']]])->column('uuid');//一级
                    $two_data = MemberAccount::where([['inviter', 'in', $one_data]])->column('uuid');//二级
                    $mid_data = MemberAccount::where([['inviter', 'in', $two_data]])->column('id');//三级
                    break;
                default:
            }
//            var_dump($mid_data);
            $where[] = ['MemberAccount.id', 'in', $mid_data];
        } else {
            $where[] = ['MemberAccount.agent_line', 'like', '%|' . $agentLine . '|%'];
        }
        if (!empty($request->param('sort'))) {
            $order = 'MemberDashboard.' . $request->param('sort') . ' ' . str_replace('ending', '', $request->param('sorts'));
        } else {
            $order = 'MemberDashboard.id desc';
        }
        $Member = MemberAccount::hasWhere('dashboard')->where($where)->field('MemberAccount.uuid,MemberAccount.id')->order($order)->page($page, $limit)->select();
        foreach ($Member as $k => $v) {
            $mids = MemberAccount::where([['inviter_line', 'like', "%|" . $Member[$k]['id'] . "|%"]])->column('id');//玩家ID
            array_push($mids, $Member[$k]['id']);
            $Member[$k]['netWin'] = abs(MemberIndex::where([['mid', 'in', $mids]])->sum('win'));
            $Member[$k]['all_member'] = count($mids);//总人数
            $Member[$k]['all_money'] = MemberRecord::where([['business', '=', 9], ['currency', '=', 4], ['mid', '=', $Member[$k]['id']]])->sum('now');//总收益
            $Member[$k]['nickname'] = $v->profile->nickname;//昵称
            $Member[$k]['mobile'] = $v->profile->mobile;//昵称
            $Member[$k]['balance'] = $v->wallet->cny;//余额
            $Member[$k]['game_quantity'] = $v->index->bet_count;//游戏局数
            $Member[$k]['recharge'] = $v->index->recharge;//游戏局数
            $Member[$k]['withdrawal'] = $v->index->withdraw;//游戏局数
            $Member[$k]['total_bet'] = $v->dashboard->all_bet;//游戏局数
            $Member[$k]['one_quantity'] = $v->team->first;//一级数量
            $Member[$k]['two_quantity'] = $v->team->second;//2级数量
            $Member[$k]['three_quantity'] = $v->team->third;//3级数量
            $Member[$k]['one_profit'] = $v->team->first_share;//一级收益
            $Member[$k]['two_profit'] = $v->team->second_share;//2级收益
            $Member[$k]['three_profit'] = $v->team->third_share;//3级收益
        }
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = MemberAccount::hasWhere('dashboard')->where($where)->count();
        $datas['data'] = $Member;
        return success($datas);
    }

    //分享总统计

    public
    function userRechargeAddress()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $mid = $this->request->param('mid');
        $address = $this->request->param('address');
        $agentLine = $this->request->merchant->id;
//        $mids = MemberAccount::where([['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]])->column('id');
        $where = [];
        $MemberAddress = new MemberAddress();
        if (!empty($mid)) {
            $where[] = ['mid', '=', $mid];
        } else {
            $MemberAddress = $MemberAddress->hasWhere('account',[['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]]);
        }
        if (!empty($address)) {
            $where[] = ['trc_address', '=', $address];
        }
        if (!empty($this->request->param('sort'))) {
            $order = 'money ' . str_replace('ending', '', $this->request->param('sorts'));
        } else {
            $order = 'money desc';
        }
        $AddressData = $MemberAddress->where($where)->page($page, $limit)->order($order)->select();
        $count = $MemberAddress->where($where)->count();
        foreach ($AddressData as $k => $v) {
            $Data[$k]['mid'] = $v['mid'];//玩家ID
            $Data[$k]['Address'] = $v['trc_address'];//子账户地址
            $Data[$k]['balance'] = $v['money'];//余额
        }
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['data'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    public
    function withdrawalAddressList()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $mid = $this->request->param('mid');
        $address = $this->request->param('address');
        $agentLine = $this->request->merchant->id;
        $where = [];
        $MemberAddress = new MemberWithdrawOrder();
        if (!empty($mid)) {
            $where[] = [['mid', '=', $mid]];
        } else {
            $MemberAddress = $MemberAddress->hasWhere('account',[['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]]);
        }
        if (!empty($address)) {
            $where[] = ['address', '=', $address];
        }
        $where[] = ['examine', '=', 1];
        $AddressData = $MemberAddress->where($where)->page($page, $limit)->order('id', 'desc')->group('mid')->select();
        $count = $MemberAddress->where($where)->count();
        foreach ($AddressData as $k => $v) {
            $Data[$k]['mid'] = $v['mid'];//玩家ID
            $Data[$k]['address'] = $v['address'];//提现地址
            $Data[$k]['money'] = 0;//$Integration->getTrc20Balance(null, $v['address']);//提现地址
        }
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['data'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    //用户充值地址

    public
    function hangeLog()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $mid = $this->request->param('mid');
        $type = $this->request->param('type');//类型 1消耗 2获得
        $source = $this->request->param('source');//来源 //1下注 2充值 3提现 4转出 5转入 6挖矿 7无限代收益 8一级收益 9二级收益 10三级收益
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');
        $MemberRecord = (new MemberRecord());
        $agentLine = $this->request->merchant->id;
        $mids = MemberAccount::where([['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]])->column('id');
        if (!empty($type)) {
            if ($type == 1) {
                $where = [['now', '<', 0]];
            }
            if ($type == 2) {
                $where = [['now', '>', 0]];
            }
        } else {
            $where = [];
        }
        if (!empty($mid)) {
            $where[] = ['mid', '=', $mid];
        } else {
            $where[] = ['mid', 'in', $mids];
        }
        //1下注 2充值 3提现 4转出 5转入 6挖矿 7GM充值 8一级收益 9二级收益 10三级收益
        if (!empty($source)) {
            switch ($source) {
                case 1:
                    $where[] = ['business', 'in', [3, 7]];
                    break;
                case 2:
                    $where[] = ['business', '=', 1];
                    break;
                case 3:
                    $where[] = ['business', '=', 2];
                    break;
                case 4:
                    $where[] = ['business', '=', 11];
                    break;
                case 5:
                    $where[] = ['business', '=', 10];
                    break;
                case 6:
                    $where[] = ['business', '=', 12];
                    break;
                case 7:
                    $where[] = ['business', '=', 6];
                    break;
                case 8:
                    $where[] = [['team', '=', 1], ['business', '=', 9]];
                    break;
                case 9:
                    $where[] = [['team', '=', 2], ['business', '=', 9]];
                    break;
                case 10:
                    $where[] = [['team', '=', 3], ['business', '=', 9]];
                    break;
            }
        }
        if (!empty($start_time)) {
            $where[] = ['create_time', '>', $start_time];
        }
        if (!empty($end_time)) {
            $where[] = ['create_time', '<', $end_time];
        }
        $order_list = [
            'amountMoney' => 'now',
            'time'        => 'create_time',
        ];
        $request = $this->request;
        if (!empty($request->param('sort'))) {
            $order = $order_list[$request->param('sort')] . ' ' . str_replace('ending', '', $request->param('sorts'));
        } else {
            $order = 'id desc';
        }
        $data = $MemberRecord->where($where)->page($page, $limit)->order($order)->select();
        $count = $MemberRecord->where($where)->count();
        foreach ($data as $k => $v) {
            $data[$k]->profile;
            $data[$k]->account;
        }
        $data = $data->toArray();
        foreach ($data as $k => $v) {
            $Data[$k]['mid'] = $v['account']['id'];//玩家ID
            $Data[$k]['nickname'] = $v['profile']['nickname'];//玩家昵称
            $Data[$k]['mobile'] = $v['profile']['mobile'];//手机号
            $Data[$k]['uid'] = $this->uid($v['account']['inviter'], $v['account']['agent_line']);//上级ID
            $Data[$k]['type'] = $v['now'];//类型  根据正负值 正获得 负消耗
            $Data[$k]['balanceBefore'] = $v['before'];//变动前金额
            $Data[$k]['postBalance'] = $v['after'];//变动后金额
            $Data[$k]['amountMoney'] = $v['now'];//金额
            $Data[$k]['team'] = $v['team'];
            $Data[$k]['source'] = $v['business'];//来源
            $Data[$k]['time'] = $v['create_time'];//时间
            $Data[$k]['currency'] = $v['currency'];//时间
        }
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['data'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    //提现地址列表

    public function uid($inviter, $uid1)
    {
        $uid1 = explode('|', $uid1)[1];
        $mid = MemberAccount::where(['uuid' => $inviter])->value('id');
        $uid = empty($mid) ? $uid1 : $mid;//上级ID
        return $uid;
    }

    //变动日志

    public
    function withdrawalLostUserList()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $mid = $this->request->param('mid');
        $agentLine = $this->request->merchant->id;
        $mids = MemberAccount::where([['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]])->column('id');
        $mids = MemberWithdrawOrder::where([['mid', 'in', $mids]])->column('mid');
        $where = [['MemberAccount.analog', '=', 0]];
        if (!empty($mid)) {
            $where[] = ['MemberAccount.id', '=', $mid];
        } else {
            $where[] = ['MemberAccount.id', 'in', $mids];
        }
        $where[] = ['MemberAccount.login_time', '<', time() - 86400 * get_config('wallet', 'wallet', 'mix_day')];
        $order_list = [
            'balance'       => 'cny',
            'number_games'  => 'game_bet',
            'bets_number'   => 'game_bet',
            'net_win'       => 'user_profit',
            'user_recharge' => 'user_recharge',
            'user_withdraw' => 'user_withdraw',
            'Transfer_out'  => 'out',
            'into'          => 'into',
            'amount_frozen' => 'user_withdraw_examine',
            'dig'           => 'minging',
            'total_revenue' => 'total_revenue',
            'fee'           => 'fee',
        ];
        $request = $this->request;
        if (!empty($request->param('sort'))) {
            $order = 'MemberDashboard.' . $order_list[$request->param('sort')] . ' ' . str_replace('ending', '', $request->param('sorts'));
        } else {
            $order = 'MemberDashboard.id desc';
        }
        $data = MemberAccount::hasWhere('dashboard')->where($where)->page($page, $limit)->order($order)->select();
        $count = MemberAccount::hasWhere('dashboard')->where($where)->count();
        foreach ($data as $k => $v) {
            $data[$k]->profile;
        }
        $data = $data->toArray();
        foreach ($data as $k => $v) {
            $Data[$k]['mid'] = $v['id'];//玩家ID
            $Data[$k]['user_type'] = $v['type'];//玩家类型
            $Data[$k]['nickname'] = $v['profile']['nickname'];//昵称
            $Data[$k]['mobile'] = $v['profile']['mobile'];//手机号
            $Data[$k]['salesman_id'] = $this->agentLine($v['agent_line']);//业务员ID
            $Data[$k]['uid'] = $this->uid($v['inviter'], $v['agent_line']);//上级ID
            $Data[$k]['balance'] = $this->balance(['mid' => $v['id']]);//余额;
            $Data[$k]['number_games'] = GameEventBet::where([
                ['mid', '=', $v['id']],
                ['type', '=', 1],
            ])->count();//游戏局数
            $Data[$k]['net_win'] = $this->netWin(
                ['mid' => $v['id'], 'business' => 4],
                ['mid' => $v['id'], 'business' => 8],
                ['mid' => $v['id'], 'business' => 3],
                ['mid' => $v['id'], 'business' => 7]
            );//净赢
            $Data[$k]['user_recharge'] = $this->flowingWater(['mid' => $v['id'], 'business' => 1]);//充值
            $Data[$k]['user_withdraw'] = $this->draw_money($v['id']);//提现
            $Data[$k]['Transfer_out'] = $this->flowingWater(['mid' => $v['id'], 'business' => 11]);//转出
            $Data[$k]['into'] = $this->flowingWater(['mid' => $v['id'], 'business' => 10]);//转入
            $amount_frozen = (new MemberAccount())->withdrawOrder()->where(['mid' => $v['id'], 'examine' => 0])->sum('money');
            $Data[$k]['amount_frozen'] = empty($amount_frozen) ? 0 : $amount_frozen;//冻结金额
            $Data[$k]['dig'] = abs($this->flowingWater(['mid' => $v['id'], 'business' => 12]));//挖矿
            $Data[$k]['total_revenue'] = $Data[$k]['net_win'] + $Data[$k]['dig'];//总收益
            $Data[$k]['received_income'] = $this->flowingWater(['mid' => $v['id'], 'business' => 13, 'currency' => 1]);//已领收益
            $Data[$k]['residual_income'] = $Data[$k]['total_revenue'] - $Data[$k]['received_income'];//剩余收益
            $Data[$k]['create_time'] = $v['create_time'];
            $Data[$k]['login_time'] = $v['login_time'];
        }
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['data'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    //提现流失用户列表

    /**
     * @param $mid
     * @param int $type
     * @return float|\think\response\Json
     */
    public function draw_money($mid, $type = 1)
    {
        return MemberWithdrawOrder::where([
            ['mid', '=', $mid],
            ['examine', '=', $type],
        ])->sum('money');
    }

    //设置开奖记录

    public function setLotteryRecord()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $issue = $this->request->param('issue');
        $game_type = $this->request->param('game_type');
        $transaction_type = $this->request->param('cid');
        $GameEventList = (new GameEventList());
        $where = [];
        if (!empty($issue)) {
            $where[] = ['title', '=', $issue];
        }
        if (!empty($game_type)) {
            $where[] = ['type', '=', $game_type];
        }
        if (!empty($transaction_type)) {
            $where[] = ['cid', '=', $transaction_type];
        }
        $where[] = ['hard', '=', 1];
        $data = $GameEventList->where($where)->page($page, $limit)->order('id desc')->select();
        $count = $GameEventList->where($where)->count();
        $data = $data->toArray();
        foreach ($data as $k => $v) {
            $Data[$k]['issue'] = $v['title'];//期号
            $Data[$k]['betting_tray'] = $v['type'];//押注盘
            $Data[$k]['title'] = GameEventCurrency::where(['id' => $v['cid']])->value('title');//交易类型
            $Data[$k]['open_prize'] = round($v['open_price'], 5);//开奖
            $Data[$k]['remark'] = $v['remark'];//开奖
            $Data[$k]['lottery_results'] = $v['open'];//开奖结果
        }
        $currency = GameEventCurrency::field('id,title')->select();
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['currency'] = $currency;
        $datas['data'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    //提现列表
    public function withdrawalList()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $mid = $this->request->param('mid');
        $type = $this->request->param('type');
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');
        $agentLine = $this->request->merchant->id;
        $mids = MemberAccount::where([['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]])->column('id');
        $WithdrawOrder = (new MemberWithdrawOrder())->hasWhere('account');
        if (!empty($mid)) {
            $where[] = ['mid', '=', $mid];
        } else {
            $where[] = ['mid', 'in', $mids];
        }
        if (!empty($type)) {
            switch ($type) {
                case 1:
                    $where[] = ['examine', '=', 0];
                    break;
                case 2:
                    $where[] = ['examine', '=', 1];
                    break;
                case 3:
                    $where[] = ['examine', '=', 2];
                    break;
            }
        }
        if (!empty($start_time)) {
            $where[] = ['create_time', '>', $start_time];
        }
        if (!empty($end_time)) {
            $where[] = ['create_time', '<', $end_time];
        }
        $order_list = [
            'balance'             => 'MemberDashboard.cny',
            'number_games'        => 'MemberDashboard.game_bet',
            'bets_number'         => 'MemberDashboard.game_bet',
            'net_win'             => 'MemberDashboard.user_profit',
            'user_recharge'       => 'MemberDashboard.user_recharge',
            'user_withdraw'       => 'MemberDashboard.user_withdraw',
            'Transfer_out'        => 'MemberDashboard.out',
            'winning_probability' => 'MemberDashboard.winning_probability',
            'into'                => 'MemberDashboard.into',
            'amount_frozen'       => 'MemberDashboard.user_withdraw_examine',
            'dig'                 => 'MemberDashboard.minging',
            'total_revenue'       => 'MemberDashboard.total_revenue',
            'received_income'     => 'MemberDashboard.share_receive',
            'fee'                 => 'MemberDashboard.fee',
            'create_time'         => 'MemberWithdrawOrder.create_time',
            'withdraw_amount'     => 'MemberWithdrawOrder.money',
        ];
        $request = $this->request;
        if (!empty($request->param('sort'))) {
            $order = $order_list[$request->param('sort')] . ' ' . str_replace('ending', '', $request->param('sorts'));
        } else {
            $order = 'MemberWithdrawOrder.id desc';
        }
        $data = $WithdrawOrder->where($where)->order($order)->page($page, $limit)->select();
        $count = $WithdrawOrder->where($where)->count();
        foreach ($data as $k => $v) {
            $data[$k]->profile;
            $data[$k]->account;
        }
        $data = $data->toArray();
        foreach ($data as $k => $v) {
            $Data[$k]['order_number'] = $v['id'];//订单号
            $Data[$k]['mid'] = $v['mid'];//玩家ID
            $Data[$k]['nickname'] = $v['profile']['nickname'];//昵称
            $Data[$k]['mobile'] = $v['profile']['mobile'];//手机号
            $Data[$k]['salesman_id'] = $this->agentLine($v['account']['agent_line']);//业务员id
            $Data[$k]['uid'] = $this->uid($v['account']['inviter'], $v['account']['agent_line']);//上级ID
            $Data[$k]['balance'] = $this->balance(['mid' => $v['mid']]);//余额
            if ($v['examine'] == 0) {
                $Data[$k]['amount_frozen'] = $v['money'];//冻结金额
            } else {
                $Data[$k]['amount_frozen'] = 0;//冻结金额
            }
            $Data[$k]['number_games'] = $this->betsNumberv(['mid' => $v['mid']]);//游戏局数
            $a = GameEventBet::where(['mid' => $v['mid'], 'type' => 0, 'is_ok' => 1])->count();
            $b = GameEventBet::where(['mid' => $v['mid'], 'type' => 0])->count();
            if ($b == 0) {
                $Data[$k]['winning_probability'] = 0;//胜率
            } else {
                $Data[$k]['winning_probability'] = $a / $b;//胜率
            }
            $Data[$k]['net_win'] = $this->netWin(
                ['mid' => $v['mid'], 'business' => 4],
                ['mid' => $v['mid'], 'business' => 8],
                ['mid' => $v['mid'], 'business' => 3],
                ['mid' => $v['mid'], 'business' => 7]
            );//净赢
            $Data[$k]['user_recharge'] = $this->flowingWater(['mid' => $v['mid'], 'business' => 1]);//充值
            $Data[$k]['user_withdraw'] = MemberWithdrawOrder::where([['mid', '=', $v['mid']], ['examine', '=', 1]])->sum('money');//提现
            $Data[$k]['Transfer_out'] = $this->flowingWater(['mid' => $v['mid'], 'business' => 11]);//转出
            $Data[$k]['into'] = $this->flowingWater(['mid' => $v['mid'], 'business' => 10]);//转入
            $Data[$k]['dig'] = abs($this->flowingWater(['mid' => $v['mid'], 'business' => 12]));//挖矿
            $Data[$k]['received_income'] = $this->flowingWater(['mid' => $v['mid'], 'business' => 13, 'currency' => 1]);//已领收益
            $Data[$k]['create_time'] = $v['account']['create_time'];
            $Data[$k]['withdraw_amount'] = $v['money'];//提现金额
            $Data[$k]['withdraw_address'] = $v['address'];//提现地址
            $Data[$k]['state'] = $v['examine'];//状态
            $total['number_passes'] = MemberWithdrawOrder::where(['mid' => $v['mid'], 'examine' => 1])->count();//已通过笔数
            $total['passes_amount'] = MemberWithdrawOrder::where(['mid' => $v['mid'], 'examine' => 1])->sum('money');//已通过金额
            $total['number_reviewed'] = MemberWithdrawOrder::where(['mid' => $v['mid'], 'examine' => 0])->count();//待审核笔数
            $total['reviewed_amount'] = MemberWithdrawOrder::where(['mid' => $v['mid'], 'examine' => 0])->sum('money');//待审核金额
            $total['number_rejected'] = MemberWithdrawOrder::where(['mid' => $v['mid'], 'examine' => 2])->count();//已拒绝笔数
            $total['rejected_amount'] = MemberWithdrawOrder::where(['mid' => $v['mid'], 'examine' => 2])->sum('money');//已拒绝金额
        }
        $total['primary_account_address'] = get_config('wallet', 'wallet', 'collection_address');//主账户地址
        $banlance = (new Address())->balance($total['primary_account_address']);
        $total['balance_of_primary_account'] = empty($banlance['balance']) ? 0 : $banlance['balance'];//主账号余额
        $total['master_account_TRX_balance'] = empty($banlance['TRXBalance']) ? 0 : $banlance['TRXBalance'];//主账号Trx余额
        $total['withdrawal_account_address'] = get_config('wallet', 'wallet', 'withdrawal_address');//提现账户地址
        $withdrawal_banlance = (new Address())->balance($total['withdrawal_account_address']);
        $total['withdrawal_account_balance'] = empty($withdrawal_banlance['balance']) ? 0 : $withdrawal_banlance['balance'];//提现账户余额
        $total['TRX_balance_of_withdrawal_account'] = empty($withdrawal_banlance['TRXBalance']) ? 0 : $withdrawal_banlance['TRXBalance'];//提现账户Trx余额
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['data']['total'] = empty($total) ? [] : $total;
        $datas['data']['list'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    //游戏开奖记录
    public function gameLotteryRecord()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $mid = $this->request->param('mid');
        $account = $this->request->param('account');
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');
        $Member = (new MemberAccount());
        $agentLine = $this->request->merchant->id;
        $mids = $Member->where([['agent_line', 'like', '%|' . $agentLine . '|%'], ['analog', '=', 0]])->column('id');
        if (!empty($account)) {
            $Member = $Member::hasWhere('profile', [['mobile', '=', $account]]);
            if (!empty($mid)) {
                $where [] = ['MemberAccount.id', '=', $mid];
            } else {
                $where [] = ['MemberAccount.id', 'in', $mids];
            }
        } else {
            if (!empty($mid)) {
                $where [] = ['id', '=', $mid];
            } else {
                $where [] = ['id', 'in', $mids];
            }
        }
        if (!empty($start_time)) {
            $where [] = ['create_time', '>', $start_time];
        }
        if (!empty($end_time)) {
            $where [] = ['create_time', '<', $end_time];
        }
        $data = $Member->where($where)->page($page, $limit)->select();
        $count = $Member->where($where)->count();
        foreach ($data as $k => $v) {
            $data[$k]->profile;
        }
        $data = $data->toArray();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $Data[$k]['mid'] = $v['id'];//玩家ID
                $Data[$k]['account'] = $v['profile']['mobile'];//账户
                $Data[$k]['nickname'] = $v['profile']['nickname'];//昵称
                $Data[$k]['balance'] = $this->balance(['mid' => $v['id']]);//余额
                $Data[$k]['number_of_games'] = $this->betsNumberv(['mid' => $v['id']]);//游戏局数
                $Data[$k]['net_win'] = $this->netWin(['mid' => $v['id'], 'business' => 4], ['mid' => $v['id'], 'business' => 8], ['mid' => $v['id'], 'business' => 3], ['mid' => $v['id'], 'business' => 7]);//净赢
                $Data[$k]['user_recharge'] = abs($this->flowingWater(['mid' => $v['id'], 'business' => 1]));//充值
                $Data[$k]['user_withdraw'] = $this->draw_money($v['id']);//提现
                $Data[$k]['Transfer_out'] = abs($this->flowingWater(['mid' => $v['id'], 'business' => 11]));//转出
                $Data[$k]['into'] = abs($this->flowingWater(['mid' => $v['id'], 'business' => 10]));//转入
                $Data[$k]['share_in_the_profit'] = abs(MemberRecord::where([['mid', '=', $v['id']], ['currency', '=', 4], ['business', '=', 9]])->sum('now'));//分享收益
                $Data[$k]['create_time'] = $v['create_time'];//创建时间
            }
        }
        $total['total_balance'] = MemberWallet::where([['mid', 'in', $mids]])->sum('cny');//总余额
        $total['total_number_of_games'] = $this->betsNumberv([['mid', 'in', $mids]]);//总局数
        $total['total_net_win'] = $this->netWin([['mid', 'in', $mids], ['business', '=', 4]], [['mid', 'in', $mids], ['business', '=', 8]], [['mid', 'in', $mids], ['business', '=', 3]], [['mid', 'in', $mids], ['business', '=', 7]]);//总净赢
        $total['total_user_recharge'] = $this->flowingWater([['mid', 'in', $mids], ['business', '=', 1]]);//总充值
        $total['total_user_withdraw'] = MemberWithdrawOrder::where([['mid', 'in', $mids], ['examine', '=', 1]])->sum('money');//总提现
        $total['total_transfer_out'] = abs(MerchantRecord::where([['uid', '=', $this->request->merchant->id], ['business', '=', 2]])->sum('now'));//总转出
        $total['total_into'] = abs(MerchantRecord::where([['uid', '=', $this->request->merchant->id], ['business', '=', 1]])->sum('now'));//总转入
        $total['total_internal_recharge'] = $this->flowingWater([['mid', 'in', $mids], ['business', '=', 6]]);//总内部充值
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['data']['total'] = empty($total) ? [] : $total;
        $datas['data']['list'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    //内部号列表
    public function internalNumberList()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $mid = $this->request->param('mid');
        $mobile = $this->request->param('mobile');
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');
        $Member = (new MemberAccount())->hasWhere('dashboard');
        $agentLine = $this->request->merchant->id;
        $mids = $Member->where([['MemberAccount.agent_line', 'like', '%|' . $agentLine . '|%'], ['MemberAccount.analog', '=', 1]])->column('MemberAccount.id');
        $where = [['MemberAccount.analog', '=', 1]];
        if (!empty($mid)) {
            $where [] = ['MemberAccount.id', '=', $mid];
        } else {
            $where [] = ['MemberAccount.id', 'in', $mids];
        }
        if (!empty($mobile)) {
            $Member = $Member->hasWhere('profile', [['mobile', '=', $mobile]]);
        }
        if (!empty($start_time)) {
            $where [] = ['MemberAccount.create_time', '>', $start_time];
        }
        if (!empty($end_time)) {
            $where [] = ['MemberAccount.create_time', '<', $end_time];
        }
        $order_list = [
            'balance'             => 'MemberDashboard.cny',
            'number_of_games'     => 'MemberDashboard.game_bet',
            'net_win'             => 'MemberDashboard.user_profit',
            'user_recharge'       => 'MemberDashboard.user_recharge',
            'user_withdraw'       => 'MemberDashboard.user_withdraw',
            'Transfer_out'        => 'MemberDashboard.out',
            'winning_probability' => 'MemberDashboard.winning_probability',
            'into'                => 'MemberDashboard.into',
            'amount_frozen'       => 'MemberDashboard.user_withdraw_examine',
            'dig'                 => 'MemberDashboard.minging',
            'total_revenue'       => 'MemberDashboard.total_revenue',
            'received_income'     => 'MemberDashboard.share_receive',
            'fee'                 => 'MemberDashboard.fee',
        ];
        $request = $this->request;
        if (!empty($request->param('sort'))) {
            $order = $order_list[$request->param('sort')] . ' ' . str_replace('ending', '', $request->param('sorts'));
        } else {
            $order = 'MemberAccount.id desc';
        }
        $data = $Member->where($where)->order($order)->page($page, $limit)->select();
        $count = $Member->where($where)->count();
        foreach ($data as $k => $v) {
            $data[$k]->profile;
        }
        $data = $data->toArray();
//        var_dump($data);var_dump($where);var_dump($mids);
        foreach ($data as $k => $v) {
            $Data[$k]['mid'] = $v['id'];//玩家ID
            $Data[$k]['nickname'] = $v['profile']['nickname'];//昵称
            $Data[$k]['mobile'] = $v['profile']['mobile'];//手机号
            $Data[$k]['balance'] = $this->balance(['mid' => $v['id']]);//余额
            $Data[$k]['number_of_games'] = $this->betsNumberv(['mid' => $v['id']]);//游戏局数
            $Data[$k]['net_win'] = $this->netWin(['mid' => $v['id'], 'business' => 4], ['mid' => $v['id'], 'business' => 8], ['mid' => $v['id'], 'business' => 3], ['mid' => $v['id'], 'business' => 7]);//净赢
            $Data[$k]['user_recharge'] = abs($this->flowingWater(['mid' => $v['id'], 'business' => 1]));//充值
            $Data[$k]['user_withdraw'] = $this->draw_money($v['id']);//提现
            $Data[$k]['Transfer_out'] = abs($this->flowingWater(['mid' => $v['id'], 'business' => 11]));//转出
            $Data[$k]['into'] = abs($this->flowingWater(['mid' => $v['id'], 'business' => 10]));//转入
            $Data[$k]['received_income'] = abs($this->flowingWater(['mid' => $v['id'], 'business' => 13, 'currency' => 1]));//已领收益
            $Data[$k]['total_revenue'] = $Data[$k]['net_win'] + $Data[$k]['received_income'];//总收益
            $Data[$k]['residual_income'] = abs($Data[$k]['total_revenue']) - abs($Data[$k]['received_income']);//剩余收益
            $Data[$k]['create_time'] = $v['create_time'];//创建时间
        }
        $datas['page'] = $page;
        $datas['limit'] = $limit;
        $datas['count'] = $count;
        $datas['data'] = empty($Data) ? [] : $Data;
        return success($datas);
    }

    public function getMerchant()
    {
        $where = [];

        $page = $this->request->param('page/d', 1);

        $limit = $this->request->param('limit/d', 10);

        $agentLine = $this->request->merchant->id;

        $PayOrder = MerchantAccount::where([['agent_line', 'like', '%|' . $agentLine . '|%']]);

        $agent = $this->request->param('agent/d', null);

        if (!is_null($agent)) {
            $PayOrder = $PayOrder->where([['agent_line', 'like', '%|' . $agent . '|%']]);
        } else {
            $PayOrder = $PayOrder->where('agent', $this->request->merchant->agentLv + 1);
        }

        $profile = $this->request->param('profile');
        if ($profile) {
            foreach ($profile as $key => $item) {
                if (empty($item)) {
                    unset($profile[$key]);
                }
            }
            if (count($profile) > 0) {
                $PayOrder = $PayOrder->hasWhere('profile', $profile);
            }
        }


        $where = [];
        $status = $this->request->param('status/d', null);
        if (!is_null($status)) {
            $where['status'] = $status;
        }
        $PayOrder = $PayOrder->where($where);

        $count = $PayOrder->count();

        $PayOrder = $PayOrder->page($page)
            ->limit($limit)
            ->select();
        foreach ($PayOrder as $key => $item) {
            $PayOrder[$key]->profile;
            $PayOrder[$key]->dashboard;
            $PayOrder[$key]->wallet;
        }

        $list = $PayOrder->toArray();

        $pages = ceil($count / $limit);

        $data['count'] = $count;
        $data['pages'] = $pages;
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['list'] = $list;


        return success($data);
    }

    public function getDashboard()
    {
        $merchant = MerchantDashboard::where('uid', $this->request->merchant->id)->withoutField('id,uid,update_time,delete_time,create_time')->find();
        $mid = MemberAccount::where([['analog', '=', 0], ['agent_line', 'like', "%|" . $merchant->id . "|%"]])->column('id');
        $merchant['now_money'] = GameEventBet::where([['mid', 'in', $mid], ['opentime', '<', 100]])->sum('money');
        $merchant['sizzler'] = abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 8]])->sum('now')) - abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 7]])->sum('now'));
        $merchant['event'] = abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 4]])->sum('now')) - abs(MemberRecord::where([['mid', 'in', $mid], ['business', '=', 3]])->sum('now'));
        $list = [
            'team_member'           => '总用户数',
            'team_valid_member'     => '有效用户数（充值过且15天内上过线）',
            'team_profit'           => '总盈亏金额（总充值-总提现-总用户余额-用户提现待审核）',
            'team_recharge'         => '总充值金额（只统计审核通过充值，不统计手动充值）',
            'team_withdraw'         => '总提现金额（只统计提现审核通过）',
            'team_withdraw_examine' => '用户提现金额（待审核提现）',
            'team_money'            => '总用户余额（用户账户余额）',
            'day_register'          => '今日注册',
            'day_active'            => '今日活跃用户（上过线）',
            'day_recharge'          => '今日充值（统计0：00-23：59）',
            'day_withdraw'          => '今日提现',
            'day_event_money'       => '今日比赛总交易金额',
            'day_event_number'      => '今日比赛总交易笔数',
            'day_event_award'       => '今日比赛总返奖金额',
            'day_event_profit'      => '今日比赛盈利金额',
            'day_sizzler_money'     => '今日休闲交易金额',
            'day_sizzler_number'    => '今日休闲交易笔数',
            'day_sizzler_award'     => '今日休闲返奖金额',
            'day_sizzler_profit'    => '今日休闲盈利金额',
            'day_event'             => '今日交易总盈亏（用户交易金额-用户所获奖金）',
            'day_sizzler'           => '今日时时乐盈亏（用户交易金额-用户所获奖金） ',
            'now_money'             => '当前交易',
            'sizzler'               => '时时乐盈亏（用户交易金额-用户所获奖金） ',
            'event'                 => '赛事盈亏（用户交易金额-用户所获奖金） ',
        ];
        return success([$merchant, $list]);

    }

    public function saveEdit()
    {
        $param = $this->request->param();
        if (!$param['id']) {
            return error('账户标识不存在!');
        }
        $merchant = MerchantAccount::find($param['id']);
        if (!$merchant) {
            return error('账户不存在!');
        }
        if (strpos((string)$this->request->merchant->id, $merchant->agent_line)) {
            return error('无权限!');
        }
        $update = [];

        foreach ($param as $key => $item) {
            if (is_array($item)) {
//                foreach ($item as $key => $sitem) {
                foreach ($item as $k => $sitem) {
                    if (empty($sitem)) {
                        unset($item[$key]);
                    }
                }
                $merchant->$key->save($item);
            } else {
                if ($item == 0 || !empty($item)) {
                    $update[$key] = $item;
                }
            }
        }
        $merchant->save($update);

        return success('操作成功!');
    }

    public function saveDelete()
    {
        $param = $this->request->param();
        if (!$param['id']) {
            return error('账户标识不存在!');
        }
        $merchant = MerchantAccount::find($param['id']);
        if (!$merchant) {
            return error('账户不存在!');
        }
        if (strpos($param['id'], $merchant->agent_line)) {
            return error('无权限!');
        }

        $agent = MerchantAccount::where('agent_line', 'like', '%|' . $param['id'] . '|%')->select();
        foreach ($agent as $item) {
            $item->profile()->delete();
            $item->wallet()->delete();
            $item->dashboard()->delete();
            $item->delete();
        }
        $merchant->profile()->delete();
        $merchant->wallet()->delete();
        $merchant->dashboard()->delete();
        $merchant->delete();


        return success('操作成功!');
    }

    public function saveAdd()
    {
        $result = (new Account())->create($this->request->param('mobile'), $this->request->param('password'), $this->request->merchant->id);
        if (!$result) {
            return error('添加失败!');
        }

        return success('操作成功!');

    }

    public function logout()
    {
        try {
            /*执行主体*/
            jwt_delete($this->request->merchant->identification);
        } catch (\Exception $e) {
            var_dump($e->getTrace());
            return error();
        }
        return success();
    }
}
