<?php

namespace plugin\shortplay\app\api\controller;

use app\Basic;
use app\expose\enum\State;
use plugin\control\utils\yidevs\Yidevs;
use plugin\finance\expose\helper\Account;
use plugin\finance\utils\enum\PointsBillScene;
use plugin\model\app\model\PluginModel;
use plugin\notification\expose\helper\Push;
use plugin\shortplay\app\model\PluginShortplayActor;
use plugin\shortplay\app\model\PluginShortplayDrama;
use plugin\shortplay\app\model\PluginShortplayDramaActor;
use plugin\shortplay\utils\enum\ActorAge;
use plugin\shortplay\utils\enum\ActorGender;
use plugin\shortplay\utils\enum\ActorSpeciesType;
use plugin\shortplay\utils\enum\ActorStatus;
use support\Log;
use support\Request;
use think\facade\Db;
use Workerman\Coroutine;

class IndexController extends Basic
{
    public function index()
    {
        return $this->success('Hello World');
    }
    public function submit(Request $request)
    {
        $post = $request->post();
        if (empty($post['script'])) {
            return $this->fail('请选择你要创建短视频或短剧');
        }
        if (empty($post['style'])) {
            return $this->fail('请选择短剧风格画风');
        }
        if (empty($post['aspect_ratio'])) {
            return $this->fail('请选择短剧比例');
        }
        if (empty($post['episode_sum'])) {
            return $this->fail('请选择短剧集数');
        }
        if (empty($post['episode_duration'])) {
            return $this->fail('请选择短剧每集时长');
        }
        $episode_sum = abs($post['episode_sum']);
        $episode_duration = abs($post['episode_duration']);
        if ($episode_sum <= 0 || $episode_sum > 500) {
            switch ($post['script']) {
                case 'script':
                    $episode_sum = 1;
                    $episode_duration = 300;
                    break;
                case 'drama':
                    $episode_sum = 80;
                    break;
            }
        }
        if ($episode_duration < 60 || $episode_duration > 300) {
            $episode_duration = 60;
        }
        if (empty($post['prompt'])) {
            return $this->fail('请提供一个创意想法');
        }
        if (empty($post['model'])) {
            return $this->fail('请选择模型');
        }
        $PluginModel = PluginModel::where(['id' => $post['model'], 'state' => State::YES['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $prompt = $post['prompt'];
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
        $PluginShortplayDrama = new PluginShortplayDrama();
        $PluginShortplayDrama->channels_uid = $request->channels_uid;
        $PluginShortplayDrama->uid = $request->uid;
        $PluginShortplayDrama->model_id = $PluginModel->id;
        $PluginShortplayDrama->style_id = $post['style'];
        $PluginShortplayDrama->script = $post['script'];
        $PluginShortplayDrama->aspect_ratio = $post['aspect_ratio'];
        $PluginShortplayDrama->episode_sum = $episode_sum;
        $PluginShortplayDrama->episode_duration = $episode_duration;
        $PluginShortplayDrama->state = State::NO['value'];
        $PluginShortplayDrama->save();
        $data = [
            'channels_uid' => $request->channels_uid,
            'uid' => $request->uid,
            'model_id' => $PluginModel->id,
            'amount' => $PluginModel->point,
            'uuid' => $PluginShortplayDrama->id,
            'params' => [
                'model' => $PluginModel->model_id,
                'assistant' => $PluginModel->assistant_id,
                'form_data' => [
                    'prompt' => $prompt,
                    'species' => implode(',', $species),
                    'gender' => implode(',', $gender),
                    'age' => implode(',', $age),
                    'episode_sum' => $episode_sum,
                    'episode_duration' => $episode_duration,
                ]
            ],
        ];
        Coroutine::create(function () use ($data) {
            try {
                Db::startTrans();
                try {
                    $ids = Account::decPoints($data['uid'], $data['channels_uid'], $data['amount'], PointsBillScene::CONSUME['value'], null, '创建短剧', true);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                    Push::send([
                        'uid' => $data['uid'],
                        'channels_uid' => $data['channels_uid'],
                        'event' => 'generatecreatedrama'
                    ], [
                        'uuid' => $data['uuid'],
                        'msg' => $th->getMessage()
                    ]);
                    PluginShortplayDrama::where(['id' => $data['uuid']])->delete();
                    return;
                }
                $result = Yidevs::ChatAssistantCompletions($data['channels_uid'], $data['params']);
                if (empty($result)) {
                    throw new \Exception('创建短剧失败');
                }
                Db::startTrans();
                try {
                    $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $data['uuid']])->find();
                    $PluginShortplayDrama->state = State::YES['value'];
                    $PluginShortplayDrama->title = $result['title'];
                    $PluginShortplayDrama->description = $result['description'];
                    $PluginShortplayDrama->background_description = $result['background_description'];
                    $PluginShortplayDrama->outline = $result['outline'];
                    $PluginShortplayDrama->overall_hook = $result['overall_hook'];
                    $PluginShortplayDrama->core_catharsis_mechanism = $result['core_catharsis_mechanism'];
                    $PluginShortplayDrama->main_conflict = $result['main_conflict'];
                    $PluginShortplayDrama->relationship_mainline = $result['relationship_mainline'];
                    $PluginShortplayDrama->episode_num = 0;
                    $PluginShortplayDrama->save();
                    if (!empty($result['actor'])) {
                        $PluginShortplayActors = PluginShortplayActor::whereIn('actor_id', $result['actor'])->field('id,actor_id')->select();
                        foreach ($PluginShortplayActors as $PluginShortplayActor) {
                            $PluginShortplayDramaActor = new PluginShortplayDramaActor();
                            $PluginShortplayDramaActor->channels_uid = $data['channels_uid'];
                            $PluginShortplayDramaActor->drama_id = $PluginShortplayDrama->id;
                            $PluginShortplayDramaActor->actor_id = $PluginShortplayActor->id;
                            $PluginShortplayDramaActor->save();
                        }
                    }
                    if (!empty($result['create_actor'])) {
                        foreach ($result['create_actor'] as $actor) {
                            $PluginShortplayActor = PluginShortplayActor::where(['name' => $actor['name'], 'channels_uid' => $data['channels_uid'], 'drama_id' => $PluginShortplayDrama->id])->field('id,actor_id')->find();
                            if (!$PluginShortplayActor) {
                                $PluginShortplayActor = new PluginShortplayActor();
                                $PluginShortplayActor->channels_uid = $data['channels_uid'];
                                $PluginShortplayActor->drama_id = $PluginShortplayDrama->id;
                                $PluginShortplayActor->uid = $data['uid'];
                                $PluginShortplayActor->actor_id = uniqid();
                                $PluginShortplayActor->name = $actor['name'];
                                $PluginShortplayActor->species_type = $actor['species'];
                                $PluginShortplayActor->gender = $actor['gender'];
                                $PluginShortplayActor->age = $actor['age'];
                                $PluginShortplayActor->remarks = $actor['description'];
                                $PluginShortplayActor->status = ActorStatus::INITIALIZING['value'];
                                $PluginShortplayActor->save();
                            }
                            $PluginShortplayDramaActor = new PluginShortplayDramaActor();
                            $PluginShortplayDramaActor->channels_uid = $data['channels_uid'];
                            $PluginShortplayDramaActor->drama_id = $PluginShortplayDrama->id;
                            $PluginShortplayDramaActor->actor_id = $PluginShortplayActor->id;
                            $PluginShortplayDramaActor->save();
                        }
                    }
                    Db::commit();
                    Push::send([
                        'uid' => $data['uid'],
                        'channels_uid' => $data['channels_uid'],
                        'event' => 'generatecreatedrama'
                    ], [
                        'uuid' => $data['uuid'],
                        'drama_id' => $PluginShortplayDrama->id,
                        'msg' => '创建短剧成功'
                    ]);
                } catch (\Throwable $th) {
                    Db::rollback();
                    Log::error('创建短剧失败:' . $th->getMessage() . PHP_EOL . $th->getTraceAsString());
                    throw $th;
                }
            } catch (\Throwable $th) {
                if (!empty($ids)) {
                    Db::startTrans();
                    try {
                        Account::refund($data['uid'], $data['channels_uid'], $ids);
                        Db::commit();
                    } catch (\Throwable $th) {
                        Db::rollback();
                    }
                }
                PluginShortplayDrama::where(['id' => $data['uuid']])->delete();
                Push::send([
                    'uid' => $data['uid'],
                    'channels_uid' => $data['channels_uid'],
                    'event' => 'generatecreatedrama'
                ], [
                    'uuid' => $data['uuid'],
                    'msg' => '创建短剧失败'
                ]);
            }
        });
        return $this->resData(['uuid' => $data['uuid']]);
        /* if ($post['script'] == 'script') {
        } else {
            if (empty($post['title'])) {
                return $this->fail('请输入短剧标题');
            }
            if (empty($post['description'])) {
                return $this->fail('请输入短剧描述');
            }
            $PluginShortplayDrama = new PluginShortplayDrama();
            $PluginShortplayDrama->channels_uid = $request->channels_uid;
            $PluginShortplayDrama->uid = $request->uid;
            $PluginShortplayDrama->style_id = $post['style'];
            $PluginShortplayDrama->script = $post['script'];
            $PluginShortplayDrama->description = $post['description'];
            $PluginShortplayDrama->title = $post['title'];
            $PluginShortplayDrama->aspect_ratio = $post['aspect_ratio'];
            $PluginShortplayDrama->episode_sum = $episode_sum;
            $PluginShortplayDrama->episode_duration = $episode_duration;
            $PluginShortplayDrama->episode_num = 0;
            if (!empty($post['model'])) {
                $PluginModel = PluginModel::where(['id' => $post['model'], 'state' => State::YES['value']])->find();
                if (!$PluginModel) {
                    return $this->fail('模型不存在');
                }
                $PluginShortplayDrama->model_id = $PluginModel->id;
            }
            if (!empty($post['cover'])) {
                $PluginShortplayDrama->cover = $post['cover'];
            }
            $PluginShortplayDrama->save();
        }
        return $this->resData(['drama_id' => $PluginShortplayDrama->id]); */
    }
}
