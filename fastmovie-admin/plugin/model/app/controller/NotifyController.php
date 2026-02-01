<?php

namespace plugin\model\app\controller;

use app\Basic;
use app\expose\enum\State;
use plugin\finance\expose\helper\Account;
use plugin\model\app\model\PluginModelTask;
use plugin\model\app\model\PluginModelVoice;
use plugin\model\utils\enum\ModelScene;
use plugin\model\utils\enum\ModelTaskStatus;
use plugin\model\utils\enum\ModelType;
use plugin\model\utils\enum\ModelVoiceStatus;
use plugin\notification\expose\helper\Push;
use plugin\shortplay\app\model\PluginShortplayActorCharacterLook;
use plugin\shortplay\utils\enum\ActorStatus;
use plugin\shortplay\utils\enum\PropStatus;
use support\Log;
use support\Request;
use think\facade\Db;

class NotifyController extends Basic
{
    public function draw(Request $request)
    {
        Log::error('draw' . json_encode($request->post()));
        $taskId = $request->post('task_id');
        $PluginModelTask = PluginModelTask::where(['task_id' => $taskId, 'model_type' => ModelType::DRAW['value']])->with(['result'])->find();
        if (!$PluginModelTask) {
            return 'success';
        }
        $status = $request->post('status');
        if ($status == ModelTaskStatus::SUCCESS['value']) {
            $PluginModelTask->status = ModelTaskStatus::WAIT_DOWNLOAD['value'];
            $PluginModelTask->result->result = $request->post();
            $PluginModelTask->result->image = $request->post('url');
            $PluginModelTask->together(['result'])->save();
        } elseif ($status == ModelTaskStatus::FAIL['value']) {
            $PluginModelTask->status = ModelTaskStatus::FAIL['value'];
            $PluginModelTask->result->result = $request->post();
            $PluginModelTask->result->message = $request->post('message');
            $PluginModelTask->together(['result'])->save();
            if (!empty($PluginModelTask->consume_ids)) {
                Db::startTrans();
                try {
                    Account::refund($PluginModelTask->uid, $PluginModelTask->channels_uid, $PluginModelTask->consume_ids);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                }
            }
            $pushData = [
                'id' => $PluginModelTask->alias_id
            ];
            switch ($PluginModelTask->scene) {
                case ModelScene::ACTOR_IMAGE['value']:
                    $PluginModelTask = PluginModelTask::where(['pre_task_id' => $PluginModelTask->id, 'status' => ModelTaskStatus::WAIT['value']])->find();
                    if ($PluginModelTask) {
                        $PluginModelTask->status = ModelTaskStatus::FAIL['value'];
                        $PluginModelTask->save();
                        if (!empty($PluginModelTask->consume_ids)) {
                            Db::startTrans();
                            try {
                                Account::refund($PluginModelTask->uid, $PluginModelTask->channels_uid, $PluginModelTask->consume_ids);
                                Db::commit();
                            } catch (\Throwable $th) {
                                Db::rollback();
                            }
                        }
                    }
                    $pushData['status'] = ActorStatus::INITIALIZING;
                    break;
                case ModelScene::PROP_IMAGE['value']:
                    $PluginModelTask = PluginModelTask::where(['pre_task_id' => $PluginModelTask->id, 'status' => ModelTaskStatus::WAIT['value']])->find();
                    if ($PluginModelTask) {
                        $PluginModelTask->status = ModelTaskStatus::FAIL['value'];
                        $PluginModelTask->save();
                        if (!empty($PluginModelTask->consume_ids)) {
                            Db::startTrans();
                            try {
                                Account::refund($PluginModelTask->uid, $PluginModelTask->channels_uid, $PluginModelTask->consume_ids);
                                Db::commit();
                            } catch (\Throwable $th) {
                                Db::rollback();
                            }
                        }
                    }
                    $pushData['status'] = PropStatus::INITIALIZING;
                    break;
                case ModelScene::ACTOR_COSTUME['value']:
                    $PluginModelTask = PluginModelTask::where(['pre_task_id' => $PluginModelTask->id, 'status' => ModelTaskStatus::WAIT['value']])->find();
                    if ($PluginModelTask) {
                        $PluginModelTask->status = ModelTaskStatus::FAIL['value'];
                        $PluginModelTask->save();
                        if (!empty($PluginModelTask->consume_ids)) {
                            Db::startTrans();
                            try {
                                Account::refund($PluginModelTask->uid, $PluginModelTask->channels_uid, $PluginModelTask->consume_ids);
                                Db::commit();
                            } catch (\Throwable $th) {
                                Db::rollback();
                            }
                        }
                    }
                    $PluginShortplayActorCharacterLook = PluginShortplayActorCharacterLook::where(['id' => $PluginModelTask->alias_id])->find();
                    $PluginShortplayActorCharacterLook->delete();
                    $pushData['storyboard_id'] = $PluginShortplayActorCharacterLook->storyboard_id;
                    $pushData['episode_id'] = $PluginShortplayActorCharacterLook->episode_id;
                    $pushData['drama_id'] = $PluginShortplayActorCharacterLook->drama_id;
                    $pushData['actor_id'] = $PluginShortplayActorCharacterLook->actor_id;
                    break;
                case ModelScene::ACTOR_THREE_VIEW_IMAGE['value']:
                    $pushData['status'] = ActorStatus::INITIALIZING;
                    break;
                case ModelScene::PROP_THREE_VIEW_IMAGE['value']:
                    $pushData['status'] = PropStatus::INITIALIZING;
                    break;
                case ModelScene::ACTOR_COSTUME_THREE_VIEW['value']:
                    $PluginShortplayActorCharacterLook = PluginShortplayActorCharacterLook::where(['id' => $PluginModelTask->alias_id])->find();
                    $PluginShortplayActorCharacterLook->delete();
                    $pushData['storyboard_id'] = $PluginShortplayActorCharacterLook->storyboard_id;
                    $pushData['episode_id'] = $PluginShortplayActorCharacterLook->episode_id;
                    $pushData['drama_id'] = $PluginShortplayActorCharacterLook->drama_id;
                    $pushData['actor_id'] = $PluginShortplayActorCharacterLook->actor_id;
                    break;
            }
            Push::send([
                'uid' => $PluginModelTask->uid,
                'channels_uid' => $PluginModelTask->channels_uid,
                'event' => 'generate' . strtolower(str_replace('_', '', $PluginModelTask->scene)),
            ], $pushData);
        }
        return 'success';
    }
    public function video(Request $request)
    {
        Log::error('video' . json_encode($request->post()));
        $taskId = $request->post('task_id');
        $PluginModelTask = PluginModelTask::where(['task_id' => $taskId, 'model_type' => ModelType::TOVIDEO['value']])->with(['result'])->find();
        if (!$PluginModelTask) {
            return 'success';
        }
        $status = $request->post('status');
        if ($status == ModelTaskStatus::SUCCESS['value']) {
            $PluginModelTask->status = ModelTaskStatus::WAIT_DOWNLOAD['value'];
            $PluginModelTask->result->result = $request->post();
            $PluginModelTask->result->video = $request->post('url');
            $PluginModelTask->together(['result'])->save();
        } elseif ($status == ModelTaskStatus::FAIL['value']) {
            $PluginModelTask->status = ModelTaskStatus::FAIL['value'];
            $PluginModelTask->result->result = $request->post();
            $PluginModelTask->result->message = $request->post('message');
            $PluginModelTask->together(['result'])->save();
            if (!empty($PluginModelTask->consume_ids)) {
                Db::startTrans();
                try {
                    Account::refund($PluginModelTask->uid, $PluginModelTask->channels_uid, $PluginModelTask->consume_ids);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                }
            }
            $pushData = [
                'id' => $PluginModelTask->alias_id
            ];
            Push::send([
                'uid' => $PluginModelTask->uid,
                'channels_uid' => $PluginModelTask->channels_uid,
                'event' => 'generate' . strtolower(str_replace('_', '', $PluginModelTask->scene)),
            ], $pushData);
        }
        return 'success';
    }
    public function audio(Request $request)
    {
        Log::error('audio' . json_encode($request->post()));
        $taskId = $request->post('task_id');
        $PluginModelTask = PluginModelTask::where(['task_id' => $taskId, 'model_type' => ModelType::AUDIO['value']])->with(['result'])->find();
        $status = $request->post('status');
        if ($status == ModelTaskStatus::SUCCESS['value']) {
            $PluginModelTask->status = ModelTaskStatus::WAIT_DOWNLOAD['value'];
            $PluginModelTask->result->result = $request->post();
            $PluginModelTask->result->audio = $request->post('url');
            $PluginModelTask->together(['result'])->save();
        } elseif ($status == ModelTaskStatus::FAIL['value']) {
            $PluginModelTask->status = ModelTaskStatus::FAIL['value'];
            $PluginModelTask->result->result = $request->post();
            $PluginModelTask->result->message = $request->post('message');
            $PluginModelTask->together(['result'])->save();
            if (!empty($PluginModelTask->consume_ids)) {
                Db::startTrans();
                try {
                    Account::refund($PluginModelTask->uid, $PluginModelTask->channels_uid, $PluginModelTask->consume_ids);
                    Db::commit();
                } catch (\Throwable $th) {
                    Db::rollback();
                }
            }
            $pushData = [
                'id' => $PluginModelTask->alias_id
            ];
            Push::send([
                'uid' => $PluginModelTask->uid,
                'channels_uid' => $PluginModelTask->channels_uid,
                'event' => 'generate' . strtolower(str_replace('_', '', $PluginModelTask->scene)),
            ], $pushData);
        }
        return 'success';
    }
    public function voiceClone(Request $request)
    {
        Log::error('voiceClone' . json_encode($request->post()));
        $taskId = $request->post('task_id');
        $PluginModelVoice = PluginModelVoice::where(['task_id' => $taskId])->find();
        if (!$PluginModelVoice) {
            return 'success';
        }
        $status = $request->post('status');
        if ($status == ModelTaskStatus::SUCCESS['value']) {
            $PluginModelVoice->status = ModelVoiceStatus::SUCCESS['value'];
            $PluginModelVoice->voice_id = $request->post('voice_id');
            $PluginModelVoice->save();
            $pushData = [
                'id' => $PluginModelVoice->id,
                'status' => ModelVoiceStatus::SUCCESS['value'],
            ];
        } elseif ($status == ModelTaskStatus::FAIL['value']) {
            $PluginModelVoice->status = ModelVoiceStatus::FAIL['value'];
            $PluginModelVoice->message = $request->post('message');
            $PluginModelVoice->save();
            $pushData = [
                'id' => $PluginModelVoice->id,
                'status' => ModelVoiceStatus::FAIL['value'],
            ];
        }
        Push::send([
            'uid' => $PluginModelVoice->uid,
            'event' => 'clonevoice',
        ], $pushData);
        return 'success';
    }
}
