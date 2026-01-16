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

// Assume student_id from session
$student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : 1;

$success_message = '';
$error_message = '';

// SUBMIT - Submit assessment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assessment'])) {
    $assessment_id = (int)$_POST['assessment_id'];
    $answer_text = mysqli_real_escape_string($conn, trim($_POST['answer_text']));
    $submission_date = date('Y-m-d H:i:s');
    
    // Handle file upload
    $file_path = NULL;
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/assessments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['submission_file']['name']);
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $file_path)) {
            $file_path = 'uploads/assessments/' . $file_name;
        } else {
            $file_path = NULL;
        }
    }
    
    if (!empty($answer_text) || $file_path) {
        // Check if already submitted
        $check_sql = "SELECT id FROM assessment_submissions 
                      WHERE assessment_id = '$assessment_id' AND student_id = '$student_id'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing submission
            $sql = "UPDATE assessment_submissions 
                    SET answer_text = '$answer_text', file_path = " . ($file_path ? "'$file_path'" : "NULL") . ", 
                        submission_date = '$submission_date', status = 'Submitted' 
                    WHERE assessment_id = '$assessment_id' AND student_id = '$student_id'";
        } else {
            // Create new submission
            $sql = "INSERT INTO assessment_submissions (assessment_id, student_id, answer_text, file_path, submission_date, status) 
                    VALUES ('$assessment_id', '$student_id', '$answer_text', " . ($file_path ? "'$file_path'" : "NULL") . ", '$submission_date', 'Submitted')";
        }
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Assessment submitted successfully! 🎉";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Please provide an answer or upload a file!";
    }
}

// READ - Fetch all assessments assigned to this student
$sql = "SELECT a.*, 
        (SELECT status FROM assessment_submissions WHERE assessment_id = a.id AND student_id = '$student_id') as submission_status,
        (SELECT obtained_marks FROM assessment_submissions WHERE assessment_id = a.id AND student_id = '$student_id') as obtained_marks
        FROM assessments a 
        WHERE a.assigned_to_student = '$student_id' OR a.assigned_to_all = 1
        ORDER BY a.due_date ASC";
$result = mysqli_query($conn, $sql);
$assessments = array();

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $assessments[] = $row;
    }
}

// Calculate statistics
$total_assessments = count($assessments);
$submitted_count = 0;
$pending_count = 0;
$graded_count = 0;

foreach ($assessments as $assessment) {
    if ($assessment['submission_status'] === 'Submitted') {
        $submitted_count++;
    } elseif ($assessment['submission_status'] === 'Graded') {
        $graded_count++;
    } else {
        $pending_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/css/common.css">
    <style>
        .assessment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .assessment-card {
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
            padding: 1.75rem;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .assessment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary), #8b5cf6);
            border-radius: 20px 0 0 20px;
        }
        
        .assessment-card.submitted::before {
            background: linear-gradient(180deg, #3b82f6, #2563eb);
        }
        
        .assessment-card.graded::before {
            background: linear-gradient(180deg, #22c55e, #16a34a);
        }
        
        .assessment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
        }
        
        .assessment-type {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .type-quiz { background: #fef3c7; color: #92400e; }
        .type-assignment { background: #dbeafe; color: #1e40af; }
        .type-project { background: #e0e7ff; color: #4338ca; }
        .type-exam { background: #fee2e2; color: #991b1b; }
        
        .assessment-card h3 {
            color: var(--text-main);
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .assessment-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin: 1rem 0;
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .submit-assessment-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .submit-assessment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--primary-glow);
        }
        
        .submit-assessment-btn:disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }
        
        .upload-area {
            margin-top: 1rem;
            padding: 1.5rem;
            border: 2px dashed var(--glass-border);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            background: rgba(99, 102, 241, 0.05);
            border-color: var(--primary);
        }
        
        .upload-area input[type="file"] {
            display: none;
        }
        
        .upload-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .grade-display {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.125rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="user-info">
                <div class="user-avatar">AJ</div>
                <div class="user-details">
                    <h3>Alex Johnson</h3>
                    <p>Student</p>
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
                <li>
                    <a href="assessment.php" class="active">
                        <span class="nav-icon">📝</span>
                        <span>Assessments</span>
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
            <h1 class="page-title">📝 Assessments</h1>
            <p class="page-subtitle">View and submit your assigned assessments</p>
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
                </div>

                <div class="overview-card">
                    <div class="card-icon">✅</div>
                    <div class="card-value"><?php echo $submitted_count; ?></div>
                    <div class="card-label">Submitted</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">⏳</div>
                    <div class="card-value"><?php echo $pending_count; ?></div>
                    <div class="card-label">Pending</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">📊</div>
                    <div class="card-value"><?php echo $graded_count; ?></div>
                    <div class="card-label">Graded</div>
                </div>
            </div>

            <!-- Assessments Grid -->
            <?php if (empty($assessments)): ?>
                <div class="no-goals">
                    <h3>📝 No Assessments Assigned Yet!</h3>
                    <p>Your teacher hasn't assigned any assessments yet.</p>
                </div>
            <?php else: ?>
                <div class="assessment-grid">
                    <?php foreach ($assessments as $assessment): ?>
                        <?php
                            $is_submitted = !empty($assessment['submission_status']);
                            $is_graded = $assessment['submission_status'] === 'Graded';
                            $is_overdue = strtotime($assessment['due_date']) < time() && !$is_submitted;
                        ?>
                        <div class="assessment-card <?php echo $is_graded ? 'graded' : ($is_submitted ? 'submitted' : ''); ?>">
                            <span class="assessment-type type-<?php echo strtolower($assessment['type']); ?>">
                                <?php echo strtoupper($assessment['type']); ?>
                            </span>
                            
                            <h3><?php echo htmlspecialchars($assessment['title']); ?></h3>
                            
                            <p style="color: var(--text-muted); margin-bottom: 1rem; line-height: 1.6;">
                                <?php echo htmlspecialchars($assessment['description']); ?>
                            </p>
                            
                            <div class="assessment-meta">
                                <div class="meta-item">
                                    <span>📚</span>
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($assessment['subject']); ?>
                                </div>
                                <div class="meta-item">
                                    <span>📅</span>
                                    <strong>Due Date:</strong> 
                                    <span style="color: <?php echo $is_overdue ? '#dc2626' : 'inherit'; ?>">
                                        <?php echo date('M d, Y', strtotime($assessment['due_date'])); ?>
                                        <?php if ($is_overdue): ?>
                                            (Overdue)
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="meta-item">
                                    <span>💯</span>
                                    <strong>Total Marks:</strong> <?php echo $assessment['total_marks']; ?>
                                </div>
                                <?php if ($assessment['duration']): ?>
                                <div class="meta-item">
                                    <span>⏱️</span>
                                    <strong>Duration:</strong> <?php echo $assessment['duration']; ?> minutes
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($is_graded): ?>
                                <div class="grade-display">
                                    Grade: <?php echo $assessment['obtained_marks']; ?> / <?php echo $assessment['total_marks']; ?>
                                </div>
                            <?php elseif ($is_submitted): ?>
                                <span class="badge badge-info" style="display: inline-block; margin-top: 1rem;">
                                    ✅ Submitted - Awaiting Grade
                                </span>
                            <?php else: ?>
                                <button class="submit-assessment-btn" 
                                        onclick="openSubmissionModal(<?php echo $assessment['id']; ?>, '<?php echo htmlspecialchars($assessment['title'], ENT_QUOTES); ?>')"
                                        <?php echo $is_overdue ? 'disabled title="Assessment is overdue"' : ''; ?>>
                                    <?php echo $is_overdue ? '⏰ Overdue' : '📤 Submit Assessment'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Submission Modal -->
<div id="submissionModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeSubmissionModal()">&times;</button>
        <h3 id="modalTitle" style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;"></h3>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" id="assessment_id" name="assessment_id">
            
            <div class="form-group">
                <label for="answer_text">Your Answer / Solution:</label>
                <textarea id="answer_text" name="answer_text" placeholder="Type your answer here..." style="min-height: 150px;"></textarea>
            </div>

            <label for="submission_file" class="upload-area">
                <input type="file" id="submission_file" name="submission_file" accept=".pdf,.doc,.docx,.txt,.jpg,.png">
                <div class="upload-icon">📎</div>
                <div>
                    <strong>Click to upload supporting files</strong><br>
                    <small style="color: var(--text-muted);">PDF, DOC, DOCX, TXT, Images (Max 10MB)</small>
                </div>
                <div id="fileName" style="margin-top: 0.5rem; color: var(--primary); font-weight: 600;"></div>
            </label>

            <button type="submit" name="submit_assessment" class="add-goal-btn" style="width: 100%; margin-top: 1.5rem;">
                Submit Assessment 🚀
            </button>
        </form>
    </div>
</div>

<script>
    function openSubmissionModal(id, title) {
        document.getElementById('submissionModal').style.display = 'block';
        document.getElementById('modalTitle').textContent = 'Submit: ' + title;
        document.getElementById('assessment_id').value = id;
        document.getElementById('answer_text').value = '';
        document.getElementById('submission_file').value = '';
        document.getElementById('fileName').textContent = '';
    }

    function closeSubmissionModal() {
        document.getElementById('submissionModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('submissionModal');
        if (event.target === modal) {
            closeSubmissionModal();
        }
    }

    // File upload feedback
    document.getElementById('submission_file').addEventListener('change', function(e) {
        const fileName = document.getElementById('fileName');
        if (e.target.files.length > 0) {
            fileName.textContent = '✓ ' + e.target.files[0].name;
        } else {
            fileName.textContent = '';
        }
    });
</script>

</body>
</html>
<?php
mysqli_close($conn);
?>