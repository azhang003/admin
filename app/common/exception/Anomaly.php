<?php


namespace app\common\exception;

use Kaadon\Jwt\JwtException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Env;
use think\Response;
use Throwable;

class Anomaly extends Handle
{
    private $msg = '内部错误,请及时联系系统管理员!';

    private $httpcode = 200;

    private $errorcode = 201;

    public function render($request, Throwable $e): Response
    {
        if (Env::get('APP_DEBUG')) {
            // 开启 DEBUG 模式,调用系统原生异常处理
            return parent::render($request, $e);
        }
        if ($e instanceof JwtException) {
            return json([
                'message' => $e->getMessage(),
                'code'    => 403,
                'data'    => [],
            ], 200);
        }
        if ($e instanceof HttpException || $e instanceof HttpAnomaly) {
            $this->httpcode = $e->getStatusCode() ?: $this->httpcode;
        } else if ($e instanceof HttpResponseException) {
            $this->msg = $e->getResponse()->getData() ?: $this->msg;
        } else {
            $this->msg = $e->getMessage() ?: $this->msg;
        }
        // 参数验证错误
        if ($e instanceof ValidateException) {
            $this->httpcode = 200;
        }

        $this->errorcode = $e->getCode() ?: $this->errorcode;

        $resultDate = [
            'message' => $this->msg,
            'code'    => $this->errorcode,
            'data'    => [],
        ];
        return json($resultDate, $this->httpcode);

    }

}