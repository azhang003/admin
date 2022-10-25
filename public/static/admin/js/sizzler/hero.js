define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'sizzler.hero/index',
        add_url: 'sizzler.hero/add',
        edit_url: 'sizzler.hero/edit',
        delete_url: 'sizzler.hero/delete',
        export_url: 'sizzler.hero/export',
        modify_url: 'sizzler.hero/modify',
    };

    var Controller = {
        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'img', Width: 80, title: '英雄图片', search: false, templet: ea.table.image},
                    {field: 'name', width: 150, title: '名称'},
                    {field: 'bianhao', width: 150, title: '编号'},
                    {
                        field: 'sex',
                        title: '男女',
                        width: 85,
                        search: 'select',
                        selectList: {1: '男', 2: '女'},
                    },
                    {
                        field: 'mofa',
                        title: '魔法',
                        width: 85,
                        search: 'select',
                        selectList: {1: '有', 2: '无'},
                    },
                    {
                        field: 'gongji',
                        title: '攻击',
                        width: 85,
                        search: 'select',
                        selectList: {1: '有', 2: '无'},
                    },
                    {
                        field: 'danshuang',
                        title: '单双',
                        width: 85,
                        search: 'select',
                        selectList: {1: '单', 2: '双'},
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