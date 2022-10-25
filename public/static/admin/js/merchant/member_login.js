define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'merchant.member_login/index',
        add_url: 'merchant.member_login/add',
        edit_url: 'merchant.member_login/edit',
        updateMobile_url: 'merchant.member_login/updateMobile',
        delete_url: 'merchant.member_login/delete',
        export_url: 'merchant.member_login/export',
        modify_url: 'merchant.member_login/modify',
        stock_url: 'merchant.member_login/stock',
        charge_url: 'merchant.member_login/charge',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', 'export'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'mid', width: 150, title: '用户ID'},
                    {field: 'address', width: 150, title: '地址'},
                    {field: 'ip', minWidth: 150,title: 'IP'},
                    {field: 'create_time', minWidth: 80, title: '登陆时间', search: 'range'},
                ]],
            });

            ea.listen();
        },
        charge: function () {
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