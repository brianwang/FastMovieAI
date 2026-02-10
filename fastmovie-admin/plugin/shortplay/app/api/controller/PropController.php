<?php

namespace plugin\shortplay\app\api\controller;

use app\Basic;
use app\expose\enum\ResponseCode;
use app\expose\enum\State;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use plugin\control\utils\yidevs\Yidevs;
use plugin\finance\expose\helper\Account;
use plugin\finance\utils\enum\PointsBillScene;
use plugin\model\app\model\PluginModel;
use plugin\model\app\model\PluginModelTask;
use plugin\model\app\model\PluginModelTaskResult;
use plugin\model\utils\enum\ModelScene;
use plugin\model\utils\enum\ModelTaskStatus;
use plugin\model\utils\enum\ModelType;
use plugin\notification\expose\helper\Push;
use plugin\shortplay\app\model\PluginShortplayProp;
use plugin\shortplay\app\model\PluginShortplayDrama;
use plugin\shortplay\app\model\PluginShortplayDramaEpisode;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardProp;
use plugin\shortplay\utils\enum\PropStatus;
use support\Log;
use support\Request;
use support\think\Db;
use Workerman\Coroutine;

class PropController extends Basic
{
    public function index(Request $request)
    {
        $limit = $request->get('limit', 2000);
        $type = $request->get('type');
        $where = [];
        // $where[] = ['status', '=', PropStatus::GENERATED['value']];
        $where[] = ['uid', '=', $request->uid];
        switch ($type) {
            case 'episode':
                $episode_id = $request->get('episode_id');
                if (!$episode_id) {
                    return $this->fail('分集ID不能为空');
                }
                $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['id' => $episode_id])->find();
                if (!$PluginShortplayDramaEpisode) {
                    return $this->fail('分集不存在');
                }
                $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $PluginShortplayDramaEpisode->drama_id, 'uid' => $request->uid])->find();
                if (!$PluginShortplayDrama) {
                    return $this->fail('短剧不存在');
                }
                $where[] = ['episode_id', '=', $PluginShortplayDramaEpisode->id];
                break;
            case 'drama':
                $drama_id = $request->get('drama_id');
                if (!$drama_id) {
                    return $this->fail('短剧ID不能为空');
                }
                $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
                if (!$PluginShortplayDrama) {
                    return $this->fail('短剧不存在');
                }
                $where[] = ['drama_id', '=', $PluginShortplayDrama->id];
                break;
            default:
                break;
        }
        $name = $request->get('name');
        if ($name) {
            $where[] = ['name|prop_id', 'like', '%' . $name . '%'];
        }
        $PluginShortplayProp = PluginShortplayProp::where($where)->order('id', 'desc')->limit($limit)->select()->each(function ($item) {
            // $item->status_enum = PropStatus::get($item->status);
        });
        return $this->resData($PluginShortplayProp);
    }
    public function initializing(Request $request)
    {
        $id = $request->post('id');

        $PluginShortplayProp = PluginShortplayProp::where([
            'id'  => $id,
            'uid' => $request->uid
        ])->find();
        if (!$PluginShortplayProp) {
            return $this->fail('物品不存在');
        }
        $description = $request->post('description');
        if ($description) {
            $PluginShortplayProp->description = $description;
        }

        $imageState = PluginModelTask::processing(['alias_id' => $PluginShortplayProp->id, 'scene' => ModelScene::PROP_IMAGE['value']]);
        $threeViewImageState = PluginModelTask::processing(['alias_id' => $PluginShortplayProp->id, 'scene' => ModelScene::PROP_THREE_VIEW_IMAGE['value']]);
        if ($imageState > 0 || $threeViewImageState > 0) {
            return $this->fail('物品正在生成中');
        }

        // ===== 表单参数 =====
        $image_model_id           = $request->post('image_model_id');
        $three_view_model_id      = $request->post('three_view_model_id');
        $image_state              = (bool)$request->post('image_state');
        $three_view_image_state   = (bool)$request->post('three_view_image_state');
        $image                    = $request->post('image');
        $three_view_image         = $request->post('three_view_image');
        $image_reference_state   = (bool)$request->post('image_reference_state');
        $reference_image   = $request->post('reference_image');
        if ($image_reference_state && empty($reference_image)) {
            return $this->fail('请上传参考图片');
        }

        // ===== 是否需要 AI 生成 =====
        $needAiImage = ($PluginShortplayProp->image && $image_state) ? empty($image) : true;
        $needSkipImage = $PluginShortplayProp->image && !$image_state;
        $needAiThreeView = ($PluginShortplayProp->three_view_image && $three_view_image_state) ? empty($three_view_image) : true;
        $needSkipThreeView = $PluginShortplayProp->three_view_image && !$three_view_image_state;
        $isDoubleAi = ($needAiImage && !$needSkipImage) && ($needAiThreeView && !$needSkipThreeView);

        $ImagePluginModel = null;
        $ThreeViewPluginModel = null;

        // =====================================================
        // 一、模型前置校验（任何 AI 调用前）
        // =====================================================

        // 双 AI：必须一次性校验两个模型
        if ($isDoubleAi) {
            $ImagePluginModel = PluginModel::where([
                'id'    => $image_model_id,
                'scene' => ModelScene::PROP_IMAGE['value'],
                'state' => State::YES['value']
            ])->find();

            $ThreeViewPluginModel = PluginModel::where([
                'id'    => $three_view_model_id,
                'scene' => ModelScene::PROP_THREE_VIEW_IMAGE['value'],
                'state' => State::YES['value']
            ])->find();

            if (!$ImagePluginModel || !$ThreeViewPluginModel) {
                return $this->fail('模型不存在');
            }
        } else {
            // 单独 image AI
            if ($needAiImage && !$needSkipImage) {
                $ImagePluginModel = PluginModel::where([
                    'id'    => $image_model_id,
                    'scene' => ModelScene::PROP_IMAGE['value'],
                    'state' => State::YES['value']
                ])->find();

                if (!$ImagePluginModel) {
                    return $this->fail('物品图片模型不存在');
                }
            }

            // 单独 three view AI
            if ($needAiThreeView && !$needSkipThreeView) {
                $ThreeViewPluginModel = PluginModel::where([
                    'id'    => $three_view_model_id,
                    'scene' => ModelScene::PROP_THREE_VIEW_IMAGE['value'],
                    'state' => State::YES['value']
                ])->find();

                if (!$ThreeViewPluginModel) {
                    return $this->fail('物品三视图模型不存在');
                }
            }
        }

        // =====================================================
        // 二、物品图片 AI 生成
        // =====================================================

        $lockImage = false;
        $taskId = null;
        if (!$needSkipImage) {
            if ($needAiImage) {
                $lockImage = true;
                $data = [
                    'assistant' => $ImagePluginModel->assistant_id,
                    'model'     => $ImagePluginModel->model_id,
                    'notify_url' => 'https://' . $request->host() . '/app/model/Notify/draw',
                    'form_data' => [
                        'prompt'     => $PluginShortplayProp->description,
                        'aspect_ratio' => '1:1',
                    ]
                ];
                if ($image_reference_state) {
                    $data['form_data']['images'] = [$reference_image];
                }
                Db::startTrans();
                try {
                    $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $ImagePluginModel->point, PointsBillScene::CONSUME['value'], null, '生成物品图片', true);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                    return $this->fail($th->getMessage());
                }
                try {
                    $result = Yidevs::DrawAssistantTIGI($request->channels_uid, $data);

                    Db::startTrans();
                    try {
                        $task = new PluginModelTask();
                        $task->channels_uid   = $request->channels_uid;
                        $task->uid            = $request->uid;
                        $task->model_id       = $ImagePluginModel->id;
                        $task->model_type     = ModelType::DRAW['value'];
                        $task->alias_id       = $PluginShortplayProp->id;
                        $task->scene          = ModelScene::PROP_IMAGE['value'];
                        $task->status         = ModelTaskStatus::PROCESSING['value'];
                        $task->task_id        = $result['task_id'];
                        $task->last_heartbeat = date('Y-m-d H:i:s');
                        $task->consume_ids = $consume_ids;
                        $task->save();
                        $taskId = $task->id;
                        $taskResult = new PluginModelTaskResult();
                        $taskResult->task_id      = $task->id;
                        $taskResult->channels_uid = $request->channels_uid;
                        $taskResult->params       = $data;
                        $taskResult->save();

                        Db::commit();
                    } catch (\Throwable $e) {
                        Db::rollback();
                        throw $e;
                    }
                } catch (\Throwable $e) {
                    if (!empty($consume_ids)) {
                        Db::startTrans();
                        try {
                            Account::refund($request->uid, $request->channels_uid, $consume_ids);
                            Db::commit();
                        } catch (\Throwable $th) {
                            Db::rollback();
                        }
                    }
                    Log::error('GeneratePropImage Error:' . $e->getMessage());
                    return $this->fail('物品图片生成失败');
                }
                $PluginShortplayProp->image = null;
            }
        }

        // =====================================================
        // 三、物品三视图 AI 生成
        // =====================================================
        if (!$needSkipThreeView) {
            if ($needAiThreeView) {
                $data = [
                    'assistant' => $ThreeViewPluginModel->assistant_id,
                    'model'     => $ThreeViewPluginModel->model_id,
                    'notify_url' => 'https://' . $request->host() . '/app/model/Notify/draw',
                    'form_data' => [
                        'prompt'     => $PluginShortplayProp->description,
                        'aspect_ratio' => '1:1',
                    ]
                ];

                // 非 image AI 场景，必须依赖已有形象
                if (!$lockImage) {
                    if (!$PluginShortplayProp->image) {
                        return $this->fail('请先生成物品图片，再生成物品三视图');
                    }
                    $data['form_data']['images'] = [$PluginShortplayProp->image];
                }

                Db::startTrans();
                try {
                    $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $ThreeViewPluginModel->point, PointsBillScene::CONSUME['value'], null, '生成物品三视图', true);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                    return $this->fail($th->getMessage());
                }
                try {
                    if (!$lockImage) {
                        $result = Yidevs::DrawAssistantTIGI($request->channels_uid, $data);
                    }

                    Db::startTrans();
                    try {
                        $task = new PluginModelTask();
                        $task->channels_uid   = $request->channels_uid;
                        $task->uid            = $request->uid;
                        $task->model_id       = $ThreeViewPluginModel->id;
                        $task->model_type     = ModelType::DRAW['value'];
                        $task->alias_id       = $PluginShortplayProp->id;
                        $task->scene          = ModelScene::PROP_THREE_VIEW_IMAGE['value'];
                        $task->pre_task_id    = $taskId;
                        $task->status         = $lockImage
                            ? ModelTaskStatus::WAIT['value']
                            : ModelTaskStatus::PROCESSING['value'];
                        $task->task_id        = $lockImage ? null : $result['task_id'];
                        $task->last_heartbeat = date('Y-m-d H:i:s');
                        $task->consume_ids = $consume_ids;
                        $task->save();

                        $taskResult = new PluginModelTaskResult();
                        $taskResult->task_id      = $task->id;
                        $taskResult->channels_uid = $request->channels_uid;
                        $taskResult->params       = $data;
                        $taskResult->save();

                        Db::commit();
                    } catch (\Throwable $e) {
                        Db::rollback();
                        throw $e;
                    }
                } catch (\Throwable $e) {
                    if (!empty($consume_ids)) {
                        Db::startTrans();
                        try {
                            Account::refund($request->uid, $request->channels_uid, $consume_ids);
                            Db::commit();
                        } catch (\Throwable $th) {
                            Db::rollback();
                        }
                    }
                    Log::error('GeneratePropThreeView Error:' . $e->getMessage());
                    return $this->fail('物品三视图生成失败');
                }
                $PluginShortplayProp->three_view_image = null;
            }
        }
        $PluginShortplayProp->save();

        $PluginShortplayProp = PluginShortplayProp::where(['id' => $PluginShortplayProp->id])->find();
        return $this->resData($PluginShortplayProp);
    }
    public function update(Request $request)
    {
        $msg = '更新成功';
        Db::startTrans();
        try {
            $id = $request->post('id');
            if ($id) {
                $PluginShortplayProp = PluginShortplayProp::where(['id' => $id, 'uid' => $request->uid])->find();
                if (!$PluginShortplayProp) {
                    throw new \Exception('物品不存在');
                }
            } else {
                $PluginShortplayProp = new PluginShortplayProp();
                $PluginShortplayProp->uid = $request->uid;
                $PluginShortplayProp->channels_uid = $request->channels_uid;
                $PluginShortplayProp->status = PropStatus::INITIALIZING['value'];
                $drama_id = $request->post('drama_id');
                if ($drama_id) {
                    $PluginShortplayProp->drama_id = $drama_id;
                }
                $episode_id = $request->post('episode_id');
                if ($episode_id) {
                    $PluginShortplayProp->episode_id = $episode_id;
                }
                $msg = '创建成功';
                $PluginShortplayProp->prop_id = uniqid();
            }
            $PluginShortplayProp->name = $request->post('name');
            $PluginShortplayProp->image = $request->post('image');
            $PluginShortplayProp->description = $request->post('description');
            $PluginShortplayProp->three_view_image = $request->post('three_view_image');
            $PluginShortplayProp->save();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        return $this->success($msg, $PluginShortplayProp);
    }
    public function delete(Request $request)
    {
        $id = $request->post('id');
        $PluginShortplayProp = PluginShortplayProp::where(['id' => $id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayProp) {
            return $this->fail('物品不存在');
        }
        Db::startTrans();
        try {
            PluginShortplayDramaStoryboardProp::where(['prop_id' => $PluginShortplayProp->id])->delete();
            $PluginShortplayProp->delete();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        return $this->success('删除成功');
    }
    public function ReplaceProp(Request $request)
    {
        $id = $request->post('id');
        $task_id = $request->post('task_id');
        $PluginShortplayProp = PluginShortplayProp::where(['id' => $id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayProp) {
            return $this->fail('物品不存在');
        }
        $PluginModelTask = PluginModelTask::where(['id' => $task_id, 'alias_id' => $PluginShortplayProp->id, 'scene' => ModelScene::PROP_IMAGE['value'], 'status' => ModelTaskStatus::SUCCESS['value']])->find();
        if (!$PluginModelTask) {
            return $this->fail('任务不存在');
        }
        $PluginShortplayProp->image = $PluginModelTask->result->image_path;
        $PluginModelTask = PluginModelTask::where(['pre_task_id' => $PluginModelTask->id, 'alias_id' => $PluginShortplayProp->id, 'scene' => ModelScene::PROP_THREE_VIEW_IMAGE['value'], 'status' => ModelTaskStatus::SUCCESS['value']])->find();
        if ($PluginModelTask) {
            $PluginShortplayProp->three_view_image = $PluginModelTask->result->image_path;
            $PluginShortplayProp->status = PropStatus::GENERATED['value'];
        } else {
            $PluginShortplayProp->status = PropStatus::INITIALIZING['value'];
            $PluginShortplayProp->three_view_image = null;
        }
        $PluginShortplayProp->save();
        $PluginShortplayProp = PluginShortplayProp::where(['id' => $id])->find();
        return $this->resData($PluginShortplayProp);
    }
}
