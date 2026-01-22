<?php
session_start();


$conn = mysqli_connect('localhost', 'root', '', 'digital_school_management_system');
if (!$conn) {
    die("Database connection failed. Please check your database credentials.");
}


if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2; 
    $_SESSION['role'] = 'teacher';
    $_SESSION['full_name'] = 'John Smith';
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'];


$success_message = '';
$error_message = '';
$students = [];
$assessments = [];


$check_assessments = mysqli_query($conn, "SHOW TABLES LIKE 'assessments'");
if (!$check_assessments || mysqli_num_rows($check_assessments) == 0) {

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS assessments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        subject VARCHAR(50) NOT NULL,
        type VARCHAR(50) NOT NULL,
        total_marks INT NOT NULL,
        duration INT,
        due_date DATE NOT NULL,
        created_by INT NOT NULL,
        assigned_to_all BOOLEAN DEFAULT 1,
        assigned_to_student INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}


$check_submissions = mysqli_query($conn, "SHOW TABLES LIKE 'submissions'");
if (!$check_submissions || mysqli_num_rows($check_submissions) == 0) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assessment_id INT NOT NULL,
        student_id INT NOT NULL,
        answer_text TEXT,
        file_path VARCHAR(255),
        file_name VARCHAR(255),
        submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        obtained_marks DECIMAL(5,2),
        submission_status VARCHAR(50) DEFAULT 'Submitted',
        feedback TEXT,
        FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE
    )");
}


$check_users = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (!$check_users || mysqli_num_rows($check_users) == 0) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        password VARCHAR(255),
        role VARCHAR(20),
        subject VARCHAR(50),
        class VARCHAR(20),
        risk_status VARCHAR(20) DEFAULT 'Green',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
 
    mysqli_query($conn, "INSERT INTO users (full_name, role) VALUES ('$teacher_name', 'teacher')");
    mysqli_query($conn, "INSERT INTO users (full_name, role) VALUES ('Alice Brown', 'student')");
    mysqli_query($conn, "INSERT INTO users (full_name, role) VALUES ('Bob Wilson', 'student')");
    mysqli_query($conn, "INSERT INTO users (full_name, role) VALUES ('Charlie Davis', 'student')");
}


$student_query = "SELECT id, full_name as name FROM users WHERE role = 'student' ORDER BY full_name ASC";
$student_result = mysqli_query($conn, $student_query);
if ($student_result && mysqli_num_rows($student_result) > 0) {
    while ($row = mysqli_fetch_assoc($student_result)) {
        $students[] = $row;
    }
}


$assessment_query = "SELECT a.*, 
                    (SELECT COUNT(*) FROM submissions WHERE assessment_id = a.id) as submission_count
                    FROM assessments a
                    WHERE a.created_by = '$teacher_id'
                    ORDER BY a.created_at DESC";
    
$assessment_result = mysqli_query($conn, $assessment_query);
if ($assessment_result) {
    if (mysqli_num_rows($assessment_result) > 0) {
        while ($row = mysqli_fetch_assoc($assessment_result)) {
            $assessments[] = $row;
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_assessment'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $subject = trim($_POST['subject']);
        $type = trim($_POST['type']);
        $total_marks = intval($_POST['total_marks']);
        $duration = !empty($_POST['duration']) ? intval($_POST['duration']) : NULL;
        $due_date = trim($_POST['due_date']);
        $assign_to = $_POST['assign_to'];
        
        
        if (empty($title) || empty($description) || empty($subject) || empty($type) || empty($total_marks) || empty($due_date)) {
            $error_message = "All required fields must be filled!";
        } else {
   
            $title = mysqli_real_escape_string($conn, $title);
            $description = mysqli_real_escape_string($conn, $description);
            $subject = mysqli_real_escape_string($conn, $subject);
            $type = mysqli_real_escape_string($conn, $type);
            $due_date = mysqli_real_escape_string($conn, $due_date);
            

            $assigned_to_all = ($assign_to == 'all') ? 1 : 0;
            $assigned_to_student = ($assign_to != 'all') ? intval($assign_to) : NULL;
            
        
            $sql = "INSERT INTO assessments (title, description, subject, type, total_marks, duration, due_date, created_by, assigned_to_all, assigned_to_student) 
                    VALUES ('$title', '$description', '$subject', '$type', $total_marks, " . 
                    ($duration ? "$duration" : "NULL") . ", '$due_date', '$teacher_id', $assigned_to_all, " . 
                    ($assigned_to_student ? "$assigned_to_student" : "NULL") . ")";
            
            if (mysqli_query($conn, $sql)) {
                $success_message = "✅ Assessment created successfully!";
   
                header("Location: assessment_management.php?success=1");
                exit();
            } else {
                $error_message = "❌ Error creating assessment: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['edit_assessment'])) {
        $assessment_id = intval($_POST['assessment_id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $subject = mysqli_real_escape_string($conn, $_POST['subject']);
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $total_marks = intval($_POST['total_marks']);
        $duration = !empty($_POST['duration']) ? intval($_POST['duration']) : NULL;
        $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
        
        $sql = "UPDATE assessments SET 
                title = '$title',
                description = '$description',
                subject = '$subject',
                type = '$type',
                total_marks = $total_marks,
                duration = " . ($duration ? "$duration" : "NULL") . ",
                due_date = '$due_date'
                WHERE id = $assessment_id AND created_by = '$teacher_id'";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Assessment updated successfully!";
            header("Location: assessment_management.php");
            exit();
        } else {
            $error_message = "Error updating assessment: " . mysqli_error($conn);
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM assessments WHERE id = $delete_id AND created_by = '$teacher_id'";
    if (mysqli_query($conn, $delete_sql)) {
        $success_message = "Assessment deleted successfully!";
        header("Location: assessment_management.php");
        exit();
    } else {
        $error_message = "Error deleting assessment: " . mysqli_error($conn);
    }
}


if (isset($_GET['success'])) {
    $success_message = "✅ Assessment created successfully!";
}


$total_assessments = count($assessments);
$total_submissions = 0;
$pending_grading = 0;
$graded_count = 0;

foreach ($assessments as $assessment) {
    $total_submissions += intval($assessment['submission_count'] ?? 0);
}


$pending_query = "SELECT COUNT(*) as pending FROM submissions s
                 INNER JOIN assessments a ON s.assessment_id = a.id
                 WHERE a.created_by = '$teacher_id' AND (s.submission_status = 'Submitted')";
$pending_result = mysqli_query($conn, $pending_query);
if ($pending_result && $row = mysqli_fetch_assoc($pending_result)) {
    $pending_grading = $row['pending'];
}

$graded_query = "SELECT COUNT(*) as graded FROM submissions s
                INNER JOIN assessments a ON s.assessment_id = a.id
                WHERE a.created_by = '$teacher_id' AND s.obtained_marks IS NOT NULL";
$graded_result = mysqli_query($conn, $graded_query);
if ($graded_result && $row = mysqli_fetch_assoc($graded_result)) {
    $graded_count = $row['graded'];
}


if (empty($students)) {
    $students = [
        ['id' => 1, 'name' => 'Alice Brown'],
        ['id' => 2, 'name' => 'Bob Wilson'],
        ['id' => 3, 'name' => 'Charlie Davis']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Management - Teacher</title>
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
                    <a href="dashboard.php">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="assessment_management.php" class="active">
                        <span class="nav-icon">📝</span>
                        <span>Assessments Management</span>
                    </a>
                </li>
                <li>
                    <a href="student_risk.php">
                        <span class="nav-icon">👥</span>
                        <span>Risk_Indicator</span>
                    </a>
                </li>
                <li>
                    <a href="intervention_log.php">
                        <span class="nav-icon">📈</span>
                        <span>Intervention_log</span>
                    </a>
                </li>
                <li>
                    <a href="notices.php">
                        <span class="nav-icon">📢</span>
                        <span>Notices</span>
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
            <h1 class="page-title">📝 Assessment Management</h1>
            <p class="page-subtitle">Create, manage and grade student assessments</p>
        </div>

        <div class="container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            
            <div class="overview-cards">
                <div class="overview-card">
                    <div class="card-icon">📋</div>
                    <div class="card-value"><?php echo $total_assessments; ?></div>
                    <div class="card-label">Total Assessments</div>
                    <div class="card-trend">Active assignments</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">📤</div>
                    <div class="card-value"><?php echo $total_submissions; ?></div>
                    <div class="card-label">Total Submissions</div>
                    <div class="card-trend">From students</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">⏳</div>
                    <div class="card-value"><?php echo $pending_grading; ?></div>
                    <div class="card-label">Pending Grading</div>
                    <div class="card-trend" style="color: #f59e0b;">Needs attention</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">✅</div>
                    <div class="card-value"><?php echo $graded_count; ?></div>
                    <div class="card-label">Graded</div>
                    <div class="card-trend">Completed</div>
                </div>
            </div>

            
            <button class="add-goal-btn" onclick="toggleForm()" style="margin-bottom: 2rem;">
                ➕ Create New Assessment
            </button>

            
            <div class="goal-form hidden" id="assessmentForm">
                <h3>Create New Assessment</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Assessment Title *</label>
                        <input type="text" id="title" name="title" placeholder="e.g., Mid-term Mathematics Exam" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" placeholder="Describe what this assessment covers, instructions, or any special notes..." required></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" placeholder="e.g., Mathematics, Science, English" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="type">Assessment Type *</label>
                            <select id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="Quiz">Quiz</option>
                                <option value="Assignment">Assignment</option>
                                <option value="Project">Project</option>
                                <option value="Exam">Exam</option>
                                <option value="Class Test">Class Test</option>
                                <option value="Presentation">Presentation</option>
                                <option value="Lab Report">Lab Report</option>
                                <option value="Essay">Essay</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="total_marks">Total Marks *</label>
                            <input type="number" id="total_marks" name="total_marks" placeholder="e.g., 100" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">Duration (minutes)</label>
                            <input type="number" id="duration" name="duration" placeholder="e.g., 60 (optional)" min="0">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="due_date">Due Date *</label>
                            <input type="date" id="due_date" name="due_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="assign_to">Assign To *</label>
                            <select id="assign_to" name="assign_to" required>
                                <option value="all">📢 All Students</option>
                                <optgroup label="Individual Students">
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            👤 <?php echo htmlspecialchars($student['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="create_assessment" class="add-goal-btn">
                        Create & Assign Assessment
                    </button>
                </form>
            </div>

        
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">All Assessments</h2>
                    <button class="btn btn-edit" onclick="toggleView()" style="padding: 0.625rem 1.25rem;">
                        <span id="viewToggleText">📋 Table View</span>
                    </button>
                </div>
                
                <div id="tableView" class="hidden">
                    <?php if (empty($assessments)): ?>
                        <div class="no-goals">
                            <h3>📝 No Assessments Created Yet!</h3>
                            <p>Create your first assessment to assign to students.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Type</th>
                                    <th>Due Date</th>
                                    <th>Marks</th>
                                    <th>Assigned To</th>
                                    <th>Submissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assessments as $assessment): ?>
                                    <?php
                                        if ($assessment['assigned_to_all'] == 1) {
                                            $expected_count = count($students);
                                            $assigned_to_text = 'All Students';
                                        } else {
                                            $expected_count = 1;
                                            $student_name_sql = "SELECT name FROM students WHERE id = " . $assessment['assigned_to_student'];
                                            $student_name_result = mysqli_query($conn, $student_name_sql);
                                            if ($student_name_result && mysqli_num_rows($student_name_result) > 0) {
                                                $student_name_row = mysqli_fetch_assoc($student_name_result);
                                                $assigned_to_text = $student_name_row['name'];
                                            } else {
                                                $assigned_to_text = 'Specific Student';
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($assessment['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($assessment['subject']); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo $assessment['type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($assessment['due_date'])); ?></td>
                                        <td><strong><?php echo $assessment['total_marks']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($assigned_to_text); ?></td>
                                        <td>
                                            <strong><?php echo $assessment['submission_count']; ?></strong> / <?php echo $expected_count; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-edit" style="padding: 0.5rem 1rem; font-size: 0.8rem; margin-right: 0.25rem;" 
                                                    onclick="viewSubmissions(<?php echo $assessment['id']; ?>, '<?php echo htmlspecialchars($assessment['title'], ENT_QUOTES); ?>')">
                                                👁️
                                            </button>
                                            <button class="btn btn-edit" style="padding: 0.5rem 1rem; font-size: 0.8rem; margin-right: 0.25rem;" 
                                                    onclick="openEditModal(<?php echo $assessment['id']; ?>, '<?php echo htmlspecialchars($assessment['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($assessment['description'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($assessment['subject'], ENT_QUOTES); ?>', '<?php echo $assessment['type']; ?>', <?php echo $assessment['total_marks']; ?>, <?php echo $assessment['duration'] ? $assessment['duration'] : 'null'; ?>, '<?php echo $assessment['due_date']; ?>')">
                                                ✏️
                                            </button>
                                            <a href="?delete=<?php echo $assessment['id']; ?>" 
                                               onclick="return confirm('Are you sure? This will delete all submissions too!')" 
                                               class="btn btn-delete" style="padding: 0.5rem 1rem; font-size: 0.8rem; text-decoration: none; display: inline-block;">
                                                🗑️
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            
            <?php if (empty($assessments)): ?>
                <div class="no-goals" id="cardView">
                    <h3>📝 No Assessments Created Yet!</h3>
                    <p>Create your first assessment to assign to students.</p>
                </div>
            <?php else: ?>
                <div class="goals-grid" id="cardView">
                    <?php foreach ($assessments as $assessment): ?>
                        <?php
                            
                            if ($assessment['assigned_to_all'] == 1) {
                                $expected_count = count($students);
                                $assigned_to_text = 'All Students';
                                $assigned_badge = 'badge-success';
                            } else {
                                $expected_count = 1;
                                
                                $student_name_sql = "SELECT name FROM students WHERE id = " . $assessment['assigned_to_student'];
                                $student_name_result = mysqli_query($conn, $student_name_sql);
                                if ($student_name_result && mysqli_num_rows($student_name_result) > 0) {
                                    $student_name_row = mysqli_fetch_assoc($student_name_result);
                                    $assigned_to_text = $student_name_row['name'];
                                } else {
                                    $assigned_to_text = 'Specific Student';
                                }
                                $assigned_badge = 'badge-info';
                            }
                            
                            $submission_percentage = $expected_count > 0 ? round(($assessment['submission_count'] / $expected_count) * 100) : 0;
                        ?>
                        <div class="goal-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <span class="status-badge <?php echo $assigned_badge; ?>">
                                    <?php echo htmlspecialchars($assigned_to_text); ?>
                                </span>
                                <span class="badge badge-warning">
                                    <?php echo htmlspecialchars($assessment['type']); ?>
                                </span>
                            </div>
                            
                            <h3><?php echo htmlspecialchars($assessment['title']); ?></h3>
                            <p><?php echo htmlspecialchars($assessment['description']); ?></p>
                            
                            <div class="goal-meta">
                                <span><strong>📚 Subject:</strong> <?php echo htmlspecialchars($assessment['subject']); ?></span>
                            </div>
                            
                            <div class="goal-meta">
                                <span><strong>📅 Due:</strong> <?php echo date('M d, Y', strtotime($assessment['due_date'])); ?></span>
                                <span><strong>💯 Marks:</strong> <?php echo $assessment['total_marks']; ?></span>
                            </div>
                            
                            <?php if ($assessment['duration']): ?>
                            <div class="goal-meta">
                                <span><strong>⏱️ Duration:</strong> <?php echo $assessment['duration']; ?> minutes</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span>Submissions</span>
                                    <span><?php echo $assessment['submission_count']; ?> / <?php echo $expected_count; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $submission_percentage; ?>%;"></div>
                                </div>
                            </div>

                            <div class="goal-actions">
                                <button class="btn btn-edit" 
                                        onclick="viewSubmissions(<?php echo $assessment['id']; ?>, '<?php echo htmlspecialchars($assessment['title'], ENT_QUOTES); ?>')">
                                    👁️ View
                                </button>
                                <button class="btn btn-edit" 
                                        onclick="openEditModal(<?php echo $assessment['id']; ?>, '<?php echo htmlspecialchars($assessment['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($assessment['description'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($assessment['subject'], ENT_QUOTES); ?>', '<?php echo $assessment['type']; ?>', <?php echo $assessment['total_marks']; ?>, <?php echo $assessment['duration'] ? $assessment['duration'] : 'null'; ?>, '<?php echo $assessment['due_date']; ?>')">
                                    ✏️ Edit
                                </button>
                                <a href="?delete=<?php echo $assessment['id']; ?>" 
                                   onclick="return confirm('Are you sure? This will delete all submissions too!')" 
                                   class="btn btn-delete">
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


<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeEditModal()">&times;</button>
        <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;">Edit Assessment</h3>
        
        <form method="POST" action="">
            <input type="hidden" id="edit_assessment_id" name="assessment_id">
            
            <div class="form-group">
                <label for="edit_title">Assessment Title</label>
                <input type="text" id="edit_title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" required></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit_subject">Subject</label>
                    <input type="text" id="edit_subject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_type">Type</label>
                    <select id="edit_type" name="type" required>
                        <option value="Quiz">Quiz</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Project">Project</option>
                        <option value="Exam">Exam</option>
                        <option value="Class Test">Class Test</option>
                        <option value="Presentation">Presentation</option>
                        <option value="Lab Report">Lab Report</option>
                        <option value="Essay">Essay</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit_total_marks">Total Marks</label>
                    <input type="number" id="edit_total_marks" name="total_marks" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_duration">Duration (minutes)</label>
                    <input type="number" id="edit_duration" name="duration" min="0">
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_due_date">Due Date</label>
                <input type="date" id="edit_due_date" name="due_date" required>
            </div>
            
            <button type="submit" name="edit_assessment" class="add-goal-btn" style="width: 100%;">
                Save Changes
            </button>
        </form>
    </div>
</div>


<div id="submissionsModal" class="modal">
    <div class="modal-content" style="max-width: 900px; max-height: 85vh; overflow-y: auto;">
        <button class="close-modal" onclick="closeSubmissionsModal()">&times;</button>
        <h3 id="submissionsTitle" style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;"></h3>
        <div id="submissionsContent">
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                Loading submissions...
            </div>
        </div>
    </div>
</div>

<script>
    function toggleForm() {
        document.getElementById('assessmentForm').classList.toggle('hidden');
    }

    function openEditModal(id, title, description, subject, type, marks, duration, dueDate) {
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_assessment_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_subject').value = subject;
        document.getElementById('edit_type').value = type;
        document.getElementById('edit_total_marks').value = marks;
        document.getElementById('edit_duration').value = duration || '';
        document.getElementById('edit_due_date').value = dueDate;
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function viewSubmissions(assessmentId, title) {
        document.getElementById('submissionsModal').style.display = 'block';
        document.getElementById('submissionsTitle').textContent = 'Submissions: ' + title;
        
        
        fetch('get_submissions.php?assessment_id=' + assessmentId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('submissionsContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('submissionsContent').innerHTML = '<div class="no-goals"><h3>❌ Error Loading Submissions</h3><p>Please try again.</p></div>';
            });
    }

    function closeSubmissionsModal() {
        document.getElementById('submissionsModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const submissionsModal = document.getElementById('submissionsModal');
        
        if (event.target === editModal) {
            closeEditModal();
        }
        if (event.target === submissionsModal) {
            closeSubmissionsModal();
        }
    }

    function toggleView() {
        const cardView = document.getElementById('cardView');
        const tableView = document.getElementById('tableView');
        const toggleText = document.getElementById('viewToggleText');
        
        if (cardView.classList.contains('hidden')) {
            
            cardView.classList.remove('hidden');
            tableView.classList.add('hidden');
            toggleText.textContent = '📋 Table View';
        } else {
        
            cardView.classList.add('hidden');
            tableView.classList.remove('hidden');
            toggleText.textContent = '🎴 Card View';
        }
    }
</script>

</body>
</html>
<?php
mysqli_close($conn);
?>