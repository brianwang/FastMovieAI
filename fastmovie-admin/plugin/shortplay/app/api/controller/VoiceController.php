<?php

namespace plugin\shortplay\app\api\controller;

use app\Basic;
use app\expose\enum\State;
use plugin\control\utils\yidevs\Yidevs;
use plugin\model\app\model\PluginModel;
use plugin\model\app\model\PluginModelVoice;
use plugin\model\utils\enum\ModelVoiceStatus;
use plugin\shortplay\utils\enum\ActorAge;
use plugin\shortplay\utils\enum\ActorGender;
use support\Request;

class VoiceController extends Basic
{
    public function list(Request $request)
    {
        $model_id = $request->get('model_id');
        $scene = $request->get('scene');
        $action = $request->get('action', 'yidevs');
        if ($action == 'yidevs') {
            $PluginModel = PluginModel::where(['id' => $model_id, 'scene' => $scene, 'state' => State::YES['value']])->find();
            if (!$PluginModel) {
                return $this->fail('模型不存在');
            }
            try {
                $result = Yidevs::AudioVoiceList($PluginModel->channels_uid, ['model' => $PluginModel->model_id]);
                return $this->resData($result);
            } catch (\Throwable $th) {
                return $this->exception($th);
            }
        } else {
            $model_id = $request->get('model_id');
            $where = [];
            $where[] = ['channels_uid', '=', $request->channels_uid];
            $where[] = ['uid', '=', $request->uid];
            if ($model_id) {
                $where[] = ['model_id', '=', $model_id];
            }
            $status = $request->get('status');
            if ($status) {
                $where[] = ['status', '=', $status];
            }
            $voice = PluginModelVoice::where($where)->order('id desc')->select()->each(function ($item) {
                $item->gender_enum = ActorGender::get($item->gender);
                $item->age_enum = ActorAge::get($item->age);
            });
            return $this->resData($voice);
        }
    }
}
