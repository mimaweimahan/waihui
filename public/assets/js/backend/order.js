define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    import_url: 'order/import',
                    table: 'order',
                }
            });

            var table = $("#table");

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
                        // {field: 'id', title: __('Id')},
                        {field: 'order_sn', title: __('订单编号')},
                        {field: 'user_id', title: __('用户ID')},
                        {field: 'user.username', title: __('User.username')},
                        {field: 'product.symbol', title: __('Product.symbol')},
                        {field: 'amount', title: __('Amount'), operate:'BETWEEN'},
                        {field: 'fact_profits', title: __('Fact_profits'), operate:'BETWEEN'},
                        {field: 'direction', title: __('Direction'), searchList: {"1":__('Direction 1'),"2":__('Direction 2')}, formatter: Table.api.formatter.label},

                        // {field: 'seconds', title: __('Seconds')},
                        // {field: 'open_price', title: __('Open_price'), operate:'BETWEEN'},
                        // {field: 'end_price', title: __('End_price'), operate:'BETWEEN'},
                        // {field: 'profit_ratio', title: __('Profit_ratio'), operate:'BETWEEN'},
                        // {field: 'status', title: __('Status')},
                        {field: 'pre_profit_result', title: __('Pre_profit_result'),formatter: Controller.api.formatter.pre_set},
                        {
                            field: 'type',
                            title: __('Type'),
                            searchList: {"0": __('Type 0'), "1": __('Type 1')},
                            formatter: Table.api.formatter.label
                        },
                        // {field: 'profit_result', title: __('Profit_result')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'handletime', title: __('Handletime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            var fresh = '';

            if ($("#auto_refresh").attr('data-status') == 1) {
                $("#auto_refresh").html('取消自动刷新');
                fresh = setInterval(function () {
                    $(".btn-refresh").trigger("click");
                }, 20000);
            } else {

            }

            // 启动和暂停按钮
            $(document).on("click", ".btn-start", function () {
                var status = $("#auto_refresh").attr('data-status');

                if (status == 1) {
                    clearInterval(fresh);
                    $("#auto_refresh").html('开启自动刷新');

                } else {
                    fresh = setInterval(function () {
                        $(".btn-refresh").trigger("click");
                    }, 20000);
                    $("#auto_refresh").html('取消自动刷新');
                }
                $("#auto_refresh").attr('data-status', status == 1 ? 0 : 1);
                Table.api.multi("status", status == 1 ? 0 : 1, table, this);
            });


            // 启动和暂停按钮
            $(document).on("change", ".pre_set_btn", function () {
                var pre_profit_result = $(this).val();
                var ids = $(this).attr('data-id');
                Table.api.multi("pre_profit_result", ids + '_' + pre_profit_result, table, this);
            });

            var submitForm = function (ids, pre_profit_result, layero) {
                Fast.api.ajax({
                    url: Fast.api.fixurl('order/multiChange'),
                    data: {ids: ids, pre_profit_result: pre_profit_result},
                    dataType: 'json',
                    success: function (data) {
                        $(".layui-layer-btn a", layero).removeClass("layui-layer-btn0");
                        Layer.closeAll();
                        $('.btn-refresh').trigger("click");
                        if (data.code == 1) {
                            Layer.msg('操作成功');
                        } else{
                            Layer.msg(data.msg);
                        }
                    },
                })
            };

            $(document).on("click", ".btn-selected", function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm("请选择操作选项", {
                    title: '批量修改',
                    btn: ["默认", "赢", "亏", "取消"],
                    success: function (layero, index) {
                        $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
                    },
                    yes: function (index, layero) {
                        submitForm(ids.join(","), 0, layero);
                    },
                    btn2: function (index, layero) {
                        submitForm(ids.join(","), 1, layero);
                    },
                    btn3: function (index, layero) {
                        submitForm(ids.join(","), 2, layero);
                    },
                    btn4: function (index, layero) {
                        Layer.closeAll();
                    }
                })
            });
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
            },
            formatter: {
                pre_set: function (value, row, index) {

                    if (row.status == 1) {
                        var str = '<select class="pre_set_btn" data-id="' + row.id + '">' +
                            '<option value="0"' + (value == 0 ? 'selected' : '') + '>默认</option>' +
                            '<option value="1"' + (value == 1 ? 'selected' : '') + '>赢</option>' +
                            '<option value="2"' + (value == 2 ? 'selected' : '') + '>输</option>' +
                            '</select>';
                        return str;
                    } else {
                        return '<small class="label bg-red">已平仓</small>';
                    }

                },
            }
        }
    };
    return Controller;
});
