define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'sizzler.rule/index',
        add_url: 'sizzler.rule/add',
        edit_url: 'sizzler.rule/edit',
        delete_url: 'sizzler.rule/delete',
        export_url: 'sizzler.rule/export',
        modify_url: 'sizzler.rule/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'title', width: 150, title: '名称',edit:true},
                    {field: 'a_obbs', width: 150, title: 'A队赔率',edit:true},
                    {field: 'b_obbs', width: 150, title: 'B队赔率',edit:true},
                    {
                        field: 'status',
                        title: '开启',
                        width: 120,
                        search: 'select',
                        selectList: {1: '开启', 2: '关闭'},
                        templet: ea.table.switch

                    },
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