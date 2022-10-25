<?php

namespace Usdtcloud\TronService;


use Exception;

class TronKit
{
    const HAPY_TOKEN = 'TS2Hzo6KpAc8Ym2nGb3idpMtUpM2GiK2gL';

    public $api;
    public $credential;

    public function __construct($tronApi, $credential = null)
    {
        $this->api        = $tronApi;
        $this->credential = $credential;

        //new ExceptionHandler();
    }

    public function setCredential($credential)
    {
        $this->credential = $credential;
    }

    public function getCredential()
    {
        if (is_null($this->credential)) {
            throw new Exception('Credential not set.');
        }
        return $this->credential;
    }

    public function sendTrx($to, $amount)
    {
        $credential = $this->getCredential();
        $from       = $credential->address()->base58();

        $tx       = $this->api->createTransaction($to, $amount, $from);
        $signedTx = $credential->signTx($tx);
        $ret      = $this->api->broadcastTransaction($signedTx);
        return (object)[
            'txid'   => $signedTx->txID,
            'result' => $ret->result,
        ];
    }

    public function broadcast($tx)
    {
        return $this->api->broadcastTransaction($tx);
    }

    public function getTrxBalance($address)
    {
        return $this->api->getBalance($address);
    }

    public function contract($abi)
    {
        $credential = $this->getCredential();
        return new Contract($this->api, $abi, $credential);
        return $inst;
    }

    public function trc20($address)
    {
        $credential = $this->getCredential();
        $inst       = new Trc20($this->api, $credential);
        return $inst->at($address);
    }
}