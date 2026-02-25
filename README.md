# OA2

培训公司 OA 样例（前后端分离）：

- 后端：`api/`（原生 PHP）
- H5 前端：`h5/`
- uni-app 前端样例：`uniapp/`
- 数据库脚本：`db/schema.sql`

## H5 页面（按常见 OA 管理台）

- `h5/login.html`：登录页
- `h5/dashboard.html`：管理台（左侧菜单 + 右侧模块）
  - 总览
  - 学员管理
  - 课程管理
  - 订单管理
  - 财务管理
  - 待办管理
  - 通知管理
  - 用户管理
  - 菜单管理

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

## 数据库配置

`api/config.php`：

- 默认数据库名：`oa2`
- 支持环境变量覆盖：`DB_HOST`、`DB_PORT`、`DB_NAME`、`DB_USER`、`DB_PASS`

## 初始化与测试数据

执行：

```bash
mysql -uroot -p < db/schema.sql
```

`schema.sql` 会创建以下业务表并写入测试数据：

- `oa_user`
- `oa_menu`
- `oa_student`
- `oa_course`
- `oa_order`
- `oa_finance_record`
- `oa_todo`
- `oa_notification`

默认登录账号：`admin / 123456`
