<?php

namespace plugin\shortplay\app\api\controller;

use app\Basic;
use app\expose\enum\State;
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
use plugin\shortplay\app\model\PluginShortplayDrama;
use plugin\shortplay\app\model\PluginShortplayDramaActor;
use plugin\shortplay\app\model\PluginShortplayCharacterLook;
use plugin\shortplay\app\model\PluginShortplayDramaEpisode;
use plugin\shortplay\app\model\PluginShortplayDramaEpisodeActor;
use plugin\shortplay\app\model\PluginShortplayDramaScene;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboard;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardActor;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardDialogue;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardProp;
use plugin\shortplay\app\model\PluginShortplayStyle;
use plugin\model\app\model\PluginModelVoice;
use plugin\shortplay\utils\enum\ActorAge;
use plugin\shortplay\utils\enum\ActorGender;
use plugin\shortplay\utils\enum\ActorSpeciesType;
use plugin\shortplay\utils\enum\ActorStatus;
use plugin\shortplay\utils\enum\PropStatus;
use plugin\shortplay\utils\enum\VoiceEmotion;
use plugin\shortplay\utils\enum\VoiceLanguage;
use support\Log;
use support\Request;
use support\think\Db;
use Workerman\Coroutine;

class GenerateController extends Basic
{
    public function dramaCover(Request $request)
    {
        $id = $request->post('id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $id, 'uid' => $request->uid])->with('style')->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $model_id = $request->post('model_id');
        $PluginModel = PluginModel::where(['id' => $model_id, 'scene' => ModelScene::DRAMA_COVER['value'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $PluginShortplayDramaActor = PluginShortplayDramaActor::where(['drama_id' => $id])->order('sort asc')->with('actor')->limit(4)->select();
        $images = [];
        foreach ($PluginShortplayDramaActor as $item) {
            $images[] = $item->actor->headimg;
        }
        $prompts = [];
        $prompts[] = '剧名：' . $PluginShortplayDrama->title;
        $prompts[] = '简介：' . $PluginShortplayDrama->description;
        $data = [
            'assistant' => $PluginModel->assistant_id,
            'model' => $PluginModel->model_id,
            'form_data' => [
                'images' => $images,
                'prompt' => implode(";\n", $prompts) . ";\n",
                'style' => $PluginShortplayDrama->style->prompts,
                'notify_url' => 'https://' . $request->host() . '/app/model/Notify/draw',
                'aspect_ratio' => '2:3'
            ]
        ];

        Db::startTrans();
        try {
            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point, PointsBillScene::CONSUME['value'], null, '生成封面', true);
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        try {
            $result = Yidevs::DrawAssistantTIGI($request->channels_uid, $data);
            Db::startTrans();
            try {
                $PluginModelTask = new PluginModelTask();
                $PluginModelTask->channels_uid = $request->channels_uid;
                $PluginModelTask->uid = $request->uid;
                $PluginModelTask->model_id = $model_id;
                $PluginModelTask->model_type = ModelType::DRAW['value'];
                $PluginModelTask->alias_id = $PluginShortplayDrama->id;
                $PluginModelTask->scene = ModelScene::DRAMA_COVER['value'];
                $PluginModelTask->status = ModelTaskStatus::PROCESSING['value'];
                $PluginModelTask->task_id = $result['task_id'];
                $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s');
                $PluginModelTask->consume_ids = $consume_ids;
                $PluginModelTask->save();
                $PluginModelTaskResult = new PluginModelTaskResult();
                $PluginModelTaskResult->task_id = $PluginModelTask->id;
                $PluginModelTaskResult->channels_uid = $request->channels_uid;
                $PluginModelTaskResult->params = $data;
                $PluginModelTaskResult->save();
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                throw $th;
            }
        } catch (\Throwable $th) {
            if (!empty($consume_ids)) {
                Db::startTrans();
                try {
                    Account::refund($request->uid, $request->channels_uid, $consume_ids);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                }
            }
            return $this->fail($th->getMessage());
        }
        return $this->success('生成中');
    }
    public function continueEpisode(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $model_id = $request->post('model_id');
        $PluginModel = PluginModel::where(['id' => $model_id, 'scene' => ModelScene::CREATIVE_EPISODE['value'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $episode_sum = (int)$request->post('episode_sum');
        if (!$episode_sum) {
            return $this->fail('请输入需要生成的集数');
        }
        $continue_episode_state = PluginModelTask::processing(['alias_id' => $PluginShortplayDrama->id, 'scene' => ModelScene::CREATIVE_EPISODE['value']]);
        if ($continue_episode_state > 0) {
            return $this->fail('正在生成中');
        }
        Db::startTrans();
        try {
            $PluginShortplayDramaActor = PluginShortplayDramaActor::where('drama_id', $PluginShortplayDrama->id)->with('actor')->select();
            $actors = [];
            foreach ($PluginShortplayDramaActor as $PluginShortplayActor) {
                $actors[] = "{$PluginShortplayActor->actor->name}{{$PluginShortplayActor->actor->actor_id}}";
            }
            $background_description = $PluginShortplayDrama->background_description;
            $outline = $PluginShortplayDrama->outline;
            $pre_episode = '';
            $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where('drama_id', $PluginShortplayDrama->id)->order('episode_no desc')->find();
            if ($PluginShortplayDramaEpisode) {
                // $pre_episode[] = "{$PluginShortplayDramaEpisode->episode_no}集大纲:{$PluginShortplayDramaEpisode->outline}";
                $pre_episode = $PluginShortplayDramaEpisode->content;
            }
            $task_id = uniqid('local-');
            $species = [];
            $gender = [];
            $age = [];
            foreach (ActorSpeciesType::getOptions() as $value) {
                $species[] = $value['label'] . ':' . $value['value'] . '';
            }
            foreach (ActorGender::getOptions() as $value) {
                $gender[] = $value['label'] . ':' . $value['value'] . '';
            }
            foreach (ActorAge::getOptions() as $value) {
                $age[] = $value['label'] . ':' . $value['value'] . '';
            }
            $data = [
                'channels_uid' => $request->channels_uid,
                'uid' => $request->uid,
                'drama_id' => $PluginShortplayDrama->id,
                'task_id' => $task_id,
                'params' => [
                    'model' => $PluginModel->model_id,
                    'assistant' => $PluginModel->assistant_id,
                    'form_data' => [
                        'background_description' => $background_description,
                        'episode_duration' => $PluginShortplayDrama->episode_duration,
                        'outline' => $outline,
                        'pre_episode' => $pre_episode,
                        'episode_sum' => $PluginShortplayDrama->episode_sum,
                        'actors' => implode(',', $actors),
                        'episode_no' => $PluginShortplayDrama->episode_num,
                        'species' => implode(',', $species),
                        'gender' => implode(',', $gender),
                        'age' => implode(',', $age),
                    ]
                ]
            ];
            $PluginModelTask = new PluginModelTask();
            $PluginModelTask->channels_uid = $request->channels_uid;
            $PluginModelTask->uid = $request->uid;
            $PluginModelTask->model_id = $PluginModel->id;
            $PluginModelTask->model_type = ModelType::CHAT['value'];
            $PluginModelTask->alias_id = $PluginShortplayDrama->id;
            $PluginModelTask->scene = ModelScene::CREATIVE_EPISODE['value'];
            $PluginModelTask->status = ModelTaskStatus::WAIT['value'];
            $PluginModelTask->task_id = $task_id;
            $PluginModelTask->execution_count = 0;
            $PluginModelTask->expectation_execution_count = $episode_sum;
            if ($PluginModelTask->expectation_execution_count > $PluginShortplayDrama->episode_sum - $PluginShortplayDrama->episode_num) {
                $PluginModelTask->expectation_execution_count = $PluginShortplayDrama->episode_sum - $PluginShortplayDrama->episode_num;
            }
            $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s', strtotime('+10 seconds'));
            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point * $PluginModelTask->expectation_execution_count, PointsBillScene::CONSUME['value'], null, '续写分集', true);
            $PluginModelTask->consume_ids = $consume_ids;
            $PluginModelTask->save();
            $PluginModelTaskResult = new PluginModelTaskResult();
            $PluginModelTaskResult->task_id = $PluginModelTask->id;
            $PluginModelTaskResult->channels_uid = $request->channels_uid;
            $PluginModelTaskResult->params = $data;
            $PluginModelTaskResult->save();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            Log::error('续写分集失败:' . $th->getMessage() . PHP_EOL . $th->getTraceAsString());
            return $this->fail($th->getMessage());
        }
        return $this->success('生成中');
    }
    public function scene(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $episode_id = $request->post('episode_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['drama_id' => $PluginShortplayDrama->id, 'id' => $episode_id])->with('actors')->find();
        if (!$PluginShortplayDramaEpisode) {
            return $this->fail('分集不存在');
        }
        $model_id = $request->post('model_id');
        $PluginModel = PluginModel::where(['id' => $model_id, 'scene' => ModelScene::CREATIVE_SCENES['value'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $actors = [];
        foreach ($PluginShortplayDramaEpisode->actors as $item) {
            $actors[] = "{$item->actor->name}{{$item->actor->actor_id}}";
        }
        try {
            $prompt = $PluginShortplayDramaEpisode->content;
            $NextPluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['drama_id' => $PluginShortplayDrama->id, 'episode_no' => $PluginShortplayDramaEpisode->episode_no + 1])->find();
            if ($NextPluginShortplayDramaEpisode) {
                $next_episode_content = $NextPluginShortplayDramaEpisode->content;
            } else {
                $next_episode_content = '';
            }
            $task_id = uniqid('local-');
            $data = [
                'channels_uid' => $request->channels_uid,
                'uid' => $request->uid,
                'drama_id' => $PluginShortplayDrama->id,
                'episode_id' => $PluginShortplayDramaEpisode->id,
                'task_id' => $task_id,
                'params' => [
                    'model' => $PluginModel->model_id,
                    'assistant' => $PluginModel->assistant_id,
                    'form_data' => [
                        'prompt' => $prompt,
                        'next_episode_content' => $next_episode_content,
                        'episode_duration' => $PluginShortplayDrama->episode_duration,
                        // 'episode_outline' => $outline,
                        'episode_num' => $PluginShortplayDrama->episode_num,
                        'episode_no' => $PluginShortplayDramaEpisode->episode_no,
                        'drama_outline' => $PluginShortplayDrama->outline,
                        'actors' => implode(',', $actors)
                    ]
                ]
            ];
            Db::startTrans();
            try {
                $PluginModelTask = new PluginModelTask();
                $PluginModelTask->channels_uid = $request->channels_uid;
                $PluginModelTask->uid = $request->uid;
                $PluginModelTask->model_id = $model_id;
                $PluginModelTask->model_type = ModelType::CHAT['value'];
                $PluginModelTask->alias_id = $PluginShortplayDramaEpisode->id;
                $PluginModelTask->scene = ModelScene::CREATIVE_SCENES['value'];
                $PluginModelTask->status = ModelTaskStatus::PROCESSING['value'];
                $PluginModelTask->task_id = $task_id;
                $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s', strtotime('+30 seconds'));
                $PluginModelTask->save();
                $PluginModelTaskResult = new PluginModelTaskResult();
                $PluginModelTaskResult->task_id = $PluginModelTask->id;
                $PluginModelTaskResult->channels_uid = $request->channels_uid;
                $PluginModelTaskResult->params = $data;
                $PluginModelTaskResult->save();
                $consume_ids = Account::decPoints($PluginModelTask->uid, $PluginModelTask->channels_uid, $PluginModel->point, PointsBillScene::CONSUME['value'], null, '生成场景', true);
                $data['consume_ids'] = $consume_ids;
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                throw $th;
            }
            Coroutine::create(function () use ($data) {
                try {
                    p('start');
                    $result = Yidevs::ChatAssistantCompletions($data['channels_uid'], $data['params']);
                    p($result);
                } catch (\Throwable $th) {
                    Log::error('GenerateScene Error:' . $th->getMessage() . PHP_EOL . $th->getTraceAsString());
                }
                $taksStatus = ModelTaskStatus::FAIL['value'];
                if (!empty($result)) {
                    Db::startTrans();
                    try {
                        $wherePublic[] = ['uid', '=', null];
                        $whereSelf[] = ['uid', '=', $data['uid']];
                        foreach ($result as $scene_key => $scene) {
                            $PluginShortplayDramaScene = new PluginShortplayDramaScene();
                            $PluginShortplayDramaScene->channels_uid = $data['channels_uid'];
                            $PluginShortplayDramaScene->uid = $data['uid'];
                            $PluginShortplayDramaScene->drama_id = $data['drama_id'];
                            $PluginShortplayDramaScene->episode_id = $data['episode_id'];
                            $PluginShortplayDramaScene->sort = $scene_key + 1;
                            $PluginShortplayDramaScene->title = $scene['title'];
                            $PluginShortplayDramaScene->scene_space = $scene['scene_space'];
                            $PluginShortplayDramaScene->scene_location = $scene['scene_location'];
                            $PluginShortplayDramaScene->scene_time = $scene['scene_time'];
                            $PluginShortplayDramaScene->scene_weather = $scene['scene_weather'];
                            $PluginShortplayDramaScene->description = $scene['description'];
                            if (!empty($scene['atmosphere'])) {
                                $PluginShortplayDramaScene->atmosphere = $scene['atmosphere'];
                            }
                            $PluginShortplayDramaScene->save();
                            if (empty($scene['storyboards'])) {
                                continue;
                            }
                            $sort = PluginShortplayDramaStoryboard::where(['drama_id' => $data['drama_id'], 'episode_id' => $data['episode_id']])->max('sort') + 1 ?? 1;
                            foreach ($scene['storyboards'] as $storyboard) {
                                $PluginShortplayDramaStoryboard = new PluginShortplayDramaStoryboard();
                                $PluginShortplayDramaStoryboard->channels_uid = $data['channels_uid'];
                                $PluginShortplayDramaStoryboard->drama_id = $data['drama_id'];
                                $PluginShortplayDramaStoryboard->episode_id = $data['episode_id'];
                                $PluginShortplayDramaStoryboard->scene_id = $PluginShortplayDramaScene->id;
                                $PluginShortplayDramaStoryboard->sort = $sort;
                                $PluginShortplayDramaStoryboard->description = $storyboard;
                                $PluginShortplayDramaStoryboard->save();
                                $sort++;
                            }
                        }
                        $PluginModelTask = PluginModelTask::where(['task_id' => $data['task_id'], 'model_type' => ModelType::CHAT['value']])->with(['result'])->find();
                        $PluginModelTask->status = ModelTaskStatus::SUCCESS['value'];
                        $PluginModelTask->result->result = $result;
                        $PluginModelTask->together(['result'])->save();
                        $taksStatus = ModelTaskStatus::SUCCESS['value'];
                        Db::commit();
                    } catch (\Throwable $th) {
                        Db::rollback();
                        Log::error('GenerateScene Error:' . $th->getMessage() . PHP_EOL . $th->getTraceAsString());
                    }
                } else {
                    if (!empty($data['consume_ids'])) {
                        Db::startTrans();
                        try {
                            Account::refund($data['uid'], $data['channels_uid'], $data['consume_ids']);
                            Db::commit();
                        } catch (\Throwable $th) {
                            Db::rollback();
                        }
                    }
                }
                if ($taksStatus == ModelTaskStatus::FAIL['value']) {
                    $PluginModelTask = PluginModelTask::where(['task_id' => $data['task_id'], 'model_type' => ModelType::CHAT['value']])->find();
                    $PluginModelTask->status = ModelTaskStatus::FAIL['value'];
                    $PluginModelTask->save();
                }
                Push::send([
                    'uid' => $data['uid'],
                    'channels_uid' => $data['channels_uid'],
                    'hash' => $data['drama_id'],
                    'event' => 'generatescenestoryboard',
                ], [
                    'drama_id' => $data['drama_id'],
                    'episode_id' => $data['episode_id'],
                ]);
            });
        } catch (\Throwable $th) {
            return $this->exception($th);
        }
        return $this->success('生成中');
    }

    public function storyboard(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $episode_id = $request->post('episode_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['drama_id' => $PluginShortplayDrama->id, 'id' => $episode_id])->with('actors')->find();
        if (!$PluginShortplayDramaEpisode) {
            return $this->fail('分集不存在');
        }
        $model_id = $request->post('model_id');
        $PluginModel = PluginModel::where(['id' => $model_id, 'scene' => ModelScene::CREATIVE_STORYBOARDS['value'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $actors = [];
        foreach ($PluginShortplayDramaEpisode->actors as $item) {
            $actors[] = "{$item->actor->name}{{$item->actor->actor_id}}";
        }
        $scene_list = [];
        $PluginShortplayDramaScene = PluginShortplayDramaScene::where(['drama_id' => $PluginShortplayDrama->id, 'episode_id' => $PluginShortplayDramaEpisode->id])->select();
        foreach ($PluginShortplayDramaScene as $item) {
            $scene_list[] = "{$item->scene_space}·{$item->scene_location}·{$item->scene_time}·{$item->scene_weather}";
        }
        $prompt = $PluginShortplayDramaEpisode->content;
        $NextPluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['drama_id' => $PluginShortplayDrama->id, 'episode_no' => $PluginShortplayDramaEpisode->episode_no + 1])->find();
        if ($NextPluginShortplayDramaEpisode) {
            $next_episode_content = $NextPluginShortplayDramaEpisode->content;
        } else {
            $next_episode_content = '';
        }
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['drama_id' => $PluginShortplayDrama->id, 'episode_id' => $PluginShortplayDramaEpisode->id])->order('sort asc')->find();
        $NextPluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['drama_id' => $PluginShortplayDrama->id, 'episode_id' => $PluginShortplayDramaEpisode->id, 'sort' => $PluginShortplayDramaStoryboard->sort + 1])->order('sort asc')->find();
        if ($NextPluginShortplayDramaStoryboard) {
            $next_description = $NextPluginShortplayDramaStoryboard->description;
        } else {
            $next_description = '';
        }
        $previous_dialogues = '';
        $PreviousPluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['drama_id' => $PluginShortplayDrama->id, 'episode_id' => $PluginShortplayDramaEpisode->id, 'sort' => $PluginShortplayDramaStoryboard->sort - 1])->order('sort asc')->find();
        if ($PreviousPluginShortplayDramaStoryboard) {
            $previous_description = $PreviousPluginShortplayDramaStoryboard->description;
            $PreviousPluginShortplayDramaStoryboardDialogue = PluginShortplayDramaStoryboardDialogue::where(['storyboard_id' => $PreviousPluginShortplayDramaStoryboard->id])->order('sort asc')->select();
            if ($PreviousPluginShortplayDramaStoryboardDialogue) {
                foreach ($PreviousPluginShortplayDramaStoryboardDialogue as $item) {
                    $previous_dialogues .= "{$item->actor->name}：{$item->content}\n";
                }
            } else {
                $previous_dialogues = '';
            }
        } else {
            $previous_description = '';
        }
        $task_id = uniqid('local-');
        $data = [
            'channels_uid' => $request->channels_uid,
            'uid' => $request->uid,
            'drama_id' => $PluginShortplayDrama->id,
            'episode_id' => $PluginShortplayDramaEpisode->id,
            'task_id' => $task_id,
            'params' => [
                'model' => $PluginModel->model_id,
                'assistant' => $PluginModel->assistant_id,
                'form_data' => [
                    'prompt' => $prompt,
                    'next_episode_content' => $next_episode_content,
                    'description' => $PluginShortplayDramaStoryboard->description,
                    'storyboard_num' => $PluginShortplayDramaStoryboard->sort,
                    'previous_description' => $previous_description,
                    'previous_dialogues' => $previous_dialogues,
                    'storyboard_sum' => PluginShortplayDramaStoryboard::where(['drama_id' => $PluginShortplayDrama->id, 'episode_id' => $PluginShortplayDramaEpisode->id])->order('sort asc')->count(),
                    'next_description' => $next_description,
                    // 'episode_outline' => $outline,
                    'episode_num' => $PluginShortplayDrama->episode_num,
                    'episode_duration' => $PluginShortplayDrama->episode_duration,
                    'episode_no' => $PluginShortplayDramaEpisode->episode_no,
                    'scene_list' => implode(',', $scene_list),
                    'actors' => implode(',', $actors)
                ]
            ]
        ];
        Db::startTrans();
        try {
            $PluginModelTask = new PluginModelTask();
            $PluginModelTask->channels_uid = $request->channels_uid;
            $PluginModelTask->uid = $request->uid;
            $PluginModelTask->model_id = $model_id;
            $PluginModelTask->model_type = ModelType::CHAT['value'];
            $PluginModelTask->alias_id = $PluginShortplayDramaEpisode->id;
            $PluginModelTask->scene = ModelScene::CREATIVE_STORYBOARDS['value'];
            $PluginModelTask->status = ModelTaskStatus::WAIT['value'];
            $PluginModelTask->task_id = $task_id;
            $PluginModelTask->execution_count = 0;
            $PluginModelTask->expectation_execution_count = PluginShortplayDramaStoryboard::where(['drama_id' => $PluginShortplayDrama->id, 'episode_id' => $PluginShortplayDramaEpisode->id])->order('sort asc')->count();
            $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s', strtotime('+10 seconds'));
            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point * $PluginModelTask->expectation_execution_count, PointsBillScene::CONSUME['value'], null, '生成分镜', true);
            $PluginModelTask->consume_ids = $consume_ids;
            $PluginModelTask->save();
            $PluginModelTaskResult = new PluginModelTaskResult();
            $PluginModelTaskResult->task_id = $PluginModelTask->id;
            $PluginModelTaskResult->channels_uid = $request->channels_uid;
            $PluginModelTaskResult->params = $data;
            $PluginModelTaskResult->save();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->exception($th);
        }
        return $this->success('生成中');
    }
    public function sceneImage(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $episode_id = $request->post('episode_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->with('style')->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['drama_id' => $PluginShortplayDrama->id, 'id' => $episode_id])->with('actors')->find();
        if (!$PluginShortplayDramaEpisode) {
            return $this->fail('分集不存在');
        }
        $selectData = [];
        $id = $request->post('id');
        if (!empty($id)) {
            $PluginShortplayDramaScene = PluginShortplayDramaScene::where(['id' => $id, 'drama_id' => $PluginShortplayDrama->id, 'episode_id' => $PluginShortplayDramaEpisode->id])->find();
            if (!$PluginShortplayDramaScene) {
                return $this->fail('场景不存在');
            }
            $title = $request->post('title');
            if (!empty($title)) {
                $PluginShortplayDramaScene->title = $title;
            }
            $scene_space = $request->post('scene_space');
            if (!empty($scene_space)) {
                $PluginShortplayDramaScene->scene_space = $scene_space;
            }
            $scene_location = $request->post('scene_location');
            if (!empty($scene_location)) {
                $PluginShortplayDramaScene->scene_location = $scene_location;
            }
            $scene_time = $request->post('scene_time');
            if (!empty($scene_time)) {
                $PluginShortplayDramaScene->scene_time = $scene_time;
            }
            $scene_weather = $request->post('scene_weather');
            if (!empty($scene_weather)) {
                $PluginShortplayDramaScene->scene_weather = $scene_weather;
            }
            $description = $request->post('description');
            if (!empty($description)) {
                $PluginShortplayDramaScene->description = $description;
            }
            $atmosphere = $request->post('atmosphere');
            if (!empty($atmosphere)) {
                $PluginShortplayDramaScene->atmosphere = $atmosphere;
            }
            $PluginShortplayDramaScene->save();
            $item = [
                'id' => $PluginShortplayDramaScene->id,
                'title' => $PluginShortplayDramaScene->title,
                'scene_space' => $PluginShortplayDramaScene->scene_space,
                'scene_location' => $PluginShortplayDramaScene->scene_location,
                'scene_time' => $PluginShortplayDramaScene->scene_time,
                'scene_weather' => $PluginShortplayDramaScene->scene_weather,
                'description' => $PluginShortplayDramaScene->description,
                'atmosphere' => $PluginShortplayDramaScene->atmosphere,
                'style' => $PluginShortplayDrama->style->prompts,
                'aspect_ratio' => $PluginShortplayDrama->aspect_ratio
            ];
            $reference_image = $request->post('reference_image');
            if (!empty($reference_image)) {
                $item['images'] = [$reference_image];
            }
            $selectData[] = $item;
        } else {
            $PluginShortplayDramaScene = PluginShortplayDramaScene::where(['drama_id' => $PluginShortplayDrama->id, 'episode_id' => $PluginShortplayDramaEpisode->id])->select();
            foreach ($PluginShortplayDramaScene as $item) {
                $state = PluginModelTask::processing(['alias_id' => $item->id, 'scene' => ModelScene::SCENE_IMAGE['value']]);
                if ($item->image || $state > 0) {
                    continue;
                }
                $selectData[] = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'scene_space' => $item->scene_space,
                    'scene_location' => $item->scene_location,
                    'scene_time' => $item->scene_time,
                    'scene_weather' => $item->scene_weather,
                    'description' => $item->description,
                    'atmosphere' => $item->atmosphere,
                    'style' => $PluginShortplayDrama->style->prompts,
                    'aspect_ratio' => $PluginShortplayDrama->aspect_ratio
                ];
            }
        }
        if (empty($selectData)) {
            return $this->fail('场景不存在');
        }
        $model_id = $request->post('model_id');
        $PluginModel = PluginModel::where(['id' => $model_id, 'scene' => ModelScene::SCENE_IMAGE['value'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $successIds = [];
        foreach ($selectData as $item) {
            $data = [
                'assistant' => $PluginModel->assistant_id,
                'model' => $PluginModel->model_id,
                'form_data' => array_merge($item, [
                    'notify_url' => 'https://' . $request->host() . '/app/model/Notify/draw'
                ])
            ];
            Db::startTrans();
            try {
                $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point, PointsBillScene::CONSUME['value'], null, '生成场景图片', true);
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                return $this->fail($th->getMessage());
            }
            try {
                $result = Yidevs::DrawAssistantTIGI($request->channels_uid, $data);
                $successIds[] = $item['id'];
                Db::startTrans();
                try {
                    $PluginModelTask = new PluginModelTask();
                    $PluginModelTask->channels_uid = $request->channels_uid;
                    $PluginModelTask->uid = $request->uid;
                    $PluginModelTask->model_id = $model_id;
                    $PluginModelTask->model_type = ModelType::DRAW['value'];
                    $PluginModelTask->alias_id = $item['id'];
                    $PluginModelTask->scene = ModelScene::SCENE_IMAGE['value'];
                    $PluginModelTask->status = ModelTaskStatus::PROCESSING['value'];
                    $PluginModelTask->task_id = $result['task_id'];
                    $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s');
                    $PluginModelTask->consume_ids = $consume_ids;
                    $PluginModelTask->save();
                    $PluginModelTaskResult = new PluginModelTaskResult();
                    $PluginModelTaskResult->task_id = $PluginModelTask->id;
                    $PluginModelTaskResult->channels_uid = $request->channels_uid;
                    $PluginModelTaskResult->params = $data;
                    $PluginModelTaskResult->save();
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                    throw $th;
                }
            } catch (\Throwable $th) {
                if (!empty($consume_ids)) {
                    Db::startTrans();
                    try {
                        Account::refund($request->uid, $request->channels_uid, $consume_ids);
                        Db::commit();
                    } catch (\Throwable $th) {
                        Db::rollback();
                    }
                }
                Log::error('GenerateSceneImage Error:' . $th->getMessage() . PHP_EOL . $th->getTraceAsString());
            }
        }
        return $this->resData($successIds);
    }
    public function storyboardImage(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->with('style')->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $storyboard_id = $request->post('storyboard_id');
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id, 'drama_id' => $drama_id])->find();
        if (!$PluginShortplayDramaStoryboard) {
            return $this->fail('分镜不存在');
        }
        $model_id = $request->post('model_id');
        $PluginModel = PluginModel::where(['id' => $model_id, 'scene' => ModelScene::STORYBOARD_IMAGE['value'], 'state' => State::YES['value']])->find();
        $images = [];
        $PluginShortplayDramaScene = PluginShortplayDramaScene::where(['id' => $PluginShortplayDramaStoryboard->scene_id])->find();
        if (!$PluginShortplayDramaScene) {
            return $this->fail('分镜场景不存在');
        }
        if (!$PluginShortplayDramaScene->image) {
            return $this->fail('分镜场景图片不存在');
        }
        $storyboards = $request->post('storyboards');
        if ($storyboards) {
            $QuotePluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['drama_id' => $drama_id])->whereIn('id', $storyboards)->order('sort', 'asc')->select();
            foreach ($QuotePluginShortplayDramaStoryboard as $item) {
                $images[] = $item->image;
            }
        } else {
            $images[] = $PluginShortplayDramaScene->image;
        }
        $PluginShortplayDramaStoryboardActor = PluginShortplayDramaStoryboardActor::where(['storyboard_id' => $storyboard_id])->with('actor')->select();
        foreach ($PluginShortplayDramaStoryboardActor as $item) {
            if ($item->actor->status != ActorStatus::GENERATED['value']) {
                return $this->fail('演员状态异常：' . $item->actor->name);
            }
            if ($item->character_look_id && $item->headimg) {
                $images[] = $item->headimg;
            } else {
                $images[] = $item->actor->headimg;
            }
        }
        $PluginShortplayDramaStoryboardProp = PluginShortplayDramaStoryboardProp::where(['storyboard_id' => $storyboard_id])->with('prop')->select();
        foreach ($PluginShortplayDramaStoryboardProp as $item) {
            if ($item->prop->status != PropStatus::GENERATED['value']) {
                return $this->fail('物品状态异常：' . $item->prop->name);
            }
            if ($item->prop->image) {
                $images[] = $item->prop->image;
            }
        }
        $prompt = $request->post('prompt');
        if (!$prompt) {
            $prompts = [];
            $prompts[] = '镜头设计：';
            $prompts[] = '-景别：' . $PluginShortplayDramaStoryboard->shot_type;
            $prompts[] = '-镜头视角：' . $PluginShortplayDramaStoryboard->shot_angle;
            $prompts[] = "\n\n画面描述：\n" . $PluginShortplayDramaStoryboard->description;
            $prompt = implode("\n", $prompts) . "\n";
        }
        $PluginShortplayDramaStoryboard->image_prompt = $prompt;
        $PluginShortplayDramaStoryboard->save();
        $data = [
            'assistant' => $PluginModel->assistant_id,
            'model' => $PluginModel->model_id,
            'form_data' => [
                'images' => $images,
                'prompt' => $prompt,
                'style' => $PluginShortplayDrama->style->prompts,
                'notify_url' => 'https://' . $request->host() . '/app/model/Notify/draw',
                'aspect_ratio' => $PluginShortplayDrama->aspect_ratio
            ]
        ];
        Db::startTrans();
        try {
            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point, PointsBillScene::CONSUME['value'], null, '生成分镜图片', true);
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        try {
            $result = Yidevs::DrawAssistantTIGI($request->channels_uid, $data);
            Db::startTrans();
            try {
                $PluginModelTask = new PluginModelTask();
                $PluginModelTask->channels_uid = $request->channels_uid;
                $PluginModelTask->uid = $request->uid;
                $PluginModelTask->model_id = $PluginModel->id;
                $PluginModelTask->model_type = ModelType::DRAW['value'];
                $PluginModelTask->alias_id = $PluginShortplayDramaStoryboard->id;
                $PluginModelTask->scene = ModelScene::STORYBOARD_IMAGE['value'];
                $PluginModelTask->status = ModelTaskStatus::PROCESSING['value'];
                $PluginModelTask->task_id = $result['task_id'];
                $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s');
                $PluginModelTask->consume_ids = $consume_ids;
                $PluginModelTask->save();
                $PluginModelTaskResult = new PluginModelTaskResult();
                $PluginModelTaskResult->task_id = $PluginModelTask->id;
                $PluginModelTaskResult->channels_uid = $request->channels_uid;
                $PluginModelTaskResult->params = $data;
                $PluginModelTaskResult->save();
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                throw $th;
            }
        } catch (\Throwable $th) {
            if (!empty($consume_ids)) {
                Db::startTrans();
                try {
                    Account::refund($request->uid, $request->channels_uid, $consume_ids);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                }
            }
            return $this->fail($th->getMessage());
        }
        return $this->success('生成中');
    }
    public function OptimizeStoryboardPrompt(Request $request)
    {
        $prompt = $request->post('prompt');
        if (!$prompt) {
            return $this->fail('优化提示词不能为空');
        }
        $data = [
            'model' => 2,
            "messages" => [
                [
                    "content" => "你是一位分镜大师，擅长使用AI工具",
                    "role" => "system"
                ],
            ]
        ];
        $type = $request->post('type');
        if ($type == ModelScene::STORYBOARD_IMAGE['value']) {
            $data['messages'][] = [
                "content" => "优化以下提示词用于生成高质量图片：\n" . $prompt . "\n\n只需要输出优化后的提示词，不要输出其他内容",
                "role" => "user"
            ];
        } else if ($type == ModelScene::STORYBOARD_VIDEO['value']) {
            $data['messages'][] = [
                "content" => "优化以下提示词用于生成高质量视频：\n" . $prompt . "\n\n只需要输出优化后的提示词，不要输出其他内容",
                "role" => "user"
            ];
        }
        try {
            $result = [];
            Yidevs::ChatCompletions($request->channels_uid, $data, function ($ch, $data) use (&$result) {
                if (empty($data['choices'][0])) {
                    return $data;
                }
                $choices = $data['choices'][0];
                if (!empty($choices['message']['content'])) {
                    $result[] = $choices['message']['content'];
                } elseif (!empty($choices['delta']['content'])) {
                    $result[] = $choices['delta']['content'];
                }
                return $data;
            });
            return $this->resData(['prompt' => implode('', $result)]);
        } catch (\Throwable $th) {
            return $this->fail($th->getMessage());
        }
        return $this->fail('优化失败');
    }
    public function characterLookCostume(Request $request)
    {
        $id = $request->post('id');
        $PluginShortplayCharacterLook = PluginShortplayCharacterLook::where(['id' => $id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayCharacterLook) {
            return $this->fail('装扮不存在');
        }
        $model_id = $request->post('model_id');
        $PluginModel = PluginModel::where(['id' => $model_id, 'scene' => ModelScene::CHARACTER_LOOK_COSTUME['value'], 'state' => State::YES['value']])->find();
        $images = [];
        $costume_url = $request->post('costume_url');
        $costume_reference_state = (bool)$request->post('costume_reference_state');
        if ($costume_reference_state && $costume_url) {
            $images[] = $costume_url;
        }
        $style = $request->post('style');
        if ($style) {
            $PluginShortplayStyle = PluginShortplayStyle::where(['id' => $style])->find();
            if ($PluginShortplayStyle) {
                $style = $PluginShortplayStyle->prompts;
            }
        } else {
            if ($PluginShortplayCharacterLook->drama_id) {
                $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $PluginShortplayCharacterLook->drama_id])->with('style')->find();
                if ($PluginShortplayDrama) {
                    $style = $PluginShortplayDrama->style->prompts;
                }
            }
        }
        if (!$style) {
            return $this->fail('风格不存在');
        }
        $data = [
            'assistant' => $PluginModel->assistant_id,
            'model' => $PluginModel->model_id,
            'form_data' => [
                'images' => $images,
                'hair_style' => $PluginShortplayCharacterLook->hair_style,
                'costume' => $PluginShortplayCharacterLook->costume,
                'style' => $style,
                'aspect_ratio' => '1:1',
                'notify_url' => 'https://' . $request->host() . '/app/model/Notify/draw'
            ]
        ];
        Db::startTrans();
        try {
            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point, PointsBillScene::CONSUME['value'], null, '生成角色换装', true);
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        try {
            $result = Yidevs::DrawAssistantTIGI($request->channels_uid, $data);
            Db::startTrans();
            try {
                $PluginModelTask = new PluginModelTask();
                $PluginModelTask->channels_uid = $request->channels_uid;
                $PluginModelTask->uid = $request->uid;
                $PluginModelTask->model_id = $PluginModel->id;
                $PluginModelTask->model_type = ModelType::DRAW['value'];
                $PluginModelTask->alias_id = $PluginShortplayCharacterLook->id;
                $PluginModelTask->scene = ModelScene::CHARACTER_LOOK_COSTUME['value'];
                $PluginModelTask->status = ModelTaskStatus::PROCESSING['value'];
                $PluginModelTask->task_id = $result['task_id'];
                $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s');
                $PluginModelTask->consume_ids = $consume_ids;
                $PluginModelTask->save();
                $PluginModelTaskResult = new PluginModelTaskResult();
                $PluginModelTaskResult->task_id = $PluginModelTask->id;
                $PluginModelTaskResult->channels_uid = $request->channels_uid;
                $PluginModelTaskResult->params = $data;
                $PluginModelTaskResult->save();
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                throw $th;
            }
        } catch (\Throwable $th) {
            if (!empty($consume_ids)) {
                Db::startTrans();
                try {
                    Account::refund($request->uid, $request->channels_uid, $consume_ids);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                }
            }
            return $this->fail($th->getMessage());
        }
        return $this->success('生成中');
    }
    public function characterLook(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->with('style')->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $episode_id = $request->post('episode_id');
        $storyboard_id = $request->post('storyboard_id');
        $actor_id = $request->post('actor_id');
        $PluginShortplayActor = PluginShortplayActor::where(['id' => $actor_id])->whereOr([['uid', '=', $request->uid], ['uid', '=', null]])->find();
        if (!$PluginShortplayActor) {
            return $this->fail('演员不存在');
        }
        $character_look_id = $request->post('character_look_id');
        $PluginShortplayCharacterLook = PluginShortplayCharacterLook::where(['id' => $character_look_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayCharacterLook) {
            return $this->fail('装扮不存在');
        }
        if ($PluginShortplayCharacterLook->status !== ActorStatus::GENERATED['value']) {
            return $this->fail('装扮还未完成初始化');
        }
        $PluginShortplayActorCharacterLook = PluginShortplayActorCharacterLook::where(['actor_id' => $actor_id, 'character_look_id' => $PluginShortplayCharacterLook->id, 'uid' => $request->uid, 'status' => ActorStatus::PENDING['value']])->find();
        if ($PluginShortplayActorCharacterLook) {
            return $this->fail('演员换装中');
        }
        $actor_costume_model_id = $request->post('actor_costume_model_id');
        $PluginModel = PluginModel::where(['id' => $actor_costume_model_id, 'scene' => ModelScene::ACTOR_COSTUME['value'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('角色换装模型不存在');
        }
        $actor_costume_three_view_model_id = $request->post('actor_costume_three_view_model_id');
        $ThreeViewPluginModel = PluginModel::where(['id' => $actor_costume_three_view_model_id, 'scene' => ModelScene::ACTOR_COSTUME_THREE_VIEW['value'], 'state' => State::YES['value']])->find();
        if (!$ThreeViewPluginModel) {
            return $this->fail('角色换装三视图模型不存在');
        }
        $ids = [
            'channels_uid' => $request->channels_uid,
            'uid' => $request->uid,
            'model_id' => $PluginModel->id,
            'model_model_id' => $PluginModel->model_id,
            'model_points' => $PluginModel->point,
            'three_view_model_points' => $ThreeViewPluginModel->point,
            'assistant_id' => $PluginModel->assistant_id,
            'drama_id' => $drama_id,
            'episode_id' => $episode_id,
            'storyboard_id' => $storyboard_id,
            'actor_id' => $actor_id,
            'three_view_model_id' => $ThreeViewPluginModel->id,
            'three_view_model_model_id' => $ThreeViewPluginModel->model_id,
            'three_view_assistant_id' => $ThreeViewPluginModel->assistant_id,
            'character_look_id' => $PluginShortplayCharacterLook->id,
            'host' => $request->host(),
        ];
        $data = [
            'assistant' => $PluginModel->assistant_id,
            'model'     => $PluginModel->model_id,
            'form_data' => [
                'prompt'     => $PluginShortplayActor->remarks,
                'species'    => ActorSpeciesType::getText($PluginShortplayActor->species_type),
                'gender'     => ActorGender::getText($PluginShortplayActor->gender),
                'age'        => ActorAge::getText($PluginShortplayActor->age),
                'overall_style' => $PluginShortplayCharacterLook->overall_style,
                'status_note' => $PluginShortplayCharacterLook->status_note,
                'makeup' => $PluginShortplayCharacterLook->makeup,
                'hair_style' => $PluginShortplayCharacterLook->hair_style,
                'costume' => $PluginShortplayCharacterLook->costume,
                'style' => $PluginShortplayDrama->style->prompts,
                'aspect_ratio' => '1:1',
                'notify_url' => 'https://' . $ids['host'] . '/app/model/Notify/draw',
                'images' => [$PluginShortplayActor->headimg, $PluginShortplayActor->three_view_image, $PluginShortplayCharacterLook->costume_url]
            ]
        ];
        Db::startTrans();
        try {
            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point, PointsBillScene::CONSUME['value'], null, '生成角色换装', true);
            $three_view_consume_ids = Account::decPoints($request->uid, $request->channels_uid, $ThreeViewPluginModel->point, PointsBillScene::CONSUME['value'], null, '生成角色换装三视图', true);
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        Coroutine::create(function () use ($ids, $data, $consume_ids, $three_view_consume_ids) {
            try {

                $result = Yidevs::DrawAssistantTIGI($ids['channels_uid'], $data);

                Db::startTrans();
                try {
                    $PluginShortplayActorCharacterLook = new PluginShortplayActorCharacterLook();
                    $PluginShortplayActorCharacterLook->channels_uid = $ids['channels_uid'];
                    $PluginShortplayActorCharacterLook->uid = $ids['uid'];
                    $PluginShortplayActorCharacterLook->actor_id = $ids['actor_id'];
                    $PluginShortplayActorCharacterLook->status = ActorStatus::PENDING['value'];
                    $PluginShortplayActorCharacterLook->character_look_id = $ids['character_look_id'];
                    if ($ids['drama_id']) {
                        $PluginShortplayActorCharacterLook->drama_id = $ids['drama_id'];
                    }
                    if ($ids['episode_id']) {
                        $PluginShortplayActorCharacterLook->episode_id = $ids['episode_id'];
                    }
                    if ($ids['storyboard_id']) {
                        $PluginShortplayActorCharacterLook->storyboard_id = $ids['storyboard_id'];
                    }
                    $PluginShortplayActorCharacterLook->save();
                    $task = new PluginModelTask();
                    $task->channels_uid   = $ids['channels_uid'];
                    $task->uid            = $ids['uid'];
                    $task->model_id       = $ids['model_id'];
                    $task->model_type     = ModelType::DRAW['value'];
                    $task->alias_id       = $PluginShortplayActorCharacterLook->id;
                    $task->scene          = ModelScene::ACTOR_COSTUME['value'];
                    $task->status         = ModelTaskStatus::PROCESSING['value'];
                    $task->task_id        = $result['task_id'];
                    $task->last_heartbeat = date('Y-m-d H:i:s');
                    $task->consume_ids = $consume_ids;
                    $task->save();
                    $taskId = $task->id;
                    $taskResult = new PluginModelTaskResult();
                    $taskResult->task_id      = $task->id;
                    $taskResult->channels_uid = $ids['channels_uid'];
                    $taskResult->params       = $data;
                    $taskResult->save();

                    $data = [
                        'assistant' => $ids['three_view_assistant_id'],
                        'model'     => $ids['three_view_model_model_id'],
                        'form_data' => [
                            'images' => [],
                            'aspect_ratio' => '1:1',
                            'style' => $data['form_data']['style'],
                            'notify_url' => 'https://' . $ids['host'] . '/app/model/Notify/draw'
                        ]
                    ];
                    $task = new PluginModelTask();
                    $task->channels_uid   = $ids['channels_uid'];
                    $task->uid            = $ids['uid'];
                    $task->model_id       = $ids['three_view_model_id'];
                    $task->model_type     = ModelType::DRAW['value'];
                    $task->alias_id       = $PluginShortplayActorCharacterLook->id;
                    $task->scene          = ModelScene::ACTOR_COSTUME_THREE_VIEW['value'];
                    $task->pre_task_id    = $taskId;
                    $task->status         = ModelTaskStatus::WAIT['value'];
                    $task->task_id        = null;
                    $task->last_heartbeat = date('Y-m-d H:i:s', strtotime('+30 seconds'));
                    $task->consume_ids = $three_view_consume_ids;
                    $task->save();

                    $taskResult = new PluginModelTaskResult();
                    $taskResult->task_id      = $task->id;
                    $taskResult->channels_uid = $ids['channels_uid'];
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
                        Account::refund($ids['uid'], $ids['channels_uid'], $consume_ids);
                        Db::commit();
                    } catch (\Throwable $th) {
                        Db::rollback();
                    }
                }
                if (!empty($three_view_consume_ids)) {
                    Db::startTrans();
                    try {
                        Account::refund($ids['uid'], $ids['channels_uid'], $three_view_consume_ids);
                        Db::commit();
                    } catch (\Throwable $th) {
                        Db::rollback();
                    }
                }
                Log::error('GenerateActorImage Error:' . $e->getMessage());
                Push::send([
                    'uid' => $ids['uid'],
                    'channels_uid' => $ids['channels_uid'],
                    'event' => 'generate' . strtolower(str_replace('_', '', ModelScene::ACTOR_COSTUME['value'])),
                ], [
                    'actor_id' => $ids['actor_id'],
                    'drama_id' => $ids['drama_id'],
                    'episode_id' => $ids['episode_id'],
                    'storyboard_id' => $ids['storyboard_id'],
                ]);
            }
        });
        return $this->resData($PluginShortplayActor);
    }
    public function storyboardVideo(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $storyboard_id = $request->post('storyboard_id');
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id, 'drama_id' => $drama_id])->find();
        if (!$PluginShortplayDramaStoryboard) {
            return $this->fail('分镜不存在');
        }
        $model_id = $request->post('model_id');
        $PluginModel = PluginModel::where(['id' => $model_id, 'scene' => ModelScene::STORYBOARD_VIDEO['value'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $prompt = $request->post('prompt');
        if (!$prompt) {
            return $this->fail('提示词不能为空');
        }
        $PluginShortplayDramaStoryboard->video_prompt = $prompt;
        $first_image = $request->post('first_image');
        if (!$first_image) {
            return $this->fail('首帧图不能为空');
        }
        $last_image = $request->post('last_image');
        $negative_prompt = $request->post('negative_prompt');
        $duration = (int)$request->post('duration');
        if ($duration) {
            $PluginShortplayDramaStoryboard->duration = $duration;
        }
        $duration = max(5, min(15, floor($PluginShortplayDramaStoryboard->duration / 1000)));
        $PluginShortplayDramaStoryboard->save();
        $data = [
            'assistant' => $PluginModel->assistant_id,
            'model' => $PluginModel->model_id,
            'form_data' => [
                'prompt' => $prompt,
                'negative_prompt' => $negative_prompt,
                'first_image' => $first_image,
                'last_image' => $last_image,
                'duration' => $duration,
                'notify_url' => 'https://' . $request->host() . '/app/model/Notify/video',
                'aspect_ratio' => $PluginShortplayDrama->aspect_ratio
            ]
        ];
        Db::startTrans();
        try {
            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point, PointsBillScene::CONSUME['value'], null, '生成分镜视频', true);
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        try {
            $result = Yidevs::VideoAssistantTITV($request->channels_uid, $data);
            Db::startTrans();
            try {
                $PluginModelTask = new PluginModelTask();
                $PluginModelTask->channels_uid = $request->channels_uid;
                $PluginModelTask->uid = $request->uid;
                $PluginModelTask->model_id = $PluginModel->id;
                $PluginModelTask->model_type = ModelType::TOVIDEO['value'];
                $PluginModelTask->alias_id = $PluginShortplayDramaStoryboard->id;
                $PluginModelTask->scene = ModelScene::STORYBOARD_VIDEO['value'];
                $PluginModelTask->status = ModelTaskStatus::PROCESSING['value'];
                $PluginModelTask->task_id = $result['task_id'];
                $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s');
                $PluginModelTask->consume_ids = $consume_ids;
                $PluginModelTask->save();
                $PluginModelTaskResult = new PluginModelTaskResult();
                $PluginModelTaskResult->task_id = $PluginModelTask->id;
                $PluginModelTaskResult->channels_uid = $request->channels_uid;
                $PluginModelTaskResult->params = $data;
                $PluginModelTaskResult->image_path = $first_image;
                $PluginModelTaskResult->save();
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                throw $th;
            }
        } catch (\Throwable $th) {
            if (!empty($consume_ids)) {
                Db::startTrans();
                try {
                    Account::refund($request->uid, $request->channels_uid, $consume_ids);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                }
            }
            return $this->fail($th->getMessage());
        }
        return $this->success('生成中');
    }
    public function storyboardDialogueVoice(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $storyboard_id = $request->post('storyboard_id');
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id, 'drama_id' => $drama_id])->find();
        if (!$PluginShortplayDramaStoryboard) {
            return $this->fail('分镜不存在');
        }
        $dialogue_id = $request->post('dialogue_id');
        $PluginShortplayDramaStoryboardDialogue = PluginShortplayDramaStoryboardDialogue::where(['id' => $dialogue_id, 'storyboard_id' => $PluginShortplayDramaStoryboard->id])->with('actor')->find();
        if (!$PluginShortplayDramaStoryboardDialogue) {
            return $this->fail('台词不存在');
        }
        $voice = $request->post('voice');
        if (empty($voice)) {
            $voice = $PluginShortplayDramaStoryboardDialogue->voice;
        }
        if (empty($voice)) {
            $ActorVoiceLevel = [
                [
                    'model' => PluginShortplayDramaStoryboardActor::class,
                    'where' => [
                        'storyboard_id' => $PluginShortplayDramaStoryboard->id,
                        'actor_id' => $PluginShortplayDramaStoryboardDialogue->actor_id
                    ]
                ],
                [
                    'model' => PluginShortplayDramaEpisodeActor::class,
                    'where' => [
                        'episode_id' => $PluginShortplayDramaStoryboard->episode_id,
                        'actor_id' => $PluginShortplayDramaStoryboardDialogue->actor_id
                    ]
                ],
                [
                    'model' => PluginShortplayDramaActor::class,
                    'where' => [
                        'drama_id' => $PluginShortplayDrama->id,
                        'actor_id' => $PluginShortplayDramaStoryboardDialogue->actor_id
                    ]
                ],
                [
                    'level' => 'actor',
                    'model' => PluginShortplayActor::class,
                    'where' => [
                        'actor_id' => $PluginShortplayDramaStoryboardDialogue->actor_id
                    ]
                ]
            ];
            foreach ($ActorVoiceLevel as $item) {
                $PluginActorVoice = $item['model']::where($item['where'])->find();
                if ($PluginActorVoice && $PluginActorVoice->voice) {
                    $voice = $PluginActorVoice->voice;
                    break;
                }
            }
        }
        $prosody_volume = $request->post('prosody_volume', null);
        $prosody_speed = $request->post('prosody_speed', null);
        if ($prosody_volume !== null) {
            $PluginShortplayDramaStoryboardDialogue->prosody_volume = $prosody_volume;
        }
        if ($prosody_speed !== null) {
            $PluginShortplayDramaStoryboardDialogue->prosody_speed = $prosody_speed;
        }
        $content = $request->post('content');
        if ($content) {
            $PluginShortplayDramaStoryboardDialogue->content = $content;
        }
        $PluginShortplayDramaStoryboardDialogue->save();
        $PluginModel = PluginModel::where(['id' => $voice['model_id'], 'scene' => ModelScene::DIALOGUE_VOICE['value'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $voice_id = $voice['voice_id'];
        $voice_channel = $voice['voice_channel'];
        $clone_voice_id = '';
        if ($voice_channel === 'self') {
            $PluginModelVoice = PluginModelVoice::where(['id' => $voice['id']])->find();
            if (!$PluginModelVoice) {
                return $this->fail('音色不存在');
            }
            $voice_id = '';
            $clone_voice_id = $PluginModelVoice->voice_id;
        }
        if (!$clone_voice_id && !$voice_id) {
            return $this->fail('未找到音色');
        }
        $language = empty($voice['selected_language']['value']) ? VoiceLanguage::ZH['value'] : $voice['selected_language']['value'];
        $emotion = empty($voice['selected_emotion']['value']) ? VoiceEmotion::NEUTRAL['value'] : $voice['selected_emotion']['value'];
        $volume = $PluginShortplayDramaStoryboardDialogue->prosody_volume;
        $speed = $PluginShortplayDramaStoryboardDialogue->prosody_speed;
        $data = [
            'assistant' => $PluginModel->assistant_id,
            'model' => $PluginModel->model_id,
            'form_data' => [
                'text' => $PluginShortplayDramaStoryboardDialogue->content,
                'voice_id' => $voice_id,
                'clone_voice_id' => $clone_voice_id,
                'emotion' => $emotion,
                'volume' => $volume,
                'speed' => $speed,
                'language' => [$language],
                'notify_url' => 'https://' . $request->host() . '/app/model/Notify/audio',
            ]
        ];
        Db::startTrans();
        try {
            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point, PointsBillScene::CONSUME['value'], null, '生成台词语音', true);
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        try {
            $result = Yidevs::AudioAssistantTTS($request->channels_uid, $data);
            Db::startTrans();
            try {
                $PluginModelTask = new PluginModelTask();
                $PluginModelTask->channels_uid = $request->channels_uid;
                $PluginModelTask->uid = $request->uid;
                $PluginModelTask->model_id = $PluginModel->id;
                $PluginModelTask->model_type = ModelType::AUDIO['value'];
                $PluginModelTask->alias_id = $PluginShortplayDramaStoryboardDialogue->id;
                $PluginModelTask->scene = ModelScene::DIALOGUE_VOICE['value'];
                $PluginModelTask->status = ModelTaskStatus::PROCESSING['value'];
                $PluginModelTask->task_id = $result['task_id'];
                $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s');
                $PluginModelTask->consume_ids = $consume_ids;
                $PluginModelTask->save();
                $PluginModelTaskResult = new PluginModelTaskResult();
                $PluginModelTaskResult->task_id = $PluginModelTask->id;
                $PluginModelTaskResult->channels_uid = $request->channels_uid;
                $PluginModelTaskResult->params = $data;
                $PluginModelTaskResult->save();
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                throw $th;
            }
        } catch (\Throwable $th) {
            if (!empty($consume_ids)) {
                Db::startTrans();
                try {
                    Account::refund($request->uid, $request->channels_uid, $consume_ids);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                }
            }
            return $this->fail($th->getMessage());
        }
        return $this->success();
    }
    public function storyboardNarrationVoice(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $storyboard_id = $request->post('storyboard_id');
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id, 'drama_id' => $drama_id])->find();
        if (!$PluginShortplayDramaStoryboard) {
            return $this->fail('分镜不存在');
        }
        $voice = $request->post('voice');
        if (!empty($voice)) {
            if (!empty($voice['emotions_enum']) && empty($voice['selected_emotion'])) {
                $voice['selected_emotion'] = $voice['emotions_enum'][0];
            }
            if (empty($voice['selected_language'])) {
                $voice['selected_language'] = VoiceLanguage::ZH;
            }
            $PluginShortplayDrama->voice = $voice;
        }
        $voice = $PluginShortplayDrama->voice;
        $prosody_volume = $request->post('prosody_volume', null);
        $prosody_speed = $request->post('prosody_speed', null);
        if ($prosody_volume !== null) {
            $PluginShortplayDrama->prosody_volume = $prosody_volume;
        }
        if ($prosody_speed !== null) {
            $PluginShortplayDrama->prosody_speed = $prosody_speed;
        }
        $narration = $request->post('narration');
        if ($narration) {
            $PluginShortplayDramaStoryboard->narration = $narration;
            $PluginShortplayDramaStoryboard->save();
        }
        $PluginShortplayDrama->save();
        $PluginModel = PluginModel::where(['id' => $voice['model_id'], 'scene' => ModelScene::STORYBOARD_NARRATION_VOICE['value'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $voice_id = $voice['voice_id'];
        $voice_channel = $voice['voice_channel'];
        $clone_voice_id = '';
        if ($voice_channel === 'self') {
            $PluginModelVoice = PluginModelVoice::where(['id' => $voice['id']])->find();
            if (!$PluginModelVoice) {
                return $this->fail('音色不存在');
            }
            $voice_id = '';
            $clone_voice_id = $PluginModelVoice->voice_id;
        }
        if (!$clone_voice_id && !$voice_id) {
            return $this->fail('未找到音色');
        }
        $language = empty($voice['selected_language']['value']) ? VoiceLanguage::ZH['value'] : $voice['selected_language']['value'];
        $emotion = empty($voice['selected_emotion']['value']) ? VoiceEmotion::NEUTRAL['value'] : $voice['selected_emotion']['value'];
        $volume = $PluginShortplayDrama->prosody_volume;
        $speed = $PluginShortplayDrama->prosody_speed;
        $data = [
            'assistant' => $PluginModel->assistant_id,
            'model' => $PluginModel->model_id,
            'form_data' => [
                'text' => $PluginShortplayDramaStoryboard->narration,
                'voice_id' => $voice_id,
                'clone_voice_id' => $clone_voice_id,
                'emotion' => $emotion,
                'volume' => $volume,
                'speed' => $speed,
                'language' => [$language],
                'notify_url' => 'https://' . $request->host() . '/app/model/Notify/audio',
            ]
        ];
        Db::startTrans();
        try {
            $consume_ids = Account::decPoints($request->uid, $request->channels_uid, $PluginModel->point, PointsBillScene::CONSUME['value'], null, '生成分镜旁白语音', true);
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        try {
            $result = Yidevs::AudioAssistantTTS($request->channels_uid, $data);
            Db::startTrans();
            try {
                $PluginModelTask = new PluginModelTask();
                $PluginModelTask->channels_uid = $request->channels_uid;
                $PluginModelTask->uid = $request->uid;
                $PluginModelTask->model_id = $PluginModel->id;
                $PluginModelTask->model_type = ModelType::AUDIO['value'];
                $PluginModelTask->alias_id = $PluginShortplayDramaStoryboard->id;
                $PluginModelTask->scene = ModelScene::STORYBOARD_NARRATION_VOICE['value'];
                $PluginModelTask->status = ModelTaskStatus::PROCESSING['value'];
                $PluginModelTask->task_id = $result['task_id'];
                $PluginModelTask->last_heartbeat = date('Y-m-d H:i:s');
                $PluginModelTask->consume_ids = $consume_ids;
                $PluginModelTask->save();
                $PluginModelTaskResult = new PluginModelTaskResult();
                $PluginModelTaskResult->task_id = $PluginModelTask->id;
                $PluginModelTaskResult->channels_uid = $request->channels_uid;
                $PluginModelTaskResult->params = $data;
                $PluginModelTaskResult->save();
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                throw $th;
            }
        } catch (\Throwable $th) {
            if (!empty($consume_ids)) {
                Db::startTrans();
                try {
                    Account::refund($request->uid, $request->channels_uid, $consume_ids);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                }
            }
            return $this->fail($th->getMessage());
        }
        return $this->success();
    }
}
