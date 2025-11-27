define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

        var Controller = {
            index: function () {
                // 初始化表格参数配置
                Table.api.init({
                    extend: {
                        index_url: 'product/index' + location.search,
                        add_url: 'product/add',
                        edit_url: 'product/edit',
                        del_url: 'product/del',
                        multi_url: 'product/multi',
                        import_url: 'product/import',
                        table: 'product',
                    }
                });

                var table = $("#table");

                table.on('post-body.bs.table', function () {
                    $(".btn-editone").data("area", ["100%", "100%"]);
                    $(".btn-addone").data("area", ["100%", "100%"]);
                });

                // 初始化表格
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    sortName: 'id',
                    fixedColumns: true,
                    fixedRightNumber: 1,
                    columns: [
                        [
                            {checkbox: true},
                            {field: 'id', title: __('Id')},
                            {field: 'code', title: __('Code'), operate: 'LIKE'},
                            {field: 'symbol', title: __('Symbol'), operate: 'LIKE'},
                            {
                                field: 'title',
                                title: __('Title'),
                                operate: 'LIKE',
                                table: table,
                                class: 'autocontent',
                                formatter: Table.api.formatter.content
                            },
                            {
                                field: 'image',
                                title: __('Image'),
                                operate: false,
                                events: Table.api.events.image,
                                formatter: Table.api.formatter.image
                            },
                            {field: 'min', title: __('Min'), operate: 'BETWEEN'},
                            {field: 'max', title: __('Max'), operate: 'BETWEEN'},
                            {
                                field: 'type',
                                title: __('Type'),
                                searchList: {"1": __('Type 1'), "2": __('Type 2'), "3": __('Type 3')},
                                formatter: Table.api.formatter.label
                            },
                            {
                                field: 'status',
                                title: __('Status'),
                                searchList: {"0": __('Status 0'), "1": __('Status 1')},
                                formatter: Table.api.formatter.toggle
                            },
                            {field: 'open_price', title: __('Open_price'), operate: 'BETWEEN'},
                            {field: 'price_high', title: __('Price_high'), operate: 'BETWEEN'},
                            {field: 'price_low', title: __('Price_low'), operate: 'BETWEEN'},
                            {field: 'price_pre', title: __('Price_pre'), operate: 'BETWEEN'},
                            {field: 'price', title: __('Price'), operate: 'BETWEEN'},
                            {field: 'weight', title: __('Weight')},
                            {
                                field: 'operate',
                                title: __('Operate'),
                                table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate
                            }
                        ]
                    ]
                });

                // 为表格绑定事件
                Table.api.bindevent(table);
            },
            add: function () {
                Controller.api.bindevent();
            }
            ,
            edit: function () {
                Controller.api.bindevent();
            }
            ,
            api: {
                bindevent: function () {
                    Form.api.bindevent($("form[role=form]"));
                }
            }
        };
        return Controller;
    }
)
;
