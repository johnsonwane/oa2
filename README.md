# OA2

培训公司 OA 样例（前后端分离）：

- 后端：`api/`（原生 PHP）
- H5 前端页面：仓库根目录
- 数据库脚本：`db/schema.sql`

## H5 页面入口

- `login.html`：登录页
- `assets/style.css`：样式文件

- 独立菜单页面（按模块拆分）：`overview.html`、`students.html`、`courses.html`、`orders.html`、`referrers.html`、`receipts.html`、`delivery_logs.html`、`department_stats.html`、`finance.html`、`todos.html`、`notifications.html`、`users.html`、`departments.html`、`menus.html`、`rbac_groups.html`、`rbac_permissions.html`、`rbac_assign.html`
- Sprint123 扩展页面（本轮新增 17 个）：`materials.html`、`material_campaigns.html`、`material_claims.html`、`contracts.html`、`invoice_profiles.html`、`invoices.html`、`class_terms.html`、`student_terms.html`、`shipments.html`、`certificate_templates.html`、`certificate_issues.html`、`commission_rules.html`、`commission_scopes.html`、`commission_calcs.html`、`commission_adjustments.html`、`payroll_periods.html`、`payroll_slips.html`

## API 接口

- `POST /api/login.php` 登录
- `POST /api/logout.php` 退出登录（需 Bearer Token）
- `GET/POST/PUT/DELETE /api/menu.php` 菜单 CRUD
- `GET/POST/PUT/DELETE /api/students.php` 学员 CRUD
- `GET/POST/PUT/DELETE /api/courses.php` 课程 CRUD
- `GET/POST/PUT/DELETE /api/orders.php` 订单 CRUD（支持定金/中期款/尾款/全款与分成字段）
- `orders.php` 已增加旧库兼容补列（`oa_order`/`oa_user`/`oa_student` 必要字段），可降低历史库直接升级时的 500 风险。
- `GET/POST/PUT/DELETE /api/finance.php` 财务记录与统计（支持编辑）
- `GET/POST/PUT/DELETE /api/todos.php` 待办 CRUD
- `GET/POST/PUT/DELETE /api/notifications.php` 通知 CRUD
- `GET/POST/PUT/DELETE /api/users.php` 员工 CRUD
- `GET/POST/PUT/DELETE /api/departments.php` 部门 CRUD
- `GET /api/meta_options.php` 下拉选项（角色/部门/职位/收款渠道）
- `GET/POST/PUT/DELETE /api/referrers.php` 推荐者 CRUD
- `GET/POST/PUT/DELETE /api/receipts.php` 收款单 CRUD（用于订单核对）
- `GET/POST/PUT/DELETE /api/delivery_logs.php` 教练交付记录 CRUD
- `GET /api/department_stats.php?user_id=xx` 部门经营统计（老板看全局，部门经理看本部门）
- `GET /api/user_menus.php?user_id=xx` 按用户返回可见菜单（RBAC菜单过滤）
- `GET /api/dashboard_summary.php` 统计汇总
- `GET /api/health.php` 健康检查（DB连通/关键表）
- `GET/POST/PUT/DELETE /api/rbac_groups.php` 角色 CRUD
- `GET/POST/PUT/DELETE /api/rbac_permissions.php` 权限 CRUD
- `GET/POST/DELETE /api/rbac_assign.php` 角色分配、角色权限分配

- `GET/POST/PUT/DELETE /api/materials.php` 资料库
- `GET/POST/PUT/DELETE /api/material_campaigns.php` 引流活动
- `GET/POST/PUT/DELETE /api/material_claims.php` 资料领取记录
- `GET/POST/PUT/DELETE /api/contracts.php` 合同管理
- `GET/POST/PUT/DELETE /api/invoice_profiles.php` 开票资料
- `GET/POST/PUT/DELETE /api/invoices.php` 发票管理
- `GET/POST/PUT/DELETE /api/class_terms.php` 课程期次
- `GET/POST/PUT/DELETE /api/student_terms.php` 学员-期次关系
- `GET/POST/PUT/DELETE /api/shipments.php` 实体资料邮寄
- `GET/POST/PUT/DELETE /api/commission_rules.php` 分成规则
- `GET/POST/PUT/DELETE /api/commission_adjustments.php` 分成调节
- `GET/POST/PUT/DELETE /api/payroll_periods.php` 薪资期间
- `GET/POST/PUT/DELETE /api/payroll_slips.php` 工资条
- `GET/POST/PUT/DELETE /api/expense_vouchers.php` 支出单
- `GET/POST/PUT/DELETE /api/payment_callback_logs.php` 支付回调日志
- `GET/POST/PUT/DELETE /api/certificate_templates.php` 证书模板
- `GET/POST/PUT/DELETE /api/certificate_issues.php` 证书发放
- `GET/POST/PUT/DELETE /api/commission_scopes.php` 分成规则作用范围
- `GET/POST/PUT/DELETE /api/commission_calcs.php` 分成计算明细
- `GET/POST/PUT/DELETE /api/payroll_items.php` 工资条明细
- `GET/POST/PUT/DELETE /api/salary_payment_logs.php` 工资发放记录


## 整改方案与执行进展（分步实施）

- Week1 执行看板：`docs/stability_window_week1.md`（按周/按人/按风险）

为降低线上 500 风险并提升可维护性，采用“小步快跑、每步验证通过后继续下一步”的执行方式：

### Phase 1：稳定性（进行中）
1. 将运行期高风险点前置可观测（健康检查）。
2. 梳理并逐步替换“请求时自动改表”为“发布时迁移”。

### Phase 2：数据与接口标准化（待执行）
1. 统一日期/编码/导入导出规范。
2. 将导入导出能力逐步后端化（保留前端入口）。

### Phase 3：前端结构治理（待执行）
1. 继续收敛独立页面公共能力，减少重复脚本。
2. 统一新增/编辑/查看的表单 schema 与交互。

### 当前已执行的小步（含验证）
- [x] 新增 `GET /api/health.php`，用于快速检查 DB 连通与关键表存在性。
- [x] 导入兼容斜线日期格式（`YYYY/M/D` 与 `YYYY/M/D HH:mm[:ss]`）。
- [x] 导入兼容常见编码（UTF-8 / GB18030 回退）。

### `/api/health.php` 返回示例
```json
{
  "code": 0,
  "message": "ok",
  "data": {
    "db_connected": true,
    "database": "oa2",
    "tables": {
      "oa_user": true,
      "oa_student": true,
      "oa_course": true,
      "oa_order": true
    }
  }
}
```

## 数据库配置

`api/config.php`：

- 默认数据库地址：`10.0.0.7:3306`，数据库名：`oa2`
- 支持环境变量覆盖：`DB_HOST`、`DB_PORT`、`DB_NAME`、`DB_USER`、`DB_PASS`

## 初始化与测试数据

执行：

```bash
mysql -uroot -p < db/schema.sql
```

默认登录账号：`admin / 123456`

> 若页面提示 `请求失败（HTTP 500 Internal Server Error）`，请优先检查 `api/config.php` 或环境变量中的数据库连接（`DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS`）是否与本机 MySQL 一致。


## 系统管理（RBAC）

- 一级菜单新增：`系统管理`。
- 二级菜单包含：员工管理、菜单管理、角色管理、权限管理、RBAC分配。
- 可进行用户、角色、权限、用户-角色分配、角色-权限分配的增删改查。


## 学员与用户资料扩展

- 学员资料已扩展为常见培训行业字段：性别、生日、手机号、微信、身份证号、意向等级、跟进状态、来源渠道、报课名称（数组）、顾问、交付教练、监护人信息、地址、备注等。
- 员工资料已扩展为常见组织字段：性别、手机号、邮箱、身份证号、部门、岗位、入职日期、备注等。
- 前端独立页面已支持学员与员工编辑（点击列表“编辑”回填表单后保存）。

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


- 角色边界进一步明确：班主任负责学员服务与排课/教练安排，财务负责收款数据上报与统计核对，两者已拆分为独立角色。
- 学员分类建议与落地：`lead`（线索）、`pending_payment`（待缴费学员）、`active`（正式在读学员），`students.php` 支持 `student_category` 参数分类管理。
- 订单支持记录“销售归属人/销售角色”（顾问、教练、班主任）与销售分成，支持教练卖课后的业绩核算。


## Sprint 规划文档

- 业务与技术分期设计：`docs/sprint123_roadmap.md`
- 扩展建表脚本：`db/sprint123_extension.sql`
- 流程缺口复盘（第二轮）：`docs/flow-gap-review.md`
- 顾问到交付角色旅程梳理：`docs/role-journey-consultant-to-delivery.md`


## 鉴权

- 除 `login.php`、`health.php` 外，其他 API 均需携带请求头：`Authorization: Bearer <token>`。
- 登录后返回的 token 默认 7 天过期。
- 会话数据存储在 `oa_session` 表中。
