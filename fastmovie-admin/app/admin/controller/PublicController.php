<?php

namespace app\admin\controller;

use app\Basic;
use app\expose\build\config\Action;
use app\expose\build\config\Web;
use app\expose\enum\Action as EnumAction;
use app\expose\enum\Filesystem;
use app\expose\enum\ResponseEvent;
use app\expose\helper\Config;
use app\expose\helper\Menus;
use app\expose\utils\DatabaseSchemaSync;
use loong\oauth\facade\Auth;
use support\Request;
use support\think\Db;

class PublicController extends Basic
{
    /**
     * 不需要登录的方法
     * @var string[]
     */
    protected $notNeedLogin = ['config', 'menus', 'unlock'];
    protected $notNeedAuth = ['config', 'menus', 'unlock'];
    public function config(Request $request)
    {
        $lang = $request->lang;
        $domain = $request->app;
        $config = Config::get('basic');
        $config = new Web($config);
        $captcha_state = Config::get('captcha', 'state');
        $config->useLogin([
            'title' => trans('Login Title', [], $domain, $lang),
            'url' => 'Login/login',
            'user_agreement' => '',
            'captcha' => $captcha_state
        ]);
        $config->useApis([
            'userinfo' => 'Admin/getSelfInfo',
            'lock' => 'Public/lock',
            'unlock' => 'Public/unlock',
            'menus' => 'Public/menus',
            'outLogin' => 'Public/outLogin'
        ]);
        $toolbar = new Action();
        $toolbar->add(EnumAction::LOCK['value'], [
            'icon' => 'Lock',
            'tips' => trans('toolbar Lock', [], $domain, $lang),
        ]);
        $toolbar->add(EnumAction::SEARCH['value'], [
            'icon' => 'Search',
            'tips' => trans('toolbar Search', [], $domain, $lang),
        ]);
        $toolbar->add(EnumAction::NOTIFICATION['value'], [
            'icon' => 'Notification',
            'tips' => trans('toolbar Notification', [], $domain, $lang),
        ]);
        $toolbar->add(EnumAction::FULL_SCREEN['value'], [
            'icon' => 'FullScreen',
            'tips' => trans('toolbar FullScreen', [], $domain, $lang),
        ]);
        // 读取update/VERSION文件和跟目录下的VERSION文件对比
        $updateVersionContent = file_get_contents(base_path('update/VERSION'));
        $updateVersion = explode("\n", $updateVersionContent);
        $updateVersion = (int)trim($updateVersion[0]);
        $versionContent = file_get_contents(base_path('VERSION'));
        $version = explode("\n", $versionContent);
        $version = (int)trim($version[0]);
        if ($updateVersion < $version) {
            $toolbar->add(EnumAction::COMFIRM['value'], [
                'path' => 'Public/syncSql',
                'icon' => 'Coin',
                'tips' => trans('同步SQL', [], $domain, $lang),
                'props' => [
                    'message' => '检测到有SQL未同步，确定要同步SQL吗？',
                    'confirmButtonClass' => 'el-button--danger'
                ]
            ]);
        }
        $toolbar->add(EnumAction::LINK['value'], [
            'icon' => 'House',
            'tips' => trans('toolbar House', [], $domain, $lang),
            'props' => [
                'url' => '/',
                'target' => '_blank'
            ]
        ]);
        $toolbar->add(EnumAction::LINK['value'], [
            'icon' => 'ElementPlus',
            'tips' => trans('toolbar Control', [], $domain, $lang),
            'props' => [
                'url' => '/control/',
                'target' => '_blank'
            ]
        ]);
        $config->useToolbar($toolbar->toArray());
        $userDropdownMenu = new Action();
        $userDropdownMenu->add(EnumAction::DIALOG['value'], [
            'path' => 'Admin/updateSelf',
            'label' => trans('userDropdownMenu updateSelf', [], $domain, $lang),
            'icon' => 'User',
            'props' => [
                'title' => trans('userDropdownMenu updateSelf', [], $domain, $lang)
            ]
        ]);
        $config->useUserDropdownMenu($userDropdownMenu->toArray());
        $config->storage = Filesystem::getOptions();
        $pluginConfig = glob(base_path("plugin/*/api/{$request->app}/PublicController.php"));
        foreach ($pluginConfig as $path) {
            $plugin_name = basename(dirname(dirname(dirname($path))));
            $class = 'plugin\\' . $plugin_name . "\\api\\{$request->app}\\PublicController";
            if (!class_exists($class)) {
                continue;
            }
            $plugin = new $class;
            if (method_exists($plugin, 'config')) {
                $plugin->config($config);
            }
        }
        $updateVersionContent = file_get_contents(base_path('update/VERSION'));
        $updateVersionArr = explode("\n", $updateVersionContent);
        $config->version_name = $updateVersionArr[1];
        $config->version = $updateVersionArr[0];
        return $this->resData($config);
    }
    public function menus(Request $request)
    {
        $Install = new \app\admin\api\Install;
        $menus = new Menus($Install);
        return $this->resData($menus);
    }
    public function outLogin(Request $request)
    {
        $token = $request->header('Authorization');
        if ($token) {
            try {
                Auth::setPrefix('ADMIN')->delete($token);
            } catch (\Throwable $th) {
            }
        }
        return $this->success(trans('Logout Success', [], $request->app, $request->lang));
    }
    public function lock(Request $request)
    {
        try {
            $password = $request->post('password');
            if (!$password) {
                return $this->fail(trans('PIN Code Cannot Be Empty', [], $request->app, $request->lang));
            }
            if (mb_strlen($password) != 6) {
                return $this->fail(trans('Please Enter 6 Digits PIN Code', [], $request->app, $request->lang));
            }
            $token = $request->header('Authorization');
            Auth::setPrefix('CONTROL')->lock($token, $password);
            return $this->event(ResponseEvent::UPDATE_USERINFO, trans('Lock Success', [], $request->app, $request->lang));
        } catch (\Throwable $th) {
            return $this->exception($th);
        }
    }
    public function unlock(Request $request)
    {
        $password = $request->post('password');
        try {
            $token = $request->header('Authorization');
            Auth::setPrefix('CONTROL')->unlock($token, $password);
            return $this->success(trans('Unlock Success', [], $request->app, $request->lang));
        } catch (\Throwable $th) {
            return $this->exception($th);
        }
    }
    public function syncSql(Request $request)
    {
        $updateVersionContent = file_get_contents(base_path('update/VERSION'));
        $updateVersionArr = explode("\n", $updateVersionContent);
        $updateVersion = (int)trim($updateVersionArr[0]);
        $versionContent = file_get_contents(base_path('VERSION'));
        $versionArr = explode("\n", $versionContent);
        $version = (int)trim($versionArr[0]);
        if ($updateVersion >= $version) {
            return $this->fail('已是最新版本');
        }
        $sqlSum = 0;
        $sqlSuccessSum = 0;
        $sqlErrorSum = 0;
        for ($i = $updateVersion + 1; $i <= $version; $i++) {
            $sqlFile = glob(base_path("update/{$i}/*.sql"));
            if (empty($sqlFile)) {
                continue;
            }
            foreach ($sqlFile as $file) {
                $sqlSum++;
                Db::startTrans();
                try {
                    $sql = file_get_contents($file);
                    $prefix = config('thinkorm.connections.mysql.prefix');
                    $sql = str_replace('`php_', "`$prefix", $sql);
                    $sql = str_replace('INTO php_', "INTO $prefix", $sql);
                    Db::execute($sql);
                    Db::commit();
                    $sqlSuccessSum++;
                } catch (\Throwable $th) {
                    Db::rollback();
                    $sqlErrorSum++;
                }
            }
        }
        file_put_contents(base_path('update/VERSION'), $versionContent);
        return $this->success('同步SQL成功，共执行' . $sqlSum . '条SQL，成功' . $sqlSuccessSum . '条，失败' . $sqlErrorSum . '条，已更新到：' . $versionArr[1]);
    }
}
