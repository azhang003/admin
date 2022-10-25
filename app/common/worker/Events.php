<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace app\common\worker;

use GatewayWorker\Lib\Gateway;
use think\App;
use think\worker\Application;
use Workerman\Worker;


/**
 * Worker 命令行服务类
 */
class Events
{


    /**
     * onWorkerStart 事件回调
     * 当businessWorker进程启动时触发。每个进程生命周期内都只会触发一次
     *
     * @access public
     * @param \Workerman\Worker $businessWorker
     * @return void
     */
    public static function onWorkerStart(Worker $businessWorker)
    {
        $app = new Application();
        $app->initialize();
    }

    /**
     * onConnect 事件回调
     * 当客户端连接上gateway进程时(TCP三次握手完毕时)触发
     *
     * @access public
     * @param int $client_id
     * @return void
     */
    public static function onConnect($client_id)
    {
        $data                = [];
        $data['type']        = 'connected';
        $data['client_id']   = $client_id;
        $data['client_time'] = time();
        Gateway::sendToCurrentClient(json_encode($data, true));
    }

    /**
     * onWebSocketConnect 事件回调
     * 当客户端连接上gateway完成websocket握手时触发
     *
     * @param integer $client_id 断开连接的客户端client_id
     * @param mixed $data
     * @return void
     */
    public static function onWebSocketConnect($client_id, $data)
    {

    }

    /**
     * onMessage 事件回调
     * 当客户端发来数据(Gateway进程收到数据)后触发
     *
     * @access public
     * @param int $client_id
     * @param mixed $data
     * @return void
     */
    public static function onMessage($client_id, $data)
    {

        if (!is_string($data)) {
            Gateway::sendToClient($client_id,json_encode([
                'type' => 'unknown message',
                'content' => 'Unable to identify your message'
                ]));
            return;
        }

        $meta = json_decode($data, true);

        if (!is_array($meta)) {
            Gateway::sendToClient($client_id,json_encode([
                'type' => 'unknown message',
                'content' => 'Non-platform acceptance message type'
            ]));
            return;
        }
        if (!array_key_exists('type', $meta)) {
            Gateway::sendToClient($client_id,json_encode([
                'type' => 'unknown message',
                'content' => 'Non-platform acceptance message type'
            ]));
            return;
        }
//        if (!in_array($meta['type'], ['login', 'tocontent','leave','gameleave', 'game', 'ticker','tickerleave', 'history', 'toread','pong'])) {
        if (!in_array($meta['type'], ['login', 'tocontent','leave', 'ticker','tickerleave', 'history', 'toread','pong'])) {
            Gateway::sendToClient($client_id,json_encode([
                'type' => 'unknown message',
                'content' => 'Non-platform acceptance message type'
            ]));
            return;
        }
        // 启动事务
        try {
            $fun = new WorkerMessage($client_id);
            if ($meta['type'] == 'pong'){
                call_user_func(array($fun, 'login'), $meta);
            }else{
                call_user_func(array($fun, $meta['type']), $meta);
            }
        } catch (\Exception $e) {
            Gateway::sendToClient($client_id,json_encode($e->getTrace(),true));
        }

    }

    /**
     * onClose 事件回调 当用户断开连接时触发的方法
     *
     * @param integer $client_id 断开连接的客户端client_id
     * @return void
     */
    public static function onClose($client_id)
    {

        $fun = new WorkerMessage($client_id);
        call_user_func(array($fun, 'close'), $client_id);
    }

    /**
     * onWorkerStop 事件回调
     * 当businessWorker进程退出时触发。每个进程生命周期内都只会触发一次。
     *
     * @param \Workerman\Worker $businessWorker
     * @return void
     */
    public static function onWorkerStop(Worker $businessWorker)
    {
        echo "WorkerStop\n";
    }


}
