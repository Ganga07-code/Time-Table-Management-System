<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php?role=student");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get student information including section
$student_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$student = mysqli_fetch_assoc($student_query);
$section = $student['section'];

// Get section details
$section_query = mysqli_query($conn, "SELECT * FROM sections WHERE name = '$section'");
$section_details = mysqli_fetch_assoc($section_query);
$section_id = $section_details ? $section_details['id'] : 0;

// Get timetable for this section
$timetable_query = mysqli_query($conn, "
    SELECT t.*, u.name as faculty_name 
    FROM timetable t 
    LEFT JOIN users u ON t.faculty_id = u.id 
    WHERE t.section_id = $section_id 
    ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), t.period_number
");

$timetable = [];
while ($row = mysqli_fetch_assoc($timetable_query)) {
    $timetable[$row['day_of_week']][$row['period_number']] = $row;
}

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
    <title>Student Dashboard - Timetable Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .timetable-entry {
            padding: 8px;
            border-radius: 4px;
            background-color: #f8f9fa;
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
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Student Dashboard</h5>
                    </div>
                    <div class="card-body">
                        <h4>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h4>
                        <?php if ($section_details): ?>
                            <p>Your section: <strong><?php echo htmlspecialchars($section); ?></strong> (Year: <?php echo $section_details['year']; ?>, Department: <?php echo htmlspecialchars($section_details['department']); ?>)</p>
                            <p>Here is your class schedule for the week:</p>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Your section is not set up properly. Please contact an administrator or <a href="../database/setup_sample_data.php" class="alert-link">click here to set up sample data</a>.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($section_details): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Class Timetable</h5>
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
                                                <td>
                                                    <?php if (isset($timetable[$day][$period])): ?>
                                                        <div class="timetable-entry">
                                                            <div class="subject"><?php echo htmlspecialchars($timetable[$day][$period]['subject']); ?></div>
                                                            <div class="faculty">
                                                                <?php if ($timetable[$day][$period]['faculty_name']): ?>
                                                                    <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($timetable[$day][$period]['faculty_name']); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No faculty assigned</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="room">Room: <?php echo htmlspecialchars($timetable[$day][$period]['room_number']); ?></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
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
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
