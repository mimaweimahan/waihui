define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'recharge/index' + location.search,
                    add_url: 'recharge/add',
                    edit_url: 'recharge/edit',
                    del_url: 'recharge/del',
                    multi_url: 'recharge/multi',
                    import_url: 'recharge/import',
                    table: 'recharge',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('编号')},
                        // {field: 'order_sn', title: __('订单编号')},
                        {field: 'user_id', title: __('用户ID')},
                        {field: 'user.username', title: __('User.username')},
                        {field: 'amount', title: __('Amount'), operate: 'BETWEEN'},
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {"0": __('Status 0'), "1": __('Status 1'), "2": __('Status 2')},
                            formatter: Table.api.formatter.label
                        },
                        // {
                        //     field: 'image',
                        //     title: __('Image'),
                        //     operate: false,
                        //     events: Table.api.events.image,
                        //     formatter: Table.api.formatter.image
                        // },
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'updatetime',
                            title: __('Updatetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'agree',
                                    title: '同意',
                                    text:'同意',
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: '',
                                    hidden: function(row){
                                        return row.status > 0;
                                    },
                                    url: function (row, column) {
                                        return 'recharge/check/status/1/ids/' + row.id;
                                    },
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                },
                                {
                                    name: 'against',
                                    title: '拒绝',
                                    text:'拒绝',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: '',
                                    hidden: function(row){
                                        return row.status > 0;
                                    },
                                    url: function (row, column) {
                                        return 'recharge/check/status/2/ids/' + row.id;
                                    },
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                }
                            ],
                            formatter: Table.api.formatter.buttons
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
