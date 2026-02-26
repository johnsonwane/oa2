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
- `GET/POST/PUT/DELETE /api/orders.php` 订单 CRUD
- `GET/POST/DELETE /api/finance.php` 财务记录与统计
- `GET/POST/PUT/DELETE /api/todos.php` 待办 CRUD
- `GET/POST/PUT/DELETE /api/notifications.php` 通知 CRUD
- `GET/POST/PUT/DELETE /api/users.php` 用户 CRUD
- `GET /api/dashboard_summary.php` 统计汇总
- `GET/POST/PUT/DELETE /api/rbac_groups.php` 用户组 CRUD
- `GET/POST/PUT/DELETE /api/rbac_permissions.php` 权限 CRUD
- `GET/POST/DELETE /api/rbac_assign.php` 用户组分配、权限组分配

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
- 二级菜单包含：用户管理、菜单管理、用户组管理、权限管理、RBAC分配。
- 可进行用户、用户组、权限、用户组-用户分配、用户组-权限分配的增删改查。


## 学员与用户资料扩展

- 学员资料已扩展为常见培训行业字段：性别、生日、手机号、微信、身份证号、意向等级、跟进状态、来源渠道、报课名称（数组）、顾问、交付教练、监护人信息、地址、备注等。
- 用户资料已扩展为常见组织字段：性别、手机号、邮箱、身份证号、部门、岗位、入职日期、备注等。
- 前端 `dashboard.html` 已支持学员与用户的编辑（点击列表“编辑”回填表单后保存）。

- 学员资料新增：报课名称（数组）、交付教练；并移除校区、班级字段。
