<?php

namespace plugin\model\app\control\controller;

use app\Basic;
use app\expose\build\builder\FormBuilder;
use app\expose\build\builder\TableBuilder;
use app\expose\enum\Action;
use app\expose\enum\State;
use app\expose\enum\Week;
use plugin\control\utils\yidevs\Yidevs;
use plugin\model\app\model\PluginModel;
use plugin\model\app\model\PluginModelTask;
use plugin\model\utils\enum\ModelScene;
use plugin\model\utils\enum\ModelTaskStatus;
use plugin\model\utils\enum\ModelType;
use support\Request;

class TaskController extends Basic
{
    public function __construct()
    {
        $this->model = new PluginModelTask();
    }

    public function indexGetTable(Request $request)
    {
        $builder = new TableBuilder();
        $formBuilder = new FormBuilder(null, null, [
            'inline' => true
        ]);
        $builder->addTableAction('重试', [
            'model' => Action::COMFIRM['value'],
            'path' => '/app/model/control/Task/retry',
            'where' => [
                ['retry', '=', 1]
            ],
            'props' => [
                'type' => 'warning',
                'message' => '确定要重试《{task_id}》任务吗？',
                'confirmButtonClass' => 'el-button--danger'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'warning',
                    'size' => 'small'
                ]
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
            'options' => ModelTaskStatus::getOptions(),
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
                'options' => ModelTaskStatus::getOptions()
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
        $builder->add('result.image_path', '图片', [
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
        $builder->add('result.video_path', '视频', [
            'component' => [
                'name' => 'link',
                'props' => [
                    'target' => '_blank'
                ]
            ],
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('result.audio_path', '音频', [
            'component' => [
                'name' => 'link',
                'props' => [
                    'target' => '_blank'
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
        $where[] = ['pmt.channels_uid', '=', $request->channels_uid];
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
        $list = PluginModelTask::alias('pmt')
            ->join('plugin_model pm', 'pmt.model_id = pm.id')
            ->with(['user' => function ($query) {
                $query->field('id,nickname,headimg,mobile,channels_uid');
            }, 'result' => function ($query) {
                $query->field('task_id,result,image,image_path,video_path,message,channels_uid');
            }])
            ->field('pmt.*,pm.name as model_name')
            ->where($where)
            ->order('pmt.id desc')
            ->paginate($limit)->each(function ($item) {
                $item->retry = 0;
                if (in_array($item->status, [ModelTaskStatus::PROCESSING['value'], ModelTaskStatus::DOWNLOADING['value'], ModelTaskStatus::UPLOADING['value']]) && $item->update_time < date('Y-m-d H:i:s', strtotime('-30 minutes'))) {
                    $item->retry = 1;
                }
            });
        return $this->resData($list);
    }
    public function retry(Request $request)
    {
        $id = $request->post('id');
        $PluginModelTask = PluginModelTask::where('id', $id)->find();
        if (!$PluginModelTask) {
            return $this->fail('任务不存在');
        }
        switch ($PluginModelTask->status) {
            case ModelTaskStatus::PROCESSING['value']:
                $PluginModelTask->status = ModelTaskStatus::WAIT['value'];
                break;
            case ModelTaskStatus::DOWNLOADING['value']:
            case ModelTaskStatus::UPLOADING['value']:
                $PluginModelTask->status = ModelTaskStatus::WAIT_DOWNLOAD['value'];
                break;
        }
        $PluginModelTask->save();
        return $this->success('重试成功');
    }
}
