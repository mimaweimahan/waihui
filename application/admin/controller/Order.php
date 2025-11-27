<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Config;
use think\Db;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{

    protected $noNeedRight = ['setRefresh', 'multiChange'];

    /**
     * Order模型对象
     * @var \app\admin\model\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Order;
        $this->view->assign("directionList", $this->model->getDirectionList());
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->with(['product', 'user'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id', 'user_id','order_sn', 'product_id', 'type', 'direction', 'seconds', 'amount', 'open_price', 'end_price', 'profit_ratio', 'fact_profits', 'status', 'pre_profit_result', 'profit_result', 'createtime', 'handletime']);
                
                $row['amount'] = number_format($row['amount'],2,'.','');
                $row['fact_profits'] = number_format($row['fact_profits'],2,'.','');
                $row->visible(['product']);
                $row->getRelation('product')->visible(['code', 'symbol']);

                $row->visible(['user']);
                $row->getRelation('user')->visible(['username']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }

        $this->view->assign('fresh', Config::where('name', 'order_fresh')->value('value'));
        return $this->view->fetch();
    }


    /**
     * 批量更新
     */
    public function multi($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {

            if ($this->request->has('action') == 'pre_profit_result') {
                $idInfo = explode('_', $ids);
                $this->model->where(array('id' => $idInfo[0]))->setField('pre_profit_result', $idInfo[1]);
                $this->success();
            }

            if ($this->request->has('params')) {
                parse_str($this->request->post("params"), $values);
                $values = array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
                if ($values || $this->auth->isSuperAdmin()) {
                    $adminIds = $this->getDataLimitAdminIds();
                    if (is_array($adminIds)) {
                        $this->model->where($this->dataLimitField, 'in', $adminIds);
                    }
                    $count = 0;
                    Db::startTrans();
                    try {
                        $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
                        foreach ($list as $index => $item) {
                            $count += $item->allowField(true)->isUpdate(true)->save($values);
                        }
                        Db::commit();
                    } catch (PDOException $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    if ($count) {
                        $this->success();
                    } else {
                        $this->error(__('No rows were updated'));
                    }
                } else {
                    $this->error(__('You have no permission'));
                }
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    public function setRefresh()
    {
        $status = Config::where('name', 'order_fresh')->value('value');
        Config::where('name', 'order_fresh')->setField('value', $status ? 0 : 1);
        $this->success();
    }

    /**
     * 批量更新盈亏
     */
    public function multiChange()
    {
        $params = $this->request->param();
        if ($params['ids']) {
            $result = $this->model->where('id', 'in', $params ['ids'])->where('status', 1)->update(['pre_profit_result' => $params ['pre_profit_result']]);
            if ($result) {
                $this->success();
            } else {
                $this->error(__('修改失败'));
            }
        }

        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
