<?php

namespace plugin\model\app\admin\controller;

use app\Basic;
use app\expose\build\builder\FormBuilder;
use app\expose\build\builder\TableBuilder;
use plugin\control\app\model\PluginChannelsUser;
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
        $formBuilder->add('channels_uid', '渠道', 'select', '', [
            'options' => $channelList,
            'props' => [
                'placeholder' => '渠道搜索',
                'clearable' => true
            ]
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
        $formBuilder->add('model_type', '模型类型', 'select', '', [
            'options' => ModelType::getOptions(),
            'props' => [
                'placeholder' => '模型类型搜索',
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

        $formBuilder->add('model_name', '模型名称(壹定)', 'input', '', [
            'props' => [
                'placeholder' => '模型名称(壹定)搜索',
                'clearable' => true
            ]
        ]);
        $formBuilder->add('scene', '场景', 'select', '', [
            'options' => ModelScene::getOptions(),
            'props' => [
                'placeholder' => '场景搜索',
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
        $builder->add('channels', '渠道', [
            'component' => [
                'name' => 'table-userinfo',
                'props' => [
                    'nickname' => 'channels.nickname',
                    'avatar' => 'channels.headimg',
                    'info' => 'channels.mobile',
                    'tags' => [
                        [
                            'field' => 'channels_uid',
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
        $builder->add('model_name', '模型名称', [
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('model_type', '模型类型', [
            'component' => [
                'name' => 'tag',
                'options' => ModelType::getOptions()
            ],
            'props' => [
                'width' => '120px'
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
        $builder->add('scene', '场景', [
            'component' => [
                'name' => 'tag',
                'options' => ModelScene::getOptions()
            ],
            'props' => [
                'width' => '120px'
            ]
        ]);
        $builder->add('result.image_path', '结果图片', [
            'component' => [
                'name' => 'image',
                'props' => [
                    'style' => 'width:40px;height:40px;'
                ]
            ],
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('result.video_path', '结果视频', [
            'component' => [
                'name' => 'video',
                'props' => [
                    'style' => 'width:40px;height:40px;'
                ]
            ],
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('result.message', '错误消息', [
            'props' => [
                'width' => '200px'
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
        $channels_uid = $request->get('channels_uid');
        if ($channels_uid) {
            $where[] = ['pmt.channels_uid', '=', $channels_uid];
        }
        $model_name = $request->get('model_name');
        if ($model_name) {
            $where[] = ['pm.name', 'like', "%{$model_name}%"];
        }
        $model_type = $request->get('model_type');
        if ($model_type) {
            $where[] = ['pm.model_type', '=', $model_type];
        }
        $scene = $request->get('scene');
        if ($scene) {
            $where[] = ['pm.scene', '=', $scene];
        }
        $status = $request->get('status');
        if ($status) {
            $where[] = ['pmt.status', '=', $status];
        }
        $task_id = $request->get('task_id');
        if ($task_id) {
            $where[] = ['pmt.task_id', '=', $task_id];
        }
        $user_id = $request->get('user_id');
        if ($user_id) {
            $where[] = ['pmt.uid', '=', $user_id];
        }
        $list = PluginModelVoice::alias('pmt')
            ->join('plugin_model pm', 'pmt.model_id = pm.id')
            ->with(['user' => function ($query) {
                $query->field('id,nickname,headimg,mobile,channels_uid');
            }, 'result' => function ($query) {
                $query->field('task_id,result,image,image_path,video_path,message,channels_uid');
            }, 'channels' => function ($query) {
                $query->field('id,nickname,headimg,mobile');
            }])
            ->field('pmt.*,pm.name as model_name')
            ->where($where)
            ->order('pmt.id desc')
            ->paginate($limit);
        return $this->resData($list);
    }
}
