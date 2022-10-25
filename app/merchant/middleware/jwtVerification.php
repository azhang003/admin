<?php
/**
 * Created by : PhpStorm
 * Web: https://www.kaadon.com
 * User: ipioo
 * Date: 2022/1/10 03:49
 */

namespace app\merchant\middleware;


use app\common\exception\JwtAnomaly;
use app\common\model\MerchantAccount;
use app\Request;
use Kaadon\Jwt\JwtException;
use think\Exception;
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
    public function handle(Request $request, \Closure $next)
    {
        /*执行主体*/
        $JwtData = jwt_verify();
        $data = $JwtData->data;
        if (!property_exists($data, 'type') || $data->type !== "merchant") {
            throw new JwtException('你无权限查看!');
        }
        $request->merchant = $data;
        $ahents = explode('|',$data->agent_line);
        if (count($ahents)>3){
            $request->merchant->id = $ahents[2];
        }
        $memberLine = MerchantAccount::where('agent_line', 'like', '%|' . $request->merchant->id . "|%")->column('id');
        array_push($memberLine, $request->merchant->id);
        $request->agent = $memberLine;
        return $next($request);
    }
}