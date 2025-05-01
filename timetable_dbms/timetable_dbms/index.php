<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-calendar-alt me-2"></i>TMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-1"></i>Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="signup.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="row justify-content-center text-center mb-5">
                <div class="col-md-8">
                    <h1 class="display-4 mb-4">Welcome to Timetable Management System</h1>
                    <p class="lead">A modern solution for managing college timetables efficiently.</p>
                </div>
            </div>
            <div class="row justify-content-center g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-graduate fa-3x text-primary mb-3"></i>
                            <h3>Student</h3>
                            <p>Access your class schedule and stay updated with your daily timetable.</p>
                            <a href="login.php?role=student" class="btn btn-primary">Student Login</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chalkboard-teacher fa-3x text-primary mb-3"></i>
                            <h3>Staff</h3>
                            <p>View your teaching schedule and manage your classes efficiently.</p>
                            <a href="login.php?role=staff" class="btn btn-primary">Staff Login</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                            <h3>Admin</h3>
                            <p>Manage timetables, users, and overall system administration.</p>
                            <a href="login.php?role=admin" class="btn btn-primary">Admin Login</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php
            header("Location: " . $_SESSION['user_role'] . "/dashboard.php");
            exit();
            ?>
        <?php endif; ?>
    </div>

    <footer class="bg-light py-4 mt-auto">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Timetable Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
