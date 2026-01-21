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

// Assume teacher_id from session
$teacher_id = isset($_SESSION['teacher_id']) ? $_SESSION['teacher_id'] : 1;
$teacher_name = isset($_SESSION['teacher_name']) ? $_SESSION['teacher_name'] : 'Teacher';

$success_message = '';
$error_message = '';

// CREATE - Add new assessment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assessment'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $total_marks = (int)$_POST['total_marks'];
    $duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : NULL;
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $assign_to = $_POST['assign_to'];
    
    if (!empty($title) && !empty($description) && !empty($subject) && $total_marks > 0) {
        if ($assign_to === 'all') {
            // Assign to all students
            $sql = "INSERT INTO assessments (title, description, subject, type, total_marks, duration, due_date, assigned_to_all, created_by) 
                    VALUES ('$title', '$description', '$subject', '$type', '$total_marks', " . ($duration ? "'$duration'" : "NULL") . ", '$due_date', 1, '$teacher_id')";
        } else {
            // Assign to specific student
            $student_id = (int)$assign_to;
            $sql = "INSERT INTO assessments (title, description, subject, type, total_marks, duration, due_date, assigned_to_student, assigned_to_all, created_by) 
                    VALUES ('$title', '$description', '$subject', '$type', '$total_marks', " . ($duration ? "'$duration'" : "NULL") . ", '$due_date', '$student_id', 0, '$teacher_id')";
        }
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Assessment created and assigned successfully! 🎉";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    } else {
        $error_message = "All required fields must be filled!";
    }
}

// UPDATE - Edit assessment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_assessment'])) {
    $assessment_id = (int)$_POST['assessment_id'];
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $total_marks = (int)$_POST['total_marks'];
    $duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : NULL;
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    
    if (!empty($title) && !empty($description) && !empty($subject) && $total_marks > 0) {
        $sql = "UPDATE assessments 
                SET title = '$title', description = '$description', subject = '$subject', 
                    type = '$type', total_marks = '$total_marks', 
                    duration = " . ($duration ? "'$duration'" : "NULL") . ", due_date = '$due_date'
                WHERE id = '$assessment_id' AND created_by = '$teacher_id'";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Assessment updated successfully! ✨";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}

// DELETE - Delete assessment
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $assessment_id = (int)$_GET['delete'];
    
    // Delete submissions first
    $delete_submissions = "DELETE FROM assessment_submissions WHERE assessment_id = '$assessment_id'";
    mysqli_query($conn, $delete_submissions);
    
    // Delete assessment
    $sql = "DELETE FROM assessments WHERE id = '$assessment_id' AND created_by = '$teacher_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Assessment deleted successfully! 🗑️";
        header("Location: assessment_management.php");
        exit();
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// GRADE - Grade student submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = (int)$_POST['submission_id'];
    $obtained_marks = (int)$_POST['obtained_marks'];
    $feedback = mysqli_real_escape_string($conn, trim($_POST['feedback']));
    $graded_at = date('Y-m-d H:i:s');
    
    $sql = "UPDATE assessment_submissions 
            SET obtained_marks = '$obtained_marks', feedback = '$feedback', 
                status = 'Graded', graded_by = '$teacher_id', graded_at = '$graded_at'
            WHERE id = '$submission_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Submission graded successfully! 📝";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// READ - Fetch all assessments created by this teacher
$sql = "SELECT a.*, 
        COUNT(DISTINCT s.id) as submission_count
        FROM assessments a
        LEFT JOIN assessment_submissions s ON a.id = s.assessment_id
        WHERE a.created_by = '$teacher_id'
        GROUP BY a.id
        ORDER BY a.created_at DESC";
$result = mysqli_query($conn, $sql);
$assessments = array();

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $assessments[] = $row;
    }
}

// Fetch all students for assignment dropdown
$students_sql = "SELECT id, name, email FROM students ORDER BY name ASC";
$students_result = mysqli_query($conn, $students_sql);
$students = array();

if ($students_result) {
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}

// Statistics
$total_assessments = count($assessments);
$total_submissions = 0;
$pending_grading = 0;
$graded_count = 0;

foreach ($assessments as $assessment) {
    $total_submissions += $assessment['submission_count'];
}

$pending_sql = "SELECT COUNT(*) as pending FROM assessment_submissions s
                INNER JOIN assessments a ON s.assessment_id = a.id
                WHERE a.created_by = '$teacher_id' AND s.status = 'Submitted'";
$pending_result = mysqli_query($conn, $pending_sql);
if ($pending_result) {
    $pending_row = mysqli_fetch_assoc($pending_result);
    $pending_grading = $pending_row['pending'];
}

$graded_sql = "SELECT COUNT(*) as graded FROM assessment_submissions s
               INNER JOIN assessments a ON s.assessment_id = a.id
               WHERE a.created_by = '$teacher_id' AND s.status = 'Graded'";
$graded_result = mysqli_query($conn, $graded_sql);
if ($graded_result) {
    $graded_row = mysqli_fetch_assoc($graded_result);
    $graded_count = $graded_row['graded'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Management - Teacher</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/css/common.css">
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

    <!-- Main Content -->
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

            <!-- Statistics Cards -->
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

            <!-- Create Assessment Button -->
            <button class="add-goal-btn" onclick="toggleForm()" style="margin-bottom: 2rem;">
                ➕ Create New Assessment
            </button>

            <!-- Create Assessment Form -->
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

            <!-- Assessments Table View -->
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

            <!-- Assessments Grid -->
            <?php if (empty($assessments)): ?>
                <div class="no-goals" id="cardView">
                    <h3>📝 No Assessments Created Yet!</h3>
                    <p>Create your first assessment to assign to students.</p>
                </div>
            <?php else: ?>
                <div class="goals-grid" id="cardView">
                    <?php foreach ($assessments as $assessment): ?>
                        <?php
                            // Get expected student count
                            if ($assessment['assigned_to_all'] == 1) {
                                $expected_count = count($students);
                                $assigned_to_text = 'All Students';
                                $assigned_badge = 'badge-success';
                            } else {
                                $expected_count = 1;
                                // Get student name
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

<!-- Edit Modal -->
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

<!-- Submissions Modal -->
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
        
        // Fetch submissions via AJAX
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
            // Show card view, hide table view
            cardView.classList.remove('hidden');
            tableView.classList.add('hidden');
            toggleText.textContent = '📋 Table View';
        } else {
            // Show table view, hide card view
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