define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'sizzler.sizzler_bet/index',
        add_url: 'sizzler.sizzler_bet/add',
        edit_url: 'sizzler.sizzler_bet/edit',
        delete_url: 'sizzler.sizzler_bet/delete',
        export_url: 'sizzler.sizzler_bet/export',
        modify_url: 'sizzler.sizzler_bet/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'record.qishu', width: 150, title: '期号'},
                    {field: 'money', width: 150, title: '金额'},
                    {field: 'profile.mobile', width: 150, title: '用户手机'},

                    { width: 150, title: '交易内容',search: false,templet: function (data) {
                            return '<span>' + data.rule.title + '</span><br/> <span style="color: red">['+ (data.rule.title.split('')[data.bet - 1]) +  ']</span>'
                        }},

                    {field: 'is_ok', width: 150, title: '中奖',search: 'select',
                        selectList: {0: '未结算', 1: '中奖', 2: '未中奖'},},

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