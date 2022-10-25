<?php

namespace Usdtcloud\TronService;


use Exception;

class TronApi
{
    protected $fullNode;
    protected $solidityNode;
    protected $eventNode;

    public static function mainNet()
    {
        return new self('https://api.trongrid.io');//韩国节点 http://13.124.62.58:8090
    }

    public static function testNet()
    {
        return new self('https://api.shasta.trongrid.io');
    }

    public function __construct($fullNodeUrl, $solidityNodeUrl = null, $eventNodeUrl = null)
    {
        if (is_null($solidityNodeUrl)) {
            $solidityNodeUrl = $fullNodeUrl;
        }
        if (is_null($eventNodeUrl)) {
            $eventNodeUrl = $fullNodeUrl;
        }
        $this->fullNode     = new NodeClient($fullNodeUrl);
        $this->solidityNode = new NodeClient($solidityNodeUrl);
        $this->eventNode    = new NodeClient($eventNodeUrl);
    }

    public function getNextMaintenanceTime()
    {
        return $this->fullNode->get('/wallet/getnextmaintenancetime');
    }

    public function timeUntilNextVoteCycle()
    {
        $args = func_get_args();
        return $this->getNextMaintenanceTime(...$args);
    }

    public function broadcastTransaction($tx)
    {
        return $this->fullNode->post('/wallet/broadcasttransaction', $tx);
    }

    public function sendRawTransaction()
    {
        $args = func_get_args();
        return $this->broadcastTransaction(...$args);
    }

    /*
    public function createTransaction($to,$amount,$from){
      $payload = [
        'to_address' => Address::decode($to),
        'owner_address' => Address::decode($from),
        'amount' => $amount
      ];
      return $this->fullNode->post('/wallet/createtransaction',$payload);
    }
    */

    public function sendTrx()
    {
        $args = func_get_args();
        return $this->createTransaction(...$args);
    }

    public function getContractEvents($contractAddress, $since)
    {
        $api     = '/event/contract/' . $contractAddress;
        $payload = ['since' => $since, 'sort' => 'block_timestamp'];
        return $this->eventNode->get($api, $payload);
    }

    public function getTransactionEvents($txid)
    {
        $api = '/event/transaction/' . $txid;
        return $this->eventNode->get($api, []);
    }

    /*
    public function triggerSmartContract($contractAddress,$functionSelector,$parameter,$fromAddress,$feeLimit=1000000000,$callValue=0,$bandwidthLimit=0){
      $payload = [
        'contract_address' => Address::decode($contractAddress),
        'function_selector' => $functionSelector,
        'parameter' => $parameter,
        'owner_address' =>  Address::decode($fromAddress),
        'fee_limit'     =>  $feeLimit,
        'call_value'    =>  $callValue,
        'consume_user_resource_percent' =>  $bandwidthLimit,
      ];
      return $this->fullNode->post('/wallet/triggersmartcontract', $payload);
    }
    */

    public function getAccount($address, $confirmed = true)
    {
        $payload = [
            'address' => Address::decode($address),
        ];
        if ($confirmed) {
            return $this->fullNode->get('/wallet/getaccount', $payload);
        } else {
            return $this->solidityNode->get('/walletsolidity/getaccount', $payload);
        }
    }

    //todo
    public function getBalance($address, $confirmed = true)
    {
        $accountInfo = $this->getAccount($address, $confirmed);
        if (!isset($accountInfo->balance)) {
            throw new Exception('Balance error. Maybe you should send 10 trx to this address to activate it.');
        }
        return $accountInfo->balance;
    }

    public function getUncomfirmedBalance($address)
    {
        return $this->getBalance($address, false);
    }

    public function getAccountNet($address)
    {
        $payload = [
            'address' => Address::decode($address),
        ];
        return $this->fullNode->post('/wallet/getaccountnet', $payload);
    }

    public function ValidateAddress($address)
    {
        $payload = [
            'address' => $address,
        ];
        return $this->fullNode->post('/wallet/validateaddress', $payload);
    }

    public function getBandwidth()
    {
        $args = func_get_args();
        return $this->getAccountNet(...$args);
    }

    public function getAccountResource($address)
    {
        $payload = [
            'address' => Address::decode($address),
        ];
        return $this->fullNode->post('/wallet/getaccountresource', $payload);
    }

    public function getContract($address)
    {
        $payload = [
            'value' => Address::decode($address),
        ];
        return $this->fullNode->get('/wallet/getcontract', $payload);
    }

    public function getChainParameters()
    {
        return $this->fullNode->get('/wallet/getchainparameters', []);
    }

    public function getNodeInfo()
    {
        return $this->fullNode->get('/wallet/nodeinfo', []);
    }

    public function listNodes()
    {
        return $this->fullNode->get('/wallet/listnodes', []);
    }

    //get|post?
    public function getNowBlock($confirmed = true)
    {
        return $this->fullNode->get('/wallet/getnowblock', []);

//        if ($confirmed) {
//            return $this->solidityNode->get('/walletsolidity/getnowblock', []);
//        } else {
//        return $this->fullNode->get('/wallet/getnowblock', []);
//
//        }
    }

    public function getCurrentBlock()
    {
        $args = func_get_args();
        return $this->getNowBlock(...$args);
    }

    public function getBlockById($hash)
    {
        $payload = [
            'value' => $hash,
        ];
        return $this->fullNode->post('/wallet/getblockbyid', $payload);
    }

    public function getBlockByHash()
    {
        $args = func_get_args();
        return $this->getBlockById(...$args);
    }

    public function getBlockByNum($num)
    {
        $payload = [
            'num' => $num,
        ];
        return $this->fullNode->post('/wallet/getblockbynum', $payload);
    }

    public function getBlockByNumber()
    {
        $args = func_get_args();
        return $this->getBlockByNum(...$args);
    }

    public function getBlockByLimitNext($start, $end)
    {
        $payload = [
            'startNum' => $start,
            'endNum'   => $end,
        ];
        return $this->fullNode->get('/wallet/getblockbylimitnext', $payload);
    }

    public function getBlockRange()
    {
        $args = func_get_args();
        return $this->getBlockByLimitNext(...$args);
    }

    public function getTransactionById($txid, $confirmed = true)
    {
        $payload = [
            'value' => $txid,
        ];
        if ($confirmed) {
            return $this->solidityNode->post('/walletsolidity/gettransactionbyid', $payload);
        } else {
            return $this->fullNode->post('/wallet/gettransactionbyid', $payload);
        }
    }

    public function getTransaction()
    {
        $args = func_get_args();
        return $this->gettransactionbyid(...$args);
    }

    public function getConfirmedTransaction()
    {
        $args = func_get_args();
        return $this->getTransactionById(...$args);
    }

    public function getTransactionInfoById($txid, $confirmed = true)
    {
        $payload = [
            'value' => $txid,
        ];
        if ($confirmed) {
            return $this->solidityNode->post('/walletsolidity/gettransactioninfobyid', $payload);
        } else {
            return $this->fullNode->post('/wallet/gettransactioninfobyid', $payload);
        }
    }

    public function getTransactionInfo()
    {
        $args = func_get_args();
        return $this->getTransactionInfoById(...$args);
    }

    public function getUnconfirmedTransactionInfo($txid)
    {
        return $this->getTransactionInfoById($txid, false);
    }


    //all|from|to
    public function getTransactionsByAddress($address, $direction = 'from', $offset = 0, $limit = 30)
    {
        $payload = [
            'account' => [
                'address' => Address::decode($address),
            ],
            'offset'  => $offset,
            'limit'   => $limit,
        ];
        $api     = '/walletextension/gettransactions' . $direction . 'this';
        return $this->solidityNode->post($api, $payload);
    }

    public function getReward($address, $confirmed = true)
    {
        $payload = [
            'address' => Address::decode($address),
        ];
        if ($confirmed) {
            return $this->solidityNode->post('/walletsolidity/getreward', $payload);
        } else {
            return $this->fullNode->post('/wallet/getreward', $payload);
        }
    }

    public function getUnconfirmedReward($address)
    {
        return $this->getReward($address, false);
    }

    public function getApprovedList($tx)
    {
        return $this->fullNode->post('/wallet/getapprovedlist', $tx);
    }

    public function getSignWeight($tx)
    {
        return $this->fullNode->post('/wallet/getsignweight', $tx);
    }

    public function listWitnesses($confirmed = true)
    {
        if ($confirmed) {
            return $this->solidityNode->get('/walletsolidity/listwitnesses', []);
        } else {
            return $this->fullNode->get('/walletsolidity/listwitnesses', []);
        }
    }

    public function listSuperRepresentatives()
    {
        $args = func_get_args();
        return $this->listWitnesses(...$args);
    }

    /*txbuilder*/
    public function createTransaction($to, $amount, $from)
    {
        $payload = [
            'to_address'    => Address::decode($to),
            'owner_address' => Address::decode($from),
            'amount'        => $amount,
        ];
        $ret     = $this->fullNode->post('/wallet/createtransaction', $payload);
        return $ret;
    }

    public function transferAsset($to, $asset, $amount, $from)
    {
        $payload = [
            'to_address'    => Address::decode($to),
            'asset_name'    => bin2hex($asset),
            'amount'        => $amount,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/transferasset', $payload);
    }

    public function sendAsset()
    {
        $args = func_get_args();
        return $this->transferAsset(...$args);
    }

    public function sendToken()
    {
        $args = func_get_args();
        return $this->transferAsset(...$args);
    }

    public function createAssetIssue($name, $abbr, $desc, $url, $supply, $trxRatio, $tokenRatio, $start, $end, $limit, $publicLimit, $frozenAmout, $frozenDays, $precision, $from)
    {
        $payload = [
            'name'                        => bin2hex($name),
            'addr'                        => bin2hex($abbr),
            'total_supply'                => $supply,
            'precision'                   => $precision,
            'trx_num'                     => $trxRatio,
            'num'                         => $tokenRatio,
            'start_time'                  => $start,
            'end_time'                    => $end,
            'description'                 => bin2hex($desc),
            'url'                         => bin2hex($url),
            'free_asset_net_limit'        => $limit,
            'public_free_asset_net_limit' => $publicLimit,
            'frozen_supply'               => [
                'frozen_amount' => $frozenAmout,
                'frozen_days'   => $frozenDays,
            ],
            'owner_address'               => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/createassetissue', $payload);
    }

    public function createToken()
    {
        $args = func_get_args();
        return $this->createAssetIssue(...$args);
    }

    public function createAsset()
    {
        $args = func_get_args();
        return $this->createAssetIssue(...$args);
    }

    public function updateAsset($url, $desc, $limit, $publicLimit, $from)
    {
        $payload = [
            'url'              => bin2hex($url),
            'description'      => bin2hex($desc),
            'new_limit'        => $limit,
            'new_public_limit' => $publicLimit,
            'owner_address'    => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/updateasset', $payload);
    }

    public function updateToken()
    {
        $args = func_get_args();
        return $this->updateAsset(...$args);
    }

    public function participateAssetIssue($to, $asset, $amount, $from)
    {
        $payload = [
            'to_address'    => Address::decode($to),
            'asset_name'    => bin2hex($asset),
            'amount'        => $amount,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/participateassetissue', $payload);
    }

    public function purchaseAsset()
    {
        $args = func_get_args();
        return $this->participateAssetIssue(...$args);
    }

    public function purchaseToken()
    {
        $args = func_get_args();
        return $this->participateAssetIssue(...$args);
    }

    public function getAssetIssueById($token, $confirmed = true)
    {
        $payload = [
            'value' => $token,
        ];
        if ($confirmed) {
            return $this->solidityNode->post('/walletsolidity/getassetissuebyid', $payload);
        } else {
            return $this->fullNode->post('/wallet/getassetissuebyid', $payload);
        }
    }

    public function getTokenById()
    {
        $args = func_get_args();
        return $this->getAssetIssueById(...$args);
    }

    public function getAssetIssueByName($token)
    {
        $payload = [
            'value' => bin2hex($token),
        ];
        return $this->fullNode->post('/wallet/getassetissuebyname', $payload);
    }

    public function getTokenFromId()
    {
        $args = func_get_args();
        return $this->getAssetIssueByName(...$args);
    }

    public function getAssetIssueList()
    {
        return $this->fullNode->get('/wallet/getassetissuelist', []);
    }

    public function listTokens()
    {
        $args = func_get_args();
        return $this->getAssetIssueList(...$args);
    }

    public function getAssetIssueListByName($token)
    {
        $payload = [
            'value' => bin2hex($token),
        ];
        return $this->fullNode->post('/wallet/getassetissuelistbyname', $payload);
    }

    public function getTokenListByName()
    {
        $args = func_get_args();
        return $this->getAssetIssueListByName(...$args);
    }

    public function getAssetIssueByAccount($address)
    {
        $payload = [
            'address' => Address::decode($address),
        ];
        return $this->fullNode->post('/wallet/getassetissuebyaccount', $payload);
    }

    public function getTokenIssuedByAddress()
    {
        $args = func_get_args();
        return $this->getAssetIssueByAccount(...$args);
    }

    public function freezeBalance($balance, $duration, $type, $from, $receiver = null)
    {
        $payload = [
            'freeze_balance'   => $balance,
            'freeze_duration'  => $duration,
            'resource'         => $type,
            'owner_address'    => Address::decode($from),
            'receiver_address' => Address::decode($receiver),
        ];
        return $this->fullNode->post('/wallet/freezebalance', $payload);
    }

    public function unfreezeBalance($type, $from, $receiver = null)
    {
        $payload = [
            'resource'      => $type,
            'owner_address' => Address::decode($from),
            'receiver'      => Address::decode($receiver),
        ];
        return $this->fullNode->post('/wallet/unfreezebalance', $payload);
    }

    public function withdrawBalance($address)
    {
        $payload = [
            'owner_address' => Address::decode($address),
        ];
        return $this->fullNode->post('/wallet/withdrawbalance', $payload);
    }

    public function withdrawBlockRewards()
    {
        $args = func_get_args();
        return $this->withdrawBalance(...$args);
    }

    public function createWitness($address, $url)
    {
        $payload = [
            'owner_address' => Address::decode($address),
            'url'           => bin2hex($url),
        ];
        return $this->fullNode->post('/wallet/createwitness', $payload);
    }

    public function applyForSR()
    {
        $args = func_get_args();
        return $this->createWitness(...$args);
    }

    public function getBrokerage($address, $confirmed = true)
    {
        $payload = [
            'address' => Address::decode($address),
        ];
        if ($confirmed) {
            return $this->solidityNode->post('/walletsolidity/getbrokerage', $payload);
        } else {
            return $this->fullNode->post('/wallet/getbrokerage', $payload);
        }
    }

    public function getUncomfirmedBrokerage($address)
    {
        return $this->getBrokerage($address, false);
    }

    public function voteWitnessAccount($address, $votes)
    {
        $payload = [
            'owner_address' => Address::decode($address),
            'votes'         => $votes,
        ];
        return $this->fullNode->post('/wallet/votewitnessaccount', $payload);
    }

    public function vote()
    {
        $args = func_get_args();
        return $this->voteWitnessAccount(...$args);
    }

    //trc20
    public function deployContract($abi, $bytecode, $parameter, $name, $value, $from)
    {
        $payload = [
            'abi'                           => $abi,
            'bytecode'                      => $bytecode,
            'parameter'                     => $parameter,
            'name'                          => $name,
            'call_value'                    => $value,
            'owner_address'                 => Address::decode($from),
            'fee_limit'                     => 1000000000,
            'origin_energy_limit'           => 10000000,
            'consume_user_resource_percent' => 100,
        ];
        return $this->fullNode->post('/wallet/deploycontract', $payload);
    }

    public function createSmartContract()
    {
        $args = func_get_args();
        return $this->deployContract(...$args);
    }

    public function triggerSmartContract($contract, $function, $parameter, $value, $from)
    {
        $payload = [
            'contract_address'  => Address::decode($contract),
            'function_selector' => $function,
            'parameter'         => $parameter,
            'call_value'        => $value,
            'owner_address'     => Address::decode($from),
            'fee_limit'         => 1000000000,
        ];
        return $this->fullNode->post('/wallet/triggersmartcontract', $payload);
    }

    public function triggerConstantSmartContract($contract, $function, $parameter, $value, $from, $confirmed = true)
    {
        $payload = [
            'contract_address'  => Address::decode($contract),
            'function_selector' => $function,
            'parameter'         => $parameter,
            'call_value'        => $value,
            'owner_address'     => Address::decode($from),
            'fee_limit'         => 1000000000,
        ];
        if ($confirmed) {
            return $this->solidityNode->post('/walletsolidity/triggerconstantsmartcontract', $payload);
        } else {
            return $this->fullNode->post('/wallet/triggerconstantsmartcontract', $payload);
        }
    }

    public function clearAbi($contract, $from)
    {
        $payload = [
            'contract_address' => Address::decode($contract),
            'owner_address'    => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/clearabi', $payload);
    }

    public function updateSetting($contract, $userPercent, $from)
    {
        $payload = [
            'contract_address'              => Address::decode($contract),
            'consume_user_resource_percent' => $userPercent,
            'owner_address'                 => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/updatesetting', $payload);
    }

    public function updateEnergyLimit($contract, $limit, $from)
    {
        $payload = [
            'contract_address'    => Address::decode($contract),
            'origin_energy_limit' => $limit,
            'owner_address'       => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/updateenergylimit', $payload);
    }

    public function updateBrokerage($brokerage, $from)
    {
        $payload = [
            'brokerage'     => $brokerage,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/updatebrokerage', $payload);
    }

    public function updateAccount($name, $from)
    {
        $payload = [
            'account_name'  => bin2hex($name),
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/updateaccount', $payload);
    }

    public function accountPermissionUpdate($ownerPermits, $witnessPermits, $activePermits, $from)
    {
        $payload = [
            'owner'         => $ownerPermits,
            'witness'       => $witnessPermits,
            'active'        => $activePermits,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/accountpermissionupdate', $payload);
    }

    public function setAccountId($id, $from)
    {
        $payload = [
            'account_id'    => $id,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/setaccountid', $payload);
    }

    //dex
    public function proposalCreate($parameters, $from)
    {
        $payload = [
            'parameters'    => $parameters,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/proposalcreate', $payload);
    }

    public function createProposal()
    {
        $args = func_get_args();
        return $this->proposalCreate(...$args);
    }

    public function listProposals()
    {
        return $this->fullNode->post('/wallet/listproposals', []);
    }

    public function proposalDelete($id, $from)
    {
        $payload = [
            'proposal_id'   => $id,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/proposaldelete', $payload);
    }

    public function deleteProposal()
    {
        $args = func_get_args();
        return $this->proposalDelete(...$args);
    }

    public function proposalApprove($id, $from)
    {
        $payload = [
            'proposal_id'   => $id,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/proposalapprove', $payload);
    }

    public function voteProposal()
    {
        $args = func_get_args();
        return $this->proposalApprove(...$args);
    }

    public function exchangeCreate($token1, $balance1, $token2, $balance2, $from)
    {
        $payload = [
            'first_token_id'       => bin2hex($token1),
            'first_token_balance'  => $balance1,
            'second_token_id'      => bin2hex($token2),
            'second_token_balance' => $balance2,
            'owner_address'        => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/exchangecreate', $payload);
    }

    public function listExchanges()
    {
        return $this->fullNode->post('/wallet/listexchanges', []);
    }

    public function getPaginatedExchangeList($offset = 0, $limit = 30)
    {
        $payload = [
            'offset' => $offset,
            'limit'  => $limit,
        ];
        return $this->fullNode->post('/wallet/getpaginatedexchangelist', $payload);
    }

    public function listExchangePaginated()
    {
        $args = func_get_args();
        return $this->getPaginatedExchangeList(...$args);
    }

    public function exchangeInject($exchange, $token, $quant, $from)
    {
        $payload = [
            'exchange_id'   => $exchange,
            'token_id'      => bin2hex($token),
            'quant'         => $quant,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/exchangeinject', $payload);
    }

    public function injectExchangeToken()
    {
        $args = func_get_args();
        return $this->exchangeInject(...$args);
    }

    public function exchangeWithdraw($exchange, $token, $quant, $from)
    {
        $payload = [
            'exchange_id'   => $exchange,
            'token_id'      => bin2hex($token),
            'quant'         => $quant,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/exchangewithdraw', $payload);
    }

    public function withdrawExchangeTokens()
    {
        $args = func_get_args();
        return $this->exchangeWithdraw(...$args);
    }

    public function exchangeTransaction($exchange, $token, $quant, $expected, $from)
    {
        $payload = [
            'exchange_id'   => $exchange,
            'token_id'      => bin2hex($token),
            'quant'         => $quant,
            'expected'      => $expected,
            'owner_address' => Address::decode($from),
        ];
        return $this->fullNode->post('/wallet/exchangetransaction', $payload);
    }

    public function getExchangeById($id)
    {
        $payload = [
            'id' => $id,
        ];
        return $this->fullNode->post('/wallet/getexchangebyid', $payload);
    }

    public function tradeExchangeTokens()
    {
        $args = func_get_args();
        return $this->exchangeTransaction(...$args);
    }
}

