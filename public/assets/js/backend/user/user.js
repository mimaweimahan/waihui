define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'editable'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: "money",
                                    text: "上下分",
                                    icon: "fa fa-cart-plus",
                                    classname: "btn btn-warning btn-xs btn-detail btn-dialog",
                                    url: "user/user/money",
                                },
                                {
                                    name: "money_log",
                                    text: "账变",
                                    icon: "fa fa-cart-plus",
                                    extend: 'data-area=\'["100%","100%"]\'',
                                    classname: "btn btn-warning btn-xs btn-detail btn-dialog",
                                    url: function (row, value) {
                                        return "user/user/money_log?user_id=" + row.id;
                                    },
                                },
                            ],
                            formatter: Table.api.formatter.operate
                        },
                        {field: 'id', title: __('Id'), sortable: true},
                        // {field: 'group.name', title: __('Group')},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        // {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        // {field: 'email', title: __('Email'), operate: 'LIKE'},
                        // {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        // {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        // {field: 'level', title: __('Level'), operate: 'BETWEEN', sortable: true},
                        // {field: 'gender', title: __('Gender'), visible: false, searchList: {1: __('Male'), 0: __('Female')}},
                        {field: 'money', title: __('Money'), operate: 'BETWEEN'},
                        {
                            field: 'remark',
                            title: __('Remark'),
                            operate: false
                        },
                        {
                            field: "risk",
                            title: "会员风控",
                            editable: {
                                type: "select",
                                pk: 1,
                                disabled: false,
                                source: [
                                    {
                                        value: "0",
                                        text: "默认"
                                    },
                                    {
                                        value: "1",
                                        text: "赢"
                                    },
                                    {
                                        value: "2",
                                        text: "亏"
                                    },
                                ],
                            },
                            searchList: {
                                0: "默认",
                                1: "赢",
                                2: "亏"
                            },
                        },
                        {
                            field: 'want_risk',
                            title: __('委买单控制'),
                            editable: {
                                type: "select",
                                pk: 1,
                                disabled: false,
                                source: [
                                    {
                                        value: "0",
                                        text: "默认"
                                    },
                                    {
                                        value: "1",
                                        text: "赢"
                                    },
                                    {
                                        value: "2",
                                        text: "亏"
                                    },
                                ],
                            },
                            searchList: {
                                0: "默认",
                                1: "赢",
                                2: "亏"
                            },
                        },
                        {
                            field: 'real_name',
                            title: __('真实姓名'),
                            operate: false
                        },
                        {
                            field: 'country',
                            title: __('国籍'),
                            operate: false
                        },
                        {
                            field: 'id_card',
                            title: __('身份证号码'),
                            operate: false
                        },
                        {
                            field: 'id_card_images',
                            title: __('身份证'),
                            events: Table.api.events.image,
                            formatter: Table.api.formatter.images,
                            operate: false
                        },
                        {
                            field: 'status',
                            title: __('Status'),
                            formatter: Table.api.formatter.toggle,
                            yes: "normal",
                            no: "hidden",
                            searchList: {normal: __('Normal'), hidden: __('Hidden')}
                        },
                        {
                            field: 'fund_status',
                            title: __('Fund_status'),
                            formatter: Table.api.formatter.toggle,
                            searchList: {"0": __('Fund_status 0'), "1": __('Fund_status 1')}
                        },
                        {
                            field: 'safe_status',
                            title: __('实名认证'),
                            editable: {
                                type: "select",
                                pk: 1,
                                disabled: false,
                                source: [
                                    {
                                        value: "0",
                                        text: "待提交"
                                    },
                                    {
                                        value: "1",
                                        text: "待审核"
                                    },
                                    {
                                        value: "2",
                                        text: "通过"
                                    },
                                ],
                            },
                            searchList: {"0": __('Safe_status 0'), "1": __('Safe_status 1'), "2": __('Safe_status 2')},
                        },
                        // {field: 'score', title: __('Score'), operate: 'BETWEEN', sortable: true},
                        // {field: 'successions', title: __('Successions'), visible: false, operate: 'BETWEEN', sortable: true},
                        // {field: 'maxsuccessions', title: __('Maxsuccessions'), visible: false, operate: 'BETWEEN', sortable: true},
                        {
                            field: 'logintime',
                            title: __('Logintime'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search},
                        {
                            field: 'jointime',
                            title: __('Jointime'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {field: 'joinip', title: __('Joinip'), formatter: Table.api.formatter.search},
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
        money: function () {
            Controller.api.bindevent();
        },
        money_log: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/money_log',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'money_log',
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
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'money', title: __('变动金额'), operate: 'BETWEEN'},
                        {field: 'before', title: __('变动前'), operate: 'BETWEEN'},
                        {field: 'after', title: __('变动后'), operate: 'BETWEEN'},
                        {
                            field: 'type',
                            title: __('变动类型'),
                            searchList: {
                                1: "充值",
                                2: "提现",
                                3: "下单",
                                4: "平仓",
                            },
                            formatter: Table.api.formatter.label,
                        },
                        {field: 'memo', title: __('备注'), operate: false},
                        {
                            field: 'createtime',
                            title: __('变动时间'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                    ]
                ],
                queryParams: function (params) {
                    //这里可以追加搜索条件
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以动态赋值，比如从URL中获取admin_id的值，filter.admin_id=Fast.api.query('admin_id');
                    if (Config.user_id > 0) {
                        filter.user_id = Config.user_id;
                        op.user_id = "=";
                    }

                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                },
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});