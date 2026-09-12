-- ============================================
-- 资料资源下载器 - Initial Data
-- MySQL 5.7 compatible, utf8mb4
-- Database: ziliaoku
-- ============================================

USE `ziliaoku`;

-- -------------------------------------------
-- 默认管理员 (admin / admin123)
-- bcrypt hash of admin123
-- -------------------------------------------
INSERT INTO `admin_users` (`username`, `password`, `realname`, `role`, `status`) VALUES
('admin', '$2y$10$HfzJhPXM.3M/D7AqIOTseOJGYgLPNd/VCKOwGJbKMIjVJkNIwyWQe', '超级管理员', 'super', 1);

-- -------------------------------------------
-- 10个默认分类
-- -------------------------------------------
INSERT INTO `categories` (`id`, `parent_id`, `name`, `icon`, `sort_order`, `is_hot`, `status`) VALUES
(1,  0, '办公文档',   'icon-office',   100, 1, 1),
(2,  0, '学习教育',   'icon-education', 90,  1, 1),
(3,  0, '源码素材',   'icon-code',      80,  1, 1),
(4,  0, '设计素材',   'icon-design',    70,  1, 1),
(5,  0, '电子书籍',   'icon-book',      60,  0, 1),
(6,  0, 'PPT模板',   'icon-ppt',       50,  1, 1),
(7,  0, '音频素材',   'icon-audio',     40,  0, 1),
(8,  0, '视频素材',   'icon-video',     30,  0, 1),
(9,  0, '图片素材',   'icon-image',     20,  0, 1),
(10, 0, '其他资源',   'icon-more',      10,  0, 1),
-- 二级分类
(11, 1, '合同协议',   'icon-contract',  95,  0, 1),
(12, 1, '工作总结',   'icon-summary',   94,  0, 1),
(13, 1, '规章制度',   'icon-rules',     93,  0, 1),
(14, 2, '编程开发',   'icon-dev',       85,  1, 1),
(15, 2, '考试资料',   'icon-exam',      84,  0, 1),
(16, 2, '语言学习',   'icon-lang',      83,  0, 1),
(17, 3, '前端源码',   'icon-frontend',  75,  1, 1),
(18, 3, '后端源码',   'icon-backend',   74,  0, 1),
(19, 3, '小程序源码', 'icon-miniprogram', 73, 0, 1),
(20, 4, 'UI素材',    'icon-ui',        65,  1, 1),
(21, 4, '图标素材',   'icon-icons',     64,  0, 1),
(22, 4, '字体素材',   'icon-font',      63,  0, 1),
-- 三级分类
(23, 14, 'Python',   'icon-python',    82,  1, 1),
(24, 14, 'Java',     'icon-java',      81,  0, 1),
(25, 14, 'JavaScript', 'icon-js',      80,  0, 1),
(26, 17, 'Vue.js',   'icon-vue',       72,  1, 1),
(27, 17, 'React',    'icon-react',     71,  0, 1),
(28, 20, 'Figma',    'icon-figma',     62,  1, 1),
(29, 20, 'Sketch',   'icon-sketch',    61,  0, 1),
-- 四级分类
(30, 23, 'Python基础', 'icon-py-basic', 79, 0, 1),
(31, 23, 'Python进阶', 'icon-py-adv',   78, 0, 1),
(32, 23, 'Python实战', 'icon-py-project', 77, 0, 1);

-- -------------------------------------------
-- 系统设置
-- -------------------------------------------
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name',          '资料资源下载器', 'basic'),
('site_description',   '海量资源一键下载', 'basic'),
('free_download_limit', '3',            'download'),
('vip_download_limit',  '999',          'download'),
('points_per_download', '10',           'points'),
('upload_max_size',     '100',          'upload');

-- -------------------------------------------
-- 4个VIP套餐
-- -------------------------------------------
INSERT INTO `vip_plans` (`name`, `duration_days`, `original_price`, `price`, `points_price`, `description`, `sort_order`, `status`) VALUES
('月度会员',  30,   49.90, 29.90,  2990, '开通即可享30天VIP特权，全场资源免费下载',       100, 1),
('季度会员',  90,   129.90, 69.90, 6990, '开通即可享90天VIP特权，全场资源免费下载，更划算', 90,  1),
('年度会员',  365,  399.90, 199.90, 19990, '开通即可享365天VIP特权，全场资源免费下载，超值之选', 80, 1),
('终身会员', 3650,  999.90, 499.90, 49990, '一次开通永久VIP，海量资源无限下载，尊享专属服务', 70, 1);

-- -------------------------------------------
-- 3个默认轮播图
-- -------------------------------------------
INSERT INTO `banners` (`title`, `image_url`, `link_type`, `link_value`, `sort_order`, `status`) VALUES
('海量资源免费下载', '/uploads/banners/banner1.png', 'url', '/pages/category/list', 100, 1),
('VIP会员限时特惠',  '/uploads/banners/banner2.png', 'url', '/pages/user/vip',      90,  1),
('每日精选推荐',     '/uploads/banners/banner3.png', 'url', '/pages/category/list',  80,  1);

-- -------------------------------------------
-- 热门搜索关键词
-- -------------------------------------------
INSERT INTO `search_keywords` (`keyword`, `search_count`, `status`) VALUES
('PPT模板', 1200, 1),
('简历', 980, 1),
('设计素材', 860, 1),
('Excel', 750, 1),
('Word文档', 680, 1),
('PDF', 590, 1),
('源码', 520, 1),
('教程', 480, 1),
('Python', 450, 1),
('UI设计', 380, 1);
