define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'merchant.member_profile/index',
        add_url: 'merchant.member_profile/add',
        agree_url: 'merchant.member_profile/agree',
        delete_url: 'merchant.member_profile/delete',
        stock_url: 'merchant.member_profile/stock',
        adopt_url: 'merchant.member_profile/adopt',
        modify_url: 'merchant.member_profile/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh' ,[{
                    class: 'layui-btn layui-btn-normal layui-btn-sm',
                    method: 'post',
                    field: 'mid',
                    icon: '',
                    text: '一键通过',
                    title: '确定通过？',
                    auth: 'refuse',
                    url: init.agree_url,
                    extend: "",
                    checkbox:true
                }],],
                cols: [[
                    {type: "checkbox"},
                    {field: 'mid', width: 80, title: 'ID'},
                    {field: 'mobile', minWidth: 80, title: '账户',edit:true, search: false,},
                    {field: 'nickname', minWidth: 80, title: '昵称', search: false,},
                    {field: 'certificate', minWidth: 80, title: '封面', search: false, templet: ea.table.image},
                    {field: 'authens', title: '状态', search: false, width: 85, selectList: {'0': '未实名', "2": '认证中', "1": '正常'}},
                    {field: 'update_time', minWidth: 80, title: '认证时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [[{
                            class: 'layui-btn layui-btn-succes layui-btn-xs',
                            method: 'get',
                            field: 'id',
                            icon: '',
                            text: '通过',
                            title: '确定通过？',
                            auth: 'delete',
                            url: init.adopt_url + '?action=1',
                            // url: init.adopt_url,
                            extend: ""
                        }],[{
                            class: 'layui-btn layui-btn-succes layui-btn-xs',
                            method: 'get',
                            field: 'id',
                            icon: '',
                            text: '拒绝',
                            title: '确定拒绝？',
                            auth: 'delete',
                            url: init.adopt_url + '?action=2',
                            // url: init.adopt_url,
                            extend: ""
                        }],]
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
        stock: function () {
            ea.listen();
        },
    };
    return Controller;
});