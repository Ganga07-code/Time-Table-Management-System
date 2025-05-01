<?php
require_once '../includes/config.php';

// Clear existing data (optional - comment out if you don't want to reset)
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
mysqli_query($conn, "TRUNCATE TABLE timetable");
mysqli_query($conn, "TRUNCATE TABLE sections");
mysqli_query($conn, "DELETE FROM users WHERE role IN ('staff', 'student')");
mysqli_query($conn, "TRUNCATE TABLE faculty_codes");
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

// Create default admin if not exists
$admin_check = mysqli_query($conn, "SELECT id FROM users WHERE role='admin' LIMIT 1");
if (mysqli_num_rows($admin_check) == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('Administrator', 'admin@tms.com', '$admin_password', 'admin')");
    $admin_id = mysqli_insert_id($conn);
} else {
    $admin_row = mysqli_fetch_assoc($admin_check);
    $admin_id = $admin_row['id'];
}

// Create 8 sections (5 CSE, 3 ECE) according to the requirements
// Each year starts with section A
$sections = [
    // CSE 1st year (3 sections)
    ['CSE-A', 1, 'Computer Science Engineering'],
    ['CSE-B', 1, 'Computer Science Engineering'],
    ['CSE-C', 1, 'Computer Science Engineering'],
    // CSE 2nd year (2 sections) - starting with A
    ['CSE-A', 2, 'Computer Science Engineering'],
    ['CSE-B', 2, 'Computer Science Engineering'],
    // ECE 1st year (2 sections)
    ['ECE-A', 1, 'Electronics & Communication Engineering'],
    ['ECE-B', 1, 'Electronics & Communication Engineering'],
    // ECE 2nd year (1 section) - starting with A
    ['ECE-A', 2, 'Electronics & Communication Engineering']
];

$section_ids = [];
foreach ($sections as $section) {
    // Create a unique identifier for each section that includes department, year, and section name
    $section_key = $section[0] . '-Y' . $section[1];
    mysqli_query($conn, "INSERT INTO sections (name, year, department) VALUES ('$section[0]', $section[1], '$section[2]')");
    $section_ids[$section_key] = mysqli_insert_id($conn);
}

// Create 7 faculty members
$faculties = [
    ['Dr. John Smith', 'john.smith@tms.com', 'Data Structures & Algorithms', 'Computer Science Engineering'],
    ['Prof. Sarah Johnson', 'sarah.johnson@tms.com', 'Database Systems', 'Computer Science Engineering'],
    ['Dr. Michael Brown', 'michael.brown@tms.com', 'Computer Networks', 'Computer Science Engineering'],
    ['Prof. Emily Davis', 'emily.davis@tms.com', 'Operating Systems', 'Computer Science Engineering'],
    ['Dr. Robert Wilson', 'robert.wilson@tms.com', 'Digital Electronics', 'Electronics & Communication Engineering'],
    ['Prof. Jennifer Lee', 'jennifer.lee@tms.com', 'Microprocessors', 'Electronics & Communication Engineering'],
    ['Dr. David Miller', 'david.miller@tms.com', 'Signal Processing', 'Electronics & Communication Engineering']
];

$faculty_ids = [];
foreach ($faculties as $faculty) {
    // Create faculty code
    $code = strtoupper(bin2hex(random_bytes(4)));
    mysqli_query($conn, "INSERT INTO faculty_codes (code, is_used, created_by) VALUES ('$code', 1, $admin_id)");
    
    // Create faculty account
    $password = password_hash('faculty123', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (name, email, password, role, faculty_code, department) VALUES ('$faculty[0]', '$faculty[1]', '$password', 'staff', '$code', '$faculty[3]')");
    $faculty_ids[$faculty[0]] = mysqli_insert_id($conn);
}

// Create 16 students (2 for each section)
$students = [
    // CSE-A (1st year)
    ['Alice Johnson', 'alice.johnson@tms.com', 'CSE-A', 1, 'Computer Science Engineering'],
    ['Bob Williams', 'bob.williams@tms.com', 'CSE-A', 1, 'Computer Science Engineering'],
    // CSE-B (1st year)
    ['Charlie Davis', 'charlie.davis@tms.com', 'CSE-B', 1, 'Computer Science Engineering'],
    ['Diana Miller', 'diana.miller@tms.com', 'CSE-B', 1, 'Computer Science Engineering'],
    // CSE-C (1st year)
    ['Ethan Brown', 'ethan.brown@tms.com', 'CSE-C', 1, 'Computer Science Engineering'],
    ['Fiona Smith', 'fiona.smith@tms.com', 'CSE-C', 1, 'Computer Science Engineering'],
    // CSE-A (2nd year)
    ['George Wilson', 'george.wilson@tms.com', 'CSE-A', 2, 'Computer Science Engineering'],
    ['Hannah Lee', 'hannah.lee@tms.com', 'CSE-A', 2, 'Computer Science Engineering'],
    // CSE-B (2nd year)
    ['Ian Taylor', 'ian.taylor@tms.com', 'CSE-B', 2, 'Computer Science Engineering'],
    ['Julia Martin', 'julia.martin@tms.com', 'CSE-B', 2, 'Computer Science Engineering'],
    // ECE-A (1st year)
    ['Kevin Johnson', 'kevin.johnson@tms.com', 'ECE-A', 1, 'Electronics & Communication Engineering'],
    ['Laura Davis', 'laura.davis@tms.com', 'ECE-A', 1, 'Electronics & Communication Engineering'],
    // ECE-B (1st year)
    ['Mike Wilson', 'mike.wilson@tms.com', 'ECE-B', 1, 'Electronics & Communication Engineering'],
    ['Nancy Brown', 'nancy.brown@tms.com', 'ECE-B', 1, 'Electronics & Communication Engineering'],
    // ECE-A (2nd year)
    ['Oliver Smith', 'oliver.smith@tms.com', 'ECE-A', 2, 'Electronics & Communication Engineering'],
    ['Patricia Lee', 'patricia.lee@tms.com', 'ECE-A', 2, 'Electronics & Communication Engineering']
];

foreach ($students as $student) {
    $password = password_hash('student123', PASSWORD_DEFAULT);
    // Create a section identifier that includes the year
    $section_identifier = $student[2];
    $year = $student[3];
    $department = $student[4];
    
    // Store the section name in the database
    mysqli_query($conn, "INSERT INTO users (name, email, password, role, section, year, department) VALUES ('$student[0]', '$student[1]', '$password', 'student', '$section_identifier', $year, '$department')");
}

// Subjects for each department and year
$subjects = [
    'Computer Science Engineering' => [
        1 => [ // 1st year subjects
            'Programming Fundamentals',
            'Computer Organization',
            'Discrete Mathematics',
            'Digital Logic Design',
            'Engineering Mathematics',
            'Technical Communication'
        ],
        2 => [ // 2nd year subjects
            'Data Structures',
            'Algorithms',
            'Database Systems',
            'Operating Systems',
            'Computer Networks',
            'Software Engineering'
        ]
    ],
    'Electronics & Communication Engineering' => [
        1 => [ // 1st year subjects
            'Basic Electronics',
            'Circuit Theory',
            'Engineering Physics',
            'Engineering Mathematics',
            'Technical Drawing',
            'Workshop Practice'
        ],
        2 => [ // 2nd year subjects
            'Digital Electronics',
            'Analog Circuits',
            'Communication Systems',
            'Signal Processing',
            'Microprocessors',
            'Control Systems'
        ]
    ]
];

// Room numbers
$rooms = ['101', '102', '103', '104', '105', '201', '202', '203', '204', '205'];

// Days of the week
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Time periods (with lunch break from 1 to 2)
$periods = [1, 2, 3, 4, 5, 6]; // 1-4 are morning, 5-6 are afternoon (after lunch)
$period_times = [
    1 => '9:00 - 10:00',
    2 => '10:00 - 11:00',
    3 => '11:00 - 12:00',
    4 => '12:00 - 1:00',
    // Lunch break (1:00 - 2:00)
    5 => '2:00 - 3:00',
    6 => '3:00 - 4:00'
];

// Initialize faculty teaching schedule tracking
$faculty_schedule = [];
foreach ($faculty_ids as $faculty_name => $faculty_id) {
    $faculty_schedule[$faculty_id] = [];
    foreach ($days as $day) {
        $faculty_schedule[$faculty_id][$day] = array_fill(1, 6, false); // Initialize all periods as free
    }
}

// First, assign exactly 2 leisure periods per faculty per day
foreach ($faculty_ids as $faculty_name => $faculty_id) {
    foreach ($days as $day) {
        // Randomly select 2 periods to be leisure periods
        $leisure_periods = array_rand(array_flip($periods), 2);
        foreach ($leisure_periods as $period) {
            $faculty_schedule[$faculty_id][$day][$period] = 'leisure'; // Mark as leisure period
        }
    }
}

// Generate timetable for each section
foreach ($section_ids as $section_key => $section_id) {
    // Get department and year for this section
    $section_query = mysqli_query($conn, "SELECT department, year FROM sections WHERE id = $section_id");
    $section_data = mysqli_fetch_assoc($section_query);
    $department = $section_data['department'];
    $year = $section_data['year'];
    
    // Get subjects for this department and year
    $dept_subjects = isset($subjects[$department][$year]) ? $subjects[$department][$year] : [];
    
    // Get faculty members for this department
    $dept_faculty_ids = [];
    foreach ($faculties as $faculty) {
        if ($faculty[3] == $department) {
            $dept_faculty_ids[] = $faculty_ids[$faculty[0]];
        }
    }
    
    // For each day and period, assign a subject and faculty
    foreach ($days as $day) {
        foreach ($periods as $period) {
            // Find an available faculty (one who doesn't have a leisure period at this time)
            $assigned_faculty = false;
            shuffle($dept_faculty_ids); // Shuffle to distribute classes evenly
            
            foreach ($dept_faculty_ids as $faculty_id) {
                // Check if this period is not marked as a leisure period for this faculty
                if ($faculty_schedule[$faculty_id][$day][$period] !== 'leisure' && $faculty_schedule[$faculty_id][$day][$period] !== true) {
                    // Randomly select a subject
                    $subject_index = array_rand($dept_subjects);
                    $subject = $dept_subjects[$subject_index];
                    
                    // Randomly select a room
                    $room = $rooms[array_rand($rooms)];
                    
                    // Mark period as occupied
                    $faculty_schedule[$faculty_id][$day][$period] = true;
                    
                    // Insert timetable entry
                    mysqli_query($conn, "INSERT INTO timetable (section_id, day_of_week, period_number, subject, faculty_id, room_number) 
                        VALUES ($section_id, '$day', $period, '$subject', $faculty_id, '$room')");
                    
                    $assigned_faculty = true;
                    break;
                }
            }
            
            // If no faculty was available, this period will be empty for this section
        }
    }
}

// Verify and report faculty leisure periods
$leisure_report = [];
foreach ($faculty_ids as $faculty_name => $faculty_id) {
    $leisure_report[$faculty_name] = [];
    foreach ($days as $day) {
        $leisure_count = 0;
        foreach ($periods as $period) {
            if ($faculty_schedule[$faculty_id][$day][$period] === 'leisure') {
                $leisure_count++;
            }
        }
        $leisure_report[$faculty_name][$day] = $leisure_count;
    }
}

// Add CSS for better display
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Data Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .success-message { margin-bottom: 20px; }
        .section-info { margin-bottom: 15px; }
        .login-info { margin-bottom: 15px; }
        .table-container { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">';

echo '<div class="alert alert-success success-message">
        <h4><i class="bi bi-check-circle"></i> Sample data has been set up successfully!</h4>
      </div>';

echo '<div class="card section-info">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Created 8 sections:</h5>
        </div>
        <div class="card-body">
            <p><strong>CSE:</strong> 3 sections for 1st year (CSE-A, CSE-B, CSE-C), 2 sections for 2nd year (CSE-A, CSE-B)</p>
            <p><strong>ECE:</strong> 2 sections for 1st year (ECE-A, ECE-B), 1 section for 2nd year (ECE-A)</p>
        </div>
      </div>';

echo '<div class="card login-info">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Login Information</h5>
        </div>
        <div class="card-body">
            <p><strong>Faculty login:</strong> any faculty email (e.g., john.smith@tms.com) with password \'faculty123\'</p>
            <p><strong>Student login:</strong> any student email (e.g., alice.johnson@tms.com) with password \'student123\'</p>
            <p><strong>Admin login:</strong> admin@tms.com with password \'admin123\'</p>
        </div>
      </div>';

echo '<div class="card table-container">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Faculty Leisure Periods Report</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Note:</strong> Each faculty has been assigned exactly 2 leisure periods per day.
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Faculty</th>';
                            foreach ($days as $day) {
                                echo "<th>$day</th>";
                            }
echo '                  </tr>
                    </thead>
                    <tbody>';
                    foreach ($leisure_report as $faculty_name => $day_counts) {
                        echo "<tr><td>$faculty_name</td>";
                        foreach ($days as $day) {
                            echo "<td class='text-success'>{$day_counts[$day]} leisure periods</td>";
                        }
                        echo "</tr>";
                    }
echo '              </tbody>
                </table>
            </div>
        </div>
      </div>';

echo '<div class="mt-4">
        <a href="../index.php" class="btn btn-primary">Go to homepage</a>
      </div>';

echo '</div>
</body>
</html>';
?>
