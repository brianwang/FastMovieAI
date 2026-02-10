<?php

namespace plugin\model\app\control\controller;

use app\Basic;
use app\expose\build\builder\ComponentBuilder;
use app\expose\build\builder\FormBuilder;
use app\expose\build\builder\TableBuilder;
use app\expose\enum\Action;
use app\expose\enum\State;
use app\expose\enum\Week;
use plugin\control\utils\yidevs\Yidevs;
use plugin\model\app\model\PluginModel;
use plugin\model\utils\enum\ModelPointType;
use plugin\model\utils\enum\ModelScene;
use plugin\model\utils\enum\ModelType;
use support\Request;

class ModelController extends Basic
{
    public function __construct()
    {
        $this->model = new PluginModel();
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
            'path' => '/app/model/control/Model/update',
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
            'path' => '/app/model/control/Model/delete',
            'where' => [
                ['is_system', '!=', 1]
            ],
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
            'path' => '/app/model/control/Model/create',
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
        $builder->addHeaderAction('初始化', [
            'model' => Action::COMFIRM['value'],
            'path' => '/app/model/control/Model/init',
            'props' => [
                'title' => '初始化'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'primary'
                ]
            ]
        ]);
        $formBuilder = new FormBuilder(null, null, [
            'inline' => true
        ]);
        $formBuilder->add('name', '模型名称', 'input', '', [
            'props' => [
                'placeholder' => '模型名称搜索',
                'clearable' => true
            ]
        ]);
        $formBuilder->add('model_type', '模型类型', 'select', '', [
            'options' => ModelType::getOptions(),
            'props' => [
                'placeholder' => '模型类型搜索',
                'clearable' => true
            ]
        ]);
        $formBuilder->add('model_name', '模型名称(壹定)', 'input', '', [
            'props' => [
                'placeholder' => '模型名称(壹定)搜索',
                'clearable' => true
            ]
        ]);
        $formBuilder->add('assistant_name', '助手名称', 'input', '', [
            'props' => [
                'placeholder' => '助手名称搜索',
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
        $builder->add('id', 'ID', [
            'props' => [
                'width' => '80px'
            ]
        ]);
        $builder->add('icon', '模型图标', [
            'props' => [
                'width' => '100px'
            ],
            'component' => [
                'name' => 'image',
                'props' => [
                    'style' => 'width:40px;height:40px;'
                ]
            ]
        ]);
        $builder->add('name', '模型名称（本地）', [
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
                'width' => '140px'
            ]
        ]);
        $builder->add('model_name', '模型名称(壹定)', [
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('assistant_name', '助手名称', [
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('sort', '排序（小到大）', [
            'props' => [
                'width' => '130px'
            ]
        ]);
        $builder->add('point', '积分', [
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('scene', '场景', [
            'component' => [
                'name' => 'tag',
                'options' => ModelScene::getOptions()
            ],
            'props' => [
                'width' => '200px'
            ]
        ]);
        $builder->add('state', '状态', [
            'component' => [
                'name' => 'switch',
                'api' => '/app/model/control/Model/indexUpdateState',
                'props' => [
                    'active-value' => State::YES['value'],
                    'inactive-value' => State::NO['value']
                ]
            ],
        ]);
        $builder->add('description', '描述', [
            'props' => [
                'minWidth' => '300px'
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
        $name = $request->get('name');
        if ($name) {
            $where[] = ['name', 'like', "%{$name}%"];
        }
        $model_name = $request->get('model_name');
        if ($model_name) {
            $where[] = ['model_name', 'like', "%{$model_name}%"];
        }
        $assistant_name = $request->get('assistant_name');
        if ($assistant_name) {
            $where[] = ['assistant_name', 'like', "%{$assistant_name}%"];
        }
        $scene = $request->get('scene');
        if ($scene) {
            $where[] = ['scene', '=', $scene];
        }
        $model_type = $request->get('model_type');
        if ($model_type) {
            $where[] = ['model_type', '=', $model_type];
        }
        $list = PluginModel::where($where)->order('id desc')->paginate($limit);
        return $this->resData($list);
    }


    public function create(Request $request)
    {
        if ($request->method() === 'POST') {
            $D = $request->post();
            $D['channels_uid'] = $request->channels_uid;
            $model = new PluginModel();
            $model_type = $D['model_type'];
            switch ($model_type) {
                case ModelType::CHAT['value']:
                    $ydModel = Yidevs::ChatModels($request->channels_uid, ['model_id' => $D['model_id']]);
                    $ydAssistant = Yidevs::ChatAssistantlist($request->channels_uid, ['assistant_id' => $D['assistant_id']]);
                    break;
                case ModelType::DRAW['value']:
                    $ydModel = Yidevs::DrawModels($request->channels_uid, ['model_id' => $D['model_id']]);
                    $ydAssistant = Yidevs::DrawAssistantlist($request->channels_uid, ['assistant_id' => $D['assistant_id']]);
                    break;
                case ModelType::TOVIDEO['value']:
                    $ydModel = Yidevs::VideoModels($request->channels_uid, ['model_id' => $D['model_id']]);
                    $ydAssistant = Yidevs::VideoAssistantlist($request->channels_uid, ['assistant_id' => $D['assistant_id']]);
                    break;
                case ModelType::AUDIO['value']:
                    $ydModel = Yidevs::AudioModels($request->channels_uid, ['model_id' => $D['model_id']]);
                    $ydAssistant = Yidevs::AudioAssistantlist($request->channels_uid, ['assistant_id' => $D['assistant_id']]);
                    break;
            }
            if (!isset($ydModel[0])) {
                return $this->fail('模型不存在');
            }
            if ($ydModel[0]['model_id'] != $D['model_id']) {
                return $this->fail('模型不存在');
            }
            if (!isset($ydAssistant[0])) {
                return $this->fail('助手不存在');
            }
            if ($ydAssistant[0]['assistant_id'] != $D['assistant_id']) {
                return $this->fail('助手不存在');
            }
            $modelSelected = $ydModel[0];
            $D['model_name'] = $modelSelected['name'];
            if (!empty($modelSelected['form'])) {
                $D['form'] = $modelSelected['form'];
            }
            $assistantSelected = $ydAssistant[0];
            $D['assistant_name'] = $assistantSelected['name'];
            if ($model->save($D)) {
                return $this->success('创建成功');
            }
            return $this->fail('创建失败');
        }
        $builder = $this->getFormBuilder($request);
        $builder->addValue('id', null);
        return $this->resData($builder);
    }


    public function update(Request $request)
    {
        if ($request->method() === 'POST') {
            $D = $request->post();
            $model = PluginModel::where(['id' => $D['id']])->find();
            $model_type = $D['model_type'];
            switch ($model_type) {
                case ModelType::CHAT['value']:
                    $ydModel = Yidevs::ChatModels($request->channels_uid, ['model_id' => $D['model_id']]);
                    $ydAssistant = Yidevs::ChatAssistantlist($request->channels_uid, ['assistant_id' => $D['assistant_id']]);
                    break;
                case ModelType::DRAW['value']:
                    $ydModel = Yidevs::DrawModels($request->channels_uid, ['model_id' => $D['model_id']]);
                    $ydAssistant = Yidevs::DrawAssistantlist($request->channels_uid, ['assistant_id' => $D['assistant_id']]);
                    break;
                case ModelType::TOVIDEO['value']:
                    $ydModel = Yidevs::VideoModels($request->channels_uid, ['model_id' => $D['model_id']]);
                    $ydAssistant = Yidevs::VideoAssistantlist($request->channels_uid, ['assistant_id' => $D['assistant_id']]);
                    break;
                case ModelType::AUDIO['value']:
                    $ydModel = Yidevs::AudioModels($request->channels_uid, ['model_id' => $D['model_id']]);
                    $ydAssistant = Yidevs::AudioAssistantlist($request->channels_uid, ['assistant_id' => $D['assistant_id']]);
                    break;
            }
            if (!isset($ydModel[0])) {
                return $this->fail('模型不存在');
            }
            if ($ydModel[0]['model_id'] != $D['model_id']) {
                return $this->fail('模型不存在');
            }
            if (!isset($ydAssistant[0])) {
                return $this->fail('助手不存在');
            }
            if ($ydAssistant[0]['assistant_id'] != $D['assistant_id']) {
                return $this->fail('助手不存在');
            }
            $modelSelected = $ydModel[0];
            $D['model_name'] = $modelSelected['name'];
            if (!empty($modelSelected['form'])) {
                $D['form'] = $modelSelected['form'];
            }
            $assistantSelected = $ydAssistant[0];
            $D['assistant_name'] = $assistantSelected['name'];
            if ($model->save($D)) {
                return $this->success('更新成功');
            }
            return $this->fail('更新失败');
        }
        $id = $request->get('id');
        $model = PluginModel::where(['id' => $id])->find();
        $builder = $this->getFormBuilder($request);
        $builder->setData($model->toArray());
        return $this->resData($builder);
    }

    public function getFormBuilder(Request $request)
    {
        $Component = new ComponentBuilder();
        $formBuilder = new FormBuilder(null, null, [
            'labelPosition' => 'right',
            'label-width' => "200px",
            'class' => 'w-80 mx-auto',
            'size' => 'large'
        ]);
        $formBuilder->add('icon', '模型图标', 'bundle', '', [
            'required' => true,
            'props' => [
                'accept' => 'image/jpeg,image/png,image/jpg',
                'multiple' => 1,
                'size' => 2
            ]
        ]);
        $formBuilder->add('name', '模型名称', 'input', '', [
            'required' => true,
            'props' => [
                'placeholder' => '模型名称'
            ]
        ]);
        $formBuilder->add('model_type', '模型类别', 'radio', ModelType::CHAT['value'], [
            'required' => true,
            'options' => ModelType::getOptions(),
            'subProps' => [
                'border' => true
            ],
            'where' => [
                ['id', 'null', null]
            ]
        ]);
        # 对话模型
        $ydModel = Yidevs::ChatModels($request->channels_uid);
        $modelList = $assistantList = [];
        foreach ($ydModel as $model) {
            $modelList[] = [
                'value' => $model['model_id'],
                'tips' => $model['integral'] . $model['integral_unit'],
                'label' => $model['name']
            ];
        }
        $ydAssistant = Yidevs::ChatAssistantlist($request->channels_uid);
        foreach ($ydAssistant as $assistant) {
            $assistantList[] = [
                'value' => $assistant['assistant_id'],
                'tips' => $assistant['integral'] . $assistant['integral_unit'],
                'label' => $assistant['name']
            ];
        }
        $formBuilder->add('model_id', '绑定模型', 'select', '', [
            'required' => true,
            'options' => $modelList,
            'where' => [
                ['model_type', '=', ModelType::CHAT['value']]
            ],
            'props' => [
                'filterable' => true
            ]
        ]);
        $formBuilder->add('assistant_id', '绑定助手', 'select', '', [
            'required' => true,
            'options' => $assistantList,
            'where' => [
                ['model_type', '=', ModelType::CHAT['value']]
            ],
            'props' => [
                'filterable' => true
            ]
        ]);
        # 绘图模型
        $ydModel = Yidevs::DrawModels($request->channels_uid);
        $modelList = $assistantList = [];
        foreach ($ydModel as $model) {
            $modelList[] = [
                'value' => $model['model_id'],
                'tips' => $model['integral'] . $model['integral_unit'],
                'label' => $model['name']
            ];
        }
        $ydAssistant = Yidevs::DrawAssistantlist($request->channels_uid);
        foreach ($ydAssistant as $assistant) {
            $assistantList[] = [
                'value' => $assistant['assistant_id'],
                'tips' => $assistant['integral'] . $assistant['integral_unit'],
                'label' => $assistant['name']
            ];
        }
        $formBuilder->add('model_id', '绑定模型', 'select', '', [
            'required' => true,
            'options' => $modelList,
            'where' => [
                ['model_type', '=', ModelType::DRAW['value']]
            ],
            'props' => [
                'filterable' => true
            ]
        ]);
        $formBuilder->add('assistant_id', '绑定助手', 'select', '', [
            'required' => true,
            'options' => $assistantList,
            'where' => [
                ['model_type', '=', ModelType::DRAW['value']]
            ],
            'props' => [
                'filterable' => true
            ]
        ]);
        # 视频模型
        $ydModel = Yidevs::VideoModels($request->channels_uid);
        $modelList = $assistantList = [];
        foreach ($ydModel as $model) {
            $modelList[] = [
                'value' => $model['model_id'],
                'tips' => $model['integral'] . $model['integral_unit'],
                'label' => $model['name']
            ];
        }
        $ydAssistant = Yidevs::VideoAssistantlist($request->channels_uid);
        foreach ($ydAssistant as $assistant) {
            $assistantList[] = [
                'value' => $assistant['assistant_id'],
                'tips' => $assistant['integral'] . $assistant['integral_unit'],
                'label' => $assistant['name']
            ];
        }
        $formBuilder->add('model_id', '绑定模型', 'select', '', [
            'required' => true,
            'options' => $modelList,
            'where' => [
                ['model_type', '=', ModelType::TOVIDEO['value']]
            ],
            'props' => [
                'filterable' => true
            ]
        ]);
        $formBuilder->add('assistant_id', '绑定助手', 'select', '', [
            'required' => true,
            'options' => $assistantList,
            'where' => [
                ['model_type', '=', ModelType::TOVIDEO['value']]
            ],
            'props' => [
                'filterable' => true
            ]
        ]);
        # 音频模型
        $ydModel = Yidevs::AudioModels($request->channels_uid);
        $modelList = $assistantList = [];
        foreach ($ydModel as $model) {
            $modelList[] = [
                'value' => $model['model_id'],
                'tips' => $model['integral'] . $model['integral_unit'],
                'label' => $model['name']
            ];
        }
        $ydAssistant = Yidevs::AudioAssistantlist($request->channels_uid);
        foreach ($ydAssistant as $assistant) {
            $assistantList[] = [
                'value' => $assistant['assistant_id'],
                'tips' => $assistant['integral'] . $assistant['integral_unit'],
                'label' => $assistant['name']
            ];
        }
        $formBuilder->add('model_id', '绑定模型', 'select', '', [
            'required' => true,
            'options' => $modelList,
            'where' => [
                ['model_type', '=', ModelType::AUDIO['value']]
            ],
            'props' => [
                'filterable' => true
            ]
        ]);
        $formBuilder->add('assistant_id', '绑定助手', 'select', '', [
            'required' => true,
            'options' => $assistantList,
            'where' => [
                ['model_type', '=', ModelType::AUDIO['value']]
            ],
            'props' => [
                'filterable' => true
            ]
        ]);

        $formBuilder->add('state', '状态', 'switch',  State::YES['value'], [
            'props' => [
                'active-value' => State::YES['value'],
                'inactive-value' => State::NO['value']
            ]
        ]);
        $formBuilder->add('point', '扣除数量', 'input-number', '', [
            'required' => true,
        ]);
        $formBuilder->add('point_type', '计费类型', 'radio', ModelPointType::TIMES['value'], [
            'col' => [
                'xs' => 24,
                'sm' => 12,
                'md' => 16,
                'lg' => 18,
                'xl' => 20
            ],
            'required' => true,
            'options' => ModelPointType::getOptions(),
            'prompt' => [
                $Component->add('text', ['default' => '计费类型'], ['type' => 'info', 'size' => 'small'])
                    ->add('text', ['default' => ModelPointType::FIELD_RULE['label'] . ':通过表单中，组件选择器、单选框、多选框的值中的基础点数和倍数计算点数'], ['type' => 'info', 'size' => 'small'])
                    ->builder()
            ],
            'subProps' => [
                'border' => true
            ]
        ]);
        $formBuilder->add('scene', '场景', 'select', '', [
            'required' => true,
            'options' => ModelScene::getOptions()
        ]);
        $formBuilder->add('sort', '排序（小到大）', 'input-number', '', [
            'required' => true,
            'props' => [
                'placeholder' => '排序（小到大）'
            ]
        ]);
        $formBuilder->add('description', '描述', 'input', '', [
            'required' => true,
            'props' => [
                'type' => 'textarea',
                'autosize' => [
                    'minRows' => 4,
                    'maxRows' => 20
                ],
                'placeholder' => '描述'
            ]
        ]);
        return $formBuilder;
    }

    public function delete(Request $request)
    {
        $id = $request->post('id');
        $model = PluginModel::where(['id' => $id])->find();
        if ($model->delete()) {
            return $this->success('删除成功');
        }
        return $this->fail('删除失败');
    }

    /**
     * 初始化数据方法
     * 从 JSON 文件读取数据并写入数据库
     * 
     * @param Request $request
     * @return \support\Response
     */
    public function init(Request $request)
    {
        // 从请求中获取 uid（channels_uid）
        $uid = $request->channels_uid;
        if (empty($uid)) {
            return $this->fail('缺少渠道参数');
        }

        // JSON 文件路径（放在 plugin/model 目录下）
        $jsonFile = base_path('plugin/model/modal.json');

        // 检查文件是否存在
        if (!file_exists($jsonFile)) {
            return $this->fail('初始化数据文件不存在：' . $jsonFile);
        }

        // 读取 JSON 文件内容
        $jsonContent = file_get_contents($jsonFile);
        if ($jsonContent === false) {
            return $this->fail('读取初始化数据文件失败');
        }

        // 解析 JSON 数据
        $dataList = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->fail('JSON 数据格式错误：' . json_last_error_msg());
        }

        // 确保是数组格式
        if (!is_array($dataList)) {
            return $this->fail('JSON 数据格式错误：必须是数组格式');
        }

        // 定义需要排除的字段（id 和 channels_uid）
        $excludeFields = ['id', 'channels_uid'];

        // 定义允许的字段（除了 id 和 channels_uid 之外的所有字段）
        $allowedFields = [
            'name',
            'icon',
            'model_type',
            'model_id',
            'model_name',
            'assistant_id',
            'assistant_name',
            'scene',
            'point',
            'state',
            'sort',
            'description',
            'create_time',
            'update_time'
        ];

        $successCount = 0;
        $failCount = 0;
        $skipCount = 0;
        $errors = [];

        // 遍历数据并插入数据库
        foreach ($dataList as $index => $data) {
            try {
                // 过滤掉不需要的字段
                $insertData = [];
                foreach ($allowedFields as $field) {
                    if (isset($data[$field])) {
                        $insertData[$field] = $data[$field];
                    }
                }

                // 检查必填字段
                if (!isset($insertData['model_id']) || !isset($insertData['assistant_id']) || !isset($insertData['scene'])) {
                    $failCount++;
                    $errors[] = "第 " . ($index + 1) . " 条数据缺少必填字段（model_id、assistant_id、scene）";
                    continue;
                }

                // 设置 channels_uid
                $insertData['channels_uid'] = $uid;

                // 检查是否存在相同的数据（model_id、assistant_id、scene、channels_uid）
                $exists = PluginModel::where([
                    'model_id' => $insertData['model_id'],
                    'assistant_id' => $insertData['assistant_id'],
                    'scene' => $insertData['scene'],
                    'channels_uid' => $uid
                ])->find();

                // 如果存在相同数据，跳过
                if ($exists) {
                    $skipCount++;
                    continue;
                }

                // 如果没有设置 create_time，则使用当前时间
                if (!isset($insertData['create_time'])) {
                    $insertData['create_time'] = date('Y-m-d H:i:s');
                }

                // 如果没有设置 update_time，则使用当前时间
                if (!isset($insertData['update_time'])) {
                    $insertData['update_time'] = date('Y-m-d H:i:s');
                }

                // 如果没有设置 state，则默认为 1
                if (!isset($insertData['state'])) {
                    $insertData['state'] = 1;
                }

                // 如果没有设置 sort，则默认为 0
                if (!isset($insertData['sort'])) {
                    $insertData['sort'] = 0;
                }

                // 创建模型实例并保存
                $model = new PluginModel();
                if ($model->save($insertData)) {
                    $successCount++;
                } else {
                    $failCount++;
                    $errors[] = "第 " . ($index + 1) . " 条数据保存失败";
                }
            } catch (\Exception $e) {
                $failCount++;
                $errors[] = "第 " . ($index + 1) . " 条数据保存失败：" . $e->getMessage();
            }
        }

        // 返回结果
        $message = "初始化完成，成功：{$successCount} 条";
        if ($skipCount > 0) {
            $message .= "，跳过：{$skipCount} 条（已存在相同数据）";
        }
        if ($failCount > 0) {
            $message .= "，失败：{$failCount} 条";
            if (!empty($errors)) {
                $message .= "。" . implode('; ', array_slice($errors, 0, 5)); // 只显示前5个错误
                if (count($errors) > 5) {
                    $message .= "（还有 " . (count($errors) - 5) . " 个错误）";
                }
            }
            return $this->fail($message);
        }

        return $this->success($message);
    }
}
