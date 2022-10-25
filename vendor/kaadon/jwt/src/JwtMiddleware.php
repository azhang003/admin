<?php


namespace Kaadon\Jwt;


use think\Request;
use think\Response;
use think\facade\Config;

class JwtMiddleware
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $Currentoute = strtolower($request->pathinfo());
        $api_white_list = Config::get('jwt.api.white');
        if (!in_array($Currentoute, $api_white_list)) {
            $tokenBearer = app('request')->header('Authorization');
            if (!$tokenBearer) {
                throw new JwtException('token is must.');
            }
            $token = substr($tokenBearer, 7);
            if (!$token) {
                throw new JwtException('token is required.');
            }
            $JwtData = Jwt::verify($token);
            $data = [
                'username' => $JwtData->data->identification
            ];
            $Oldtoken = JwtCache::get((int) $data['username']);
            if ($Oldtoken != $token){
                throw new JwtException('你的账号在别处登录!');
            }
            if (\think\facade\Request::ip() != $JwtData->data->ip){
                throw new JwtException('网络环境更换,请重新登录!');
            }
            $request->username = $JwtData->data->identification;
        }
        return $next($request);
    }
}
