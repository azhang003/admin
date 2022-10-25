define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'wallet.recharge/index',
        add_url: 'wallet.recharge/add',
        edit_url: 'wallet.recharge/edit',
        adopt_url: 'wallet.recharge/adopt',
        refuse_url: 'wallet.recharge/refuse',
        delete_url: 'wallet.recharge/delete',
        export_url: 'wallet.recharge/export',
        modify_url: 'wallet.recharge/modify',
        collectss_url: 'wallet.recharge/collectss',
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
                    auth: 'delete',
                    url: init.refuse_url,
                    extend: "",
                    checkbox:true
                }],
                //      [{
                //     class: 'layui-btn layui-btn-normal layui-btn-sm',
                //     method: 'post',
                //     icon: '',
                //     text: '手动归集',
                //     title: '确定归集？',
                //     auth: 'delete',
                //     url: init.collectss_url,
                //     extend: "",
                // }],
                    'export'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'mid', width: 80, title: '用户ID'},
                    {field: 'allagent.mobile', width: 150, title: '代理', search: false},
                    {field: 'agent.mobile', width: 150, title: '业务员', search: false},
                    {field: 'number', width: 150, title: '对应平台币金额',symbol:"￥",sort:true, templet: ea.table.money},
                    {field: 'profile.nickname', width: 150, title: '用户昵称'},
                    {field: 'wallet.cny', width: 150, title: '用户余额',sort:true},
                    // {field: 'payment.title', width: 150, title: '支付方式'},
                    // {field: 'image', Width: 80, title: '付款截图', search: false, templet: ea.table.image},
                    {field: 'profile.mobile', width: 150, title: '用户手机'},
                    {field: 'hash_id', width: 150, title: '哈希'},
                    {field: 'register_ip', width: 150, title: '注册IP', search: false},
                    {field: 'account.login_ip', width: 150, title: '在线IP', search: false},
                    {field: 'address', width: 150, title: '地址'},
                    {field: 'account.authen', title: '用户状态', minWidth: 85, selectList: {0: '未实名', 1: '正常'}},
                    {field: 'status', width: 150, title: '充值状态',search: 'select',
                        selectList: {0: '不满足充值条件', 1: '成功到账'},},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                        //     [{
                        //     class: 'layui-btn layui-btn-succes layui-btn-xs',
                        //     method: 'get',
                        //     field: 'id',
                        //     icon: '',
                        //     text: '通过',
                        //     title: '确定通过？',
                        //     auth: 'delete',
                        //     // url: init.edit_url + '?action=1',
                        //     url: init.adopt_url,
                        //     extend: ""
                        // }], [{
                        //     class: 'layui-btn layui-btn-succes layui-btn-xs',
                        //     method: 'get',
                        //     field: 'id',
                        //     icon: '',
                        //     text: '拒绝',
                        //     title: '确定拒绝？',
                        //     auth: 'delete',
                        //     url: init.refuse_url,
                        //     extend: ""
                        // }],
                            'delete']
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