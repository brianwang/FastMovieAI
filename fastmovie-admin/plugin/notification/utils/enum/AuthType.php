<?php

namespace plugin\notification\utils\enum;

use app\expose\enum\builder\Enum;

class AuthType extends Enum
{
    // notify|orders|continueepisode|generatescenestoryboard|generatesceneimage|generatedramacover|generateactorimage|generateactorthreeviewimage|generatestoryboardimage|generatestoryboard|generatecharacterlookcostume
    const USER = [
        'label' => '用户',
        'value' => 'user',
        'action' => 'user',
    ];
    const NOTIFY = [
        'label' => '通知',
        'value' => 'notify',
        'action' => 'user',
    ];
    const ORDERS = [
        'label' => '订单',
        'value' => 'orders',
        'action' => 'orders',
    ];
    const CONTINUE_EPISODE = [
        'label' => '续写分集',
        'value' => 'continueepisode',
        'action' => 'drama',
    ];
    const GENERATE_SCENE_STORYBOARD = [
        'label' => '创作分镜',
        'value' => 'generatescenestoryboard',
        'action' => 'drama',
    ];
    const GENERATE_DRAMA_COVER = [
        'label' => '创作短剧封面',
        'value' => 'generatedramacover',
        'action' => 'drama',
    ];
    const GENERATE_SCENE_IMAGE = [
        'label' => '创作场景',
        'value' => 'generatesceneimage',
        'action' => 'user',
    ];
    const GENERATE_ACTOR_IMAGE = [
        'label' => '创作角色形象',
        'value' => 'generateactorimage',
        'action' => 'user',
    ];
    const GENERATE_ACTOR_THREE_VIEW_IMAGE = [
        'label' => '创作角色三视图',
        'value' => 'generateactorthreeviewimage',
        'action' => 'user',
    ];
    const GENERATE_STORYBOARD_IMAGE = [
        'label' => '创作分镜图片',
        'value' => 'generatestoryboardimage',
        'action' => 'user',
    ];
    const GENERATE_STORYBOARD = [
        'label' => '创作分镜',
        'value' => 'generatestoryboard',
        'action' => 'user',
    ];
    const GENERATE_CHARACTER_LOOK_COSTUME = [
        'label' => '创作角色服饰',
        'value' => 'generatecharacterlookcostume',
        'action' => 'user',
    ];
    const GENERATE_PROP_IMAGE = [
        'label' => '创作物品图片',
        'value' => 'generatepropimage',
        'action' => 'user',
    ];
    const GENERATE_PROP_THREE_VIEW_IMAGE = [
        'label' => '创作物品三视图',
        'value' => 'generatepropthreeviewimage',
        'action' => 'user',
    ];
    const CREATE_DRAMA = [
        'label' => '创建短剧',
        'value' => 'generatecreatedrama',
        'action' => 'user',
    ];
    const CLONE_VOICE = [
        'label' => '克隆音色',
        'value' => 'clonevoice',
        'action' => 'user',
    ];
}
