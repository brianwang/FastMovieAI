<?php

namespace app;

use app\expose\trait\Json;
use support\Request;

class Basic
{
    protected $model;
    /**
     * 允许通过 indexUpdateField 更新的字段白名单
     * 子类可覆盖此属性扩展允许字段
     */
    protected $allowedUpdateFields = ['state', 'sort', 'remarks', 'title', 'name'];

    /**
     * 允许通过 indexUpdateState 更新的字段白名单
     */
    protected $allowedStateFields = ['state'];

    use Json;
    /**
     * 更新指定字段
     * @method POST
     */
    public function indexUpdateField(Request $request)
    {
        $id = $request->post('id');
        $field = $request->post('field');
        $value = $request->post('value');
        if (!in_array($field, $this->allowedUpdateFields)) {
            return $this->fail('不允许更新该字段');
        }
        $model = $this->model->where(['id' => $id])->find();
        if (!$model) {
            return $this->fail('数据不存在');
        }
        $model->{$field} = $value;
        if ($model->save()) {
            return $this->success();
        }
        return $this->fail('操作失败');
    }
    /**
     * 更新状态字段
     * @method POST
     */
    public function indexUpdateState(Request $request)
    {
        $id = $request->post('id');
        $field = $request->post('field');
        $value = $request->post('value');
        if (!in_array($field, $this->allowedStateFields)) {
            return $this->fail('不允许更新该字段');
        }
        $model = $this->model->where(['id' => $id])->find();
        if (!$model) {
            return $this->fail('数据不存在');
        }
        $model->{$field} = $value;
        if ($model->save()) {
            return $this->success();
        }
        return $this->fail('操作失败');
    }
}
