define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'merchant.member_dashboard/index',
        add_url: 'merchant.member_dashboard/add',
        edit_url: 'merchant.member_dashboard/edit',
        updateMobile_url: 'merchant.member_dashboard/updateMobile',
        delete_url: 'merchant.member_dashboard/delete',
        export_url: 'merchant.member_dashboard/export',
        modify_url: 'merchant.member_dashboard/modify',
        stock_url: 'merchant.member_dashboard/stock',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', 'export'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'profile.nickname', width: 150, title: '昵称'},
                    {field: 'profile.mobile', width: 150, title: '手机'},
                    {field: 'withdraw_address', minWidth: 150,edit:true, title: '提现地址'},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
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