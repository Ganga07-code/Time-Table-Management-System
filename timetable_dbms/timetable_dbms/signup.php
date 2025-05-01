<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['user_role'] . "/dashboard.php");
    exit();
}

// Define available departments and years
$departments = ['Computer Science Engineering', 'Electronics & Communication Engineering'];
$years = [1, 2];

// Define available sections based on department and year
// Each year starts with section A
$available_sections = [
    'Computer Science Engineering' => [
        1 => ['CSE-A', 'CSE-B', 'CSE-C'], // 3 sections for CSE 1st year
        2 => ['CSE-A', 'CSE-B']           // 2 sections for CSE 2nd year
    ],
    'Electronics & Communication Engineering' => [
        1 => ['ECE-A', 'ECE-B'],          // 2 sections for ECE 1st year
        2 => ['ECE-A']                    // 1 section for ECE 2nd year
    ]
];

// Get all sections from the database
$sections_query = mysqli_query($conn, "SELECT * FROM sections ORDER BY department, year, name");
$sections = [];
while ($row = mysqli_fetch_assoc($sections_query)) {
    $sections[] = $row;
}

// Group sections by department and year
$grouped_sections = [];
foreach ($sections as $section) {
    $dept = $section['department'];
    $year = $section['year'];
    
    if (!isset($grouped_sections[$dept])) {
        $grouped_sections[$dept] = [];
    }
    
    if (!isset($grouped_sections[$dept][$year])) {
        $grouped_sections[$dept][$year] = [];
    }
    
    $grouped_sections[$dept][$year][] = $section;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize_input($_POST['role']);
    $section = isset($_POST['section']) ? sanitize_input($_POST['section']) : null;
    $year = isset($_POST['year']) ? sanitize_input($_POST['year']) : null;
    $faculty_code = isset($_POST['faculty_code']) ? sanitize_input($_POST['faculty_code']) : null;
    
    // Validate password match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    }
    // Validate faculty code for staff
    elseif ($role === 'staff' && (!$faculty_code || !verify_faculty_code($faculty_code))) {
        $error = "Invalid faculty code!";
    }
    else {
        // Check if email already exists
        $check_email = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check_email, "s", $email);
        mysqli_stmt_execute($check_email);
        $result = mysqli_stmt_get_result($check_email);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($role === 'student') {
                $query = "INSERT INTO users (name, email, password, role, section, year) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $hashed_password, $role, $section, $year);
            } else {
                $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed_password, $role);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                if ($role === 'staff' && $faculty_code) {
                    mark_faculty_code_used($faculty_code);
                }
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed! Please try again.";
            }
        }
    }
}

$selected_role = isset($_GET['role']) ? $_GET['role'] : 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Timetable Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-calendar-alt me-2"></i>TMS</a>
        </div>
    </nav>

    <div class="container">
        <div class="signup-container">
            <h2 class="text-center mb-4">
                <?php if ($selected_role === 'staff'): ?>
                    <i class="fas fa-chalkboard-teacher me-2"></i>Staff Registration
                <?php else: ?>
                    <i class="fas fa-user-graduate me-2"></i>Student Registration
                <?php endif; ?>
            </h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <a href="login.php<?php echo $selected_role ? "?role=$selected_role" : ''; ?>" class="alert-link">Click here to login</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="signup.php">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <?php if ($selected_role === 'student'): ?>
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="Computer Science Engineering">Computer Science Engineering (CSE)</option>
                            <option value="Electronics & Communication Engineering">Electronics & Communication Engineering (ECE)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <select class="form-select" id="year" name="year" required>
                            <option value="">Select Year</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section" required>
                            <option value="">Select Department and Year First</option>
                        </select>
                        <?php if (count($sections) == 0): ?>
                            <div class="form-text text-danger">
                                No sections found. <a href="database/setup_sample_data.php">Click here to set up sample data</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($selected_role === 'staff'): ?>
                    <div class="mb-3">
                        <label for="faculty_code" class="form-label">Faculty Code</label>
                        <input type="text" class="form-control" id="faculty_code" name="faculty_code" required>
                        <div class="form-text">Enter the unique code provided by admin</div>
                    </div>
                <?php endif; ?>
                
                <input type="hidden" name="role" value="<?php echo $selected_role; ?>">
                <button type="submit" class="btn btn-primary w-100 mb-3">Sign Up</button>
                <div class="text-center">
                    <p class="mb-0">Already have an account? 
                        <a href="login.php<?php echo $selected_role ? "?role=$selected_role" : ''; ?>">Login</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dynamic section selection based on department and year
        document.addEventListener('DOMContentLoaded', function() {
            const departmentSelect = document.getElementById('department');
            const yearSelect = document.getElementById('year');
            const sectionSelect = document.getElementById('section');
            
            if (departmentSelect && yearSelect && sectionSelect) {
                const availableSections = <?php echo json_encode($available_sections); ?>;
                
                function updateSections() {
                    const department = departmentSelect.value;
                    const year = yearSelect.value;
                    
                    // Clear current options
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    
                    if (department && year && availableSections[department] && availableSections[department][year]) {
                        const sections = availableSections[department][year];
                        
                        sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section;
                            option.textContent = section;
                            sectionSelect.appendChild(option);
                        });
                    }
                }
                
                departmentSelect.addEventListener('change', updateSections);
                yearSelect.addEventListener('change', updateSections);
            }
        });
    </script>
</body>
</html>
