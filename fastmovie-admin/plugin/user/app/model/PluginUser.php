<?php

namespace plugin\user\app\model;

use app\expose\enum\EventName;
use app\expose\utils\Password;
use app\expose\utils\Rsa;
use app\expose\utils\Str;
use app\model\Basic;
use loong\oauth\facade\Auth;
use plugin\control\app\model\PluginChannelsUser;
use plugin\control\expose\helper\Uploads;
use plugin\finance\app\model\PluginFinanceWallet;
use plugin\user\utils\enum\UserPermission;
use Webman\Event\Event;

class PluginUser extends Basic
{
    public function channels()
    {
        return $this->hasOne(PluginChannelsUser::class, 'id', 'channels_uid');
    }
    public function parent()
    {
        return $this->hasOne(PluginUser::class, 'id', 'puid');
    }
    public function wallet()
    {
        return $this->hasOne(PluginFinanceWallet::class, 'uid', 'id');
    }
    public function getHeadimgAttr($value, $data)
    {
        if(empty($value)){
            return '';
        }
        return Uploads::url($data['channels_uid'], $value);
    }
    public function setHeadimgAttr($value, $data)
    {
        return Uploads::path($data['channels_uid'], $value);
    }
    public function setPasswordAttr($value)
    {
        return $value ? Password::encrypt($value) : $value;
    }
    public static function options($where = [])
    {
        $models = self::where($where)->field('id,nickname,mobile')->select();
        $data = [];
        foreach ($models as $item) {
            $data[] = [
                'label' => $item->nickname,
                'value' => $item->id,
                'tips' => "UID：{$item->id}，M：{$item->mobile}"
            ];
        }
        return $data;
    }
    public static function genUser($id)
    {
        $id = (int)$id;
        return str_replace(['+', '/'], ['-', '_'], Rsa::encryptNumber($id));
    }
    public static function getUidByUser($user)
    {
        return Rsa::decryptNumber(str_replace(['-', '_'], ['+', '/'], $user));
    }
    public static function getTokenInfo($model, $twofa = false)
    {
        $request = request();
        /* 重组用户信息 */
        $User = new \stdClass;
        $User->user = self::genUser($model->id);
        $User->nickname = $model->nickname;
        $User->headimg = $model->headimg;
        $User->id = $model->id;
        $User->username = $model->username;
        $User->username_time = $model->username_time ? 30 - ceil((time() - strtotime($model->username_time)) / 86400) : 0;
        $User->mobile = Str::mask($model->mobile);
        $User->email = Str::mask($model->email);
        $User->password = $model->password ? 1 : 0;
        $User->create_time = $model->create_time;
        $User->last_login_time = $model->login_time;
        $User->last_login_ip = $model->login_ip;
        $User->twofa_state = $model->twofa_state;
        $User->activation_time = $model->activation_time;
        $PluginUserWechat = PluginUserWechat::where(['uid' => $model->id])->field('openid,unionid,mp_openid,nickname,headimg,subscribe')->find();
        if ($PluginUserWechat) {
            $User->wechat = $PluginUserWechat;
        }
        $User->permissions = [];
        if ($model->mobile) {
            $User->permissions[] = UserPermission::MOBILE['value'];
        }
        if ($model->email) {
            $User->permissions[] = UserPermission::EMAIL['value'];
        }
        if ($model->twofa_state) {
            $User->permissions[] = UserPermission::TWOFA['value'];
        }
        if ($model->username) {
            $User->permissions[] = UserPermission::USERNAME['value'];
        }
        if ($model->password) {
            $User->permissions[] = UserPermission::PASSWORD['value'];
        }
        if ($model->realname) {
            $User->permissions[] = UserPermission::REALNAME['value'];
        }
        if ($model->mobile && $model->realname) {
            $User->permissions[] = UserPermission::BUY['value'];
        }
        $pluginConfig = glob(base_path("plugin/*/api/{$request->app}/PublicController.php"));
        foreach ($pluginConfig as $path) {
            $plugin_name = basename(dirname(dirname(dirname($path))));
            if ($plugin_name == 'user') {
                continue;
            }
            $class = 'plugin\\' . $plugin_name . "\\api\\{$request->app}\\PublicController";
            if (!class_exists($class)) {
                continue;
            }
            $plugin = new $class;
            if (method_exists($plugin, 'appendUserInfo')) {
                $plugin->appendUserInfo($User, $model);
            }
        }
        /* 生成token */
        $data = new \stdClass;
        $data->uid = $model->id;
        $data->channels_uid = $model->channels_uid;
        $data->username = $model->username;
        $data->mobile = $model->mobile;
        $data->email = $model->email;
        $data->twofa_state = $model->twofa_state;
        if ($twofa) {
            if ($twofa === true) {
                $data->twofa = [
                    'expire' => time() + (config('oauth.expire') / 2),
                    'time' => time(),
                ];
            } else {
                $data->twofa = $twofa;
            }
        }
        $User->token = Auth::setPrefix('CONTROL')->encrypt($data);
        if ($request->token) {
            Auth::setPrefix('CONTROL')->refresh($request->token, 60);
        }
        return $User;
    }
    public static function onAfterRead($model)
    {
        if (empty($model->nickname)) {
            if (!empty($model->mobile)) {
                $model->nickname = 'FM-' . substr($model->mobile, -4);
            } else {
                $model->nickname = '未命名的用户';
            }
        }
    }
    public static function onBeforeWrite($model)
    {
        if (empty($model->nickname)) {
            if (!empty($model->mobile)) {
                $model->nickname = 'FM-' . substr($model->mobile, -4);
            } else {
                $model->nickname = 'FM-' . Str::random();
            }
        } else {
            $model->nickname = iconv('UTF-8', 'UTF-8//IGNORE', $model->nickname);
        }
    }
    public static function onAfterInsert($model)
    {
        $PluginFinanceWallet = new PluginFinanceWallet;
        $PluginFinanceWallet->channels_uid = $model->channels_uid;
        $PluginFinanceWallet->uid = $model->id;
        $PluginFinanceWallet->save();
        Event::emit(EventName::USER_REGISTER['value'], $model);
    }
}
