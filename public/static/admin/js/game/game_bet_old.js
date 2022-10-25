define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'game.game_bet_old/index',
        add_url: 'game.game_bet_old/add',
        edit_url: 'game.game_bet_old/edit',
        delete_url: 'game.game_bet_old/delete',
        export_url: 'game.game_bet_old/export',
        modify_url: 'game.game_bet_old/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'gameList.id', width: 100, title: '场次id',},
                    {field: 'gameList.title', width: 100, title: '期数',},
                    {field: 'mid', width: 100, title: '用户ID',},
                    {field: 'profile.mobile', width: 150, title: '用户手机'},
                    {field: 'gameList.type', width: 100, title: '类型',},
                    {field: 'money', width: 100, title: '金额',sort:true},
                    {field: 'bet', width: 150, title: '交易', search: 'select', selectList: {1:"涨",2:"跌"}},
                    {
                        field: 'is_ok', width: 150, title: '是否中奖', search: 'select',
                        selectList: {2: '未中奖', 1: '中奖', 0: '未开奖'}, templet: function (data, option) { 
                            return '<span style=" color:' + (data[option.field] > 0 ? (data[option.field] == 1 ?`red`:`blue`) : `block`) + '">' + option.selectList[data[option.field]] + '</span>'
                        }
                    },
                    {field: 'price', minWidth: 150, title: '实时余额', search: false,sort:true},
                    {field: 'game_count', minWidth: 150, title: '每日（游戏局数）', search: false},
                    {field: 'create_time', minWidth: 150, title: '交易时间', search: 'range'},
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