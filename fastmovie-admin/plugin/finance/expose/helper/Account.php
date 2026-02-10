<?php

namespace plugin\finance\expose\helper;

use app\expose\enum\State;
use plugin\finance\app\model\PluginFinanceWallet;
use plugin\finance\utils\enum\PointsBillScene;
use plugin\finance\utils\enum\ValidityPeriod;
use plugin\user\app\model\PluginUserBill;
use plugin\user\app\model\PluginUserPoints;
use plugin\user\app\model\PluginUserPointsBill;
use plugin\user\utils\enum\MoneyAction;
use think\facade\Db;

class Account
{

    /**
     * 增加积分
     * @param int $uid 用户ID
     * @param int $channels_uid 渠道ID
     * @param float $amount 操作金额
     * @param string $scene 场景
     * @param int $form_id 表单ID
     * @param string $remarks 备注
     * @param bool $is_accumulate 是否计入累计
     * @param string $validity_period 有效期
     * @throws \Exception
     */
    public static function incPoints($uid, $channels_uid, $amount, $scene = '', $form_id = 0, $remarks = '', $is_accumulate = true, $valid_time = null)
    {
        $wallet = PluginFinanceWallet::where('uid', $uid)
            ->where('channels_uid', $channels_uid)
            ->find();
        if (!$wallet) {
            $wallet = new PluginFinanceWallet();
            $wallet->uid = $uid;
            $wallet->channels_uid = $channels_uid;
            $wallet->save();
            $wallet = PluginFinanceWallet::where('uid', $uid)
                ->where('channels_uid', $channels_uid)
                ->find();
        }
        $validity_period = empty($valid_time) ? ValidityPeriod::PERMANENT['value'] : ValidityPeriod::TEMPORARY['value'];

        $log = new PluginUserPointsBill();
        $log->channels_uid = $channels_uid;
        $log->uid = $uid;
        $log->form_id = $form_id;
        $log->num = $amount;
        $log->remarks = $remarks;
        $log->action = MoneyAction::INCREASE['value'];
        $log->scene = $scene;
        $log->type = $validity_period;
        $log->is_sum = $is_accumulate ? State::YES['value'] : State::NO['value'];
        //永久积只累加
        if ($validity_period === ValidityPeriod::PERMANENT['value']) {
            $log->before = $wallet->points;
            $log->after = $wallet->points + $amount;
            $wallet->points = Db::raw('points+' . $amount);
            if ($is_accumulate) {
                $wallet->points_sum = Db::raw('points_sum + ' . $amount);
            }
        } else {
            $log->before = $wallet->tmp_points;
            $log->after = $wallet->tmp_points + $amount;
            $wallet->tmp_points = Db::raw('tmp_points + ' . $amount);
            if ($is_accumulate) {
                $wallet->tmp_points_sum = Db::raw('tmp_points_sum + ' . $amount);
            }

            $userPoints = new PluginUserPoints();
            $userPoints->channels_uid = $channels_uid;
            $userPoints->uid = $uid;
            $userPoints->total_points = $amount;
            $userPoints->points = $amount;
            $userPoints->used_points = 0;
            $userPoints->valid_time = $valid_time;
            $userPoints->extended_time = $valid_time;
            $userPoints->scene =  $scene;
            $userPoints->source_id = $form_id;
            $userPoints->save();
            $log->source_id = $userPoints->id;
        }
        $wallet->save();
        $log->save();
        return $log->id;
    }
    public static function hasPoints($uid, $channels_uid, $amount)
    {
        if ($amount <= 0) {
            return true;
        }
        $wallet = PluginFinanceWallet::where('uid', $uid)
            ->where('channels_uid', $channels_uid)
            ->lock(true)
            ->find();
        if (!$wallet) {
            throw new \Exception('钱包不存在');
        }
        if ($wallet->points + $wallet->tmp_points < $amount) {
            throw new \Exception('积分不足');
        }
    }

    /**
     * 减少积分
     * @param int $uid 用户ID
     * @param int $channels_uid 渠道ID
     * @param float $amount 操作金额
     * @param string $scene 场景
     * @param int $form_id 表单ID
     * @param string $remarks 备注
     * @param bool $is_accumulate 是否计入累计
     * @param bool $is_ignore_balance 是否忽略余额
     * @throws \Exception
     * @author:1950781041@qq.com 
     * @Date:2025-12-30
     */
    public static function decPoints($uid, $channels_uid, $amount, $scene = '', $form_id = 0, $remarks = '', $is_accumulate = true,$is_ignore_balance=false)
    {
        if ($amount <= 0) return [];
        $wallet = PluginFinanceWallet::where('uid', $uid)
            ->where('channels_uid', $channels_uid)
            ->lock(true)
            ->find();
        if (!$wallet) {
            throw new \Exception('钱包不存在');
        }
        if (!$is_ignore_balance && $wallet->points + $wallet->tmp_points < $amount) {
            throw new \Exception('积分不足');
        }
        $ids = [];
        $tempPointsList = PluginUserPoints::where('uid', $uid)
            ->where('channels_uid', $channels_uid)
            ->where('points', '>', 0)
            ->where('valid_time', '>=', date('Y-m-d H:i:s'))
            ->where('extended_time', '>=', date('Y-m-d H:i:s'))
            ->order('extended_time ASC, valid_time ASC')
            ->lock(true)
            ->select();
        $tmp_points = 0;
        $tmp_points_used = 0;
        foreach ($tempPointsList as $point) {
            if ($amount <= 0) break;
            $deduct = min($point['points'] - $point['used_points'], $amount);
            if ($deduct <= 0) continue;
            $PluginUserPoints = PluginUserPoints::where('id', $point['id'])->find();
            if (!$PluginUserPoints) {
                continue;
            }
            $PluginUserPoints->used_points = Db::raw('used_points + ' . $deduct);
            $PluginUserPoints->points = Db::raw('points - ' . $deduct);
            $PluginUserPoints->save();
            $log = new PluginUserPointsBill();
            $log->channels_uid = $channels_uid;
            $log->uid = $uid;
            $log->form_id = $form_id;
            $log->source_id = $point['id'];
            $log->num = $deduct;
            $log->before = $wallet->tmp_points;
            $log->after = $wallet->tmp_points - $deduct;
            $log->remarks = $remarks;
            $log->action = MoneyAction::DECREASE['value'];
            $log->scene = $scene;
            $log->type = ValidityPeriod::TEMPORARY['value'];
            $log->is_sum = $is_accumulate ? State::YES['value'] : State::NO['value'];
            $log->save();
            $ids[] = $log->id;
            $amount -= $deduct;
            $tmp_points += $deduct;
            if ($is_accumulate) {
                $tmp_points_used += $deduct;
            }
        }
        $points = 0;
        $points_used = 0;
        if ($amount > 0) {
            $deduct = min($wallet->points, $amount);
            if ($deduct > 0) {
                $log = new PluginUserPointsBill();
                $log->channels_uid = $channels_uid;
                $log->uid = $uid;
                $log->form_id = $form_id;
                $log->source_id = 0;
                $log->num = $deduct;
                $log->before = $wallet->points;
                $log->after = $wallet->points - $deduct;
                $log->remarks = $remarks;
                $log->action = MoneyAction::DECREASE['value'];
                $log->scene = $scene;
                $log->type = ValidityPeriod::PERMANENT['value'];
                $log->is_sum = $is_accumulate ? State::YES['value'] : State::NO['value'];
                $log->save();
                $amount -= $deduct;
                $points += $deduct;
                if ($is_accumulate) {
                    $points_used += $deduct;
                }
                $ids[] = $log->id;
            }
        }
        if ($tmp_points_used > 0) {
            $wallet->tmp_points_used = Db::raw('tmp_points_used + ' . $tmp_points_used);
        }
        if ($tmp_points > 0) {
            $wallet->tmp_points = Db::raw('tmp_points - ' . $tmp_points);
        }
        if ($points > 0) {
            $wallet->points = Db::raw('points - ' . $points);
        }
        if ($points_used > 0) {
            $wallet->points_used = Db::raw('points_used + ' . $points_used);
        }
        $wallet->save();
        return $ids;
    }
    public static function rollbackPoints($uid, $channels_uid, $ids)
    {
        $bills = PluginUserPointsBill::whereIn('id', $ids)->select();
        foreach ($bills as $bill) {
            $bill->action = MoneyAction::INCREASE['value'];
            $bill->save();
        }
    }
    /**
     * 操作永久积分
     * @param int $uid 用户ID
     * @param int $channels_uid 渠道ID
     * @param float $amount 操作金额
     * @param string $scene 场景
     * @param int $form_id 表单ID
     * @param string $remarks 备注
     * @param bool $is_accumulate 是否计入累计
     * @throws \Exception
     * @author:1950781041@qq.com 
     * @Date:2025-12-30
     */
    public static function updatePermanentPoints($uid, $channels_uid, $amount, $scene = '', $form_id = 0, $action = '', $remarks = '', $is_accumulate = 1)
    {
        if ($amount <= 0) {
            throw new \Exception('操作金额必须大于0');
        }
        if (!in_array($action, [MoneyAction::DECREASE['value'], MoneyAction::INCREASE['value']])) {
            throw new \Exception('操作类型错误');
        }
        $wallet = PluginFinanceWallet::where('uid', $uid)
            ->where('channels_uid', $channels_uid)
            ->lock(true)
            ->find();

        if (!$wallet) {
            throw new \Exception('钱包不存在');
        }
        if ($action === MoneyAction::DECREASE['value']) {
            if ($wallet->points < $amount) {
                throw new \Exception('金额不足');
            }
        }
        $log = new PluginUserPointsBill();
        $log->channels_uid = $channels_uid;
        $log->uid = $uid;
        $log->form_id = $form_id;
        $log->num = $amount;
        $log->remarks = $remarks;
        $log->action = $action;
        $log->scene = $scene;
        $log->before = $wallet->points;
        $log->type = ValidityPeriod::PERMANENT['value'];
        $log->is_accumulate = $is_accumulate ? State::YES['value'] : State::NO['value'];
        if ($action === MoneyAction::DECREASE['value']) {
            $log->after = $wallet->points - $amount;
            $wallet->points -= $amount;
            if ($is_accumulate) {
                $wallet->points_used += $amount;
            }
        } else {
            $log->after = $wallet->points + $amount;
            $wallet->points += $amount;
            if ($is_accumulate) {
                $wallet->points_sum += $amount;
            }
        }
        $wallet->save();
        $log->save();
        return $log->id;
    }
    /**
     * 操作临时积分
     * @param int $uid 用户ID
     * @param int $channels_uid 渠道ID
     * @param float $amount 操作金额
     * @param string $scene 场景
     * @param int $form_id 表单ID
     * @param string $remarks 备注
     * @param bool $is_accumulate 是否计入累计
     * @throws \Exception
     * @author:1950781041@qq.com 
     * @Date:2025-12-30
     */
    public static function decTemporaryPoints($uid, $channels_uid, $amount, $scene = '', $form_id = 0, $action = '', $remarks = '', $is_accumulate = 1, $id = 0)
    {
        $userPoints = PluginUserPoints::where('id', $id)->find();
        $wallet = PluginFinanceWallet::where('uid', $uid)
            ->where('channels_uid', $channels_uid)
            ->lock(true)
            ->find();
        if (!$userPoints || !$wallet) {
            throw new \Exception('积分或钱包不存在');
        }
        if ($userPoints->points < $amount) {
            throw new \Exception('积分不足');
        }
        if ($wallet->tmp_points < $amount) {
            throw new \Exception('临时积分不足');
        }
        $log = new PluginUserPointsBill();
        $log->channels_uid = $channels_uid;
        $log->uid = $uid;
        $log->form_id = $form_id;
        $log->source_id = $id;
        $log->num = $amount;
        $log->remarks = $remarks;
        $log->action = $action;
        $log->scene = $scene;
        $log->type = ValidityPeriod::TEMPORARY['value'];
        $log->is_accumulate = $is_accumulate ? State::YES['value'] : State::NO['value'];
        if ($action === MoneyAction::DECREASE['value']) {
            $log->after = $wallet->tmp_points - $amount;
            $wallet->tmp_points -= $amount;
            if ($is_accumulate) {
                $wallet->tmp_points_used += $amount;
                $userPoints->used_points += $amount;
            }
        }
        $userPoints->points -= $amount;
        $userPoints->save();
        $wallet->save();
        $log->save();
        return $log->id;
    }


    /**
     * 操作用户资产（余额或积分）
     * @param int $uid 用户ID
     * @param int $channels_uid 渠道ID
     * @param float $amount 操作金额
     * @param string $action 操作类型 MoneyAction::DECREASE['value'] / MoneyAction::INCREASE['value']
     * @param string $type 操作类型 balance/points
     * @param string $scene 触发场景
     * @param int $form_id 表单ID
     * @param string|null $remarks 备注
     * @param bool $is_accumulate 是否计入累计 如退款时，不计入累计
     * @return int 日志ID
     * @throws \Exception
     */
    public static function updateAccount($uid, $channels_uid, $amount, $action, $scene = '',  $form_id = 0, $remarks = null, $is_accumulate = true, $validity_period = ValidityPeriod::PERMANENT['value'])
    {
        if ($amount <= 0) {
            throw new \Exception('操作金额必须大于0');
        }
        if (!in_array($action, [MoneyAction::DECREASE['value'], MoneyAction::INCREASE['value']])) {
            throw new \Exception('操作类型错误');
        }
        $wallet = PluginFinanceWallet::where('uid', $uid)
            ->where('channels_uid', $channels_uid)
            ->lock(true)
            ->find();

        if (!$wallet) {
            throw new \Exception('钱包不存在');
        }
        $fieldBalance = 'balance';
        $fieldUsed = 'balance_used';
        $fieldSum = 'balance_sum';
        //判断金额是否足够
        if ($action === MoneyAction::DECREASE['value']) {
            if ($wallet->$fieldBalance < $amount) {
                throw new \Exception('金额不足');
            }
        }

        $log = new PluginUserBill();
        $log->channels_uid = $channels_uid;
        $log->uid = $uid;
        $log->form_id = $form_id;
        $log->num = $amount;
        $log->remarks = $remarks;
        $log->action = $action;
        $log->scene = $scene;
        $log->before = $wallet->$fieldBalance;
        $log->type = $validity_period;
        $log->is_accumulate = $is_accumulate ? State::YES['value'] : State::NO['value'];
        if ($action === MoneyAction::DECREASE['value']) {
            $log->after = $wallet->$fieldBalance - $amount;
            $wallet->$fieldBalance -= $amount;
            if ($is_accumulate) {
                $wallet->$fieldUsed += $amount;
            }
        } else {
            $log->after = $wallet->$fieldBalance + $amount;
            $wallet->$fieldBalance += $amount;
            if ($is_accumulate) {
                $wallet->$fieldSum += $amount;
            }
        }
        $wallet->save();
        $log->save();
        return $log->id;
    }


    /**
     * 退费
     * @param int $log_id 账单ID
     * @param float $number 退费金额
     * @param string $type 操作类型 balance/points
     * @author:1950781041@qq.com
     * @Date:2025-12-20
     */
    public static function refund($uid, $channels_uid, $billIds, $number = null, $remarks = '积分退还',  $scene = 'refunded')
    {
        if ($number !== null && $number <= 0) {
            throw new \Exception('退费金额必须大于0');
        }
        $wallet = PluginFinanceWallet::where('uid', $uid)
            ->where('channels_uid', $channels_uid)
            ->lock(true)
            ->find();
        if (!$wallet) {
            throw new \Exception('钱包不存在');
        }
        $bills = PluginUserPointsBill::whereIn('id', $billIds)->whereRaw('refunded < num')
            ->where('action', 'decrease')
            ->order('id', 'desc')
            ->lock(true)
            ->select();
        $tmp_points = 0;
        $tmp_points_used = 0;
        $points = 0;
        $points_used = 0;
        foreach ($bills as $bill) {
            if ($number === null) {
                $refund = $bill->num;
            } else {
                if ($number <= 0) {
                    break;
                }
                $canRefund = $bill->num - $bill->refunded;
                if ($canRefund <= 0) {
                    continue;
                }
                $refund = min($canRefund, $number);
            }
            $log = new PluginUserPointsBill();
            $log->channels_uid = $channels_uid;
            $log->uid = $uid;
            $log->form_id = $bill->form_id;
            $log->source_id = $bill->source_id;
            $log->num = $refund;
            $log->action = MoneyAction::INCREASE['value'];
            $log->scene = $scene;
            $log->remarks =   "退款，原账单ID：{$bill->id}，" . $remarks;
            $log->type = $bill->type;
            $log->is_sum = 0;
            if ($bill->type === 'temporary') {
                $points = PluginUserPoints::where('id', $bill->source_id)
                    ->lock(true)
                    ->find();
                if (!$points) {
                    throw new \Exception('临时积分记录不存在');
                }
                $points->points = Db::raw('points + ' . $refund);
                $points->used_points = Db::raw('used_points - ' . $refund);
                $points->state = 0;
                $points->save();
                $log->source_id = $points->id;
                $log->before = $wallet->tmp_points;
                $tmp_points += $refund;
                $tmp_points_used += $refund;
                $log->after = $wallet->tmp_points;
            } else {
                $log->before = $wallet->points;
                $points += $refund;
                $points_used += $refund;
                $log->after = $wallet->points;
            }
            $bill->refunded = Db::raw('refunded + ' . $refund);
            $bill->save();

            $log->save();

            if ($number !== null) {
                $number -= $refund;
            }
        }
        if ($tmp_points > 0) {
            $wallet->tmp_points = Db::raw('tmp_points + ' . $tmp_points);
        }
        if ($tmp_points_used > 0) {
            $wallet->tmp_points_used = Db::raw('tmp_points_used - ' . $tmp_points_used);
        }
        if ($points > 0) {
            $wallet->points = Db::raw('points + ' . $points);
        }
        if ($points_used > 0) {
            $wallet->points_used = Db::raw('points_used - ' . $points_used);
        }
        $wallet->save();
        return true;
    }
}
