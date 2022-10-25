define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'merchant.member_record/index',
        add_url: 'merchant.member_record/add',
        edit_url: 'merchant.member_record/edit',
        updateMobile_url: 'merchant.member_record/updateMobile',
        delete_url: 'merchant.member_record/delete',
        export_url: 'merchant.member_record/export',
        modify_url: 'merchant.member_record/modify',
        stock_url: 'merchant.member_record/stock',
        charge_url: 'merchant.member_record/charge',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh',],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'mid', width: 80, title: '用户ID'},
                    {field: 'profile.nickname', width: 150, title: '昵称',search: false},
                    {field: 'profile.mobile', width: 150, title: '手机',search: false},
                    {field: 'business', width: 150, title: '业务', search: 'select', selectList: {1:"充值",2:'提现',3:'投注',4:'开奖',5:'提现退回',6:'后台充值',9:'团队返利',
                        10:'转入',11:'转出',12:'挖矿',13:'领取团队收益',14:'领取挖矿收益',15:'手续费'}},
                    {field: 'time', width: 150, title: '投注类型（只有投注开奖体现）'},
                    {field: 'now', width: 150, title: '操作金额',templet:ea.table.money,sort:true},
                    {field: 'after', width: 150, title: '当时余额',templet:ea.table.money,sort:true},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                ]],
            });

            ea.listen();
        },
        charge: function () {
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