define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'merchant.members/index',
        add_url: 'merchant.members/add',
        edit_url: 'merchant.members/edit',
        updateMobile_url: 'merchant.members/updateMobile',
        delete_url: 'merchant.members/delete',
        export_url: 'merchant.members/export',
        modify_url: 'merchant.members/modify',
        stock_url: 'merchant.members/stock',
        charge_url: 'merchant.member_wallet/charge',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh',
                    'delete',
                    // [{
                    //     auth: 'charge',
                    //     field:'username',
                    //     class: 'layui-btn layui-btn-sm layuimini-btn-primary',
                    //     text: '会员充值',
                    //     title: '会员充值',
                    //     url: init.charge_url,
                    //     icon: 'fa fa-hourglass',
                    //     extend: 'data-table="' + init.table_render_id + '"',
                    // }],
                    'export'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'profile.mid', minWidth: 120, title: '用户ID', search: true,templet:function (data, option){
                            if (data.repeat == 1){
                                return "<div style='background-color: red'><p>" + data.id +"</p></div>"
                            }else {
                                return "<p>" + data.id +"</p>"
                            }
                        }},
                    {field: 'profile.nickname', minWidth: 120, title: '昵称'},
                    {field: 'profile.mobile', minWidth: 120, title: '手机'},
                    {field: 'uuid', minWidth: 120, title: '自身邀请码'},
                    {field: 'level', minWidth: 80, title: '会员级别', search: false,sort:true},
                    {field: 'index.allagent', minWidth: 80, title: '代理', search: false},
                    {field: 'index.agent', minWidth: 80, title: '业务员', search: false},
                    {field: 'index.agent_id', minWidth: 80, title: '上级id', search: false},
                    {field: 'index.team_count', minWidth: 80, title: '团队人数', search: false,sort:true},
                    {field: 'index.win', minWidth: 120, title: '总净赢', search: false,sort:true},
                    {field: 'index.daywin', minWidth: 120, title: '今日净赢', search: false,sort:true},
                    {field: 'index.bet_count', minWidth: 50, title: '游戏局数', search: false,sort:true},
                    {field: 'index.recharge', minWidth: 120, title: '充值', search: false,sort:true},
                    {field: 'index.withdraw', minWidth: 120, title: '提现', search: false,sort:true},
                    {field: 'index.Transfer_out', minWidth: 120, title: '转出', search: false,sort:true},
                    {field: 'index.into', minWidth: 120, title: '转入', search: false,sort:true},
                    {field: 'index.freeze', minWidth: 120, title: '冻结金额', search: false,sort:true},
                    {field: 'index.fee', minWidth: 120, title: '手续费', search: false,sort:true},
                    {field: 'index.share', minWidth: 120, title: '分享收益', search: false,sort:true},
                    {field: 'index.ming', minWidth: 120, title: '挖矿收益', search: false,sort:true},
                    {field: 'index.register_ip', minWidth: 120, title: '注册IP', search: false,templet:function (data, option){
                            return "<p>" + data.register_ip +"</p><p>" + data.register_address +"</p>"
                        }},
                    {field: 'index.login_ip', minWidth: 120, title: '在线IP', search: false,templet:function (data, option){
                        if (data.repeat == 1){
                            return "<div style='background-color: red'><p>" + data.login_ip +"</p><p>" + data.login_address +"</p></div>"
                        }else {
                            return "<p>" + data.login_ip +"</p><p>" + data.login_address +"</p>"
                        }
                    }},
                    {field: 'wallet.cny', minWidth: 120, title: '账户余额',symbol:"￥", templet: ea.table.money, search: false,sort:true},
                    {field: 'status', title: '状态', minWidth: 80, selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch,tips:'禁用|启用'},
                    // {field: 'type', title: '关注', minWidth: 80,  templet: ea.table.switch,tips:'正常用户|关注用户', search: false},
                    {field: 'authen', title: '认证状态', minWidth: 80, selectList: {0: '未实名', 1: '正常'}},
                    {field: 'analog', title: '是否模拟号', minWidth: 80, selectList: {0: '真实用户', 1: '模拟号'}},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range',sort:true},
                    {
                        minWidth: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: ['edit','delete']
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