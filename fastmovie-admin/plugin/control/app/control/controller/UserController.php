<?php

namespace plugin\control\app\control\controller;

use app\Basic;
use app\expose\build\builder\FormBuilder;
use app\expose\build\builder\TableBuilder;
use app\expose\enum\Action;
use app\expose\enum\ResponseEvent;
use app\expose\enum\State;
use app\expose\utils\Password;
use app\validate\User as ValidateUser;
use loong\oauth\facade\Auth;
use plugin\control\app\model\PluginChannelsRole;
use plugin\control\app\model\PluginChannelsUser;
use support\Request;

class UserController extends Basic
{
    public function indexGetTable(Request $request)
    {
        $builder = new TableBuilder();
        $builder->addAction('操作', [
            'width' => '200px',
            'fixed' => 'right'
        ]);
        $builder->addTableAction('编辑', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/control/control/User/updateUser',
            'props' => [
                'title' => '编辑《{id}》账号'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'primary',
                    'size' => 'small'
                ]
            ]
        ]);
        $builder->addHeader();
        $builder->addHeaderAction('创建账号', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/control/control/User/create',
            'props' => [
                'title' => '创建账号'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'success'
                ]
            ]
        ]);
        $builder->add('id', 'ID', [
            'props' => [
                'width' => '80px'
            ]
        ]);
        $builder->add('userinfo', '用户', [
            'component' => [
                'name' => 'table-userinfo',
                'props' => [
                    'nickname' => 'nickname',
                    'avatar' => 'headimg',
                    'info' => 'username',
                    'nicknameTags' => [
                        [
                            'field' => 'new_text',
                            'props' => [
                                'type' => 'success',
                                'size' => 'small'
                            ]
                        ],
                        [
                            'field' => 'week_text',
                            'props' => [
                                'type' => 'warning',
                                'size' => 'small'
                            ]
                        ],
                        [
                            'field' => 'month_text',
                            'props' => [
                                'type' => 'info',
                                'size' => 'small'
                            ]
                        ]
                    ]
                ]
            ],
            'props' => [
                'minWidth' => '300px'
            ]
        ]);
        $builder->add('contact', '联系方式', [
            'props' => [
                'width' => '280px'
            ],
            'component' => [
                'name' => 'table-times',
                'props' => [
                    'group' => [
                        [
                            'field' => 'mobile',
                            'label' => '手机号'
                        ],
                        [
                            'field' => 'email',
                            'label' => '邮箱'
                        ]
                    ]
                ]
            ]
        ]);
        $builder->add('login_ip', '登录IP', [
            'props' => [
                'width' => '130px'
            ]
        ]);
        $builder->add('role.name', '角色', [
            'props' => [
                'width' => '100px'
            ]
        ]);
        $builder->add('role.state', '状态', [
            'props' => [
                'width' => '100px'
            ]
        ]);
        $builder->add('sort', '排序（小到大）', [
            'props' => [
                'width' => '130px'
            ]
        ]);
        $builder->add('create_time', '时间', [
            'props' => [
                'width' => '200px'
            ],
            'component' => [
                'name' => 'table-times',
                'props' => [
                    'group' => [
                        [
                            'field' => 'create_time',
                            'label' => '创建'
                        ],
                        [
                            'field' => 'update_time',
                            'label' => '更新'
                        ]
                    ]
                ]
            ]
        ]);
        $builder = $builder->builder();
        return $this->resData($builder);
    }

    public function index(Request $request)
    {

        $limit = $request->get('limit', 10);
        $where = [];
        $where[] = ['channels_uid', '=', $request->channels_uid];
        $list = PluginChannelsUser::with('role')->where($where)->paginate($limit);
        return $this->resData($list);
    }

    public function create(Request $request)
    {
        if ($request->method() === 'POST') {
            $D = $request->post();
            if (empty($D['password'])) {
                return $this->fail('密码不能为空');
            }
            try {
                $User = new PluginChannelsUser();
                $User->channels_uid = $request->channels_uid;
                $User->role_id = $D['role_id'] ?? 0;
                $User->username = $D['username'] ?? '';
                $User->nickname = $D['nickname'] ?? '';
                $User->headimg = $D['headimg'] ?? '';
                $User->mobile = $D['mobile'] ?? '';
                $User->email = $D['email'] ?? '';
                $User->password = $D['password'];
                $User->save();
            } catch (\Throwable $th) {
                return $this->exception($th);
            }
            return $this->success('创建成功');
        }
        $builder = $this->getForm($request->channels_uid);
        return $this->resData($builder);
    }
    public function updateUser(Request $request)
    {
        if ($request->method() === 'POST') {
            $D = $request->post();
            $User = PluginChannelsUser::where(['id' => $D['id']])->find();
            if (!$User) {
                return $this->fail('用户不存在');
            }
            if (empty($D['password'])) {
                unset($D['password']);
            } else {
                $password = $D['password'];
            }
            $User->role_id = $D['role_id'];
            $User->username = $D['username'];
            $User->nickname = $D['nickname'];
            $User->headimg = $D['headimg'];
            $User->mobile = $D['mobile'];
            $User->email = $D['email'];
            $User->password = $password;
            if ($User->save()) {
                return $this->success('更新成功');
            }
            return $this->fail('更新失败');
        }
        $id = $request->get('id');
        $User = PluginChannelsUser::where(['id' => $id])->find();
        if (!$User) {
            return $this->fail('用户不存在');
        }
        $builder = $this->getForm($request->channels_uid);
        unset($User->password);
        $builder->setData($User->toArray());
        return $this->resData($builder);
    }

    private function getForm($uid)
    {
        $list = PluginChannelsRole::field('id as value,name as label')->where('channels_uid', $uid)->where('state', State::YES['value'])->select();
        $builder = new FormBuilder;
        $builder->add('role_id', '所属角色', 'select', null, [
            'options' => $list
        ]);
        $builder->add('username', '账号', 'input', '', [
            'props' => [
                'maxlength' => 30,
                'show-word-limit' => true
            ]
        ]);
        $builder->add('password', '密码', 'input', '', [
            'props' => [
                'placeholder' => '不修改密码请留空',
                'maxlength' => 30,
                'show-word-limit' => true
            ]
        ]);
        $builder->add('nickname', '昵称', 'input', '', [
            'required' => true,
            'maxlength' => 30,
            'show-word-limit' => true
        ]);
        $builder->add('headimg', '头像', 'bundle', '', [
            'props' => [
                'accept' => 'image/*',
                'multiple' => 1
            ]
        ]);
        $builder->add('mobile', '手机号', 'input', '', [
            'props' => [
                'maxlength' => 11,
                'show-word-limit' => true
            ]
        ]);
        $builder->add('email', '邮箱', 'input', '', [
            'props' => [
                'maxlength' => 50,
                'show-word-limit' => true
            ]
        ]);
        return $builder;
    }









    public function update(Request $request)
    {
        $id = $request->channels_uid;
        if ($request->method() === 'POST') {
            $D = $request->post();
            $D['id'] = $id;
            try {
                $validate = new ValidateUser;
                $validate->scene('self')->check($D);
            } catch (\Throwable $th) {
                return $this->exception($th);
            }
            $User = PluginChannelsUser::where(['id' => $D['id']])->find();
            if (!$User) {
                return $this->fail('用户不存在');
            }
            if (!$User->username) {
                $User->username = $D['username'];
            }
            $User->nickname = $D['nickname'];
            $User->headimg = $D['headimg'];
            $User->mobile = $D['mobile'];
            $User->email = $D['email'];
            if ($D['password']) {
                $User->password = $D['password'];
            }
            if ($User->save()) {
                return $this->event(ResponseEvent::UPDATE_USERINFO, '保存成功');
            }
            return $this->fail('保存失败');
        }
        $User = PluginChannelsUser::where(['id' => $id])->withoutField('password')->find();
        if (!$User) {
            return $this->fail('用户不存在');
        }
        $builder = new FormBuilder();
        $builder->add('username', '账号', 'input', '', [
            'props' => [
                'maxlength' => 30,
                'show-word-limit' => true,
                'disabled' => $User->username ? true : false
            ]
        ]);
        $builder->add('password', '密码', 'input', '', [
            'props' => [
                'placeholder' => '不修改密码请留空',
                'maxlength' => 30,
                'show-word-limit' => true
            ]
        ]);
        $builder->add('nickname', '昵称', 'input', '', [
            'required' => true,
            'maxlength' => 30,
            'show-word-limit' => true
        ]);
        $builder->add('headimg', '头像', 'bundle', '', [
            'props' => [
                'accept' => 'image/*',
                'multiple' => 1
            ]
        ]);
        $builder->add('mobile', '手机号', 'input', '', [
            'props' => [
                'maxlength' => 11,
                'show-word-limit' => true
            ]
        ]);
        $builder->add('email', '邮箱', 'input', '', [
            'props' => [
                'maxlength' => 50,
                'show-word-limit' => true
            ]
        ]);
        $builder->setData($User->toArray());
        return $this->resData($builder);
    }





    public function getInfo(Request $request)
    {
        $User = PluginChannelsUser::where(['id' => $request->uid])->withoutField('password')->find();
        return $this->resData(PluginChannelsUser::getTokenInfo($User));
    }
    public function refresh()
    {
        return $this->event(ResponseEvent::UPDATE_USERINFO, '刷新成功');
    }
    public function lock(Request $request)
    {
        try {
            $password = $request->post('password');
            if (!$password) {
                return $this->fail('PIN码不能为空');
            }
            if (mb_strlen($password) != 6) {
                return $this->fail('请输入6位PIN码');
            }
            $token = $request->header('Authorization');
            Auth::setPrefix('CONTROL')->lock($token, $password);
            return $this->event(ResponseEvent::UPDATE_USERINFO, '锁定成功');
        } catch (\Throwable $th) {
            return $this->exception($th);
        }
    }
}
