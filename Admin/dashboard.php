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

$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';

// Fetch System Statistics
// Total Students
$students_sql = "SELECT COUNT(*) as total FROM students";
$students_result = mysqli_query($conn, $students_sql);
$total_students = mysqli_fetch_assoc($students_result)['total'];

// Total Teachers
$teachers_sql = "SELECT COUNT(*) as total FROM teachers";
$teachers_result = mysqli_query($conn, $teachers_sql);
$total_teachers = mysqli_fetch_assoc($teachers_result)['total'];

// Total Assessments
$assessments_sql = "SELECT COUNT(*) as total FROM assessments";
$assessments_result = mysqli_query($conn, $assessments_sql);
$total_assessments = mysqli_fetch_assoc($assessments_result)['total'];

// Total Classes
$classes_sql = "SELECT COUNT(*) as total FROM classes";
$classes_result = mysqli_query($conn, $classes_sql);
$total_classes = mysqli_fetch_assoc($classes_result)['total'];

// At-Risk Students
$risk_sql = "SELECT COUNT(*) as at_risk FROM students WHERE risk_status IN ('Yellow', 'Red')";
$risk_result = mysqli_query($conn, $risk_sql);
$at_risk_students = mysqli_fetch_assoc($risk_result)['at_risk'];

// Overall Performance Average
$perf_sql = "SELECT AVG(percentage) as avg_perf FROM performance";
$perf_result = mysqli_query($conn, $perf_sql);
$avg_performance = round(mysqli_fetch_assoc($perf_result)['avg_perf'], 2);

// Total Interventions
$interventions_sql = "SELECT COUNT(*) as total FROM interventions";
$interventions_result = mysqli_query($conn, $interventions_sql);
$total_interventions = mysqli_fetch_assoc($interventions_result)['total'];

// Active Notices
$notices_sql = "SELECT COUNT(*) as total FROM notices WHERE status = 'Active'";
$notices_result = mysqli_query($conn, $notices_sql);
$active_notices = mysqli_fetch_assoc($notices_result)['total'];

// Recent Activities
$activities_sql = "SELECT 'Student' as type, name as entity, created_at, 'Registered' as action FROM students
                   UNION ALL
                   SELECT 'Teacher' as type, name as entity, created_at, 'Registered' as action FROM teachers
                   UNION ALL
                   SELECT 'Assessment' as type, title as entity, created_at, 'Created' as action FROM assessments
                   ORDER BY created_at DESC LIMIT 10";
$activities_result = mysqli_query($conn, $activities_sql);
$recent_activities = array();
if ($activities_result) {
    while ($row = mysqli_fetch_assoc($activities_result)) {
        $recent_activities[] = $row;
    }
}

// Performance by Subject
$subject_perf_sql = "SELECT subject, AVG(percentage) as avg_score, COUNT(*) as total_assessments
                     FROM performance
                     GROUP BY subject
                     ORDER BY avg_score DESC
                     LIMIT 5";
$subject_perf_result = mysqli_query($conn, $subject_perf_sql);
$subject_performance = array();
if ($subject_perf_result) {
    while ($row = mysqli_fetch_assoc($subject_perf_result)) {
        $subject_performance[] = $row;
    }
}

// Risk Distribution
$risk_dist_sql = "SELECT risk_status, COUNT(*) as count FROM students GROUP BY risk_status";
$risk_dist_result = mysqli_query($conn, $risk_dist_sql);
$risk_distribution = array('Green' => 0, 'Yellow' => 0, 'Red' => 0);
if ($risk_dist_result) {
    while ($row = mysqli_fetch_assoc($risk_dist_result)) {
        $risk_distribution[$row['risk_status']] = $row['count'];
    }
}

// Class Enrollment
$class_enrollment_sql = "SELECT c.name as class_name, COUNT(s.id) as student_count
                        FROM classes c
                        LEFT JOIN students s ON c.id = s.class_id
                        GROUP BY c.id
                        ORDER BY student_count DESC
                        LIMIT 5";
$class_enrollment_result = mysqli_query($conn, $class_enrollment_sql);
$class_enrollments = array();
if ($class_enrollment_result) {
    while ($row = mysqli_fetch_assoc($class_enrollment_result)) {
        $class_enrollments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/css/common.css">
</head>
<body>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="user-info">
                <div class="user-avatar" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <?php echo strtoupper(substr($admin_name, 0, 2)); ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($admin_name); ?></h3>
                    <p>Administrator</p>
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
                    <a href="user_class_manage.php">
                        <span class="nav-icon">👥</span>
                        <span>Users & Classes</span>
                    </a>
                </li>
                <li>
                    <a href="policy_manager.php">
                        <span class="nav-icon">📋</span>
                        <span>Policies & Notices</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php">
                        <span class="nav-icon">📈</span>
                        <span>Reports</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <span class="nav-icon">⚙️</span>
                        <span>Settings</span>
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
            <h1 class="page-title">🎓 Admin Dashboard</h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($admin_name); ?>! Here's your system overview.</p>
        </div>

        <div class="container">
       
            <div class="overview-cards">
                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">👨‍🎓</div>
                    <div class="card-value"><?php echo $total_students; ?></div>
                    <div class="card-label">Total Students</div>
                    <div class="card-trend">Enrolled learners</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">👨‍🏫</div>
                    <div class="card-value"><?php echo $total_teachers; ?></div>
                    <div class="card-label">Total Teachers</div>
                    <div class="card-trend">Active educators</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">🏫</div>
                    <div class="card-value"><?php echo $total_classes; ?></div>
                    <div class="card-label">Total Classes</div>
                    <div class="card-trend">Active sections</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">📝</div>
                    <div class="card-value"><?php echo $total_assessments; ?></div>
                    <div class="card-label">Assessments</div>
                    <div class="card-trend">System-wide</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">⚠️</div>
                    <div class="card-value"><?php echo $at_risk_students; ?></div>
                    <div class="card-label">At-Risk Students</div>
                    <div class="card-trend" style="color: #ef4444;">Need attention</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">📊</div>
                    <div class="card-value"><?php echo $avg_performance; ?>%</div>
                    <div class="card-label">Avg Performance</div>
                    <div class="card-trend">System-wide</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">🔔</div>
                    <div class="card-value"><?php echo $active_notices; ?></div>
                    <div class="card-label">Active Notices</div>
                    <div class="card-trend">Published</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);">📋</div>
                    <div class="card-value"><?php echo $total_interventions; ?></div>
                    <div class="card-label">Interventions</div>
                    <div class="card-trend">Logged actions</div>
                </div>
            </div>

        
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="user_class_manage.php?action=add_student" style="text-decoration: none;">
                        <button class="add-goal-btn" style="width: 100%; margin: 0; background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            ➕ Add Student
                        </button>
                    </a>
                    <a href="user_class_manage.php?action=add_teacher" style="text-decoration: none;">
                        <button class="add-goal-btn" style="width: 100%; margin: 0; background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            ➕ Add Teacher
                        </button>
                    </a>
                    <a href="user_class_manage.php?action=add_class" style="text-decoration: none;">
                        <button class="add-goal-btn" style="width: 100%; margin: 0; background: linear-gradient(135deg, #10b981, #059669);">
                            ➕ Create Class
                        </button>
                    </a>
                    <a href="policy_manager.php?action=add_notice" style="text-decoration: none;">
                        <button class="add-goal-btn" style="width: 100%; margin: 0; background: linear-gradient(135deg, #f59e0b, #d97706);">
                            📢 Publish Notice
                        </button>
                    </a>
                </div>
            </div>

 
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
          
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Student Risk Distribution</h2>
                    </div>
                    <div style="padding: 1rem 0;">
                        <?php foreach ($risk_distribution as $status => $count): ?>
                            <?php 
                                $percentage = $total_students > 0 ? round(($count / $total_students) * 100) : 0;
                                $color = $status === 'Green' ? '#22c55e' : ($status === 'Yellow' ? '#f59e0b' : '#ef4444');
                            ?>
                            <div style="margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 600; color: var(--text-main);">
                                        <?php 
                                            if ($status === 'Green') echo '🟢';
                                            elseif ($status === 'Yellow') echo '🟡';
                                            else echo '🔴';
                                        ?>
                                        <?php echo $status; ?>
                                    </span>
                                    <span style="font-weight: 700; color: <?php echo $color; ?>;">
                                        <?php echo $count; ?> (<?php echo $percentage; ?>%)
                                    </span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>


                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Performance by Subject</h2>
                    </div>
                    <?php if (empty($subject_performance)): ?>
                        <p style="color: var(--text-muted); padding: 1rem 0;">No performance data available.</p>
                    <?php else: ?>
                        <?php foreach ($subject_performance as $subject): ?>
                            <div style="padding: 1rem 0; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <strong style="color: var(--text-main);">
                                        <?php echo htmlspecialchars($subject['subject']); ?>
                                    </strong>
                                    <span style="color: var(--primary); font-weight: 700;">
                                        <?php echo round($subject['avg_score'], 1); ?>%
                                    </span>
                                </div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">
                                    <?php echo $subject['total_assessments']; ?> assessments
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

      
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
        
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Class Enrollment</h2>
                        <a href="user_class_manage.php" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                            Manage →
                        </a>
                    </div>
                    <?php if (empty($class_enrollments)): ?>
                        <p style="color: var(--text-muted); padding: 1rem 0;">No classes created yet.</p>
                    <?php else: ?>
                        <?php foreach ($class_enrollments as $class): ?>
                            <div style="padding: 1rem 0; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong style="color: var(--text-main); display: block; margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </strong>
                                        <span style="font-size: 0.875rem; color: var(--text-muted);">
                                            <?php echo $class['student_count']; ?> students enrolled
                                        </span>
                                    </div>
                                    <span class="badge badge-info">
                                        Active
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

   
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Recent System Activities</h2>
                    </div>
                    <?php if (empty($recent_activities)): ?>
                        <p style="color: var(--text-muted); padding: 1rem 0;">No recent activities.</p>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div style="padding: 0.875rem 0; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 1rem;">
                                <span style="font-size: 1.5rem;">
                                    <?php 
                                        if ($activity['type'] === 'Student') echo '👨‍🎓';
                                        elseif ($activity['type'] === 'Teacher') echo '👨‍🏫';
                                        else echo '📝';
                                    ?>
                                </span>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.875rem; color: var(--text-main);">
                                        <strong><?php echo htmlspecialchars($activity['entity']); ?></strong>
                                        <span style="color: var(--text-muted);">
                                            (<?php echo $activity['type']; ?> <?php echo $activity['action']; ?>)
                                        </span>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
<?php
mysqli_close($conn);
?>