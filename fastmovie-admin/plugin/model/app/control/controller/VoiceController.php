<?php

namespace plugin\model\app\control\controller;

use app\Basic;
use app\expose\build\builder\FormBuilder;
use app\expose\build\builder\TableBuilder;
use plugin\control\app\model\PluginChannelsUser;
use plugin\model\app\model\PluginModel;
use plugin\model\app\model\PluginModelVoice;
use plugin\model\utils\enum\ModelScene;
use plugin\model\utils\enum\ModelVoiceStatus;
use plugin\model\utils\enum\ModelType;
use support\Request;

class VoiceController extends Basic
{
    public function __construct()
    {
        $this->model = new PluginModelVoice();
    }

    public function indexGetTable(Request $request)
    {
        $builder = new TableBuilder();
        $channelList = PluginChannelsUser::options();
        $formBuilder = new FormBuilder(null, null, [
            'inline' => true
        ]);
        $formBuilder->add('task_id', '任务ID', 'input', '', [
            'props' => [
                'placeholder' => '任务ID搜索',
            ]
        ]);
        $formBuilder->add('user_id', '用户ID', 'input', '', [
            'props' => [
                'placeholder' => '用户ID搜索',
            ]
        ]);
        $formBuilder->add('model_id', '模型', 'select', '', [
            'options' => PluginModel::options(['scene' => ModelScene::DIALOGUE_VOICE['value']]),
            'props' => [
                'placeholder' => '模型搜索',
                'clearable' => true
            ]
        ]);
        $formBuilder->add('status', '状态', 'select', '', [
            'options' => ModelVoiceStatus::getOptions(),
            'props' => [
                'placeholder' => '状态搜索',
                'clearable' => true
            ]
        ]);
        $builder->addScreen($formBuilder);
        // $builder->addAction('操作', [
        //     'width' => '200px',
        //     'fixed' => 'right'
        // ]);
        $builder->add('id', 'ID', [
            'props' => [
                'width' => '100px'
            ]
        ]);
        $builder->add('user', '用户', [
            'where' => [
                ['uid', '!=', null]
            ],
            'component' => [
                'name' => 'table-userinfo',
                'props' => [
                    'nickname' => 'user.nickname',
                    'avatar' => 'user.headimg',
                    'info' => 'user.mobile',
                    'tags' => [
                        [
                            'field' => 'uid',
                            'props' => [
                                'type' => 'success',
                                'size' => 'small'
                            ]
                        ]
                    ],
                ]
            ],
            'props' => [
                'width' => '300px'
            ]
        ]);
        $builder->add('task_id', '任务ID', [
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('model.name', '模型名称', [
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('status', '状态', [
            'component' => [
                'name' => 'tag',
                'options' => ModelVoiceStatus::getOptions()
            ],
            'props' => [
                'width' => '120px'
            ]
        ]);
        $builder->add('message', '错误消息', [
            'props' => [
                'minWidth' => '200px'
            ],
            'component' => [
                'name' => 'text',
                'props' => [
                    'type' => 'danger'
                ]
            ]
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
        $model_id = $request->get('model_id');
        if ($model_id) {
            $where[] = ['model_id', '=', $model_id];
        }
        $status = $request->get('status');
        if ($status) {
            $where[] = ['status', '=', $status];
        }
        $task_id = $request->get('task_id');
        if ($task_id) {
            $where[] = ['task_id', '=', $task_id];
        }
        $user_id = $request->get('user_id');
        if ($user_id) {
            $where[] = ['uid', '=', $user_id];
        }
        $list = PluginModelVoice::where($where)
            ->with(['user' => function ($query) {
                $query->field('id,nickname,headimg,mobile,channels_uid');
            }, 'model' => function ($query) {
                $query->field('id,name');
            }])
            ->order('id desc')
            ->paginate($limit);
        return $this->resData($list);
    }
}
