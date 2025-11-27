<?php
require_once 'config.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP
$current_role = getCurrentUserRole();
if ($current_role !== 'admin' && $current_role !== 'inventory') {
    header('Location: index.php');
    exit;
}

$success_message = $_GET['success'] ?? ''; 
$error_message = $_GET['error'] ?? '';

// 🔴 SỬA LỖI TRUY VẤN CỘT TÊN NGƯỜI DÙNG
// GIẢ ĐỊNH tên cột trong bảng users là 'name'. 
// Nếu tên cột là 'username' hoặc 'full_name', hãy thay thế 'u.name' bằng tên cột đó.
// Đặt bí danh là user_display_name để sử dụng trong HTML.
$sql = "SELECT r.*, u.name as user_display_name FROM receipts r 
        LEFT JOIN users u ON r.user_id = u.id 
        ORDER BY r.receipt_date DESC, r.id DESC";

try {
    $stmt = $pdo->query($sql);
    $receipts = $stmt->fetchAll();
} catch (PDOException $e) {
    // ⚠️ Nếu lỗi vẫn xảy ra, thông báo cho người dùng biết lỗi.
    $error_message = "Lỗi CSDL: Không tìm thấy cột tên nhân viên trong bảng users. Vui lòng kiểm tra lại tên cột (ví dụ: name, username) và sửa lại trong truy vấn SQL.";
    $receipts = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Phiếu Nhập - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css"> 
    <style>
        .main-content { margin-left: 250px; padding: 40px 60px; }
        .table-primary th { font-size: 14px; }
    </style>
</head>
<body>
<div class="sidebar d-none d-md-block">
    <div class="brand-logo"><i class="fas fa-box"></i> <?php echo APP_NAME; ?></div>
    <nav class="nav flex-column">
        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <a class="nav-link active" href="inventory.php"><i class="fas fa-box"></i> Quản lý kho</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="inventory.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Quay lại Kho</a>
        <span class="me-3 fw-bold">Xin chào, <?php echo getCurrentUser(); ?></span>
    </div>

    <h1 class="fw-bold mb-4 text-uppercase"><i class="fas fa-file-invoice me-2"></i> Danh Sách Phiếu Nhập (<?php echo count($receipts); ?>)</h1>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-end mb-4">
        <a href="receipt_manage.php" class="btn btn-success fw-bold">
            <i class="fas fa-plus me-2"></i> Tạo Phiếu Nhập Mới
        </a>
    </div>

    <div class="card p-4 shadow-sm border-0">
        <?php if (count($receipts) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover w-100">
                <thead>
                    <tr class="table-primary">
                        <th>Mã Phiếu</th>
                        <th>Ngày Nhập</th>
                        <th>Nhà Cung Cấp</th>
                        <th>Nhân Viên</th>
                        <th class="text-end">Tổng Tiền</th>
                        <th>Trạng Thái</th>
                        <th style="width: 100px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($receipts as $r): ?>
                    <tr>
                        <td class="fw-bold"><?php echo htmlspecialchars($r['receipt_code']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($r['receipt_date'])); ?></td>
                        <td><?php echo htmlspecialchars($r['supplier_name']); ?></td>
                        <!-- 🔴 HIỂN THỊ TÊN NGƯỜI DÙNG BẰNG BÍ DANH (user_display_name) -->
                        <td><?php echo htmlspecialchars($r['user_display_name'] ?? 'N/A'); ?></td>
                        <td class="text-end fw-bold text-danger"><?php echo number_format($r['total_amount'], 0, ',', '.') . ' ' . CURRENCY; ?></td>
                        <td><span class="badge bg-success"><?php echo htmlspecialchars($r['status']); ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" title="Xem chi tiết" onclick="alert('Chức năng xem chi tiết sẽ được làm sau!')"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info text-center">Chưa có phiếu nhập nào được tạo.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>