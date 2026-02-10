<?php

namespace plugin\finance\app\control\controller;

use app\Basic;
use app\expose\build\builder\ActionBuilder;
use app\expose\build\builder\ComponentBuilder;
use app\expose\build\builder\FormBuilder;
use app\expose\build\builder\TableBuilder;
use app\expose\enum\Action;
use app\expose\enum\State;
use app\expose\helper\Uploads;
use plugin\finance\app\model\PluginFinanceWallet;
use plugin\finance\expose\helper\Account;
use plugin\finance\utils\enum\BillScene;
use plugin\finance\utils\enum\PointsBillScene;
use plugin\finance\utils\enum\ValidityPeriod;
use plugin\user\app\model\PluginUserPoints;
use plugin\user\utils\enum\MoneyAction;
use support\Request;

class WalletController extends Basic
{
    public function __construct()
    {
        $this->model = new PluginFinanceWallet;
    }
    public function indexGetTable(Request $request)
    {
        $builder = new TableBuilder;
        $builder->addAction('操作', [
            'width' => '180px',
            'fixed' => 'right'
        ]);
        $builder->addTableAction('余额', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/finance/control/Wallet/recharge',
            'props' => [
                'title' => '余额操作《{nickname}》'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'primary',
                    'size' => 'small'
                ]
            ]
        ]);
        $builder->addTableAction('积分', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/finance/control/Wallet/rechargePoints',
            'props' => [
                'title' => '积分操作《{nickname}》'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'info',
                    'size' => 'small'
                ]
            ]
        ]);
        $builder->addTableAction('临时积分', [
            'model' => Action::DIALOG['value'],
            'path' => '/app/finance/control/Wallet/rechargeTmpPoints',
            'props' => [
                'title' => '临时积分操作《{nickname}》'
            ],
            'component' => [
                'name' => 'button',
                'props' => [
                    'type' => 'warning',
                    'size' => 'small'
                ]
            ]
        ]);
        $formBuilder = new FormBuilder(null, null, [
            'inline' => true
        ]);

        $formBuilder->add('uid', 'UID', 'input', '', [
            'props' => [
                'placeholder' => 'UID搜索',
                'clearable' => true
            ]
        ]);
        $formBuilder->add('mobile', '手机号', 'input', '', [
            'props' => [
                'placeholder' => '手机号搜索',
                'clearable' => true
            ]
        ]);
        $formBuilder->add('nickname', '昵称', 'input', '', [
            'props' => [
                'placeholder' => '昵称搜索',
                'clearable' => true
            ]
        ]);
        $builder->addScreen($formBuilder);
        $builder->add('id', 'ID', [
            'props' => [
                'width' => '100px'
            ]
        ]);
        $builder->add('user', '用户', [
            'where' => [
                ['uid', '!=', null]
            ],
            'component' => [
                'name' => 'table-userinfo',
                'props' => [
                    'nickname' => 'nickname',
                    'avatar' => 'headimg',
                    'info' => 'mobile',
                    'tags' => [
                        [
                            'field' => 'uid',
                            'props' => [
                                'type' => 'success',
                                'size' => 'small'
                            ]
                        ]
                    ],
                ]
            ],
            'props' => [
                'width' => '240px'
            ]
        ]);
        $builder->add('points', '可用积分', [
            'props' => [
                'minWidth' => '100px'
            ]
        ]);
        $builder->add('points_sum', '累计积分', [
            'props' => [
                'minWidth' => '100px'
            ]
        ]);
        $builder->add('points_used', '累计支出积分', [
            'props' => [
                'minWidth' => '100px'
            ]
        ]);
        $builder->add('tmp_points', '临时积分', [
            'props' => [
                'minWidth' => '100px'
            ]
        ]);
        $builder->add('tmp_points_sum', '累计临时积分', [
            'props' => [
                'minWidth' => '100px'
            ]
        ]);
        $builder->add('tmp_points_used', '累计支出临时积分', [
            'props' => [
                'minWidth' => '100px'
            ]
        ]);
        $builder->add('balance', '可用余额', [
            'props' => [
                'minWidth' => '100px'
            ]
        ]);
        $builder->add('balance_sum', '累计余额', [
            'props' => [
                'minWidth' => '100px'
            ]
        ]);
        $builder->add('balance_used', '累计支出余额', [
            'props' => [
                'minWidth' => '100px'
            ]
        ]);
        $builder = $builder->builder();
        return $this->resData($builder);
    }
    public function index(Request $request)
    {
        $limit = $request->get('limit', 10);
        $where = [];
        $where[] = ['w.channels_uid', '=', $request->channels_uid];
        $nickname = $request->get('nickname');
        if ($nickname) {
            $where[] = ['u.nickname', 'like', "%{$nickname}%"];
        }
        $mobile = $request->get('mobile');
        if ($mobile) {
            $where[] = ['u.mobile', '=', $mobile];
        }
        $uid = $request->get('uid');
        if ($uid) {
            $where[] = ['w.uid', '=', $uid];
        }
        $list = PluginFinanceWallet::alias('w')
            ->join('plugin_user u', ' u.id=w.uid ')
            ->field('w.*,u.id,u.nickname,u.headimg,u.mobile')
            ->where($where)
            ->order('w.uid desc')
            ->paginate($limit)
            ->each(function ($item) {
                $item->headimg = Uploads::url($item->headimg);
            });
        return $this->resData($list);
    }


    public function recharge(Request $request)
    {
        if ($request->method() == 'POST') {
            $data = $request->post();
            Account::updateAccount($data['id'], $request->channels_uid, $data['number'], $data['action'],  BillScene::ADMIN['value'], 0, $data['remarks'], $data['is_accumulate'] == 1);
            return $this->success('操作成功');
        }
        $builder = new FormBuilder();

        $builder->add('action', '操作', 'radio', MoneyAction::INCREASE['value'], [
            'options' => MoneyAction::getOptions()
        ]);
        $Component = new ComponentBuilder;
        $builder->add('is_accumulate', '是否计入累计', 'switch',  State::NO['value'], [
            'prompt' => [
                $Component->add('text', ['default' => '是否计入累计，如退款时，不计入累计'], ['type' => 'info', 'size' => 'small'])->builder()
            ],
            'props' => [
                'active-value' => State::YES['value'],
                'inactive-value' => State::NO['value']
            ]
        ]);
        $builder->add('number', '操作数量', 'input-number', 0, [
            'required' => true,
            'props' => [
                'controls' => false,
                'min' => 1,
                'step' => 1,
                'style' => [
                    'width' => '200px'
                ]
            ]
        ]);
        $builder->add('remarks', '备注', 'input', '', [
            'required' => true,
        ]);
        return $this->resData($builder);
    }

    public function rechargePoints(Request $request)
    {
        if ($request->method() == 'POST') {
            $data = $request->post();
            Account::updatePermanentPoints($data['id'], $request->channels_uid, $data['number'], PointsBillScene::ADMIN['value'], 0, $data['action'], $data['remarks'], $data['is_accumulate']);
            return $this->success('操作成功');
        }
        $builder = new FormBuilder();
        $builder->add('action', '操作', 'radio', MoneyAction::INCREASE['value'], [
            'options' => MoneyAction::getOptions()
        ]);

        $Component = new ComponentBuilder;
        $builder->add('is_accumulate', '是否计入累计', 'switch',  State::NO['value'], [
            'prompt' => [
                $Component->add('text', ['default' => '是否计入累计，如退款时，不计入累计消费'], ['type' => 'info', 'size' => 'small'])->builder()
            ],
            'props' => [
                'active-value' => State::YES['value'],
                'inactive-value' => State::NO['value']

            ]
        ]);
        $builder->add('number', '操作数量', 'input-number', 0, [
            'required' => true,
            'props' => [
                'controls' => false,
                'min' => 1,
                'step' => 1,
                'style' => [
                    'width' => '200px'
                ]
            ]
        ]);
        $builder->add('remarks', '备注', 'input', '', [
            'required' => true,
        ]);
        return $this->resData($builder);
    }
    public function rechargeTmpPoints(Request $request)
    {
        if ($request->method() == 'POST') {
            $data = $request->post();

            if ($data['action'] == MoneyAction::INCREASE['value']) {
                Account::incPoints($data['id'], $request->channels_uid, $data['number'], PointsBillScene::ADMIN['value'], 0,  $data['remarks'], $data['is_accumulate'], $data['valid_time']);
            } else {
                Account::decTemporaryPoints($data['id'], $request->channels_uid, $data['number'], PointsBillScene::ADMIN['value'], 0, $data['action'], $data['remarks'], $data['is_accumulate'], $data['point_id']);
            }
            return $this->success('操作成功');
        }
        $list = PluginUserPoints::field('id as value,points as label, valid_time as tips')
            ->where('channels_uid', $request->channels_uid)
            ->where('uid', $request->get('id'))
            ->where('points', '>', 0)
            ->where('valid_time', '>=', date('Y-m-d H:i:s'))
            ->where('extended_time', '>=', date('Y-m-d H:i:s'))
            ->order('extended_time ASC, valid_time ASC')
            ->select();
        $list = $list->map(function ($item) {
            return [
                'value' => $item['value'],
                'label' =>  '剩余积分' . $item['label'],
                'tips' => '有效期至：' . $item['tips']
            ];
        });
        $builder = new FormBuilder();
        $builder->add('action', '操作', 'radio', MoneyAction::INCREASE['value'], [
            'options' => MoneyAction::getOptions()
        ]);

        $builder->add('point_id', '选择扣除对象', 'select', null, [
            'options' => $list,
            'required' => true,
            'where' => [
                ['action', '=', MoneyAction::DECREASE['value']]
            ]
        ]);
        $Component = new ComponentBuilder;
        $builder->add('is_accumulate', '是否计入累计', 'switch',  State::NO['value'], [
            'prompt' => [
                $Component->add('text', ['default' => '是否计入累计，如退款时，不计入累计消费'], ['type' => 'info', 'size' => 'small'])->builder()
            ],
            'props' => [
                'active-value' => State::YES['value'],
                'inactive-value' => State::NO['value']
            ]
        ]);
        $builder->add('number', '操作数量', 'input-number', 0, [
            'required' => true,
            'props' => [
                'controls' => false,
                'min' => 1,
                'step' => 1,
                'style' => [
                    'width' => '200px'
                ]
            ]
        ]);
        $builder->add('valid_time', '有效期', 'date-picker', null, [
            'required' => true,
            'where' => [
                ['action', '=', MoneyAction::INCREASE['value']]
            ]
        ]);
        $builder->add('remarks', '备注', 'input', '', [
            'required' => true,
        ]);
        return $this->resData($builder);
    }
}
