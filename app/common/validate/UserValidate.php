<?php
declare (strict_types=1);

namespace app\common\validate;


/**
 * Class UserValidate
 * @package app\api\validate
 */
class UserValidate extends CommonValidate
{
    /**
     * 定义更新登录密码验证场景
     * @return UserValidate
     */
    public function sceneForget()
    {
        return $this->only(['username', 'newpassword', 'verify_code', 'type'])
            ->append('username', 'usernameOnly:existent');
    }

    /**
     * 定义更新登录密码验证场景
     * @return UserValidate
     */
    public function sceneImgcode()
    {
        return $this->only(['verify_img_id', 'verify_img_code',]);
    }

    /**
     * 定义更新支付宝验证场景
     * @return UserValidate
     */
    public function sceneAlipay()
    {
        return $this->only(['username', 'realname'])
            ->append('username', 'usernameOnly:existent');;
    }

    /**
     * 定义更新银行验证场景
     * @return UserValidate
     */
    public function sceneBank()
    {
        return $this->only(['username', 'realname', 'bankname', 'bankcard'])
            ->append('username', 'usernameOnly:existent');
    }

    /**
     * 重构定义更新安全密码验证场景
     * @return UserValidate
     */
    public function sceneUpdateProfile()
    {
        return $this->only(['username', 'nickname']);
    }

    /**
     * 定义更新实名认证
     * @return UserValidate
     */
    public function sceneUpdateAuthen()
    {
        return $this->only(['username', 'front', 'back', 'hold']);
    }

    /**
     * 定义更新安全密码验证场景
     * @return UserValidate
     */
    public function sceneUpdateSafeword()
    {
        return $this->only(['username', 'password', 'newsafeword', 'verify_img_id', 'verify_img_code'])
            ->append('username', 'usernameOnly:existent')
            ->append('password', 'verifySecret');
    }

    /**
     * 定义更新安全密码验证场景
     * @return UserValidate
     */
    public function sceneExchange()
    {
        return $this->only(['username', 'safeword', 'verify_img_id', 'verify_img_code'])
            ->append('username', 'usernameOnly:existent')
            ->append('safeword', 'verifySecret');
    }
    /**
     * 定义更新安全密码验证场景
     * @return UserValidate
     */
    public function sceneUpdateSafePassword()
    {
        return $this->only(['username', 'verify_code'])
            ->append('username', 'usernameOnly:existent');
    }

    /**
     * 定义修改密码验证场景
     * @return UserValidate
     */
    public function sceneUpdatePassword()
    {
        return $this->only(['W', 'password', 'newpassword',])
            ->append('password', 'verifySecret');
    }

    /**
     * 定义邮箱登录验证场景
     * @return UserValidate
     */
    public function sceneEmailLogin()
    {
//        return $this->only(['username', 'password', 'verify_img_id', 'verify_img_code'])
        return $this->only(['username', 'password'])
            ->append('username', 'emailOnly:existent,status')
            ->append('password', 'verifySecret');
    }

    /**
     * 定义手机登录验证场景
     * @return UserValidate
     */
    public function sceneMobileLogin()
    {
//        return $this->only(['username', 'password', 'verify_img_id', 'verify_img_code'])
//        return $this->only(['username', 'password', 'verify_code'])
        return $this->only(['username', 'password'])
            ->append('username', 'mobileOnly:existent,status')
            ->append('password', 'verifySecret');
    }

    /**
     * 定义email注册验证场景
     * @return object
     */
    public function sceneEmailRegister()
    {
        return $this->only(['email', 'password','verify_code'])
//            ->append('safeword', 'different:password')
            ->append('email', 'emailOnly:non-existent');
    }

    /**
     * 定义mobile注册验证场景
     * @return object
     */
    public function sceneMobileRegister()
    {
        return $this->only(['username', 'password','verify_code'])
//            ->append('safeword', 'different:password')
            ->append('username', 'mobileOnly:non-existent');
    }
    /**
     * 定义mobile注册验证场景
     * @return object
     */
    public function sceneMobileForget()
    {
        return $this->only(['username', 'password','verify_code'])
//            ->append('safeword', 'different:password')
            ->append('username', 'mobileOnly:existent');
    }

    /**
     * 定义用户验证场景
     * @return object
     */
    public function sceneUserMode()
    {
        return $this->only(['username'])
            ->append('username', ['usernameOnly:existent,status,authen']);
    }

    /**
     * 定义短信验证场景
     * @return object
     */
    public function sceneSms()
    {
        return $this->only(['verify_img_id', 'verify_img_code']);
    }

    /**
     * 定义短信验证场景
     * @return object
     */
    public function sceneOrdinarysms()
    {
        return $this->only(['username', 'type', 'verify_img_id', 'verify_img_code'])
            ->append('username', ['usernameOnly:non-existent']);
    }

    /**
     * @return \app\common\validate\UserValidate
     * 充值 验证
     */
    public function sceneRecharge()
    {
        return $this->only(['amount'])
        ->append('payment');
    }


    /**
     * @return \app\common\validate\UserValidate
     * 充值方式 验证
     */
    public function scenePayment()
    {
        return $this->only(['amount', 'payment'])
        ->append('payment', ['paymentNum']);
    }


    /**
     * 定义支付验证场景
     * @return UserValidate
     */
    public function scenePay()
    {
        return $this->only(['username', 'safeword'])
            ->append('username', 'usernameOnly:existent')
            ->append('safeword', 'verifySecret');
    }

    /**
     * 定义支付验证场景
     * @return UserValidate
     */
    public function sceneWithDraw()
    {
        return $this->only(['username', 'safeword'])
            ->append('username', 'usernameOnly:existent')
            ->append('safeword', 'verifySecret');
    }

    /**
     * 定义支付验证场景
     * @return UserValidate
     */
    public function sceneWithDraws()
    {
        return $this->only(['username', 'safeword', 'verify_code'])
            ->append('username', 'usernameOnly:existent')
            ->append('safeword', 'verifySecret');
    }


    /**
     * 更新资料
     * @return UserValidate
     */
    public function sceneProfile()
    {

        return $this->only(["realname"]);
    }
}
