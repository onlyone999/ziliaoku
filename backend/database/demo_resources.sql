-- ============================================
-- 资料资源下载器 - Demo Resources
-- 12 demo resources + 8 tags + tag-resource links
-- ============================================

USE `ziliaoku`;

-- Insert 8 tags
INSERT INTO `tags` (`name`, `sort_order`, `use_count`) VALUES
('办公', 1, 0),
('学习', 2, 0),
('编程', 3, 0),
('设计', 4, 0),
('教程', 5, 0),
('源码', 6, 0),
('模板', 7, 0),
('电子书', 8, 0);

-- Insert 12 demo resources
INSERT INTO `resources`
(`category_id`, `title`, `description`, `cover_url`, `file_url`, `file_size`, `file_type`, `file_suffix`, `download_count`, `view_count`, `price_type`, `price`, `vip_price`, `status`, `is_recommended`, `sort_order`, `admin_id`)
VALUES
-- category 1: 办公文档
(1, '2024年度工作总结模板', '包含各类岗位的年终工作总结范文模板，格式规范，即拿即用，帮助您高效完成年度总结汇报。', '/uploads/covers/default.jpg', '/uploads/resources/demo.pdf', 3500000, 'pdf', 'pdf', 1820, 4650, 'free', 0.00, 0.00, 'approved', 1, 100, 1),
(1, '商务合同范本大全', '涵盖劳动合同、采购合同、合作协议等20余种常用商务合同范本，专业律师审核，可直接修改使用。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 28500000, 'zip', 'zip', 956, 3120, 'paid', 9.99, 4.99, 'approved', 1, 100, 1),

-- category 2: 学习教育
(2, 'Python入门到精通教程', '从零基础到项目实战的完整Python学习路线，涵盖基础语法、面向对象、Web开发、数据分析等核心内容。', '/uploads/covers/default.jpg', '/uploads/resources/demo.pdf', 45200000, 'pdf', 'pdf', 1560, 4890, 'paid', 12.99, 6.99, 'approved', 1, 100, 1),
(2, '考研英语真题集', '近15年考研英语一/二真题汇编，含详细解析和高频词汇总结，考研必备资料。', '/uploads/covers/default.jpg', '/uploads/resources/demo.pdf', 18700000, 'pdf', 'pdf', 1230, 3650, 'member_free', 5.99, 0.00, 'approved', 0, 100, 1),

-- category 3: 源码素材
(3, 'Vue3后台管理系统源码', '基于Vue3 + TypeScript + Element Plus开发的后台管理系统模板，含用户管理、权限控制、数据报表等完整模块。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 32100000, 'zip', 'zip', 876, 2540, 'paid', 19.99, 9.99, 'approved', 1, 100, 1),
(3, '微信小程序商城源码', '完整的微信小程序商城系统源码，包含商品展示、购物车、订单管理、支付集成等功能，开箱即用。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 25600000, 'zip', 'zip', 720, 2100, 'paid', 15.99, 7.99, 'approved', 1, 100, 1),

-- category 4: 设计素材
(4, 'UI设计规范模板', '包含色彩体系、字体规范、组件库、间距系统等完整的UI设计规范文档，适用于Figma和Sketch。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 15800000, 'zip', 'zip', 645, 1980, 'free', 0.00, 0.00, 'approved', 0, 100, 1),
(4, 'PS高级调色预设包', '100+款专业Photoshop调色预设，涵盖人像、风景、商业、胶片等多种风格，一键调色提升效率。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 8200000, 'zip', 'zip', 1100, 3400, 'member_free', 8.99, 0.00, 'approved', 1, 100, 1),

-- category 5: 电子书籍
(5, '深度学习实战指南', '系统讲解CNN、RNN、Transformer等深度学习核心架构，配合PyTorch代码实战，适合AI从业者和研究者。', '/uploads/covers/default.jpg', '/uploads/resources/demo.pdf', 22400000, 'pdf', 'pdf', 780, 2860, 'paid', 14.99, 5.99, 'approved', 1, 100, 1),
(5, '产品思维入门', '产品经理必读书籍，从需求分析到产品设计全流程讲解，附大量互联网产品案例拆解。', '/uploads/covers/default.jpg', '/uploads/resources/demo.pdf', 12600000, 'pdf', 'pdf', 430, 1560, 'free', 0.00, 0.00, 'approved', 0, 100, 1),

-- category 6: PPT模板
(6, '商务汇报PPT模板50套', '精选50套高端商务汇报PPT模板，适用于工作汇报、项目提案、商业计划书等多种场景。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 42000000, 'zip', 'zip', 1450, 4200, 'paid', 6.99, 2.99, 'approved', 0, 100, 1),
-- Extra resource for variety
(5, '人工智能简史', '从图灵测试到ChatGPT，全面梳理人工智能发展历程中的里程碑事件和关键人物。', '/uploads/covers/default.jpg', '/uploads/resources/demo.pdf', 9800000, 'pdf', 'pdf', 320, 1200, 'member_free', 3.99, 0.00, 'approved', 0, 100, 1),

-- category 7: 音频素材
(7, '商用背景音乐合集', '50首高品质商用背景音乐，适用于短视频、直播、广告等场景，无版权风险。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 156000000, 'zip', 'zip', 560, 1890, 'paid', 19.99, 9.99, 'approved', 1, 100, 1),
(7, '自然白噪音音频包', '包含雨声、海浪、森林、篝火等15种自然白噪音，帮助放松入睡和专注工作。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 89000000, 'zip', 'zip', 420, 1560, 'free', 0.00, 0.00, 'approved', 0, 100, 1),

-- category 8: 视频素材
(8, '4K城市航拍素材包', '20段4K分辨率城市航拍视频素材，涵盖日景、夜景、延时摄影等多种场景。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 2800000000, 'zip', 'zip', 380, 1420, 'paid', 39.99, 19.99, 'approved', 1, 100, 1),
(8, '动态粒子特效素材', '100+款动态粒子特效视频素材，适用于片头、转场、背景等创意制作。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 450000000, 'zip', 'zip', 690, 2100, 'member_free', 12.99, 0.00, 'approved', 0, 100, 1),

-- category 9: 图片素材
(9, '高质量免抠PNG素材库', '500+张高清免抠PNG素材，涵盖人物、动物、植物、建筑等类别，即下即用。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 320000000, 'zip', 'zip', 870, 2980, 'paid', 9.99, 4.99, 'approved', 1, 100, 1),
(9, '扁平化图标库2000+', '2000+个扁平化设计图标，覆盖互联网、教育、医疗、金融等行业，SVG格式可编辑。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 45000000, 'zip', 'zip', 1200, 3800, 'free', 0.00, 0.00, 'approved', 0, 100, 1),

-- category 10: 其他资源
(10, 'Excel数据分析模板', '包含数据透视表、KPI看板、财务报表等30个专业Excel数据分析模板。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 18500000, 'zip', 'zip', 1680, 5200, 'paid', 4.99, 1.99, 'approved', 1, 100, 1),
(10, 'Notion效率模板合集', '50个精选Notion模板，涵盖项目管理、读书笔记、习惯追踪、财务管理等场景。', '/uploads/covers/default.jpg', '/uploads/resources/demo.zip', 5200000, 'zip', 'zip', 950, 3100, 'member_free', 6.99, 0.00, 'approved', 0, 100, 1);

-- Link resources to tags (resource_tags)
-- tag_id: 1=办公, 2=学习, 3=编程, 4=设计, 5=教程, 6=源码, 7=模板, 8=电子书

INSERT INTO `resource_tags` (`resource_id`, `tag_id`) VALUES
-- 2024年度工作总结模板 (res 1): 办公, 模板
(1, 1), (1, 7),
-- 商务合同范本大全 (res 2): 办公, 模板
(2, 1), (2, 7),
-- Python入门到精通教程 (res 3): 学习, 编程, 教程
(3, 2), (3, 3), (3, 5),
-- 考研英语真题集 (res 4): 学习, 电子书
(4, 2), (4, 8),
-- Vue3后台管理系统源码 (res 5): 编程, 源码, 教程
(5, 3), (5, 6), (5, 5),
-- 微信小程序商城源码 (res 6): 编程, 源码
(6, 3), (6, 6),
-- UI设计规范模板 (res 7): 设计, 模板
(7, 4), (7, 7),
-- PS高级调色预设包 (res 8): 设计, 模板
(8, 4), (8, 7),
-- 深度学习实战指南 (res 9): 学习, 编程, 电子书
(9, 2), (9, 3), (9, 8),
-- 产品思维入门 (res 10): 学习, 电子书
(10, 2), (10, 8),
-- 商务汇报PPT模板50套 (res 11): 办公, 模板
(11, 1), (11, 7),
-- 人工智能简史 (res 12): 学习, 电子书
(12, 2), (12, 8),
-- 商用背景音乐合集 (res 13): 设计, 模板
(13, 4), (13, 7),
-- 自然白噪音音频包 (res 14): 学习
(14, 2),
-- 4K城市航拍素材包 (res 15): 设计, 模板
(15, 4), (15, 7),
-- 动态粒子特效素材 (res 16): 设计, 模板
(16, 4), (16, 7),
-- 高质量免抠PNG素材库 (res 17): 设计, 模板
(17, 4), (17, 7),
-- 扁平化图标库2000+ (res 18): 设计, 模板
(18, 4), (18, 7),
-- Excel数据分析模板 (res 19): 办公, 模板
(19, 1), (19, 7),
-- Notion效率模板合集 (res 20): 办公, 模板, 教程
(20, 1), (20, 7), (20, 5);

-- Update tag use_count based on actual links
UPDATE `tags` t SET t.use_count = (SELECT COUNT(*) FROM `resource_tags` rt WHERE rt.tag_id = t.id);
