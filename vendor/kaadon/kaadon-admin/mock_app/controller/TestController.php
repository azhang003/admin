<?php

namespace MockApp\controller;

use KaadonAdmin\annotation\ControllerAnnotation;
use KaadonAdmin\annotation\NodeAnotation;
use MockApp\BaseController;


/**
 * Class TestController
 * @package MockApp\controller
 * @ControllerAnnotation(title="测试管理")
 */
class TestController extends BaseController
{

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        var_dump(__METHOD__);
    }

    /**
     * @NodeAnotation(title="详情")
     */
    public function details()
    {
        var_dump(__METHOD__);
    }


}