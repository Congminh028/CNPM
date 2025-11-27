<?php
require_once 'config.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Lấy ID người dùng từ session
$user_id = $_SESSION['user_id'];

// 2. TẠO MAPPING (Ánh xạ) CHO CHỨC VỤ
$role_mapping = [
    'admin' => ['name' => 'Quản Trị Hệ Thống', 'color' => 'bg-danger'],
    'sales' => ['name' => 'Nhân viên Bán Hàng & Thu Ngân', 'color' => 'bg-success'],
    'inventory' => ['name' => 'Quản lý Kho & Nhập Hàng', 'color' => 'bg-info'],
    'hr' => ['name' => 'Nhân viên Bộ phận Nhân sự (HR)', 'color' => 'bg-warning'],
    'support' => ['name' => 'Nhân viên Chăm sóc Khách hàng', 'color' => 'bg-primary']
];

// 3. LẤY THÔNG TIN CHI TIẾT TỪ CSDL
$stmt = $pdo->prepare("SELECT username, fullname, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Lấy thông tin role để hiển thị
$role_info = $role_mapping[$user['role']] ?? ['name' => 'Nhân viên chung', 'color' => 'bg-secondary'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản cá nhân - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* Đồng bộ các biến màu */
        :root { --app-black: #111111; --app-gray: #757575; --card-bg: #f5f5f5; }

        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        .container { max-width: 900px; }
        .profile-card { border-radius: 12px; }
        
        /* Đồng bộ Header/Banner thành màu đen đậm */
        .header-banner { 
            background-color: var(--app-black); 
            padding: 40px 30px; 
            border-top-left-radius: 12px; 
            border-top-right-radius: 12px;
            color: white;
            position: relative;
        }
        .header-banner h1 { font-weight: 800; } /* Font weight đậm hơn */

        .info-label { font-weight: 500; color: var(--app-gray); font-size: 0.9rem; }
        .info-value { font-size: 1rem; font-weight: 600; color: var(--app-black); }
        .role-badge { 
            font-size: 0.9rem; 
            padding: 8px 20px; 
            border-radius: 30px; /* Bo tròn hoàn toàn */
            font-weight: 700;
        }

        /* Đồng bộ style nút viền mỏng */
        .btn-outline-app {
            display: inline-block; background-color: transparent; color: var(--app-black);
            border-radius: 30px; padding: 8px 20px; font-weight: 600;
            border: 1px solid #e5e5e5; text-decoration: none; text-align: center;
        }
        .btn-outline-app:hover { border-color: var(--app-black); color: var(--app-black); }

    </style>
</head>
<body>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php" class="btn btn-sm btn-outline-app"><i class="fas fa-arrow-left me-2"></i> Trang chủ</a>
        <a href="logout.php" class="btn btn-sm btn-outline-app"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a>
    </div>

    <div class="card border-0 shadow-lg profile-card">
        
        <div class="header-banner">
            <h1 class="mb-0 text-uppercase"><i class="fas fa-user-shield me-3"></i> Thông tin Tài khoản</h1>
        </div>

        <div class="card-body p-5">

            <div class="d-flex align-items-center mb-5 border-bottom pb-4">
                <i class="fas fa-user-circle fa-4x me-4 text-secondary"></i> 
                <div>
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($user['fullname']); ?></h2>
                    <p class="text-secondary mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                
                <div class="ms-auto text-end">
                    <span class="d-block info-label mb-2">Chức vụ</span>
                    <span class="badge <?php echo $role_info['color']; ?> text-white role-badge shadow-sm">
                        <i class="fas fa-briefcase me-2"></i> 
                        <?php echo htmlspecialchars($role_info['name']); ?>
                    </span>
                </div>
            </div>

            <h5 class="fw-bold mt-4 mb-3 text-uppercase text-dark">Chi tiết Người dùng</h5>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <p class="info-label mb-1">Tên Đăng Nhập</p>
                    <p class="info-value"><?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="info-label mb-1">Email</p>
                    <p class="info-value"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="info-label mb-1">Mã Quyền Hạn Hệ Thống</p>
                    <p class="info-value text-muted"><?php echo htmlspecialchars($user['role']); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="info-label mb-1">Trạng thái tài khoản</p>
                    <p class="info-value text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Đang hoạt động</p>
                </div>
            </div>

            <hr class="mt-5 mb-4">
            
            </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>