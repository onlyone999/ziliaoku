# 资料资源下载器小程序

## 项目介绍

资料资源下载器是一款微信小程序应用，为用户提供资料资源的浏览、搜索、下载和管理服务。支持多种文件格式的在线预览和下载，包含 VIP 会员体系和微信支付功能。

## 技术栈

| 层级 | 技术 | 版本 |
|------|------|------|
| 前端 | UniApp (Vue 3) | HBuilderX 3.x |
| 后端 | PHP | 7.4 |
| 数据库 | MySQL | 5.7 |
| 服务器 | Nginx | 1.20+ |
| PHP-FGI 端口 | 9001 | - |
| 服务端口 | 9901 | - |
| 数据库名 | ziliaoku | - |

## 目录结构

```
E:/ziliaoku/
├── README.md                      # 项目说明文档
├── nginx/                         # Nginx 配置
│   ├── ziliaoku.conf              # 主站配置
│   └── ziliaoku-admin.conf        # 后台管理子域配置
├── backend/                       # 后端 PHP 代码
│   ├── .env.example               # 环境变量模板
│   ├── .env                       # 环境变量(从模板复制后修改)
│   ├── index.php                  # 入口文件
│   ├── api.php                    # API 入口
│   ├── admin/                     # 后台管理模块
│   │   ├── index.php              # 后台入口
│   │   ├── views/                 # 后台视图模板
│   │   └── controllers/           # 后台控制器
│   ├── api/                       # API 模块
│   │   └── controllers/           # API 控制器
│   ├── models/                    # 数据模型
│   ├── common/                    # 公共函数库
│   │   ├── functions.php          # 通用函数
│   │   ├── db.php                 # 数据库连接
│   │   └── response.php           # 响应封装
│   ├── config/                    # 配置文件
│   │   └── config.php             # 主配置
│   ├── middleware/                # 中间件
│   │   └── auth.php               # 鉴权中间件
│   ├── uploads/                   # 上传文件目录
│   │   ├── avatars/               # 头像
│   │   ├── resources/             # 资源文件
│   │   └── images/                # 图片
│   ├── certs/                     # 证书目录
│   │   ├── apiclient_cert.pem     # 微信支付证书
│   │   └── apiclient_key.pem      # 微信支付密钥
│   └── database/                  # 数据库文件
│       ├── schema.sql             # 建表 SQL
│       ├── init_data.sql          # 初始化数据
│       └── add_order_type.sql     # 迁移 SQL
├── miniprogram/                   # 小程序前端代码
│   ├── pages/                     # 页面
│   ├── components/                # 组件
│   ├── static/                    # 静态资源
│   ├── utils/                     # 工具函数
│   ├── api/                       # 接口封装
│   ├── App.vue                    # 应用入口
│   ├── main.js                    # 主入口
│   ├── manifest.json              # 应用配置
│   ├── pages.json                 # 页面路由
│   └── uni.scss                   # 全局样式
└── runtime/                       # 运行时目录(自动生成)
    ├── access.log                 # 访问日志
    ├── error.log                  # 错误日志
    ├── cache/                     # 缓存
    ├── logs/                      # 应用日志
    └── sessions/                  # 会话文件
```

## 安装部署

### 一、环境准备

1. 安装 phpStudy 并启动 Nginx + MySQL 服务
2. 确认 PHP 7.4 FastCGI 运行在端口 9001
3. 确认 MySQL 5.7 运行在 3306 端口

### 二、创建数据库

```sql
-- 登录 MySQL
mysql -u root -proot

-- 创建数据库
CREATE DATABASE ziliaoku DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 导入表结构
USE ziliaoku;
SOURCE E:/ziliaoku/backend/database/schema.sql;

-- 导入初始数据
SOURCE E:/ziliaoku/backend/database/init_data.sql;

-- 执行迁移(订单类型字段)
SOURCE E:/ziliaoku/backend/database/add_order_type.sql;
```

### 三、配置 Nginx

1. 复制 Nginx 配置到 phpStudy:

```bash
cp E:/ziliaoku/nginx/ziliaoku.conf E:/phpstudy_pro/Extensions/Nginx/conf/vhost/
```

2. 编辑 phpStudy 的 `nginx.conf`，在 `http {}` 块中引入:

```nginx
include vhost/ziliaoku.conf;
# 可选: 后台管理子域
# include vhost/ziliaoku-admin.conf;
```

3. 在 hosts 文件中添加域名解析:

```
127.0.0.1 ziliaoku.local
127.0.0.1 admin.ziliaoku.local
```

4. 重启 Nginx 服务

### 四、配置环境变量

```bash
# 复制模板
cp E:/ziliaoku/backend/.env.example E:/ziliaoku/backend/.env

# 编辑 .env 文件，修改以下关键配置:
# - DB_USER / DB_PASS: 数据库账号密码
# - JWT_SECRET: 随机生成的 32 位以上字符串
# - WX_APPID / WX_SECRET: 微信小程序 AppID 和 Secret
# - WX_MCH_ID / WX_API_KEY: 微信支付商户号和密钥
```

### 五、PHP 扩展要求

确保 PHP 7.4 启用以下扩展:

```
pdo_mysql   - 数据库连接
gd          - 图片处理(缩略图/水印)
json        - JSON 解析
openssl     - 加密/HTTPS
curl        - HTTP 请求(微信API)
mbstring    - 多字节字符串
fileinfo    - 文件类型检测
```

在 phpStudy 中检查: 软件管理 -> PHP 7.4 -> 扩展管理

### 六、目录权限

确保以下目录可写:

```bash
# Windows 环境: 右键目录 -> 属性 -> 安全 -> 编辑 -> IIS_IUSRS / Users -> 完全控制
E:/ziliaoku/backend/uploads/        # 上传文件
E:/ziliaoku/backend/uploads/avatars/ # 用户头像
E:/ziliaoku/backend/uploads/resources/ # 资源文件
E:/ziliaoku/runtime/                 # 运行时目录
E:/ziliaoku/runtime/cache/           # 缓存
E:/ziliaoku/runtime/logs/            # 日志
E:/ziliaoku/runtime/sessions/        # 会话
```

### 七、访问验证

- 前端接口: http://ziliaoku.local:9901/api/
- 后台管理: http://ziliaoku.local:9901/admin/

## 后台管理

| 项目 | 说明 |
|------|------|
| 访问地址 | http://ziliaoku.local:9901/admin/ |
| 默认账号 | admin |
| 默认密码 | admin123 |

> 首次登录后请立即修改默认密码!

## 小程序配置

### 1. manifest.json 配置

```json
{
  "mp-weixin": {
    "appid": "your_appid_here",
    "setting": {
      "urlCheck": false,
      "es6": true,
      "postcss": true
    }
  }
}
```

### 2. 接口地址配置

编辑 `miniprogram/utils/config.js`:

```javascript
const config = {
  // 开发环境
  dev: {
    baseUrl: 'http://ziliaoku.local:9901/api'
  },
  // 生产环境
  prod: {
    baseUrl: 'https://yourdomain.com/api'
  }
}

// 切换环境
const env = 'dev'
export default config[env]
```

### 3. 微信开发者工具设置

- 详情 -> 本地设置 -> 勾选「不校验合法域名」(开发阶段)
- 生产环境需在微信后台配置合法域名

## 微信虚拟支付配置说明

> 注意: 微信小程序虚拟支付仅限 iOS 端 Android 端因政策限制需引导用户至 H5 页面支付。

### 支付流程

```
用户下单 -> 后端创建订单 -> 调用微信统一下单接口 -> 返回支付参数 -> 前端调用 wx.requestPayment -> 支付回调通知 -> 更新订单状态
```

### 配置步骤

1. 登录 [微信商户平台](https://pay.weixin.qq.com/) 获取商户号和 API 密钥
2. 下载支付证书，放置到 `E:/ziliaoku/backend/certs/` 目录
3. 在 `.env` 中配置支付相关参数
4. 设置支付回调地址: `https://yourdomain.com/api/pay/notify`
5. 微信小程序后台 -> 功能 -> 虚拟支付 -> 开通

### 支付参数说明

| 参数 | 说明 |
|------|------|
| WX_MCH_ID | 微信支付商户号 |
| WX_API_KEY | APIv2 密钥(32 位) |
| WX_NOTIFY_URL | 支付结果回调地址(必须 HTTPS) |
| WX_CERT_PATH | 证书文件路径 |
| WX_KEY_PATH | 密钥文件路径 |

## 功能列表

### 用户端 (小程序)

- **用户登录**: 微信授权一键登录
- **资源浏览**: 分类浏览、关键词搜索
- **资源详情**: 文件预览、描述、大小、下载次数
- **资源下载**: 免费资源直接下载，付费/VIP 资源解锁后下载
- **用户中心**: 个人信息、下载记录、收藏列表
- **VIP 会员**: 月卡/季卡/年卡购买，VIP 专属资源
- **收藏管理**: 收藏/取消收藏资源
- **积分系统**: 签到得积分，积分兑换下载次数

### 后台管理

- **数据仪表盘**: 用户统计、下载统计、收入统计
- **资源管理**: 上传/编辑/删除资源，设置分类、标签、价格
- **分类管理**: 资源分类的增删改查
- **用户管理**: 用户列表、状态管理、VIP 设置
- **订单管理**: 订单列表、支付状态查看、退款处理
- **系统设置**: 站点名称、Logo、公告、下载规则
- **轮播管理**: 首页轮播图管理

## API 接口文档概要

### 认证接口

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/auth/login | 微信登录 |
| POST | /api/auth/refresh | 刷新 Token |
| GET  | /api/auth/userinfo | 获取用户信息 |

### 资源接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET  | /api/resource/list | 资源列表(分页/分类/搜索) |
| GET  | /api/resource/detail/:id | 资源详情 |
| GET  | /api/resource/hot | 热门资源 |
| GET  | /api/resource/latest | 最新资源 |
| POST | /api/resource/download/:id | 获取下载链接 |
| POST | /api/resource/collect/:id | 收藏/取消收藏 |

### 分类接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET  | /api/category/list | 分类列表 |
| GET  | /api/category/:id/resources | 分类下资源 |

### 用户接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET  | /api/user/profile | 个人资料 |
| PUT  | /api/user/profile | 修改资料 |
| GET  | /api/user/downloads | 下载记录 |
| GET  | /api/user/collections | 收藏列表 |
| POST | /api/user/signin | 每日签到 |

### VIP / 订单接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET  | /api/vip/plans | VIP 套餐列表 |
| POST | /api/order/create | 创建订单 |
| POST | /api/order/pay | 发起支付 |
| POST | /api/pay/notify | 微信支付回调 |
| GET  | /api/order/status/:id | 查询订单状态 |

### 所有 API 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### API 响应格式

```json
{
  "code": 200,
  "message": "success",
  "data": {}
}
```

错误码说明:

| code | 说明 |
|------|------|
| 200 | 成功 |
| 400 | 参数错误 |
| 401 | 未登录/Token 过期 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

## 注意事项

1. **安全**: 生产环境务必修改 `.env` 中的默认密码和 JWT_SECRET
2. **HTTPS**: 微信小程序要求接口必须使用 HTTPS，生产环境需配置 SSL 证书
3. **跨域**: 开发阶段已配置 CORS 头，生产环境建议限制 `Access-Control-Allow-Origin` 为具体域名
4. **上传**: 大文件上传建议分片处理，单文件限制 100MB
5. **数据库**: 定期备份数据库，建议配置自动备份脚本
6. **日志**: 定期清理 `runtime/` 目录下的日志文件
7. **支付**: 微信支付回调必须外网可访问，开发阶段可用内网穿透工具
8. **小程序审核**: 虚拟支付类小程序上架需提供相关资质证明
9. **PHP-FPM**: 确认 PHP 7.4 的 FastCGI 监听在 `127.0.0.1:9001`
10. **域名**: 开发阶段修改 hosts 文件即可，生产环境需注册域名并备案
