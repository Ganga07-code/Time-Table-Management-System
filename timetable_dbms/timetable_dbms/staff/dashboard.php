<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../login.php?role=staff");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get faculty information
$faculty_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$faculty = mysqli_fetch_assoc($faculty_query);

// First, clear all existing timetable data that has 3rd or 4th year students
mysqli_query($conn, "DELETE t FROM timetable t 
                    JOIN sections s ON t.section_id = s.id 
                    WHERE s.year > 2");

// Get timetable entries for this faculty
$timetable_query = mysqli_query($conn, "
    SELECT t.*, s.name as section_name, s.year, s.department 
    FROM timetable t 
    JOIN sections s ON t.section_id = s.id 
    WHERE t.faculty_id = $user_id AND s.year IN (1, 2)
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
    // Lunch break (1:00 - 2:00)
    5 => '2:00 - 3:00',
    6 => '3:00 - 4:00'
];

// Ensure exactly 2 leisure periods per day
$leisure_periods = [];
foreach ($days as $day) {
    $leisure_periods[$day] = [];
    $assigned_periods = isset($timetable[$day]) ? array_keys($timetable[$day]) : [];
    $available_periods = array_diff($periods, $assigned_periods);
    
    // If we have more than 2 available periods, randomly select 2
    if (count($available_periods) > 2) {
        shuffle($available_periods);
        $leisure_periods[$day] = array_slice($available_periods, 0, 2);
    } 
    // If we have exactly 2 available periods, use them
    else if (count($available_periods) == 2) {
        $leisure_periods[$day] = $available_periods;
    }
    // If we have less than 2 available periods, we need to free up some periods
    else {
        $needed = 2 - count($available_periods);
        $leisure_periods[$day] = $available_periods;
        
        // If we have assigned periods, remove some to make room for leisure
        if (isset($timetable[$day]) && count($timetable[$day]) > 0) {
            $periods_to_remove = array_rand($timetable[$day], min($needed, count($timetable[$day])));
            if (!is_array($periods_to_remove)) {
                $periods_to_remove = [$periods_to_remove];
            }
            
            foreach ($periods_to_remove as $period) {
                // Delete from database
                $entry_id = $timetable[$day][$period]['id'];
                mysqli_query($conn, "DELETE FROM timetable WHERE id = $entry_id");
                
                // Remove from our array
                unset($timetable[$day][$period]);
                
                // Add to leisure periods
                $leisure_periods[$day][] = $period;
            }
        }
    }
}

// Count leisure periods
$leisure_count = [];
foreach ($days as $day) {
    $leisure_count[$day] = count($leisure_periods[$day]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Timetable Management System</title>
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
        .timetable-entry .section {
            font-size: 0.9rem;
            margin: 4px 0;
            color: #198754;
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
        .leisure-period {
            background-color: #d1e7dd;
            text-align: center;
            font-weight: bold;
            padding: 10px;
        }
        .leisure-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8rem;
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
                        <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Staff Dashboard</h5>
                    </div>
                    <div class="card-body">
                        <h4>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h4>
                        <p>Here is your teaching schedule for the week:</p>
                        <div class="row">
                            <?php foreach ($days as $day): ?>
                            <div class="col-md-auto mb-2">
                                <span class="badge <?php echo ($leisure_count[$day] >= 2) ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $day; ?>: <?php echo $leisure_count[$day]; ?> leisure periods
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>Your timetable has been updated to include exactly 2 leisure periods per day and only shows 1st and 2nd year classes.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>My Timetable</h5>
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
                                                            <div class="section">
                                                                <i class="fas fa-users me-1"></i>
                                                                <?php echo htmlspecialchars($timetable[$day][$period]['section_name']); ?> 
                                                                (Year: <?php echo $timetable[$day][$period]['year']; ?>)
                                                            </div>
                                                            <div class="department">
                                                                <i class="fas fa-university me-1"></i>
                                                                <?php echo htmlspecialchars($timetable[$day][$period]['department']); ?>
                                                            </div>
                                                            <div class="room">
                                                                <i class="fas fa-door-open me-1"></i>
                                                                Room: <?php echo htmlspecialchars($timetable[$day][$period]['room_number']); ?>
                                                            </div>
                                                        </div>
                                                    <?php elseif (in_array($period, $leisure_periods[$day])): ?>
                                                        <div class="leisure-period">
                                                            <i class="fas fa-coffee me-2"></i>Leisure Period
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

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <p><strong>Note:</strong> Each faculty member has exactly 2 leisure periods per day.</p>
                            <p>Leisure periods are shown in green and can be used for preparation, research, or rest.</p>
                            <p>The timetable only includes classes for 1st and 2nd year students in CSE and ECE departments.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
