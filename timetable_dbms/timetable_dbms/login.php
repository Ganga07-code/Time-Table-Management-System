<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['user_role'] . "/dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize_input($_POST['role']);
    
    if ($user = verify_user($email, $password)) {
        if ($user['role'] === $role) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            
            header("Location: " . $role . "/dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials for selected role!";
        }
    } else {
        $error = "Invalid email or password!";
    }
}

$selected_role = isset($_GET['role']) ? $_GET['role'] : 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Timetable Management System</title>
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
        <div class="login-container">
            <h2 class="text-center mb-4">
                <?php if ($selected_role === 'admin'): ?>
                    <i class="fas fa-user-shield me-2"></i>Admin Login
                <?php elseif ($selected_role === 'staff'): ?>
                    <i class="fas fa-chalkboard-teacher me-2"></i>Staff Login
                <?php else: ?>
                    <i class="fas fa-user-graduate me-2"></i>Student Login
                <?php endif; ?>
            </h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <input type="hidden" name="role" value="<?php echo $selected_role; ?>">
                <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                <div class="text-center">
                    <p class="mb-0">Don't have an account? 
                        <a href="signup.php<?php echo $selected_role ? "?role=$selected_role" : ''; ?>">Sign up</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
