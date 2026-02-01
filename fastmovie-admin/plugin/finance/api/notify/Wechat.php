<?php

namespace plugin\finance\api\notify;

use app\Basic;
use app\expose\enum\PaymentChannels;
use plugin\finance\app\model\PluginFinanceOrders;
use plugin\finance\app\model\PluginFinanceOrdersLog;
use plugin\finance\expose\helper\Account;
use plugin\finance\utils\enum\OrdersState;
use plugin\finance\utils\enum\OrdersType;
use plugin\finance\utils\enum\PointsBillScene;
use plugin\marketing\app\model\PluginMarketingPlan;
use plugin\marketing\app\model\PluginMarketingPlanPrice;
use plugin\marketing\app\model\PluginMarketingPoints;
use plugin\user\app\model\PluginUserVip;
use support\Log;
use think\facade\Db;

class Wechat extends Basic
{
    public function transaction($data)
    {
        Db::startTrans();
        try {
            $order = PluginFinanceOrders::where(['trade_no' => $data['out_trade_no']])->find();
            if (!$order) {
                return false;
            }
            $order->state = OrdersState::PAID['value'];
            $order->pay_time = date('Y-m-d H:i:s');
            $order->finish_time = date('Y-m-d H:i:s');
            $order->transaction_id = $data['transaction_id'];
            $order->save();
            PluginFinanceOrdersLog::info($order, PaymentChannels::WXPAY['label'] . '支付成功');
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            throw $th;
        }
        try {
            PluginFinanceOrders::finish(['id' => $order->id]);
        } catch (\Throwable $th) {
            Log::error("订单完成失败:{$order->trade_no},{$th->getMessage()},{$th->getFile()}:{$th->getLine()}", $th->getTrace());
        }
        return true;
    }
}
