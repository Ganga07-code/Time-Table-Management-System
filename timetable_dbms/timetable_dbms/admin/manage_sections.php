<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_admin();

// Handle section addition
if (isset($_POST['add_section'])) {
    $name = sanitize_input($_POST['name']);
    $year = (int)$_POST['year'];
    $department = sanitize_input($_POST['department']);
    
    // Check if section already exists
    $check = mysqli_query($conn, "SELECT id FROM sections WHERE name = '$name' AND year = $year");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO sections (name, year, department) VALUES ('$name', $year, '$department')");
        header("Location: manage_sections.php?success=1");
        exit();
    } else {
        $error = "Section already exists!";
    }
}

// Handle section deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $section_id = $_GET['delete'];
    
    // Check if section is being used in timetable
    $check = mysqli_query($conn, "SELECT id FROM timetable WHERE section_id = $section_id LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        header("Location: manage_sections.php?error=1");
        exit();
    }
    
    mysqli_query($conn, "DELETE FROM sections WHERE id = $section_id");
    header("Location: manage_sections.php?success=2");
    exit();
}

// Get all sections
$sections = mysqli_query($conn, "SELECT * FROM sections ORDER BY department, year, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections - TMS</title>
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
                        <a class="nav-link" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_sections.php">Sections</a>
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
                <h2><i class="fas fa-layer-group me-2"></i>Manage Sections</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                    <i class="fas fa-plus me-1"></i> Add New Section
                </button>
            </div>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">Section has been added successfully.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == 2): ?>
            <div class="alert alert-success">Section has been deleted successfully.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="alert alert-danger">Cannot delete section because it is being used in timetable.</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Year</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($section = mysqli_fetch_assoc($sections)): ?>
                                <tr>
                                    <td><?php echo $section['id']; ?></td>
                                    <td><?php echo htmlspecialchars($section['name']); ?></td>
                                    <td><?php echo $section['year']; ?></td>
                                    <td><?php echo htmlspecialchars($section['department']); ?></td>
                                    <td>
                                        <a href="manage_sections.php?delete=<?php echo $section['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this section?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php if (mysqli_num_rows($sections) == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No sections found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Section Modal -->
    <div class="modal fade" id="addSectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Section Name</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., CSE-A">
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
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="">Select Department</option>
                                <option value="Computer Science Engineering">Computer Science Engineering</option>
                                <option value="Electronics & Communication Engineering">Electronics & Communication Engineering</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_section" class="btn btn-primary">Add Section</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
