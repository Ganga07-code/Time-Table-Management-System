-- Create the database
CREATE DATABASE IF NOT EXISTS timetable_dbms;
USE timetable_dbms;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'student') NOT NULL,
    section VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sections table
CREATE TABLE IF NOT EXISTS sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Faculty codes table
CREATE TABLE IF NOT EXISTS faculty_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) NOT NULL UNIQUE,
    created_by INT,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Timetable table
CREATE TABLE IF NOT EXISTS timetable (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    time_slot TIME NOT NULL,
    subject VARCHAR(100) NOT NULL,
    teacher_id INT,
    room_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) 
VALUES ('Administrator', 'admin@tms.com', '$2y$10$8FPi8P.U0F1RxUr.J7TAeOtl0USQLZIIzGvxB1FJQYqFQWkxvDP.O', 'admin');

-- Insert default sections
INSERT INTO sections (name) VALUES 
('A'),
('B'),
('C'),
('D'),
('E');

-- Insert some sample faculty codes
INSERT INTO faculty_codes (code, created_by) VALUES 
('FAC001', 1),
('FAC002', 1),
('FAC003', 1),
('FAC004', 1),
('FAC005', 1);
