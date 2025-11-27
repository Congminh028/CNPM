<?php
require_once 'config.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 2. KIỂM TRA QUYỀN HẠN (CHỈ CHO PHÉP ADMIN HOẶC INVENTORY)
$current_role = getCurrentUserRole();
$is_admin = $current_role === 'admin';

if (!$is_admin && $current_role !== 'inventory') {
    // Nếu không phải admin hoặc inventory, chuyển hướng về trang chủ
    $_SESSION['error_message'] = "Bạn không có quyền truy cập chức năng này.";
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Kho & Nhập Hàng - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    
    <style>
        .module-card {
            border: none;
            border-radius: 12px;
            height: 180px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: var(--app-black);
            background-color: #ffffff; /* Nền trắng */
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .module-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .module-card .card-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #111111; /* Màu đen */
        }
        .module-card .card-title {
            font-weight: 700;
            font-size: 1.1rem;
        }
        .section-heading { 
            font-weight: 800; 
            color: var(--app-black); 
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<div class="sidebar d-none d-md-block">
    <div class="brand-logo">
        <i class="fas fa-box"></i> <?php echo APP_NAME; ?>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <a class="nav-link active" href="inventory.php"><i class="fas fa-box"></i> Quản lý kho</a>
        <?php if($is_admin): ?>
            <a class="nav-link" href="#"><i class="fas fa-chart-line"></i> Báo cáo</a>
            <a class="nav-link" href="#"><i class="fas fa-cog"></i> Cài đặt</a>
        <?php endif; ?>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-end align-items-center mb-5">
        <span class="me-3 fw-bold">Xin chào, <?php echo getCurrentUser(); ?></span>
        <a href="account.php" class="btn-outline-app me-2">Tài khoản</a>
        <a href="logout.php" class="btn-outline-app">Đăng xuất</a>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <h1 class="hero-title" style="font-size: 3rem; margin-bottom: 20px;">
                <i class="fas fa-warehouse me-3 text-secondary"></i>
                Quản lý Kho Vận
            </h1>
            <p class="lead text-secondary" style="max-width: 700px;">
                Quản lý hàng hóa, kiểm kê và xử lý các phiếu nhập hàng.
            </p>
        </div>
    </div>
    
    <hr>
    
    <h2 class="section-heading mt-5 mb-4"><i class="fas fa-cubes me-2"></i> Chức Năng Hàng Hoá</h2>
    
    <div class="row g-4 mb-5">
        
        <div class="col-md-3">
            <a href="product_manage.php" class="module-card shadow">
                <div>
                    <i class="fas fa-pen-to-square card-icon text-danger"></i>
                    <div class="card-title">Thêm/Sửa/Xóa Hàng Hoá</div>
                </div>
            </a>
        </div>
        
        

        <div class="col-md-3">
            <a href="product_list.php" class="module-card shadow">
                <div>
                    <i class="fas fa-search card-icon text-primary"></i>
                    <div class="card-title">Tìm Kiếm & Tra Cứu Hàng</div>
                </div>
            </a>
        </div>
        
    </div>

    <hr>
    
    <h2 class="section-heading mt-5 mb-4"><i class="fas fa-file-import me-2"></i> Quản Lý Phiếu Nhập</h2>
    
    <div class="row g-4 mb-5">
        
        <div class="col-md-3">
            <a href="receipt_manage.php" class="module-card shadow">
                <div>
                    <i class="fas fa-truck-loading card-icon text-info"></i>
                    <div class="card-title">Tạo & Quản lý Phiếu Nhập</div>
                </div>
            </a>
        </div>
    </div>
    
    <h2 class="section-heading mt-5 mb-4"><i class="fas fa-list-alt me-2"></i> Danh Sách Hàng Hoá</h2>
    <div class="card border-0 shadow-sm p-4">
        <p class="text-muted">Tính năng hiển thị dữ liệu (Database table) sẽ được tích hợp tại đây...</p>
        <table class="table table-clean w-100">
            <thead>
                <tr>
                    <th>Mã hàng</th>
                    <th>Tên hàng hoá</th>
                    <th>Số lượng tồn</th>
                    <th>Giá nhập cuối</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>SP-0001</td>
                    <td>Bánh quy X</td>
                    <td>250</td>
                    <td>15.000 <?php echo CURRENCY; ?></td>
                    <td><a href="#" class="text-primary small">Chi tiết</a></td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>