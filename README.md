# OA2

这是一个培训公司 OA 系统样例，采用前后端分离：

- 前端（uni-app Vue3 项目）放在 `uniapp/`
- 后端（PHP 无框架）放在 `api/`

## 前端技术栈

- uni-app
- Vue 3（`<script setup>` 组合式 API）

## 项目结构

```text
.
├── api/
│   └── login.php
└── uniapp/
    ├── App.vue
    ├── main.js
    ├── manifest.json
    ├── package.json
    ├── pages.json
    ├── uni.scss
    ├── pages/
    │   ├── login/login.vue
    │   └── dashboard/dashboard.vue
    └── utils/
        └── request.js
```

## 样例功能

- 登录页：`uniapp/pages/login/login.vue`
- 仪表盘页：`uniapp/pages/dashboard/dashboard.vue`
- 登录接口：`POST /api/login.php`
- 样例账号：`admin / 123456`

> 说明：当前登录逻辑是样例实现，账号密码为硬编码。生产环境请改为数据库用户体系与安全认证方案。

## 可直接运行的 H5 版本

为便于快速调试，新增了不依赖 HBuilderX 构建的静态页面：

- `h5/login.html`
- `h5/dashboard.html`
- `h5/assets/style.css`

在站点根目录部署后，可直接访问：`/h5/login.html`。

## Dashboard 菜单管理功能（H5）

已新增完整菜单管理样例（增删改查）：

- 数据库脚本：`db/schema.sql`
- 后端接口：`api/menu.php`
- 数据库连接：`api/db.php`（读取 `api/config.php`）
- 前端页面：`h5/dashboard.html`

### 菜单接口说明

- `GET /api/menu.php`：查询菜单列表
- `POST /api/menu.php`：新增菜单
- `PUT /api/menu.php`：更新菜单
- `DELETE /api/menu.php?id=1`：删除菜单

### 数据库初始化

```bash
mysql -uroot -proot < db/schema.sql
```


### 数据库配置文件

数据库配置位于 `api/config.php`，并支持通过环境变量覆盖：`DB_HOST`、`DB_PORT`、`DB_NAME`、`DB_USER`、`DB_PASS`。


### 页面布局

- 仪表盘采用常见 OA 的 **左侧菜单 + 右侧内容区** 布局。
- 左侧菜单按你提供的一级/二级清单分组展示（运营、顾问、交付、人力、财务、行政、系管、超管、我的、常用等）。
- 支持一键“初始化菜单到数据库”，写入 `oa_menu`。

- 菜单内容已按你给定的“一级菜单/二级菜单”清单预置到左侧导航与 `db/schema.sql`（去重后 65 条）。
