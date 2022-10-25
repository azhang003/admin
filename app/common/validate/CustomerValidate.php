<?php
declare (strict_types=1);

namespace app\common\validate;


/**
 * Class UserValidate
 * @package app\api\validate
 */
class CustomerValidate extends CommonValidate
{
    /**
     * 定义mobile注册验证场景
     * @return object
     */
    public function sceneAddCustomer()
    {
        return $this->only(['cardid', 'password', 'verify_img_id', 'verify_img_code']);
    }

}
