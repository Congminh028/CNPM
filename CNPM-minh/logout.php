<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? ''); 
    $password = trim($_POST['password'] ?? ''); 

    // 1. TÌM NGƯỜI DÙNG TRONG CSDL (Cần SELECT * để lấy cột role)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // 2. KIỂM TRA SỰ TỒN TẠI VÀ MẬT KHẨU
    if ($user && password_verify($password, $user['password'])) {
        // ĐĂNG NHẬP THÀNH CÔNG!
        
        // Thiết lập Session
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['user_id'] = $user['id'];
        
        // !!! ĐIỀU CHỈNH QUAN TRỌNG: LƯU VAI TRÒ (ROLE) VÀO SESSION !!!
        $_SESSION['user_role'] = $user['role']; 

        header("Location: index.php");
        exit();
    } else {
        $error = "Tên đăng nhập hoặc mật khẩu không đúng. Vui lòng thử lại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css"> 
</head>
<body>

<div class="container-fluid p-0 login-container">
    <div class="row g-0 h-100">
        
        <div class="col-md-6 col-lg-7 d-none d-md-block bg-image" style="background-image: url('https://images.unsplash.com/photo-1578916171728-46686eac8d58?q=80&w=1974&auto=format&fit=crop');">
            <div class="bg-overlay"></div>
            <div class="brand-text">
                <h1 class="display-3 fw-bold text-uppercase">Hệ thống<br>Điều hành</h1>
                <p class="lead">Đăng nhập để vào <?php echo APP_NAME; ?>.</p>
            </div>
        </div>

        <div class="col-md-6 col-lg-5 form-section">
            <div class="form-wrapper">
                <div class="app-logo"><?php echo APP_NAME; ?></div>
                
                <h3 class="fw-bold mb-4">Đăng nhập tài khoản</h3>
                
                <?php if($error): ?>
                    <div class="alert alert-danger small p-2"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group"><input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required></div>
                    <div class="form-group"><input type="password" name="password" class="form-control" placeholder="Mật khẩu" required></div>

                    <div class="link-group d-flex justify-content-between mb-3">
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="rememberMe"><label class="form-check-label text-secondary" for="rememberMe">Ghi nhớ</label></div>
                        <a href="#" class="text-secondary small text-decoration-none">Quên mật khẩu?</a>
                    </div>
                    
                    <button type="submit" class="btn-primary-app">Đăng nhập</button>
                </form>

                <div class="link-prompt">
                    Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>