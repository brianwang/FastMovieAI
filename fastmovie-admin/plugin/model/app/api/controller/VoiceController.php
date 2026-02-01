<?php

namespace plugin\model\app\api\controller;

use app\Basic;
use app\expose\enum\State;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Format\Audio\Wav;
use plugin\control\expose\helper\Uploads;
use plugin\control\utils\yidevs\Yidevs;
use plugin\model\app\model\PluginModel;
use plugin\model\app\model\PluginModelVoice;
use plugin\model\app\model\PluginModelVoiceText;
use plugin\model\utils\enum\ModelScene;
use plugin\model\utils\enum\ModelVoiceStatus;
use plugin\notification\expose\helper\Push;
use support\Request;
use Webman\Http\UploadFile;
use Workerman\Coroutine;

class VoiceController extends Basic
{
    public function submit(Request $request)
    {
        $D = $request->post();
        if (empty($D['text_id'])) {
            return $this->fail('请选择文案');
        }
        if (empty($D['model_id'])) {
            return $this->fail('请选择模型');
        }
        $PluginModelVoiceText = PluginModelVoiceText::where(['id' => $D['text_id'], 'channels_uid' => $request->channels_uid])->find();
        if (!$PluginModelVoiceText) {
            return $this->fail('文案不存在');
        }
        $PluginModel = PluginModel::where(['id' => $D['model_id'], 'channels_uid' => $request->channels_uid, 'state' => State::YES['value'], 'scene' => ModelScene::DIALOGUE_VOICE['value']])->find();
        if (!$PluginModel) {
            return $this->fail('模型不存在');
        }
        $audio_url = '';
        $file = $request->file('file');
        if (empty($file)) {
            return $this->fail('请录制语音');
        }
        $fileName = uniqid('voice_') . '.wav';
        $temp_path = runtime_path('temp/' . $fileName);
        // 判断文件的mime是否为MP3或WAV
        if (!str_starts_with($file->getUploadMimeType(), 'audio/wav')) {
            // 使用phpffmpeg转换为MP3
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => '/www/server/ffmpeg/ffmpeg-6.1/ffmpeg',
                'ffprobe.binaries' => '/www/server/ffmpeg/ffmpeg-6.1/ffprobe',
            ]);
            $media = $ffmpeg->open($file->getRealPath());
            $wav = new Wav();
            $media->save($wav, $temp_path);
            $file = new UploadFile($temp_path, $fileName, 'audio/wav', $file->getUploadErrorCode());
        }
        $result = Uploads::upload($request->channels_uid, $file, null, 'uploads/voice_audio');
        if (file_exists($temp_path)) {
            unlink($temp_path);
        }
        $audio_url = $result['url'];
        $PluginModelVoice = new PluginModelVoice();
        $PluginModelVoice->uid = $request->uid;
        $PluginModelVoice->channels_uid = $request->channels_uid;
        if (!empty($D['voice_id'])) {
            $PluginModelVoice = PluginModelVoice::where(['id' => $D['voice_id'], 'channels_uid' => $request->channels_uid, 'uid' => $request->uid])->find();
            if (!$PluginModelVoice) {
                return $this->fail('音色不存在');
            }
            $audio_url = $PluginModelVoice->audio_url;
        }
        $PluginModelVoice->text_id = $D['text_id'];
        $PluginModelVoice->model_id = $D['model_id'];
        if ($audio_url) {
            $PluginModelVoice->audio = $audio_url;
        }
        $PluginModelVoice->status = ModelVoiceStatus::WAIT['value'];
        $PluginModelVoice->save();
        $ids = [
            'id' => $PluginModelVoice->id,
            'uid' => $request->uid,
            'channels_uid' => $request->channels_uid,
        ];
        $data = [
            'model' => $PluginModel->model_id,
            'form_data' => [
                'audio' => $audio_url,
                'notify_url' => 'https://' . $request->host() . '/app/model/Notify/voiceClone',
            ]
        ];
        Coroutine::create(function () use ($ids, $data) {
            $status = ModelVoiceStatus::CLONING['value'];
            try {
                $result = Yidevs::AudioVoiceClone($ids['channels_uid'], $data);
                $PluginModelVoice = PluginModelVoice::where(['id' => $ids['id'], 'channels_uid' => $ids['channels_uid']])->find();
                $PluginModelVoice->status = ModelVoiceStatus::CLONING['value'];
                $PluginModelVoice->task_id = $result['task_id'];
                $PluginModelVoice->save();
            } catch (\Throwable $th) {
                $PluginModelVoice = PluginModelVoice::where(['id' => $ids['id'], 'channels_uid' => $ids['channels_uid']])->find();
                $PluginModelVoice->status = ModelVoiceStatus::FAIL['value'];
                $PluginModelVoice->message = $th->getMessage();
                $PluginModelVoice->save();
                $status = ModelVoiceStatus::FAIL['value'];
            }
            Push::send([
                'uid' => $ids['uid'],
                'event' => 'clonevoice',
            ], [
                'id' => $PluginModelVoice->id,
                'status' => $status,
            ]);
        });
        return $this->resData(['voice_id' => $PluginModelVoice->id]);
    }
    public function update(Request $request)
    {
        $id = $request->post('id');
        $PluginModelVoice = PluginModelVoice::where(['id' => $id, 'channels_uid' => $request->channels_uid, 'uid' => $request->uid])->find();
        if (!$PluginModelVoice) {
            return $this->fail('音色不存在');
        }
        $name = $request->post('name');
        if ($name) {
            $PluginModelVoice->name = $name;
        }
        $description = $request->post('description');
        if ($description) {
            $PluginModelVoice->description = $description;
        }
        $image = $request->post('image');
        if ($image) {
            $PluginModelVoice->image = $image;
        }
        $headimg = $request->post('headimg');
        if ($headimg) {
            $PluginModelVoice->headimg = $headimg;
        }
        $gender = $request->post('gender');
        if ($gender) {
            $PluginModelVoice->gender = $gender;
        }
        $age = $request->post('age');
        if ($age) {
            $PluginModelVoice->age = $age;
        }
        $PluginModelVoice->save();
        return $this->success('更新成功');
    }
    public function detail(Request $request)
    {
        $id = $request->get('id');
        $PluginModelVoice = PluginModelVoice::where(['id' => $id, 'channels_uid' => $request->channels_uid, 'uid' => $request->uid])->find();
        if (!$PluginModelVoice) {
            return $this->fail('音色不存在');
        }
        return $this->resData($PluginModelVoice);
    }
}
