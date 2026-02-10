<?php

namespace plugin\shortplay\app\api\controller;

use app\Basic;
use app\expose\helper\Config;
use app\expose\utils\Str;
use app\model\Uploads as ModelUploads;
use plugin\control\expose\helper\Uploads;
use plugin\shortplay\app\model\PluginShortplayDrama;
use plugin\shortplay\app\model\PluginShortplayDramaActor;
use plugin\shortplay\app\model\PluginShortplayDramaEpisode;
use plugin\shortplay\app\model\PluginShortplayDramaEpisodeActor;
use plugin\shortplay\app\model\PluginShortplayDramaScene;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboard;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardActor;
use plugin\shortplay\app\model\PluginShortplayDramaStoryboardProp;
use plugin\shortplay\utils\enum\VoiceLanguage;
use Shopwwi\WebmanFilesystem\Storage;
use support\Log;
use support\Request;
use support\think\Db;
use Webman\Http\UploadFile;
use Workerman\Coroutine;

class DramaController extends Basic
{
    public function uploadChunkCheck(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $episode_id = $request->post('episode_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['id' => $episode_id, 'drama_id' => $PluginShortplayDrama->id])->find();
        if (!$PluginShortplayDramaEpisode) {
            return $this->fail('分集不存在');
        }
        // 保存分片目录
        $tempDir = runtime_path('temp/chunks/' . $drama_id . '_' . $episode_id);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        } else {
            # 清空目录
            shell_exec('rm -rf ' . $tempDir . '/*');
        }
        return $this->success();
    }
    public function uploadChunk(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $episode_id = $request->post('episode_id');
        $chunkIndex = intval($request->post('chunkIndex', 0));
        $totalChunks = intval($request->post('totalChunks', 0));

        if (!$drama_id || !$episode_id || $totalChunks <= 0) {
            return $this->fail('参数错误');
        }

        // 上传的文件片段
        $chunkFile = $request->file('chunkData');
        if (!$chunkFile || $chunkFile->isValid() !== true) {
            return $this->fail('分片上传失败');
        }

        // 保存分片目录
        $tempDir = runtime_path('temp/chunks/' . $drama_id . '_' . $episode_id);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // 分片保存路径
        $chunkPath = $tempDir . '/' . $chunkIndex;
        $chunkFile->move($chunkPath);

        return $this->success('分片上传成功');
    }
    public function mergeChunks(Request $request)
    {
        Coroutine::create(function () use ($request) {
            try {
                $drama_id = $request->post('drama_id');
                $episode_id = $request->post('episode_id');
                $totalChunks = intval($request->post('totalChunks', 0));
                $channels_uid = (int)$request->channels_uid;
                if (empty($channels_uid)) {
                    throw new \Exception('渠道用户ID不存在');
                }

                if (!$drama_id || !$episode_id || $totalChunks <= 0) {
                    throw new \Exception('参数错误');
                }
                $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
                if (!$PluginShortplayDrama) {
                    throw new \Exception('短剧不存在');
                }
                $PluginShortplayDramaEpisode = PluginShortplayDramaEpisode::where(['id' => $episode_id, 'drama_id' => $PluginShortplayDrama->id])->find();
                if (!$PluginShortplayDramaEpisode) {
                    throw new \Exception('分集不存在');
                }

                $tempDir = runtime_path('temp/chunks/' . $drama_id . '_' . $episode_id);
                if (!is_dir($tempDir)) {
                    throw new \Exception('分片目录不存在');
                }

                $targetDir = runtime_path('temp/videos/');
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                $targetPath = $targetDir . $drama_id . '_' . $episode_id . '.mp4';
                if (file_exists($targetPath)) {
                    \unlink($targetPath);
                }

                $out = fopen($targetPath, 'ab');
                if (!$out) {
                    throw new \Exception('打开文件失败');
                }

                // 合并分片
                for ($i = 0; $i < $totalChunks; $i++) {
                    $chunkPath = $tempDir . '/' . $i;
                    if (!file_exists($chunkPath)) {
                        fclose($out);
                        throw new \Exception("分片 {$i} 不存在");
                    }
                    $in = fopen($chunkPath, 'rb');
                    stream_copy_to_stream($in, $out);
                    fclose($in);
                    // 删除分片
                    unlink($chunkPath);
                }
                fclose($out);

                // 删除分片目录
                rmdir($tempDir);
                try {
                    $file = new UploadFile($targetPath, $drama_id . '_' . $episode_id . '.mp4', 'video/mp4', 0);
                    $config = new Config('filesystem', '', $channels_uid);
                    $classify = Uploads::getClassify($channels_uid, 'uploads/drama/video', '短剧', $config->default);
                    $Storage = new Storage($config->toArray());
                    $date_path = date('Ymd');
                    $result = $Storage->adapter($classify->channels)->path($classify->dir_name . '/' . $date_path)->upload($file);
                    \unlink($targetPath);
                    $Uploads = new ModelUploads();
                    $Uploads->classify_id = $classify->id;
                    $Uploads->filename = $result->origin_name;
                    $Uploads->path = $result->file_name;
                    $Uploads->ext = $result->extension;
                    $Uploads->mime = $result->mime_type;
                    $Uploads->size = $result->size;
                    $Uploads->channels = $classify->channels;
                    $Uploads->channels_uid = $channels_uid;
                    $Uploads->save();
                } catch (\Throwable $th) {
                    \unlink($targetPath);
                    throw $th;
                }
                $PluginShortplayDramaEpisode->video_path = $result->file_url;
                $PluginShortplayDramaStoryboard = PluginShortplayDramaStoryboard::where(['drama_id' => $PluginShortplayDrama->id, 'episode_id' => $PluginShortplayDramaEpisode->id])->whereNotNull('image')->order('sort ASC')->find();
                $PluginShortplayDramaEpisode->cover = $PluginShortplayDramaStoryboard->image;
                $PluginShortplayDramaEpisode->save();
            } catch (\Throwable $th) {
                Log::error('合并分片失败：' . $th->getMessage(), $th->getTrace());
            }
        });
        return $this->success('合并成功');
    }
    public function update(Request $request)
    {
        $id = $request->post('id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $D = $request->post();
        if (!empty($D['style_id'])) {
            $PluginShortplayDrama->style_id = $D['style_id'];
        }
        if (!empty($D['aspect_ratio'])) {
            $PluginShortplayDrama->aspect_ratio = $D['aspect_ratio'];
        }
        if (!empty($D['title'])) {
            $PluginShortplayDrama->title = $D['title'];
        }
        if (!empty($D['overall_hook'])) {
            $PluginShortplayDrama->overall_hook = $D['overall_hook'];
        }
        if (!empty($D['core_catharsis_mechanism'])) {
            $PluginShortplayDrama->core_catharsis_mechanism = $D['core_catharsis_mechanism'];
        }
        if (!empty($D['main_conflict'])) {
            $PluginShortplayDrama->main_conflict = $D['main_conflict'];
        }
        if (!empty($D['relationship_mainline'])) {
            $PluginShortplayDrama->relationship_mainline = $D['relationship_mainline'];
        }
        if (!empty($D['description'])) {
            $PluginShortplayDrama->description = $D['description'];
        }
        if (!empty($D['background_description'])) {
            $PluginShortplayDrama->background_description = $D['background_description'];
        }
        if (!empty($D['outline'])) {
            $PluginShortplayDrama->outline = $D['outline'];
        }
        $PluginShortplayDrama->save();
        return $this->success('更新成功');
    }
    public function delete(Request $request)
    {
        $id = $request->post('id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        Db::startTrans();
        try {
            PluginShortplayDramaActor::where(['drama_id' => $PluginShortplayDrama->id])->delete();
            $PluginShortplayDramaEpisodes = PluginShortplayDramaEpisode::where(['drama_id' => $PluginShortplayDrama->id])->select();
            foreach ($PluginShortplayDramaEpisodes as $PluginShortplayDramaEpisode) {
                PluginShortplayDramaEpisodeActor::where(['episode_id' => $PluginShortplayDramaEpisode->id])->delete();
                $PluginShortplayDramaEpisode->delete();
            }
            PluginShortplayDramaScene::where(['drama_id' => $PluginShortplayDrama->id])->delete();
            $PluginShortplayDramaStoryboards = PluginShortplayDramaStoryboard::where(['drama_id' => $PluginShortplayDrama->id])->select();
            foreach ($PluginShortplayDramaStoryboards as $PluginShortplayDramaStoryboard) {
                PluginShortplayDramaStoryboardActor::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id])->delete();
                PluginShortplayDramaStoryboardProp::where(['storyboard_id' => $PluginShortplayDramaStoryboard->id])->delete();
                $PluginShortplayDramaStoryboard->delete();
            }
            $PluginShortplayDrama->delete();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        return $this->success('删除成功');
    }
    public function voice(Request $request)
    {
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        Db::startTrans();
        try {
            $voice = $request->post('voice');
            if (!empty($voice['emotions_enum']) && empty($voice['selected_emotion'])) {
                $voice['selected_emotion'] = $voice['emotions_enum'][0];
            }
            if (empty($voice['selected_language'])) {
                $voice['selected_language'] = VoiceLanguage::ZH;
            }
            $PluginShortplayDrama->voice = $voice;
            $PluginShortplayDrama->save();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        return $this->success('更新成功');
    }
    public function deleteActor(Request $request)
    {
        $id = $request->post('id');
        $drama_id = $request->post('drama_id');
        $PluginShortplayDrama = PluginShortplayDrama::where(['id' => $drama_id, 'uid' => $request->uid])->find();
        if (!$PluginShortplayDrama) {
            return $this->fail('短剧不存在');
        }
        $PluginShortplayDramaActor = PluginShortplayDramaActor::where(['actor_id' => $id, 'drama_id' => $PluginShortplayDrama->id])->find();
        if (!$PluginShortplayDramaActor) {
            return $this->fail('演员不存在');
        }
        Db::startTrans();
        try {
            $PluginShortplayDramaActor->delete();
            PluginShortplayDramaEpisodeActor::where(['drama_id' => $PluginShortplayDrama->id, 'actor_id' => $PluginShortplayDramaActor->actor_id])->delete();
            PluginShortplayDramaStoryboardActor::where(['drama_id' => $PluginShortplayDrama->id, 'actor_id' => $PluginShortplayDramaActor->actor_id])->delete();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->fail($th->getMessage());
        }
        return $this->success('删除成功');
    }
}
