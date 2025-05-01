<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_admin();

// Get all sections
$sections = mysqli_query($conn, "SELECT * FROM sections ORDER BY department, year, name");
$section_list = [];
while ($row = mysqli_fetch_assoc($sections)) {
    $section_list[$row['id']] = $row;
}

// Get all faculty
$faculty = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'staff' ORDER BY name");
$faculty_list = [];
while ($row = mysqli_fetch_assoc($faculty)) {
    $faculty_list[$row['id']] = $row;
}

// Handle timetable entry addition
if (isset($_POST['add_entry'])) {
    $section_id = (int)$_POST['section_id'];
    $day = sanitize_input($_POST['day']);
    $period = (int)$_POST['period'];
    $subject = sanitize_input($_POST['subject']);
    $faculty_id = (int)$_POST['faculty_id'];
    $room = sanitize_input($_POST['room']);
    
    // Check if entry already exists
    $check = mysqli_query($conn, "SELECT id FROM timetable WHERE section_id = $section_id AND day_of_week = '$day' AND period_number = $period");
    if (mysqli_num_rows($check) > 0) {
        // Update existing entry
        mysqli_query($conn, "UPDATE timetable SET subject = '$subject', faculty_id = $faculty_id, room_number = '$room' 
                           WHERE section_id = $section_id AND day_of_week = '$day' AND period_number = $period");
    } else {
        // Add new entry
        mysqli_query($conn, "INSERT INTO timetable (section_id, day_of_week, period_number, subject, faculty_id, room_number) 
                           VALUES ($section_id, '$day', $period, '$subject', $faculty_id, '$room')");
    }
    
    header("Location: manage_timetable.php?section=$section_id&success=1");
    exit();
}

// Handle timetable entry deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $entry_id = $_GET['delete'];
    $section_id = isset($_GET['section']) ? $_GET['section'] : 0;
    
    mysqli_query($conn, "DELETE FROM timetable WHERE id = $entry_id");
    
    header("Location: manage_timetable.php?section=$section_id&success=2");
    exit();
}

// Get selected section's timetable
$selected_section = isset($_GET['section']) ? (int)$_GET['section'] : 0;
$timetable_entries = [];

if ($selected_section > 0) {
    $timetable_query = mysqli_query($conn, "
        SELECT t.*, u.name as faculty_name 
        FROM timetable t 
        LEFT JOIN users u ON t.faculty_id = u.id 
        WHERE t.section_id = $selected_section 
        ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), t.period_number
    ");
    
    while ($row = mysqli_fetch_assoc($timetable_query)) {
        $timetable_entries[$row['day_of_week']][$row['period_number']] = $row;
    }
}

// Define days and periods
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$periods = [1, 2, 3, 4, 5, 6]; // Periods with lunch break between 4 and 5
$period_times = [
    1 => '9:00 - 10:00',
    2 => '10:00 - 11:00',
    3 => '11:00 - 12:00',
    4 => '12:00 - 1:00',
    5 => '2:00 - 3:00',
    6 => '3:00 - 4:00'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Timetable - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .timetable-cell {
            min-height: 100px;
            padding: 10px;
            border: 1px solid #dee2e6;
            position: relative;
        }
        .timetable-entry {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .timetable-entry .subject {
            font-weight: bold;
            color: #0d6efd;
        }
        .timetable-entry .faculty {
            font-size: 0.9rem;
            margin: 4px 0;
        }
        .timetable-entry .room {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .timetable-entry .actions {
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .lunch-break {
            background-color: #ffeeba;
            text-align: center;
            font-weight: bold;
            padding: 10px;
        }
    </style>
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
                        <a class="nav-link" href="manage_sections.php">Sections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_timetable.php">Timetable</a>
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
                <h2><i class="fas fa-calendar-week me-2"></i>Manage Timetable</h2>
            </div>
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <select name="section" class="form-select me-2" onchange="this.form.submit()">
                        <option value="">Select Section</option>
                        <?php foreach ($section_list as $id => $section): ?>
                            <option value="<?php echo $id; ?>" <?php echo $selected_section == $id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($section['name']); ?> 
                                (Year: <?php echo $section['year']; ?>, 
                                <?php echo htmlspecialchars($section['department']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selected_section > 0): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                            <i class="fas fa-plus me-1"></i> Add Entry
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">Timetable entry has been added/updated successfully.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == 2): ?>
            <div class="alert alert-success">Timetable entry has been deleted successfully.</div>
        <?php endif; ?>

        <?php if ($selected_section > 0): ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        Timetable for <?php echo htmlspecialchars($section_list[$selected_section]['name']); ?> 
                        (Year: <?php echo $section_list[$selected_section]['year']; ?>, 
                        <?php echo htmlspecialchars($section_list[$selected_section]['department']); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <?php foreach ($days as $day): ?>
                                        <th><?php echo $day; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periods as $period): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo $period_times[$period]; ?></td>
                                        <?php foreach ($days as $day): ?>
                                            <td class="timetable-cell">
                                                <?php if (isset($timetable_entries[$day][$period])): ?>
                                                    <?php $entry = $timetable_entries[$day][$period]; ?>
                                                    <div class="timetable-entry">
                                                        <div class="actions">
                                                            <a href="manage_timetable.php?delete=<?php echo $entry['id']; ?>&section=<?php echo $selected_section; ?>" 
                                                               class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Are you sure you want to delete this entry?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                        <div class="subject"><?php echo htmlspecialchars($entry['subject']); ?></div>
                                                        <div class="faculty">
                                                            <?php if ($entry['faculty_name']): ?>
                                                                <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($entry['faculty_name']); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">No faculty assigned</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="room">Room: <?php echo htmlspecialchars($entry['room_number']); ?></div>
                                                    </div>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary add-entry-btn" 
                                                            data-bs-toggle="modal" data-bs-target="#addEntryModal"
                                                            data-day="<?php echo $day; ?>" data-period="<?php echo $period; ?>">
                                                        <i class="fas fa-plus"></i> Add
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php if ($period == 4): ?>
                                        <tr>
                                            <td class="fw-bold">1:00 - 2:00</td>
                                            <?php foreach ($days as $day): ?>
                                                <td class="lunch-break">
                                                    <i class="fas fa-utensils me-2"></i>Lunch Break
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Please select a section to view or manage its timetable.
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Entry Modal -->
    <div class="modal fade" id="addEntryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Timetable Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="section_id" value="<?php echo $selected_section; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="day" class="form-label">Day</label>
                                <select class="form-select" id="day" name="day" required>
                                    <option value="">Select Day</option>
                                    <?php foreach ($days as $day): ?>
                                        <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="period" class="form-label">Period</label>
                                <select class="form-select" id="period" name="period" required>
                                    <option value="">Select Period</option>
                                    <?php foreach ($periods as $period): ?>
                                        <option value="<?php echo $period; ?>"><?php echo $period_times[$period]; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="faculty_id" class="form-label">Faculty</label>
                            <select class="form-select" id="faculty_id" name="faculty_id" required>
                                <option value="">Select Faculty</option>
                                <?php foreach ($faculty_list as $id => $fac): ?>
                                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($fac['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="room" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="room" name="room" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_entry" class="btn btn-primary">Add Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set day and period when clicking "Add" button in a cell
            const addEntryBtns = document.querySelectorAll('.add-entry-btn');
            addEntryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const day = this.getAttribute('data-day');
                    const period = this.getAttribute('data-period');
                    
                    document.getElementById('day').value = day;
                    document.getElementById('period').value = period;
                });
            });
        });
    </script>
</body>
</html>
