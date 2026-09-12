-- ============================================
-- 资料资源下载器 - 订单表升级迁移
-- 新增 order_type 和 vip_plan_id 字段
-- 支持资源购买和VIP购买两种订单类型
-- ============================================

USE `ziliaoku`;

-- 新增订单类型字段
ALTER TABLE `orders`
  ADD COLUMN `order_type` enum('resource','vip') NOT NULL DEFAULT 'resource' COMMENT '订单类型: resource=资源下载, vip=VIP购买' AFTER `user_id`,
  ADD COLUMN `vip_plan_id` int(10) unsigned DEFAULT NULL COMMENT 'VIP套餐ID(仅VIP订单)' AFTER `resource_id`;

-- 修改 resource_id 允许为 NULL（VIP订单不需要）
ALTER TABLE `orders`
  MODIFY COLUMN `resource_id` int(10) unsigned DEFAULT NULL;

-- 修改外键约束，允许 resource_id 为 NULL
ALTER TABLE `orders`
  DROP FOREIGN KEY `fk_orders_resource`,
  ADD CONSTRAINT `fk_orders_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`);

-- 新增 VIP 套餐外键
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_vip_plan` FOREIGN KEY (`vip_plan_id`) REFERENCES `vip_plans` (`id`);

-- 新增索引
ALTER TABLE `orders`
  ADD INDEX `idx_order_type` (`order_type`);
