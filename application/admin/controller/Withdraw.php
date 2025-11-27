<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\User;
use think\Db;

/**
 * 提现管理
 *
 * @icon fa fa-circle-o
 */
class Withdraw extends Backend
{

    /**
     * Withdraw模型对象
     * @var \app\admin\model\Withdraw
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Withdraw;
        $this->view->assign("statusList", $this->model->getStatusList());
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
                ->with(['user', 'address', 'bank'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $row) {
                
                $row['amount'] = number_format($row['amount'],2,'.','');
                $row['amount_received'] = number_format($row['amount_received'],2,'.','');
                $row['withdrawal_fee'] = number_format($row['withdrawal_fee'],2,'.','');
                
            //     $row->visible(['id', 'user_id', 'type', 'amount', 'amount_received', 'withdrawal_fee', 'fee', 'status', 'image', 'createtime', 'updatetime']);

            //     $row->visible(['user']);
            //     $row->getRelation('user')->visible(['username']);

            //     $row->visible(['address']);
            //     $row->getRelation('address')->visible(['address']);

            //     $row->visible(['bank']);
            //     $row->getRelation('bank')->visible(['username,bank_name,account']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    
        /**
     * 拒绝操作
     * @param $ids
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function agree($ids = null)
    {

        if (request()->isPost()) {
            $result = input();
            $params=[
                'status'=>$result['status'],
                'ids'=>$result['ids'],
                'remark'=>$result['row']['remark']
            ];
            $row = $this->model->get($params['ids']);
            if($row){
                if($params){
                    $result = false;
                    Db::startTrans();
                    try {
                        if ($params['status'] > 0) {
                            if ($params['status'] == 2) {
                                //User::money($row['id'],$row ['amount'], $row ['user_id'], 2, '提现拒绝退回',$params['remark']);
                                User::money($row ['amount'], $row ['user_id'], 2, '提现拒绝退回',$params['remark'],$row['id']);
                            }
                        }
                        $result = $row->allowField(true)->save($params);

                        Db::commit();
                    } catch (ValidateException $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    } catch (PDOException $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error(__('No rows were updated'));
                    }
                }
                $this->error(__('Parameter %s can not be empty', ''));
            }
        }
        return $this->fetch();
    }

    /**
     * 审核
     */
    public function check($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($row) {
            $params = $this->request->param();
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {

                    if ($params['status'] > 0) {
                        if ($params['status'] == 2) {
                            User::money($row ['amount'], $row ['user_id'], 2, '提现拒绝退回');
                        }
                    }


                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
