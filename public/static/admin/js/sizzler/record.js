define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'sizzler.record/index',
        add_url: 'sizzler.record/add',
        edit_url: 'sizzler.record/edit',
        delete_url: 'sizzler.record/delete',
        export_url: 'sizzler.record/export',
        modify_url: 'sizzler.record/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'qishu', width: 150, title: '期号'},
                    {field: 'hero.img', Width: 80, title: '英雄图片', search: false, templet: ea.table.image},
                    {field: 'tournament_name', width: 150, title: '名称'},
                    {field: 'start_time', width: 200, title: '开始时间',templet: ea.table.date},
                    {field: 'end_time', width: 200, title: '结束时间',templet: ea.table.date},
                    {
                        field: 'status',
                        title: '开奖',
                        width: 85,
                        search: 'select',
                        selectList: {1: '开奖', 0: '未开奖'},
                    },
                    {
                        field: 'is_pan',
                        title: '封盘',
                        width: 85,
                        search: 'select',
                        selectList: {1: '封盘', 0: '未封盘'},
                    },
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '查看场次',
                                url: init.viewSessions_url,
                                field: 'id',
                                method: 'open',
                                class: 'layui-btn layui-btn-xs layui-btn-success',
                                extend: 'data-full="true"',
                            }],'edit','delete']
                    }
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