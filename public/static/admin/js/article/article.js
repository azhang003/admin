define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRender',
        index_url: 'article.article/index',
        add_url: 'article.article/add',
        edit_url: 'article.article/edit',
        delete_url: 'article.article/delete',
        modify_url: 'article.article/modify',
        export_url: 'article.article/export'
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
                    {field: 'title', minWidth: 80, title: '标题'},
                    {field: 'image', minWidth: 80, title: '封面', search: false, templet: ea.table.image},
                    {field: 'status', title: '显示状态', width: 100, search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch,tips:'显示|禁用'},
                    // {field: 'top', title: '置顶', width: 100, search: 'select', selectList: {0: '关闭', 1: '置顶'}, templet: ea.table.switch,tips:'置顶|禁用'},
                    {field: 'lang', title: '语言', width: 100, search: 'select', selectList: Lang_identification},
                    {field: 'articleCate.title', title: '类型', width: 85, },
                    // {field: 'start_time', minWidth: 80, title: '显示时间', search: 'range'},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'edit',
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