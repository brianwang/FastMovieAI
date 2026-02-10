<?php

namespace plugin\control\utils\yidevs\trait;

use plugin\control\utils\yidevs\Client;

trait Chat
{
    /**
     * 获取壹定开放平台大模型-模型列表
     * @param int $channels_uid 分站使用$request->uid，客户端使用$request->channels_uid
     * @param array $query
     * @return array
     */
    public static function ChatModels(int $channels_uid, array $query = [])
    {
        $Client = new Client();
        $Client->setChannelsUid($channels_uid);
        return $Client->get('app/yimind/api/Chat/models', $query);
    }
    /**
     * 调用壹定开放平台大模型-聊天-对话
     * @param int $channels_uid 分站使用$request->uid，客户端使用$request->channels_uid
     * @param array $data
     * @param callable|null $stream
     * @return mixed
     */
    public static function ChatCompletions(int $channels_uid, array $data = [], ?callable $stream = null)
    {
        $Client = new Client();
        $Client->setChannelsUid($channels_uid);
        if ($stream) {
            return $Client->stream('app/yimind/api/Chat/completions', $data, $stream);
        }
        return $Client->request('POST', 'app/yimind/api/Chat/completions', ['json' => $data]);
    }
}
