-- 预览功能数据库迁移
-- 添加预览计数字段 + 预览相关系统设置

-- 资源表添加预览计数
ALTER TABLE `resources` ADD COLUMN `preview_count` int(11) DEFAULT 0 COMMENT '预览次数' AFTER `download_count`;

-- 预览相关系统设置
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('allowed_preview_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,js,css,html,json,xml,py,java,c,cpp,zip,rar,7z'),
('max_preview_size', '52428800'),
('office_preview_mode', 'microsoft'),
('archive_preview_enabled', '1');
