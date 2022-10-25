<?php

namespace app\common\validate;


use app\common\model\MemberAccount;
use app\common\model\MemberProfile;
use app\common\model\MerchantProfile;
use app\common\model\SystemPayment;
use app\common\model\SystemSms;
use app\service\controller\Index;
use app\service\controller\Service;
use Kaadon\CapCha\capcha;
use think\Validate;

/**
 * Class CommonValidate
 * @package app\common\validate
 */
class CommonValidate extends Validate
{
    /**
     * 正则表达式过滤
     * @var array
     */
    protected $regex = [
        'path'     => '/^[a-zA-Z0-9\/\.\:]+$/',
        'realname' => '/^[\x80-\xff\.]{2,30}$/',
        'wechat'   => '/^[a-zA-Z0-9_-]+$/',
    ];
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'qq|qq号'                => 'require|number',
        'mobile|手机号'            => 'require|mobile|number',
        'cardid|客服登录账号'            => 'require|alphaNum|length:6,15',
        'nickname|昵称'           => 'require|chsAlphaNum|length:6,32',
        'password|登录密码'         => 'require|length:6,32',
        'payment|支付方式'         => 'require|length:1,2|paymentNum',
        'newpassword|新登录密码'     => 'require|length:6,32',
        'safeword|交易密码'         => 'require|length:6,32',
        'newsafeword|新安全密码'     => 'require|length:6,32',
        'inviter|推荐人'           => 'require|length:12',
        'verify_code|短信验证码'     => 'require|number|length:4|smsCode',
        'type|类型'               => 'require|in:Common,Ordinary',//短信发送类型验证
        'realname|姓名'           => 'require|regex:realname|length:2,16',
        'idcard|身份证'            => 'require|idCard|length:18',
        'amount|充值数额'            => 'require|number|length:1,10',
        'usdt|USDT收款地址'         => 'require|length:15,64',
        'wechat|微信'             => 'require|regex:wechat|length:4,32',
        'alipay|支付宝'            => 'require|length:4,32',
        'bankname|银行名称'         => 'require|chs|length:4,16',
        'bankcard|银行卡'          => 'require|number|length:15,20',//银行卡验证
        'verify_img_id|9999'  => 'requireWith:verify_img_code',//图片验证ID验证
        'verify_img_code|9998' => 'require|length:4|verifyImgcode',//图片验证码型验证
        'image|图片'              => 'require|file|fileMime:image/bmp,image/png,image/gif,image/jpeg,image/x-ms-bmp',//图片验证码型验证
        'file|文件'               => 'require|file|fileMime:application/x-rar,text/plain,audio/mpeg,video/mp4,application/vnd.android.package-archive,application/iphone',//图片验证码型验证
        'avatar|头像'             => 'require|regex:path',
        'front|身边证正面照片'         => 'require|regex:path',
        'back|身份证反面照片'          => 'require|regex:path',
        'hold|手持身份证照片'          => 'require|regex:path',
        'bank|银行名称'          => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */

    protected $message = [
        'image.fileMime' => '文件类型只支持jpg,png,gif,jpeg,bmp格式',
        'file.fileMime'  => '文件类型只支持rar,txt,mp3,mp4,apk,ipa格式',
        'realname.regex' => '姓名只能是中文和"."的组合',
        'wechat.regex'   => '微信号只能是小写字母大写字母和"_-"的组合',
        'avatar.regex'   => '头像路径格式不正确!',
        'cardid.alphaNum'   => '只能为6-15位数字和字母!',
        'front.regex'    => '身份证正面照片路径格式不正确!',
        'back.regex'     => '身份证反面照片路径格式不正确!',
        'hold.regex'     => '手持身份证照片路径格式不正确!',
    ];

    /**
     * 定义验证场景
     * UploadImage 上传图片验证场景
     * UploadFile 上传文件验证场景
     * @var array
     */
    protected $scene = [
        'UploadImage' => ['image'],
        'UploadFile'  => ['file'],
    ];

    /**
     * 自定义验证规则
     * 判断用户存在或者不存在
     * @param $value
     * @param $rule
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function inviterOnly($value, $rule)
    {
        if (is_string($rule)) {
            $rules = explode(',', $rule);
        }
        $res = MemberAccount::where('uuid', $value)->find();
        if (!is_null($res)) {
            $res = $res->toArray();
        }
        if (in_array('existent', $rules)) {
            if (empty($res)) {
                return "1002";//$value . '不存在!'
            }
            if (in_array('status', $rules) && $res['status'] != 1) {
                return '邀请人已被冻结';
            }
            if (in_array('authen', $rules) && $res['authen'] != 1) {
                return '邀请人未完成实名认证';
            }
            return true;
        }
        if (in_array('non-existent', $rules)) {
            return empty($res) ? true : 1003;//$value . '已存在!'
        }
    }

    /**
     * 自定义验证规则
     * 判断email用户存在或者不存在
     * @param $value
     * @param $rule
     * @return bool|string
     */
    protected function emailOnly($value, $rule)
    {
        if (is_string($rule)) {
            $rules = explode(',', $rule);
        }
        $res = MemberProfile::where('email', $value)->find();
        if (!is_null($res)) {
            $res = $res->toArray();
        }
        if (in_array('existent', $rules)) {
            if (empty($res)) {
                return "1002";//$value . '不存在!'
            }
            if (in_array('status', $rules) && $res['status'] != 1) {
                return '1001';//账号已被冻结
            }
            if (in_array('authen', $rules) && $res['authen'] != 1) {

                return '1000';//账号未完成实名认证
            }
            return true;
        }
        if (in_array('non-existent', $rules)) {
            return empty($res) ? true : 1003;//$value . '已存在!'
        }
    }

    /**
     * 自定义验证规则
     * 判断mobile用户存在或者不存在 唯一
     * @param $value
     * @param $rule
     * @return bool|string
     */
    protected function mobileOnly($value, $rule)
    {
        if (is_string($rule)) {
            $rules = explode(',', $rule);
        }
        $ress = MemberProfile::where('mobile', $value)->find();
        if (!is_null($ress)) {
            $res = $ress->toArray();
        }
        if (in_array('existent', $rules)) {
            if (empty($res)) {
                return "1002";//$value . '不存在!'
            }
            $account = $ress->account;
            if (in_array('status', $rules) && $account->status != 1) {
                return '1001';//账号已被冻结
            }
            if (in_array('authen', $rules) && $account->authen != 1) {

                return '1000';//账号未完成实名认证
            }
            return true;
        }
        if (in_array('non-existent', $rules)) {
            return empty($res) ? true : 1003;//$value . '已存在!'
        }
    }

    /**
     * 自定义验证规则
     * 判断密码是否正确
     * @param $value
     * @param string $rule
     * @param array $data
     * @param string $name
     * @param string $Abbreviation
     * @return bool|string
     */
    protected function profileOnly($value, $rule = '', array $data = [], $name = '', $Abbreviation = '')
    {
        if (!$data['username']) {
            return '用户名不能为空,不能验证' . ($Abbreviation ?: $name) . '!';
        }
        $key = MemberProfile::where('username', '<>', $data['username'])
            ->where($name, '<>', $value)
            ->find();

        if (empty($key)) {
            return true;
        } else {
            return ($Abbreviation ?: $name) . '已存在!';
        }
    }

    /**
     * 自定义验证规则
     * 支付方式是否正确
     * @param $value
     * @param string $rule
     * @param array $data
     * @param string $name
     * @param string $Abbreviation
     * @return bool|string
     */
    protected function paymentNum($value, $rule = '', array $data = [], $name = '', $Abbreviation = '')
    {
        if (!$data['payment']) {
            return '支付方式不能为空,不能验证' . ($Abbreviation ?: $name) . '!';
        }
        $key = SystemPayment::where([
            ['id', '=', $data['payment']],
            ['status', '=', 1],
        ])->find();

        if (!empty($key)) {
            return true;
        } else {
            return ($Abbreviation ?: $name) . '不存在!';
        }
    }


    /**
     * 自定义验证规则
     * 判断短信验证码是否正确
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function smsCode($value, $rule, $data = [])
    {
        if (substr($data['username'],0,2) == "92" && get_config('site','site','otg')){
            $raw  = SystemSms::where([['title','=',$data['username']],['status','=','0']])->order('id desc')->find();
            if (!$raw){
                return 1005;
            }
            $send = (new Index())->otg_verify($raw->code,$data['username'],$data['verify_code']);
            if ($send == true){
                SystemSms::where('id',$raw->id)->update(['status'=>1]);
                return true;
            }
        }else{
            $raw  = SystemSms::where([['title','=',$data['username']],['status','=','0'],['code','=',$data['verify_code']]])->find();
            if (!empty($raw)) {
                SystemSms::where('id',$raw->id)->update(['status'=>1]);
                return true;
            }
        }
        return 1005;//'短信验证码不正确!'
    }

    /**
     * 自定义验证规则
     * 判断密码是否正确
     * @param $value
     * @param string $rule
     * @param array $data
     * @param string $name
     * @param string $Abbreviation
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function verifySecret($value, $rule = '', array $data = [], $name = '', $Abbreviation = '')
    {
        $user = MemberProfile::where('mobile', $data['username'])->find();
//        if (empty($user)) {
//            return '类型不能为空,不能验证密码!';
//        }
        if ($user->account->error_password == "1"){
            return '1004';//password error
        }
//        var_dump($value);var_dump($name);var_dump($user->account->$name);exit();
        if (password_verify($value, $user->account->$name)) {
            return true;
        } else {
            return 1004;//($Abbreviation ?: $name) . '不正确!'
        }
    }


    /**
     * 自定义验证规则{Merchant}
     * 判断密码是否正确
     * @param $value
     * @param string $rule
     * @param array $data
     * @param string $name
     * @param string $Abbreviation
     * @return bool|string
     */
    protected function merchantVerifySecret($value, $rule = '', array $data = [], $name = '', $Abbreviation = '')
    {
        $user = MerchantProfile::where($data['type'], $data[$data['type']])->find();
        if (empty($user)) {
            return '类型不能为空,不能验证密码!';
        }
        if (password_verify($value, $user->account->$name)) {
            return true;
        } else {
            return ($Abbreviation ?: $name) . '不正确!';
        }
    }
    /**
     * 自定义验证规则
     * 判断用户存在或者不存在
     * @param $value
     * @param $rule
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function usernameOnly($value, $rule)
    {
        $value = str_replace('+','',$value);
        if (is_string($rule)) {
            $rules = explode(',', $rule);
        }
        $username = (new MemberProfile())->where('mobile', $value)->find();
        $res = empty($username) ? array() : (new MemberAccount())->where('id', $username->mid)->find()->toArray();
        if (in_array('existent', $rules)) {
            if (empty($res)) {
                return "1002";//$value . '不存在!'
            }
            if (in_array('status', $rules) && $res['status'] != 1) {
                return '1001';//账号已被冻结
            }
            if (in_array('authen', $rules) && $res['authen'] != 1) {

                return '1000';//账号未完成实名认证
            }
            return true;
        }
        if (in_array('non-existent', $rules)) {
            return empty($res) ? true : 1003;//$value . '已存在!'
        }
        return true;
    }

    /**
     * 自定义验证规则
     * 判断图片验证码是否正确
     * @param $value
     * @param string $rule
     * @param array $data
     * @param string $name
     * @param string $Abbreviation
     * @return bool|string
     */
    protected function verifyImgcode($value, $rule = '', array $data = [], $name = '', $Abbreviation = '')
    {
        $check_verify = (new \Kaadon\CapCha\capcha())->check($data['verify_img_id'], $value);
        if (empty($check_verify)) {
            return 9999;
            return ($Abbreviation ?: $name) . '错误或已过期!';
        }
        return true;
    }
}
