<?php

namespace plugin\shortplay\app\api\controller;

use app\Basic;
use app\expose\enum\State;
use plugin\shortplay\app\model\PluginShortplayDrama;
use plugin\shortplay\app\model\PluginShortplayDramaEpisode;
use plugin\shortplay\app\model\PluginShortplayShare;
use plugin\shortplay\app\model\PluginShortplayShareEpisode;
use plugin\shortplay\app\model\PluginShortplayShareLikes;
use support\Request;
use think\facade\Db;

class WorksController extends Basic
{
    public function index(Request $request)
    {
        $limit = $request->get('limit', 10);
        $where = [];
        $where[] = ['uid', '=', $request->uid];
        $name = $request->get('name');
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        $script = $request->get('script', 'all');
        if ($script != 'all') {
            $where[] = ['script', '=', $script];
        }
        $PluginShortplayDrama = PluginShortplayDrama::where($where)->order('state asc ,id desc')->paginate($limit)->each(function ($item) {});
        return $this->resData($PluginShortplayDrama);
    }
    public function updateCover(Request $request)
    {
        $id = $request->post('id');
        $cover = $request->post('cover');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginShortplayDrama->cover = $cover;
        $PluginShortplayDrama->save();
        return $this->success('更新成功');
    }
    public function details(Request $request)
    {
        $id = $request->get('id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $id, 'uid' => $request->uid])->with(['episodes', 'style'])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        if ($PluginShortplayDrama->cover_state > 0) {
            $PluginShortplayDrama->cover = null;
        }
        return $this->resData($PluginShortplayDrama);
    }
    public function episode(Request $request)
    {
        $drama_id = $request->get('drama_id');
        $episode_id = $request->get('episode_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['drama_id' => $PluginShortplayDrama->id, 'id' => $episode_id])->with(['scenes' => function ($query) {
            $query->order('sort', 'asc');
        }, 'storyboards' => function ($query) {
            $query->order('sort', 'asc');
        }])->find();
        if (!$PluginShortplayDramaEpisode) {
            return $this->fail('分集不存在');
        }
        return $this->resData($PluginShortplayDramaEpisode);
    }

    public function share(Request $request)
    {
        $limit = $request->get('limit', 10);
        $where = [];
        $where[] = ['psd.uid', '=', $request->uid];
        $name = $request->get('name');
        if ($name) {
            $where[] = ['psd.name', 'like', '%' . $name . '%'];
        }
        $script = $request->get('script', 'all');
        if ($script != 'all') {
            $where[] = ['psd.script', '=', $script];
        }
        $PluginShortplayDrama = PluginShortplayDrama::alias('psd')
            ->join('plugin_shortplay_share pss', 'psd.id = pss.drama_id')
            ->where($where)
            ->order('pss.update_time desc,pss.id desc')
            ->field('psd.*,pss.likes,pss.id as share_id')
            ->paginate($limit)
            ->each(function ($item) {});
        return $this->resData($PluginShortplayDrama);
    }

    public function deleteShare(Request $request)
    {
        $id = $request->post('id');
        $PluginShortplayShare = PluginShortplayShare::where(['id' => $id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayShare) {
            return $this->fail('分享不存在');
        }
        Db::startTrans();
        try {
            PluginShortplayShareEpisode::where(['share_id' => $PluginShortplayShare->id])->delete();
            PluginShortplayShareLikes::where(['share_id' => $PluginShortplayShare->id])->delete();
            $PluginShortplayShare->delete();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        return $this->success('删除成功');
    }
}
