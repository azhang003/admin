<?php

namespace app\common\command;


use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use think\facade\Db;

class Generate extends Command
{
    protected function configure()
    {
        $this->setName('Generate')->setDescription("计划任务 设置场次");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln(date('Y-m-d h:i:s') . '任务开始!');
        /*** 这里写计划任务列表集 START ***/
        var_dump(date('Y-m-d h:i:s'));
        $this->checkBlock();
        /*** 这里写计划任务列表集sd END ***/
        $output->writeln(date('Y-m-d h:i:s') . '任务结束!');
    }

    public function checkBlock()
    {
//        return
//        try {
//            /** 检查是否生成订单 **/
//            $is_generate = GameEventList::where([['title', 'like', '%' . date('Ymd', time()) . '%']])->find();
//            if ($is_generate) {
//                throw new Exception('今日已生成订单!');
//            }
//
//            /** 备份旧表 **/
//            $sql = "create table ea_game_event_list_" . date('Ymd', time())." select * from ea_game_event_list";
//            $back_bool = Db::execute($sql);
//            if (!$back_bool){
//                throw new Exception('备份失败!>>' . $back_bool);
//            }
//            /** 将当日订单插入总表 **/
//            $sql ="insert into ea_game_event_list_old select * from ea_game_event_list";
//            $insert_bool = Db::execute($sql);
//            if (!$insert_bool){
//                throw new Exception('插入失败!' . $insert_bool);
//            }
//            /** 清除昨日备份表 **/
//            $sql = "drop table ea_game_event_list_" . date('Ymd', time() - 86400);
//            Db::execute($sql);
//
//            $is_delete = GameEventList::whereNotLike('title', '%' . date('Ymd', time()) . '%')->delete();
//            if (!$is_delete) {
//                throw new Exception('清空旧表失败!' . $is_generate);
//            }
//        }catch (\Exception $e){
//            var_dump($e->getMessage());
//            var_dump($e->getTrace());
//            return;
//        }
        /** 添加新赛事 **/
        $currency = GameEventCurrency::CurreryAll();
        foreach ($currency as $value) {
            $insertAll = [];
            //开启事务操作
            /**
             * 1min
             */
            for ($j = 0001; $j <= 24 * 60; $j++) {
                $insert                = [];
                $insert['type']        = "1m";
                $insert['cid']         = $value['id'];
                $insert['title']       = date('Ymd', time()) . 'B' . $j;
                $insert['begin_time']  = strtotime(date('Y-m-d', time())) + ($j - 1) * 60;
                $insert['end_time']    = strtotime(date('Y-m-d', time())) + $j * 60;
                $insert['create_time'] = time();
                $insert['Identify']    = $insert['title'] . '_' . $insert['cid'] . '_' . $insert['type'];
                $insert['seal_time']   = $insert['end_time'] - 15;
                $insertAll[]           = $insert;
            }
            /**
             * 5min
             */
            for ($j = 0001; $j <= 24 * 12; $j++) {
                $insert                = [];
                $insert['type']        = "5m";
                $insert['cid']         = $value['id'];
                $insert['title']       = date('Ymd', time()) . 'C' . $j;
                $insert['begin_time']  = strtotime(date('Y-m-d', time())) + ($j - 1) * 5 * 60;
                $insert['end_time']    = strtotime(date('Y-m-d', time())) + $j * 5 * 60;
                $insert['create_time'] = time();
                $insert['Identify']    = $insert['title'] . '_' . $insert['cid'] . '_' . $insert['type'];
                $insert['seal_time']   = $insert['end_time'] - 30;
                $insertAll[]           = $insert;
            }
            /**
             * 15min
             */
            for ($j = 0001; $j <= 24 * 4; $j++) {
                $insert                = [];
                $insert['type']        = "15m";
                $insert['cid']         = $value['id'];
                $insert['title']       = date('Ymd', time()) . 'D' . $j;
                $insert['begin_time']  = strtotime(date('Y-m-d', time())) + ($j - 1) * 15 * 60;
                $insert['end_time']    = strtotime(date('Y-m-d', time())) + $j * 15 * 60;
                $insert['create_time'] = time();
                $insert['Identify']    = $insert['title'] . '_' . $insert['cid'] . '_' . $insert['type'];
                $insert['seal_time']   = $insert['end_time'] - 30;
                $insertAll[]           = $insert;
            }
            /**
             * 30min
             */
            for ($j = 0001; $j <= 24 * 2; $j++) {
                $insert                = [];
                $insert['type']        = "30m";
                $insert['cid']         = $value['id'];
                $insert['title']       = date('Ymd', time()) . 'E' . $j;
                $insert['begin_time']  = strtotime(date('Y-m-d', time())) + ($j - 1) * 30 * 60;
                $insert['end_time']    = strtotime(date('Y-m-d', time())) + $j * 30 * 60;
                $insert['create_time'] = time();
                $insert['Identify']    = $insert['title'] . '_' . $insert['cid'] . '_' . $insert['type'];
                $insert['seal_time']   = $insert['end_time'] - 30;
                $insertAll[]           = $insert;
            }
            /**
             * 1h
             */
            for ($j = 0001; $j <= 24; $j++) {
                $insert                = [];
                $insert['type']        = "1h";
                $insert['cid']         = $value['id'];
                $insert['title']       = date('Ymd', time()) . 'F' . $j;
                $insert['begin_time']  = strtotime(date('Y-m-d', time())) + ($j - 1) * 60 * 60;
                $insert['end_time']    = strtotime(date('Y-m-d', time())) + $j * 60 * 60;
                $insert['create_time'] = time();
                $insert['Identify']    = $insert['title'] . '_' . $insert['cid'] . '_' . $insert['type'];
                $insert['seal_time']   = $insert['end_time'] - 60;
                $insertAll[]           = $insert;
            }
            $insert                = [];
            $insert['type']        = "1d";
            $insert['cid']         = $value['id'];
            $insert['title']       = date('Ymd', time()) . 'G001';
            $insert['begin_time']  = strtotime(date('Y-m-d', time()));
            $insert['end_time']    = strtotime(date('Y-m-d', time())) + 86400;
            $insert['create_time'] = time();
            $insert['Identify']    = $insert['title'] . '_' . $insert['cid'] . '_' . $insert['type'];
            $insert['seal_time']   = $insert['end_time'] - 600;
            $insertAll[]           = $insert;
            try {
                foreach ($insertAll as $item) {
                    $list = (new GameEventList())
                        ->where([
                            ['cid', '=', $item['cid']],
                            ['title', '=', $item['title']],
                            ['type', '=', $item['type']],
                        ])->find();
                    if (empty($list)) {
                        $bool = (new GameEventList())->save($item);
                    }
                }
            } catch (Exception $exception) {
                var_dump($exception->getMessage());
            }
//            $bool = (new GameEventList())->insertAll($insertAll);

        }
    }
}
