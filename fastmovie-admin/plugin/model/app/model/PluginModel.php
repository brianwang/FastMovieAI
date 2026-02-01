<?php

namespace plugin\model\app\model;

use app\model\Basic;
use plugin\control\expose\helper\Uploads;
use plugin\control\app\model\PluginChannelsUser;
class PluginModel extends Basic 
{
    public function getIconAttr($value,$data)
    {
        return Uploads::url($data['channels_uid'], $value);
    }
    public function setIconAttr($value,$data)
    {
        return Uploads::path($data['channels_uid'], $value);
    }
    public function channels()
    {
        return $this->hasOne(PluginChannelsUser::class, 'id', 'channels_uid');
    }
    public static function options($where = [])
    {
        $models = self::where($where)->field('id,name')->select();
        $data = [];
        foreach ($models as $item) {
            $data[] = [
                'label' => $item->name,
                'value' => $item->id,
            ];
        }
        return $data;
    }
}
