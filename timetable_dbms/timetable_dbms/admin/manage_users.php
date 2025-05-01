<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_admin();

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    // Don't allow deleting the current admin
    if ($user_id != $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
        header("Location: manage_users.php?success=1");
        exit();
    }
}

// Get users based on filter
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

if ($role_filter) {
    $users = mysqli_query($conn, "SELECT u.*, s.year, s.department 
                                 FROM users u 
                                 LEFT JOIN sections s ON u.section = s.name AND u.year = s.year
                                 WHERE u.role = '$role_filter' 
                                 ORDER BY u.name");
} else {
    $users = mysqli_query($conn, "SELECT u.*, s.year, s.department 
                                 FROM users u 
                                 LEFT JOIN sections s ON u.section = s.name AND u.year = s.year
                                 ORDER BY u.role, u.name");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-calendar-alt me-2"></i>TMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_sections.php">Sections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_timetable.php">Timetable</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../profile.php">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-users me-2"></i>Manage Users</h2>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group" role="group">
                    <a href="manage_users.php" class="btn <?php echo $role_filter == '' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                    <a href="manage_users.php?role=admin" class="btn <?php echo $role_filter == 'admin' ? 'btn-primary' : 'btn-outline-primary'; ?>">Admins</a>
                    <a href="manage_users.php?role=staff" class="btn <?php echo $role_filter == 'staff' ? 'btn-primary' : 'btn-outline-primary'; ?>">Staff</a>
                    <a href="manage_users.php?role=student" class="btn <?php echo $role_filter == 'student' ? 'btn-primary' : 'btn-outline-primary'; ?>">Students</a>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">User has been deleted successfully.</div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Section</th>
                                <th>Year</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php elseif ($user['role'] == 'staff'): ?>
                                            <span class="badge bg-success">Staff</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Student</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['section'] ?? '-'); ?></td>
                                    <td><?php echo $user['year'] ? $user['year'] : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="manage_users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php if (mysqli_num_rows($users) == 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
