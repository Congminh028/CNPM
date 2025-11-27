<?php
require_once 'config.php';

// 1. KIỂM TRA ĐĂNG NHẬP VÀ VAI TRÒ
$current_role = $_SESSION['user_role'] ?? '';
// CHỈ ADMIN VÀ SALES ĐƯỢC PHÉP TRUY CẬP TRANG NÀY
$allowed_roles = ['admin', 'sales']; 

// Nếu người dùng CHƯA ĐĂNG NHẬP hoặc VAI TRÒ KHÔNG ĐƯỢC PHÉP
if (!isLoggedIn() || !in_array($current_role, $allowed_roles)) {
    // Nếu bị chặn, CHUYỂN HƯỚNG về trang chủ
    header("Location: index.php");
    exit();
}

// Giữ lại dữ liệu mẫu đơn giản cho việc hiển thị tên người dùng
$current_user_name = getCurrentUser(); 
$error = ''; // Khởi tạo biến $error

// Hàm format tiền tệ (Đảm bảo định nghĩa)
function formatCurrency($amount) {
    // Giả định CURRENCY được định nghĩa trong config.php
    $currency = defined('CURRENCY') ? CURRENCY : '₫';
    return number_format($amount, 0, ',', '.') . ' ' . $currency;
}

// Hàm hiển thị badge trạng thái (Đã lấy từ sales.php)
function getStatusBadge($status) {
    switch ($status) {
        case 'paid':
            return '<span class="badge bg-success rounded-pill">Đã thanh toán</span>';
        case 'pending':
            return '<span class="badge bg-warning rounded-pill">Chờ thanh toán</span>';
        case 'cancelled':
            return '<span class="badge bg-danger rounded-pill">Đã hủy</span>';
        default:
            return '<span class="badge bg-secondary rounded-pill">' . $status . '</span>';
    }
}


// ------------------------------------------------------------------
// KHỐI TÍNH TOÁN THỐNG KÊ TỪ CSDL (CHỈ TÍNH CÁC ĐƠN 'paid')
// ------------------------------------------------------------------
$stats = [
    'today_revenue' => 0,
    'month_revenue' => 0,
    'today_orders' => 0,
    'month_orders' => 0,
];
$transactions = [];
$limit = 10;

try {
    global $pdo; // Đảm bảo $pdo có sẵn
    
    // Lấy ngày hiện tại và đầu tháng hiện tại
    $today = date('Y-m-d');
    $start_of_month = date('Y-m-01');

    // 1. TRUY VẤN TỔNG HỢP 4 CHỈ SỐ STATS
    $sql_stats = "
        SELECT 
            SUM(CASE WHEN DATE(created_at) = :today AND status = 'paid' THEN total_amount ELSE 0 END) as today_revenue,
            COUNT(CASE WHEN DATE(created_at) = :today AND status = 'paid' THEN id ELSE NULL END) as today_orders,
            SUM(CASE WHEN created_at >= :start_of_month AND status = 'paid' THEN total_amount ELSE 0 END) as month_revenue,
            COUNT(CASE WHEN created_at >= :start_of_month AND status = 'paid' THEN id ELSE NULL END) as month_orders
        FROM transactions
    ";
    
    $stmt_stats = $pdo->prepare($sql_stats);
    $stmt_stats->bindParam(':today', $today);
    $stmt_stats->bindParam(':start_of_month', $start_of_month);
    $stmt_stats->execute();
    $db_stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // Gán dữ liệu đã tính toán từ CSDL
    if ($db_stats) {
        $stats['today_revenue'] = (float)$db_stats['today_revenue'];
        $stats['month_revenue'] = (float)$db_stats['month_revenue'];
        $stats['today_orders'] = (int)$db_stats['today_orders'];
        $stats['month_orders'] = (int)$db_stats['month_orders'];
    }

    // 2. TRUY VẤN DANH SÁCH PHIẾU BÁN HÀNG GẦN ĐÂY
    $sql_trans = "
        SELECT 
            t.id, t.customer_name, t.total_amount, t.status, t.created_at, 
            u.fullname as sales_person 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC 
        LIMIT :limit
    ";
    
    $stmt_trans = $pdo->prepare($sql_trans);
    $stmt_trans->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt_trans->execute();
    $transactions = $stmt_trans->fetchAll();

} catch (PDOException $e) {
    $error = "LỖI TRUY VẤN CSDL: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Điều Khiển - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Tinh chỉnh CSS để có giao diện phẳng và sạch hơn (như hình 2) */
        :root { 
            --app-black: #111111; 
            --app-gray: #757575; 
            --card-bg: #FFFFFF; /* Đổi thành nền trắng */
            --transition-speed: 0.3s;
            --border-radius-lg: 10px;
        }
        
        /* 1. Stat Card Style (Áp dụng cho cả 8 ô) */
        .stat-card { 
            background: var(--card-bg); 
            border: 1px solid #f0f0f0; /* Thêm border nhẹ */
            padding: 25px; 
            border-radius: var(--border-radius-lg);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); /* Shadow nhẹ hơn */
            transition: all var(--transition-speed);
            display: block; 
            text-decoration: none; 
            color: inherit;
        }
        .stat-card:hover {
             transform: translateY(-2px);
             box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
             text-decoration: none;
        }
        .stat-icon { 
            font-size: 30px; 
            opacity: 0.5; /* Làm biểu tượng mờ hơn */
            color: var(--app-black); /* Biểu tượng màu đen */
        }
        
        /* 2. Style cho Bảng Giao dịch */
        .table-clean th { 
            font-weight: 500; 
            color: var(--app-gray); 
            border-bottom: 1px solid #ddd; 
            text-transform: uppercase; 
            font-size: 11px; 
            padding-bottom: 15px; 
        }
        .table-clean td { 
            padding: 15px 0; /* Giảm padding */
            font-weight: 500; 
            border-bottom: 1px solid #f0f0f0; /* Border mỏng và nhạt hơn */
        }
        .table-clean tbody tr:last-child td {
            border-bottom: none; /* Loại bỏ border ở dòng cuối */
        }
        
        /* 3. Style Sidebar (Giữ nguyên) */
        .sidebar .nav-link:nth-child(2) { 
            background-color: var(--app-black);
            color: white;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="sidebar d-none d-md-block">
    <a href="index.php" class="brand-logo">
        <i class="fab fa-autoprefixer"></i> <?php echo APP_NAME; ?>
    </a>
    <nav class="nav flex-column">
        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <a class="nav-link active" href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Bảng điều khiển</a>
        <a class="nav-link" href="sales.php"><i class="fas fa-cash-register"></i> POS / Bán hàng</a> 
        <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i> Quản lý Kho</a> 
        <a class="nav-link" href="account.php"><i class="fas fa-user-circle"></i> Tài khoản</a>
        <a class="nav-link" href="#"><i class="fas fa-cog"></i> Cài đặt</a>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold">Bảng Điều Khiển (Báo cáo)</h1>
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3 fw-bold">Xin chào, <?php echo $current_user_name; ?></span>
            <a href="logout.php" class="btn-outline-app">Đăng xuất</a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger mb-4">
            <i class="fas fa-times-circle me-2"></i> <?php echo $error; ?>
        </div>
    <?php elseif ($current_role === 'sales'): ?>
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i> Bạn đang truy cập **Giao diện Bán hàng/POS** (Sử dụng chung trang này).
        </div>
    <?php endif; ?>

   <div class="row g-4 mb-5">
    
    <div class="col-md-3">
        <a href="sales.php" class="stat-card card h-100 text-decoration-none">
            <div class="card-body">
                <i class="fas fa-file-invoice stat-icon mb-3 text-success"></i> 
                <p class="text-secondary small mb-1">Chức năng</p>
                <h3 class="fw-bold m-0">Tạo và Cập nhật Phiếu Bán hàng</h3>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="financial_report.php" class="stat-card card h-100 text-decoration-none">
            <div class="card-body">
                <i class="fas fa-calculator stat-icon mb-3 text-primary"></i>
                <p class="text-secondary small mb-1">Chức năng</p>
                <h3 class="fw-bold m-0">Tính toán Tự động & Chiết khấu</h3>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="inventory.php" class="stat-card card h-100 text-decoration-none">
            <div class="card-body">
                <i class="fas fa-boxes stat-icon mb-3 text-warning"></i>
                <p class="text-secondary small mb-1">Chức năng</p>
                <h3 class="fw-bold m-0">Kiểm soát Tồn kho</h3>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="audit_trail.php" class="stat-card card h-100 text-decoration-none"> 
            <div class="card-body">
                <i class="fas fa-history stat-icon mb-3 text-info"></i>
                <p class="text-secondary small mb-1">Chức năng</p>
                <h3 class="fw-bold m-0">Lưu vết Lịch sử Chỉnh sửa</h3>
            </div>
        </a>
    </div>

</div>
    <div class="row g-4 mb-5">
        
        <div class="col-md-3">
            <div class="stat-card card">
                <i class="fas fa-money-bill-wave stat-icon mb-2 text-success" style="opacity: 1;"></i> 
                <p class="text-secondary small mb-1">Doanh thu ngày</p>
                <h3 class="fw-bold m-0 text-success"><?php echo formatCurrency($stats['today_revenue']); ?></h3>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card card">
                <i class="fas fa-chart-line stat-icon mb-2 text-primary" style="opacity: 1;"></i>
                <p class="text-secondary small mb-1">Doanh thu tháng</p>
                <h3 class="fw-bold m-0 text-primary"><?php echo formatCurrency($stats['month_revenue']); ?></h3>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card card">
                <i class="fas fa-receipt stat-icon mb-2 text-warning" style="opacity: 1;"></i>
                <p class="text-secondary small mb-1">Số đơn hàng (Ngày)</p>
                <h3 class="fw-bold m-0 text-warning"><?php echo number_format($stats['today_orders']); ?> đơn</h3>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card card">
                <i class="fas fa-shopping-basket stat-icon mb-2 text-info" style="opacity: 1;"></i>
                <p class="text-secondary small mb-1">Số đơn hàng (Tháng)</p>
                <h3 class="fw-bold m-0 text-info"><?php echo number_format($stats['month_orders']); ?> đơn</h3>
            </div>
        </div>
    </div>
    <div class="row mb-5">
        <div class="col-12">
            <div class="p-4 bg-white rounded shadow-sm">
                <div class="d-flex justify-content-between align-items-end mb-3 border-bottom pb-2">
                    <h2 class="fw-bold m-0">Đơn hàng gần đây</h2>
                    <a href="sales.php" class="text-dark text-decoration-none fw-bold small">Xem tất cả <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <table class="table table-clean w-100">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Mã đơn</th>
                            <th>Khách</th>
                            <th>NV Bán</th>
                            <th width="150">Tổng tiền</th>
                            <th width="100">Trạng thái</th>
                            <th width="150">Ngày</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    Chưa có giao dịch nào được ghi nhận.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['id']); ?></td>
                                <td>#QLBH-<?php echo str_pad(htmlspecialchars($t['id']), 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($t['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($t['sales_person']); ?></td>
                                <td class="fw-bold text-dark"><?php echo formatCurrency($t['total_amount']); ?></td>
                                <td><?php echo getStatusBadge($t['status']); ?></td>
                                <td><?php echo date('H:i:s d/m/Y', strtotime($t['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>