<?php


namespace app\common\service;


use app\common\controller\member\Wallet;
use app\common\model\MemberAddress;
use app\common\model\MemberPayOrder;
use app\common\model\MemberTiming;
use app\common\model\MemberWallet;
use app\common\model\SystemConfig;
use app\job\queueRecharge;
use Usdtcloud\TronService\Address;
use Usdtcloud\TronService\TronApi;
use think\Exception;
use think\facade\Db;

class TransactionBlock
{
    public    $ChargeAddress;
    protected $mainNet;
    protected $Blocks;

    public function __construct()
    {
        $this->ChargeAddress = MemberAddress::where(1)->column('mid', 'trc_address');
        $this->mainNet = TronApi::mainNet();
    }

    public function setBlocks()
    {
        $startBlock = get_config('block', 'block', 'block') ?: 37575836;
        $NowBlock = TronApi::mainNet()->getNowBlock()->block_header->raw_data->number;
        if ($NowBlock) {
            if ($NowBlock - $startBlock > 20) {
                $EndBlock = $startBlock + 20;
            } else if ($NowBlock - $startBlock <= 1) {
                $EndBlock = 0;
            } else {
                $EndBlock = $NowBlock - 1;
            }
        } else {
            $EndBlock = 0;
        }

        $this->Blocks = [
            'start' => $startBlock,
            'end'   => $EndBlock,
            'now'   => $NowBlock,
        ];

        return $this;
    }

    public function checkBlock()
    {
        $Blocks = $this->Blocks;
        if (empty($Blocks) || $Blocks['start'] == 0 || $Blocks['end'] == 0) {
            throw new Exception('无可扫描的区块!');
        }
        var_dump('扫描区块开始!' . $Blocks['start'] . '-' . $Blocks['end']);
        $rpcBlock = TronApi::mainNet()->getBlockByLimitNext($Blocks['start'], $Blocks['end']);
        $rpcBlock = object_array($rpcBlock);
        $rows = [];
        var_dump('数据循环开始!' . count($rpcBlock['block']) . '个块数据!');
        if (isset($rpcBlock['block']) && $rpcBlock['block']) {
            foreach ($rpcBlock['block'] as $key => $value) {
                $seek_num = $value['block_header']['raw_data']['number'];
                $transactions = isset($value['transactions']) ? $value['transactions'] : '';
                if (!empty($transactions)) {
                    foreach ($transactions as $key => $value) {
                        $type = isset($value['raw_data']['contract'][0]['type']) ? $value['raw_data']['contract'][0]['type'] : '';
                        if ($type == 'TriggerSmartContract') {
                            $resDta = $this->TriggerSmartContract($value);
                            if (!empty($resDta)) {
                                if (empty($rows)) {
                                    $rows = [$resDta];
                                } else {
                                    array_push($rows, $resDta);
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($rows)) {
            $this->TransactionSave($rows);
        }
        $config = SystemConfig::where([['group', '=', 'block'], ['gname', '=', 'block']])->find()->toArray();
        $value = json_decode($config['value'], true);
        $value['block'] = $this->Blocks['end'];
        (new SystemConfig())->setUpdate('block', 'block', $value);
        var_dump(date('Y-m-d h:i:s') . '已扫描到-' . $this->Blocks['end'] . '区块!最新区块' . $this->Blocks['now']);
    }

    public function TriggerSmartContract($value)
    {
        //
        $resData = [];
        $contract_address = $value['raw_data']['contract'][0]['parameter']['value']['contract_address'];
        $txid = $value['txID'];
        $data = '';
        if (isset($value['raw_data']['contract'][0]['parameter']['value']['data'])) {
            $data = $value['raw_data']['contract'][0]['parameter']['value']['data'];
        }
        //判断是否是USDT,判断是否调用智能合约transfer函数
        if ($contract_address == '41a614f803b6fd780986a42c78ec9c7f77e6ded13c' && substr($data, 0, 8) == 'a9059cbb') {
            $hextoadd = substr($data, 32, 40);
            $to_address = Address::encode('41' . $hextoadd);
            //判断转入地址是否用户地址
            if (isset($this->ChargeAddress[$to_address])) {
                //转出地址
                $from_address = Address::encode($value['raw_data']['contract'][0]['parameter']['value']['owner_address']);
                //转入金额

                $amount = hexdec(preg_replace('/^0*/', '', substr($data, 72, 64))) / 1000000;
                $time = time();
                if (isset($value['raw_data']['timestamp'])) $time = intval($value['raw_data']['timestamp'] / 1000);
                $resData['hash_id'] = $txid;
                $resData['mid'] = $this->ChargeAddress[$to_address];
                $resData['type'] = 'USDT';
                $resData['transfer_status'] = 0;
                $resData['treaty'] = 'TRC20';
                $resData['status'] = $value['ret'][0]['contractRet'] == "SUCCESS" ? 1 : 0;
                $resData['number'] = $amount;
                $resData['time'] = $time;
                $resData['reason'] = '';
                $resData['treaty'] = 'TRC20';
                $resData['create_time'] = time();
                $resData['address'] = $from_address;
            }
        }
        return $resData;
    }

    public function TransferAssetContract($value)
    {
        $resData = [];

        $amount = number_format($value['raw_data']['contract'][0]['parameter']['value']['amount'] / 1000000, 6, '.', '');
        $txid = $value['txID'];
        $from_address = Address::encode($value['raw_data']['contract'][0]['parameter']['value']['owner_address']);

        $to_address = Address::encode($value['raw_data']['contract'][0]['parameter']['value']['to_address']);

        if (isset($this->ChargeAddress[$to_address])) {

            $tokenId = $value['raw_data']['contract'][0]['parameter']['value']['asset_name'];

            $token = TronApi::mainNet()->getAssetIssueById(Address::hexToStr($tokenId));

            $tokenname = Address::hexToStr($token->abbr);

            if (isset($value['raw_data']['timestamp'])) $time = intval($value['raw_data']['timestamp'] / 1000);
            $resData['rid'] = $txid;
            $resData['hash_id'] = $txid;
            $resData['mid'] = $this->ChargeAddress[$to_address];
            $resData['type'] = $tokenname;
            $resData['transfer_status'] = 0;
            $resData['treaty'] = 'TRC20';
            $resData['status'] = $value['ret'][0]['contractRet'] == "SUCCESS" ? 1 : 0;
            $resData['number'] = $amount;
            $resData['time'] = $time;
            $resData['reason'] = '';
            $resData['treaty'] = 'TRC_10';
            $resData['create_time'] = time();
            $resData['address'] = $from_address;
        }
        return $resData;
    }

    public function TransferContract($value)
    {
        $resData = [];

        $amount = number_format($value['raw_data']['contract'][0]['parameter']['value']['amount'] / 1000000, 6, '.', '');
        $txid = $value['txID'];
        $from_address = Address::encode($value['raw_data']['contract'][0]['parameter']['value']['owner_address']);

        $to_address = Address::encode($value['raw_data']['contract'][0]['parameter']['value']['to_address']);

        if (isset($this->ChargeAddress[$to_address])) {

            if (isset($value['raw_data']['timestamp'])) $time = intval($value['raw_data']['timestamp'] / 1000);
            $resData['rid'] = $txid;
            $resData['hash_id'] = $txid;
            $resData['mid'] = $this->ChargeAddress[$to_address];
            $resData['type'] = 'TRX';
            $resData['transfer_status'] = 0;
            $resData['treaty'] = 'TRC20';
            $resData['status'] = $value['ret'][0]['contractRet'] == "SUCCESS" ? 1 : 0;
            $resData['number'] = $amount;
            $resData['time'] = $time;
            $resData['reason'] = '';
            $resData['treaty'] = 'TRX';
            $resData['address'] = $from_address;
            $resData['create_time'] = time();
//            $resData['trx_status']   = $value['ret'][0]['contractRet'];
        }
        return $resData;
    }

    public function TransactionSave($rows)
    {
        $haves = [];
        foreach ($rows as $key => $row) {
            $row['rid'] = $row['hash_id'];
            $rows[$key]['rid'] = $row['hash_id'];
            $is_have = MemberPayOrder::where('rid', $row['rid'])
                ->find();
            if (!empty($is_have)) {
                unset($rows[$key]);
            }
            if ($row['number'] < get_config('wallet', 'wallet', 'rechage_mix')) {
                $rows[$key]['status'] = 0;
            }
        }

        if (!empty($haves)) {
            foreach ($haves as $have) {
                unset($rows[$have]);
            }
        }
//        foreach ($rows as $k => $row) {
//            $currencys = config('hello.currencys');
//            foreach ($currencys as $key => $currency) {
//                if ($currency['name'] == $row['type']){
//                    $rows[$k]['type'] =  $key;
//                }
//            }
//        }
//        var_dump($rows);

        if (count($rows) > 0) {
            $bool = MemberPayOrder::insertAll($rows);
            $this->addUserMoney($rows);
        }
    }

    public function addUserMoney($rows)
    {
        if (!empty($rows)) {
            foreach ($rows as $k => $row) {
//                if ($row['status'] == "1"){
//                    $wallet = MemberWallet::where([['mid','=',$row['mid']]])->value('cny');
//                    (new Wallet())->change($row['mid'],1,[
//                        1 => [$wallet, $row['number'], $wallet + $row['number']],
//                    ]);
//                }
                if ($row['status'] == "1") {
                    if ($row['number'] > get_config('wallet', 'wallet', 'rechage_mast')) {
                        $pid = MemberPayOrder::where('hash_id', $row['hash_id'])->value('id');
                        MemberTiming::insert([
                            'mid'         => $row['mid'],
                            'pid'         => $pid,
                            'number'      => $row['number'],
                            'time'        => time() + get_config('wallet', 'wallet', 'rechage_time'),
                            'create_time' => time(),
                        ]);
                    } else {
                        $wallet = MemberWallet::where([['mid', '=', $row['mid']]])->value('cny');
                        (new Wallet())->change($row['mid'], 1, [
                            1 => [$wallet, $row['number'], $wallet + $row['number']],
                        ]);

                    }
                    queue(queueRecharge::class,['task' => 'updateAddressMoney','data' => ['mid' => $row['mid'],'money' => $row['number']]],0,'updateAddressMoney');
                }
            }


        }
    }
}

