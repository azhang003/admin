define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'activity.cate/index',
        add_url: 'activity.cate/add',
        edit_url: 'activity.cate/edit',
        delete_url: 'activity.cate/delete',
        export_url: 'activity.cate/export',
        modify_url: 'activity.cate/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'title', minWidth: 80, title: '活动名称'},
                    {field: 'type', title: '活动类型', minWidth: 85, search: 'select', selectList: {0: '充值', 1: '签到', 2: '真实交易次数', 3: '真实交易天数', 4: '模拟交易次数', 5: '模拟交易天数', 6: '邀请用户'}},
                    {field: 'must', minWidth: 80,edit:true, title: '必须次数'},
                    {field: 'number', minWidth: 80,edit:true, title: '奖励原力豆数量'},
                    {field: 'frequency', minWidth: 80,edit:true, title: '可领取次数（0为无限次）'},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'delete'
                        ]
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