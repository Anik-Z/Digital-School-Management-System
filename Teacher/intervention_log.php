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

// CREATE - Add intervention
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_intervention'])) {
    $student_id = (int)$_POST['student_id'];
    $action_type = mysqli_real_escape_string($conn, $_POST['action_type']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $intervention_date = mysqli_real_escape_string($conn, $_POST['intervention_date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $follow_up_date = !empty($_POST['follow_up_date']) ? mysqli_real_escape_string($conn, $_POST['follow_up_date']) : NULL;
    
    if (!empty($description)) {
        $sql = "INSERT INTO interventions (student_id, teacher_id, action_type, description, intervention_date, status, follow_up_date) 
                VALUES ('$student_id', '$teacher_id', '$action_type', '$description', '$intervention_date', '$status', " . ($follow_up_date ? "'$follow_up_date'" : "NULL") . ")";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Intervention logged successfully! 🎉";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}

// UPDATE - Edit intervention
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_intervention'])) {
    $intervention_id = (int)$_POST['intervention_id'];
    $action_type = mysqli_real_escape_string($conn, $_POST['action_type']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $intervention_date = mysqli_real_escape_string($conn, $_POST['intervention_date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $follow_up_date = !empty($_POST['follow_up_date']) ? mysqli_real_escape_string($conn, $_POST['follow_up_date']) : NULL;
    
    if (!empty($description)) {
        $sql = "UPDATE interventions 
                SET action_type = '$action_type', description = '$description', 
                    intervention_date = '$intervention_date', status = '$status', 
                    follow_up_date = " . ($follow_up_date ? "'$follow_up_date'" : "NULL") . "
                WHERE id = '$intervention_id' AND teacher_id = '$teacher_id'";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Intervention updated successfully! ✨";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}

// DELETE - Delete intervention
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $intervention_id = (int)$_GET['delete'];
    $sql = "DELETE FROM interventions WHERE id = '$intervention_id' AND teacher_id = '$teacher_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Intervention deleted successfully! 🗑️";
        header("Location: intervention_log.php");
        exit();
    }
}

// Fetch all students for dropdown
$students_sql = "SELECT id, name, risk_status FROM students ORDER BY name ASC";
$students_result = mysqli_query($conn, $students_sql);
$students = array();
if ($students_result) {
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}

// Fetch interventions with student info
$filter_student = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where_clause = "WHERE i.teacher_id = '$teacher_id'";
if ($filter_student > 0) {
    $where_clause .= " AND i.student_id = '$filter_student'";
}
if (!empty($filter_status)) {
    $where_clause .= " AND i.status = '$filter_status'";
}

$interventions_sql = "SELECT i.*, s.name as student_name, s.email as student_email, s.risk_status
                      FROM interventions i
                      INNER JOIN students s ON i.student_id = s.id
                      $where_clause
                      ORDER BY i.intervention_date DESC";
$interventions_result = mysqli_query($conn, $interventions_sql);
$interventions = array();
if ($interventions_result) {
    while ($row = mysqli_fetch_assoc($interventions_result)) {
        $interventions[] = $row;
    }
}

// Statistics
$total_interventions = count($interventions);
$pending_count = 0;
$in_progress_count = 0;
$resolved_count = 0;

foreach ($interventions as $intervention) {
    switch ($intervention['status']) {
        case 'Pending':
            $pending_count++;
            break;
        case 'In Progress':
            $in_progress_count++;
            break;
        case 'Resolved':
            $resolved_count++;
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intervention Log</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="common.css">
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
                    <a href="student_risk.php">
                        <span class="nav-icon">⚠️</span>
                        <span>Risk Indicators</span>
                    </a>
                </li>
                <li>
                    <a href="intervention_log.php" class="active">
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
            <h1 class="page-title">📋 Intervention Log</h1>
            <p class="page-subtitle">Track and manage interventions for at-risk students</p>
        </div>

        <div class="container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="overview-cards">
                <div class="overview-card">
                    <div class="card-icon">📋</div>
                    <div class="card-value"><?php echo $total_interventions; ?></div>
                    <div class="card-label">Total Interventions</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">⏳</div>
                    <div class="card-value"><?php echo $pending_count; ?></div>
                    <div class="card-label">Pending</div>
                    <div class="card-trend" style="color: #f59e0b;">Awaiting action</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">🔄</div>
                    <div class="card-value"><?php echo $in_progress_count; ?></div>
                    <div class="card-label">In Progress</div>
                    <div class="card-trend" style="color: #3b82f6;">Active interventions</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">✅</div>
                    <div class="card-value"><?php echo $resolved_count; ?></div>
                    <div class="card-label">Resolved</div>
                    <div class="card-trend" style="color: #059669;">Completed successfully</div>
                </div>
            </div>

            <!-- Action Buttons & Filters -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap;">
                <button class="add-goal-btn" onclick="toggleForm()">
                    ➕ Log New Intervention
                </button>
                
                <div style="display: flex; gap: 1rem;">
                    <select onchange="filterByStudent(this.value)" style="padding: 0.75rem; border: 1px solid var(--glass-border); border-radius: 10px; background: white;">
                        <option value="">All Students</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo $filter_student == $student['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select onchange="filterByStatus(this.value)" style="padding: 0.75rem; border: 1px solid var(--glass-border); border-radius: 10px; background: white;">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?php echo $filter_status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Resolved" <?php echo $filter_status === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
            </div>

            <!-- Create Intervention Form -->
            <div class="goal-form hidden" id="interventionForm">
                <h3>Log New Intervention</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="student_id">Student *</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['name']); ?> 
                                    <?php 
                                        if ($student['risk_status'] === 'Red') echo '🔴';
                                        elseif ($student['risk_status'] === 'Yellow') echo '🟡';
                                        else echo '🟢';
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="action_type">Action Type *</label>
                            <select id="action_type" name="action_type" required>
                                <option value="">Select Type</option>
                                <option value="Parent Meeting">Parent Meeting</option>
                                <option value="One-on-One Session">One-on-One Session</option>
                                <option value="Extra Help">Extra Help</option>
                                <option value="Counseling">Counseling</option>
                                <option value="Academic Support">Academic Support</option>
                                <option value="Behavioral Support">Behavioral Support</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="intervention_date">Intervention Date *</label>
                            <input type="date" id="intervention_date" name="intervention_date" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description / Notes *</label>
                        <textarea id="description" name="description" placeholder="Describe the intervention action taken..." required></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Resolved">Resolved</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="follow_up_date">Follow-up Date</label>
                            <input type="date" id="follow_up_date" name="follow_up_date">
                        </div>
                    </div>
                    
                    <button type="submit" name="create_intervention" class="add-goal-btn">
                        Log Intervention
                    </button>
                </form>
            </div>

            <!-- Interventions List -->
            <?php if (empty($interventions)): ?>
                <div class="no-goals">
                    <h3>📋 No Interventions Logged Yet</h3>
                    <p>Start by logging your first intervention for at-risk students.</p>
                </div>
            <?php else: ?>
                <div class="goals-grid">
                    <?php foreach ($interventions as $intervention): ?>
                        <?php
                            $status_class = '';
                            if ($intervention['status'] === 'Resolved') {
                                $status_class = 'badge-success';
                            } elseif ($intervention['status'] === 'In Progress') {
                                $status_class = 'badge-info';
                            } else {
                                $status_class = 'badge-warning';
                            }
                        ?>
                        <div class="goal-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin: 0 0 0.5rem 0;">
                                        <?php echo htmlspecialchars($intervention['student_name']); ?>
                                    </h3>
                                    <p style="margin: 0; color: var(--text-muted); font-size: 0.875rem;">
                                        <?php echo htmlspecialchars($intervention['student_email']); ?>
                                    </p>
                                </div>
                                <span style="font-size: 1.5rem;">
                                    <?php 
                                        if ($intervention['risk_status'] === 'Red') echo '🔴';
                                        elseif ($intervention['risk_status'] === 'Yellow') echo '🟡';
                                        else echo '🟢';
                                    ?>
                                </span>
                            </div>

                            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                                <span class="badge badge-info">
                                    <?php echo htmlspecialchars($intervention['action_type']); ?>
                                </span>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($intervention['status']); ?>
                                </span>
                            </div>

                            <p style="color: var(--text-muted); margin-bottom: 1rem; line-height: 1.6;">
                                <?php echo htmlspecialchars($intervention['description']); ?>
                            </p>

                            <div class="goal-meta" style="margin-bottom: 0.5rem;">
                                <span><strong>📅 Date:</strong></span>
                                <span><?php echo date('M d, Y', strtotime($intervention['intervention_date'])); ?></span>
                            </div>

                            <?php if ($intervention['follow_up_date']): ?>
                            <div class="goal-meta" style="margin-bottom: 1rem;">
                                <span><strong>🔔 Follow-up:</strong></span>
                                <span><?php echo date('M d, Y', strtotime($intervention['follow_up_date'])); ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="goal-actions">
                                <button class="btn btn-edit" 
                                        onclick="openEditModal(<?php echo $intervention['id']; ?>, <?php echo $intervention['student_id']; ?>, '<?php echo htmlspecialchars($intervention['action_type'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($intervention['description'], ENT_QUOTES); ?>', '<?php echo $intervention['intervention_date']; ?>', '<?php echo $intervention['status']; ?>', '<?php echo $intervention['follow_up_date']; ?>')">
                                    ✏️ Edit
                                </button>
                                <a href="?delete=<?php echo $intervention['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this intervention log?')" 
                                   class="btn btn-delete" style="text-decoration: none; text-align: center;">
                                    🗑️ Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeEditModal()">&times;</button>
        <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;">Edit Intervention</h3>
        
        <form method="POST" action="">
            <input type="hidden" id="edit_intervention_id" name="intervention_id">
            <input type="hidden" id="edit_student_id" name="student_id">
            
            <div class="form-group">
                <label for="edit_action_type">Action Type</label>
                <select id="edit_action_type" name="action_type" required>
                    <option value="Parent Meeting">Parent Meeting</option>
                    <option value="One-on-One Session">One-on-One Session</option>
                    <option value="Extra Help">Extra Help</option>
                    <option value="Counseling">Counseling</option>
                    <option value="Academic Support">Academic Support</option>
                    <option value="Behavioral Support">Behavioral Support</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description / Notes</label>
                <textarea id="edit_description" name="description" required></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit_intervention_date">Intervention Date</label>
                    <input type="date" id="edit_intervention_date" name="intervention_date" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_follow_up_date">Follow-up Date</label>
                <input type="date" id="edit_follow_up_date" name="follow_up_date">
            </div>
            
            <button type="submit" name="edit_intervention" class="add-goal-btn" style="width: 100%;">
                Save Changes
            </button>
        </form>
    </div>
</div>

<script>
    function toggleForm() {
        document.getElementById('interventionForm').classList.toggle('hidden');
    }

    function openEditModal(id, studentId, actionType, description, date, status, followUp) {
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_intervention_id').value = id;
        document.getElementById('edit_student_id').value = studentId;
        document.getElementById('edit_action_type').value = actionType;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_intervention_date').value = date;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_follow_up_date').value = followUp || '';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function filterByStudent(studentId) {
        const url = new URL(window.location.href);
        if (studentId) {
            url.searchParams.set('student_id', studentId);
        } else {
            url.searchParams.delete('student_id');
        }
        window.location.href = url.toString();
    }

    function filterByStatus(status) {
        const url = new URL(window.location.href);
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        window.location.href = url.toString();
    }

    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }
</script>

</body>
</html>
<?php
mysqli_close($conn);
?>