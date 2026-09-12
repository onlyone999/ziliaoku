-- 资源表新增字段：VIP免费等级（逗号分隔，如 "3,4" 表示年卡+终身免费）
ALTER TABLE `resources` ADD COLUMN `vip_free_levels` varchar(50) DEFAULT NULL COMMENT 'VIP免费等级,逗号分隔:1=月卡,2=季卡,3=年卡,4=终身' AFTER `points_price`;
