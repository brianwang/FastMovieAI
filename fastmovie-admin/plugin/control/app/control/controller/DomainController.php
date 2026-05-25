<?php

namespace plugin\control\app\control\controller;

use app\Basic;
use app\expose\build\builder\ComponentBuilder;
use app\expose\build\builder\FormBuilder;
use app\expose\build\builder\TableBuilder;
use app\expose\enum\Action;
use app\expose\enum\State;
use app\expose\utils\Rsa;
use app\expose\utils\Str;
use GuzzleHttp\Client;
use plugin\control\app\model\PluginChannelsDomain;
use support\Redis;
use support\Request;

class DomainController extends Basic
{
    protected $notNeedLogin = ['downloadFile'];
    public function __construct()
    {
        $this->model = new PluginChannelsDomain();
    }
    public function indexGetTable(Request $request)
    {
        $builder = new TableBuilder;
        $builder->addAction('操作', [
            'width' => '100px',
            'fixed' => 'right'
        ]);
        $builder->addTableAction('删除', [
            'model' => Action::COMFIRM['value'],
            'path' => '/app/control/control/Domain/delete',
            'props' => [
                'message' => '确定要删除《{domain}》域名吗？'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'danger',
                    'size' => 'small'
                ]
            ]
        ]);
        $builder->addHeader();
        $builder->addHeaderAction('绑定域名', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/control/control/Domain/create',
            'props' => [
                'title' => '绑定域名'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'success'
                ]
            ]
        ]);
        $formBuilder = new FormBuilder(null, null, [
            'inline' => true
        ]);
        $formBuilder->add('domain', '域名', 'input', '', [
            'props' => [
                'placeholder' => '域名搜索',
                'clearable' => true
            ]
        ]);
        $builder->addScreen($formBuilder);
        $builder->add('id', 'ID', [
            'props' => [
                'width' => '100px'
            ]
        ]);
        $builder->add('domain', '域名', [
            'props' => [
                'minWidth' => '200px'
            ]
        ]);
        $builder->add('state', '状态', [
            'component' => [
                'name' => 'switch',
                'api' => '/app/control/control/Domain/indexUpdateState',
                'props' => [
                    'active-value' => State::YES['value'],
                    'inactive-value' => State::NO['value']
                ]
            ],
            'props' => [
                'width' => '100px'
            ]
        ]);
        $builder->add('remarks', '备注', [
            'props' => [
                'width' => '300px'
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
        $domain = $request->get('domain');
        if ($domain) {
            $where[] = ['domain', 'like', "%{$domain}%"];
        }
        $where[] = ['channels_uid', '=', $request->channels_uid];
        $list = PluginChannelsDomain::where($where)
            ->order('id desc')->paginate($limit)->each(function ($item) {});
        return $this->resData($list);
    }
    /**
     * 更新状态字段
     * @method POST
     */
    public function indexUpdateState(Request $request)
    {
        $id = $request->post('id');
        $field = $request->post('field');
        $value = $request->post('value');
        if (!in_array($field, $this->allowedStateFields)) {
            return $this->fail('不允许更新该字段');
        }
        $model = $this->model->where(['id' => $id])->find();
        if (!$model) {
            return $this->fail('数据不存在');
        }
        $model->{$field} = $value;
        if ($model->save()) {
            if ($field == 'state' && $value == State::YES['value']) {
                Redis::hSet('domain_map', $model->domain, $model->channels_uid);
            } else if ($field == 'state' && $value == State::NO['value']) {
                Redis::hDel('domain_map', $model->domain);
            }
            return $this->success();
        }
        return $this->fail('操作失败');
    }
    public function create(Request $request)
    {
        $file_name = md5('domain-verify-' . $request->channels_uid) . '.txt';
        $file = runtime_path('temp/') . $file_name;
        if ($request->method() === 'POST') {
            $data = $request->post();
            try {
                $content = file_get_contents($file);
                if ($data['verify_type'] === 'file') {
                    // SSRF 防护：验证域名解析到公网 IP
                    $dns = dns_get_record($data['domain'], DNS_A);
                    $privateRanges = ['10.', '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.', '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.', '192.168.', '127.'];
                    foreach ($dns as $record) {
                        if (isset($record['ip'])) {
                            foreach ($privateRanges as $prefix) {
                                if (str_starts_with($record['ip'], $prefix)) {
                                    throw new \Exception('域名指向内网地址，不允许验证');
                                }
                            }
                        }
                    }
                    $remote_content = @file_get_contents('http://' . $data['domain'] . '/' . $file_name);
                    if ($content !== $remote_content) {
                        throw new \Exception('验证失败');
                    }
                } else {
                    // 获取域名解析记录
                    $this->verifyDomainRecord($file_name . '.' . $data['domain'],  $content);
                }
            } catch (\Throwable $th) {
                return $this->fail('验证失败');
            }
            try {
                $insterData = [];
                if (empty($data['domain'])) {
                    throw new \Exception('请填写域名');
                }
                if (!empty($data['domain'])) {
                    $Find = PluginChannelsDomain::where(['domain' => $data['domain']])->find();
                    if ($Find) {
                        throw new \Exception('域名已存在');
                    }
                    $insterData['domain'] = $data['domain'];
                }
                if (!empty($data['remarks'])) {
                    $insterData['remarks'] = $data['remarks'];
                }
                $insterData['channels_uid'] = $request->channels_uid;
                $insterData['state'] = State::YES['value'];
                $model = new PluginChannelsDomain();
                $model->save($insterData);
                Redis::hSet('domain_map', $model->domain, $model->channels_uid);
            } catch (\Throwable $th) {
                return $this->exception($th);
            }
            return $this->success('创建成功');
        }
        $builder = new FormBuilder(null, null, [
            'labelPosition' => 'right',
            'label-width' => "200px",
            'class' => 'w-80 mx-auto',
            'size' => 'large',
        ]);
        $Component = new ComponentBuilder;
        $builder->add('verify_type', '验证方式', 'radio', 'file', [
            'required' => true,
            'options' => [
                [
                    'label' => '文件',
                    'value' => 'file'
                ],
                [
                    'label' => 'DNS',
                    'value' => 'dns'
                ]
            ],
            'subProps' => [
                'border' => true
            ]
        ]);
        if (!is_dir(runtime_path('temp'))) {
            mkdir(runtime_path('temp'), 0755, true);
        }
        if (!file_exists($file)) {
            $content = Str::random(32);
            file_put_contents($file, $content);
        } else {
            $content = file_get_contents($file);
        }
        $builder->add('domain', '域名(HOST)', 'input', '', [
            'required' => true,
            'prompt' => [
                $Component->add('link', ['default' => '点击下载验证文件'], ['href' => '/app/control/control/Domain/downloadFile?file_name=' . $file_name, 'type' => 'primary', 'size' => 'small', 'target' => '_blank', 'underline' => 'never'])
                    ->add('text', ['default' => "文件验证：将文件 {$file_name} 放置域名根目录下，确保域名+文件名能访问到文件内容：{$content}"], ['type' => 'info', 'size' => 'small'])
                    ->add('text', ['default' => "DNS验证：添加一条域名解析记录，记录类型为TXT，记录值为：{$content}"], ['type' => 'info', 'size' => 'small'])
                    ->builder()
            ],
            'props' => [
                'placeholder' => '请输入不带协议的域名(HOST)，如：www.example.com',
                'clearable' => true
            ]
        ]);
        $builder->add('remarks', '备注', 'input', '', [
            'props' => [
                'clearable' => true
            ]
        ]);
        return $this->resData($builder);
    }
    public function downloadFile(Request $request)
    {
        $file_name = $request->get('file_name');
        $file_name = basename($file_name);
        $file = runtime_path('temp/') . $file_name;
        if (!file_exists($file)) {
            return $this->fail('文件不存在');
        }
        $real = realpath($file);
        if ($real && !str_starts_with($real, realpath(runtime_path('temp/')))) {
            return $this->fail('文件不存在');
        }
        return response()->download($file, $file_name);
    }
    public function verifyDomainRecord($domain, $content)
    {
        $res = dns_get_record($domain, DNS_ALL);
        foreach ($res as $key => $value) {
            if ($value['type'] === 'TXT') {
                if ($value['txt'] === $content) {
                    return true;
                }
            }
        }
        throw new \Exception('验证失败');
    }
    public function delete(Request $request)
    {
        $id = $request->post('id');
        $model = $this->model->where(['id' => $id, 'channels_uid' => $request->channels_uid])->find();
        if (!$model) {
            return $this->fail('数据不存在');
        }
        if (!$model->delete()) {
            return $this->fail('删除失败');
        }
        Redis::hDel('domain_map', $model->domain);
        return $this->success('删除成功');
    }
}
