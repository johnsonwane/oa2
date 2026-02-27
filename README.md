# OA2

培训公司 OA 样例（前后端分离）：

- 后端：`api/`（原生 PHP）
- H5 前端页面：仓库根目录
- 数据库脚本：`db/schema.sql`

## H5 页面入口

- `login.html`：登录页
- `dashboard.html`：管理台（左侧菜单 + 右侧模块）
- `assets/style.css`：样式文件

## API 接口

- `POST /api/login.php` 登录
- `GET/POST/PUT/DELETE /api/menu.php` 菜单 CRUD
- `GET/POST/PUT/DELETE /api/students.php` 学员 CRUD
- `GET/POST/PUT/DELETE /api/courses.php` 课程 CRUD
- `GET/POST/PUT/DELETE /api/orders.php` 订单 CRUD（支持定金/中期款/尾款/全款与分成字段）
- `GET/POST/PUT/DELETE /api/finance.php` 财务记录与统计（支持编辑）
- `GET/POST/PUT/DELETE /api/todos.php` 待办 CRUD
- `GET/POST/PUT/DELETE /api/notifications.php` 通知 CRUD
- `GET/POST/PUT/DELETE /api/users.php` 用户 CRUD
- `GET/POST/PUT/DELETE /api/referrers.php` 推荐者 CRUD
- `GET /api/user_menus.php?user_id=xx` 按用户返回可见菜单（RBAC菜单过滤）
- `GET /api/dashboard_summary.php` 统计汇总
- `GET/POST/PUT/DELETE /api/rbac_groups.php` 角色 CRUD
- `GET/POST/PUT/DELETE /api/rbac_permissions.php` 权限 CRUD
- `GET/POST/DELETE /api/rbac_assign.php` 角色分配、角色权限分配

## 数据库配置

`api/config.php`：

- 默认数据库名：`oa2`
- 支持环境变量覆盖：`DB_HOST`、`DB_PORT`、`DB_NAME`、`DB_USER`、`DB_PASS`

## 初始化与测试数据

执行：

```bash
mysql -uroot -p < db/schema.sql
```

默认登录账号：`admin / 123456`


## 系统管理（RBAC）

- 一级菜单新增：`系统管理`。
- 二级菜单包含：用户管理、菜单管理、角色管理、权限管理、RBAC分配。
- 可进行用户、角色、权限、用户-角色分配、角色-权限分配的增删改查。


## 学员与用户资料扩展

- 学员资料已扩展为常见培训行业字段：性别、生日、手机号、微信、身份证号、意向等级、跟进状态、来源渠道、报课名称（数组）、顾问、交付教练、监护人信息、地址、备注等。
- 用户资料已扩展为常见组织字段：性别、手机号、邮箱、身份证号、部门、岗位、入职日期、备注等。
- 前端 `dashboard.html` 已支持学员与用户的编辑（点击列表“编辑”回填表单后保存）。

- 学员资料新增：报课名称（数组）、交付教练；并移除校区、班级字段。


- 兼容旧库：`api/profile_bootstrap.php` 会在学员/用户接口首次访问时自动补齐新增字段，避免因历史表结构导致“无法编辑”。


## 面向复杂场景的增强

- `students.php` 支持按 `keyword`、`follow_status` 过滤；支持分页模式：`?paged=1&page=1&page_size=20`。
- `orders.php` 支持按 `keyword`、`pay_status` 过滤与分页，并在创建/更新时校验学员与课程是否存在。
- `orders.php` 在未传 `amount` 时会自动回填课程价格，减少人工录入错误。
- `finance.php` 新增 `PUT` 更新能力，并支持按 `record_type`、`from_date`、`to_date` 的筛选与分页。
- `students.php` 对手机号增加重复校验（创建与编辑均生效），降低重复线索/重复学员风险。
- `users.php` 与 `referrers.php` 支持 `keyword` 检索，并支持 `paged=1` 分页返回，适合数据量较大场景。

- 订单支持付款阶段（定金/中期款/尾款/全款）、销售分成与推荐者分成。
- 新增推荐者管理：可维护推荐者姓名、手机、渠道、默认分成比例，并在订单中选择推荐者自动计算分成。

- 已支持按角色/权限返回登录后可见菜单，不同用户登录展示不同左侧菜单。

- 学员信息中改为“微信ID”为必填，姓名改为可选，减少录入歧义。
- 角色语义升级：顾问仅管理未成交准学员；班主任管理全部学员和销课信息；教练仅管理自己跟进学员的销课记录。
