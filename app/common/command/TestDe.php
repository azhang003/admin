<?php

namespace app\common\command;

use app\common\model\GameEventBet;
use app\common\model\GameEventList;
use app\common\model\MemberAccount;
use app\common\model\MemberAddress;
use app\common\model\MemberDashboard;
use app\common\model\MemberLogin;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MemberWithdrawOrder;
use app\job\queueClearOrder;
use app\job\queueRecharge;
use app\job\queueUpdate;
use app\service\controller\Index;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class TestDe extends Command
{
    protected function configure()
    {
        $this->setName('TestDe')->setDescription("计划任务 手动归集!")
            ->addOption('collect_mix', 'c', Option::VALUE_REQUIRED, '最小归集数量', 100);
    }

    //调用SendMessage 这个类时,会自动运行execute方法
    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        $collect_mix = floatval($input->getOption('collect_mix'));
        $output->error($collect_mix);
        if ($collect_mix < 10){
            $output->error('手动归集不能小于10');
            return;
        }
        return;
        $adress = MemberAddress::where([
            [
                "money",
                ">=",
                $collect_mix
            ]
        ])->select();
        foreach ($adress as $ADRESS) {
            queue(queueRecharge::class,['task' => 'tronIntegrationFromUser','data' => ['mid' => $ADRESS->mid,'address' => $ADRESS->trc_address,'privateKey' => $ADRESS->trc20_pv,'collect_mix' => $collect_mix]],0,'tronIntegrationFromUser');
        }
        /*** 这里写计划任务列表集 END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
    }

    public function otg_verify($verificationId, $mobile, $code)
    {
        $apiKey                    = "cPZmnOsb";
        $apiSecret                 = "QHtVnWL4";
        $url                       = "https://api.onbuka.com/v3/otp/verification/verify";
        $timeStamp                 = time();
        $sign                      = md5($apiKey . $apiSecret . $timeStamp);
        $dataArr['verificationId'] = $verificationId;
        $dataArr['code']           = $code;
        $data                      = json_encode($dataArr);
        $headers                   = array('Content-Type:application/json;charset=UTF-8', "Sign:$sign", "Timestamp:$timeStamp", "Api-Key:$apiKey");

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $output = curl_exec($ch);
        curl_close($ch);
        $code = json_decode($output, true);
        var_dump($code);
        if ($code['status'] == '0' && $code['data']['to'] == $mobile && $code['data']['matched'] == 0) {
            return true;
        } else {
            return false;
        }
    }
}
