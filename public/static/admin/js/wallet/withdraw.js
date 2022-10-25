define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'wallet.withdraw/index',
        add_url: 'wallet.withdraw/add',
        edit_url: 'wallet.withdraw/edit',
        adopt_url: 'wallet.withdraw/adopt',
        agree_url: 'wallet.withdraw/agree',
        turn_url: 'wallet.withdraw/turn',
        refuse_url: 'wallet.withdraw/refuse',
        delete_url: 'wallet.withdraw/delete',
        export_url: 'wallet.withdraw/export',
        modify_url: 'wallet.withdraw/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', [{
                    class: 'layui-btn layui-btn-normal layui-btn-sm',
                    method: 'post',
                    field: 'id',
                    icon: '',
                    text: '一键通过',
                    title: '确定通过？',
                    auth: 'refuse',
                    url: init.agree_url,
                    extend: "",
                    checkbox:true
                }], [{
                    class: 'layui-btn layui-btn-normal layui-btn-sm',
                    method: 'post',
                    field: 'id',
                    icon: '',
                    text: '一键拒绝',
                    title: '确定拒绝？',
                    auth: 'refuse',
                    url: init.turn_url,
                    extend: "",
                    checkbox:true
                }], 'export'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'mid', width: 80, title: '用户ID'},
                    {field: 'index.allagent', width: 80, title: '代理', search: false},
                    {field: 'index.agent', width: 80, title: '业务员', search: false},
                    {field: 'old_money', width: 80, title: '提现前余额',sort:true,search: false},
                    {field: 'now_money', width: 80, title: '提现后余额',sort:true,search: false},
                    {field: 'money', width: 80, title: '订单金额',sort:true},
                    // {field: 'money', width: 150, title: '订单金额', templet: ea.table.money},
                    {field: 'fee', width: 80, title: '手续费',symbol:"￥", templet: ea.table.money, search: false},
                    // {field: 'confirm', width: 80, title: '审核人'},
                    {field: 'wallet.cny', width: 80, title: '余额',sort:true},
                    {field: 'win', width: 80, title: '净赢',sort:true, search: false,templet:function (data, option){
                            if (data.win > "0"){
                                return "<p>+ " + data.win.toString().substring(0,7) +"</p>"
                            }else {
                                return "<p>" + data.win.toString().substring(0,7) +"</p>"
                            }
                        }},
                    {field: 'share', width: 80, title: '佣金收益', search: false,sort:true},
                    {field: 'image', width: 80, title: '上传照片', templet: ea.table.image, search: false},
                    {field: 'profile.certificate', width: 80, title: '认证照片', templet: ea.table.image, search: false},
                    {field: 'rid', width: 80, title: '哈希'},
                    {field: 'register_ip', width: 80, title: '注册IP-地址', search: false},
                    {field: 'account.login_ip', width: 80, title: '在线IP-地址', search: false},
                    {field: 'account.status', title: '账户状态', width: 80, selectList: {0: '禁用', 1: '启用'}, search: false},
                    {field: 'address', width: 80, title: '地址', search: false},
                    // {field: 'payment.name', width: 150, title: '收款人'},
                    // {field: 'payment.title', width: 150, title: '开户行'},
                    {field: 'paymaddre', width: 80, title: '提款地址', search: false},
                    {field: 'profile.mobile', width: 80, title: '用户手机'},
                    {field: 'examine', width: 80, title: '审核状态',search: 'select',
                        selectList: {0: '未审核', 1: '通过', 2: '拒绝'},},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
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
                            // url: init.edit_url + '?action=1',
                            url: init.adopt_url,
                            extend: ""
                        }], [{
                            class: 'layui-btn layui-btn-succes layui-btn-xs',
                            method: 'get',
                            field: 'id',
                            icon: '',
                            text: '拒绝',
                            title: '确定拒绝？',
                            auth: 'delete',
                            url: init.refuse_url,
                            extend: ""
                        }], 'delete']
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