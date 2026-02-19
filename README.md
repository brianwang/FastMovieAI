<div align="center">

<img src="./readme_assets/banner.png" alt="FastMovieAI Banner" width="100%" />

# FastMovieAI

**可商用的一站式短剧创作平台**

[在线演示](https://fastmovie.ai) | [官方网站](https://www.fastmovieai.com) | [English](./README.en.md)

![NestJS](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php&logoColor=white)
![Webman](https://img.shields.io/badge/Webman-2.1+-00ADD8?style=flat-square)
![Vue](https://img.shields.io/badge/Vue-3.5+-4FC08D?style=flat-square&logo=vue.js&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-5.9+-3178C6?style=flat-square&logo=typescript&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-7.1+-646CFF?style=flat-square&logo=vite&logoColor=white)
![License](https://img.shields.io/badge/License-Apache%202.0-blue?style=flat-square)

</div>

---

## 📖 项目介绍

FastMovieAI 是一个功能完整的开源短剧/短视频创作平台，采用前后端分离架构，提供 AI 驱动的视频内容创作能力。平台集成了用户管理、支付系统、内容管理、视频生成等完整功能模块，适合内容创作者和视频制作团队使用。


<div align="center">
  <img src="./readme_assets/111@1x.png" alt="FastMovieAI 平台展示" width="100%" style="border-radius: 5px;" />
</div>

## 🎯 我们的愿景

FastMovieAI 致力于打造**全球领先的开源短剧创作生态系统**，全面对标商汤 Seko、幻舟、巨日禄、可梦、Oii、麻署、Flova、百度智能创作、火山引擎、阿里云视频 AI 等业界顶尖的商业化短剧创作平台。

### 为什么选择 FastMovieAI？

🌟 **真正的开源自由** - 不同于闭源商业平台的黑盒操作，我们提供完全开放的源代码，让每一位开发者都能深度定制、二次开发，打造专属的短剧创作工具。

💰 **零成本商用** - 无需支付高昂的 SaaS 订阅费用，无需担心按量计费的天价账单，一次部署，永久使用，真正实现降本增效。

🚀 **持续进化** - 我们相信开源的力量，通过社区驱动的方式，快速迭代功能，响应用户需求，让平台始终保持技术领先。

🤝 **共创共赢** - 我们诚挚邀请影视制作公司、短剧团队、MCN 机构、独立创作者、技术开发者加入我们的生态，共同打磨产品细节，分享行业经验，让 FastMovieAI 成为真正懂创作者的工具。

### 我们的承诺

- ✅ **开放透明** - 所有代码开源，所有决策公开，接受社区监督
- ✅ **用户至上** - 认真对待每一条反馈，快速响应每一个需求
- ✅ **技术领先** - 紧跟 AI 技术前沿，持续引入最新的视频生成、语音合成、智能剪辑能力
- ✅ **生态共建** - 提供完善的插件机制，支持第三方开发者贡献功能模块
- ✅ **长期维护** - 承诺长期维护更新，不会因商业化而放弃开源版本

### 加入我们

无论你是：
- 🎬 **影视公司** - 寻求降低制作成本、提升创作效率的解决方案
- 📱 **短剧团队** - 需要批量化、工业化的内容生产工具
- 🎨 **个人创作者** - 想要低成本实现创意想法的独立制作人
- 💻 **技术开发者** - 对 AI 视频技术感兴趣的工程师

我们都欢迎你的加入！让我们一起，用开源的力量，重新定义短剧创作的未来，让每个人都能成为优秀的内容创作者！

### ✨ 核心特性

- 🎬 **AI 视频创作** - 基于 AI 的短剧视频生成和编辑能力
- 🎭 **角色管理** - 虚拟角色创建、配置和管理
- 🎙️ **语音合成** - 多语言语音合成和配音功能
- 📝 **剧本编辑** - 可视化剧本编辑器和分镜头管理
- 💰 **支付集成** - 支持支付宝、微信支付等多种支付方式
- 👥 **用户系统** - 完整的用户注册、登录、VIP 会员体系
- 💎 **积分系统** - 灵活的积分充值和消费机制
- 📱 **微信集成** - 支持微信公众号和小程序对接
- 🔌 **插件架构** - 模块化插件系统，易于扩展
- 🌍 **多语言支持** - 中文和英文界面切换

### 🎯 适用场景

- 短视频内容创作平台
- AI 驱动的视频制作工具
- 短剧创作和分发系统
- 内容创作者社区平台

## 🏗️ 技术架构

### 后端技术栈

- **框架**: Webman v2.1+ (基于 Workerman 的高性能 PHP 框架)
- **PHP 版本**: ≥8.1
- **数据库**: MySQL ≥8.0, Redis
- **ORM**: ThinkORM v2.1+
- **模板引擎**: ThinkTemplate v3.0+
- **支付**: Yansongda/Pay v3.7+ (支付宝、微信支付)
- **视频处理**: php-ffmpeg v1.3+
- **WebSocket**: webman/push v1.1+

### 前端技术栈

- **框架**: Vue 3.5+ (Composition API)
- **构建工具**: Vite 7.1+
- **语言**: TypeScript 5.9+
- **UI 框架**: Element Plus 2.11+
- **状态管理**: Pinia 3.0+
- **路由**: Vue Router 4.5+
- **视频处理**: @webav/av-cliper, mp4box

### 项目结构

```
fastmovieai/
├── fastmovie-admin/    # 后端应用 (PHP/Webman)
│   ├── app/           # 应用代码
│   ├── config/        # 配置文件
│   ├── plugin/        # 插件系统
│   ├── public/        # 公共资源
│   └── start.php      # 启动入口
├── fastmovie-vue/     # 前端应用 (Vue3/TypeScript)
│   ├── src/          # 源代码
│   ├── public/       # 静态资源
│   └── vite.config.ts # Vite 配置
└── README.md         # 项目文档
```

## 📦 环境要求

### 后端环境

- PHP ≥ 8.1
- MySQL ≥ 8.0
- Redis
- 必需的 PHP 扩展：
  - PDO, PDO_MySQL, MySQLi
  - Redis
  - cURL, OpenSSL
  - GD, Fileinfo
  - MBString, JSON
  - Event (推荐，提升性能)

### 前端环境

- Node.js (推荐 LTS 版本)
- npm 或 yarn

## 🚀 快速开始

### 1. 克隆项目

本项目由两个独立的 Git 仓库组成，需要分别克隆：

```bash
# 创建项目目录
mkdir fastmovieai
cd fastmovieai

# 克隆后端仓库
git clone https://gitee.com/yc_open/ai-short-play.git fastmovie-admin

# 克隆前端仓库
git clone https://gitee.com/yc_open/ai-short-play-vue.git fastmovie-vue
```

克隆完成后，目录结构如下：
```
fastmovieai/
├── fastmovie-admin/    # 后端仓库
└── fastmovie-vue/      # 前端仓库
```

### 2. 后端安装

#### 使用 Web 安装向导（推荐）

**步骤 1：配置站点**

如果使用宝塔面板：
1. 创建站点，将运行目录设置为 `fastmovie-admin/public`
2. PHP 版本选择 8.1 或更高

**步骤 2：访问安装向导**

访问 `http://your-domain.com/install`，按照向导完成安装：

1. **许可协议** - 阅读并同意开源协议
2. **环境检测** - 自动检测 PHP 版本、扩展、权限等
3. **参数配置** - 配置数据库和 Redis 连接信息，设置管理员账号
4. **开始安装** - 自动完成数据库初始化和配置文件生成

**安装完成后的操作：**

安装向导会自动完成以下工作：
- 生成 `.env` 配置文件（基于 `.env.example`）
- 自动配置数据库连接池参数
- 生成随机的 PUSH_KEY 和 PUSH_SECRET（32位字符串）
- 同步更新 `nginx.example` 中的 PUSH_KEY
- 创建 `install.lock` 锁定文件防止重复安装

安装完成后，页面会提示您完成以下操作：

**1. 配置伪静态规则**
- 复制 `fastmovie-admin/nginx.example` 文件的全部内容
- 在宝塔面板的"站点设置 → 伪静态"中粘贴并保存
- PUSH_KEY 已自动更新为实际生成的随机值

**2. 启动后端服务**
```bash
cd fastmovie-admin
php start.php start -d
```
或在宝塔面板配置进程守护（推荐）

**3. 删除安装目录**
```bash
# 删除安装目录（重要！）
rm -rf public/install
```

⚠️ **重要提示**：
- 首次登录后立即修改默认管理员密码
- 确保 `.env` 文件权限安全，不要暴露到公网
- `install.lock` 文件用于防止重复安装，请勿删除

#### 手动安装（高级用户）

如果不使用 Web 安装向导，可以手动配置：

```bash
cd fastmovie-admin

# 1. 复制环境配置文件
copy .env.example .env

# 2. 编辑 .env 文件，配置以下信息：
# 数据库配置
# - DATABASE_HOST=127.0.0.1
# - DATABASE_PORT=3306
# - DATABASE_NAME=your_database
# - DATABASE_USERNAME=your_username
# - DATABASE_PASSWORD=your_password
# - DATABASE_PREFIX=php_
# 
# 数据库连接池配置（必需）
# - DATABASE_MAX_CONNECTIONS=10
# - DATABASE_MIN_CONNECTIONS=1
# - DATABASE_WAIT_TIMEOUT=3
# - DATABASE_IDLE_TIMEOUT=60
# - DATABASE_HEARTBEAT_INTERVAL=50
#
# Redis 配置
# - REDIS_HOST=127.0.0.1
# - REDIS_PORT=6379
# - REDIS_PASSWORD=
# - REDIS_DATABASE=2
#
# WebSocket 推送配置
# - PUSH_KEY=生成32位随机字符串
# - PUSH_SCERET=生成32位随机字符串
# - PUSH_API_PORT=37000
# - PUSH_WSS_PORT=37001

# 3. 导入数据库
mysql -u root -p your_database < database.sql

# 4. 更新 nginx.example 中的 PUSH_KEY
# 将 /app/PUSH_KEY 替换为 /app/你的实际PUSH_KEY值
```

### 3. 前端安装

```bash
cd fastmovie-vue

# 安装依赖
npm install

# 配置后端 API 地址
# 编辑 .env.development 文件，设置 VITE_API_URL
```

### 4. 启动服务

**后端服务**

```bash
cd fastmovie-admin

# 开发模式启动
php start.php start

# 守护进程模式（生产环境）
php start.php start -d

# 停止服务
php start.php stop

# 重启服务
php start.php restart

# 查看状态
php start.php status
```

**前端服务**

```bash
cd fastmovie-vue

# 启动开发服务器（默认端口 36310）
npm run dev

# 构建生产版本
npm run build
```

### 5. 访问系统

- **前端地址**: http://localhost:36310
- **后端 API**: http://localhost:36999
- **默认管理员账号**: admin
- **默认密码**: 123456

⚠️ **安全提示**: 首次登录后请立即修改默认密码！

## 🔧 生产环境部署

### 宝塔面板部署（推荐）

1. **创建站点**
   - 在宝塔面板中创建新站点
   - 将站点运行目录设置为 `fastmovie-admin/public`
   - 配置 PHP 版本为 8.1 或更高

2. **配置伪静态**

在站点设置的"伪静态"选项中，复制 `fastmovie-admin/nginx.example` 文件的全部内容并保存。

**nginx.example 完整内容：**

```nginx
# 站内信推送
location /app/PUSH_KEY {
    proxy_pass http://127.0.0.1:36302;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header X-Real-IP $remote_addr;
}

# 将请求转发到webman
location ^~ / {
    # 接口QPS插件，nginx版本需要选择：nginx openresty
    # 如需使用接口QPS插件，取消注释
    # access_by_lua_file $document_root/lua/qps.lua;
    proxy_set_header Host $http_host;
    proxy_set_header X-Forwarded-For $remote_addr;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_http_version 1.1;
    proxy_set_header Connection "";
    if (!-f $request_filename){
        proxy_pass http://127.0.0.1:36300;
    }
}

# 拒绝访问所有以 .php 结尾的文件
location ~ \.php$ {
    return 404;
}

# 允许访问 .well-known 目录
location ~ ^/\.well-known/ {
  allow all;
}

# 拒绝访问所有以 . 开头的文件或目录
location ~ /\. {
    return 404;
}
```

⚠️ **重要说明**：
- `/app/PUSH_KEY` 中的 `PUSH_KEY` 在安装完成后会自动替换为实际的 32 位随机字符串
- 如果手动安装，需要将 `PUSH_KEY` 替换为 `.env` 文件中的实际值
- `proxy_pass http://127.0.0.1:36300` 中的端口需要与 `.env` 中的 `SERVER_PORT` 一致（默认 36999）

3. **启动后端服务**

```bash
cd /www/wwwroot/your-site/fastmovie-admin
php start.php start -d
```

4. **配置进程守护**

在宝塔面板的"进程守护"中添加：
- 名称: FastMovieAI
- 启动命令: `cd /www/wwwroot/your-site/fastmovie-admin && php start.php start`
- 运行目录: `/www/wwwroot/your-site/fastmovie-admin`

5. **访问安装向导**

访问 `http://your-domain.com/install` 完成安装配置。安装程序会自动：
- 生成 `.env` 配置文件
- 创建数据库表结构
- 生成随机的 PUSH_KEY 和 PUSH_SECRET
- 同步更新 `nginx.example` 中的 PUSH_KEY
- 创建管理员账号

安装完成后，记得删除 `public/install` 目录。

### 手动部署

#### 后端部署

1. **配置 Nginx 反向代理**

参考 `fastmovie-admin/nginx.example` 配置文件，完整配置示例：

```nginx
upstream fastmovie {
    server 127.0.0.1:36999;
    keepalive 10240;
}

server {
    listen 80;
    server_name your-domain.com;
    root /path/to/fastmovie-admin/public;
    index index.html index.htm;

    # 站内信推送（PUSH_KEY 需要替换为实际值）
    location /app/YOUR_ACTUAL_PUSH_KEY {
        proxy_pass http://127.0.0.1:37000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header X-Real-IP $remote_addr;
    }

    # 将请求转发到 webman
    location ^~ / {
        proxy_set_header Host $http_host;
        proxy_set_header X-Forwarded-For $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        if (!-f $request_filename){
            proxy_pass http://fastmovie;
        }
    }

    # 拒绝访问所有以 .php 结尾的文件
    location ~ \.php$ {
        return 404;
    }

    # 允许访问 .well-known 目录
    location ~ ^/\.well-known/ {
        allow all;
    }

    # 拒绝访问所有以 . 开头的文件或目录
    location ~ /\. {
        return 404;
    }
}
```

⚠️ **配置说明**：
- `YOUR_ACTUAL_PUSH_KEY` 需要替换为 `.env` 文件中的实际 PUSH_KEY 值
- upstream 端口（36999）需要与 `.env` 中的 SERVER_PORT 一致
- WebSocket 推送端口（37000）需要与 `.env` 中的 PUSH_API_PORT 一致

2. **启动后端服务**

```bash
cd fastmovie-admin
php start.php start -d
```

3. **配置进程守护**

使用 systemd 或 supervisor 管理进程，确保服务自动重启。

**systemd 示例** (`/etc/systemd/system/fastmovie.service`)：

```ini
[Unit]
Description=FastMovieAI Backend Service
After=network.target

[Service]
Type=forking
User=www-data
WorkingDirectory=/path/to/fastmovie-admin
ExecStart=/usr/bin/php start.php start -d
ExecStop=/usr/bin/php start.php stop
ExecReload=/usr/bin/php start.php restart
Restart=always

[Install]
WantedBy=multi-user.target
```

启用服务：
```bash
sudo systemctl enable fastmovie
sudo systemctl start fastmovie
```

### 前端部署

1. **构建生产版本**

```bash
cd fastmovie-vue
npm run build
```

2. **部署构建文件**

构建完成后，将生成的文件部署到 `fastmovie-admin/public/assets/` 目录，或配置独立的静态文件服务器。

3. **配置 Nginx**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/fastmovie-admin/public;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass http://127.0.0.1:36999;
    }
}
```

## 📚 使用说明

### 开发文档

- [后端开发文档](./docs/backend-development.md) - PHP/Webman 后端开发指南
- [前端开发文档](./docs/frontend-development.md) - Vue3/TypeScript 前端开发指南

### 插件系统

FastMovieAI 采用模块化插件架构，核心插件包括：

- **user** - 用户管理和认证
- **finance** - 支付和财务管理
- **marketing** - 营销和推广工具
- **article** - 内容管理系统
- **shortplay** - 短剧创作核心功能
- **model** - AI 模型管理
- **notification** - 通知和消息推送
- **control** - 平台控制和配置

每个插件都是独立的功能模块，可以根据需要启用或禁用。

### 开发命令

**后端常用命令**

```bash
# 查看所有命令
php webman

# 数据库迁移（如果支持）
php webman migrate

# 清除缓存
php webman cache:clear
```

**前端常用命令**

```bash
# 开发服务器
npm run dev

# 生产构建
npm run build

# 预览构建结果
npm run preview

# 类型检查
vue-tsc --noEmit
```

### 端口配置

- **后端服务**: 36999 (可在 .env 中配置 SERVER_PORT)
- **前端开发**: 36310
- **WebSocket Push**: 37000
- **WebSocket WSS**: 37001

## 🤝 参与贡献

我们欢迎所有形式的贡献，包括但不限于：

1. 🐛 提交 Bug 报告
2. 💡 提出新功能建议
3. 📝 改进文档
4. 🔧 提交代码修复或新功能

### 贡献流程

1. Fork 对应的仓库（后端或前端）
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m '添加某个很棒的功能'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 提交 Pull Request

**注意**: 后端和前端是独立的仓库，请根据修改内容向对应的仓库提交 PR。

### 开发规范

- 后端代码遵循 PSR-12 编码规范
- 前端代码使用 ESLint 和 Prettier 格式化
- 提交信息使用中文，清晰描述改动内容
- 添加必要的代码注释和文档

## 📄 开源协议

本项目采用 [Apache License 2.0](./LICENSE) 开源协议。

## 🔗 相关链接

- [在线演示](https://fastmovie.ai)
- [后端仓库 (Gitee)](https://gitee.com/yc_open/ai-short-play)
- [前端仓库 (Gitee)](https://gitee.com/yc_open/ai-short-play-vue)
- [后端开发文档](./docs/backend-development.md)
- [前端开发文档](./docs/frontend-development.md)
- [问题反馈](https://gitee.com/yc_open/ai-short-play/issues)

## ⚠️ 免责声明

本项目仅供学习和研究使用，使用本项目所产生的一切后果由使用者自行承担。请遵守相关法律法规，不得用于非法用途。

## ⚠️ 优秀开源项目推荐

- [imaiwork](https://gitee.com/imai-work/imaiwork)
- [BuildingAI](https://gitee.com/BidingCC/BuildingAI)
- [aiflowy](https://gitee.com/aiflowy/aiflowy)

## 💬 联系方式

如有问题或建议，欢迎通过以下方式联系：

- 提交 Issue: 
  - 后端问题: [ai-short-play Issues](https://gitee.com/yc_open/ai-short-play/issues)
  - 前端问题: [ai-short-play-vue Issues](https://gitee.com/yc_open/ai-short-play-vue/issues)
- 邮箱: 416716328@qq.com
- 在线演示: [https://fastmovie.ai](https://fastmovie.ai)

### 加入社区

<div align="center">

<table>
  <tr>
    <td align="center">
      <img src="./readme_assets/qrcode_2.png" width="200" alt="用户交流群" />
      <br />
      <b>用户交流群</b>
      <br />
      <span>扫码加入，与创作者交流</span>
    </td>
    <td align="center">
      <img src="./readme_assets/qrcode_1.png" width="200" alt="商务合作" />
      <br />
      <b>技术咨询 & 商务合作</b>
      <br />
      <span>扫码联系，洽谈合作</span>
    </td>
  </tr>
</table>

</div>

---

<div align="center">

**如果这个项目对你有帮助，请给我们一个 ⭐️ Star！**

Made with ❤️ by FastMovieAI Team

</div>
