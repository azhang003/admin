<?php


namespace app\common\service;


use Usdtcloud\TronService\Credential;
use Usdtcloud\TronService\TronApi;
use Usdtcloud\TronService\TronKit;

class Integration
{
    protected $mainnet;

    protected $trc20;

    protected $contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';


    public function __construct()
    {
        $this->mainnet = TronApi::mainNet();
    }

    public function setContractAddress($contractAddress)
    {
        $this->contractAddress = $contractAddress;
    }

    public function getBalance($PrivateKey = null, $address = null)
    {

        if (is_null($PrivateKey)){
            $PrivateKey = 'bfdc5e21096472aa3341595f644144b5bce7ee55e00e8575ba9c5ecc632c18b3';
        }
        $Credential = Credential::fromPrivateKey($PrivateKey);
        $kit = new TronKit(
            TronApi::mainNet(),
            $Credential
        );
        try {
            /*执行主体*/
            $Balance = $kit->getTrxBalance($address);
        } catch (\Exception $e) {
//            $e->getMessage() == 'Balance error. Maybe you should send 10 trx to this address to activate it.'
            $Balance = 0;
        }
        if ($Balance > 0) {
            $Balance = $Balance / 1000000;
        }
        return $Balance;
    }

    public function getTrc20Balance($PrivateKey = null, $address = null)
    {
        if (is_null($PrivateKey)){
            $PrivateKey = 'bfdc5e21096472aa3341595f644144b5bce7ee55e00e8575ba9c5ecc632c18b3';
        }
        $Credential = Credential::fromPrivateKey($PrivateKey);
        $kit = new TronKit(
            TronApi::mainNet(),
            $Credential
        );
        $usdt = $kit->Trc20($this->contractAddress);          //创建USDT-TRC20代币合约实例

        if (is_null($address)) {
            $address = $Credential->address()->base58();
        }

        $balance = $usdt->balanceOf($address)->toString();  //查询Trc20代币余额

        if ($balance > 0){
            $balance = $balance / 1000000;
        }

        return $balance;

    }

    public function transfer($fromPrivateKey,$to,$amount)
    {
        $kit = new TronKit(
            $this->mainnet,
            Credential::fromPrivateKey($fromPrivateKey)
        );

        $amount = $amount * 1000000;                                       //转账金额，单位：SUN
        try {
            /*执行主体*/
            $ret = $kit->sendTrx($to,$amount);                          //提交Trx转账交易
        } catch (\Exception $e) {
            $ret =  ['txid'=> false,'result'=> false] ;
        }

        return $ret;
    }
    public function trxTransfer($fromPrivateKey,$to,$amount)
    {
        $kit = new TronKit(
            $this->mainnet,
            Credential::fromPrivateKey($fromPrivateKey)
        );
        var_dump($amount);
        $amount = $amount * 1000000;                                       //转账金额，单位：SUN

        $usdt = $kit->Trc20($this->contractAddress);                      //创建Trc20代币合约实例
        try {
            /*执行主体*/
            $ret = $usdt->transfer($to,$amount);

        } catch (\Exception $e) {
            var_dump($e->getTrace());
            return ['txid'=> false,'result'=> false];
        }

        $data = [
           'txid'=> $ret->tx->txID,
           'tx'=> $ret->tx,
           'result'=> $ret->result,
        ];

        return $data;
    }
}