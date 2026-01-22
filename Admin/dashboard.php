<?php
session_start();


$conn = mysqli_connect('localhost', 'root', '', 'digital_school_management_system');
if (!$conn) {
    die("Database connection failed. Please check your database credentials.");
}


if (!isset($_SESSION['user_id'])) {

    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Admin User';
}

if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('Access denied. Admins only.'); window.location.href='../auth/login.php';</script>";
    exit();
}

$admin_name = $_SESSION['full_name'];

// Helper functions
function fetchOne($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return ['total' => 0]; 
}

function fetchAll($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $rows = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function tableExists($conn, $tableName) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return ($check && mysqli_num_rows($check) > 0);
}

// Get total students
$student_result = fetchOne($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$total_students = $student_result['total'] ?? 0;

// Get total teachers
$teacher_result = fetchOne($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
$total_teachers = $teacher_result['total'] ?? 0;

// Get total assessments if table exists
$total_assessments = 0;
if (tableExists($conn, 'assessments')) {
    $assessment_result = fetchOne($conn, "SELECT COUNT(*) as total FROM assessments");
    $total_assessments = $assessment_result['total'] ?? 0;
}

// Get total classes if table exists
$total_classes = 0;
if (tableExists($conn, 'classes')) {
    $class_result = fetchOne($conn, "SELECT COUNT(*) as total FROM classes");
    $total_classes = $class_result['total'] ?? 0;
}

// Get at-risk students
$risk_result = fetchOne($conn, "SELECT COUNT(*) as at_risk FROM users WHERE role = 'student' AND risk_status IN ('Yellow', 'Red')");
$at_risk_students = $risk_result['at_risk'] ?? 0;

// Get average performance if performance table exists
$avg_performance = 0;
if (tableExists($conn, 'performance')) {
    $perf_result = fetchOne($conn, "SELECT AVG(percentage) as avg FROM performance");
    $avg_performance = $perf_result['avg'] ? round($perf_result['avg'], 2) : 0;
}

// Risk distribution
$risk_distribution = ['Green' => 0, 'Yellow' => 0, 'Red' => 0];
$risk_query = "SELECT risk_status, COUNT(*) as count FROM users WHERE role = 'student' GROUP BY risk_status";
$risk_result = mysqli_query($conn, $risk_query);
if ($risk_result && mysqli_num_rows($risk_result) > 0) {
    while ($row = mysqli_fetch_assoc($risk_result)) {
        $risk_distribution[$row['risk_status']] = $row['count'];
    }
}

// Recent activities - get users with their creation date
$recent_activities = [];
$activities_query = "SELECT full_name as entity, role as type, created_at 
                     FROM users 
                     ORDER BY created_at DESC 
                     LIMIT 10";
$activities_result = mysqli_query($conn, $activities_query);
if ($activities_result && mysqli_num_rows($activities_result) > 0) {
    while ($row = mysqli_fetch_assoc($activities_result)) {
        $row['action_type'] = 'joined'; // Default action type
        $recent_activities[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
 
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
            </div>

            <!-- Student Risk Distribution Card -->
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

            <!-- Recent Activities -->
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
                                    if ($activity['type'] === 'student') echo '👨‍🎓';
                                    elseif ($activity['type'] === 'teacher') echo '👨‍🏫';
                                    else echo '👤';
                                ?>
                            </span>
                            <div style="flex: 1;">
                                <div style="font-size: 0.875rem; color: var(--text-main);">
                                    <strong><?php echo htmlspecialchars($activity['entity']); ?></strong>
                                    <span style="color: var(--text-muted);">
                                        (<?php echo ucfirst($activity['type']); ?> <?php echo $activity['action_type']; ?>)
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
    </main>
</div>

</body>
</html>
<?php
mysqli_close($conn);
?>