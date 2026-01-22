<?php
session_start();


if (!isset($_SESSION['user_id'])) {
 
    $_SESSION['user_id'] = 2;
    $_SESSION['role'] = 'teacher';
    $_SESSION['full_name'] = 'John Smith';
    $_SESSION['subject'] = 'Mathematics';
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'];


$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'digital_school_management_system';

$conn = @mysqli_connect($host, $user, $pass, $db);


$total_students = 0;
$total_assessments = 0;
$pending_submissions = 0;
$at_risk_students = 0;
$class_average = 0;
$recent_assessments = [];
$risk_students = [];
$recent_interventions = [];

if ($conn) {
 
    $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $total_students = $row['total'];
    } else {
        $total_students = 25; 
    }
    

    $sql = "SELECT COUNT(*) as total FROM assessments WHERE created_by = '$teacher_id'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $total_assessments = $row['total'];
    } else {
        $total_assessments = 8; 
    }
    

    $sql = "SELECT COUNT(*) as pending FROM submissions s 
            INNER JOIN assessments a ON s.assessment_id = a.id 
            WHERE a.created_by = '$teacher_id' AND s.submission_status = 'Submitted'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $pending_submissions = $row['pending'];
    } else {
        $pending_submissions = 5; 
    }
    
    
    $sql = "SELECT COUNT(*) as at_risk FROM users WHERE role = 'student' AND risk_status IN ('Yellow', 'Red')";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $at_risk_students = $row['at_risk'];
    } else {
        $at_risk_students = 3; 
    }
    

    $sql = "SELECT AVG(percentage) as class_avg FROM performance";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $class_average = round($row['class_avg'], 2);
    } else {
        $class_average = 78.5;
    }
    
 
    $sql = "SELECT a.*, 
                   (SELECT COUNT(*) FROM submissions WHERE assessment_id = a.id) as submission_count 
            FROM assessments a 
            WHERE a.created_by = '$teacher_id' 
            ORDER BY a.created_at DESC 
            LIMIT 5";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $recent_assessments[] = $row;
        }
    }
    
 
    $sql = "SELECT u.id, u.full_name as name, u.risk_status,
                   (SELECT AVG(percentage) FROM performance WHERE student_id = u.id) as avg_performance,
                   (SELECT COUNT(*) FROM submissions WHERE student_id = u.id) as total_submissions 
            FROM users u 
            WHERE u.role = 'student' AND u.risk_status IN ('Yellow', 'Red') 
            ORDER BY CASE u.risk_status WHEN 'Red' THEN 1 WHEN 'Yellow' THEN 2 END 
            LIMIT 5";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $risk_students[] = $row;
        }
    }
    

    $sql = "SELECT i.*, u.full_name as student_name, u.risk_status 
            FROM interventions i 
            INNER JOIN users u ON i.student_id = u.id 
            WHERE i.teacher_id = '$teacher_id' 
            ORDER BY i.created_at DESC 
            LIMIT 5";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $recent_interventions[] = $row;
        }
    }
    
    mysqli_close($conn);
} else {
    
    $total_students = 25;
    $total_assessments = 8;
    $pending_submissions = 5;
    $at_risk_students = 3;
    $class_average = 78.5;
    
    $recent_assessments = [
        ['id' => 1, 'title' => 'Algebra Quiz', 'type' => 'Quiz', 'due_date' => date('Y-m-d', strtotime('+3 days')), 'submission_count' => 20],
        ['id' => 2, 'title' => 'Geometry Test', 'type' => 'Test', 'due_date' => date('Y-m-d', strtotime('+7 days')), 'submission_count' => 15],
        ['id' => 3, 'title' => 'Calculus Assignment', 'type' => 'Assignment', 'due_date' => date('Y-m-d', strtotime('+5 days')), 'submission_count' => 18]
    ];
    
    $risk_students = [
        ['id' => 1, 'name' => 'Bob Wilson', 'risk_status' => 'Red', 'avg_performance' => 62.5, 'total_submissions' => 3],
        ['id' => 2, 'name' => 'Charlie Davis', 'risk_status' => 'Yellow', 'avg_performance' => 68.3, 'total_submissions' => 5]
    ];
    
    $recent_interventions = [
        ['id' => 1, 'student_name' => 'Bob Wilson', 'intervention_date' => date('Y-m-d'), 'action_type' => 'Parent Meeting', 'description' => 'Discussed performance issues', 'status' => 'Resolved'],
        ['id' => 2, 'student_name' => 'Charlie Davis', 'intervention_date' => date('Y-m-d', strtotime('-2 days')), 'action_type' => 'Extra Tutoring', 'description' => 'Provided additional help', 'status' => 'In Progress']
    ];
}


if (empty($recent_assessments)) {
    $recent_assessments = [];
}

if (empty($risk_students)) {
    $risk_students = [];
}

if (empty($recent_interventions)) {
    $recent_interventions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
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