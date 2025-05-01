<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
require_admin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get statistics
$student_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='student'"))['count'];
$staff_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='staff'"))['count'];
$section_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sections"))['count'];

// Get department and year statistics
$dept_stats = [];
$year_stats = [];
$section_details = [];

// Get department statistics
$dept_query = mysqli_query($conn, "SELECT department, COUNT(*) as count FROM sections GROUP BY department ORDER BY department");
while ($row = mysqli_fetch_assoc($dept_query)) {
    $dept_stats[$row['department']] = $row['count'];
}

// Get year statistics
$year_query = mysqli_query($conn, "SELECT year, COUNT(*) as count FROM sections GROUP BY year ORDER BY year");
while ($row = mysqli_fetch_assoc($year_query)) {
    $year_stats[$row['year']] = $row['count'];
}

// Get detailed section information
$section_query = mysqli_query($conn, "SELECT s.*, 
    (SELECT COUNT(*) FROM users WHERE role='student' AND section=s.name AND year=s.year) as student_count,
    (SELECT COUNT(*) FROM timetable WHERE section_id=s.id) as class_count
    FROM sections s ORDER BY s.department, s.year, s.name");
while ($row = mysqli_fetch_assoc($section_query)) {
    $section_details[] = $row;
}

// Get faculty teaching load
$faculty_load = [];
$faculty_query = mysqli_query($conn, "
    SELECT u.id, u.name, COUNT(t.id) as class_count 
    FROM users u 
    LEFT JOIN timetable t ON u.id = t.faculty_id 
    WHERE u.role = 'staff' 
    GROUP BY u.id 
    ORDER BY class_count DESC
");
while ($row = mysqli_fetch_assoc($faculty_query)) {
    $faculty_load[] = $row;
}

// Handle faculty code generation
if (isset($_POST['generate_code'])) {
    $code = strtoupper(bin2hex(random_bytes(4)));
    mysqli_query($conn, "INSERT INTO faculty_codes (code, is_used, created_by) VALUES ('$code', 0, $user_id)");
    $_SESSION['success_msg'] = "Faculty code generated successfully: $code";
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Timetable Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 1rem;
            color: #6c757d;
        }
        .action-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .action-card:hover {
            transform: translateY(-5px);
        }
        .action-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-calendar-alt me-2"></i>TMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-id-card me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_msg']; 
                unset($_SESSION['success_msg']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h5>
                    </div>
                    <div class="card-body">
                        <h4>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h4>
                        <p>Manage your timetable system from here.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card bg-light">
                    <div class="card-body text-center p-4">
                        <div class="stat-icon text-primary">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-value"><?php echo $student_count; ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-light">
                    <div class="card-body text-center p-4">
                        <div class="stat-icon text-success">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-value"><?php echo $staff_count; ?></div>
                        <div class="stat-label">Faculty Members</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-light">
                    <div class="card-body text-center p-4">
                        <div class="stat-icon text-warning">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo $section_count; ?></div>
                        <div class="stat-label">Sections</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-university me-2"></i>Departments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Sections</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dept_stats as $dept => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dept); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $count; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Years</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th>Sections</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($year_stats as $year => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($year); ?></td>
                                        <td><span class="badge bg-success"><?php echo $count; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Section Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Section</th>
                                        <th>Year</th>
                                        <th>Department</th>
                                        <th>Students</th>
                                        <th>Classes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($section_details as $section): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($section['name']); ?></td>
                                        <td><?php echo htmlspecialchars($section['year']); ?></td>
                                        <td><?php echo htmlspecialchars($section['department']); ?></td>
                                        <td><span class="badge bg-info"><?php echo $section['student_count']; ?></span></td>
                                        <td><span class="badge bg-warning"><?php echo $section['class_count']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Faculty Teaching Load</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Faculty Name</th>
                                        <th>Total Classes</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($faculty_load as $faculty): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($faculty['name']); ?></td>
                                        <td><?php echo $faculty['class_count']; ?></td>
                                        <td>
                                            <?php 
                                            $total_possible = 5 * 6; // 5 days, 6 periods per day
                                            $load_percentage = ($faculty['class_count'] / $total_possible) * 100;
                                            
                                            if ($load_percentage < 40) {
                                                echo '<span class="badge bg-success">Light Load</span>';
                                            } elseif ($load_percentage < 70) {
                                                echo '<span class="badge bg-warning">Moderate Load</span>';
                                            } else {
                                                echo '<span class="badge bg-danger">Heavy Load</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card action-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="action-icon text-primary">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <h5 class="card-title">Manage Users</h5>
                        <p class="card-text">Add, edit, or remove users from the system.</p>
                        <a href="manage_users.php" class="btn btn-primary">Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card action-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="action-icon text-success">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h5 class="card-title">Manage Sections</h5>
                        <p class="card-text">Create, edit, or delete sections.</p>
                        <a href="manage_sections.php" class="btn btn-success">Sections</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card action-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="action-icon text-warning">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h5 class="card-title">Manage Timetable</h5>
                        <p class="card-text">Create and manage timetables for sections.</p>
                        <a href="manage_timetable.php" class="btn btn-warning">Timetable</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Generate Faculty Code</h5>
                    </div>
                    <div class="card-body">
                        <p>Generate a unique code for faculty registration.</p>
                        <form method="post" action="">
                            <button type="submit" name="generate_code" class="btn btn-primary">Generate Code</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
