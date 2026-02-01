<?php

namespace plugin\user\app\api\controller;

use app\Basic;
use app\expose\enum\ResponseCode;
use app\expose\enum\ResponseEvent;
use app\expose\enum\State;
use plugin\control\expose\helper\Vcode;
use app\expose\utils\TwofaAuthenticator;
use app\model\Uploads;
use app\model\UploadsClassify;
use plugin\finance\app\model\PluginFinanceWallet;
use plugin\user\app\model\PluginUser;
use plugin\user\app\model\PluginUserInvitationCode;
use plugin\user\app\model\PluginUserTwofaSecret;
use plugin\user\app\model\PluginUserWechat;
use plugin\user\expose\helper\User;
use plugin\user\utils\enum\TotpApp;
use plugin\user\utils\enum\VcodeScene;
use Shopwwi\WebmanFilesystem\Facade\Storage;
use support\Request;
use think\facade\Db;

class UserController extends Basic
{
    protected $notNeedTwofa = ['getTwofaList', 'refreshTwofaSecret'];
    protected $notNeedLogin = ['checkInvitationCode'];
    public function info(Request $request)
    {
        $user = PluginUser::where(['id' => $request->uid])->find();
        if (!$user) {
            return $this->fail('用户不存在');
        }
        $info = PluginUser::getTokenInfo($user, $request->twofa);
        $count = PluginUserInvitationCode::where(['uid' => $request->uid])->where('state', State::YES['value'])->where('status', 'unused')->count();
        $info->invitation_code_count = $count;
        return $this->resData($info);
    }
    public function update(Request $request)
    {
        $D = $request->post();
        try {
            if ($request->twofa_state) {
                $time = time() - (10 * 60);
                if ($request->twofa['time'] < $time) {
                    return $this->fail('请验证你的身份', [
                        'code' => ResponseCode::NEED_TWOFA,
                        'action' => 'stop'
                    ]);
                }
                switch (true) {
                    case isset($D['username']):
                        $PluginUser = PluginUser::where(['id' => $request->uid])->find();
                        $username_time = $PluginUser->username_time ? 30 - ceil((time() - strtotime($PluginUser->username_time)) / 86400) : 0;
                        if ($username_time > 0) {
                            return $this->fail('30天内仅支持修改一次');
                        }
                        break;
                    case isset($D['mobile']):
                        Vcode::check($D['mobile'], $D['vcode'], VcodeScene::BIND_MOBILE['value'], $D['token']);
                        break;
                    case isset($D['email']):
                        Vcode::check($D['email'], $D['vcode'], VcodeScene::BIND_EMAIL['value'], $D['token']);
                        break;
                    case isset($D['password']):
                        if (empty($D['vpassword'])) {
                            return $this->fail('两次输入的密码不一致');
                        }
                        if ($D['password'] != $D['vpassword']) {
                            return $this->fail('两次输入的密码不一致');
                        }
                        Vcode::check($D['username'], $D['vcode'], VcodeScene::SET_PASSWORD['value'], $D['token'] ?? null);
                        break;
                }
            }
            $D['channels_uid'] = $request->channels_uid;
            User::update($request->uid, $D);
        } catch (\Throwable $th) {
            return $this->exception($th);
        }
        return $this->code(ResponseCode::SUCCESS_EVENT_PUSH, '更新成功', [
            'event' => ResponseEvent::UPDATE_USERINFO
        ]);
    }
    public function checkUsername(Request $request)
    {
        $username = $request->post('username');
        $user = PluginUser::where(['username' => $username])->find();
        if ($user && $user->id != $request->uid) {
            return $this->fail('用户名已存在');
        }
        return $this->success();
    }
    public function checkMobile(Request $request)
    {
        $mobile = $request->post('mobile');
        $user = PluginUser::where(['mobile' => $mobile])->find();
        if ($user) {
            if ($user->id == $request->uid) {
                return $this->fail('和原手机号一致');
            }
            return $this->fail('手机号已存在');
        }
        return $this->success();
    }
    public function checkEmail(Request $request)
    {
        $email = $request->post('email');
        $user = PluginUser::where(['email' => $email])->find();
        if ($user) {
            if ($user->id == $request->uid) {
                return $this->fail('和原邮箱一致');
            }
            return $this->fail('邮箱已存在');
        }
        return $this->success();
    }
    public function checkTwofa(Request $request)
    {
        if (!$request->twofa_state) {
            return $this->success();
        }
        $time = time() - (10 * 60);
        if ($request->twofa['time'] < $time) {
            return $this->fail('请验证你的身份', [
                'code' => ResponseCode::NEED_TWOFA,
                'action' => 'stop'
            ]);
        }
        return $this->success();
    }
    public function uploadHeadimg(Request $request)
    {
        $default_channels = config('plugin.shopwwi.filesystem.app.default');
        $UploadsClassify = UploadsClassify::where(['dir_name' => 'uploads/default', 'is_system' => 1, 'channels' => $default_channels])->find();
        if (!$UploadsClassify) {
            return $this->fail('分类不存在');
        }
        $date_path = date('Ymd');
        //单文件上传
        $file = $request->file('file');
        try {
            $result = Storage::adapter($UploadsClassify->channels)->path($UploadsClassify->dir_name . '/' . $date_path)->upload($file);
        } catch (\Throwable $th) {
            return $this->exception($th);
        }
        try {
            $Uploads = new Uploads();
            $Uploads->uid = $request->uid;
            $Uploads->classify_id = $UploadsClassify->id;
            $Uploads->filename = $result->origin_name;
            $Uploads->path = $result->file_name;
            $Uploads->ext = $result->extension;
            $Uploads->mime = $result->mime_type;
            $Uploads->size = $result->size;
            $Uploads->channels = $UploadsClassify->channels;
            $Uploads->save();
        } catch (\Throwable $th) {
            return $this->exception($th);
        }
        $user = PluginUser::where(['id' => $request->uid])->find();
        $user->headimg = $result->file_url;
        $user->save();
        return $this->code(ResponseCode::SUCCESS_EVENT_PUSH, '保存成功', [
            'event' => ResponseEvent::UPDATE_USERINFO
        ]);
    }
    public function outLogin(Request $request)
    {
        return $this->success();
    }
    /**
     * 获取两步验证密钥
     */
    public function getTwofaSecret(Request $request)
    {
        try {
            $ga = new TwofaAuthenticator();
            $secret = $ga->createSecret();
            $PluginUser = PluginUser::where(['id' => $request->uid])->find();

            // 生成 otpauth URL
            $appName = "XH";
            $accountName = $PluginUser->nickname;
            $qrCodeUrl = $ga->getQRCode($appName . ':' . $accountName, $secret, $appName);
        } catch (\Throwable $th) {
            return $this->exception($th);
        }

        return $this->resData([
            'secret' => $secret,
            'qrcode' => $qrCodeUrl
        ]);
    }

    /**
     * 验证两步验证密钥
     */
    public function verifyTwofaSecret(Request $request)
    {
        $code = $request->post('code');
        $secret = $request->post('secret');
        $totp_app = $request->post('totp_app');
        $ga = new TwofaAuthenticator();

        $isValid = $ga->verifyCode($secret, $code, 2); // 1 = 30 秒容差

        if ($isValid) {
            Db::startTrans();
            try {
                $PluginUser = PluginUser::where(['id' => $request->uid])->find();
                $PluginUser->twofa_state = State::YES['value'];
                $PluginUser->save();
                $PluginUserTwofaSecret = new PluginUserTwofaSecret();
                $PluginUserTwofaSecret->uid = $request->uid;
                $PluginUserTwofaSecret->secret = $secret;
                $PluginUserTwofaSecret->totp_app = $totp_app;
                $PluginUserTwofaSecret->is_default = PluginUserTwofaSecret::where(['uid' => $request->uid])->count() == 0 ? 1 : 0;
                $PluginUserTwofaSecret->save();
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                return $this->exception($th);
            }
            return $this->success('验证成功');
        } else {
            return $this->fail('验证码错误');
        }
    }
    /**
     * 刷新账户两步验证时效
     */
    public function refreshTwofaSecret(Request $request)
    {
        $code = $request->post('code');
        $totp_app = $request->post('totp_app');
        $ga = new TwofaAuthenticator();
        $PluginUserTwofaSecret = PluginUserTwofaSecret::where(['uid' => $request->uid, 'id' => $totp_app])->find();
        if (!$PluginUserTwofaSecret) {
            return $this->fail('两步验证密钥不存在');
        }
        $secret = $PluginUserTwofaSecret->secret;
        $isValid = $ga->verifyCode($secret, $code, 2); // 1 = 30 秒容差

        if ($isValid) {
            $user = PluginUser::where(['id' => $request->uid])->find();
            if (!$user) {
                return $this->fail('用户不存在');
            }
            $info = PluginUser::getTokenInfo($user, true);
            return $this->resData($info);
        } else {
            return $this->fail('验证码错误');
        }
    }
    public function getTwofaList(Request $request)
    {
        $PluginUserTwofaSecret = PluginUserTwofaSecret::where(['uid' => $request->uid])->order('is_default desc,id desc')->select()->scene('external')->each(function ($item) {
            $item->totp_app_text = TotpApp::get($item->totp_app);
        });
        return $this->resData($PluginUserTwofaSecret);
    }
    public function setDefaultTwofa(Request $request)
    {
        $id = $request->post('id');
        $PluginUserTwofaSecret = PluginUserTwofaSecret::where(['uid' => $request->uid, 'id' => $id])->find();
        if (!$PluginUserTwofaSecret) {
            return $this->fail('两步验证密钥不存在');
        }
        Db::startTrans();
        try {
            PluginUserTwofaSecret::where(['uid' => $request->uid])->update(['is_default' => 0]);
            $PluginUserTwofaSecret->is_default = 1;
            $PluginUserTwofaSecret->save();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->exception($th);
        }
        return $this->success('设置成功');
    }
    public function deleteTwofa(Request $request)
    {
        $id = $request->post('id');
        $PluginUserTwofaSecret = PluginUserTwofaSecret::where(['uid' => $request->uid, 'id' => $id])->find();
        if (!$PluginUserTwofaSecret) {
            return $this->fail('两步验证密钥不存在');
        }
        Db::startTrans();
        try {
            // 判断是否还有其它两步验证密钥
            $PluginUserTwofaSecretOther = PluginUserTwofaSecret::where(['uid' => $request->uid])->where('id', '<>', $id)->order('id desc')->find();
            if ($PluginUserTwofaSecretOther) {
                if ($PluginUserTwofaSecret->is_default == 1) {
                    $PluginUserTwofaSecretOther->is_default = 1;
                    $PluginUserTwofaSecretOther->save();
                }
            } else {
                $PluginUser = PluginUser::where(['id' => $request->uid])->find();
                $PluginUser->twofa_state = State::NO['value'];
                $PluginUser->save();
            }
            $PluginUserTwofaSecret->delete();
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return $this->exception($th);
        }
        return $this->success('删除成功');
    }
    /**
     * 获取用户未使用的邀请码
     * @author:1950781041@qq.com 
     * @Date:2026-01-07
     */
    public function getUnusedInvitationCode(Request $request)
    {
        $PluginUserInvitationCode = PluginUserInvitationCode::where(['uid' => $request->uid])
            ->with(['useUser'])
            ->where('channels_uid', $request->channels_uid)
            ->where('state', State::YES['value'])
            ->select();
        return $this->resData($PluginUserInvitationCode);
    }
    /**
     * 用户绑定邀请码
     * @author:1950781041@qq.com 
     * @Date:2026-01-15
     */
    public function bindInvitationCode(Request $request)
    {
        $code = $request->post('code');
        $PluginUserInvitationCode = PluginUserInvitationCode::where(['code' => $code])->find();
        if (!$PluginUserInvitationCode) {
            return $this->fail('邀请码不存在');
        }
        if ($PluginUserInvitationCode->uid == $request->uid) {
            return $this->fail('不能邀请自己');
        }
        if (!empty($PluginUserInvitationCode->use_uid)) {
            return $this->fail('邀请码已使用');
        }
        if ($PluginUserInvitationCode->status != 'unused') {
            return $this->fail('邀请码已使用');
        }
        if ($PluginUserInvitationCode->state != State::YES['value']) {
            return $this->fail('邀请码已失效');
        }
        $PluginUserInvitationCode->status = 'used';
        $PluginUserInvitationCode->use_uid = $request->uid;
        $PluginUserInvitationCode->use_time = date('Y-m-d H:i:s');
        $PluginUserInvitationCode->save();
        $PluginUser = PluginUser::where(['id' => $request->uid])->find();
        $PluginUser->activation_time = date('Y-m-d H:i:s');
        $PluginUser->puid = $PluginUserInvitationCode->uid;
        $PluginUser->save();
        return $this->success('绑定成功');
    }
    /**
     * 用户绑定电话
     * @author:1950781041@qq.com 
     * @Date:2026-01-15
     */
    public function bindMobile(Request $request)
    {
        $mobile = $request->post('username');
        $username = $request->post('username');
        $vcode = $request->post('vcode');
        $token = $request->post('token');
        if (empty($username) || empty($vcode) || empty($token)) {
            return $this->fail('参数错误');
        }
        Vcode::check($username, $vcode, VcodeScene::BIND_MOBILE['value'], $token);
        $PluginUser = PluginUser::where(['mobile' => $mobile, 'channels_uid' => $request->channels_uid])->find();
        if ($PluginUser) {
            PluginUser::where(['id' => $request->uid])->update(['username' => '']);
            $wechat = PluginUserWechat::where(['uid' => $request->uid])->find();
            $wechat->uid = $PluginUser->id;
            $wechat->save();
            return $this->resData(PluginUser::getTokenInfo($PluginUser, true));
        }
        $PluginUser = PluginUser::where(['id' => $request->uid])->find();
        if ($PluginUser->mobile) {
            return $this->fail('电话已绑定');
        }
        $PluginUser->mobile = $mobile;
        $PluginUser->save();
        return $this->resData(PluginUser::getTokenInfo($PluginUser, true));
    }

    /**
     * 修改密码
     * @author:1950781041@qq.com 
     * @Date:2026-01-17
     */
    public function setPassword(Request $request)
    {
        $password = $request->post('password');
        $token = $request->post('token');
        $vcode = $request->post('vcode');
        $username = $request->post('username');
        if (empty($password) || empty($token) || empty($vcode) || empty($username)) {
            return $this->fail('参数错误');
        }
        Vcode::check($username, $vcode, VcodeScene::BIND_MOBILE['value'], $token);
        $PluginUser = PluginUser::where(['id' => $request->uid])->find();
        $PluginUser->password = $password;
        $PluginUser->save();
        return $this->success('修改成功');
    }

    /**
     * 验证邀请码是否被使用
     * @author:1950781041@qq.com 
     * @Date:2026-01-26
     */
    public function checkInvitationCode(Request $request)
    {
        $code = $request->post('code');
        $PluginUserInvitationCode = PluginUserInvitationCode::where(['code' => $code])->find();
        if (!$PluginUserInvitationCode) {
            return $this->fail('邀请码不存在');
        }
        if ($PluginUserInvitationCode->uid == $request->uid) {
            return $this->fail('不能邀请自己');
        }
        if (!empty($PluginUserInvitationCode->use_uid)) {
            return $this->fail('邀请码已使用');
        }
        if ($PluginUserInvitationCode->status != 'unused') {
            return $this->fail('邀请码已使用');
        }
        return $this->success();
    }
}
