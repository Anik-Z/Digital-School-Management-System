<?php
session_start();


$host = 'localhost';
$dbname = 'digital_school_management_system';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'] ?? 'Student';


$student = [];
$gpa = 0;
$courses_data = [];
$progress_data = [];
$activities_data = [];

// 1. Get student info from users table
$sqlStudent = "SELECT id, full_name, email, class, roll_number, 
               COALESCE(risk_status, 'Green') as risk_status 
               FROM users 
               WHERE id = '$student_id' AND role = 'student'";

$resStudent = mysqli_query($conn, $sqlStudent);

if ($resStudent && mysqli_num_rows($resStudent) > 0) {
    $student = mysqli_fetch_assoc($resStudent);
} else {
  
    $student = [
        'full_name' => $student_name,
        'email' => 'student@school.com',
        'class' => 'Not Assigned',
        'roll_number' => 'N/A',
        'risk_status' => 'Green'
    ];
}

// 2. Calculate GPA from performance table (if exists)
// First check if performance table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'performance'");
if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
    $sqlGpa = "SELECT IFNULL(ROUND(AVG(percentage)/25, 2), 0) AS gpa 
               FROM performance 
               WHERE student_id = $student_id";
    $gpaRes = mysqli_query($conn, $sqlGpa);
    if ($gpaRes && mysqli_num_rows($gpaRes) > 0) {
        $gpaRow = mysqli_fetch_assoc($gpaRes);
        $gpa = $gpaRow['gpa'];
    }
} else {
    $gpa = 0; // Default if table doesn't exist
}


$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'assessments'");
if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
    $sqlCourses = "SELECT DISTINCT subject 
                   FROM assessments 
                   WHERE assigned_to_all = 1 
                      OR assigned_to_student = $student_id
                   LIMIT 10";
    $coursesResult = mysqli_query($conn, $sqlCourses);
    if ($coursesResult) {
        while ($row = mysqli_fetch_assoc($coursesResult)) {
            $courses_data[] = $row;
        }
    }
}


$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'performance'");
if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
    $sqlProgress = "SELECT subject, 
                           COUNT(*) AS total, 
                           IFNULL(AVG(percentage), 0) AS avg_score
                    FROM performance 
                    WHERE student_id = $student_id 
                    GROUP BY subject
                    LIMIT 10";
    $progressResult = mysqli_query($conn, $sqlProgress);
    if ($progressResult) {
        while ($row = mysqli_fetch_assoc($progressResult)) {
            $progress_data[] = $row;
        }
    }
}


$activities_data = [];

// Check assessment_submissions table
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'assessment_submissions'");
if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
    $sqlActivities = "SELECT submission_date as date, 
                             CONCAT('Submitted Assessment #', assessment_id) as activity,
                             status 
                      FROM assessment_submissions 
                      WHERE student_id = $student_id 
                      ORDER BY submission_date DESC 
                      LIMIT 5";
    $activitiesResult = mysqli_query($conn, $sqlActivities);
    if ($activitiesResult) {
        while ($row = mysqli_fetch_assoc($activitiesResult)) {
            $activities_data[] = $row;
        }
    }
}

// If no activities from submissions, check performance table
if (empty($activities_data)) {
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'performance'");
    if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
        $sqlActivities = "SELECT date, 
                                 assignment_name as activity,
                                 CONCAT(score, '/', max_score) as status 
                          FROM performance 
                          WHERE student_id = $student_id 
                          ORDER BY date DESC 
                          LIMIT 5";
        $activitiesResult = mysqli_query($conn, $sqlActivities);
        if ($activitiesResult) {
            while ($row = mysqli_fetch_assoc($activitiesResult)) {
                $activities_data[] = $row;
            }
        }
    }
}


if (empty($activities_data)) {
    $activities_data[] = [
        'date' => date('Y-m-d'),
        'activity' => 'No recent activities',
        'status' => '-'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard</title>
<link rel="stylesheet" href="../Assets/css/common.css">
</head>

<body>

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="user-avatar">
                <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
            </div>
            <div class="user-details">
                <h4><?= htmlspecialchars($student['full_name']) ?></h4>
                <p><?= htmlspecialchars($student['class']) ?></p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li><a class="active">📊 Dashboard</a></li>
                <li>
                    <a href="performance.php">
                        <span class="nav-icon">📈</span>
                        <span>Performance</span>
                    </a>
                </li>
                <li>
                    <a href="goal_tracker.php">
                        <span class="nav-icon">🎯</span>
                        <span>Goal Tracker</span>
                    </a>
                </li>
                <li>    <a href="notices.php">📢 Notices</a></li>
                <li><a href="assessment.php">📝 Assessments</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <p>Risk Status: <strong><?= htmlspecialchars($student['risk_status']) ?></strong></p>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <h1 class="page-title">
            Welcome, <?= explode(" ", $student['full_name'])[0] ?>
        </h1>

        <!-- OVERVIEW CARDS -->
        <section class="overview-cards">

            <div class="overview-card">
                <div class="card-value"><?= $gpa ?></div>
                <div class="card-label">GPA</div>
            </div>

            <div class="overview-card">
                <div class="card-value"><?= htmlspecialchars($student['class']) ?></div>
                <div class="card-label">Class</div>
            </div>

            <div class="overview-card">
                <div class="card-value"><?= htmlspecialchars($student['roll_number']) ?></div>
                <div class="card-label">Roll No</div>
            </div>

        </section>

        <!-- ASSIGNED COURSES -->
        <section class="card">
            <h3>Assigned Courses</h3>
            <ul>
                <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                    <li><?= htmlspecialchars($c['subject']) ?></li>
                <?php endwhile; ?>
            </ul>
        </section>

        <!-- COURSE PROGRESS -->
        <section class="progress-summary">
            <h3>Course Progress</h3>

            <?php while ($p = mysqli_fetch_assoc($progress)): ?>
                <div class="progress-item">
                    <strong><?= htmlspecialchars($p['subject']) ?></strong>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= round($p['avg_score']) ?>%"></div>
                    </div>
                    <small>Average: <?= round($p['avg_score'], 1) ?>%</small>
                </div>
            <?php endwhile; ?>
        </section>

        <!-- RECENT ACTIVITIES -->
        <section class="card">
            <h3>Recent Activities</h3>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Activity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($a = mysqli_fetch_assoc($activities)): ?>
                    <tr>
                        <td><?= $a['date'] ?></td>
                        <td><?= htmlspecialchars($a['activity']) ?></td>
                        <td><?= htmlspecialchars($a['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </section>

    </main>
</div>

</body>
</html>
