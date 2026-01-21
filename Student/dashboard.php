<?php
session_start();

// -----------------------------
// DATABASE CONNECTION
// -----------------------------
$conn = mysqli_connect("localhost", "root", "", "digital_school_management_system");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// -----------------------------
// LOGIN CHECK
// -----------------------------
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = mysqli_real_escape_string($conn, $_SESSION['email']);

// -----------------------------
// GET STUDENT INFO
// -----------------------------
$sqlStudent = "
SELECT 
    id,
    full_name,
    email,
    class,
    roll_number,
    risk_status
FROM users
WHERE email = '$email' AND role = 'student'
";

$resStudent = mysqli_query($conn, $sqlStudent);

if (!$resStudent || mysqli_num_rows($resStudent) == 0) {
    die("Student record not found.");
}

$student = mysqli_fetch_assoc($resStudent);
$student_id = $student['id']; // Using users.id as student_id

// -----------------------------
// CALCULATE GPA
// -----------------------------
$sqlGpa = "
SELECT IFNULL(ROUND(AVG(percentage)/25,2), 0) AS gpa
FROM performance
WHERE student_id = $student_id
";
$gpaRes = mysqli_query($conn, $sqlGpa);
$gpa = mysqli_fetch_assoc($gpaRes)['gpa'];

// -----------------------------
// ASSIGNED COURSES
// -----------------------------
$sqlCourses = "
SELECT DISTINCT subject 
FROM assessments 
WHERE assigned_to_all = 1 
   OR assigned_to_student = $student_id
";
$courses = mysqli_query($conn, $sqlCourses);

// -----------------------------
// COURSE PROGRESS
// -----------------------------
$sqlProgress = "
SELECT subject, COUNT(*) AS total, AVG(percentage) AS avg_score
FROM performance
WHERE student_id = $student_id
GROUP BY subject
";
$progress = mysqli_query($conn, $sqlProgress);

// -----------------------------
// RECENT ACTIVITIES
// -----------------------------
$sqlActivities = "
SELECT date, activity, status FROM (
    SELECT 
        submission_date AS date,
        CONCAT('Submitted Assessment #', assessment_id) AS activity,
        status AS status
    FROM assessment_submissions
    WHERE student_id = $student_id

    UNION ALL

    SELECT 
        date,
        assignment_name AS activity,
        CONCAT(score,'/',max_score) AS status
    FROM performance
    WHERE student_id = $student_id
) AS acts
ORDER BY date DESC
LIMIT 10
";

$activities = mysqli_query($conn, $sqlActivities);

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
                <li><a href="goal_tracker.php">🎯 Goals</a></li>
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
