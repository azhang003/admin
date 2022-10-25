define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'merchant.merchant/index',
        add_url: 'merchant.merchant/add',
        edit_url: 'merchant.merchant/edit',
        delete_url: 'merchant.merchant/delete',
        export_url: 'merchant.merchant/export',
        modify_url: 'merchant.merchant/modify',
        charge_url: 'merchant.merchant/charge',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh','add',[{
                    auth: 'charge',
                    field:'username',
                    class: 'layui-btn layui-btn-sm layuimini-btn-primary',
                    text: '代理充值',
                    title: '代理充值',
                    url: init.charge_url,
                    icon: 'fa fa-hourglass',
                    extend: 'data-table="' + init.table_render_id + '"',
                }], 'export'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'profile.mobile', minWidth: 200, title: '业务员'},
                    {field: 'uuid', minWidth: 80, title: '邀请码'},
                    {
                        field: 'agent',
                        title: '代理类型',
                        width: 120,
                        search: 'select',
                        selectList: {0: '总代理', 1: '二级代理', 2: '二级代理', 3: '二级代理'},
                        // templet: ea.table.switch
                    },
                    {field: 'index.user', minWidth: 80, title: '下级人数',search: false},
                    {field: 'index.game', minWidth: 80, title: '游戏局数',search: false},
                    {field: 'index.win', minWidth: 80, title: '净赢',search: false},
                    {field: 'index.recharge', minWidth: 80, title: '充值',search: false},
                    {field: 'index.recharge_member', minWidth: 80, title: '充值人数',search: false},
                    {field: 'index.withdraw', minWidth: 80, title: '提现',search: false},
                    {field: 'index.withdraw_member', minWidth: 80, title: '提现人数',search: false},
                    {field: 'index.all_share', minWidth: 80, title: '总收益',search: false},
                    {field: 'index.in_share', minWidth: 80, title: '已领收益',search: false},
                    {field: 'index.surplus_share', minWidth: 80, title: '剩余收益',search: false},
                    {field: 'index.ming', minWidth: 80, title: '挖矿',search: false},
                    {field: 'index.transfer', minWidth: 80, title: '转出',search: false},
                    {field: 'index.into', minWidth: 80, title: '内部转入',search: false},
                    {
                        field: 'status',
                        title: '状态',
                        width: 85,
                        search: 'select',
                        selectList: {0: '冻结', 1: '正常'},
                        templet: ea.table.switch
                    },
                    {field: 'create_time', minWidth: 150, title: '创建时间', search: 'range'},
                    {width: 250, title: '操作', templet: ea.table.tool}
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
    };
    return Controller;
});