<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'student_db';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$teacher_id = isset($_SESSION['teacher_id']) ? $_SESSION['teacher_id'] : 1;
$teacher_name = isset($_SESSION['teacher_name']) ? $_SESSION['teacher_name'] : 'Teacher';

$success_message = '';
$error_message = '';

// UPDATE Risk Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_risk'])) {
    $student_id = (int)$_POST['student_id'];
    $risk_status = mysqli_real_escape_string($conn, $_POST['risk_status']);
    
    $sql = "UPDATE students SET risk_status = '$risk_status' WHERE id = '$student_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Risk status updated successfully! ✨";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// Fetch all students with their performance data
$students_sql = "SELECT s.*, 
                (SELECT AVG(percentage) FROM performance WHERE student_id = s.id) as avg_performance,
                (SELECT COUNT(*) FROM assessment_submissions sub 
                 INNER JOIN assessments a ON sub.assessment_id = a.id 
                 WHERE sub.student_id = s.id AND sub.status = 'Submitted') as total_submissions,
                (SELECT COUNT(*) FROM assessments WHERE assigned_to_all = 1 OR assigned_to_student = s.id) as total_assessments,
                (SELECT COUNT(*) FROM interventions WHERE student_id = s.id) as intervention_count
                FROM students s
                ORDER BY 
                  CASE s.risk_status 
                    WHEN 'Red' THEN 1 
                    WHEN 'Yellow' THEN 2 
                    WHEN 'Green' THEN 3 
                  END,
                  s.name ASC";

$students_result = mysqli_query($conn, $students_sql);
$students = array();

if ($students_result) {
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}

// Calculate statistics
$total_students = count($students);
$green_count = 0;
$yellow_count = 0;
$red_count = 0;

foreach ($students as $student) {
    switch ($student['risk_status']) {
        case 'Green':
            $green_count++;
            break;
        case 'Yellow':
            $yellow_count++;
            break;
        case 'Red':
            $red_count++;
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Risk Indicators</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/css/common.css">
    <style>
        .risk-indicator {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .risk-green {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .risk-yellow {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .risk-red {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .student-card {
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
            padding: 1.75rem;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
        }
        
        .student-card.red-risk {
            border-left: 4px solid #ef4444;
        }
        
        .student-card.yellow-risk {
            border-left: 4px solid #f59e0b;
        }
        
        .student-card.green-risk {
            border-left: 4px solid #22c55e;
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    <!-- Sidebar -->
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
                    <a href="dashboard.php">
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
                    <a href="student_risk.php" class="active">
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">⚠️ Student Risk Indicators</h1>
            <p class="page-subtitle">Monitor and manage student risk status</p>
        </div>

        <div class="container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Risk Statistics -->
            <div class="overview-cards">
                <div class="overview-card">
                    <div class="card-icon">👥</div>
                    <div class="card-value"><?php echo $total_students; ?></div>
                    <div class="card-label">Total Students</div>
                </div>

                <div class="overview-card" style="border-left: 4px solid #22c55e;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">🟢</div>
                    <div class="card-value" style="color: #22c55e;"><?php echo $green_count; ?></div>
                    <div class="card-label">Low Risk (Green)</div>
                    <div class="card-trend" style="color: #059669;">Performing well</div>
                </div>

                <div class="overview-card" style="border-left: 4px solid #f59e0b;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">🟡</div>
                    <div class="card-value" style="color: #f59e0b;"><?php echo $yellow_count; ?></div>
                    <div class="card-label">Medium Risk (Yellow)</div>
                    <div class="card-trend" style="color: #d97706;">Needs monitoring</div>
                </div>

                <div class="overview-card" style="border-left: 4px solid #ef4444;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">🔴</div>
                    <div class="card-value" style="color: #ef4444;"><?php echo $red_count; ?></div>
                    <div class="card-label">High Risk (Red)</div>
                    <div class="card-trend" style="color: #dc2626;">Urgent intervention</div>
                </div>
            </div>

            <!-- Risk Legend -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--text-main); font-weight: 700;">Risk Status Guide</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div style="padding: 1rem; background: #f0fdf4; border-radius: 10px; border-left: 4px solid #22c55e;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                            <span style="font-size: 1.5rem;">🟢</span>
                            <strong style="color: #166534;">Green - Low Risk</strong>
                        </div>
                        <p style="color: #15803d; font-size: 0.875rem; margin: 0;">
                            Average ≥ 70% • Regular submissions • Good attendance
                        </p>
                    </div>
                    
                    <div style="padding: 1rem; background: #fffbeb; border-radius: 10px; border-left: 4px solid #f59e0b;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                            <span style="font-size: 1.5rem;">🟡</span>
                            <strong style="color: #854d0e;">Yellow - Medium Risk</strong>
                        </div>
                        <p style="color: #a16207; font-size: 0.875rem; margin: 0;">
                            Average 50-69% • Inconsistent submissions • Requires monitoring
                        </p>
                    </div>
                    
                    <div style="padding: 1rem; background: #fef2f2; border-radius: 10px; border-left: 4px solid #ef4444;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                            <span style="font-size: 1.5rem;">🔴</span>
                            <strong style="color: #991b1b;">Red - High Risk</strong>
                        </div>
                        <p style="color: #b91c1c; font-size: 0.875rem; margin: 0;">
                            Average < 50% • Missing submissions • Urgent intervention needed
                        </p>
                    </div>
                </div>
            </div>

            <!-- Students Grid -->
            <?php if (empty($students)): ?>
                <div class="no-goals">
                    <h3>👥 No Students Found</h3>
                    <p>No student records available.</p>
                </div>
            <?php else: ?>
                <div class="goals-grid">
                    <?php foreach ($students as $student): ?>
                        <?php
                            $submission_rate = $student['total_assessments'] > 0 
                                ? round(($student['total_submissions'] / $student['total_assessments']) * 100) 
                                : 0;
                            
                            $risk_class = strtolower($student['risk_status']) . '-risk';
                        ?>
                        <div class="student-card <?php echo $risk_class; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;">
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 0.5rem 0; color: var(--text-main);">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </h3>
                                    <p style="margin: 0; color: var(--text-muted); font-size: 0.875rem;">
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </p>
                                </div>
                                <div class="risk-indicator risk-<?php echo strtolower($student['risk_status']); ?>">
                                    <?php 
                                        if ($student['risk_status'] === 'Green') echo '🟢';
                                        elseif ($student['risk_status'] === 'Yellow') echo '🟡';
                                        else echo '🔴';
                                    ?>
                                </div>
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <div class="goal-meta" style="margin-bottom: 0.75rem;">
                                    <span><strong>📊 Avg Performance:</strong></span>
                                    <span><strong><?php echo $student['avg_performance'] ? round($student['avg_performance'], 1) . '%' : 'N/A'; ?></strong></span>
                                </div>
                                <div class="goal-meta" style="margin-bottom: 0.75rem;">
                                    <span><strong>📝 Submissions:</strong></span>
                                    <span><?php echo $student['total_submissions']; ?> / <?php echo $student['total_assessments']; ?></span>
                                </div>
                                <div class="goal-meta">
                                    <span><strong>🔔 Interventions:</strong></span>
                                    <span><?php echo $student['intervention_count']; ?></span>
                                </div>
                            </div>

                            <div class="progress-container">
                                <div class="progress-label">
                                    <span>Submission Rate</span>
                                    <span><?php echo $submission_rate; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $submission_rate; ?>%;"></div>
                                </div>
                            </div>

                            <div class="goal-actions" style="margin-top: 1.5rem;">
                                <button class="btn btn-edit" 
                                        onclick="updateRiskStatus(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', '<?php echo $student['risk_status']; ?>')">
                                    🔄 Update Status
                                </button>
                                <a href="intervention_log.php?student_id=<?php echo $student['id']; ?>" 
                                   class="btn btn-edit" style="text-decoration: none; text-align: center;">
                                    📋 Log Action
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Update Risk Status Modal -->
<div id="riskModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeRiskModal()">&times;</button>
        <h3 id="riskModalTitle" style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;"></h3>
        
        <form method="POST" action="">
            <input type="hidden" id="risk_student_id" name="student_id">
            
            <div class="form-group">
                <label for="risk_status">Risk Status</label>
                <select id="risk_status" name="risk_status" required style="font-size: 1.125rem; padding: 1rem;">
                    <option value="Green">🟢 Green - Low Risk</option>
                    <option value="Yellow">🟡 Yellow - Medium Risk</option>
                    <option value="Red">🔴 Red - High Risk</option>
                </select>
            </div>
            
            <button type="submit" name="update_risk" class="add-goal-btn" style="width: 100%;">
                Update Risk Status
            </button>
        </form>
    </div>
</div>

<script>
    function updateRiskStatus(studentId, studentName, currentStatus) {
        document.getElementById('riskModal').style.display = 'block';
        document.getElementById('riskModalTitle').textContent = 'Update Risk Status: ' + studentName;
        document.getElementById('risk_student_id').value = studentId;
        document.getElementById('risk_status').value = currentStatus;
    }

    function closeRiskModal() {
        document.getElementById('riskModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('riskModal');
        if (event.target === modal) {
            closeRiskModal();
        }
    }
</script>

</body>
</html>
<?php
mysqli_close($conn);
?>