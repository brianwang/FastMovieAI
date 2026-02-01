<?php

namespace plugin\model\app\model;

use app\model\Basic;
use plugin\control\app\model\PluginChannelsUser;
use plugin\control\expose\helper\Uploads;
use plugin\user\app\model\PluginUser;

class PluginModelVoice extends Basic 
{
    public function getAudioAttr($value, $data)
    {
        return Uploads::url($data['channels_uid'], $value);
    }
    public function setAudioAttr($value, $data)
    {
        return Uploads::path($data['channels_uid'], $value);
    }
    public function model()
    {
        return $this->hasOne(PluginModel::class, 'id', 'model_id');
    }
    public function channels()
    {
        return $this->hasOne(PluginChannelsUser::class, 'id', 'channels_uid');
    }
    public function user()
    {
        return $this->hasOne(PluginUser::class, 'id', 'uid');
    }
}
