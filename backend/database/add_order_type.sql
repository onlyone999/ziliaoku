-- ============================================================
-- 资料资源下载器 - 数据库迁移
-- 功能: orders 表添加 order_type 和 ref_id 字段
-- 用途: 支持 VIP 购买等不同类型的订单
-- 执行: mysql -u root -proot ziliaoku < add_order_type.sql
-- ============================================================

-- 1. 添加 order_type 字段 (订单类型)
--    resource=资源购买, vip=VIP会员购买, recharge=积分充值
ALTER TABLE `orders`
    ADD COLUMN `order_type` ENUM('resource', 'vip', 'recharge') NOT NULL DEFAULT 'resource' COMMENT '订单类型' AFTER `resource_id`;

-- 2. 添加 ref_id 字段 (关联ID)
--    当 order_type=vip 时, ref_id 关联 vip_plans 表的 id
--    当 order_type=recharge 时, ref_id 关联充值套餐 id
ALTER TABLE `orders`
    ADD COLUMN `ref_id` INT(11) UNSIGNED NULL DEFAULT NULL COMMENT '关联ID(VIP套餐/充值套餐)' AFTER `order_type`;

-- 3. 扩展 payment_method 枚举值, 添加 wechat 支付方式
ALTER TABLE `orders`
    MODIFY COLUMN `payment_method` ENUM('wechat', 'alipay', 'balance', 'points') NOT NULL DEFAULT 'wechat' COMMENT '支付方式: wechat=微信支付, alipay=支付宝, balance=余额, points=积分';

-- 4. 为 order_type 添加索引, 加速按类型查询
ALTER TABLE `orders`
    ADD INDEX `idx_order_type` (`order_type`);

-- 5. 为 ref_id 添加索引
ALTER TABLE `orders`
    ADD INDEX `idx_ref_id` (`ref_id`);

-- ============================================================
-- VIP 套餐表 (如果尚未创建)
-- ============================================================
CREATE TABLE IF NOT EXISTS `vip_plans` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
    `name` VARCHAR(50) NOT NULL COMMENT '套餐名称',
    `type` ENUM('monthly', 'quarterly', 'yearly', 'lifetime') NOT NULL COMMENT '类型: monthly=月卡, quarterly=季卡, yearly=年卡, lifetime=终身',
    `duration_days` INT(11) NOT NULL COMMENT '有效天数',
    `price` DECIMAL(10,2) NOT NULL COMMENT '价格(元)',
    `original_price` DECIMAL(10,2) NULL DEFAULT NULL COMMENT '原价(元)',
    `description` TEXT NULL COMMENT '套餐描述',
    `features` TEXT NULL COMMENT '权益说明(JSON)',
    `sort_order` INT(11) NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态: 0=下架, 1=上架',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VIP会员套餐表';

-- ============================================================
-- 初始化 VIP 套餐数据
-- ============================================================
INSERT INTO `vip_plans` (`name`, `type`, `duration_days`, `price`, `original_price`, `description`, `features`, `sort_order`, `status`) VALUES
('月卡会员', 'monthly', 30, 19.90, 29.90, '一个月VIP会员', '{"download_limit": -1, "vip_resources": true, "no_ads": true}', 1, 1),
('季卡会员', 'quarterly', 90, 49.90, 89.70, '三个月VIP会员, 省39.8元', '{"download_limit": -1, "vip_resources": true, "no_ads": true, "priority_download": true}', 2, 1),
('年卡会员', 'yearly', 365, 149.90, 358.80, '一年VIP会员, 省208.9元', '{"download_limit": -1, "vip_resources": true, "no_ads": true, "priority_download": true, "exclusive_resources": true}', 3, 1),
('终身会员', 'lifetime', 36500, 299.90, NULL, '一次购买, 永久使用', '{"download_limit": -1, "vip_resources": true, "no_ads": true, "priority_download": true, "exclusive_resources": true, "early_access": true}', 4, 1);

-- ============================================================
-- 用户 VIP 信息表 (如果尚未创建)
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_vip` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
    `vip_level` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'VIP等级: 0=普通, 1=VIP',
    `vip_expire` DATETIME NULL DEFAULT NULL COMMENT 'VIP到期时间',
    `total_downloads` INT(11) NOT NULL DEFAULT 0 COMMENT '总下载次数',
    `daily_downloads` INT(11) NOT NULL DEFAULT 0 COMMENT '今日下载次数',
    `last_download_date` DATE NULL DEFAULT NULL COMMENT '最后下载日期',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uk_user_id` (`user_id`),
    INDEX `idx_vip_expire` (`vip_expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户VIP信息表';

-- 完成
SELECT 'Migration add_order_type.sql executed successfully' AS result;
