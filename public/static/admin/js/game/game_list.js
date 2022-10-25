define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'game.game_list/index',
        edit_url: 'game.game_list/edit',
        delete_url: 'game.game_list/delete',
        export_url: 'game.game_list/export',
        modify_url: 'game.game_list/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', minWidth: 75, title: 'ID'},
                    {field: 'title', minWidth: 75, title: '期数'},
                    {field: 'gameCurrery.title', minWidth: 150, title: '币种类型'},
                    {field: 'type', width: 150, title: '游戏类型', search: 'select', selectList: {0:"1分钟",2:"5分钟"}},
                    {field: 'open', width: 150, title: '开奖结果（含预设）', search: 'select', selectList: {1:"涨",0:"未开奖",2:"跌"}},
                    {field: 'open_profile', width: 150, title: '单期盈亏（中奖-下注）', search: false},
                    {field: 'begin_time', minWidth: 200, title: '开始时间', search: 'range', templet: ea.table.date},
                    {field: 'end_time', minWidth: 200, title: '结束时间', search: 'range', templet: ea.table.date},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '预设开奖',
                                url: init.edit_url,
                                field: 'id',
                                method: 'open',
                                class: 'layui-btn layui-btn-xs layui-btn-success',
                                extend: 'data-full="true"',
                            }],'delete']
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