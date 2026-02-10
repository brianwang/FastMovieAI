<?php

namespace plugin\model\app\api\controller;

use app\Basic;
use app\expose\enum\State;
use plugin\model\app\model\PluginModel;
use plugin\model\utils\enum\ModelPointType;
use plugin\shortplay\utils\PricingCalculator;

class ModelController extends Basic
{
    protected $notNeedLoginAll = ['models'];
    public function models()
    {
        $PluginModel = PluginModel::where(['state' => State::YES['value']])->order('sort asc')->field('id,channels_uid,icon,name,point,point_type,scene,description,form')->select()->each(function ($item) {
            $item->unit_point = $item->point;
            if ($item->point_type == ModelPointType::FIELD_RULE['value']) {
                $formData = [
                    'resolution' => '720P',
                    'duration' => 5,
                ];
                $calculator = new PricingCalculator($item->form, $formData, $item->point);
                $item->point = $calculator->calculate() / 5;
            }
        });
        $models = [];
        foreach ($PluginModel as $model) {
            $models[$model->scene][] = [
                'id' => $model->id,
                'icon' => $model->icon,
                'name' => $model->name,
                'point' => $model->point,
                'scene' => $model->scene,
                'description' => $model->description,
                'form' => $model->form,
                'unit_point' => $model->unit_point,
                'unit' => ModelPointType::get($model->point_type, 'unit')
            ];
        }
        return $this->resData($models);
    }
}
