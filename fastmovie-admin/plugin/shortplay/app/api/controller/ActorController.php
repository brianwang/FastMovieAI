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
use plugin\shortplay\app\model\PluginShortplayActor;
use plugin\shortplay\app\model\PluginShortplayActorCharacterLook;
use plugin\shortplay\app\model\PluginShortplayCharacterLook;
use plugin\shortplay\app\model\PluginShortplayDrama;
use plugin\shortplay\app\model\PluginShortplayDramaActor;
use plugin\shortplay\app\model\PluginShortplayDramaEpisode;
use plugin\shortplay\app\model\PluginShortplayDramaEpisodeActor;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboard;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardActor;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardDialogue;
use plugin\shortplay\utils\enum\ActorAge;
use plugin\shortplay\utils\enum\ActorGender;
use plugin\shortplay\utils\enum\ActorSpeciesType;
use plugin\shortplay\utils\enum\ActorStatus;
use plugin\shortplay\utils\enum\VoiceLanguage;
use support\Log;
use support\Request;
use support\think\Db;
use Workerman\Coroutine;

class ActorController extends Basic
{
    protected $notNeedLogin = ['index'];
    public function index(Request $request)
    {
        $limit = $request->get('limit', 2000);
        $page = $request->get('page');
        $type = $request->get('type');
        $where = [];
        // $where[] = ['status', '=', ActorStatus::GENERATED['value']];
        $whereOr = [];
        $params = [];
        $uid = $request->uid;
        switch ($type) {
            case 'public':
                $where[] = ['actor.uid', '=', null];
                break;
            case 'personal':
                if (!$request->uid) {
                    return $this->code(ResponseCode::NEED_LOGIN, '请先登录');
                }
                $where[] = ['actor.uid', '=', $request->uid];
                $where[] = ['actor.drama_id', '=', null];
                break;
            case 'self':
                if (!$request->uid) {
                    return $this->code(ResponseCode::NEED_LOGIN, '请先登录');
                }
                $where[] = ['actor.uid', '=', $request->uid];
                break;
            case 'storyboard':
                $storyboard_id = $request->get('storyboard_id');
                if (!$storyboard_id) {
                    return $this->fail('分镜ID不能为空');
                }
                $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id])->find();
                if (!$PluginShortplayDramaStoryboard) {
                    return $this->fail('分镜不存在');
                }
                $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['id' => $PluginShortplayDramaStoryboard->episode_id, 'drama_id' => $PluginShortplayDramaStoryboard->drama_id])->find();
                if (!$PluginShortplayDramaEpisode) {
                    return $this->fail('短剧不存在');
                }
                $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $PluginShortplayDramaStoryboard->drama_id, 'uid' => $request->uid])->find();
                if (!$PluginShortplayDrama) {
                    return $this->fail('短剧不存在');
                }
                $whereOr[] = ['actor.uid', '=', null];
                $whereOr[] = ['actor.uid', '=', $request->uid];
                $params['drama_id'] = $PluginShortplayDrama->id;
                $params['episode_id'] = $PluginShortplayDramaEpisode->id;
                $params['storyboard_id'] = $PluginShortplayDramaStoryboard->id;
                break;
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
                $whereOr[] = ['actor.uid', '=', null];
                $whereOr[] = ['actor.uid', '=', $request->uid];
                $params['drama_id'] = $PluginShortplayDrama->id;
                $params['episode_id'] = $PluginShortplayDramaEpisode->id;
                $params['storyboard_id'] = null;
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
                $whereOr[] = ['actor.uid', '=', null];
                $whereOr[] = ['actor.uid', '=', $request->uid];
                $params['drama_id'] = $PluginShortplayDrama->id;
                $params['episode_id'] = null;
                $params['storyboard_id'] = null;
                break;
            default:
                $whereOr[] = ['actor.uid', '=', null];
                $whereOr[] = ['actor.uid', '=', $request->uid];
                break;
        }
        $name = $request->get('name');
        if ($name) {
            $where[] = ['actor.name', 'like', '%' . $name . '%'];
        }
        $actor_id = $request->get('actor_id');
        if ($actor_id) {
            $where[] = ['actor.actor_id', 'like', '%' . $actor_id . '%'];
        }
        $species_type = $request->get('species_type');
        if ($species_type) {
            $where[] = ['actor.species_type', '=', $species_type];
        }
        $gender = $request->get('gender');
        if ($gender) {
            $where[] = ['actor.gender', '=', $gender];
        }
        $age = $request->get('age');
        if ($age) {
            $where[] = ['actor.age', '=', $age];
        }
        $PluginShortplayActor = PluginShortplayActor::actorQuery($params)->where($where)->whereOr($whereOr)->order('actor.id', 'desc')->limit($limit);
        if ($page) {
            $PluginShortplayActor = $PluginShortplayActor->paginate($limit, $page);
        } else {
            $PluginShortplayActor = $PluginShortplayActor->select();
        }
        $list = $PluginShortplayActor->each(function ($item) use ($uid, $params) {
            $item->species_type_enum = ActorSpeciesType::get($item->species_type);
            $item->gender_enum = ActorGender::get($item->gender);
            $item->age_enum = ActorAge::get($item->age);
            $item->is_edit = $item->uid == $uid && $uid;
            $params['actor_id'] = $item->id;
            $item->character_look_state = PluginShortplayActorCharacterLook::processing($params);
            if ($item->character_look_id) {
                $characterLook = PluginShortplayActorCharacterLook::where(['id' => $item->character_look_id])->with('characterLook')->find();
                if ($characterLook) {
                    $item->characterLook = $characterLook->characterLook;
                }
            }
        });
        return $this->resData($list);
    }
    public function initializing(Request $request)
    {
        $id = $request->post('id');

        $PluginShortplayActor = PluginShortplayActor::where([
            'id'  => $id,
            'uid' => $request->uid
        ])->find();

        if (!$PluginShortplayActor) {
            return $this->fail('演员不存在');
        }
        $name = $request->post('name');
        if ($name) {
            $PluginShortplayActor->name = $name;
        }
        $actor_id = uniqid();
        if ($actor_id) {
            $PluginShortplayActor->actor_id = $actor_id;
        }
        $species_type = $request->post('species_type');
        if ($species_type) {
            $PluginShortplayActor->species_type = $species_type;
        }
        $gender = $request->post('gender');
        if ($gender) {
            $PluginShortplayActor->gender = $gender;
        }
        $age = $request->post('age');
        if ($age) {
            $PluginShortplayActor->age = $age;
        }
        $remarks = $request->post('remarks');
        if ($remarks) {
            $PluginShortplayActor->remarks = $remarks;
        }
        $imageState = PluginModelTask::processing(['alias_id' => $PluginShortplayActor->id, 'scene' => ModelScene::ACTOR_IMAGE['value']]);
        $threeViewImageState = PluginModelTask::processing(['alias_id' => $PluginShortplayActor->id, 'scene' => ModelScene::ACTOR_THREE_VIEW_IMAGE['value']]);
        if ($imageState > 0 || $threeViewImageState > 0) {
            return $this->fail('演员正在生成中');
        }

        // ===== 表单参数 =====
        $image_model_id           = $request->post('image_model_id');
        $three_view_model_id      = $request->post('three_view_model_id');
        $image_state              = (bool)$request->post('image_state');
        $three_view_image_state   = (bool)$request->post('three_view_image_state');
        $image                    = $request->post('image');
        $three_view_image         = $request->post('three_view_image');
        $image_reference_state   = (bool)$request->post('image_reference_state');
        $reference_headimg   = $request->post('reference_headimg');
        if ($image_reference_state && empty($reference_headimg)) {
            return $this->fail('请上传参考图片');
        }

        // ===== 是否需要 AI 生成 =====
        $needAiImage = ($PluginShortplayActor->headimg && $image_state) ? empty($image) : true;
        $needSkipImage = $PluginShortplayActor->headimg && !$image_state;
        $needAiThreeView = ($PluginShortplayActor->three_view_image && $three_view_image_state) ? empty($three_view_image) : true;
        $needSkipThreeView = $PluginShortplayActor->three_view_image && !$three_view_image_state;
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
                'scene' => ModelScene::ACTOR_IMAGE['value'],
                'state' => State::YES['value']
            ])->find();

            $ThreeViewPluginModel = PluginModel::where([
                'id'    => $three_view_model_id,
                'scene' => ModelScene::ACTOR_THREE_VIEW_IMAGE['value'],
                'state' => State::YES['value']
            ])->find();

            if (!$ImagePluginModel || !$ThreeViewPluginModel) {
                return $this->fail('模型不存在');
            }
        } else {
            // 单独 image AI
            if ($needAiImage && !$needSkipImage) {
                if (!$image_model_id) {
                    return $this->fail('请选择角色形象模型');
                }
                $ImagePluginModel = PluginModel::where([
                    'id'    => $image_model_id,
                    'scene' => ModelScene::ACTOR_IMAGE['value'],
                    'state' => State::YES['value']
                ])->find();

                if (!$ImagePluginModel) {
                    return $this->fail('角色形象模型不存在');
                }
            }

            // 单独 three view AI
            if ($needAiThreeView && !$needSkipThreeView) {
                if (!$three_view_model_id) {
                    return $this->fail('请选择角色三视图模型');
                }
                $ThreeViewPluginModel = PluginModel::where([
                    'id'    => $three_view_model_id,
                    'scene' => ModelScene::ACTOR_THREE_VIEW_IMAGE['value'],
                    'state' => State::YES['value']
                ])->find();

                if (!$ThreeViewPluginModel) {
                    return $this->fail('角色三视图模型不存在');
                }
            }
        }

        // =====================================================
        // 二、角色形象 AI 生成
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
                        'prompt'     => $PluginShortplayActor->remarks,
                        'species'    => ActorSpeciesType::getText($PluginShortplayActor->species_type),
                        'gender'     => ActorGender::getText($PluginShortplayActor->gender),
                        'age'        => ActorAge::getText($PluginShortplayActor->age),
                        'aspect_ratio' => '1:1',
                    ]
                ];
                if ($image_reference_state) {
                    $data['form_data']['images'] = [$reference_headimg];
                }
                Db::startTrans();
                try {
                    $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $ImagePluginModel->point, PointsBillScene::CONSUME['value'], null, '生成角色形象图', true);
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
                        $task->alias_id       = $PluginShortplayActor->id;
                        $task->scene          = ModelScene::ACTOR_IMAGE['value'];
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
                    Log::error('GenerateActorImage Error:' . $e->getMessage());
                    return $this->fail('角色形象生成失败');
                }
                $PluginShortplayActor->headimg = null;
            }
        }

        // =====================================================
        // 三、角色三视图 AI 生成
        // =====================================================
        if (!$needSkipThreeView) {
            if ($needAiThreeView) {
                $data = [
                    'assistant' => $ThreeViewPluginModel->assistant_id,
                    'model'     => $ThreeViewPluginModel->model_id,
                    'notify_url' => 'https://' . $request->host() . '/app/model/Notify/draw',
                    'form_data' => [
                        'prompt'     => $PluginShortplayActor->remarks,
                        'species'    => ActorSpeciesType::getText($PluginShortplayActor->species_type),
                        'gender'     => ActorGender::getText($PluginShortplayActor->gender),
                        'age'        => ActorAge::getText($PluginShortplayActor->age),
                        'aspect_ratio' => '1:1',
                    ]
                ];

                // 非 image AI 场景，必须依赖已有形象
                if (!$lockImage) {
                    if (!$PluginShortplayActor->headimg) {
                        return $this->fail('请先生成角色形象，再生成角色三视图');
                    }
                    $data['form_data']['images'] = [$PluginShortplayActor->headimg];
                }

                try {
                    if (!$lockImage) {
                        Db::startTrans();
                        try {
                            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $ThreeViewPluginModel->point, PointsBillScene::CONSUME['value'], null, '生成角色三视图', true);
                            Db::commit();
                        } catch (\Throwable $th) {
                            Db::rollback();
                            return $this->fail($th->getMessage());
                        }
                        $result = Yidevs::DrawAssistantTIGI($request->channels_uid, $data);
                    }

                    Db::startTrans();
                    try {
                        $task = new PluginModelTask();
                        $task->channels_uid   = $request->channels_uid;
                        $task->uid            = $request->uid;
                        $task->model_id       = $ThreeViewPluginModel->id;
                        $task->model_type     = ModelType::DRAW['value'];
                        $task->alias_id       = $PluginShortplayActor->id;
                        $task->scene          = ModelScene::ACTOR_THREE_VIEW_IMAGE['value'];
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
                    Log::error('GenerateActorThreeView Error:' . $e->getMessage());
                    return $this->fail('角色三视图生成失败');
                }
                $PluginShortplayActor->three_view_image = null;
            }
        }
        $PluginShortplayActor->save();
        $PluginShortplayActor = PluginShortplayActor::where(['id' => $PluginShortplayActor->id])->find();
        return $this->resData($PluginShortplayActor);
    }
    public function update(Request $request)
    {
        $msg = '更新成功';
        Db::startTrans();
        try {
            $id = $request->post('id');
            $PluginShortplayDramaActor = null;
            $PluginShortplayDramaEpisodeActor = null;
            if ($id) {
                $PluginShortplayActor = PluginShortplayActor::where(['id' => $id, 'uid' => $request->uid])->find();
                if (!$PluginShortplayActor) {
                    throw new \Exception('演员不存在');
                }
            } else {
                $PluginShortplayActor = new PluginShortplayActor();
                $PluginShortplayActor->uid = $request->uid;
                $PluginShortplayActor->channels_uid = $request->channels_uid;
                $drama_id = $request->post('drama_id');
                if ($drama_id) {
                    $PluginShortplayActor->drama_id = $drama_id;
                    $PluginShortplayDramaActor = new PluginShortplayDramaActor();
                    $PluginShortplayDramaActor->channels_uid = $request->channels_uid;
                    $PluginShortplayDramaActor->drama_id = $drama_id;
                }
                $episode_id = $request->post('episode_id');
                if ($episode_id) {
                    $PluginShortplayActor->episode_id = $episode_id;
                    $PluginShortplayDramaEpisodeActor = new PluginShortplayDramaEpisodeActor();
                    $PluginShortplayDramaEpisodeActor->channels_uid = $request->channels_uid;
                    $PluginShortplayDramaEpisodeActor->drama_id = $drama_id;
                    $PluginShortplayDramaEpisodeActor->episode_id = $episode_id;
                }
                $PluginShortplayActor->status = ActorStatus::INITIALIZING['value'];
                $msg = '创建成功';
                $PluginShortplayActor->actor_id = uniqid();
            }
            $PluginShortplayActor->name = $request->post('name');
            $PluginShortplayActor->headimg = $request->post('headimg');
            $PluginShortplayActor->species_type = $request->post('species_type');
            $PluginShortplayActor->gender = $request->post('gender');
            $PluginShortplayActor->age = $request->post('age');
            $PluginShortplayActor->remarks = $request->post('remarks');
            $PluginShortplayActor->three_view_image = $request->post('three_view_image');
            if ($PluginShortplayActor->headimg && $PluginShortplayActor->three_view_image) {
                $PluginShortplayActor->status = ActorStatus::GENERATED['value'];
            }
            $PluginShortplayActor->save();
            if ($PluginShortplayDramaActor) {
                $PluginShortplayDramaActor->actor_id = $PluginShortplayActor->id;
                $PluginShortplayDramaActor->save();
            }
            if ($PluginShortplayDramaEpisodeActor) {
                $PluginShortplayDramaEpisodeActor->actor_id = $PluginShortplayActor->id;
                $PluginShortplayDramaEpisodeActor->save();
            }
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        return $this->success($msg, $PluginShortplayActor);
    }
    public function ReplaceActor(Request $request)
    {
        $id = $request->post('id');
        $task_id = $request->post('task_id');
        $PluginShortplayActor = PluginShortplayActor::where(['id' => $id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayActor) {
            return $this->fail('演员不存在');
        }
        $PluginModelTask = PluginModelTask::where(['id' => $task_id, 'alias_id' => $PluginShortplayActor->id, 'scene' => ModelScene::ACTOR_IMAGE['value'], 'status' => ModelTaskStatus::SUCCESS['value']])->find();
        if (!$PluginModelTask) {
            return $this->fail('任务不存在');
        }
        $PluginShortplayActor->headimg = $PluginModelTask->result->image_path;
        $PluginModelTask = PluginModelTask::where(['pre_task_id' => $PluginModelTask->id, 'alias_id' => $PluginShortplayActor->id, 'scene' => ModelScene::ACTOR_THREE_VIEW_IMAGE['value'], 'status' => ModelTaskStatus::SUCCESS['value']])->find();
        if ($PluginModelTask) {
            $PluginShortplayActor->three_view_image = $PluginModelTask->result->image_path;
            $PluginShortplayActor->status = ActorStatus::GENERATED['value'];
        } else {
            $PluginShortplayActor->status = ActorStatus::INITIALIZING['value'];
            $PluginShortplayActor->three_view_image = null;
        }
        $PluginShortplayActor->save();
        $PluginShortplayActor = PluginShortplayActor::where(['id' => $id])->find();
        return $this->resData($PluginShortplayActor);
    }
    public function voice(Request $request)
    {
        $id = $request->post('id');
        $drama_id = $request->post('drama_id');
        $episode_id = $request->post('episode_id');
        $storyboard_id = $request->post('storyboard_id');
        $scene_id = $request->post('scene_id');
        $dialogue_id = $request->post('dialogue_id');
        $apply_scope = $request->post('apply_scope');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        Db::startTrans();
        try {
            $voice = $request->post('voice');
            if (!empty($voice['emotions_enum']) && empty($voice['selected_emotion'])) {
                $voice['selected_emotion'] = $voice['emotions_enum'][0];
            }
            if (empty($voice['selected_language'])) {
                $voice['selected_language'] = VoiceLanguage::ZH;
            }
            if ($apply_scope === 'scene') {
                $PluginShortplayDramaStoryboards = PluginShortplayDramaStoryboard::where(['scene_id' => $scene_id, 'drama_id' => $PluginShortplayDrama->id])->find();
                if ($PluginShortplayDramaStoryboards->isEmpty()) {
                    throw new \Exception('该场景下无分镜');
                }
                foreach ($PluginShortplayDramaStoryboards as $PluginShortplayDramaStoryboard) {
                    $PluginShortplayDramaStoryboardActor = PluginShortplayDramaStoryboardActor::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id, 'actor_id' => $id])->find();
                    if ($PluginShortplayDramaStoryboardActor) {
                        $PluginShortplayDramaStoryboardActor->voice = $voice;
                        $PluginShortplayDramaStoryboardActor->save();
                    }
                }
            } else {
                $ActorModel = null;
                switch ($apply_scope) {
                    case 'drama':
                        $ActorModel = PluginShortplayDramaActor::where(['drama_id' => $PluginShortplayDrama->id, 'actor_id' => $id])->find();
                        break;
                    case 'episode':
                        $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['id' => $episode_id, 'drama_id' => $PluginShortplayDrama->id])->find();
                        if (!$PluginShortplayDramaEpisode) {
                            throw new \Exception('分集不存在');
                        }
                        $ActorModel = PluginShortplayDramaEpisodeActor::where(['episode_id' => $PluginShortplayDramaEpisode->id, 'actor_id' => $id])->find();
                        break;
                    case 'storyboard':
                        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id, 'drama_id' => $PluginShortplayDrama->id])->find();
                        if (!$PluginShortplayDramaStoryboard) {
                            throw new \Exception('分镜不存在');
                        }
                        $ActorModel = PluginShortplayDramaStoryboardActor::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id, 'actor_id' => $id])->find();
                        break;
                    case 'dialogue':
                        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id, 'drama_id' => $PluginShortplayDrama->id])->find();
                        if (!$PluginShortplayDramaStoryboard) {
                            throw new \Exception('分镜不存在');
                        }
                        $ActorModel = PluginShortplayDramaStoryboardDialogue::where(['id' => $dialogue_id, 'storyboard_id' => $PluginShortplayDramaStoryboard->id])->find();
                        break;
                }
                if (!$ActorModel) {
                    throw new \Exception('演员不存在');
                }
                $ActorModel->voice = $voice;
                $ActorModel->save();
            }
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        return $this->success('更新成功');
    }
    public function delete(Request $request)
    {
        $id = $request->post('id');
        $PluginShortplayActor = PluginShortplayActor::where(['id' => $id, 'uid' => $request->uid])->whereNull('drama_id')->find();
        if (!$PluginShortplayActor) {
            return $this->fail('演员不存在');
        }
        $PluginShortplayActor->delete();
        return $this->success('删除成功');
    }
}
