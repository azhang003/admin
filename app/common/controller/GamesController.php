<?php

namespace app\common\controller;

use app\common\controller\member\Redis;
use app\common\model\GameEventList;
use think\Exception;

class GamesController
{
    private $redis;
    public $list;
    public $bets;
    public $AllMembersBets;

    public function __construct($list)
    {
        $this->redis = Redis::redis(6);
        $this->list = GameEventList::where('id', $list)->find();
        if (empty($this->list)) {
            throw new Exception('当期赛事不存在!');
        }
        $this->bets = self::getBetCacheKeys($this->list->id);
        return $this;
    }

    /**
     * 缓存投注订单
     * @param array $betArr
     * @return bool
     */
    public static function cacheBet(array $betArr): bool
    {
        if (in_array($betArr['cycle'], ['1m', '5m'])) {
            $expire = ($betArr['cycle'] == '1m' ? 60 : 300) + ($betArr['end_time'] - time());
            $redis = Redis::redis(6);
            try {
                $Bet_key = 'EventBetCache:' . $betArr['list_id'] . ':' . $betArr['mid'];
                $redis->sAdd($Bet_key, json_encode($betArr));
                $redis->expire($Bet_key, $expire);
                //增加会员ID索引
//                $Bet_list_key = 'EventBetListCache:' . $betArr['list_id'];
//                $redis->zAdd($Bet_list_key,1 ,$betArr['mid']);
//                $redis->expire($Bet_key, $expire);
            } catch (\Exception $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取谋期所有keys
     * @param $list_id
     * @return array|boolean
     */
    public static function getBetCacheKeys($list_id = null)
    {
        if (empty($list_id)) {
            return false;
        }
        $redis = Redis::redis(6);
        $Bet_key = 'EventBetCache:' . $list_id . ':*';
        $Bets = $redis->keys($Bet_key);
        return $Bets;
    }

    /**
     * 获取所有投注人数
     * @return int
     */
    public function getPeopleCounts()
    {
        return count($this->bets);
    }

    /**
     * 获取所有投注缓存集合KEYS
     * @return array|bool
     */
    public function getBets()
    {
        return $this->bets;
    }

    /**
     * 获取当前赛事
     * @return GameEventList|array|mixed|\think\Model|null
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * 获取缓存的所有会员及订单
     * @return $this|array
     */
    public function getAllBets()
    {
        $AllMembersBets = [];
        foreach ($this->bets as $bet) {
            $AllMembersBets[] = $this->redis->sMembers($bet);
        }
        $this->AllMembersBets = $AllMembersBets;
        return $this;
    }

    /**
     * 获取所有订单
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllMembersBets()
    {
        $BetOrde = [];
        $proportions = get_config('game', 'game', 'proportion');
        if (count($this->AllMembersBets) > 0) {
            foreach ($this->AllMembersBets as $MemberBets) {
                $memberCnyA = 0;
                $memberCnyB = 0;
                $BetOrders = [];
                $MemberTimeA = $MemberTimeB = 2147483647;
                $Orders = [];
                foreach ($MemberBets as $MemberBet) {
                    $MemberBetData = json_decode($MemberBet, true);
                    if ($MemberBetData['create_time'] < $MemberTimeA && $MemberBetData['bet'] == 1) {
                        $MemberTimeA = $MemberBetData['create_time'];
                        $memberCnyA = $MemberBetData['cny'];
                    }
                    if ($MemberBetData['create_time'] < $MemberTimeB && $MemberBetData['bet'] == 2) {
                        $MemberTimeB = $MemberBetData['create_time'];
                        $memberCnyB = $MemberBetData['cny'];
                    }
                    $Orders[] = $MemberBetData;
                }
                foreach ($Orders as $order) {
                    $betName = $order['bet'] == 2 ? "B" : "A";
                    if (!empty($BetOrders[$order['mid'] . $betName])) {
                        $order['money'] = bcadd($order['money'],$BetOrders[$order['mid'] . $betName]['money'],8);
                    }
                    $BetOrders[$order['mid'] . $betName] = $order;
                }
                foreach ($BetOrders as $key => $betOrder) {
                    if ($betOrder['bet'] == 2) {
                        if ($memberCnyA <= $memberCnyB) {
                            $memberCny = $memberCnyB;
                        } else {
//                            $memberCny = $memberCnyA - $BetOrders[$betOrder['mid'] . 'A']['money'];
                            $memberCny = bcsub($memberCnyA , $BetOrders[$betOrder['mid'] . 'A']['money'],8);
                        }
                    } else {
                        if ($memberCnyA >= $memberCnyB) {
                            $memberCny = $memberCnyA;
                        } else {
                            $memberCny = bcsub($memberCnyB , $BetOrders[$betOrder['mid'] . 'B']['money'],8);
                        }
                    }
//                    $proportion = $memberCny * $proportions;
                    $proportion = bcmul($memberCny , $proportions,8);
                    var_dump("aa:>>" . $proportion . "\n");
                    var_dump("投注余额:>>" . $betOrder['money'] . "\n");
                    $excess_amount = bcsub($betOrder['money'] , $proportion,8);                 //超出金额
                    $excess_proportion = $excess_amount > 0 ? (bcdiv($excess_amount , ($proportion == 0 ? 1 : $proportion),8)) : 0;                 //超出比例
                    $BetOrde[$key] = $betOrder;
                    $BetOrde[$key]['excess_amount'] = $excess_amount > 0 ? $excess_amount : 0;
                    $BetOrde[$key]['excess_proportion'] = $excess_proportion ?? 0;
                    $BetOrde[$key]['cny'] = $memberCny;
                }
            }
        }
        return $BetOrde;
    }

    /**
     * 获取所有投注用户
     * @return array|int
     */
    public function getAllPeopleId()
    {
        if (count($this->bets) == 0) {
            return 0;
        }
        $AllPeopleId = [];
        foreach ($this->bets as $bet) {
            $AllPeopleId[] = explode(":", $bet)[2];
        }
        return $AllPeopleId;
    }

    /**
     * 重构投注详情
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTypeBetsDetail()
    {
        $betUp = 0;//押注人数
        $betUpMoney = 0;//押注金额
        $betUpPeople = 0;//重复人数
        $betUpBet = 0;//重复金额
        $betUpAmount = 0;//超出金额
        $betUpAmountRate = 0;//超出比例
        $betUpOvertop = 0;//超出人数
        $betUpCny = 0;//会员余额

        $betDown = 0;
        $betDownMoney = 0;
        $betDownPeople = 0;
        $betDownBet = 0;
        $betDownAmount = 0;
        $betDownAmountRate = 0;
        $betDownOvertop = 0;
        $betDownCny = 0;
        $proportion = (float)get_config('game', 'game', 'proportion');
        $data = $this->getAllMembersBets();
        foreach ($data as $MemberBets) {
            if (count($MemberBets) == 0) {
                continue;
            }
            if ($MemberBets['bet'] == 1) {
                $betUp++;
                $betUpMoney = bcadd($betUpMoney,$MemberBets['money'],8);
                $betUpCny = bcadd($betUpCny,$MemberBets['cny'],8);
                $Upproportionss = bcmul($MemberBets['cny'] , $proportion,8);
                $Upexcess_amountaa =bcsub($MemberBets['money'] , $Upproportionss,8);                 //超出金额
                if ($Upexcess_amountaa > 0) {
                    $betUpOvertop++;
                }
            } else {
                $betDown++;
                $betDownMoney = bcadd($betDownMoney ,$MemberBets['money'],8);
                $betDownCny = bcadd($betDownCny ,$MemberBets['cny'],8);
                $Downproportionss = bcmul($MemberBets['cny'] , $proportion,8);
                $Downexcess_amountaa = bcdiv($MemberBets['money'] , $Downproportionss,8);                 //超出金额
                if ($Downexcess_amountaa > 0) {
                    $betDownOvertop++;
                }
            }
        }
        $Upproportion = bcmul($betUpCny , $proportion,8);
        $Upexcess_amount = bcsub($betUpMoney , $Upproportion,8);                 //超出金额
        if ($Upexcess_amount > 0) {
            $excess_proportion = bcdiv($Upexcess_amount , ($Upproportion != 0 ? $Upproportion : 1),8);                 //超出比例
            $betUpAmount = $Upexcess_amount;
            $betUpAmountRate = $excess_proportion;
        }
        $Downproportion = bcmul($betDownCny , $proportion,8);
        $Downexcess_amount = bcsub($betDownMoney , $Downproportion,8);                 //超出金额
        if ($Downexcess_amount > 0) {
            $excess_proportion = bcsub($Downexcess_amount , ($Downproportion != 0 ? $Downproportion : 1),8);                 //超出比例
            $betDownAmount = $Downexcess_amount;
            $betDownAmountRate = $excess_proportion;
        }
        foreach ($this->AllMembersBets as $MemberBets) {
            if (count($MemberBets) == 0) {
                continue;
            }
            $MemberBes = [];
            foreach ($MemberBets as $memberBet) {
                $MemberBes[] = json_decode($memberBet, true);
            }
            if (count($MemberBes) > 1) {
                $edit = array_column($MemberBes, 'create_time');
                array_multisort($edit, SORT_ASC, $MemberBes);
            }
            $MemberUp = 0;
            $MemberDown = 0;
            foreach ($MemberBes as $memberBetData) {
//                $memberBetData = json_decode($memberBet, true);
                if ($memberBetData['bet'] == 1) {
                    $MemberUp++;
                    if ($MemberUp > 1) {
                        $betUpPeople++;
                        $betUpBet = bcadd($betUpBet , $memberBetData['money'],8);
                    }
                } else {
                    $MemberDown++;
                    if ($MemberDown > 1) {
                        $betDownPeople++;
                        $betDownBet = bcadd($betDownBet ,$memberBetData['money'],8);
                    }
                }

            }
        }
        return [
            [
                'stake' => "涨",
                "count" => $betUp, //人数
                "repeat" => $betUpPeople, //重复人数
                "bet" => $betUpBet, //重复金额
                "money" => $betUpMoney, //押注金额
                "amount" => $betUpAmount, //超出金额
                "rate" => $betUpAmountRate, //超出比例
                "cny" => $betUpCny, //总余额
                "overtop" => $betUpOvertop, //超出人数
            ],
            [
                'stake' => "跌",
                "count" => $betDown,
                "repeat" => $betDownPeople,
                "bet" => $betDownBet, //重复金额
                "money" => $betDownMoney,
                "amount" => $betDownAmount,
                "rate" => $betDownAmountRate,
                "cny" => $betDownCny,
                "overtop" => $betDownOvertop,
            ]
        ];
    }
}