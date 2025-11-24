<?php
require_once 'config.php';

// Lấy vai trò (role) của người dùng hiện tại từ Session
$current_role = $_SESSION['user_role'] ?? ''; 
$is_admin = $current_role === 'admin';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Management</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Các biến CSS cơ bản */
        :root { --app-black: #111111; --app-gray: #757575; --card-bg: #f5f5f5; }

        /* Các lớp cần thiết cho INDEX */
        .hero-title { font-weight: 900; font-size: 4rem; line-height: 1; margin-bottom: 40px; letter-spacing: -2px; text-transform: uppercase; }
        .feature-card { background-color: var(--card-bg); border: none; height: 300px; position: relative; overflow: hidden; transition: transform 0.3s ease; cursor: pointer; text-decoration: none; display: block; color: inherit; }
        .feature-card:hover { transform: scale(1.02); color: inherit; }
        .feature-card .card-label { position: absolute; bottom: 20px; left: 20px; font-weight: 800; font-size: 24px; text-transform: uppercase; }
        .feature-card .card-icon { position: absolute; top: 20px; right: 20px; font-size: 24px; opacity: 0.2; }
        .bg-sales { background: linear-gradient(45deg, #f5f5f5 0%, #e0e0e0 100%); }
        .bg-hr { background-color: #000; color: white !important; }
        .bg-stock { background: #f5f5f5; }
        
        /* Outline Button (ĐÃ SỬA LỖI CĂN CHỈNH) */
        .btn-outline-app {
            display: inline-block; background-color: transparent; color: var(--app-black);
            border-radius: 30px; padding: 10px 25px; font-weight: 600;
            border: 1px solid #e5e5e5; text-decoration: none; text-align: center;
        }
        .btn-outline-app:hover { border-color: var(--app-black); color: var(--app-black); }
        
        /* Primary Button (Dành cho nút Đăng nhập) */
        .btn-primary-app {
            display: inline-block; background-color: var(--app-black); color: white;
            border-radius: 30px; padding: 10px 25px; font-weight: 600; text-decoration: none; text-align: center;
            border: 1px solid var(--app-black);
        }
        .btn-primary-app:hover { background-color: white; color: var(--app-black); }

        /* Table Style */
        .table-clean th { font-weight: 600; color: var(--app-gray); border-bottom: 1px solid #eee; text-transform: uppercase; font-size: 12px; padding-bottom: 15px; }
        .table-clean td { padding: 20px 0; font-weight: 500; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

<div class="sidebar d-none d-md-block">
    <div class="brand-logo">
        <i class="fab fa-autoprefixer"></i> <?php echo APP_NAME; ?>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <a class="nav-link" href="#"><i class="fas fa-user-circle"></i> Tài khoản</a>
        <?php if(isLoggedIn()): ?>
            <a class="nav-link" href="#"><i class="fas fa-shopping-bag"></i> Bán hàng</a>
            <a class="nav-link" href="#"><i class="fas fa-box"></i> Quản lý kho</a>
            <?php if($is_admin): ?>
                <a class="nav-link" href="#"><i class="fas fa-chart-line"></i> Báo cáo</a>
                <a class="nav-link" href="#"><i class="fas fa-cog"></i> Cài đặt</a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div class="d-md-none font-weight-bold">MENU</div>
        <div class="ms-auto d-flex align-items-center">
            <?php if (isLoggedIn()): ?>
                <span class="me-3 fw-bold">Xin chào, <?php echo getCurrentUser(); ?></span>
                
                <a href="account.php" class="btn-outline-app me-2">Tài khoản</a>
                <a href="logout.php" class="btn-outline-app">Đăng xuất</a>
            <?php else: ?>
                <span class="me-3 text-secondary small">Bạn chưa đăng nhập?</span>
                <a href="register.php" class="btn-outline-app me-2">Đăng ký</a>
                <a href="login.php" class="btn-primary-app">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h1 class="hero-title">Hệ thống<br>Điều hành</h1>
            <p class="lead text-secondary mb-5" style="max-width: 500px;">
                Quản lý mọi hoạt động bán lẻ của <?php echo APP_NAME; ?>.
            </p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        
        <?php if(isLoggedIn()): ?>
            <?php if($is_admin || $current_role === 'sales'): ?>
            <div class="col-md-8">
                <a href="#" class="feature-card bg-sales p-4 rounded-0">
                    <i class="fas fa-shopping-bag card-icon"></i>
                    <div class="h-100 d-flex align-items-end">
                        <div>
                            <span class="badge bg-black mb-2">POS SYSTEM</span>
                            <h3 class="fw-bold text-uppercase">Bán Hàng & Thu Ngân</h3>
                            <p class="text-muted small">Tạo hóa đơn, quét mã vạch.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if($is_admin || $current_role === 'inventory'): ?>
            <div class="col-md-4">
                <a href="#" class="feature-card bg-hr p-4 rounded-0">
                    <i class="fas fa-box card-icon text-white"></i>
                    <div class="h-100 d-flex align-items-end">
                        <div>
                            <span class="badge bg-white text-dark mb-2">INVENTORY</span>
                            <h3 class="fw-bold text-uppercase text-white">Nhập Hàng</h3>
                            <p class="text-white-50 small">Quản lý kho.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if($is_admin || $current_role === 'hr'): ?>
            <div class="col-md-4">
                <a href="#" class="feature-card bg-stock p-4 rounded-0">
                    <i class="fas fa-users card-icon"></i>
                    <div class="card-label">Bộ phận<br>Nhân sự (HR)</div>
                </a>
            </div>
            <?php endif; ?>

            <?php if($is_admin || $current_role === 'support'): ?>
            <div class="col-md-4">
                <a href="#" class="feature-card bg-stock p-4 rounded-0">
                    <i class="fas fa-headset card-icon"></i>
                    <div class="card-label">Chăm sóc<br>Khách hàng</div>
                </a>
            </div>
            <?php endif; ?>

            <?php if($is_admin): ?>
            <div class="col-md-4">
                <a href="#" class="feature-card bg-stock p-4 rounded-0">
                    <i class="fas fa-user-shield card-icon"></i>
                    <div class="card-label">Phân Quyền<br>& Tài Khoản</div>
                </a>
            </div>
            <?php endif; ?>
        
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">Vui lòng <a href="login.php" class="fw-bold text-dark">Đăng nhập</a> để truy cập các tính năng.</div>
            </div>
        <?php endif; ?>

    </div>

    <?php if(isLoggedIn()): ?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-2">
                <h2 class="fw-bold m-0">Giao dịch gần đây</h2>
                <a href="#" class="text-dark text-decoration-none fw-bold small">Xem tất cả <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <table class="table table-clean w-100">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Nhân viên</th>
                        <th>Thời gian</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#QLBH-8821</td>
                        <td>Nguyễn Thu Hà</td>
                        <td>10:30 AM</td>
                        <td>1.500.000 <?php echo CURRENCY; ?></td>
                        <td><span class="badge bg-black rounded-pill">Đã thanh toán</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-secondary text-center">Vui lòng <a href="login.php" class="fw-bold text-dark">Đăng nhập</a> để xem dữ liệu báo cáo.</div>
    <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>