<?php

namespace app\job;

use app\common\controller\member\QueueError;
use app\common\model\MemberAddress;
use app\common\model\MemberPayOrder;
use app\common\model\MemberRecord;
use app\common\model\MemberTeam;
use app\common\model\MemberWallet;
use app\common\model\MerchantIndex;
use app\common\model\SystemConfig;
use app\common\service\Integration;
use think\Exception;
use think\facade\Db;
use think\queue\Job;

class queueRecharge
{

    public $queueJob;

    public function fire(Job $job, array $data)
    {
        $this->queueJob = $job;
        if ($this->doJOb($data)) {
            $job->delete();
            echo "执行成功删除任务" . $job->attempts() . "\n";
        } else {
            if ($job->attempts() > 2){
                $job->delete();
                QueueError::setError([
                    'title'      => $data['task'],
                    'controller' => self::class,
                    'context'    => json_encode($data),
                ]);
            }

            var_dump('执行失败几次:' . $job->attempts() . '次' . "\n");
        }
    }


    private function doJOb(array $data)
    {
        var_dump(json_encode($data));
        try {
            if (array_key_exists('task', $data) && array_key_exists('data', $data) && is_array($data['data'])) {
                $task = $data['task'];
                $bool = $this->$task($data['data']);
                return $bool;
            } else {
                return false;
            }
        } catch (\Exception $exception) {
            var_dump(json_encode($exception->getMessage()));
            return false;
        }
    }


    /** 更新地址余额 **/

    public  function updateAddressMoney(array $data)
    {

        $ADRESS = MemberAddress::where('mid',$data['mid'])->find();
        if ($ADRESS){
            $Integration = new Integration();
            try {
                $money = $Integration->getTrc20Balance($ADRESS->trc20_pv);
                $TrxMoney = $Integration->getBalance($ADRESS->trc20_pv);
                if ($money > 0){
                    $ADRESS->money = $money?:0;
                    $ADRESS->trc_trx = $TrxMoney?:0;
                    $bool = $ADRESS->save();
                    if (!$bool){
                        return false;
                    }
                }
                $collect_mix = get_config('wallet','wallet','collect_mix');
                if ($money >= $collect_mix){
                    //去归集
                    queue(self::class,['task' => 'tronIntegrationFromUser','data' => ['mid' => $ADRESS->mid,'address' => $ADRESS->trc_address,'privateKey' => $ADRESS->trc20_pv]],0,'tronIntegrationFromUser');
                }
            }catch (\Exception $exception){
                var_dump($exception->getMessage());
                var_dump($exception->getTraceAsString());
                return false;
            }
        }



        return true;
    }


    public function tronIntegrationFromUser(array $data)
    {
        $Integration = new Integration();
        $IntegrationAddress = get_config('wallet','wallet','collection_address');
        $address = $data['address'];
        $privateKey = $data['privateKey'];
        //USDT 余额
        $Balance = $Integration->getTrc20Balance($privateKey, $address);
        $collect_mix = get_config('wallet','wallet','collect_mix');
        if (isset($data['collect_mix']) && ((int)$data['collect_mix']) > 0){
            $collect_mix = (int)$data['collect_mix'];
        }
        if ($Balance >= $collect_mix) {
            //trx 余额
            $must = 8;
            $TRXBalance = $Integration->getBalance(null, $address);
            if ($TRXBalance >= $must) {
                $trxTransfer = $Integration->trxTransfer($privateKey, $IntegrationAddress, $Balance);
                if ($trxTransfer['result']) {
                    $TRXBalance = $Integration->getBalance(null, $address);
                    $bool = MemberAddress::where('mid', $data['mid'])->update([
                        'money' => 0,
                        'trc_trx' => $TRXBalance
                    ]);
                    var_dump('归集成功,地址余额更新:' . ($bool?"成功":"失败") . "!\n");
                    return true;
                } else {
                    var_dump("归集失败或者归集时候没有足够的手续费!\n");
                    return false;
                }
            } else {
                $ChargePrivateKey = get_config('wallet','wallet','private_key');
                $trx = $Integration->Transfer($ChargePrivateKey, $address, $must);
                var_dump("手续费转账".json_encode($trx) . "\n");
                if (empty($trx['result'])) {
                    var_dump("官方手续费账户没有足够的TRX!\n");
                    return false;
                }
                (new SystemConfig())->where([['group','=','block'],['gname','=','trx']])
                    ->inc('value',$must)
                    ->update();
                $this->queueJob->release(15);
            }
        }else{
           $bool = MemberAddress::where('mid',$data['mid'])->update([
                'money' => $Balance
            ]);
            var_dump('该地址当前余额为:' . $Balance . ',后台设置值为:' . $collect_mix . "\n" . "地址余额更新:" . ($bool?"成功":"失败") . "\n");
            return true;
        }
    }
    /** 其他 **/
}