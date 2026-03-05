# 稳定优先窗口（Week 1）执行看板

> 目标：落实“先稳定后迭代”，每个动作可执行、可验证、可追踪。

## 1) A：运行时补结构调用清单（ensure_* / ALTER）与风险分级

### 高风险（请求链路中包含 DDL 或高频触发结构补丁）
| 文件 | 调用点 | 风险说明 | 等级 |
|---|---|---|---|
| `api/orders.php` | `ensure_order_referrer_schema` / `ensure_business_workflow_schema` / `ensure_table_columns` | 订单是高频接口，请求时触发补结构，易造成抖动或锁等待 | 高 |
| `api/order_referrer_bootstrap.php` | 多处 `ALTER TABLE oa_order ...` | 存在直接 DDL，权限/锁/慢 SQL 风险高 | 高 |
| `api/profile_bootstrap.php` | `ensure_table_columns` + `ALTER TABLE` | 学员/员工相关接口路径频繁，历史库差异下风险高 | 高 |

### 中风险（请求链路中触发 ensure_*，但主要为建表/补字段兜底）
| 文件 | 调用点 | 风险说明 | 等级 |
|---|---|---|---|
| `api/students.php` | `ensure_student_user_profile_columns` / `ensure_business_workflow_schema` | 查询入口多，若频繁触发 bootstrap 存在性能风险 | 中 |
| `api/users.php` | `ensure_student_user_profile_columns` / `ensure_hr_schema` | 员工管理常用，结构兜底逻辑仍在请求链路 | 中 |
| `api/receipts.php` | `ensure_business_workflow_schema` / `ensure_hr_schema` / `ensure_table_columns` | 业务接口里包含补结构调用 | 中 |
| `api/referrers.php` | `ensure_order_referrer_schema` / `ensure_table_columns` | 推荐者接口可触发结构补齐 | 中 |
| `api/user_menus.php` / `api/rbac_*` | `ensure_rbac_tables` | 权限入口调用，虽非高频 DDL 但仍属运行期初始化 | 中 |

### 低风险（仅元数据探测/兼容查询）
| 文件 | 调用点 | 风险说明 | 等级 |
|---|---|---|---|
| `api/login.php` / `api/dashboard_summary.php` / `api/user_menus.php` / `api/orders.php` | `SHOW COLUMNS` / 列存在性判断 | 主要用于兼容查询，不直接改结构 | 低 |
| `api/health.php` | `information_schema.TABLES` 查询 | 只读健康检查，不改结构 | 低 |

---

## 2) A + D：上线前健康检查 SOP（以 `/api/health.php` 为入口）

## 检查目标
1. DB 连通是否正常。
2. 当前数据库名是否正确。
3. 核心表是否齐全：`oa_user`、`oa_student`、`oa_course`、`oa_order`。
4. 返回结构是否符合规范（便于平台监控解析）。

## 执行步骤（上线前）
1. 部署完成后执行：
   - `curl -sS -i https://<域名>/api/health.php`
2. 验证 HTTP 状态码与 JSON 字段：
   - 成功：`code=0`, `data.db_connected=true`
   - 失败：`code!=0` 且包含 `db_connected=false`、错误信息
3. 若失败：
   - 检查 `DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS`
   - 检查目标库网络连通与账户权限
   - 检查核心表是否存在
4. 完成记录：将输出截图/日志附到发布单。

## 通过标准
- 健康检查返回成功；或失败原因可解释且已回滚/阻断发布。

---

## 3) C：P0 问题基线清单（当前）

| 编号 | 问题 | 影响 | 当前状态 | 备注 |
|---|---|---|---|---|
| P0-01 | 运行时补结构导致 500（权限/锁/元数据异常） | 核心接口可用性 | 未彻底关闭 | 已有容错，仍需迁移到发布期脚本 |
| P0-02 | 导入后乱码（Excel 编辑后编码变化） | 数据可读性 | 已缓解 | 前端已做 UTF-8/GB18030 回退 |
| P0-03 | 斜线日期导入失败 | 导入可用性 | 已缓解 | 前端已做 `YYYY/M/D` 规范化 |
| P0-04 | 多 bootstrap 逻辑分散导致回归难 | 变更风险 | 处理中 | 需统一迁移框架 |

---

## 4) PM：稳定优先窗口管理动作

## 管控策略
- 冻结新增需求（仅处理 P0/P1 稳定性任务）。
- 每日站会同步：阻塞点、风险点、验证结果。
- 每次变更执行：**改一小步 -> 验证 -> 通过后继续下一步**。

## 解除冻结条件
- 连续 7 天无 P0 新增事故。
- 健康检查与关键接口回归稳定通过。
- 迁移与回滚演练至少完成 1 次闭环。
