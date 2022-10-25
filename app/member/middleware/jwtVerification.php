<?php
/**
 * Created by : PhpStorm
 * Web: https://www.kaadon.com
 * User: ipioo
 * Date: 2022/1/10 03:49
 */

namespace app\member\middleware;


use Kaadon\Jwt\JwtException;
use think\Response;

class jwtVerification
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        /*执行主体*/
        try {
            $JwtData = jwt_verify();
        }catch (\Exception $exception){
            throw new JwtException($exception->getMessage());
        }

        $data = $JwtData->data;

//        if (!property_exists($data, 'type') || $data->type !== "customer") {
//            throw new JwtException('你无权限查看!');
//        }
        //前置用户信息
        $request->customer = $data;

        //前置代理
        $agentarray = explode('|', $request->customer->agent_line);
        if (is_array($agentarray)) {
            foreach ($agentarray as $key => $item) {
                if ($item == 0 || $item == '') {
                    unset($agentarray[$key]);
                }
            }
        }

        $request->agent = $agentarray;


        return $next($request);
    }
}