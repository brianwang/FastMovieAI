/*
 Navicat Premium Data Transfer

 Source Server         : 公司服务器
 Source Server Type    : MySQL
 Source Server Version : 80036 (8.0.36)
 Source Host           : 127.0.0.1:3306
 Source Schema         : ai_short_play

 Target Server Type    : MySQL
 Target Server Version : 80036 (8.0.36)
 File Encoding         : 65001

 Date: 23/01/2026 16:47:51
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for php_admin
-- ----------------------------
DROP TABLE IF EXISTS `php_admin`;
CREATE TABLE `php_admin`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `role_id` int NULL DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `state` tinyint(1) NULL DEFAULT 1,
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `login_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `login_time` datetime NULL DEFAULT NULL,
  `online_time` datetime NULL DEFAULT NULL,
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `allow_time_start` time NULL DEFAULT '08:00:00',
  `allow_time_end` time NULL DEFAULT '18:00:00',
  `allow_week` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT '[0,1,2,3,4,5,6]',
  `wx_openid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_admin_role
-- ----------------------------
DROP TABLE IF EXISTS `php_admin_role`;
CREATE TABLE `php_admin_role`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` int NULL DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `state` tinyint(1) NULL DEFAULT 1,
  `is_system` tinyint NULL DEFAULT 0,
  `update_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '管理员-职权' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_admin_role
-- ----------------------------
INSERT INTO `php_admin_role` VALUES (1, NULL, '系统管理员', NULL, 1, 1, '2024-03-08 16:25:29', '2024-03-08 16:25:31');

-- ----------------------------
-- Table structure for php_config
-- ----------------------------
DROP TABLE IF EXISTS `php_config`;
CREATE TABLE `php_config`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `group`(`group` ASC) USING BTREE,
  INDEX `channelsgroup`(`channels_uid` ASC, `group` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 22 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Table structure for php_payment_config
-- ----------------------------
DROP TABLE IF EXISTS `php_payment_config`;
CREATE TABLE `php_payment_config`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `platform` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `channels` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `template_id` int NULL DEFAULT NULL,
  `state` tinyint NULL DEFAULT 1,
  `is_default` tinyint NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 37 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '微信支付方式绑定' ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Table structure for php_payment_notify_wechat
-- ----------------------------
DROP TABLE IF EXISTS `php_payment_notify_wechat`;
CREATE TABLE `php_payment_notify_wechat`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `notify_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `resource_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `resource_original_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `resource_algorithm` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `resource_ciphertext` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `resource_associated_data` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `resource_nonce` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `plugin` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `template_id` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '微信支付通知' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_payment_template
-- ----------------------------
DROP TABLE IF EXISTS `php_payment_template`;
CREATE TABLE `php_payment_template`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `state` tinyint NULL DEFAULT 1,
  `channels` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '微信支付模板' ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Table structure for php_plugin_article
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_article`;
CREATE TABLE `php_plugin_article`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL COMMENT '为空则为系统级',
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `release_time` datetime NULL DEFAULT NULL,
  `alias` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '文章别名',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '文章标题',
  `subtitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `state` tinyint(1) NULL DEFAULT 1,
  `examine` tinyint(1) NULL DEFAULT NULL,
  `view` int NULL DEFAULT 0,
  `thumb` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '文章封面',
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT '',
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT '',
  `description` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT '',
  `classify_id` int NULL DEFAULT NULL,
  `sort` tinyint NULL DEFAULT 99,
  `delete_time` datetime NULL DEFAULT NULL,
  `push_state` tinyint NULL DEFAULT 0 COMMENT '是否需要推送',
  `push_crowd` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '目标推送人群',
  `push_crowd_uids` json NULL COMMENT '渠道用户uid或用户uid数组',
  `push_last_id` bigint NULL DEFAULT NULL COMMENT '最后推送uid',
  `push_people_num` int NULL DEFAULT 0 COMMENT '已推送人数',
  `push_tarage_people_num` int NULL DEFAULT 0 COMMENT '目标推送人数',
  `last_heartbeat` datetime NULL DEFAULT NULL COMMENT '执行时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `cid`(`classify_id` ASC) USING BTREE,
  INDEX `alias`(`alias` ASC, `channels_uid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '文章' ROW_FORMAT = COMPACT;
-- ----------------------------
-- Table structure for php_plugin_article_classify
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_article_classify`;
CREATE TABLE `php_plugin_article_classify`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL COMMENT '为空则为系统级',
  `create_time` datetime NULL DEFAULT NULL COMMENT '分类创建时间',
  `update_time` datetime NULL DEFAULT NULL,
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '分类名称',
  `alias` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '别名',
  `pid` int NULL DEFAULT NULL COMMENT '父级ID',
  `sort` int NULL DEFAULT 99 COMMENT '排序',
  `state` tinyint(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `alias`(`alias` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '文章分类数据' ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of php_plugin_article_classify
-- ----------------------------
INSERT INTO `php_plugin_article_classify` VALUES (2, NULL, '2025-12-26 10:46:22', '2025-12-30 16:06:40', '公告', NULL, 2, 99, 1);
INSERT INTO `php_plugin_article_classify` VALUES (4, NULL, '2026-01-09 14:51:19', '2026-01-09 15:09:05', '教程', NULL, 4, 99, 1);
INSERT INTO `php_plugin_article_classify` VALUES (9, NULL, '2026-01-09 15:13:39', '2026-01-09 15:17:41', '短剧制作流程', NULL, 11, 99, 1);
INSERT INTO `php_plugin_article_classify` VALUES (11, NULL, '2026-01-09 15:14:10', '2026-01-09 15:14:38', '教程', '', NULL, 99, 1);
INSERT INTO `php_plugin_article_classify` VALUES (13, NULL, '2026-01-09 15:17:55', '2026-01-09 15:17:55', '公告', NULL, NULL, 99, 1);
INSERT INTO `php_plugin_article_classify` VALUES (14, NULL, '2026-01-09 15:18:07', '2026-01-09 15:18:07', '行业', NULL, NULL, 99, 1);

-- ----------------------------
-- Table structure for php_plugin_article_content
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_article_content`;
CREATE TABLE `php_plugin_article_content`  (
  `article_id` int UNSIGNED NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  PRIMARY KEY (`article_id`) USING BTREE,
  UNIQUE INDEX `article`(`article_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '文章内容表' ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Table structure for php_plugin_channels_domain
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_channels_domain`;
CREATE TABLE `php_plugin_channels_domain`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '绑定的域名，不带协议，如：app.example.com',
  `channels_uid` bigint UNSIGNED NOT NULL COMMENT '绑定到的用户ID',
  `state` tinyint UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1正常，0禁用',
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_domain`(`domain` ASC) USING BTREE,
  INDEX `idx_user_id`(`channels_uid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '域名绑定用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_channels_role
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_channels_role`;
CREATE TABLE `php_plugin_channels_role`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` int NULL DEFAULT NULL,
  `channels_uid` int NULL DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `state` tinyint(1) NULL DEFAULT 1,
  `is_system` int NULL DEFAULT 1,
  `update_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '管理员-职权' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_plugin_channels_user
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_channels_user`;
CREATE TABLE `php_plugin_channels_user`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `activation_time` datetime NULL DEFAULT NULL COMMENT '激活时间',
  `channels_uid` int NULL DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '账号',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '密码',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '手机号',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '邮箱',
  `state` tinyint NULL DEFAULT 1 COMMENT '状态',
  `state_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '状态原因',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '昵称',
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '头像',
  `role_id` int NULL DEFAULT 0,
  `login_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '登录IP',
  `is_system` int NULL DEFAULT 1,
  `login_time` datetime NULL DEFAULT NULL COMMENT '登录时间',
  `expire_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1656 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '渠道商' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_plugin_channels_wechat
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_channels_wechat`;
CREATE TABLE `php_plugin_channels_wechat`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` bigint NULL DEFAULT NULL COMMENT '所属用户',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '昵称',
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '头像',
  `openid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `unionid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `mp_openid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `subscribe` tinyint(1) NULL DEFAULT 0,
  `update_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uid`(`uid` ASC) USING BTREE,
  UNIQUE INDEX `openid`(`openid` ASC) USING BTREE,
  UNIQUE INDEX `unid`(`unionid` ASC) USING BTREE,
  UNIQUE INDEX `mini`(`mp_openid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '微信用户' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_plugin_channels_wechat
-- ----------------------------

-- ----------------------------
-- Table structure for php_plugin_finance_orders
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_finance_orders`;
CREATE TABLE `php_plugin_finance_orders`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `payment_id` int NULL DEFAULT NULL COMMENT 'payment_config_id',
  `template_id` int NULL DEFAULT NULL COMMENT 'payment_template_id',
  `trade_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '订单号',
  `alias_id` int NULL DEFAULT NULL COMMENT '关联ID',
  `uid` bigint NULL DEFAULT NULL COMMENT '用户id',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '订单标题',
  `origin_money` decimal(10, 2) NULL DEFAULT NULL COMMENT '原价',
  `unit_money` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '单价',
  `money` decimal(10, 2) NULL DEFAULT NULL COMMENT '需要支付价格',
  `system_money` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '平台优惠金额',
  `num` int NULL DEFAULT 1 COMMENT '数量',
  `pay_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '支付方式',
  `state` int NOT NULL DEFAULT 0 COMMENT '0 待付款 1已完成 2已退款 3已作废 99已取消',
  `pay_time` datetime NULL DEFAULT NULL COMMENT '支付时间',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '订单类型',
  `coupon` json NULL COMMENT '使用的优惠券列表',
  `plugin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `cancel_time` datetime NULL DEFAULT NULL COMMENT '取消时间',
  `expire_time` datetime NULL DEFAULT NULL COMMENT '过期时间',
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '商户支付流水号',
  `finish_time` datetime NULL DEFAULT NULL COMMENT '完成时间',
  `comment_time` datetime NULL DEFAULT NULL COMMENT '评价时间',
  `delete_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `trade`(`trade_no` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 43 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '订单' ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Table structure for php_plugin_finance_orders_log
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_finance_orders_log`;
CREATE TABLE `php_plugin_finance_orders_log`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '日志等级',
  `orders_id` int NULL DEFAULT NULL COMMENT '订单ID',
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '日志内容',
  `admin_id` int NULL DEFAULT NULL COMMENT '操作管理员ID或为空',
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `orders`(`orders_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 80 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '订单操作日志' ROW_FORMAT = COMPACT;
-- ----------------------------
-- Table structure for php_plugin_finance_payment_notify
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_finance_payment_notify`;
CREATE TABLE `php_plugin_finance_payment_notify`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `mchid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `appid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `out_trade_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `transaction_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `trade_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `trade_state` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `trade_state_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `bank_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `attach` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `success_time` datetime NULL DEFAULT NULL,
  `payer_openid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `amount_total` int NULL DEFAULT NULL,
  `amount_payer_total` int NULL DEFAULT NULL,
  `amount_currency` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `amount_payer_currency` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `state` tinyint NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '微信支付通知' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_plugin_finance_payment_notify
-- ----------------------------

-- ----------------------------
-- Table structure for php_plugin_finance_wallet
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_finance_wallet`;
CREATE TABLE `php_plugin_finance_wallet`  (
  `uid` bigint NOT NULL,
  `channels_uid` int NOT NULL,
  `balance` decimal(12, 4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '可用余额',
  `balance_sum` decimal(12, 4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '累计总收入',
  `balance_used` decimal(12, 4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '累计支出',
  `points` int NULL DEFAULT 0 COMMENT '可用积分',
  `points_sum` int NULL DEFAULT 0 COMMENT '累计总积分',
  `points_used` int NULL DEFAULT 0 COMMENT '累计支出',
  `tmp_points` int NULL DEFAULT 0 COMMENT '临时积分',
  `tmp_points_sum` int NULL DEFAULT 0 COMMENT '临时积分累计',
  `tmp_points_used` int NULL DEFAULT 0 COMMENT '临时积分使用',
  PRIMARY KEY (`uid`, `channels_uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '用户钱包' ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Table structure for php_plugin_marketing_coupon
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_marketing_coupon`;
CREATE TABLE `php_plugin_marketing_coupon`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `update_time` datetime NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '优惠券名',
  `coupon_rule` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '优惠券规则',
  `discount` decimal(3, 2) NULL DEFAULT 0.00 COMMENT '折扣',
  `full_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '满',
  `money` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '减',
  `state` tinyint(1) NULL DEFAULT 1 COMMENT '状态',
  `show_list` tinyint(1) NULL DEFAULT 1 COMMENT '是否在前台优惠列表中显示',
  `receive_type` enum('user_only','repeat_day','repeat_week','repeat_month') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'user_only' COMMENT '领取规则',
  `use_type` enum('first_order','first_plugin','','unlimited') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'unlimited' COMMENT '使用规则',
  `sum` int NULL DEFAULT 0 COMMENT '发行数量',
  `num` int NULL DEFAULT 0 COMMENT '可领取数量',
  `use_num` int NULL DEFAULT 0 COMMENT '使用数量',
  `expire_num` int NULL DEFAULT 0 COMMENT '过期数量',
  `receive_num` int NULL DEFAULT 0 COMMENT '领取数量',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 139 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '优惠券-规则' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_plugin_marketing_coupon
-- ----------------------------

-- ----------------------------
-- Table structure for php_plugin_marketing_coupon_code
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_marketing_coupon_code`;
CREATE TABLE `php_plugin_marketing_coupon_code`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `update_time` datetime NOT NULL,
  `uid` bigint NULL DEFAULT NULL COMMENT '拥有者ID',
  `coupon_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '券码',
  `coupon_id` int NOT NULL COMMENT '优惠券ID',
  `receive_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '领取规则',
  `coupon_rule` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '优惠券规则',
  `discount` decimal(5, 2) NOT NULL DEFAULT 0.00 COMMENT '优惠券折扣',
  `full_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '满',
  `money` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '减',
  `state` tinyint(1) NULL DEFAULT 0 COMMENT '状态',
  `start_time` datetime NULL DEFAULT NULL COMMENT '开始使用时间',
  `end_time` datetime NULL DEFAULT NULL COMMENT '结束使用时间',
  `day` int NULL DEFAULT 30 COMMENT '领取后有效期',
  `expire_time` datetime NULL DEFAULT NULL COMMENT '过期时间',
  `receive_time` datetime NULL DEFAULT NULL COMMENT '领取时间',
  `use_time` datetime NULL DEFAULT NULL COMMENT '使用时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `coupon_code`(`coupon_code` ASC) USING BTREE,
  INDEX `apps_available`(`uid` ASC, `state` ASC, `start_time` ASC, `end_time` ASC, `expire_time` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 175 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '优惠券-券码' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_plugin_marketing_coupon_code
-- ----------------------------

-- ----------------------------
-- Table structure for php_plugin_marketing_coupon_password
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_marketing_coupon_password`;
CREATE TABLE `php_plugin_marketing_coupon_password`  (
  `coupon_id` int NOT NULL,
  `password` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '优惠券码',
  UNIQUE INDEX `password`(`password` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '自定义优惠券码' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_plugin_marketing_coupon_password
-- ----------------------------

-- ----------------------------
-- Table structure for php_plugin_marketing_coupon_server
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_marketing_coupon_server`;
CREATE TABLE `php_plugin_marketing_coupon_server`  (
  `coupon_id` int NOT NULL,
  `server` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '可使用服务类型'
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '优惠券可使用服务' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_plugin_marketing_coupon_server
-- ----------------------------

-- ----------------------------
-- Table structure for php_plugin_marketing_plan
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_marketing_plan`;
CREATE TABLE `php_plugin_marketing_plan`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT 'basic 基础  pro 高级',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '描述',
  `state` tinyint(1) NULL DEFAULT NULL COMMENT '状态',
  `channels_uid` int NULL DEFAULT NULL,
  `sort` int NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '会员套餐' ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for php_plugin_marketing_plan_price
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_marketing_plan_price`;
CREATE TABLE `php_plugin_marketing_plan_price`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `plan_id` int NULL DEFAULT 0,
  `price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '价格',
  `billing_cycle` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '计费周期',
  `original_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '划线价',
  `points` int NULL DEFAULT 0 COMMENT '积分',
  `give` int NULL DEFAULT 0 COMMENT '赠送积分',
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '会员套餐价格' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_marketing_points
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_marketing_points`;
CREATE TABLE `php_plugin_marketing_points`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '套餐名称',
  `points` int NULL DEFAULT 0 COMMENT '购买积分点数',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `give` int NULL DEFAULT 0 COMMENT '赠送点数',
  `original_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '划线价',
  `price` decimal(10, 2) NULL DEFAULT NULL COMMENT '价格',
  `state` tinyint(1) NULL DEFAULT 1 COMMENT '状态',
  `discount` decimal(4, 2) NULL DEFAULT 0.00 COMMENT '折扣',
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '积分套餐' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_model
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_model`;
CREATE TABLE `php_plugin_model`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '模型名称',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '模型类型',
  `model_id` int NOT NULL COMMENT '模型ID',
  `model_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `assistant_id` int NOT NULL COMMENT '助手ID',
  `assistant_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `scene` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '场景',
  `point` int NULL DEFAULT NULL COMMENT '计费',
  `state` tinyint(1) NULL DEFAULT 1 COMMENT '状态',
  `sort` int NULL DEFAULT 0,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '模型' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_model_task
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_model_task`;
CREATE TABLE `php_plugin_model_task`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL,
  `model_id` int NULL DEFAULT NULL COMMENT '模型ID',
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '模型类型',
  `pre_task_id` bigint NULL DEFAULT NULL COMMENT '前置任务ID',
  `pre_task_status` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '前置任务状态',
  `alias_id` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '关联数据ID',
  `task_id` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '任务ID',
  `scene` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '场景',
  `status` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '状态',
  `execution_count` int NULL DEFAULT NULL,
  `success_execution_count` int NULL DEFAULT 0,
  `expectation_execution_count` int NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `last_heartbeat` datetime NULL DEFAULT NULL,
  `consume_ids` json NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `task_id`(`task_id` ASC, `model_type` ASC) USING BTREE,
  INDEX `alias`(`alias_id` ASC, `scene` ASC, `status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1625 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '模型任务列表' ROW_FORMAT = Dynamic;


-- ----------------------------
-- Table structure for php_plugin_model_task_result
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_model_task_result`;
CREATE TABLE `php_plugin_model_task_result`  (
  `task_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `params` json NULL COMMENT '传参',
  `result` json NULL COMMENT '结果',
  `image` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '图片地址',
  `video` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '视频地址',
  `audio` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '音频地址',
  `video_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '本地路径',
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '本地路径',
  `audio_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '本地路径',
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '错误消息',
  PRIMARY KEY (`task_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1625 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '模型任务结果' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_notification_message
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_notification_message`;
CREATE TABLE `php_plugin_notification_message`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL COMMENT '所属用户',
  `form_uid` bigint NULL DEFAULT NULL COMMENT '所属管理员',
  `form_id` bigint NULL DEFAULT NULL COMMENT '来源ID',
  `scene` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '场景',
  `alias_id` int NULL DEFAULT NULL COMMENT '关联操作ID',
  `state` tinyint NULL DEFAULT 0 COMMENT '状态',
  `read_state` tinyint NULL DEFAULT 0 COMMENT '已读状态',
  `extra` json NULL COMMENT '扩展信息',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '消息标题',
  `subtitle` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `effect` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '消息情景',
  `push_state` tinyint NULL DEFAULT 0,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 87 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '通知消息' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_plugin_notification_message_content
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_notification_message_content`;
CREATE TABLE `php_plugin_notification_message_content`  (
  `message_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  PRIMARY KEY (`message_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_plugin_notification_online
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_notification_online`;
CREATE TABLE `php_plugin_notification_online`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` bigint NULL DEFAULT NULL COMMENT '所属用户',
  `channels_uid` int NULL DEFAULT NULL,
  `channel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '渠道id',
  `event` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '监听事件',
  `hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid` ASC, `event` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 227 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '在线用户' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_plugin_shortplay_actor
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_actor`;
CREATE TABLE `php_plugin_shortplay_actor`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL COMMENT '所属用户',
  `drama_id` bigint NULL DEFAULT NULL COMMENT '所属剧本，可空，空=公共',
  `episode_id` bigint NULL DEFAULT NULL COMMENT '所属分集，可空',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '角色名称',
  `actor_id` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '角色ID',
  `species_type` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '物种类别',
  `gender` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '性别',
  `age` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '年龄',
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '角色描述',
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '角色形象',
  `three_view_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '三视图',
  `status` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '状态',
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `voice` json NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 850 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '角色列表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_actor_character_look
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_actor_character_look`;
CREATE TABLE `php_plugin_shortplay_actor_character_look`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint UNSIGNED NOT NULL,
  `actor_id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `character_look_id` bigint NULL DEFAULT NULL,
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `three_view_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `upadte_time` datetime NULL DEFAULT NULL,
  `drama_id` bigint NULL DEFAULT NULL,
  `episode_id` bigint NULL DEFAULT NULL,
  `storyboard_id` bigint NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 622 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '分集角色引用' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_character_look
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_character_look`;
CREATE TABLE `php_plugin_shortplay_character_look`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL,
  `drama_id` bigint UNSIGNED NOT NULL COMMENT '剧集ID',
  `episode_id` bigint UNSIGNED NULL DEFAULT NULL COMMENT '集ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `overall_style` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '整体风格（如：日常、精致、狼狈、正式）',
  `makeup` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '妆容描述（淡妆/浓妆/素颜感等）',
  `hair_style` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '发型描述',
  `costume` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '服饰描述（颜色+款式+搭配）',
  `costume_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '服饰URL',
  `status_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '状态补充（凌乱、破损、是否呼应情绪）',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '妆容服饰设定表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_drama
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_drama`;
CREATE TABLE `php_plugin_shortplay_drama`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint UNSIGNED NOT NULL,
  `model_id` int NULL DEFAULT NULL,
  `style_id` int NULL DEFAULT NULL,
  `script` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `aspect_ratio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `episode_num` int NULL DEFAULT 0,
  `episode_sum` int NULL DEFAULT 0,
  `episode_duration` int NULL DEFAULT 0,
  `state` tinyint(1) NULL DEFAULT 1,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `background_description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `outline` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `overall_hook` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `core_catharsis_mechanism` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `main_conflict` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `relationship_mainline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `voice` json NULL,
  `prosody_speed` double NULL DEFAULT 1,
  `prosody_volume` int NULL DEFAULT 50,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1000018 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '剧本' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_drama_actor
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_drama_actor`;
CREATE TABLE `php_plugin_shortplay_drama_actor`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `drama_id` bigint UNSIGNED NOT NULL,
  `actor_id` bigint UNSIGNED NOT NULL,
  `sort` tinyint NULL DEFAULT 99,
  `character_look_id` bigint NULL DEFAULT NULL,
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `three_view_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `voice` json NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `actorList`(`drama_id` ASC, `actor_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 241 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '剧本角色引用' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_drama_episode
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_drama_episode`;
CREATE TABLE `php_plugin_shortplay_drama_episode`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `drama_id` bigint UNSIGNED NOT NULL,
  `episode_no` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `outline` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `video` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `video_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1000212 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '剧本分集' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_drama_episode_actor
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_drama_episode_actor`;
CREATE TABLE `php_plugin_shortplay_drama_episode_actor`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `drama_id` bigint NULL DEFAULT NULL,
  `episode_id` bigint UNSIGNED NOT NULL,
  `actor_id` bigint UNSIGNED NOT NULL,
  `character_look_id` bigint NULL DEFAULT NULL,
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `three_view_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `voice` json NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `actorList`(`drama_id` ASC, `episode_id` ASC, `actor_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 892 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '分集角色引用' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_drama_scene
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_drama_scene`;
CREATE TABLE `php_plugin_shortplay_drama_scene`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL,
  `drama_id` int NULL DEFAULT NULL,
  `episode_id` int NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `scene_space` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `scene_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `scene_time` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `scene_weather` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `atmosphere` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `sort` int NULL DEFAULT 0,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 164 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '场景' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_drama_storyboard
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_drama_storyboard`;
CREATE TABLE `php_plugin_shortplay_drama_storyboard`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `drama_id` bigint NOT NULL,
  `episode_id` bigint UNSIGNED NOT NULL,
  `scene_id` bigint NULL DEFAULT NULL,
  `sort` int NULL DEFAULT NULL,
  `description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '分镜概述',
  `image_prompt` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '分镜生图提示词',
  `video_prompt` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '分镜合成视频提示词',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '分镜图片',
  `video` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '分镜视频',
  `shot_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '镜头类型',
  `shot_angle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '镜头视角',
  `shot_motion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '镜头运动',
  `sfx` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '音效指令',
  `sfx_audio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '音效音频',
  `duration` int NULL DEFAULT 0 COMMENT '分镜时长',
  `narration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '旁白',
  `narration_audio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '旁白音频',
  `use_material_type` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'image',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 517 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '分镜' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_drama_storyboard_actor
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_drama_storyboard_actor`;
CREATE TABLE `php_plugin_shortplay_drama_storyboard_actor`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `drama_id` bigint NULL DEFAULT NULL,
  `episode_id` bigint NULL DEFAULT NULL,
  `storyboard_id` bigint UNSIGNED NOT NULL,
  `actor_id` bigint UNSIGNED NOT NULL,
  `character_look_id` bigint NULL DEFAULT NULL,
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `three_view_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `voice` json NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `actorList`(`drama_id` ASC, `episode_id` ASC, `storyboard_id` ASC, `actor_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 682 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '分镜角色引用' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_drama_storyboard_dialogue
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_drama_storyboard_dialogue`;
CREATE TABLE `php_plugin_shortplay_drama_storyboard_dialogue`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `storyboard_id` bigint UNSIGNED NOT NULL,
  `actor_id` bigint UNSIGNED NOT NULL,
  `prosody_speed` double NULL DEFAULT 1 COMMENT '语速倍率。有效范围：0.5 至 2.0。1.0 = 正常速度，0.5 = 半速，2.0 = 两倍速度。',
  `prosody_volume` int NULL DEFAULT 50 COMMENT '音量，0：静音，50：正常，100：最大音量',
  `emotion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '情感：neutral、fearful、angry、sad、surprised、happy、disgusted',
  `start_time` int NULL DEFAULT 0 COMMENT '台词出现时间：毫秒',
  `end_time` int NULL DEFAULT 0 COMMENT '台词隐藏时间：毫秒',
  `inner_monologue` tinyint NULL DEFAULT 0 COMMENT '内心独白或画外音',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '台词内容',
  `audio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '生成的音频',
  `voice` json NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 346 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '分镜角色引用' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_drama_storyboard_prop
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_drama_storyboard_prop`;
CREATE TABLE `php_plugin_shortplay_drama_storyboard_prop`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `storyboard_id` bigint UNSIGNED NOT NULL,
  `prop_id` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 755 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '分镜物品引用' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_prop
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_prop`;
CREATE TABLE `php_plugin_shortplay_prop`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint UNSIGNED NULL DEFAULT NULL,
  `drama_id` bigint UNSIGNED NULL DEFAULT NULL COMMENT '所属剧本，可空',
  `episode_id` bigint UNSIGNED NULL DEFAULT NULL COMMENT '所属分集，可空',
  `prop_id` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '物品名称',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '图片',
  `three_view_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '三视图',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '描述',
  `status` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 446 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '物品（公共+剧本+分集）' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_share
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_share`;
CREATE TABLE `php_plugin_shortplay_share`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `drama_id` bigint NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `likes` int NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_share_episode
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_share_episode`;
CREATE TABLE `php_plugin_shortplay_share_episode`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `share_id` bigint NULL DEFAULT NULL,
  `drama_id` bigint NULL DEFAULT NULL,
  `episode_id` bigint NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_share_likes
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_share_likes`;
CREATE TABLE `php_plugin_shortplay_share_likes`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `share_id` bigint NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_shortplay_style
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_shortplay_style`;
CREATE TABLE `php_plugin_shortplay_style`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `classify` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '风格名称',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '风格封面',
  `prompts` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '风格指令',
  `state` tinyint NULL DEFAULT 0 COMMENT '状态',
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '风格' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_user
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user`;
CREATE TABLE `php_plugin_user`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `puid` bigint NULL DEFAULT NULL COMMENT '上级ID',
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `activation_time` datetime NULL DEFAULT NULL COMMENT '激活时间',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '账号',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '密码',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '手机号',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '邮箱',
  `state` tinyint NULL DEFAULT 1 COMMENT '状态',
  `state_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '状态原因',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '昵称',
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '头像',
  `login_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '登录IP',
  `login_time` datetime NULL DEFAULT NULL COMMENT '登录时间',
  `twofa_state` tinyint NULL DEFAULT 0 COMMENT '是否开启二次验证',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1690 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '用户' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_plugin_user_authentication
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user_authentication`;
CREATE TABLE `php_plugin_user_authentication`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL,
  `type` enum('personal','enterprise') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'personal' COMMENT '证件类型',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '证件名称',
  `id_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '证件号码',
  `img1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '证件图片',
  `img2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '证件图片',
  `img3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '证件图片',
  `img4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '证件图片',
  `examine` tinyint(1) NULL DEFAULT 0 COMMENT '审核状态',
  `update_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uid`(`uid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '用户认证' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_plugin_user_authentication
-- ----------------------------

-- ----------------------------
-- Table structure for php_plugin_user_authentication_log
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user_authentication_log`;
CREATE TABLE `php_plugin_user_authentication_log`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `auth_id` bigint NULL DEFAULT NULL,
  `state` tinyint(1) NULL DEFAULT NULL COMMENT '认证结果',
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '认证日志内容',
  `admin_id` int NULL DEFAULT NULL COMMENT '操作管理员',
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 30 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '用户认证日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_plugin_user_authentication_log
-- ----------------------------

-- ----------------------------
-- Table structure for php_plugin_user_bill
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user_bill`;
CREATE TABLE `php_plugin_user_bill`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL COMMENT '账单时间',
  `uid` bigint NULL DEFAULT NULL COMMENT '所属用户',
  `form_id` bigint NULL DEFAULT NULL,
  `num` decimal(12, 4) NULL DEFAULT 0.0000 COMMENT '本次变动余额',
  `before` decimal(12, 4) NULL DEFAULT 0.0000 COMMENT '变动前余额',
  `after` decimal(12, 4) NULL DEFAULT 0.0000 COMMENT '变动后余额',
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '变动理由',
  `action` enum('increase','decrease') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '账单类型：0减少，1增加，2余额无变动',
  `scene` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `refunded` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '已退费数量',
  `is_sum` tinyint(1) NULL DEFAULT 1 COMMENT '是否计入累计 1累计 0不累计',
  `type` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '用户-账单流水' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_plugin_user_invitation_code
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user_invitation_code`;
CREATE TABLE `php_plugin_user_invitation_code`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` int NULL DEFAULT NULL,
  `channels_uid` int NULL DEFAULT NULL,
  `code` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `state` tinyint(1) NULL DEFAULT 1,
  `points` int NULL DEFAULT 0,
  `status` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'unused',
  `use_uid` int NULL DEFAULT NULL,
  `use_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 81 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_user_points
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user_points`;
CREATE TABLE `php_plugin_user_points`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` int NULL DEFAULT NULL,
  `total_points` int NULL DEFAULT 0,
  `points` int NULL DEFAULT 0 COMMENT '点数',
  `used_points` int NULL DEFAULT 0 COMMENT '已使用',
  `valid_time` datetime NULL DEFAULT NULL COMMENT '有效期',
  `extended_time` datetime NULL DEFAULT NULL COMMENT '延长有效期',
  `state` tinyint(1) NULL DEFAULT 1 COMMENT '无剩余',
  `scene` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'vip' COMMENT '来源',
  `source_id` int NULL DEFAULT NULL COMMENT '来源ID',
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_user_points_bill
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user_points_bill`;
CREATE TABLE `php_plugin_user_points_bill`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL COMMENT '账单时间',
  `uid` bigint NULL DEFAULT NULL COMMENT '所属用户',
  `form_id` bigint NULL DEFAULT NULL,
  `source_id` int NULL DEFAULT 0,
  `num` int NULL DEFAULT 0 COMMENT '本次变动数量',
  `before` int NULL DEFAULT 0 COMMENT '变动前',
  `after` int NULL DEFAULT 0 COMMENT '变动后',
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '变动理由',
  `action` enum('increase','decrease') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '账单类型',
  `scene` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `refunded` int NULL DEFAULT 0 COMMENT '已退费数量',
  `is_sum` tinyint(1) NULL DEFAULT 1 COMMENT '是否计入累计 1累计 0不累计',
  `type` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '积分类型',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 236 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '用户-积分流水' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_plugin_user_twofa_secret
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user_twofa_secret`;
CREATE TABLE `php_plugin_user_twofa_secret`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NOT NULL COMMENT '所属用户',
  `totp_app` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '二次验证APP',
  `secret` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '密钥',
  `is_default` tinyint NULL DEFAULT 0 COMMENT '是否为默认',
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '二次验证密钥' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of php_plugin_user_twofa_secret
-- ----------------------------

-- ----------------------------
-- Table structure for php_plugin_user_vip
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user_vip`;
CREATE TABLE `php_plugin_user_vip`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` int NULL DEFAULT NULL,
  `channels_uid` int NULL DEFAULT NULL,
  `plan_id` int NULL DEFAULT NULL,
  `plan_price_id` int NULL DEFAULT NULL,
  `type` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `num` int NULL DEFAULT NULL,
  `key` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `creat_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for php_plugin_user_wechat
-- ----------------------------
DROP TABLE IF EXISTS `php_plugin_user_wechat`;
CREATE TABLE `php_plugin_user_wechat`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL COMMENT '所属用户',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '昵称',
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '头像',
  `openid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `unionid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `mp_openid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `subscribe` tinyint(1) NULL DEFAULT 0,
  `update_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uid`(`uid` ASC) USING BTREE,
  UNIQUE INDEX `openid`(`openid` ASC) USING BTREE,
  UNIQUE INDEX `unid`(`unionid` ASC) USING BTREE,
  UNIQUE INDEX `mini`(`mp_openid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '微信用户' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_uploads
-- ----------------------------
DROP TABLE IF EXISTS `php_uploads`;
CREATE TABLE `php_uploads`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL,
  `admin_uid` int NULL DEFAULT NULL,
  `classify_id` int NULL DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `ext` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `mime` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `size` double NULL DEFAULT 0,
  `channels` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'local',
  `auto_delete_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1426 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for php_uploads_classify
-- ----------------------------
DROP TABLE IF EXISTS `php_uploads_classify`;
CREATE TABLE `php_uploads_classify`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `uid` bigint NULL DEFAULT NULL,
  `admin_uid` int NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `dir_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `sort` tinyint NULL DEFAULT 99,
  `channels` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `is_system` tinyint NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 22 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = DYNAMIC;

CREATE TABLE `fastmovie`.`php_plugin_model_voice`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` bigint NULL DEFAULT NULL,
  `channels_uid` int NULL DEFAULT NULL,
  `name` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `headimg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `voice_id` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `audio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `task_id` bigint NULL DEFAULT NULL,
  `status` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `model_id` int NULL DEFAULT NULL,
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `text_id` int NULL DEFAULT NULL,
  `gender` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `age` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

CREATE TABLE `fastmovie`.`php_plugin_model_voice_text`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `channels_uid` int NULL DEFAULT NULL,
  `text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `state` tinyint NULL DEFAULT 1,
  `update_time` datetime NULL DEFAULT NULL,
  `create_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
