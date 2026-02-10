<?php

namespace plugin\shortplay\app\api\controller;

use app\Basic;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use plugin\control\expose\helper\Uploads;
use plugin\model\app\model\PluginModelTask;
use plugin\model\utils\enum\ModelScene;
use plugin\model\utils\enum\ModelTaskStatus;
use plugin\notification\expose\helper\Push;
use plugin\shortplay\app\model\PluginShortplayActor;
use plugin\shortplay\app\model\PluginShortplayActorCharacterLook;
use plugin\shortplay\app\model\PluginShortplayDrama;
use plugin\shortplay\app\model\PluginShortplayDramaActor;
use plugin\shortplay\app\model\PluginShortplayDramaEpisode;
use plugin\shortplay\app\model\PluginShortplayDramaEpisodeActor;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboard;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardActor;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardDialogue;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardProp;
use plugin\shortplay\app\model\PluginShortplayProp;
use plugin\shortplay\utils\enum\ActorStatus;
use plugin\shortplay\utils\enum\PropStatus;
use support\Cache;
use support\Log;
use support\Request;
use support\think\Db;
use Workerman\Coroutine;

class StoryboardController extends Basic
{
    public function index(Request $request)
    {
        $type = $request->get('type');
        $where = [];
        switch ($type) {
            case 'episode':
                $episode_id = $request->get('episode_id');
                if (!$episode_id) {
                    return $this->fail('分集ID不能为空');
                }
                $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['id' => $episode_id])->find();
                if (!$PluginShortplayDramaEpisode) {
                    return $this->fail('分集不存在');
                }
                $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $PluginShortplayDramaEpisode->drama_id, 'uid' => $request->uid])->find();
                if (!$PluginShortplayDrama) {
                    return $this->fail('短剧不存在');
                }
                $where[] = ['episode_id', '=', $PluginShortplayDramaEpisode->id];
                break;
            case 'drama':
                break;
            default:
                $drama_id = $request->get('drama_id');
                if (!$drama_id) {
                    return $this->fail('短剧ID不能为空');
                }
                $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
                if (!$PluginShortplayDrama) {
                    return $this->fail('短剧不存在');
                }
                $where[] = ['drama_id', '=', $PluginShortplayDrama->id];
                break;
        }
        $description = $request->get('description');
        if ($description) {
            $where[] = ['description', 'like', '%' . $description . '%'];
        }
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where($where)->with(['sceneFind',  'props', 'dialogues'])->order('sort asc,id asc')->select()->each(function ($item) {});
        return $this->resData($PluginShortplayDramaStoryboard);
    }
    public function updateSort(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $storyboards = $request->post('storyboards');
        if (empty($storyboards)) {
            $id = $request->post('id');
            $sort = $request->post('sort');
            $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['drama_id' => $PluginShortplayDrama->id, 'id' => $id])->find();
            if (!$PluginShortplayDramaStoryboard) {
                return $this->fail('分镜不存在');
            }
            $PluginShortplayDramaStoryboard->sort = $sort;
            $PluginShortplayDramaStoryboard->save();
        } else {
            Db::startTrans();
            try {
                foreach ($storyboards as $storyboard) {
                    $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['drama_id' => $PluginShortplayDrama->id, 'id' => $storyboard['id']])->find();
                    if (!$PluginShortplayDramaStoryboard) {
                        continue;
                    }
                    $PluginShortplayDramaStoryboard->sort = $storyboard['sort'];
                    $PluginShortplayDramaStoryboard->save();
                }
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                return $this->fail($th->getMessage());
            }
        }
        return $this->success('更新成功');
    }
    public function copyStoryboard(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $copy_id = $request->post('copy_id'); // 要复制的 storyboard ID
        $new_sort = $request->post('new_sort');

        // 校验短剧
        $PluginShortplayDrama = PluginShortplayDrama::where([
            'id' => $drama_id,
            'uid' => $request->uid
        ])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }

        // 校验原分镜
        $origin = PluginShortplayDramaStoryboard::where([
            'drama_id' => $drama_id,
            'id' => $copy_id
        ])->find();
        if (!$origin) {
            return $this->fail('原分镜不存在');
        }

        // 校验 new_sort
        if (!$new_sort || !is_numeric($new_sort)) {
            return $this->fail('new_sort 不合法');
        }

        Db::startTrans();
        try {
            // 1. 查询当前 scene 内所有分镜
            $sceneStoryboards = PluginShortplayDramaStoryboard::where([
                'drama_id' => $drama_id,
                'episode_id' => $origin->episode_id,
            ])
                ->order('sort asc')
                ->select()
                ->toArray();

            // 2. 将 sort >= new_sort 的分镜全部 +1
            foreach ($sceneStoryboards as $sb) {
                if ($sb['sort'] >= $new_sort) {
                    PluginShortplayDramaStoryboard::where(['id' => $sb['id']])
                        ->update(['sort' => $sb['sort'] + 1]);
                }
            }

            // 3. 插入复制后的分镜
            unset($origin['id']); // 删除原分镜ID
            $origin['sort'] = $new_sort;
            $origin['create_time'] = time();
            $origin['update_time'] = time();
            $PluginShortplayDramaStoryboard = new PluginShortplayDramaStoryboard;
            $PluginShortplayDramaStoryboard->save($origin);

            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }

        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $PluginShortplayDramaStoryboard->id])->with(['actors', 'sceneFind', 'props', 'dialogues'])->find();
        return $this->resData($PluginShortplayDramaStoryboard);
    }
    public function deleteStoryboard(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $episode_id = $request->post('episode_id');
        $id = $request->post('id');

        // 校验短剧是否存在
        $PluginShortplayDrama = PluginShortplayDrama::where([
            'id' => $drama_id,
            'uid' => $request->uid
        ])->find();

        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where([
            'id' => $episode_id,
            'drama_id' => $drama_id
        ])->find();
        if (!$PluginShortplayDramaEpisode) {
            return $this->fail('分集不存在');
        }
        if ($id) {
            // 校验分镜是否存在
            $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where([
                'id'      => $id,
                'drama_id' => $drama_id,
            ])->find();

            if (!$PluginShortplayDramaStoryboard) {
                return $this->fail('分镜不存在');
            }
        }

        Db::startTrans();
        try {
            if ($id) {
                PluginShortplayDramaStoryboardActor::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id])->delete();
                PluginShortplayDramaStoryboardDialogue::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id])->delete();
                $PluginShortplayDramaStoryboardProps = PluginShortplayDramaStoryboardProp::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id])->select();
                foreach ($PluginShortplayDramaStoryboardProps as $PluginShortplayDramaStoryboardProp) {
                    PluginShortplayProp::where(['id' => $PluginShortplayDramaStoryboardProp->prop_id, 'status' => PropStatus::INITIALIZING['value']])->delete();
                    $PluginShortplayDramaStoryboardProp->delete();
                }
                $PluginShortplayDramaStoryboard->delete();
                $PluginShortplayDramaStoryboards = PluginShortplayDramaStoryboard::where([
                    'drama_id' => $drama_id,
                    'episode_id' => $episode_id,
                ])
                    ->order('sort asc')
                    ->select();

                $sort = 1;
                foreach ($PluginShortplayDramaStoryboards as $sb) {
                    $sb->sort = $sort++;
                    $sb->save();
                }
            } else {
                $PluginShortplayDramaStoryboards = PluginShortplayDramaStoryboard::where([
                    'drama_id' => $drama_id,
                    'episode_id' => $episode_id,
                ])->select();
                foreach ($PluginShortplayDramaStoryboards as $PluginShortplayDramaStoryboard) {
                    PluginShortplayDramaStoryboardActor::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id])->delete();
                    PluginShortplayDramaStoryboardDialogue::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id])->delete();
                    PluginShortplayDramaStoryboardProp::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id])->delete();
                    $PluginShortplayDramaStoryboard->delete();
                }
                PluginShortplayProp::where(['episode_id' => $PluginShortplayDramaEpisode->id, 'status' => PropStatus::INITIALIZING['value']])->delete();
            }

            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }

        return $this->success('删除成功');
    }
    public function insertAfter(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $episode_id = $request->post('episode_id');
        $after_id = $request->post('after_id');

        $drama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$drama) {
            return $this->fail('短剧不存在');
        }
        $afterSort = 0;
        if ($after_id) {
            $after = PluginShortplayDramaStoryboard::where([
                'drama_id' => $drama->id,
                'id' => $after_id,
            ])->find();

            if (!$after) {
                return $this->fail('指定的分镜不存在');
            }
            $afterSort = $after->sort;
        }

        Db::startTrans();
        try {
            // 从 after.sort + 1 开始，所有后续 +1
            PluginShortplayDramaStoryboard::where([
                'drama_id' => $drama_id,
                'episode_id' => $episode_id,
            ])
                ->where('sort', '>', $afterSort)
                ->inc('sort', 1)
                ->update();

            // 插入新的空白分镜
            $new = new PluginShortplayDramaStoryboard();
            $new->channels_uid = $request->channels_uid;
            $new->drama_id = $drama->id;
            $new->episode_id = $episode_id;
            $new->sort = $afterSort + 1;
            $new->duration = 5000;
            $new->save();

            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }

        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $new->id])->with(['actors', 'sceneFind', 'props', 'dialogues'])->find();
        return $this->resData($PluginShortplayDramaStoryboard);
    }
    public function update(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $episode_id = $request->post('episode_id');
        $id = $request->post('id');
        $D = $request->post();
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $id, 'drama_id' => $drama_id, 'episode_id' => $episode_id])->find();
        if (!$PluginShortplayDramaStoryboard) {
            return $this->fail('分镜不存在');
        }
        if (!empty($D['scene_id'])) {
            $PluginShortplayDramaStoryboard->scene_id = $D['scene_id'];
        }
        if (!empty($D['description'])) {
            $PluginShortplayDramaStoryboard->description = $D['description'];
        }
        if (!empty($D['shot_type'])) {
            $PluginShortplayDramaStoryboard->shot_type = $D['shot_type'];
        }
        if (!empty($D['shot_angle'])) {
            $PluginShortplayDramaStoryboard->shot_angle = $D['shot_angle'];
        }
        if (!empty($D['shot_motion'])) {
            $PluginShortplayDramaStoryboard->shot_motion = $D['shot_motion'];
        }
        if (!empty($D['image'])) {
            $PluginShortplayDramaStoryboard->image = $D['image'];
            $PluginShortplayDramaStoryboard->use_material_type = 'image';
        }
        if (!empty($D['video'])) {
            $PluginShortplayDramaStoryboard->video = $D['video'];
            $PluginShortplayDramaStoryboard->use_material_type = 'video';
        }
        if (isset($D['narration'])) {
            $PluginShortplayDramaStoryboard->narration = $D['narration'];
        }
        if (isset($D['sfx'])) {
            $PluginShortplayDramaStoryboard->sfx = $D['sfx'];
        }
        $PluginShortplayDramaStoryboard->save();
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $PluginShortplayDramaStoryboard->id])->with(['actors', 'sceneFind', 'props', 'dialogues'])->find();
        return $this->resData($PluginShortplayDramaStoryboard);
    }
    public function joinActor(Request $request)
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
        $actor_id = $request->post('actor_id');
        $PluginShortplayActor = PluginShortplayActor::where(['id' => $actor_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayActor) {
            $PluginShortplayActor = PluginShortplayActor::where(['id' => $actor_id, 'channels_uid' => $request->channels_uid])->whereNull('uid')->find();
        }
        if (!$PluginShortplayActor) {
            return $this->fail('演员不存在');
        }
        Db::startTrans();
        try {
            if (!PluginShortplayDramaStoryboardActor::where(['storyboard_id' => $storyboard_id, 'actor_id' => $actor_id])->count()) {
                $PluginShortplayDramaStoryboardActor = new PluginShortplayDramaStoryboardActor();
                $PluginShortplayDramaStoryboardActor->channels_uid = $request->channels_uid;
                $PluginShortplayDramaStoryboardActor->drama_id = $PluginShortplayDramaStoryboard->drama_id;
                $PluginShortplayDramaStoryboardActor->episode_id = $PluginShortplayDramaStoryboard->episode_id;
                $PluginShortplayDramaStoryboardActor->storyboard_id = $storyboard_id;
                $PluginShortplayDramaStoryboardActor->actor_id = $actor_id;
                $PluginShortplayDramaStoryboardActor->save();
            }
            if (!PluginShortplayDramaEpisodeActor::where(['episode_id' => $PluginShortplayDramaStoryboard->episode_id, 'actor_id' => $actor_id])->count()) {
                $PluginShortplayDramaEpisodeActor = new PluginShortplayDramaEpisodeActor();
                $PluginShortplayDramaEpisodeActor->channels_uid = $request->channels_uid;
                $PluginShortplayDramaEpisodeActor->drama_id = $PluginShortplayDramaStoryboard->drama_id;
                $PluginShortplayDramaEpisodeActor->episode_id = $PluginShortplayDramaStoryboard->episode_id;
                $PluginShortplayDramaEpisodeActor->actor_id = $actor_id;
                $PluginShortplayDramaEpisodeActor->save();
            }
            if (!PluginShortplayDramaActor::where(['drama_id' => $drama_id, 'actor_id' => $actor_id])->count()) {
                $PluginShortplayDramaActor = new PluginShortplayDramaActor();
                $PluginShortplayDramaActor->channels_uid = $request->channels_uid;
                $PluginShortplayDramaActor->drama_id = $drama_id;
                $PluginShortplayDramaActor->actor_id = $actor_id;
                $PluginShortplayDramaActor->save();
            }
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id])->with(['actors', 'sceneFind', 'props', 'dialogues'])->find();
        return $this->resData($PluginShortplayDramaStoryboard);
    }
    public function deleteActor(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $id = $request->post('id');
        $storyboard_id = $request->post('storyboard_id');
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id, 'drama_id' => $drama_id])->find();
        if (!$PluginShortplayDramaStoryboard) {
            return $this->fail('分镜不存在');
        }
        Db::startTrans();
        try {
            PluginShortplayDramaStoryboardActor::where(['id' => $id, 'storyboard_id' => $PluginShortplayDramaStoryboard->id])->delete();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id])->with(['actors', 'sceneFind', 'props', 'dialogues'])->find();
        return $this->resData($PluginShortplayDramaStoryboard);
    }
    public function joinProp(Request $request)
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
        $prop_id = $request->post('prop_id');
        $PluginShortplayProp = PluginShortplayProp::where(['id' => $prop_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayProp) {
            return $this->fail('物品不存在');
        }
        Db::startTrans();
        try {
            if (!PluginShortplayDramaStoryboardProp::where(['storyboard_id' => $storyboard_id, 'prop_id' => $PluginShortplayProp->id])->count()) {
                $PluginShortplayDramaStoryboardProp = new PluginShortplayDramaStoryboardProp();
                $PluginShortplayDramaStoryboardProp->channels_uid = $request->channels_uid;
                $PluginShortplayDramaStoryboardProp->storyboard_id = $storyboard_id;
                $PluginShortplayDramaStoryboardProp->prop_id = $PluginShortplayProp->id;
                $PluginShortplayDramaStoryboardProp->save();
            }
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id])->with(['actors', 'sceneFind', 'props', 'dialogues'])->find();
        return $this->resData($PluginShortplayDramaStoryboard);
    }
    public function deleteProp(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $id = $request->post('id');
        $storyboard_id = $request->post('storyboard_id');
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id, 'drama_id' => $drama_id])->find();
        if (!$PluginShortplayDramaStoryboard) {
            return $this->fail('分镜不存在');
        }
        Db::startTrans();
        try {
            PluginShortplayDramaStoryboardProp::where(['id' => $id, 'storyboard_id' => $PluginShortplayDramaStoryboard->id])->delete();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id])->with(['actors', 'sceneFind', 'props', 'dialogues'])->find();
        return $this->resData($PluginShortplayDramaStoryboard);
    }
    public function ReplaceStoryboard(Request $request)
    {
        $id = $request->post('id');
        $task_id = $request->post('task_id');
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $id])->find();
        if (!$PluginShortplayDramaStoryboard) {
            return $this->fail('分镜不存在');
        }
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $PluginShortplayDramaStoryboard->drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginModelTask = PluginModelTask::where(['id' => $task_id, 'alias_id' => $PluginShortplayDramaStoryboard->id, 'status' => ModelTaskStatus::SUCCESS['value']])->with(['result'])->find();
        if (!$PluginModelTask) {
            return $this->fail('任务不存在');
        }
        if ($PluginModelTask->scene == ModelScene::STORYBOARD_IMAGE['value']) {
            $PluginShortplayDramaStoryboard->image = $PluginModelTask->result->image_path;
            $PluginShortplayDramaStoryboard->use_material_type = 'image';
        }
        if ($PluginModelTask->scene == ModelScene::STORYBOARD_VIDEO['value']) {
            $PluginShortplayDramaStoryboard->video = $PluginModelTask->result->video_path;
            $PluginShortplayDramaStoryboard->use_material_type = 'video';
        }
        $PluginShortplayDramaStoryboard->save();
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $id])->with(['actors', 'sceneFind', 'props', 'dialogues'])->find();
        return $this->resData($PluginShortplayDramaStoryboard);
    }
    public function CharacterLook(Request $request)
    {
        $actor_id = $request->post('actor_id');
        $drama_id = $request->post('drama_id');
        $episode_id = $request->post('episode_id');
        $storyboard_id = $request->post('storyboard_id');
        $character_look_id = $request->post('character_look_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['id' => $episode_id, 'drama_id' => $PluginShortplayDrama->id])->find();
        if (!$PluginShortplayDramaEpisode) {
            return $this->fail('分集不存在');
        }
        $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['id' => $storyboard_id, 'drama_id' => $drama_id, 'episode_id' => $episode_id])->find();
        if (!$PluginShortplayDramaStoryboard) {
            return $this->fail('分镜不存在');
        }
        $PluginShortplayDramaStoryboardActor = PluginShortplayDramaStoryboardActor::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id, 'actor_id' => $actor_id])->find();
        if (!$PluginShortplayDramaStoryboardActor) {
            return $this->fail('演员不存在');
        }
        $PluginShortplayActorCharacterLook = PluginShortplayActorCharacterLook::where(['id' => $character_look_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayActorCharacterLook) {
            return $this->fail('演员妆容不存在');
        }
        $PluginShortplayDramaStoryboardActor->character_look_id = $PluginShortplayActorCharacterLook->id;
        $PluginShortplayDramaStoryboardActor->headimg = $PluginShortplayActorCharacterLook->headimg;
        $PluginShortplayDramaStoryboardActor->three_view_image = $PluginShortplayActorCharacterLook->three_view_image;
        $PluginShortplayDramaStoryboardActor->save();
        return $this->success('设置成功');
    }
    public function downloadPackage(Request $request)
    {
        $drama_id = $request->get('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $episode_id = $request->get('episode_id');
        $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['id' => $episode_id, 'drama_id' => $PluginShortplayDrama->id])->find();
        if (!$PluginShortplayDramaEpisode) {
            return $this->fail('分集不存在');
        }
        # 初始化打包
        $uuid = uniqid();
        $data = [
            'uuid' => $uuid,
            'uid' => $PluginShortplayDrama->uid,
            'channels_uid' => $PluginShortplayDrama->channels_uid,
            'drama_id' => $PluginShortplayDrama->id,
            'episode_id' => $PluginShortplayDramaEpisode->id,
            'url' => $request->header('x-forwarded-proto') . '://' . $request->host() . '/app/shortplay/download/storyboard?uuid=' . $uuid,
        ];
        Coroutine::create(function () use ($data) {
            try {
                sleep(2);
                $PluginShortplayDramaStoryboards = PluginShortplayDramaStoryboard::where(['drama_id' => $data['drama_id'], 'episode_id' => $data['episode_id']])->with(['dialogues'])->order('sort', 'asc')->select();
                $storyboardSum = count($PluginShortplayDramaStoryboards);
                $zip = new \ZipArchive();
                $filename = $data['drama_id'] . '_' . $data['episode_id'] . '.zip';
                $filepath = runtime_path('temp/storyboard/');
                if (!is_dir($filepath)) {
                    mkdir($filepath, 0777, true);
                }
                if (!$zip->open($filepath . $filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
                    throw new \Exception('打包失败');
                }
                $localFiles = [];
                # 打包图片素材
                $index = 0;
                foreach ($PluginShortplayDramaStoryboards as $PluginShortplayDramaStoryboard) {
                    if (!empty($PluginShortplayDramaStoryboard->image)) {
                        $localfile = Uploads::downloadTemp($PluginShortplayDramaStoryboard->image);
                        if (!file_exists($localfile)) {
                            throw new \Exception('图片素材下载失败');
                        }
                        $localFiles[] = $localfile;
                        $ext = pathinfo($localfile, PATHINFO_EXTENSION);
                        $zip->addFile($localfile, $PluginShortplayDramaStoryboard->sort . '/image.' . $ext);
                        $progress = 5 + round(($index / $storyboardSum) * 10);
                        $index++;
                        Push::send([
                            'uid' => $data['uid'],
                            'channels_uid' => $data['channels_uid'],
                            'event' => 'downloadPackage',
                        ], ['uuid' => $data['uuid'], 'action' => 'process', 'index' => 1, 'progress' => $progress]);
                    }
                }
                # 打包视频素材
                $index = 0;
                foreach ($PluginShortplayDramaStoryboards as $PluginShortplayDramaStoryboard) {
                    if (!empty($PluginShortplayDramaStoryboard->video)) {
                        $localfile = Uploads::downloadTemp($PluginShortplayDramaStoryboard->video);
                        if (!file_exists($localfile)) {
                            throw new \Exception('视频素材下载失败');
                        }
                        $ext = pathinfo($localfile, PATHINFO_EXTENSION);
                        $zip->addFile($localfile, $PluginShortplayDramaStoryboard->sort . '/video.' . $ext);
                        $localFiles[] = $localfile;
                        $progress = 15 + round(($index / $storyboardSum) * 40);
                        $index++;
                        Push::send([
                            'uid' => $data['uid'],
                            'channels_uid' => $data['channels_uid'],
                            'event' => 'downloadPackage',
                        ], ['uuid' => $data['uuid'], 'action' => 'process', 'index' => 2, 'progress' => $progress]);
                    }
                }
                # 打包对话素材
                $srtContent = [];

                /**
                 * 毫秒 → SRT 时间格式
                 */
                function msToSrtTime(int $ms): string
                {
                    $hours = floor($ms / 3600000);
                    $minutes = floor(($ms % 3600000) / 60000);
                    $seconds = floor(($ms % 60000) / 1000);
                    $milliseconds = $ms % 1000;

                    return sprintf(
                        '%02d:%02d:%02d,%03d',
                        $hours,
                        $minutes,
                        $seconds,
                        $milliseconds
                    );
                }

                $index = 1;                 // SRT 序号必须从 1 开始
                $currentTimeline = 0;       // 全局时间轴（毫秒）

                foreach ($PluginShortplayDramaStoryboards as $storyboard) {
                    if (empty($storyboard->dialogues)) {
                        continue;
                    }
                    $dialogueText = '';
                    $maxEnd = 0;
                    foreach ($storyboard->dialogues as $dialogue) {

                        // 当前字幕在全局时间轴中的开始 / 结束时间
                        $startMs = $currentTimeline + $dialogue->start_time;
                        $endMs   = $currentTimeline + $dialogue->end_time;

                        $temp =
                            $index . "\n" .
                            msToSrtTime($startMs) . " --> " . msToSrtTime($endMs) . "\n" .
                            trim($dialogue->content) . "\n";
                        $dialogueText .= $temp . "\n";
                        $srtContent[] = $temp;
                        $index++;
                        $maxEnd = max($maxEnd, $endMs);
                    }

                    $currentTimeline += $maxEnd;
                    if (!empty($dialogueText)) {
                        $zip->addFromString($storyboard->sort . '/对话.txt', $dialogueText);
                    }
                }

                // 最终 SRT 文本
                $srtText = implode("\n", $srtContent);
                $zip->addFromString('字幕.srt', $srtText);
                Push::send([
                    'uid' => $data['uid'],
                    'channels_uid' => $data['channels_uid'],
                    'event' => 'downloadPackage',
                ], ['uuid' => $data['uuid'], 'action' => 'process', 'index' => 3, 'progress' => 55]);
                # 打包音频素材
                $index = 0;
                foreach ($PluginShortplayDramaStoryboards as $PluginShortplayDramaStoryboard) {
                    if (empty($PluginShortplayDramaStoryboard->dialogues)) {
                        continue;
                    }
                    $n = 0;
                    foreach ($PluginShortplayDramaStoryboard->dialogues as $PluginShortplayDramaStoryboardDialogue) {
                        if (!empty($PluginShortplayDramaStoryboardDialogue->audio)) {
                            $localfile = Uploads::downloadTemp($PluginShortplayDramaStoryboardDialogue->audio);
                            if (!file_exists($localfile)) {
                                throw new \Exception('音频素材下载失败');
                            }
                            $ext = pathinfo($localfile, PATHINFO_EXTENSION);
                            $zip->addFile($localfile, $PluginShortplayDramaStoryboard->sort . "/{$n}-{$PluginShortplayDramaStoryboardDialogue->actor->name}." . $ext);
                            $localFiles[] = $localfile;
                        }
                        $n++;
                    }
                    $progress = 55 + round(($index / $storyboardSum) * 10);
                    $index++;
                    Push::send([
                        'uid' => $data['uid'],
                        'channels_uid' => $data['channels_uid'],
                        'event' => 'downloadPackage',
                    ], ['uuid' => $data['uuid'], 'action' => 'process', 'index' => 4, 'progress' => $progress]);
                }
                # 打包旁白素材
                foreach ($PluginShortplayDramaStoryboards as $PluginShortplayDramaStoryboard) {
                    if (!empty($PluginShortplayDramaStoryboard->narration)) {
                        $zip->addFromString($PluginShortplayDramaStoryboard->sort . '/旁白.txt', $PluginShortplayDramaStoryboard->narration);
                    }
                }
                Push::send([
                    'uid' => $data['uid'],
                    'channels_uid' => $data['channels_uid'],
                    'event' => 'downloadPackage',
                ], ['uuid' => $data['uuid'], 'action' => 'process', 'index' => 5, 'progress' => 75]);
                # 打包旁白音频素材
                $index = 0;
                foreach ($PluginShortplayDramaStoryboards as $PluginShortplayDramaStoryboard) {
                    if (!empty($PluginShortplayDramaStoryboard->narration_audio)) {
                        $localfile = Uploads::downloadTemp($PluginShortplayDramaStoryboard->narration_audio);
                        if (!file_exists($localfile)) {
                            throw new \Exception('旁白音频素材下载失败');
                        }
                        $ext = pathinfo($localfile, PATHINFO_EXTENSION);
                        $zip->addFile($localfile, $PluginShortplayDramaStoryboard->sort . '/旁白.' . $ext);
                        $localFiles[] = $localfile;
                    }
                    $progress = 75 + round(($index / $storyboardSum) * 10);
                    $index++;
                    Push::send([
                        'uid' => $data['uid'],
                        'channels_uid' => $data['channels_uid'],
                        'event' => 'downloadPackage',
                    ], ['uuid' => $data['uuid'], 'action' => 'process', 'index' => 6, 'progress' => $progress]);
                }
                Push::send([
                    'uid' => $data['uid'],
                    'channels_uid' => $data['channels_uid'],
                    'event' => 'downloadPackage',
                ], ['uuid' => $data['uuid'], 'action' => 'process', 'index' => 7, 'progress' => 95]);
                $zip->close();
                $cacheData = [
                    'uuid' => $data['uuid'],
                    'filename' => $filename,
                    'filepath' => $filepath,
                ];
                Cache::set('downloadPackage_' . $data['uuid'], $cacheData, 60 * 60 * 24);
                foreach ($localFiles as $localFile) {
                    if (file_exists($localFile)) {
                        unlink($localFile);
                    }
                }
                Push::send([
                    'uid' => $data['uid'],
                    'channels_uid' => $data['channels_uid'],
                    'event' => 'downloadPackage',
                ], ['uuid' => $data['uuid'], 'action' => 'process', 'index' => 7, 'progress' => 100, 'url' => $data['url'], 'filename' => $filename]);
            } catch (\Throwable | ClientException | RequestException $th) {
                Log::error("下载打包异常：" . $th->getMessage(), $th->getTrace());
                Push::send([
                    'uid' => $data['uid'],
                    'channels_uid' => $data['channels_uid'],
                    'event' => 'downloadPackage',
                ], ['uuid' => $data['uuid'], 'msg' => $th->getMessage()]);
            }
        });
        return $this->resData(['uuid' => $uuid]);
    }
}
