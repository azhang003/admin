<?php

// +----------------------------------------------------------------------
// | kaadonAdmin
// +----------------------------------------------------------------------
// | AUTHOR: KAADON@GMAIL.COM
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/kaadon/kaadonAdmin
// +----------------------------------------------------------------------

namespace app\admin\middleware;

use app\common\service\AuthService;
use think\Request;

/**
 * @deprecated  废弃，新版TP不支持在中间件获取控制器相关信息
 * 检测用户登录和节点权限
 * Class CheckAdmin
 * @package app\admin\middleware
 */
class CheckAdmin
{

    use \app\common\traits\JumpTrait;

    public function handle(Request $request, \Closure $next)
    {
        $adminConfig = config('admin');
        $adminId     = session('admin.id');
        $expireTime  = session('admin.expire_time');
        /** @var AuthService $authService */
        $authService       = app(AuthService::class, ['adminId' => $adminId]);
        $currentNode       = $authService->getCurrentNode();
        $currentController = parse_name($request->controller());

        // 验证登录
        if (!in_array($currentController, $adminConfig['no_login_controller']) &&
            !in_array($currentNode, $adminConfig['no_login_node'])) {
            empty($adminId) && $this->error_view('请先登录后台', [], __url(admin_alias_name() . '/login/index'));

            // 判断是否登录过期
            if ($expireTime !== true && time() > $expireTime) {
                session('admin', null);
                $this->error_view('登录已过期，请重新登录', [], __url(admin_alias_name() . '/login/index'));
            }
        }

        // 验证权限
        if (!in_array($currentController, $adminConfig['no_auth_controller']) &&
            !in_array($currentNode, $adminConfig['no_auth_node'])) {
            $check = $authService->checkNode($currentNode);
            !$check && $this->error_view('无权限访问');

            // 判断是否为演示环境
            if (env('easyadmin.is_demo', false) && $request->isPost()) {
                $this->error_view('演示环境下不允许修改');
            }

        }

        return $next($request);
    }

}