<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = [];
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'staff'; // Lấy chức năng/role

    // 1. Validation cơ bản
    if (empty($username) || empty($password) || empty($confirm_password) || empty($fullname) || empty($email) || $role === 'staff') {
        $error[] = "Vui lòng điền đầy đủ thông tin và chọn chức năng.";
    }
    if ($password !== $confirm_password) {
        $error[] = "Mật khẩu xác nhận không khớp.";
    }
    
    // 2. KIỂM TRA TÊN ĐĂNG NHẬP HOẶC EMAIL ĐÃ TỒN TẠI CHƯA
    if (empty($error)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error[] = "Tên đăng nhập hoặc Email đã được sử dụng.";
        }
    }

    // 3. THỰC HIỆN ĐĂNG KÝ VÀ LƯU VÀO CSDL
    if (empty($error)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Cập nhật câu lệnh INSERT để lưu role
        $sql = "INSERT INTO users (username, password, fullname, email, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        // Cập nhật execute để truyền $role
        if ($stmt->execute([$username, $hashed_password, $fullname, $email, $role])) {
            $success_message = "Đăng ký tài khoản thành công! Bạn sẽ được chuyển hướng đến trang Đăng nhập sau 3 giây.";
            header("Refresh: 3; URL=login.php");
        } else {
            $error[] = "Lỗi hệ thống khi đăng ký. Vui lòng thử lại.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> 
</head>
<body>

<div class="container-fluid p-0 login-container">
    <div class="row g-0 h-100">
        
        <div class="col-md-5 d-none d-md-block bg-image" style="background-image: url('https://images.unsplash.com/photo-1556742502-ec72a78f309a?q=80&w=1974&auto=format&fit=crop');">
            <div class="bg-overlay"></div>
            <div class="brand-text">
                <h1 class="display-3 fw-bold text-uppercase">Khởi tạo<br>Tài khoản</h1>
                <p class="lead">Gia nhập đội ngũ quản lý <?php echo APP_NAME; ?>.</p>
            </div>
        </div>

        <div class="col-md-7 form-section register-form-section">
            <div class="form-wrapper">
                <div class="app-logo"><?php echo APP_NAME; ?></div>
                
                <h3 class="fw-bold mb-4">Đăng ký tài khoản mới</h3>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger small p-2">
                        <?php foreach($error as $err) echo "* " . htmlspecialchars($err) . "<br>"; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success small p-2"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập (ID)" required>
                        </div>
                         <div class="col-md-6">
                            <input type="text" name="fullname" class="form-control" placeholder="Họ và Tên đầy đủ" required>
                        </div>
                    </div>

                    <input type="email" name="email" class="form-control" placeholder="Email (Dùng để khôi phục)" required>

                    <div class="form-group mb-3">
                        <select name="role" class="form-control" required>
                            <option value="staff" disabled selected>-- Chọn chức năng/quyền hạn --</option>
                            <option value="sales">1. Bán Hàng & Thu Ngân (POS)</option>
                            <option value="inventory">2. Quản lý Kho & Nhập Hàng</option>
                            <option value="hr">3. Bộ phận Nhân sự (HR)</option>
                            <option value="support">4. Chăm sóc Khách hàng</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                        </div>
                        <div class="col-md-6">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Xác nhận Mật khẩu" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-app">Đăng ký tài khoản</button>
                </form>

                <div class="link-prompt">
                    Đã có tài khoản? Quay lại <a href="login.php">Đăng nhập</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>