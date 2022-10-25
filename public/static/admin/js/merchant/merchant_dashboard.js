define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'merchant.merchant_dashboard/index',
        add_url: 'merchant.merchant_dashboard/add',
        edit_url: 'merchant.merchant_dashboard/edit',
        updateMobile_url: 'merchant.merchant_dashboard/updateMobile',
        delete_url: 'merchant.merchant_dashboard/delete',
        export_url: 'merchant.merchant_dashboard/export',
        modify_url: 'merchant.merchant_dashboard/modify',
        stock_url: 'merchant.merchant_dashboard/stock',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', 'export'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    // {field: 'profile.nickname', width: 150, title: '昵称'},
                    {field: 'profile.mobile', width: 150, title: '手机'},
                    {field: 'team_member', minWidth: 150, title: '总用户数'},
                    {field: 'team_valid_member', minWidth: 150, title: '有效用户数', search: 'false'},
                    {field: 'team_money', minWidth: 150, title: '总用户余额（用户账户余额） ', search: 'false'},
                    {field: 'team_profit', minWidth: 150, title: '总盈亏金额', search: 'false'},
                    {field: 'team_recharge', minWidth: 150, title: '总充值金额', search: 'false'},
                    {field: 'team_withdraw', minWidth: 150, title: '总提现金额', search: 'false'},
                    {field: 'team_withdraw_examine', minWidth: 150, title: '用户提现金额（待审核提现）', search: 'false'},
                    {field: 'team_event', minWidth: 150, title: '交易总盈亏', search: 'false'},
                    {field: 'team_sizzler', minWidth: 150, title: '时时乐盈亏', search: 'false'},
                    {field: 'day_recharge', minWidth: 150, title: '今日充值', search: 'false'},
                    {field: 'day_withdraw', minWidth: 150, title: '总充值金额', search: 'false'},
                    {field: 'day_event_money', minWidth: 150, title: '今日比赛总交易金额', search: 'false'},
                    {field: 'day_event_number', minWidth: 150, title: '今日比赛总交易笔数', search: 'false'},
                    {field: 'day_event_award', minWidth: 150, title: '今日比赛总返奖金额', search: 'false'},
                    {field: 'day_event_profit', minWidth: 150, title: '今日比赛盈利金额', search: 'false'},
                    {field: 'day_sizzler_money', minWidth: 150, title: '今日休闲交易金额', search: 'false'},
                    {field: 'day_sizzler_number', minWidth: 150, title: '今日休闲交易笔数', search: 'false'},
                    {field: 'day_sizzler_award', minWidth: 150, title: '今日休闲返奖金额', search: 'false'},
                    {field: 'day_sizzler_profit', minWidth: 150, title: '今日休闲盈利金额', search: 'false'},
                    {field: 'day_event', minWidth: 150, title: '今日交易总盈亏', search: 'false'},
                    {field: 'day_sizzler', minWidth: 150, title: '今日时时乐盈亏', search: 'false'},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'false'},
                    // {
                    //     width: 250,
                    //     title: '操作',
                    //     templet: ea.table.tool,
                    //     operat: ['edit','delete']
                    // }
                ]],
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        stock: function () {
            ea.listen();
        },
    };
    return Controller;
});