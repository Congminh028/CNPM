<?php
require_once 'config.php';

// Xóa tất cả các biến session
$_SESSION = array();
$_SESSION['user_logged_in'] = false; // Đảm bảo trạng thái đăng nhập là false

// Nếu muốn xóa session cookie (cần thiết cho 1 số cấu hình)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Chuyển hướng về trang chủ
header("Location: index.php");
exit();
?>