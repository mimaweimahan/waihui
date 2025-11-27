define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/index' + location.search,
                    add_url: 'withdraw/add',
                    edit_url: 'withdraw/edit',
                    del_url: 'withdraw/del',
                    multi_url: 'withdraw/multi',
                    import_url: 'withdraw/import',
                    table: 'withdraw',
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
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('用户ID')},
                        {field: 'user.username', title: __('用户名')},
                        {
                            field: 'type',
                            title: __('Type'),
                            searchList: {"1": __('Type 1'), "2": __('Type 2')},
                            formatter: Table.api.formatter.label
                        },
                        {field: 'amount', title: __('Amount'), operate: 'BETWEEN'},
                        {field: 'amount_received', title: __('Amount_received'), operate: 'BETWEEN'},
                        {field: 'withdrawal_fee', title: __('Withdrawal_fee'), operate: 'BETWEEN'},
                        {
                            field: 'address.address',
                            title: __('Address.address'),
                            operate: 'LIKE',
                        },
                        {
                            field: 'bank.username',
                            title: __('Bank.username'),
                            operate: 'LIKE',
                        },
                        {
                            field: 'bank.bank_name',
                            title: __('Bank.bank_name'),
                            operate: 'LIKE',
                        },
                        {
                            field: 'bank.account',
                            title: __('Bank.account'),
                            operate: 'LIKE',
                        },
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {"0": __('Status 0'), "1": __('Status 1'), "2": __('Status 2')},
                            formatter: Table.api.formatter.label
                        },
                         {field: 'remark', title: __('Remark'), operate: 'BETWEEN'},
                        
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'agree',
                                    title: '同意',
                                    text: '同意',
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: '',
                                    hidden: function (row) {
                                        return row.status > 0;
                                    },
                                    url: function (row, column) {
                                        return 'withdraw/check/status/1/ids/' + row.id;
                                    },
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                },
                                {
                                    name: 'against',
                                    title: '拒绝提现',
                                    text: '拒绝',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-dialog',
                                    icon: '',
                                    hidden: function (row) {
                                        return row.status > 0;
                                    },
                                    url: function (row, column) {
                                        // return 'withdraw/check/status/2/ids/' + row.id;
                                        return 'withdraw/agree?status=2&ids=' + row.id;
                                    },
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                }
                            ],
                            formatter: Table.api.formatter.buttons
                        },
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
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        agree:function () {
            Controller.api.bindevent();
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
