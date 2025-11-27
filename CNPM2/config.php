<?php
// DÒNG NÀY PHẢI LÀ DÒNG ĐẦU TIÊN CỦA FILE (trừ <?php)
session_start();

// 1. Cấu hình thông tin Website
define('APP_NAME', 'QUẢN LÝ BÁN HÀNG SIÊU THỊ'); 
define('CURRENCY', '₫'); 

// 2. Cấu hình Kết nối CSDL (PHP/MySQL)
$db_host = "localhost";
$db_user = "root"; // THAY THẾ bằng username MySQL của bạn
$db_pass = ""; // THAY THẾ bằng mật khẩu MySQL của bạn
$db_name = "quan_ly_sieu_thi"; // Tên CSDL đã tạo

// 3. THIẾT LẬP KẾT NỐI PDO TOÀN CỤC
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

// 4. Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

// 5. Hàm lấy tên người dùng hiện tại
function getCurrentUser() {
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Khách';
}
// 6. Hàm lấy vai trò (role) người dùng hiện tại (PHẦN BỔ SUNG)
function getCurrentUserRole() {
    // Lấy giá trị 'user_role' đã được lưu trong login.php khi đăng nhập
    return $_SESSION['user_role'] ?? '';
}
// KHÔNG CÓ THẺ ĐÓNG PHP