<?php

namespace app\service\controller;

use app\common\controller\GameController;
use app\common\controller\GamesController;
use app\common\model\MemberAccount;
use app\common\model\MerchantAccount;

class Test
{
    public function index()
    {
        try {
            $id = request()->param('id/d', 1);
            $GamesController = (new GamesController($id))->getAllBets();
            $data['list'] = $GamesController->getTypeBetsDetail();
        }catch (\Exception $exception){
            var_dump($exception->getMessage());
            var_dump($exception->getTrace());
        }
        return success($data);
    }
    public function indexs()
    {
        try {
            $id = request()->param('id/d', 1);
            $GamesController = (new GameController($id))->getAllBets();
            $data['list'] = $GamesController->getTypeBetsDetail();
        }catch (\Exception $exception){
            var_dump($exception->getMessage());
            var_dump($exception->getTrace());
            return ;
        }
        return success($data);
    }

    public function nowddd()
    {
        $merchant = MerchantAccount::where('id',2)->find();
    }
    public function currentPrizeStatistics()
    {
        $merchant = MerchantAccount::where('id',2)->find();
        try {
            $page = request()->param('page/d', 1);
            $limit = request()->param('limit/d', 30);
            $sort = request()->param('sort/s', 'cny');
            $desc = request()->param('desc/s', 3);
            $id = request()->param('id/d');
            //list_id
            $list = (new GamesController($id))->getAllBets()->getAllMembersBets();
            $status = request()->param('status');//用户类型
            if (!empty($status)) {
                switch ($status) {
                    case 1://正常用户
                        $mid = $this->mid_list(10, $merchant->id);
                        break;
                    case 2://关注用户
                        $mid = $this->mid_list(11, $merchant->id);
                        break;
                    case 3://超出比例用户
                        $mid = $this->mid_list(6, $merchant->id, $id);
                        break;
                    case 4://IP重复用户
                        $mid = $this->mid_list(5, $merchant->id);
                        break;
                    case 5://全部用户
                        $mid = $this->mid_list(7, $merchant->id);
                        break;
                    case 6://买涨用户
                        $mid = $this->mid_list(8, $merchant->id);
                        break;
                    case 7://买跌用户
                        $mid = $this->mid_list(9, $merchant->id);
                        break;
                    case 8://直属用户
                        $mid = $this->mid_list(1, $merchant->id);
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
                    array_multisort($edit, $desc=="ascending"?4:3, $list);
                }
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
            $datas['open'] = in_array($merchant->id, $open_list) ? 1 : 0;
            $datas['limit'] = $limit;
            $datas['count'] = $count;
            $datas['data']['current_period'] = empty($titleData->title) ? '' : $titleData->title;//当前期数
            $datas['data']['current_bets'] = get_config('game', 'game', 'totals');//当前下注数
            $datas['data']['proportion'] = get_config('game', 'game', 'proportion');//比例
            $datas['data']['list'] = !empty($GameBetData) ? array_values($GameBetData) : [];
        }catch (\Exception $exception){
            var_dump($exception->getMessage());
            var_dump($exception->getTrace());
        }
//        return success($datas);


    }
}