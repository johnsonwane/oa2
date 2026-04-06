<?php
/**
 * 分成计算引擎
 *
 * 业务规则：
 * - 班期销课（status=completed）时触发分成计算
 * - 顾问/教练各自按月累计业绩匹配阶梯比例
 * - 订单类型（首单/续单/增课）影响基础规则匹配
 * - 计算结果写入 oa_commission_calc，状态 = pre_final（预提，月底终算）
 */

require_once __DIR__ . '/db.php';

/**
 * 获取用户在当月的已付款订单总金额（按角色区分seller_user_id）
 */
function commission_get_monthly_paid_amount(PDO $pdo, int $userId, string $roleType, string $yearMonth = ''): float
{
    if ($yearMonth === '') {
        $yearMonth = date('Y-m');
    }
    $start = $yearMonth . '-01';
    $end   = date('Y-m-t', strtotime($start));

    // 角色 → seller_role 字段值
    $sellerRoleMap = [
        'consultant' => '顾问',
        'coach'      => '教练',
    ];
    $sellerRole = $sellerRoleMap[$roleType] ?? $roleType;

    $sql = "SELECT COALESCE(SUM(o.paid_amount), 0)
            FROM oa_order o
            WHERE o.seller_user_id = ?
              AND o.seller_role   = ?
              AND o.pay_status    >= 1
              AND o.payment_time  >= ?
              AND o.payment_time  <= ? + INTERVAL 1 DAY";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $sellerRole, $start, $end]);
    return (float)$stmt->fetchColumn();
}

/**
 * 根据用户ID和角色类型获取当月阶梯
 * @return array{rate:float, tier_id:int|null, tier_name:string, monthly_amount:float}|null
 */
function commission_get_tier_for_user(PDO $pdo, int $userId, string $roleType): ?array
{
    $monthlyAmount = commission_get_monthly_paid_amount($pdo, $userId, $roleType);
    $now = date('Y-m-d');

    $sql = "SELECT id, tier_name, rate
            FROM oa_commission_tier
            WHERE role_type = ?
              AND status    = 1
              AND (start_date IS NULL OR start_date <= ?)
              AND (end_date   IS NULL OR end_date   >= ?)
              AND min_amount <= ?
              AND (max_amount IS NULL OR max_amount >= ?)
            ORDER BY min_amount DESC
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$roleType, $now, $now, $monthlyAmount, $monthlyAmount]);
    $row = $stmt->fetch();

    if (!$row) return null;

    return [
        'rate'           => (float)$row['rate'],
        'tier_id'        => (int)$row['id'],
        'tier_name'      => (string)$row['tier_name'],
        'monthly_amount' => $monthlyAmount,
    ];
}

/**
 * 获取基础分成规则比例兜底（无法命中阶梯时使用）
 * @return array{rule_id:int|null, rate:float}|null
 */
function commission_get_fallback_rule(PDO $pdo, string $roleType, string $orderType): ?array
{
    $now = date('Y-m-d');
    // role_type 映射：顾问首单=consultant, 续单=consultant_renewal, 增课=consultant_upgrade
    $ruleTypeMap = [
        'consultant'         => ['first', 'consultant'],
        'consultant_renewal' => ['renewal', 'consultant_renewal'],
        'consultant_upgrade' => ['upgrade', 'consultant_upgrade'],
        'coach'              => ['first', 'coach'],
        'coach_renewal'      => ['renewal', 'coach_renewal'],
        'coach_upgrade'      => ['upgrade', 'coach_upgrade'],
    ];

    $mapped = $ruleTypeMap[$roleType] ?? null;
    if (!$mapped) return null;

    [$ot, $rt] = $mapped;

    // 如果 order_type 不匹配，跳过
    if ($ot !== $orderType) return null;

    $sql = "SELECT id, rate
            FROM oa_commission_rule
            WHERE role_type   = ?
              AND commission_type = 'rate'
              AND status      = 1
              AND (start_date IS NULL OR start_date <= ?)
              AND (end_date   IS NULL OR end_date   >= ?)
            ORDER BY priority_no ASC
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rt, $now, $now]);
    $row = $stmt->fetch();

    if (!$row) return null;
    return ['rule_id' => (int)$row['id'], 'rate' => (float)$row['rate']];
}

/**
 * 班期销课时，将 pending 记录更新为 pre_final（含阶梯金额）
 *
 * 流程：订单付款时创建 pending 记录（预提金额）
 *       班期销课时，将 pending → pre_final（按阶梯终算金额）
 *       月底终算时，将 pre_final → final（按最新阶梯补差）
 *
 * @param PDO   $pdo
 * @param int   $termRelId  oa_student_term_rel.id
 * @return array [success => bool, records => [...], message => string]
 */
function calc_commission_for_term_rel(PDO $pdo, int $termRelId): array
{
    // 1. 获取班期关系记录
    $relStmt = $pdo->prepare("
        SELECT r.*, s.name student_name, s.owner_consultant_user_id, s.coach_user_id,
               ct.course_id, ct.term_name, c.course_name
        FROM oa_student_term_rel r
        JOIN oa_student s ON s.id = r.student_id
        JOIN oa_class_term ct ON ct.id = r.term_id
        JOIN oa_course c ON c.id = ct.course_id
        WHERE r.id = ? LIMIT 1
    ");
    $relStmt->execute([$termRelId]);
    $rel = $relStmt->fetch();
    if (!$rel) {
        return ['success' => false, 'records' => [], 'message' => '班期关系不存在'];
    }

    $studentId = (int)$rel['student_id'];
    $courseId  = (int)$rel['course_id'];

    // 2. 获取学员对应课程的已付款订单
    $orderStmt = $pdo->prepare("
        SELECT id, paid_amount, seller_user_id, seller_role, order_type
        FROM oa_order
        WHERE student_id = ? AND pay_status >= 1 AND course_id = ?
        ORDER BY payment_time DESC LIMIT 1
    ");
    $orderStmt->execute([$studentId, $courseId]);
    $order = $orderStmt->fetch();
    if (!$order) {
        return ['success' => false, 'records' => [], 'message' => '未找到已付款订单，无法计算分成'];
    }

    $orderId    = (int)$order['id'];
    $orderType  = (string)$order['order_type'];
    $paidAmount = (float)$order['paid_amount'];
    $now        = date('Y-m-d H:i:s');

    // 3. 查找该订单下所有 pending 记录，逐一按阶梯重算
    $pendingStmt = $pdo->prepare("
        SELECT id, user_id, role_type, base_amount, commission_amount, rate
        FROM oa_commission_calc
        WHERE order_id = ? AND calc_status = 'pending' AND term_rel_id IS NULL
    ");
    $pendingStmt->execute([$orderId]);
    $pendingRows = $pendingStmt->fetchAll();

    $calcIds = [];
    $records = [];

    foreach ($pendingRows as $row) {
        $userId   = (int)$row['user_id'];
        $roleType = (string)$row['role_type'];

        // 按阶梯计算终算金额
        $tier = commission_get_tier_for_user($pdo, $userId, $roleType);
        $finalRate = $tier ? $tier['rate'] : (float)$row['rate'];
        $tierId    = $tier ? (int)$tier['tier_id'] : null;
        $commissionAmount = round($paidAmount * $finalRate, 2);

        // 更新 pending → pre_final，关联 term_rel_id
        $upd = $pdo->prepare("
            UPDATE oa_commission_calc
            SET term_rel_id     = ?,
                tier_id          = ?,
                commission_amount = ?,
                rate              = ?,
                calc_status       = 'pre_final',
                calc_time         = ?
            WHERE id = ?
        ");
        $upd->execute([$termRelId, $tierId, $commissionAmount, $finalRate, $now, (int)$row['id']]);
        $calcIds[] = (int)$row['id'];

        $record = [
            'id'                 => (int)$row['id'],
            'role'              => $roleType,
            'user_id'           => $userId,
            'prev_amount'      => (float)$row['commission_amount'],
            'prev_rate'         => (float)$row['rate'],
            'tier'              => $tier ? $tier['tier_name'] : '兜底规则',
            'tier_rate'         => $finalRate,
            'monthly_amount'    => $tier ? $tier['monthly_amount'] : 0,
            'commission_amount' => $commissionAmount,
            'status'            => 'pre_final（预提，月底终算）',
        ];
        $records[] = $record;
    }

    // 4. 学员有归属教练但订单中无教练分成 → 补一条新记录
    $coachUserId = (int)$rel['coach_user_id'];
    if ($coachUserId > 0) {
        $hasCoachPending = false;
        foreach ($pendingRows as $r) {
            if ((int)$r['user_id'] === $coachUserId) { $hasCoachPending = true; break; }
        }
        if (!$hasCoachPending) {
            $tier = commission_get_tier_for_user($pdo, $coachUserId, 'coach');
            $finalRate = $tier ? $tier['rate'] : 0.03;
            $tierId    = $tier ? (int)$tier['tier_id'] : null;
            $commissionAmount = round($paidAmount * $finalRate, 2);
            $calcId = insert_commission_record($pdo, $orderId, $termRelId, $coachUserId, 'coach', $paidAmount, $commissionAmount, $tierId, $finalRate, $now);
            $calcIds[] = $calcId;
            $records[] = [
                'id'                 => $calcId,
                'role'               => 'coach',
                'user_id'            => $coachUserId,
                'tier'               => $tier ? $tier['tier_name'] : '兜底规则',
                'tier_rate'          => $finalRate,
                'monthly_amount'     => $tier ? $tier['monthly_amount'] : 0,
                'commission_amount'  => $commissionAmount,
                'status'             => 'pre_final（预提，月底终算）',
                'note'               => '订单中无教练，补充创建',
            ];
        }
    }

    if (count($records) === 0) {
        return ['success' => false, 'records' => [], 'message' => '没有待计算的分成记录（可能已计算过）'];
    }

    // 5. 更新 oa_student_term_rel 分成快照
    $snapshot = json_encode([
        'calc_at'      => $now,
        'order_id'     => $orderId,
        'paid_amount'  => $paidAmount,
        'order_type'   => $orderType,
        'records'      => $records,
        'total_amount' => array_sum(array_column($records, 'commission_amount')),
    ], JSON_UNESCAPED_UNICODE);

    $pdo->prepare("
        UPDATE oa_student_term_rel
        SET commission_info = ?, commission_calc_ids = ?, commission_calc_at = ?
        WHERE id = ?
    ")->execute([$snapshot, implode(',', $calcIds), $now, $termRelId]);

    // 6. 更新订单分成状态
    $pdo->exec("UPDATE oa_order SET commission_status = 'calculated' WHERE id = $orderId AND commission_status = 'pending'");

    return [
        'success'  => true,
        'records'  => $records,
        'message'  => '分成预提完成（月底终算后将更新最终金额）',
        'calc_ids' => $calcIds,
        'snapshot' => json_decode($snapshot, true),
    ];
}

/**
 * 写入单条分成记录（新建，用于补录场景）
 */
function insert_commission_record(
    PDO    $pdo,
    int    $orderId,
    int    $termRelId,
    int    $userId,
    string $roleType,
    float  $baseAmount,
    float  $commissionAmount,
    ?int   $tierId,
    float  $rate,
    string $calcTime,
    ?int   $ruleId = null
): int {
    $sql = "INSERT INTO oa_commission_calc
              (order_id, term_rel_id, rule_id, user_id, role_type,
               base_amount, commission_amount, rate, tier_id,
               calc_status, pay_status, calc_time)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pre_final', 'pending', ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $orderId, $termRelId, $ruleId, $userId, $roleType,
        $baseAmount, $commissionAmount, $rate, $tierId, $calcTime,
    ]);
    return (int)$pdo->lastInsertId();
}

/**
 * 月底批量终算：当月所有 pre_final → 重新按最新阶梯算终算金额
 *
 * @param PDO   $pdo
 * @param string $yearMonth  格式 YYYY-MM
 * @return array [total_records, total_amount, details]
 */
function commission_monthly_finalize(PDO $pdo, string $yearMonth): array
{
    $start = $yearMonth . '-01';
    $end   = date('Y-m-t', strtotime($start)) . ' 23:59:59';
    $now   = date('Y-m-d H:i:s');

    // 找出当月 pre_final 记录（关联到当月付款的订单）
    $sql = "SELECT cc.*, o.payment_time, o.seller_role
            FROM oa_commission_calc cc
            JOIN oa_order o ON o.id = cc.order_id
            WHERE cc.calc_status = 'pre_final'
              AND o.payment_time BETWEEN ? AND ?
            ORDER BY cc.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start, $end]);
    $rows = $stmt->fetchAll();

    $totalAmount = 0.0;
    $details = [];
    $updatedIds = [];

    foreach ($rows as $row) {
        $userId   = (int)$row['user_id'];
        $roleType = (string)$row['role_type'];
        $baseAmt  = (float)$row['base_amount'];

        // 重新获取最新阶梯（确保使用月底最终业绩）
        $tier = commission_get_tier_for_user($pdo, $userId, $roleType);
        $finalRate = $tier ? $tier['rate'] : (float)$row['rate'];
        $finalAmt  = round($baseAmt * $finalRate, 2);
        $tierId    = $tier ? $tier['tier_id'] : $row['tier_id'];

        // 更新终算金额
        $upd = $pdo->prepare("
            UPDATE oa_commission_calc
            SET commission_amount = ?,
                tier_id            = ?,
                calc_status        = 'final',
                final_amount       = COALESCE(final_amount, commission_amount),
                settled_at         = ?
            WHERE id = ?
        ");
        $upd->execute([$finalAmt, $tierId, $now, (int)$row['id']]);

        $totalAmount += $finalAmt;
        $updatedIds[] = (int)$row['id'];
        $details[] = [
            'id'             => (int)$row['id'],
            'user_id'        => $userId,
            'role'           => $roleType,
            'tier'           => $tier ? $tier['tier_name'] : 'N/A',
            'final_rate'     => $finalRate,
            'final_amount'   => $finalAmt,
            'monthly_amount' => $tier ? $tier['monthly_amount'] : 0,
        ];
    }

    return [
        'year_month'     => $yearMonth,
        'total_records'  => count($updatedIds),
        'total_amount'   => round($totalAmount, 2),
        'details'        => $details,
    ];
}
