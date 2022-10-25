define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRender',
        index_url: 'activity.bet/index',
        add_url: 'activity.bet/add',
        edit_url: 'activity.bet/edit',
        delete_url: 'activity.bet/delete',
        modify_url: 'activity.bet/modify',
        export_url: 'activity.bet/export'
    };

    var Controller = {

        index: function () {
            layui.use(['form'], function(){
                var form = layui.form
                    ,layer = layui.layer
                //监听指定开关
                form.on('switch(switchTest)', function(data){
                    if(this.checked){
                        layer.msg('开关checked：'+ (this.checked ? 'true' : 'false'), {
                            offset: '6px'
                        });
                        layer.tips('温馨提示：请注意开关状态的文字可以随意定义，而不仅 仅是ON|OFF', data.othis)
                    }else{
                        layer.msg('开关： 关掉了', {
                            offset: '6px'
                        });
                    }
                    //do some ajax opeartiopns;
                });
            });
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'ActivityList.title', minWidth: 80, title: '标题'},
                    {field: 'money', minWidth: 80, title: '金额'},
                    {field: 'profile.mobile', minWidth: 80, title: '领取人'},
                    // {field: 'ActivityList.type', title: '活动类型', minWidth: 85, search: 'select', selectList: {0: '充值', 1: '签到', 2: '真实交易次数', 3: '真实交易天数', 4: '模拟交易次数', 5: '模拟交易天数', 6: '邀请用户'}},
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