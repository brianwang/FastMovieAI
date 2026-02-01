<?php

namespace plugin\model\app\api\controller;

use app\Basic;
use app\expose\enum\State;
use plugin\model\app\model\PluginModelVoiceText;
use support\Request;

class VoiceTextController extends Basic
{
    public function index(Request $request)
    {
        $where = [];
        $where[] = ['channels_uid', '=', $request->channels_uid];
        $where[] = ['state', '=', State::YES['value']];
        $text = $request->get('text');
        if ($text) {
            $where[] = ['text', 'like', "%{$text}%"];
        }
        $list = PluginModelVoiceText::where($where)->order('id desc')->select();
        return $this->resData($list);
    }
}
