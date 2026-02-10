<?php

namespace plugin\marketing\app\control\controller;

use app\Basic;
use app\expose\build\builder\ActionBuilder;
use app\expose\build\builder\ComponentBuilder;
use app\expose\build\builder\FormBuilder;
use app\expose\build\builder\TableBuilder;
use app\expose\enum\Action;
use app\expose\enum\State;
use plugin\marketing\app\model\PluginMarketingPoints;
use plugin\shortplay\utils\enum\StyleClassify;
use support\Request;

class PointsController extends Basic
{
    public function __construct()
    {
        $this->model = new PluginMarketingPoints();
    }
    public function indexGetTable(Request $request)
    {
        $builder = new TableBuilder;
        $builder->addAction('操作', [
            'width' => '180px',
            'fixed' => 'right'
        ]);
        $builder->addTableAction('编辑', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/marketing/control/Points/update',
            'props' => [
                'title' => '编辑《ID：{id}》积分套餐'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'primary',
                    'size' => 'small'
                ]
            ]
        ]);
        $builder->addHeader();
        $builder->addHeaderAction('创建积分套餐', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/marketing/control/Points/create',
            'props' => [
                'title' => '创建积分套餐'
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
        $formBuilder->add('name', '名称', 'input', '', [
            'props' => [
                'placeholder' => '名称搜索',
                'clearable' => true
            ]
        ]);
        $builder->addScreen($formBuilder);
        $builder->add('id', 'ID', [
            'props' => [
                'width' => '100px'
            ]
        ]);
        $builder->add('name', '名称', [
            'props' => [
                'width' => '150px'
            ]
        ]);
        $builder->add('price', '价格', []);
        $builder->add('original_price', '原价', []);
        $builder->add('points', '积分', []);
        $builder->add('give', '操作次数', []);
        $builder->add('state', '状态', [
            'component' => [
                'name' => 'switch',
                'api' => '/app/marketing/control/Points/indexUpdateState',
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
        $name = $request->get('name');
        if ($name) {
            $where[] = ['name', 'like', "%{$name}%"];
        }
        $classify = $request->get('classify');
        if ($classify) {
            $where[] = ['classify', '=', $classify];
        }
        $list = PluginMarketingPoints::where($where)
            ->order('id desc')->paginate($limit)->each(function ($item) {});
        return $this->resData($list);
    }
    public function create(Request $request)
    {
        if ($request->method() === 'POST') {
            $D = $request->post();
            try {
                $D['channels_uid'] = $request->channels_uid;
                $PluginMarketingPoints = new PluginMarketingPoints;
                $PluginMarketingPoints->save($D);
            } catch (\Throwable $th) {
                return $this->exception($th);
            }
            return $this->success('创建成功');
        }
        $builder = $this->getFormBuilder();
        return $this->resData($builder);
    }
    public function update(Request $request)
    {
        if ($request->method() === 'POST') {
            $D = $request->post();
            try {
                $PluginMarketingPoints = PluginMarketingPoints::where(['id' => $D['id'], 'channels_uid' => $request->channels_uid])->find();
                $PluginMarketingPoints->save($D);
            } catch (\Throwable $th) {
                return $this->exception($th);
            }
            return $this->success('更新成功');
        }
        $id = $request->get('id');
        $Style = PluginMarketingPoints::where(['id' => $id, 'channels_uid' => $request->channels_uid])->find();
        $Style->discount =(int)$Style->discount > 0 ? 1 : 0; 
        $builder = $this->getFormBuilder();
        $builder->setData($Style->toArray());
        return $this->resData($builder);
    }
    private function getFormBuilder()
    {
        $builder = new FormBuilder(null, null, [
            'translations' => true,
            'size' => 'large',
        ]);
        $builder->add('name', '名称', 'input', '', [
            'required' => true,
            'maxlength' => 30,
            'show-word-limit' => true,
        ]);
        $builder->add('desc', '描述', 'input', '', [
            'required' => true,
        ]);
        $builder->add('price', '价格', 'input-number', '', [
            'required' => true,
            'props' => [
                'controls' => false
            ]
        ]);
        $builder->add('original_price', '原价', 'input-number', '', [
            'required' => true,
            'props' => [
                'controls' => false
            ]
        ]);
        $builder->add('points', '积分', 'input-number', '', [
            'required' => true,
            'props' => [
                'controls' => false
            ]
        ]);
        $builder->add('give', '赠送积分', 'input-number', '', [
            'required' => true,
            'props' => [
                'controls' => false
            ]
        ]);
        $builder->add('state', '状态', 'radio', State::YES['value'], [
            'options' => State::getOptions(),
            'subProps' => [
                'border' => true
            ],
            'required' => true,
        ]);
        $builder->add('discount', '是否优惠', 'radio', 1, [
            'options' => [
                [
                    'label' => '优惠',
                    'value' => '1'
                ],
                [
                    'label' => '无',
                    'value' => '0'
                ]
            ],
            'subProps' => [
                'border' => true
            ],
            'required' => true,
        ]);
        return $builder;
    }

    public function delete(Request $request)
    {
        $id = $request->get('id');
        $PluginMarketingPoints = PluginMarketingPoints::where(['id' => $id, 'channels_uid' => $request->channels_uid])->find();
        if (!$PluginMarketingPoints) {
            return $this->fail('积分套餐不存在');
        }
        if ($PluginMarketingPoints->delete()) {
            return $this->success('删除成功');
        }
        return $this->fail('删除失败');
    }
}
