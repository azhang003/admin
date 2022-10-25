<?php
declare (strict_types=1);

namespace app\merchant\controller;

use app\common\controller\member\Redis;
use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use app\common\model\SystemConfig;
use app\merchant\BaseMerchant;
use app\merchant\middleware\jwtVerification;
use Kaadon\CapCha\capcha;
use think\Exception;
use think\Model;

class Game extends BaseMerchant
{
    protected $middleware
        = [
            jwtVerification::class => [
                'except' => []
            ]
        ];

    public function index()
    {

    }

    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update_open_new()
    {
        if (in_array($this->request->merchant->id,explode('|',get_config('game','game','open')))){
            $list = GameEventList::where([['id','=',$this->request->param('id/d',null)],['end_time','>',time()]])->find();
            if ($list->open == "0"){
                $config = SystemConfig::where([['group','=','game'],['gname','=','game']])->find()->toArray();
                $value = json_decode($config['value'],true);
                $value['totals'] = $value['totals']+1;
                (new SystemConfig())->setUpdate('game', 'game', $value);
            }
            GameEventList::where([['id','=',$this->request->param('id/d',null)],['end_time','>',time()]])->update([
                'open'=>$this->request->param('type/d',null),
                'hard'=>1
            ]);
        }
        return success("修改成功");
    }


    /**
     * 重构版
     * @return array|string|string[]|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update_open()
    {
        try {
            if (in_array($this->request->merchant->id,explode('|',get_config('game','game','open')))){
                /** $type  控奖 0:取消控奖 1:控涨 2控跌 **/
                $type = $this->request->param('type/d',0);
                $list_id =  $this->request->param('id/d',null);
                if (!$list_id){
                    return error('无ID!');
                }
                $list = GameEventList::where([['id','=',$list_id],['seal_time','>',time()-10]])->find();
                if (!$list){
                    return error('订单不存在或者已封盘!');
                }
                if ($list->open == $type){
                    return error('请勿重复点击!');
                }
                if ($list->open == $type){
                    return error('请勿重复控奖!');
                }
                if ($list->hard == 0){
                    $config = SystemConfig::where([['group','=','game'],['gname','=','game']])->find()->toArray();
                    $value = json_decode($config['value'],true);
                    $value['totals'] = $value['totals']+1;
                    (new SystemConfig())->setUpdate('game', 'game', $value);
                }
                $bool = $list->save([
                    'hard'=>1,
                    "iscontrol" => $type
                ]);
                /** 添加控制计算器 **/
                if ($bool){
                    $title = GameEventCurrency::CurreryAll()[$list->cid]['title'];
                    if ($type != 0){
                        /** 写入当前期 **/
                        Redis::redis()->set('nowEventList:' . $list->id,
                            json_encode($list->toArray()),
                            $list->end_time - time()
                        );
                        /** 写入控制系统 **/
                        Redis::redis()->set('iscontrol:' . strtolower(str_replace('/','',$title)) . ':'. $list->type,
                            json_encode([
                                'id' => $list->id,
                                'type' => $type,// 控涨跌
                                'endtime' => $list->begin_time,
                                'begintime' => $list->end_time,
                                'hard' => 1, // 1 为手动,2为吞吐
                                'remark' => 0, // 0为未开奖  大于0,则需要延时K线回升
                                'title' => $title, // 币种名称对
                                'cycle' => $list->type, // 时间累 1m,5m
                            ]),
                            $list->end_time + 5 - time()
                        );
                    }else{
                        Redis::redis()->set('iscontrol:' . strtolower(str_replace('/','',$title)) . ':'. $list->type,
                            json_encode([
                                'id' => $list->id,
                                'type' => $type,// 控涨跌
                                'endtime' => $list->begin_time,
                                'begintime' => $list->end_time,
                                'hard' => 2, // 1 为手动,2为吞吐
                                'remark' => 0, // 0为未开奖  大于0,则需要延时K线回升
                                'title' => $title, // 币种名称对
                                'cycle' => $list->type, // 时间累 1m,5m
                            ]),
                            $list->end_time + 5 - time()
                        );
                    }
                }


            }
        }catch (Exception $exception){
            var_dump($exception->getMessage());
            var_dump($exception->getTrace());
        }

        return success("修改成功");
    }
}
