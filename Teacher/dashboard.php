<?php
session_start();


$host = 'localhost';
$dbname = 'digital_school_management_system';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$teacher_id = isset($_SESSION['teacher_id']) ? $_SESSION['teacher_id'] : 1;
$teacher_name = isset($_SESSION['teacher_name']) ? $_SESSION['teacher_name'] : 'Teacher';


$students_sql = "SELECT COUNT(*) as total FROM students";
$students_result = mysqli_query($conn, $students_sql);
$total_students = 0;
if ($students_result) {
    $students_row = mysqli_fetch_assoc($students_result);
    $total_students = $students_row['total'];
}


$assessments_sql = "SELECT COUNT(*) as total FROM assessments WHERE created_by = '$teacher_id'";
$assessments_result = mysqli_query($conn, $assessments_sql);
$total_assessments = 0;
if ($assessments_result) {
    $assessments_row = mysqli_fetch_assoc($assessments_result);
    $total_assessments = $assessments_row['total'];
}


$pending_sql = "SELECT COUNT(*) as pending FROM assessment_submissions s
                INNER JOIN assessments a ON s.assessment_id = a.id
                WHERE a.created_by = '$teacher_id' AND s.status = 'Submitted'";
$pending_result = mysqli_query($conn, $pending_sql);
$pending_submissions = 0;
if ($pending_result) {
    $pending_row = mysqli_fetch_assoc($pending_result);
    $pending_submissions = $pending_row['pending'];
}


$risk_sql = "SELECT COUNT(*) as at_risk FROM students WHERE risk_status IN ('Yellow', 'Red')";
$risk_result = mysqli_query($conn, $risk_sql);
$at_risk_students = 0;
if ($risk_result) {
    $risk_row = mysqli_fetch_assoc($risk_result);
    $at_risk_students = $risk_row['at_risk'];
}


$recent_assessments_sql = "SELECT a.*, COUNT(s.id) as submission_count
                           FROM assessments a
                           LEFT JOIN assessment_submissions s ON a.id = s.assessment_id
                           WHERE a.created_by = '$teacher_id'
                           GROUP BY a.id
                           ORDER BY a.created_at DESC
                           LIMIT 5";
$recent_assessments_result = mysqli_query($conn, $recent_assessments_sql);
$recent_assessments = array();
if ($recent_assessments_result) {
    while ($row = mysqli_fetch_assoc($recent_assessments_result)) {
        $recent_assessments[] = $row;
    }
}


$risk_students_sql = "SELECT s.*, 
                      (SELECT AVG(percentage) FROM performance WHERE student_id = s.id) as avg_performance,
                      (SELECT COUNT(*) FROM assessment_submissions sub 
                       INNER JOIN assessments a ON sub.assessment_id = a.id 
                       WHERE sub.student_id = s.id AND a.created_by = '$teacher_id') as total_submissions
                      FROM students s
                      WHERE s.risk_status IN ('Yellow', 'Red')
                      ORDER BY 
                        CASE s.risk_status 
                          WHEN 'Red' THEN 1 
                          WHEN 'Yellow' THEN 2 
                        END
                      LIMIT 5";
$risk_students_result = mysqli_query($conn, $risk_students_sql);
$risk_students = array();
if ($risk_students_result) {
    while ($row = mysqli_fetch_assoc($risk_students_result)) {
        $risk_students[] = $row;
    }
}


$interventions_sql = "SELECT i.*, s.name as student_name, s.risk_status
                      FROM interventions i
                      INNER JOIN students s ON i.student_id = s.id
                      WHERE i.teacher_id = '$teacher_id'
                      ORDER BY i.created_at DESC
                      LIMIT 5";
$interventions_result = mysqli_query($conn, $interventions_sql);
$recent_interventions = array();
if ($interventions_result) {
    while ($row = mysqli_fetch_assoc($interventions_result)) {
        $recent_interventions[] = $row;
    }
}


$class_avg_sql = "SELECT AVG(percentage) as class_avg FROM performance";
$class_avg_result = mysqli_query($conn, $class_avg_sql);
$class_average = 0;
if ($class_avg_result) {
    $class_avg_row = mysqli_fetch_assoc($class_avg_result);
    $class_average = round($class_avg_row['class_avg'], 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/css/common.css">
</head>
<body>

<div class="dashboard-layout">
   
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($teacher_name, 0, 2)); ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($teacher_name); ?></h3>
                    <p>Teacher</p>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="dashboard.php" class="active">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="assessment_management.php">
                        <span class="nav-icon">📝</span>
                        <span>Assessments</span>
                    </a>
                </li>
                <li>
                    <a href="student_risk.php">
                        <span class="nav-icon">⚠️</span>
                        <span>Risk Indicators</span>
                    </a>
                </li>
                <li>
                    <a href="intervention_log.php">
                        <span class="nav-icon">📋</span>
                        <span>Interventions</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

  
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">📊 Teacher Dashboard</h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($teacher_name); ?>! Here's your class overview.</p>
        </div>

        <div class="container">
           
            <div class="overview-cards">
                <div class="overview-card">
                    <div class="card-icon">👥</div>
                    <div class="card-value"><?php echo $total_students; ?></div>
                    <div class="card-label">Total Students</div>
                    <div class="card-trend">Active learners</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">📝</div>
                    <div class="card-value"><?php echo $total_assessments; ?></div>
                    <div class="card-label">Assessments Created</div>
                    <div class="card-trend">Active assignments</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">⏳</div>
                    <div class="card-value"><?php echo $pending_submissions; ?></div>
                    <div class="card-label">Pending Grading</div>
                    <div class="card-trend" style="color: #f59e0b;">Needs attention</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">⚠️</div>
                    <div class="card-value"><?php echo $at_risk_students; ?></div>
                    <div class="card-label">At-Risk Students</div>
                    <div class="card-trend" style="color: #ef4444;">Requires intervention</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">📊</div>
                    <div class="card-value"><?php echo $class_average; ?>%</div>
                    <div class="card-label">Class Average</div>
                    <div class="card-trend">Overall performance</div>
                </div>
            </div>

            
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="assessment_management.php" style="text-decoration: none;">
                        <button class="add-goal-btn" style="width: 100%; margin: 0;">
                            ➕ Create Assessment
                        </button>
                    </a>
                    <a href="student_risk.php" style="text-decoration: none;">
                        <button class="add-goal-btn" style="width: 100%; margin: 0; background: linear-gradient(135deg, #f59e0b, #d97706);">
                            ⚠️ View Risk Status
                        </button>
                    </a>
                    <a href="intervention_log.php" style="text-decoration: none;">
                        <button class="add-goal-btn" style="width: 100%; margin: 0; background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            📋 Log Intervention
                        </button>
                    </a>
                </div>
            </div>

            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
               
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Assessments</h2>
                        <a href="assessment_management.php" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                            View All →
                        </a>
                    </div>
                    <?php if (empty($recent_assessments)): ?>
                        <p style="color: var(--text-muted); padding: 1rem 0;">No assessments created yet.</p>
                    <?php else: ?>
                        <?php foreach ($recent_assessments as $assessment): ?>
                            <div style="padding: 1rem 0; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                    <strong style="color: var(--text-main);">
                                        <?php echo htmlspecialchars($assessment['title']); ?>
                                    </strong>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($assessment['type']); ?>
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: var(--text-muted);">
                                    <span>Due: <?php echo date('M d', strtotime($assessment['due_date'])); ?></span>
                                    <span>Submissions: <?php echo $assessment['submission_count']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

               
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">At-Risk Students</h2>
                        <a href="student_risk.php" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                            View All →
                        </a>
                    </div>
                    <?php if (empty($risk_students)): ?>
                        <p style="color: var(--text-muted); padding: 1rem 0;">All students are performing well! 🎉</p>
                    <?php else: ?>
                        <?php foreach ($risk_students as $student): ?>
                            <div style="padding: 1rem 0; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <strong style="color: var(--text-main);">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </strong>
                                    <span class="badge" style="background: <?php echo $student['risk_status'] === 'Red' ? '#fee2e2' : '#fef9c3'; ?>; color: <?php echo $student['risk_status'] === 'Red' ? '#991b1b' : '#854d0e'; ?>;">
                                        <?php echo $student['risk_status'] === 'Red' ? '🔴' : '🟡'; ?> <?php echo $student['risk_status']; ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">
                                    Avg: <?php echo $student['avg_performance'] ? round($student['avg_performance'], 1) . '%' : 'N/A'; ?> • 
                                    Submissions: <?php echo $student['total_submissions']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Interventions</h2>
                    <a href="intervention_log.php" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                        View All →
                    </a>
                </div>
                <?php if (empty($recent_interventions)): ?>
                    <p style="color: var(--text-muted); padding: 1rem 0;">No interventions logged yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Action Type</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_interventions as $intervention): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($intervention['student_name']); ?></strong>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($intervention['intervention_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($intervention['action_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($intervention['description'], 0, 50)) . '...'; ?></td>
                                    <td>
                                        <span class="badge <?php echo $intervention['status'] === 'Resolved' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo htmlspecialchars($intervention['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>
<?php
mysqli_close($conn);
?>