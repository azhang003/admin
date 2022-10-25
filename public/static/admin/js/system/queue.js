define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.queue/index',
        add_url: 'system.queue/add',
        edit_url: 'system.queue/edit',
        adopt_url: 'system.queue/adopt',
        agree_url: 'system.queue/agree',
        refuse_url: 'system.queue/refuse',
        delete_url: 'system.queue/delete',
        export_url: 'system.queue/export',
        modify_url: 'system.queue/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', [{
                    class: 'layui-btn layui-btn-normal layui-btn-sm',
                    method: 'post',
                    field: 'id',
                    icon: '',
                    text: '一键重置',
                    title: '确定重置？',
                    auth: 'refuse',
                    url: init.agree_url,
                    extend: "",
                    checkbox:true
                }], 'export'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'title', width: 80, title: '标题', search: false},
                    {field: 'controller', width: 80, title: '方法', search: false},
                    {field: 'context', width: 80, title: '内容', search: false},
                    {field: 'type', width: 80, title: '状态',search: 'select',
                        selectList: {0: '未重制', 1: '已重制'},},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
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