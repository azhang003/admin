<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'curd'                => 'app\common\command\Curd',
        'node'                => 'app\common\command\Node',
        'OssStatic'           => 'app\common\command\OssStatic',
        'Kjs'                 => \app\common\command\Kjs::class,
        'Kj'                 => \app\common\command\Kj::class,
        'Recovery'            => \app\common\command\Recovery::class,
        'date'                => \app\common\command\Date::class,
        'SaveMoney'           => \app\common\command\SaveMoney::class,
        'NumCe'               => \app\common\command\NumCe::class,
        'Withdraw'            => \app\common\command\Withdraw::class,
        'IntegrationFromUser' => \app\common\command\IntegrationFromUser::class,
        'memberdate'          => \app\common\command\MemberDate::class,
        'Membererror'          => \app\common\command\Membererror::class,
        'red'                 => \app\common\command\Money::class,
        'Gamebet'             => \app\common\command\Gamebet::class,
        'Draw'                => \app\common\command\Draw::class,
        'GiveMoney'           => \app\common\command\GiveMoney::class,
        'Kline1d'             => \app\common\command\Kline1d::class,
        'dashboard'           => \app\common\command\MemberDashboard::class,
        'dashboardss'         => \app\common\command\MemberDashboards::class,
        'Integrations'        => \app\common\command\Integrations::class,
        'Profile'             => \app\common\command\Profile::class,
        'SystemSummarize'     => \app\common\command\SystemSummarize::class,
        'SystemDays'          => \app\common\command\SystemDays::class,
        'MerchantDate'          => \app\common\command\MerchantDate::class,
        'MemberTeam'          => \app\common\command\MemberTeam::class,
        'MemberTeams'          => \app\common\command\MemberTeams::class,
        'MerchantIndex'       => \app\common\command\MerchantIndex::class,
        'MemberIndex'         => \app\common\command\MemberIndex::class,
        'MemberIndexs'         => \app\common\command\MemberIndexs::class,
        'MemberTest'          => \app\common\command\MemberTest::class,
        'TestAward'           => \app\common\command\TestAward::class,
        'orderDeal'           => \app\common\command\orderDeal::class,
        'TestTT'              => \app\common\command\TestTT::class,
        'TestDe'              => \app\common\command\TestDe::class,
        ///** 手动归集 **/
        'CollectFromMember'   => app\common\command\CollectFromMember::class,
        ///** 赛事订单创建及分表 **/
        'Generate'            => app\common\command\Generate::class,
        ///** 赛事开奖 **/
        'commandGameAward'    => \app\common\command\commandGameAward::class,
        ///** 交易记录分表 **/
        'separateEventBet'    => \app\common\command\separateEventBet::class,
        ///** 赛事预分拣 **/
        'commandGameSelect'    => \app\common\command\commandGameSelect::class,
        ///** 赛事预分拣 **/
        'updateMemberData'    => \app\common\command\update\updateMemberData::class,
    ],
];
