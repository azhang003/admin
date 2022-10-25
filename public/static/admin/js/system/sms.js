define(["jquery", "easy-admin"], function ($, ea) {


    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.sms/index',
    };

    var Controller = {
        index: function () {
            var util = layui.util;
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', width: 80, title: 'ID', search: false},
                    {field: 'title', minWidth: 80, title: '请求手机号'},
                    {field: 'code', minWidth: 80, title: '验证码'},
                    {field: 'status', title: '验证码状态', width: 80, selectList: {0: '未使用', 1: '已使用'}, search: false},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                ]],
            });

            ea.listen();
        },
    };

    return Controller;
});