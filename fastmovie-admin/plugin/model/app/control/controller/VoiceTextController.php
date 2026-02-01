<?php

namespace plugin\model\app\control\controller;

use app\Basic;
use app\expose\build\builder\FormBuilder;
use app\expose\build\builder\TableBuilder;
use app\expose\enum\Action;
use app\expose\enum\State;
use plugin\model\app\model\PluginModelVoiceText;
use support\Request;

class VoiceTextController extends Basic
{
    public function __construct()
    {
        $this->model = new PluginModelVoiceText();
    }
    public function indexGetTable(Request $request)
    {
        $builder = new TableBuilder();
        $builder->addAction('操作', [
            'width' => '200px',
            'fixed' => 'right'
        ]);
        $builder->addTableAction('编辑', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/model/control/VoiceText/update',
            'props' => [
                'title' => '编辑《{name}》'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'primary',
                    'size' => 'small'
                ]
            ]
        ]);
        $builder->addTableAction('删除', [
            'model' => Action::COMFIRM['value'],
            'path' => '/app/model/control/VoiceText/delete',
            'props' => [
                'type' => 'error',
                'message' => '确定要删除《{name}》吗？',
                'confirmButtonClass' => 'el-button--danger'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'danger',
                    'size' => 'small'
                ]
            ]
        ]);
        $builder->addHeader();
        $builder->addHeaderAction('创建', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/model/control/VoiceText/create',
            'props' => [
                'title' => '创建'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'success'
                ]
            ]
        ]);
        $formBuilder = new FormBuilder(null, null, [
            'inline' => true
        ]);
        $formBuilder->add('text', '文案', 'input', '', [
            'props' => [
                'placeholder' => '文案搜索',
                'clearable' => true
            ]
        ]);
        $builder->addScreen($formBuilder);
        $builder->add('id', 'ID', [
            'props' => [
                'width' => '80px'
            ]
        ]);
        $builder->add('text', '文案', []);
        $builder->add('state', '状态', [
            'component' => [
                'name' => 'switch',
                'api' => '/app/model/control/VoiceText/indexUpdateState',
                'props' => [
                    'active-value' => State::YES['value'],
                    'inactive-value' => State::NO['value']
                ]
            ],
        ]);
        $builder->add('create_time', '时间', [
            'props' => [
                'width' => '200px'
            ],
            'component' => [
                'name' => 'table-times',
                'props' => [
                    'group' => [
                        [
                            'field' => 'create_time',
                            'label' => '创建'
                        ],
                        [
                            'field' => 'update_time',
                            'label' => '更新'
                        ]
                    ]
                ]
            ]
        ]);
        $builder = $builder->builder();
        return $this->resData($builder);
    }
    public function index(Request $request)
    {
        $limit = $request->get('limit', 10);
        $where = [];
        $where[] = ['channels_uid', '=', $request->channels_uid];
        $text = $request->get('text');
        if ($text) {
            $where[] = ['text', 'like', "%{$text}%"];
        }
        $list = PluginModelVoiceText::where($where)->order('id desc')->paginate($limit);
        return $this->resData($list);
    }


    public function create(Request $request)
    {
        if ($request->method() === 'POST') {
            $data = $request->post();
            $data['channels_uid'] = $request->channels_uid;
            $model = new PluginModelVoiceText();
            if ($model->save($data)) {
                return $this->success('创建成功');
            }
            return $this->fail('创建失败');
        }
        $builder = $this->getFormBuilder($request);
        return $this->resData($builder);
    }


    public function update(Request $request)
    {
        if ($request->method() === 'POST') {
            $D = $request->post();
            $model = PluginModelVoiceText::where(['id' => $D['id']])->find();
            if ($model->save($D)) {
                return $this->success('更新成功');
            }
            return $this->fail('更新失败');
        }
        $id = $request->get('id');
        $model = PluginModelVoiceText::where(['id' => $id])->find();
        $builder = $this->getFormBuilder($request);
        $builder->setData($model->toArray());
        return $this->resData($builder);
    }

    public function getFormBuilder(Request $request)
    {
        $formBuilder = new FormBuilder(null, null, [
            'labelPosition' => 'right',
            'label-width' => "200px",
            'class' => 'w-80 mx-auto',
            'size' => 'large'
        ]);
        $formBuilder->add('state', '状态', 'switch',  State::YES['value'], [
            'props' => [
                'active-value' => State::YES['value'],
                'inactive-value' => State::NO['value']
            ]
        ]);
        $formBuilder->add('text', '文案', 'input', '', [
            'required' => true,
            'props' => [
                'type' => 'textarea',
                'autosize' => [
                    'minRows' => 4,
                    'maxRows' => 20
                ],
                'placeholder' => '文案'
            ]
        ]);
        return $formBuilder;
    }

    public function delete(Request $request)
    {
        $id = $request->post('id');
        $model = PluginModelVoiceText::where(['id' => $id, 'channels_uid' => $request->channels_uid])->find();
        if ($model->delete()) {
            return $this->success('删除成功');
        }
        return $this->fail('删除失败');
    }
}
