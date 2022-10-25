<?php
declare (strict_types=1);

namespace app\member\controller;

use app\common\model\ArticleList;
use app\common\model\MemberAccount;
use app\common\validate\UserValidate;
use app\member\BaseCustomer;
use app\member\middleware\jwtVerification;
use think\exception\ValidateException;
use think\facade\Lang;

class Index extends BaseCustomer
{
    protected $middleware = [
        jwtVerification::class => [
            'only' 	=> ['list']
        ]
    ];

    public function config()
    {
        return success([
            ''
        ]);
    }
    /**
     * @return \think\response\Json
     * 文章列表
     */
    public function list()
    {
        $limit = request()->param('limit/d',50);
        $limit = $limit > 50 ? 50 : $limit;
        $ArticleList = ArticleList::getList([['type','=',request()->param('type',1)],['status','=',request()->param('status',1)],['lang','=','zh']],
            request()->param('page',1),
            $limit,
            "*",'id desc'
        );
        return success($ArticleList);
    }

    public function index(){
        return view();
    }
}
