define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.payment/index',
        add_url: 'system.payment/add',
        edit_url: 'system.payment/edit',
        delete_url: 'system.payment/delete',
        export_url: 'system.payment/export',
        modify_url: 'system.payment/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'image', minWidth: 120, title: '图标', search: false, templet: ea.table.image},
                    {field: 'sort', minWidth: 80, title: '排序',edit:true,search: false},
                    {field: 'title', minWidth: 120, title: '类型'},
                    {field: 'name', minWidth: 120, title: '收款人'},
                    {field: 'back_title', minWidth: 120, title: '开户行'},
                    {field: 'rate', minWidth: 120, title: '赔率'},
                    {field: 'address', minWidth: 120, title: '账号'},
                    {field: 'code', minWidth: 120, title: '二维码', search: false, templet: ea.table.image},
                    {
                        field: 'status',
                        title: '状态',
                        width: 85,
                        search: 'select',
                        selectList: {0: '禁用', 1: '正常'},
                        templet: ea.table.switch
                    },
                    {field: 'remark', minWidth: 200, title: '备注',search: false},
                    {field: 'create_time', minWidth: 150, title: '创建时间', search: 'range' ,search: false},
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