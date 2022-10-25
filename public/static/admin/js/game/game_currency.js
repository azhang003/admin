define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'game.game_currency/index',
        add_url: 'game.game_currency/add',
        edit_url: 'game.game_currency/edit',
        delete_url: 'game.game_currency/delete',
        export_url: 'game.game_currency/export',
        modify_url: 'game.game_currency/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                // toolbar: ['refresh','add'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', minWidth: 80, title: 'ID'},
                    {field: 'title', minWidth: 80, title: '币种'},
                    {field: 'logo', minWidth: 80, title: 'Logo', search: false, templet: ea.table.image},
                    {field: 'a_odds', width: 100,edit:true, title: '涨赔率'},
                    {field: 'b_odds', width: 100,edit:true, title: '跌赔率'},
                    {field: 'create_time', minWidth: 150, title: '添加时间', search: 'range'},
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