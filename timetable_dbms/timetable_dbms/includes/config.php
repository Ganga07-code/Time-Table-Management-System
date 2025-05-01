<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'timetable');

// First connect without selecting a database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (!mysqli_query($conn, $sql)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
mysqli_select_db($conn, DB_NAME);

// Create tables if they don't exist
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'staff', 'student') NOT NULL,
        section VARCHAR(10),
        year INT,
        faculty_code VARCHAR(20),
        department VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        year INT NOT NULL,
        department VARCHAR(100) NOT NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS faculty_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) UNIQUE NOT NULL,
        is_used BOOLEAN DEFAULT FALSE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS timetable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_id INT NOT NULL,
        day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
        period_number INT NOT NULL,
        subject VARCHAR(100) NOT NULL,
        faculty_id INT,
        room_number VARCHAR(20),
        FOREIGN KEY (section_id) REFERENCES sections(id),
        FOREIGN KEY (faculty_id) REFERENCES users(id)
    )"
];

// Create each table
foreach ($tables as $table) {
    if (!mysqli_query($conn, $table)) {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

// Check if year column exists in users table, if not add it
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'year'");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN year INT AFTER section");
}

// Check if department column exists in users table, if not add it
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'department'");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN department VARCHAR(100) AFTER faculty_code");
}

// Insert default admin if not exists
$admin_check = mysqli_query($conn, "SELECT id FROM users WHERE role='admin' LIMIT 1");
if (mysqli_num_rows($admin_check) == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('Administrator', 'admin@tms.com', '$admin_password', 'admin')");
}

session_start();
?>
