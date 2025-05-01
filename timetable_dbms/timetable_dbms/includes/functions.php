<?php
require_once 'config.php';

function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

function verify_user($email, $password) {
    global $conn;
    $email = sanitize_input($email);
    
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_staff() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff';
}

function is_student() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

function generate_faculty_code() {
    return strtoupper(bin2hex(random_bytes(4)));
}

function verify_faculty_code($code) {
    global $conn;
    $code = sanitize_input($code);
    
    $query = "SELECT * FROM faculty_codes WHERE code = ? AND is_used = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt)->num_rows > 0;
}

function mark_faculty_code_used($code) {
    global $conn;
    $code = sanitize_input($code);
    
    $query = "UPDATE faculty_codes SET is_used = 1 WHERE code = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $code);
    return mysqli_stmt_execute($stmt);
}

function get_user_timetable($user_id, $role) {
    global $conn;
    
    if ($role === 'student') {
        $query = "SELECT t.*, s.name as section_name, u.name as faculty_name 
                 FROM timetable t 
                 JOIN sections s ON t.section_id = s.id 
                 LEFT JOIN users u ON t.faculty_id = u.id 
                 WHERE t.section_id = (SELECT section FROM users WHERE id = ?)
                 ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), period_number";
    } else {
        $query = "SELECT t.*, s.name as section_name 
                 FROM timetable t 
                 JOIN sections s ON t.section_id = s.id 
                 WHERE t.faculty_id = ?
                 ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), period_number";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: index.php');
        exit();
    }
}
?>
