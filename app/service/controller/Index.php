<?php
declare (strict_types=1);

namespace app\service\controller;

use app\common\controller\GameController;
use app\common\controller\member\Redis;
use app\common\controller\member\Wallet;
use app\common\model\ArticleList;
use app\common\model\GameEventBet;
use app\common\model\GameEventCurrency;
use app\common\model\GameEventList;
use app\common\model\MemberAccount;
use app\common\model\MemberDashboard;
use app\common\model\MemberLogin;
use app\common\model\MemberProfile;
use app\common\model\MemberRecord;
use app\common\model\MemberWallet;
use app\common\model\MemberWithdrawOrder;
use app\common\model\SystemPayment;
use app\common\model\SystemSms;
use app\common\service\RedisLock;
use app\common\service\Uuids;
use app\common\validate\UserValidate;
use app\job\queueGame;
use app\job\queueUpdate;
use ipinfo\ipinfo\IPinfo;
use KaadonAdmin\upload\Uploadfile;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Lang;
use think\facade\Queue;
use think\Model;
use think\Request;

class Index
{
    public function ipInfo()
    {
        //\request()->header('user-agent')
        $b = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36';
        return success(sha1($_SERVER['HTTP_USER_AGENT']) == sha1($b));
    }

    function ip($type = 0, $adv = false)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL) return $ip[$type];
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) unset($arr[$pos]);
                $ip = trim($arr[0]);
                var_dump("测试1>>:" . $ip);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
                var_dump("测试2>>:" . $ip);
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
                var_dump("测试3>>:" . $ip);
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            var_dump("测试4>>:" . $ip);
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long((string)$ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
    /**
     * @return array|string|string[]|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateTeamcommission($mid)
    {
        if (empty($mid)) {
            return error('会员ID不存在!');
        }
        $item = MemberAccount::field('id,agent_line')->where('id', $mid)->find();
        if (!empty($item)) {
            queue(queueUpdate::class, [
                'task' => "updateCommission",
                'data' => [
                    "agent_line" => agent_line_array($item->agent_line),
                    "mid"        => $item->id
                ],
            ], 0, "updateCommission");
            return success('执行成功!');
        } else {
            return error('用户不存在!');
        }

    }

    /**
     * 计算佣金
     * @return array|string|string[]|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function commission()
    {
        $mid = request()->param('mid', null);
        if (empty($mid)) {
            return error('会员ID不存在!');
        }
        $account = MemberAccount::where('id', $mid)->find();
        if (empty($account)) {
            return error('会员不存在!');
        }
        $eth = $account->wallet->eth;
        $sql = "SELECT id,inviter_line FROM `coco`.`ea_member_account` WHERE `inviter_line` LIKE '%" . $mid . "%'";
        $members = Db::query($sql);
        $data = [
            "1" => [],
            "2" => [],
            "3" => [],
            "0" => []
        ];
        $share = explode("|", get_config('site', 'setting', 'share'));
        $super = get_config('site', 'setting', 'super');
        foreach ($members as $member) {
            $inviter = agent_line_array($member['inviter_line']);
            foreach ($inviter as $key => $item) {
                if ($item == $mid) {
                    if ($key < 3) {
                        $data[$key + 1][] = $member['id'];
                    } else {
                        $data["0"][] = $member['id'];
                    }
                }
            }
        }
        $commission = [];
        foreach ($data as $key => $item) {
            $money = GameEventBet::whereIn("mid", $item)->where('type', 0)->whereLike('title', '%' . date('Ymd') . '%')->sum('money');

            if ($key > 0) {
                $commission[$key] = money_format_bet($money * $share[$key - 1]);
            } else {
                $commission[$key] = money_format_bet($money * $super);
            }
        }

        $distributeCommission = [];
        $sql = "SELECT SUM(cast(now as decimal(30,12))) AS money,team FROM `coco`.`ea_member_record` WHERE `currency` = '4' AND `mid` = '" . $mid . "' AND business = 9 AND create_time > " . strtotime(date('Y-m-d')) . "  GROUP BY team;";
        $Commission = Db::query($sql);
        foreach ($Commission as $item) {
            $distributeCommission[$item['team']] = $item['money'];
        }
        $receiveToday = [];
        $sql = "SELECT SUM(cast(now as decimal(30,12))) AS money,team FROM `coco`.`ea_member_record` WHERE `currency` = '4' AND `mid` = '" . $mid . "' AND business = 13 AND create_time > " . strtotime(date('Y-m-d')) . "  GROUP BY team;";
        $Commission = Db::query($sql);
        foreach ($Commission as $item) {
            $receiveToday[$item['team']] = abs($item['money']);
        }
        $data = [
            '实际派发' => $distributeCommission,
            "实时计算" => $commission,
            '今日领取' => $receiveToday,
            '钱包余额' => $eth
        ];
        return success($data);
    }

    /**
     * 验证开奖
     * @return \think\response\Json
     */
    public function CheckData()
    {

        $sql = "SELECT SUM(money) as '投注金额',count(*) as '投注注数' FROM `coco`.`ea_game_event_bet` WHERE `type` = '0' AND title like '%" . date("Ymd") . "%'";
        $xz = Db::query($sql);
//        $sql = "SELECT count(mid) as '投注人数' FROM `coco`.`ea_game_event_bet` WHERE `type` = '0' AND title like '%" .date("Ymd") ."%' GROUP BY mid" ;
//        $rs = Db::query($sql);
        $sql = "SELECT SUM(money) * 1.95 as '派奖金额',count(*) as '派奖注数' FROM `coco`.`ea_game_event_bet` WHERE `type` = '0' AND `is_ok` = 1 AND title like '%" . date("Ymd") . "%'";
        $pj = Db::query($sql);
        $sql = "SELECT SUM(money) as '待派奖金额',count(*) as '待派奖注数' FROM `coco`.`ea_game_event_bet` WHERE `type` = '0' AND `is_ok` = 0 AND title like '%" . date("Ymd") . "%'";
        $dpj = Db::query($sql);
        $sql = "SELECT SUM(now) as  money,business,count(*) as c FROM `coco`.`ea_member_record` WHERE `currency` = '1' AND `business` IN (3,4) AND create_time > " . strtotime(date("Y-m-d")) . " GROUP BY business";
        $order = Db::query($sql);
        $orderData = [];
        foreach ($order as $item) {
            $orderData[$item['business'] == 3 ? '投注' : '派奖'] = [
                ($item['business'] == 3 ? '投注' : '派奖') . '注数' => $item['c'],
                ($item['business'] == 3 ? '投注' : '派奖') . '金额' => $item['money']
            ];
        }
        $data = [
            '日期'     => date("Y-m-d"),
            '根据订单计算' => [
                '投注会员数' => '',
                '总投注'   => $xz,
                '总派奖'   => $pj,
                '待派奖'   => $dpj
            ],
            "根据流水计算" => $orderData
        ];

        return success($data);
    }

    public function capcha()
    {
        try {
            /*执行主体*/
            return success(capcha_create());

        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }

    /**
     * 上传文件
     */
    public function upload()
    {
        if (!request()->isPost()) {
            return error("当前请求不合法1！");
        }

        $type = request()->param('type/s', null);
        if (empty($type) || !in_array($type, ['service', 'member', 'merchant'])) {
            $type = 'service';
        }
        $data = [
            'file' => request()->file('file'),
        ];
        $uploadConfig = get_config('upload', 'default');
        empty($data['upload_type']) && $data['upload_type'] = $uploadConfig['upload_type'];
        $rule = [
            'file|文件' => "require|file|fileExt:jpg,jpeg,png,gif|fileSize:{$uploadConfig['upload_allow_size']}",
        ];
        try {
            validate($data, $rule);
            $upload = Uploadfile::instance()
                ->setUploadType($data['upload_type'])
                ->setUploadConfig($uploadConfig)
                ->setApiClassName($type)
                ->setFile($data['file'])
                ->isSave(false)
                ->save();
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if ($upload['save'] == true) {
            return success(['url' => $upload['url']], $upload['msg']);
        } else {
            return error($upload['msg']);
        }
    }

    /**
     * 下载远程图片保存到本地
     * @access public
     * @return array|string
     * @params string $url 远程图片地址
     * @params string $save_dir 需要保存的地址
     * @params string $filename 保存文件名
     * @since 1.0
     * @author      lxhui<772932587@qq.com>
     */
    public static function downloads($url, $save_dir = './upload/loan/', $filename = '')
    {
        if (trim($save_dir) == '')
            $save_dir = './';
        if (trim($filename) == '') {//保存文件名
            $allowExt = array('.gif', '.jpg', '.jpeg', '.png', '.bmp');
            $ext = strrchr($url, '.');
            if (!in_array($ext, $allowExt))
                return array('file_name' => '', 'save_path' => '', 'error' => 3);
            $filename = time() . $ext;
        }
        if (0 != strrpos($save_dir, '/'))
            $save_dir .= '/';
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true))
            return array('file_name' => '', 'save_path' => '', 'error' => 5);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        $filename = pathinfo($url, PATHINFO_BASENAME);
        $resource = fopen($save_dir . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);
        unset($file, $url);
        $name = str_replace(array('//', './'), array('/', '/'), $save_dir . $filename);
        return ($name);
    }
    public function iplist()
    {
        $account = [657727, 369240, 500167, 727483, 462744, 273776, 131575];
        foreach ($account as $value){
            $list[$value] = MemberLogin::where('mid',$value)->order('id desc')->limit(100)->select()->toArray();
        }
        var_dump($list);
    }

    public function asaa(Request $request)
    {
        $list = GameEventList::where([
            ['title', 'like', '%20220812%'],
            ['type', '=', $request->param('type', '1m')],
            ['open_profile', '<>', '0']
        ])->field('id,title,open_profile')->order('open_profile asc')->select()->toArray();
        foreach ($list as $item) {
            echo "<table  border='1' cellspacing='0' cellpadding='0' border-collapse:collapse>
                <tr>
                <td>" . $item['id'] . "</td>
                <td>" . $item['title'] . "</td>
                <td>" . $item['open_profile'] . "</td>
                </tr>
            </table>";
        }
        var_dump($list);
    }
    public function iplistss()
    {
        $account = [657727, 369240, 500167, 727483, 462744, 273776, 131575];
        foreach ($account as $value){
            $list[$value] = MemberLogin::where('mid',$value)->order('id desc')->limit(100)->select()->toArray();
        }
        var_dump($list);
    }

    public function withdraw_address()
    {
        $list = MemberDashboard::where(1)->whereNull('withdraw_address')->order('id desc')->select();
        foreach ($list as $item) {
            $address = MemberWithdrawOrder::where([
                ['mid', '=', $item->mid],
                ['examine', '=', 1],
            ])->whereNotNull('address')->value('address');
            if (!empty($address)) {
                (new MemberDashboard)->where('mid', $item->mid)->update([
                    'withdraw_address' => $address
                ]);
            }
        }
    }

    /*
     *
     */
    public function bet_game()
    {
        for ($i = 1; $i < 100; $i++) {
            $dataArr['money'] = 1;
            $dataArr['list_id'] = \request()->param('list_id/d');
            $dataArr['bet'] = 1;
            $dataArr['type'] = 0;
            $data = json_encode($dataArr);
//            $token = 'kaadon eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJrYWFkb24iLCJpYXQiOjE2NjEyMjQyNzMsImV4cCI6MTY2MTgyOTA3MywiZGF0YSI6eyJ0eXBlIjoiY3VzdG9tZXIiLCJpZCI6NDUwMDQ5LCJhZ2VudF9saW5lIjoiMHwyfDR8IiwidXVpZCI6IkgzODEyOSIsIm1pZCI6NDUwMDQ5LCJpZGVudGlmaWNhdGlvbiI6IkgzODEyOSIsImlwIjoiNjEuMTExLjEyOS4xNzMifX0.mgaU4NNNvqhPNUWmRMNsqaOULj3gluouL3jEaB85fd3fr7LOrbALTm22RbuL9Iol9FwVuSchJQXTMRjOZn4bX4WHgJSNhp29u19jbrMvhYvt99fVYcJJx1S6XAps4eWMmXs_S6jkDElGx5srdrc0f0iz326OBWeXCcCuxIRJ89A';
            $token = \request()->param('token/s');
            $url = 'http://service.genesiskz.com/member/game/bet';
            $headers = array('Content-Type:application/json;charset=UTF-8', "Authorization:$token",);
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
        }
    }

    public function ads()
    {
        $list = MemberAccount::where([
            ['agent_line', 'not like', '0|2|4|%'],
            ['agent_line', 'not like', '0|2|9|%'],
        ])->field('id')->select();
        foreach ($list as $value){
            $value->index;
        }
        var_dump($list->toArray());
    }

    /**
     *
     */
    public function curr_list()
    {
        $redis = \app\common\controller\member\Redis::redis();
        //第一次取库存,先用保存到缓存中
        $stock = $redis->keys("ticker:*");
//        $stock = $redis->get("ticker:apeusdt");
//        var_dump($stock);exit();
        foreach ($stock as $item) {
            $aaa = $redis->get($item);
            $bbb = json_decode($aaa, true);
            $data[$bbb['currency']] = $aaa;
        }
//        $stock = $redis->get("ticker:apeusdt");
        return success($data);
    }


    /***
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 调整team数据
     */
    public function asss()
    {
        $data = GameEventList::where(1)->whereNull('seal_time')->order('id desc')->limit(9999)->select();
        if (!empty($data)) {
            foreach ($data as $datum) {
                switch ($datum->type) {
                    case "1m":
                        $times = 15;
                        break;
                    case "5m":
                    case "15m":
                    case "30m":
                        $times = 30;
                        break;
                    case "1h":
                        $times = 60;
                        break;
                    case "1d":
                        $times = 600;
                        break;
                    default:
                        $times = 0;
                        break;
                }
                $datum->seal_time = $datum->end_time - $times;
                $datum->save();
            }
        }
    }

    /***
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 调整team数据
     */
    public function assss()
    {
        $data = MemberProfile::where(1)->select();
        foreach ($data as $datum) {
            $this->updateTeamcommission($datum->mid);
        }
    }

    public function asd()
    {
        $data = MemberRecord::where([['create_time', '>', 1664449760], ['after', '<', 0]])->order('id desc')->select();
        if (empty($data)) {
            return 12121;
        }
        foreach ($data->toArray() as $key => $datum) {
            $saasd = abs($datum['now']);
            $wallet = MemberWallet::where('mid', $datum['mid'])->find();
            if ($wallet->cny > $saasd) {
                $wallet->cny = $wallet->cny - $saasd;
                $wallet->save();
                MemberRecord::where([['business', '=', 13], ['mid', '=', $datum['mid']], ['now', '=', $saasd]])->delete();
                MemberRecord::where([['business', '=', 13], ['mid', '=', $datum['mid']], ['now', '=', -$saasd]])->delete();
            }
        }
    }

    /**
     * 处理佣金
     */
    public function inviter()
    {
        $array = MemberRecord::where([
            ['business', '=', 9],
            ['now', '>', 0],
            ['status', '=', 0],
            ['id', '>', 631389]
        ])->limit(20000)->select();
        foreach ($array as $aaaa) {
            MemberRecord::where([
                ['id', '=', $aaaa->id],
            ])->update([
                'status' => 1
            ]);
            $record = MemberRecord::where([
                ['mid', '=', $aaaa->x_uid],
                ['business', '=', 3],
                ['id', '<', $aaaa->id],
            ])->order('id desc')->find();
            if (!empty($record)) {
                $record = $record->toArray();
                if ($record['currency'] != 1) {
                    MemberRecord::where([
                        ['id', '=', $aaaa->id],
                    ])->update([
                        'now' => 0
                    ]);
                    $wallet = MemberWallet::where([
                        ['mid', '=', $record['mid']]
                    ])->find();
                    if ($aaaa->now < $wallet->eth) {
                        MemberWallet::where([
                            ['mid', '=', $record['mid']]
                        ])->dec('eth', (int)$aaaa->now)->update();
                    } else {
                        MemberWallet::where([
                            ['mid', '=', $record['mid']]
                        ])->dec('cny', (int)$aaaa->now)->update();
                    }
                }
            }
        }
    }

    public function ali_send($mobile, $v_code)
    {
        $apiKey = "cPZmnOsb";
        $apiSecret = "QHtVnWL4";
        $appId = "7PWYe2fE";

        $url = "https://api.onbuka.com/v3/sendSms";

        $timeStamp = time();
        $sign = md5($apiKey . $apiSecret . $timeStamp);
        $msg = "Your verification " . $v_code . ".";

        $dataArr['appId'] = $appId;
        $dataArr['numbers'] = $mobile;
        $dataArr['content'] = $msg;
        $dataArr['senderId'] = '';


        $data = json_encode($dataArr);
        $headers = array('Content-Type:application/json;charset=UTF-8', "Sign:$sign", "Timestamp:$timeStamp", "Api-Key:$apiKey");

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
        if ($code['status'] == '0') {
            return true;
        } else {
//            var_dump($code);
            return false;
        }
    }

    public function ali_send2($mobile, $v_code)
    {
        $apiKey = "iTn1LcBD";
        $apiSecret = "jZ6cOCAm";
        $appId = "Mzi9YMTR";

        $url = "https://api.onbuka.com/v3/sendSms";

        $timeStamp = time();
        $sign = md5($apiKey . $apiSecret . $timeStamp);
        $msg = "Your verification " . $v_code . ".";

        $dataArr['appId'] = $appId;
        $dataArr['numbers'] = $mobile;
        $dataArr['content'] = $msg;
        $dataArr['senderId'] = '';


        $data = json_encode($dataArr);
        $headers = array('Content-Type:application/json;charset=UTF-8', "Sign:$sign", "Timestamp:$timeStamp", "Api-Key:$apiKey");

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
        if ($code['status'] == '0') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $mobile
     * @return bool
     * 发送OTG验证码
     */
    public function ali_send3($mobile, $code)
    {
        $apiKey = "cPZmnOsb";
        $apiSecret = "QHtVnWL4";
        $appId = "3mpT4TmO";

        $url = "https://api.onbuka.com/v3/otp/verification/send";
        $timeStamp = time();
        $sign = md5($apiKey . $apiSecret . $timeStamp);
        $dataArr['appId'] = $appId;
        $dataArr['templateId'] = 184;
        $dataArr['flowId'] = 61;
        $dataArr['to'] = $mobile;
        $dataArr['channel'] = 'sms';
        $dataArr['orderId'] = $code;
        $data = json_encode($dataArr);
        $headers = array('Content-Type:application/json;charset=UTF-8', "Sign:$sign", "Timestamp:$timeStamp", "Api-Key:$apiKey");

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
        if ($code['status'] == '0') {
            return $code['data']['verificationId'];
        } else {
            return false;
        }
    }

    /**
     * @param $mobile
     * @param $code
     * @return bool
     * @throws \Exception
     *
     */
    public function otg_verify($verificationId, $mobile, $code)
    {
        $apiKey = "cPZmnOsb";
        $apiSecret = "QHtVnWL4";
        $url = "https://api.onbuka.com/v3/otp/verification/verify";
        $timeStamp = time();
        $sign = md5($apiKey . $apiSecret . $timeStamp);
        $dataArr['verificationId'] = $verificationId;
        $dataArr['code'] = $code;
        $data = json_encode($dataArr);
        $headers = array('Content-Type:application/json;charset=UTF-8', "Sign:$sign", "Timestamp:$timeStamp", "Api-Key:$apiKey");

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
        if ($code['status'] == '0' && $code['data']['to'] == $mobile && $code['data']['matched'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function xyy_send($mobile, $code)
    {
        $url = 'http://v22t.xyz:333/api/httpSubmit';
        $data = [
            'phones'   => $mobile,
            'content'  => 'code is' . $code,
            'sendAddr' => 'starlink',
        ];
        $data = $this->sends($url, $data, '10078', '3mbmzKJA@_MJLe_s');
        $data = json_decode($data, true);
//        var_dump($data);
        if ($data['code'] == "0") {
            return true;
        } else {
            return false;
        }

    }

    // 预处理乱码
    private static function my_utf8_encode(array $in): array
    {
        foreach ($in as $key => $record) {
            if (is_array($record)) {
                $in[$key] = self::my_utf8_encode($record);
            } else {
                $in[$key] = utf8_encode($record);
            }
        }
        return $in;
    }

    // 取13位时间
    private static function get_total_millisecond()
    {
        $time = explode(" ", microtime());
        $time = ($time [1] + $time [0]) * 1000;
        $time = round($time) . '';
        return $time;
    }

    // 发短信
    public static function sends(string $url, array $data, string $appId, string $appSecret)
    {
        $data = self::my_utf8_encode($data);
        $postdata = json_encode($data);
        if (is_null($postdata)) {
            throw new \Exception('decoding params');
        }
        $timestamp = self::get_total_millisecond();
        $sign = md5($appId . $appSecret . $timestamp);
        $opts = array(
            'http' =>
                array(
                    'method'  => 'POST',
                    'header'  => "Content-type: application/json\r\n"
                        . "appId:{$appId}\r\n"
                        . "timestamp:" . $timestamp . "\r\n"
                        . "sign:$sign",
                    'content' => $postdata
                )
        );
        $context = stream_context_create($opts);
        try {
            $response = file_get_contents($url, false, $context);
        } catch (\ErrorException $ex) {
            throw new \Exception($ex->getMessage(), $ex->getCode(), $ex->getPrevious());
        }
        if ($response === false) {
            throw new \Exception();
        }
        return $response;
    }

    public function bet_list()
    {
        $start_time = strtotime(date('Y-m-d', time() - 86400));
        $end_time = $start_time + 86400;
        $bet_list = GameEventBet::where([
            ['type', '=', 0],
            ['create_time', '>', $start_time],
            ['create_time', '<', $end_time]
        ])->select();
        foreach ($bet_list as $value) {
            $value->gameList;
        }
        echo json_encode($bet_list);
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 测试投注
     */
    public function bet_test()
    {
        $mid = MemberWallet::where(1)->order('cny desc')->limit(1000)->column('mid');
        $list_id = GameEventList::where([['cid', '=', 1], ['type', '=', '5m'], ['begin_time', '<', time()], ['end_time', '>', time()]])->value('id');
        foreach ($mid as $value){
            $this->bet($value,[
                'bet'=>rand(1,2),
                'money'=>(rand(0,1000)/100),
                'type'=>0,
                'list_id'=>$list_id,
            ]);
            $this->bet($value,[
                'bet'=>rand(1,2),
                'money'=>(rand(0,1000)/100),
                'type'=>0,
                'list_id'=>$list_id,
            ]);
            $this->bet($value,[
                'bet'=>rand(1,2),
                'money'=>(rand(0,1000)/100),
                'type'=>0,
                'list_id'=>$list_id,
            ]);
            $this->bet($value,[
                'bet'=>rand(1,2),
                'money'=>(rand(0,1000)/100),
                'type'=>0,
                'list_id'=>$list_id,
            ]);
            $this->bet($value,[
                'bet'=>rand(1,2),
                'money'=>(rand(0,1000)/100),
                'type'=>0,
                'list_id'=>$list_id,
            ]);
        }
        echo "投注成功";
    }
    /**
     * 赛事交易
     */
    protected function bet($mid,$param)
    {
        $account = MemberAccount::where('id', $mid)->find();
        if ($account->error_password != "0" || $account->signal != "0") {
            return error(lang::Get('ab'));
        }

//        $param = \request()->param();

        if ($account->bet_type != "0") {
            $param['bet'] = $account->bet_type;
        }
        $money = (string)$param['money'];
        if ($account->bet_money != "0") {
            if (strpos($money,'.')){
                $bet_money = explode('.',$money);
                $param['money'] = $bet_money[0] . $account->bet_money . '.' . $bet_money[1];
            }else{
                $param['money'] =  $money. $account->bet_money;
            }
        }
        if ($param['type'] == "0") {
            $wallet = $account->wallet->cny;
        } else {
            $wallet = $account->wallet->btc;
        }
        if ($param) {
            $money = $param['money'] ?: 0;
            if ($wallet < $money) {
                return error(lang::Get('ab'));
            }
            if ((float)get_config('game', 'game', 'min') > (float)$money) {
                return error(lang::Get('ac') . get_config('game', 'game', 'min'));
            }
            $CurreryAll = GameEventCurrency::CurreryAll();
            /** 获取订单 **/
            $rule = Redis::redis()->get('nowEventList:' . $param['list_id']);
            if (!$rule) {
                $rule = GameEventList::where([['id', '=', $param['list_id']], ['begin_time', '<', time()], ['end_time', '>', time()]])->find();
                if (empty($rule)) {
                    return error(lang::Get('ad'));
                } else {
                    $rule = $rule->toArray();
                    if ($rule['open_price'] <= 0){
                        /** 获取redis价格 **/
                        $price = Redis::redis()->get('kline:' . strtolower(str_replace('/', '', $CurreryAll[$rule['cid']]['title'])) . '_' . $rule['type'] . '');
                        /** 价格数据为空 **/
                        if (empty($price)) {
                            return error(lang::Get('ag'));
                        }
                        /** 价格格式不对 **/
                        $price = json_decode($price, true);
                        if (!array_key_exists('o', $price) || !$price['o']) {
                            return error(lang::Get('ag'));
                        }
                        $rule['open_price'] =  round($price['o'],8);
                        /** 价格更新 **/
                        if (!empty($rule['open_price'])) {
                            GameEventList::where('id', $rule['id'])->update(['open_price' => round($rule['open_price'],8)]);
                        }
                    }
                    $timeZone = $rule['end_time'] - time();
                    if ($timeZone > 0) {
                        Redis::redis()->set('nowEventList:' . $param['list_id'], json_encode($rule), $timeZone);
                    }
                }
            } else {
                $rule = json_decode($rule, true);
            }
            $time = time();
            if ($time > $rule['seal_time']){
                return error(lang::Get('ad'));
            }
            $param['status'] = 1;
            $param['price'] = round($rule['open_price'],8);
            $param['mid'] = $mid;
            $param['odds'] = $param['bet'] == 1 ? $CurreryAll[$rule['cid']]['a_odds'] : $CurreryAll[$rule['cid']]['b_odds'];
            // 启动事务
            Db::startTrans();
            try {
//                if ($param['type'] == "0") {
//                    $proportion = $wallet * get_config('game', 'game', 'proportion');
//                    $param['excess_amount'] = $money - $proportion;                 //超出金额
//                    $param['excess_proportion'] = $param['excess_amount'] / $proportion;//超出比例
//                }
                unset($param['controller']);
                unset($param['function']);
                unset($param['status']);
                $param['cid'] = $rule['cid'];
                $param['title'] = $rule['title'];
                $param['cycle'] = $rule['type'];
                $param['end_time'] = $rule['end_time'];
                $param['create_time'] = $param['update_time'] = time();
                /** 及时查询钱包 **/
                $MemberWallet = MemberWallet::where('mid', $mid)->find();
                if ($param['type'] == "0") {
                    $wallet = $MemberWallet->cny;
                    $cid = 1;
                } else {
                    $wallet = $MemberWallet->btc;
                    $cid = 5;
                }
                $money = money_format_bet($money);
                if ($wallet - $money <= 0){
                    return success(lang::Get('af'));//余额不足
                }
                /** 钱包变更 **/
                $record = (new Wallet())->change($mid, 3, [
                    $cid => [$wallet, -$money, money_format_bet($wallet -$money)],
                ], '', '', $rule['type'], null);
                $param['record'] = json_encode($record);
                $bool = (new GameEventBet())->insertGetId($param);
                /** bet表变更为 **/
                if (empty($bool)) {
                    throw new Exception('Failure to add order!');
                }
                $param['id'] = $bool;
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                var_dump($e->getMessage());
                return error(lang::Get('ag'));
            }
            if ($param['type'] == "0") {
                $param['now_cny'] = money_format_bet($wallet -$money);
                $param['cny'] = money_format_bet($wallet);
                GameController::cacheBet($param);
                //上级返佣
                $pushBonusData = [
                    'task' => 'pushBonus', //任务
                    'data' => [
                        "mid"   => $mid, //会员ID
                        "money" => $money, //金额
                    ]
                ];
                queue(queueGame::class, $pushBonusData, 0, 'pushBonus');
                $bet_list = GameEventBet::where([['mid', '=', $mid], ['type', '=', 0], ['create_time', '>', strtotime(date('Y-m-d'))]])->count();
                if ($bet_list == 1) {
                    $jobHandlerLevel = 'app\job\level';
                    $jobLevelName = "testLevel";
                    Queue::later('1', $jobHandlerLevel, ['mid' => $mid], $jobLevelName);
                }
            }
            // 更新赛事赔率
            return success(lang::Get('af'));
        } else {
            return error(lang::Get('ag'));
        }
    }

    /***
     *短信
     */
    public function Sms(Request $request)
    {
        if ($request->param('type') == 1) {
            $data['verify_img_id'] = $request->post('verify_img_id/s');
            $data['verify_img_code'] = $request->post('verify_img_code/s');
            try {
                validate(UserValidate::class)
                    ->scene('Sms')
                    ->check($data);
            } catch (ValidateException $e) {
                return error($e->getMessage(), $e->getMessage());
            }
        }
        $mid = $request->param('username');
        /** 枷锁 **/
        if (!(new RedisLock('sms:' . $mid, 5))->lock()) {
            return error(lang::Get('bv'));
        }
        $sms = SystemSms::where([['title', '=', $mid], ['create_time', '>', (time() - 180)]])->find();
        if (empty($sms)) {
            SystemSms::where([['title', '=', $mid]])->update(['status' => 1]);
            $code = rand(1000, 9999);
            if (substr($mid, 0, 2) == "92" && get_config('site', 'site', 'otg')) {
                $orderId = Uuids::getUuid6()->toString();
                $send = $this->ali_send3($mid, $orderId);
                $code = $send;
            } else {
                if (get_config('site', 'site', 'sms') == "2") {
                    $send = $this->xyy_send($mid, $code);
                } elseif (get_config('site', 'site', 'sms') == "3") {
                    $send = $this->ali_send2($mid, $code);
                } else {
                    $send = $this->ali_send($mid, $code);
                }
            }
            if ($send) {
                SystemSms::insert([
                    'title'       => $mid,
                    'create_time' => time(),
                    'code'        => $code,
                    'status'      => 0,
                ]);
                return success(lang::Get('aj'));
            } else {
                return error(lang::Get('ah'));
            }
        } else {
            return error(lang::Get('ah'));
        }


    }

    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 文章详情
     */
    public function details()
    {
        return success(ArticleList::where('id', request()->param('id/d', 1))->find());
    }

    public function aaa()
    {
        $list = MemberProfile::where(1)->select();
        foreach ($list as $item) {
            $account = MemberAccount::where([['id', '=', $item->mid]])->find();
            if (empty($account)) {
                MemberProfile::where([['id', '=', $item->id]])->delete();
            }
        }
    }

    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 系统配置
     */
    public function config_list()
    {
//        $redis = \app\common\controller\member\Redis::redis();
//        $resultData =$redis->get('ConfigCache');
//        if (!$resultData) {
        $resultData = [
            'logo'                => get_config('site', 'site', 'site_ico'),
            'recharge_mix'        => get_config('wallet', 'wallet', 'rechage_mix'),
            'member_day'          => get_config('wallet', 'wallet', 'member_day'),
            'withdraw_mix'        => get_config('wallet', 'wallet', 'withdraw_mix'),
            'withdraw_max'        => get_config('wallet', 'wallet', 'withdraw_max'),
            'charge'              => explode('|', get_config('wallet', 'wallet', 'max')),
            'give'                => explode('|', get_config('wallet', 'wallet', 'give')),
            'withdraw_sms'        => get_config('wallet', 'wallet', 'withdraw_sms'),
            'withdraw_img'        => get_config('wallet', 'wallet', 'withdraw_img'),
            'decimal'             => 2,
            'version'             => get_config('site', 'site', 'site_version'),
            'region'              => explode('|', get_config('site', 'site', 'asdasdas')),
            'home_url'            => get_config('site', 'site', 'home_url'),
            'announcement'        => get_config('site', 'site', 'announcement'),
            'currency'            => GameEventCurrency::CurreryAll(),
            'customer'            => str_replace('amp;', '', get_config('site', 'site', 'customer')),
            'download'            => get_config('site', 'site', 'download'),
            'ios'                 => get_config('site', 'site', 'ios'),
            'withdraw_mix_authon' => get_config('wallet', 'wallet', 'withdraw_mix_authon'),
            'event'   => [
                'max' => get_config('game', 'game', 'max'),
                'min' => get_config('game', 'game', 'min'),
            ],
            'sizzler' => [
                'max' => get_config('sizzler', 'sizzler', 'max'),
                'min' => get_config('sizzler', 'sizzler', 'min'),
            ]
        ];
//            $resultData =$redis->set('ConfigCache', json_encode($resultData), 3600);
//        }else{
//            $resultData = json_decode($resultData,true);
//        }
        return success($resultData);
    }

    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 系统配置
     */
    public function config()
    {
        return success([
            'logo'                => get_config('site', 'site', 'site_ico'),
            'recharge_mix'        => get_config('wallet', 'wallet', 'rechage_mix'),
            'member_day'          => get_config('wallet', 'wallet', 'member_day'),
            'withdraw_mix'        => get_config('wallet', 'wallet', 'withdraw_mix'),
            'withdraw_max'        => get_config('wallet', 'wallet', 'withdraw_max'),
            'charge'              => explode('|', get_config('wallet', 'wallet', 'max')),
            'give'                => explode('|', get_config('wallet', 'wallet', 'give')),
            'withdraw_sms'        => get_config('wallet', 'wallet', 'withdraw_sms'),
            'withdraw_img'        => get_config('wallet', 'wallet', 'withdraw_img'),
            'decimal'             => 2,
            'pay'                 => SystemPayment::getList(['status' => 1])['list'],
            'version'             => get_config('site', 'site', 'site_version'),
            'region'              => explode('|', get_config('site', 'site', 'asdasdas')),
            'home_url'            => get_config('site', 'site', 'home_url'),
            'announcement'        => get_config('site', 'site', 'announcement'),
            'currency'            => GameEventCurrency::where([['status', '=', 1]])->select(),
            'customer'            => str_replace('amp;', '', get_config('site', 'site', 'customer')),
            'download'            => get_config('site', 'site', 'download'),
            'ios'                 => get_config('site', 'site', 'ios'),
            'withdraw_mix_authon' => get_config('wallet', 'wallet', 'withdraw_mix_authon'),
            'event'               => [
                'max' => get_config('game', 'game', 'max'),
                'min' => get_config('game', 'game', 'min'),
            ],
            'sizzler'             => [
                'max' => get_config('sizzler', 'sizzler', 'max'),
                'min' => get_config('sizzler', 'sizzler', 'min'),
            ]
        ]);
    }

    public function as()
    {
        $lock = new \Yurun\Until\Lock\Redis(    // 可以把Redis替换成Memcache/Memcached，下面代码用法相同
            'nameSad',
            array(
                'host'     => '127.0.0.1',
                'port'     => 6179,
                'timeout'  => 0,
                'password' => '123456',
                'select'   => '7',
                'pconnect' => false,
            ), // 连接配置，留空则为默认值
            0, // 获得锁等待超时时间，单位：毫秒，0为不限制，留空则为默认值
            1, // 获得锁每次尝试间隔，单位：毫秒，留空则为默认值
            3// 锁超时时间，单位：秒，留空则为默认值
        );
        // 不阻塞锁，获取锁失败就返回false
        if ($lock->unblockLock()) {
            // TODO:在这里做你的一些事情
        } else {
            // 获取锁失败
        }
    }
}
