<?php
require_once 'config.php';

// 1. KIỂM TRA ĐĂNG NHẬP
$current_role = $_SESSION['user_role'] ?? '';
$allowed_roles = ['admin', 'accountant', 'sales']; 

if (!isLoggedIn() || !in_array($current_role, $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$current_user_name = getCurrentUser();

// Hàm format tiền tệ
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

// 2. LẤY DỮ LIỆU
$transactions = [];
$summary = [
    'total_gross' => 0, // Tổng sau thuế (Thực thu)
    'total_net'   => 0, // Tổng trước thuế
    'total_tax'   => 0, // Tổng tiền thuế
    'count'       => 0
];

try {
    global $pdo;
    
    // --- QUAN TRỌNG: Thêm cột 'tax_amount' vào câu SELECT ---
    // Nếu trong database cột thuế của bạn tên khác (vd: vat_amount), hãy sửa chữ 'tax_amount' bên dưới
    $sql = "SELECT id, customer_name, total_amount, tax_amount, created_at, status 
            FROM transactions 
            WHERE status = 'paid' 
            ORDER BY created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. TÍNH TOÁN DỰA TRÊN SỐ LIỆU THỰC TẾ
    foreach ($transactions as $t) {
        // Lấy giá trị trực tiếp từ Database
        $gross = (float)$t['total_amount'];         // Tổng thanh toán
        $tax   = (float)($t['tax_amount'] ?? 0);    // Tiền thuế (nếu null thì bằng 0)
        
        // Tiền hàng = Tổng - Thuế
        $net   = $gross - $tax;

        // Cộng dồn
        $summary['total_gross'] += $gross;
        $summary['total_net']   += $net;
        $summary['total_tax']   += $tax;
        $summary['count']++;
    }

} catch (PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo Doanh thu & Thuế - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: 0.3s; }
        .card-stat:hover { transform: translateY(-3px); }
        .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); color: white; }
        .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); color: white; }
        .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); color: white; }
    </style>
</head>
<body class="bg-light">

<div class="sidebar d-none d-md-block">
    <a href="index.php" class="brand-logo"><i class="fab fa-autoprefixer"></i> <?php echo APP_NAME; ?></a>
    <nav class="nav flex-column">
        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Quay lại Dashboard</a>
        <a class="nav-link active" href="#"><i class="fas fa-calculator"></i> Báo cáo Tài chính</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-file-invoice-dollar me-2"></i>Báo cáo Doanh thu & Thuế</h2>
        <span class="badge bg-primary p-2">Dữ liệu thực tế từ Hóa đơn</span>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card card-stat bg-gradient-info h-100 p-3">
                <div class="card-body">
                    <h6 class="text-uppercase mb-2" style="opacity: 0.8">Tổng Tiền Trước Thuế (Net)</h6>
                    <h2 class="fw-bold mb-0"><?php echo formatCurrency($summary['total_net']); ?></h2>
                    <small>Doanh thu thực tế (Chưa VAT)</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stat bg-white h-100 p-3 border-start border-4 border-warning">
                <div class="card-body">
                    <h6 class="text-uppercase text-secondary mb-2">Tổng Tiền Thuế Đã Thu</h6>
                    <h2 class="fw-bold text-warning mb-0"><?php echo formatCurrency($summary['total_tax']); ?></h2>
                    <small class="text-muted">Tổng hợp từ các hóa đơn Paid</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stat bg-gradient-success h-100 p-3">
                <div class="card-body">
                    <h6 class="text-uppercase mb-2" style="opacity: 0.8">Tổng Sau Thuế (Gross)</h6>
                    <h2 class="fw-bold mb-0"><?php echo formatCurrency($summary['total_gross']); ?></h2>
                    <small>Tổng tiền khách đã thanh toán</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold m-0 text-primary">Chi tiết từng hóa đơn</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Mã Đơn</th>
                            <th>Ngày tạo</th>
                            <th>Khách hàng</th>
                            <th class="text-end text-secondary">Trước thuế (Net)</th>
                            <th class="text-end text-danger">Thuế (VAT)</th>
                            <th class="text-end fw-bold text-success">Tổng cộng (Gross)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($transactions)): ?>
                            <tr><td colspan="6" class="text-center py-4">Chưa có dữ liệu thanh toán.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): 
                                $gross = (float)$t['total_amount'];
                                $tax   = (float)($t['tax_amount'] ?? 0); // Lấy trực tiếp
                                $net   = $gross - $tax;
                            ?>
                            <tr>
                                <td><a href="sales.php?id=<?php echo $t['id']; ?>" class="fw-bold text-decoration-none">#<?php echo $t['id']; ?></a></td>
                                <td><?php echo date('d/m/Y', strtotime($t['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($t['customer_name']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($net); ?></td>
                                <td class="text-end text-danger small">
                                    <?php echo formatCurrency($tax); ?>
                                </td>
                                <td class="text-end fw-bold text-success"><?php echo formatCurrency($gross); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end text-uppercase">Tổng cộng:</td>
                            <td class="text-end"><?php echo formatCurrency($summary['total_net']); ?></td>
                            <td class="text-end text-danger"><?php echo formatCurrency($summary['total_tax']); ?></td>
                            <td class="text-end text-success"><?php echo formatCurrency($summary['total_gross']); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>