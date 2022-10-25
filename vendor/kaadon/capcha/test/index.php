<?php
/**
 * Created by : PhpStorm
 * Web: https://www.kaadon.com
 * User: ipioo
 * Date: 2022/1/11 00:00
 */
require_once "vendor/autoload.php";
use Kaadon\CapCha\capcha;

try {

    /*执行主体*/
    (new capcha())->create();
} catch (\Exception $e) {
    var_dump($e->getTrace());
}