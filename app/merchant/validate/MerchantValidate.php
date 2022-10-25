<?php
declare (strict_types=1);

namespace app\merchant\validate;


use app\common\validate\CommonValidate;
use app\common\validate\UserValidate;

/**
 *
 */
class MerchantValidate extends CommonValidate
{

    /**
     * @return MerchantValidate
     */
    public function sceneForget()
    {
        return $this->only(['username', 'newpassword', 'verify_code', 'type'])
            ->append('username', 'usernameOnly:existent');
    }


    /**
     * 定义邮箱登录验证场景
     * @return UserValidate
     */
    public function sceneLogin()
    {
        return $this->only(['username', 'password'])//, 'verify_img_id', 'verify_img_code'
            ->append('password', 'merchantVerifySecret');
    }

}
