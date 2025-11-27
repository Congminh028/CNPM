<?php
require_once 'config.php';

// 1. KIỂM TRA ĐĂNG NHẬP VÀ VAI TRÒ
$current_role = $_SESSION['user_role'] ?? '';
$allowed_roles = ['admin', 'sales', 'inventory'];

if (!isLoggedIn() || !in_array($current_role, $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$current_user_name = getCurrentUser(); 

// ------------------------------------------------------------------
// 2. LẤY DỮ LIỆU LOG (TÁCH RIÊNG 2 MẢNG)
// ------------------------------------------------------------------
$transaction_logs = [];
$inventory_logs = [];
$error_msg = '';

try {
    global $pdo; 
    
    // QUERY 1: LẤY LOG GIAO DỊCH (HÓA ĐƠN)
    $sql_trans = "SELECT
        al.id, al.record_id, al.action, al.field_name, al.old_value, al.new_value, al.timestamp,
        u.username
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    WHERE al.table_name = 'transactions'
    ORDER BY al.timestamp DESC
    LIMIT 50";
    
    $stmt_trans = $pdo->query($sql_trans);
    $transaction_logs = $stmt_trans->fetchAll(PDO::FETCH_ASSOC);

    // QUERY 2: LẤY LOG KHO HÀNG (SẢN PHẨM)
    $sql_inv = "SELECT
        al.id, al.record_id, al.action, al.field_name, al.old_value, al.new_value, al.timestamp,
        u.username
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    WHERE al.table_name = 'products'
    ORDER BY al.timestamp DESC
    LIMIT 50";
    
    $stmt_inv = $pdo->query($sql_inv);
    $inventory_logs = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Lỗi khi tải lịch sử: " . $e->getMessage();
}

// Hàm format hiển thị thay đổi
function renderValueChange($val) {
    if (empty($val) && $val !== '0') return '<span class="text-muted small">Empty</span>';
    // Nếu nội dung quá dài, cắt bớt
    if (strlen($val) > 50) {
        return '<span title="'.htmlspecialchars($val).'">'.htmlspecialchars(substr($val, 0, 50)).'...</span>';
    }
    return htmlspecialchars($val);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử Hệ thống - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* CSS Tùy chỉnh */
        :root { --app-gray: #757575; }
        .table-clean th { font-weight: 600; color: var(--app-gray); border-bottom: 2px solid #eee; text-transform: uppercase; font-size: 11px; padding-bottom: 10px; letter-spacing: 0.5px; }
        .table-clean td { padding: 12px 0; font-weight: 500; border-bottom: 1px solid #f2f2f2; font-size: 14px; vertical-align: middle; }
        .main-content { padding: 30px; } 
        
        /* Màu sắc phân biệt */
        .section-title-sales { border-left: 5px solid #198754; padding-left: 15px; color: #198754; margin-bottom: 20px; }
        .section-title-inv { border-left: 5px solid #fd7e14; padding-left: 15px; color: #fd7e14; margin-bottom: 20px; }
        
        .val-box { background: #f8f9fa; padding: 4px 8px; border-radius: 4px; border: 1px solid #eee; font-family: monospace; font-size: 13px; display: inline-block; }
        .change-arrow { color: #999; margin: 0 5px; }
    </style>
</head>
<body>

<div class="sidebar d-none d-md-block">
    <a href="index.php" class="brand-logo">
        <i class="fab fa-autoprefixer"></i> <?php echo APP_NAME; ?>
    </a>
    <nav class="nav flex-column">
        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Bảng điều khiển</a>
        <a class="nav-link active" href="audit_trail.php"><i class="fas fa-history"></i> Lưu vết Lịch sử</a>
        <a class="nav-link" href="sales.php"><i class="fas fa-cash-register"></i> POS / Bán hàng</a> 
        <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i> Quản lý Kho</a> 
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-history me-2 text-primary"></i> Nhật ký Hoạt động (Audit Log)</h2>
        <div>
            <span class="me-3 fw-bold small text-muted">User: <?php echo $current_user_name; ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Đăng xuất</a>
        </div>
    </div>
    
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="row g-5">
        
        <div class="col-12">
            <div class="bg-white p-4 rounded shadow-sm">
                <h4 class="fw-bold section-title-sales">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Lịch sử Cập nhật Hóa đơn (Sales)
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-clean w-100">
                        <thead>
                            <tr>
                                <th width="100">Mã Đơn</th>
                                <th width="120">Người sửa</th>
                                <th width="150">Hành động</th>
                                <th>Chi tiết Thay đổi (Trường dữ liệu)</th>
                                <th width="150">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaction_logs)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có lịch sử chỉnh sửa hóa đơn.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transaction_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <a href="sales.php?search_query=<?php echo $log['record_id']; ?>&search_type=id" class="fw-bold text-decoration-none text-dark">
                                                #<?php echo str_pad($log['record_id'], 4, '0', STR_PAD_LEFT); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <i class="fas fa-user-circle text-muted small me-1"></i> 
                                            <?php echo htmlspecialchars($log['username']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <strong class="me-2 text-dark"><?php echo htmlspecialchars($log['field_name']); ?>:</strong>
                                                <span class="val-box text-danger border-danger border-opacity-25 bg-danger bg-opacity-10"><?php echo renderValueChange($log['old_value']); ?></span>
                                                <i class="fas fa-arrow-right change-arrow small mx-2"></i>
                                                <span class="val-box text-success border-success border-opacity-25 bg-success bg-opacity-10"><?php echo renderValueChange($log['new_value']); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($log['timestamp'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="bg-white p-4 rounded shadow-sm">
                <h4 class="fw-bold section-title-inv">
                    <i class="fas fa-boxes me-2"></i> Lịch sử Cập nhật Kho hàng (Inventory)
                </h4>

                <div class="table-responsive">
                    <table class="table table-clean w-100">
                        <thead>
                            <tr>
                                <th width="100">ID SP</th>
                                <th width="120">Người sửa</th>
                                <th width="150">Hành động</th>
                                <th>Chi tiết Thay đổi</th>
                                <th width="150">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory_logs)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có lịch sử chỉnh sửa kho hàng.</td></tr>
                            <?php else: ?>
                                <?php foreach ($inventory_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <a href="inventory.php" class="fw-bold text-decoration-none text-secondary">
                                                SP-<?php echo $log['record_id']; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <i class="fas fa-user-circle text-muted small me-1"></i> 
                                            <?php echo htmlspecialchars($log['username']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <strong class="me-2 text-dark"><?php echo htmlspecialchars($log['field_name']); ?>:</strong>
                                                <span class="val-box text-secondary"><?php echo renderValueChange($log['old_value']); ?></span>
                                                <i class="fas fa-arrow-right change-arrow small mx-2"></i>
                                                <span class="val-box text-dark fw-bold"><?php echo renderValueChange($log['new_value']); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($log['timestamp'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>