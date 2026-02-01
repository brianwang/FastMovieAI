<?php

namespace plugin\control\expose\helper;

use app\expose\helper\Config;
use app\model\Uploads as ModelUploads;
use app\model\UploadsClassify;
use Exception;
use GuzzleHttp\Client;
use Shopwwi\WebmanFilesystem\Storage;
use Shopwwi\WebmanFilesystem\FilesystemFactory;
use Webman\Http\UploadFile;

class Uploads
{
    /**
     * 获取文件URL
     * @param int $channels_uid 渠道用户ID
     * @param string|array|null $path 文件路径
     * @return string|array
     */
    public static function url(int $channels_uid, string|array|null $path)
    {
        if (empty($path)) {
            return $path;
        }
        if (is_array($path)) {
            $data = [];
            foreach ($path as $value) {
                $data[] = self::url($channels_uid, $value);
            }
            return $data;
        }
        $model = ModelUploads::where(['path' => $path, 'channels_uid' => $channels_uid])->find();
        if (!$model) {
            return $path;
        }
        $config = new Config('filesystem', '', $channels_uid);
        $Storage = new Storage($config->toArray());
        return $Storage->adapter($model->channels)->url($model->path);
    }
    /**
     * 获取文件在本地存储的完整路径
     * @param int $channels_uid 渠道用户ID
     * @param string|array|null $path 文件路径
     * @return string|array
     */
    public static function local(int $channels_uid, string|array|null $path)
    {
        if (empty($path)) {
            return $path;
        }
        if (is_array($path)) {
            $data = [];
            foreach ($path as $value) {
                $data[] = self::local($channels_uid, $value);
            }
            return $data;
        }
        if (strpos($path, '[') === 0) {
            $key = substr($path, 1, strpos($path, ']') - 1);
            $model = new \stdClass;
            $model->channels = $key;
            $model->path = substr($path, strpos($path, ']') + 1);
        } else {
            $model = ModelUploads::where(['path' => $path, 'channels_uid' => $channels_uid])->find();
            if (!$model) {
                return $path;
            }
        }
        $config = new Config('filesystem', '', $channels_uid);
        $filesystem =  FilesystemFactory::get($model->channels, $config->toArray());
        $has = $filesystem->has($model->path);
        if ($has) {
            return self::downloadTemp(self::url($channels_uid, $model->path));
        }
        return $path;
    }
    /**
     * 获取文件路径
     * @param string|array|null $url URL地址
     * @return string|array
     */
    public static function path(int $channels_uid, string|array|null $url)
    {
        if (empty($url)) {
            return '';
        }
        if (is_array($url)) {
            $data = [];
            if (count($url) === 1) {
                return self::path($channels_uid, current($url));
            }
            foreach ($url as $value) {
                $data[] = self::path($channels_uid, $value);
            }
            return $data;
        } else {
            if (filter_var($url, FILTER_SANITIZE_URL) === false) {
                throw new Exception('URL地址不合法');
            }
            $parseUrl = parse_url($url);
            return ltrim($parseUrl['path'], '/');
        }
    }
    public static function getClassify(int $channels_uid, string $dir_name, string $title, $channels = null)
    {
        if (!$channels) {
            $config = new Config('filesystem', '', $channels_uid);
            $channels = $config->default;
        }
        $UploadsClassify = UploadsClassify::where(['dir_name' => $dir_name, 'channels' => $channels, 'channels_uid' => $channels_uid])->find();
        if (!$UploadsClassify) {
            $UploadsClassify = new UploadsClassify;
            $UploadsClassify->title = $title;
            $UploadsClassify->dir_name = $dir_name;
            $UploadsClassify->channels = $channels;
            $UploadsClassify->channels_uid = $channels_uid;
            $UploadsClassify->sort = 0;
            $UploadsClassify->is_system = 1;
            $UploadsClassify->save();
        }
        return $UploadsClassify;
    }
    /**
     * 保存文件
     * @param int $channels_uid 渠道用户ID
     * @param string $path 文件路径
     * @param string $channels 文件存储通道
     * @param string $dir_name 文件存储目录
     * @param string $title 文件分类标题
     * @return array
     */
    public static function save(int $channels_uid, string $path, $channels = null, $dir_name = 'uploads/save', $title = '本地保存')
    {
        $config = new Config('filesystem', '', $channels_uid);
        if (!$channels) {
            $channels = $config->default;
        }
        $UploadsClassify = UploadsClassify::where(['dir_name' => $dir_name, 'channels' => $channels, 'channels_uid' => $channels_uid])->find();
        if (!$UploadsClassify) {
            $UploadsClassify = new UploadsClassify;
            $UploadsClassify->title = $title;
            $UploadsClassify->dir_name = $dir_name;
            $UploadsClassify->channels = $channels;
            $UploadsClassify->channels_uid = $channels_uid;
            $UploadsClassify->sort = 0;
            $UploadsClassify->is_system = 1;
            $UploadsClassify->save();
        }
        $date_path = date('Ymd');
        $originName = basename($path);
        //单文件上传
        $file = new UploadFile($path, $originName, mime_content_type($path), filesize($path));
        $Storage = new Storage($config->toArray());
        $result = $Storage->adapter($channels)->path($dir_name . '/' . $date_path)->upload($file);
        $Uploads = new ModelUploads;
        $Uploads->classify_id = $UploadsClassify->id;
        $Uploads->filename = $result->origin_name;
        $Uploads->path = $result->file_name;
        $Uploads->ext = $result->extension;
        $Uploads->mime = $result->mime_type;
        $Uploads->size = $result->size;
        $Uploads->channels = $channels;
        $Uploads->channels_uid = $channels_uid;
        $Uploads->save();
        return [
            'id' => $Uploads->id,
            'url' => $result->file_url,
            'path' => $result->file_name,
            'mime' => $result->mime_type,
            'dir_name' => $dir_name
        ];
    }
    /**
     * 远程下载文件
     * @param string $url 文件URL
     * @param string $channels 文件存储通道
     * @return mixed
     */
    public static function download(int $channels_uid, string $url, UploadsClassify $UploadsClassify)
    {
        $dir_name = $UploadsClassify->dir_name;
        $config = new Config('filesystem', '', $channels_uid);
        $channels = $UploadsClassify->channels;
        $date_path = date('Ymd');
        $client = new Client([
            'timeout' => 30,
            'verify' => false,
            'proxy' => false
        ]);
        $response = $client->get($url);
        $body = $response->getBody();
        $file = $body->getContents();
        $urlPath = parse_url($url, PHP_URL_PATH);
        $ext = pathinfo($urlPath, PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $ext;
        $temp = tempnam(sys_get_temp_dir(), '') . $fileName;
        file_put_contents($temp, $file);
        $file = new UploadFile($temp, $temp, $response->getHeaderLine('Content-Type'),  0);
        $Storage = new Storage($config->toArray());
        $result = $Storage->adapter($channels)->path($dir_name . '/' . $date_path)->upload($file);
        \unlink($temp);
        $Uploads = new ModelUploads;
        $Uploads->classify_id = $UploadsClassify->id;
        $Uploads->filename = $result->origin_name;
        $Uploads->path = $result->file_name;
        $Uploads->ext = $result->extension;
        $Uploads->mime = $result->mime_type;
        $Uploads->size = $result->size;
        $Uploads->channels = $channels;
        $Uploads->channels_uid = $channels_uid;
        $Uploads->save();
        return $result;
    }
    /**
     * 下载文件到临时文件
     * @param string $url 文件URL
     * @return string 临时文件路径
     */
    public static function downloadTemp(string $url, ?string $fileName = null)
    {
        if (!$fileName) {
            $fileName = uniqid();
        }
        $urlPath = parse_url($url, PHP_URL_PATH);
        $ext = pathinfo($urlPath, PATHINFO_EXTENSION);
        $temp = runtime_path('temp/' . $fileName . '.' . $ext);
        if (file_exists($temp)) {
            return $temp;
        }
        $client = new Client();
        $response = $client->get($url);
        $body = $response->getBody();
        $file = $body->getContents();
        file_put_contents($temp, $file);
        return $temp;
    }
    /**
     * 上传文件
     * @param string $path 文件路径
     * @param string $channels 文件存储通道
     * @param string $dir_name 文件存储目录
     * @return array
     */
    public static function upload(int $channels_uid, string|UploadFile $path, $channels = null, $dir_name = 'uploads/save')
    {
        $date_path = date('Ymd');
        //单文件上传
        if (is_string($path)) {
            $originName = basename($path);
            $file = new UploadFile($path, $originName, mime_content_type($path), filesize($path));
        } else {
            $originName = $path->getUploadName();
            $file = $path;
        }
        $config = new Config('filesystem', '', $channels_uid);
        if (!$channels) {
            $channels = $config->default;
        }
        $Storage = new Storage($config->toArray());
        $result = $Storage->adapter($channels)->path($dir_name . '/' . $date_path)->upload($file);
        $Uploads = new ModelUploads;
        $Uploads->filename = $result->origin_name;
        $Uploads->path = $result->file_name;
        $Uploads->ext = $result->extension;
        $Uploads->mime = $result->mime_type;
        $Uploads->size = $result->size;
        $Uploads->channels = $channels;
        $Uploads->channels_uid = $channels_uid;
        $Uploads->save();
        return [
            'id' => $Uploads->id,
            'url' => $result->file_url,
            'path' => $result->file_name,
            'mime' => $result->mime_type,
            'dir_name' => $dir_name
        ];
    }
    /**
     * 删除文件
     * @param int $channels_uid 渠道用户ID
     * @param string $path 文件路径
     * @return bool
     */
    public static function delete(int $channels_uid, string $path)
    {
        $model = ModelUploads::where(['path' => $path, 'channels_uid' => $channels_uid])->find();
        if (!$model) {
            return true;
        }
        $config = new Config('filesystem', '', $channels_uid);
        $filesystem =  FilesystemFactory::get($model->channels, $config->toArray());
        $filesystem->delete($model->path);
        $model->delete();
        return true;
    }
    /**
     * 重命名文件
     * @param int $channels_uid 渠道用户ID
     * @param string $path 文件路径
     * @param string $newName 新文件名
     * @return bool
     */
    public static function rename(int $channels_uid, string $path, string $newName)
    {
        $model = ModelUploads::where(['path' => $path, 'channels_uid' => $channels_uid])->find();
        if (!$model) {
            return false;
        }
        $config = new Config('filesystem', '', $channels_uid);
        $filesystem =  FilesystemFactory::get($model->channels, $config->toArray());
        $filesystem->move($model->path, $newName);
        $model->path = $newName;
        $model->save();
        return true;
    }
    /**
     * 判断文件是否存在
     * @param int $channels_uid 渠道用户ID
     * @param string $path 文件路径
     * @return bool
     */
    public static function has(int $channels_uid, string $path)
    {
        $model = ModelUploads::where(['path' => $path, 'channels_uid' => $channels_uid])->find();
        if (!$model) {
            return false;
        }
        $config = new Config('filesystem', '', $channels_uid);
        $filesystem =  FilesystemFactory::get($model->channels, $config->toArray());
        return $filesystem->has($model->path);
    }
    /**
     * 复制文件
     * @param int $channels_uid 渠道用户ID
     * @param string $path 文件路径
     * @param string $newName 新文件名
     * @return bool
     */
    public static function copy(int $channels_uid, string $path, string $newName)
    {
        $model = ModelUploads::where(['path' => $path, 'channels_uid' => $channels_uid])->find();
        if (!$model) {
            return false;
        }
        $config = new Config('filesystem', '', $channels_uid);
        $filesystem =  FilesystemFactory::get($model->channels, $config->toArray());
        $filesystem->copy($model->path, $newName);
        $model = new ModelUploads;
        $model->classify_id = $model->classify_id;
        $model->filename = $model->filename;
        $model->path = $newName;
        $model->ext = $model->ext;
        $model->mime = $model->mime;
        $model->size = $model->size;
        $model->path = $newName;
        $model->save();
        return true;
    }
    /**
     * 获取文件列表
     * @param int $channels_uid 渠道用户ID
     * @param string $path 文件路径
     * @param bool $recursive 是否递归
     * @return array
     */
    public static function listContents(int $channels_uid, string $path, $recursive = false)
    {
        $model = ModelUploads::where(['path' => $path, 'channels_uid' => $channels_uid])->find();
        if (!$model) {
            return false;
        }
        $config = new Config('filesystem', '', $channels_uid);
        $filesystem =  FilesystemFactory::get($model->channels, $config->toArray());
        return $filesystem->listContents($path, $recursive);
    }
}
