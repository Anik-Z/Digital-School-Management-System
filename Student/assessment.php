<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 4; 
    $_SESSION['role'] = 'student';
    $_SESSION['class'] = '10A';
    $_SESSION['full_name'] = 'Alice Brown';
}

$user_id = $_SESSION['user_id'];
$class = $_SESSION['class'];

$conn = mysqli_connect('localhost', 'root', '', 'digital_school_management_system'); 
if (!$conn) {
    // If connection fails, use demo data
    $use_demo_data = true;
} else {
    $use_demo_data = false;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assessment'])) {
    if (!$use_demo_data) {
        $assessment_id = mysqli_real_escape_string($conn, $_POST['assessment_id']);
        $answer_text = mysqli_real_escape_string($conn, $_POST['answer_text']);
        
        // File upload handling
        $file_name = '';
        $file_path = '';
        
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['submission_file']['tmp_name'];
            $file_name = basename($_FILES['submission_file']['name']);
            $upload_dir = '../uploads/submissions/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $new_file_name = time() . '_' . $user_id . '_' . $file_name;
            $dest_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                $file_path = $dest_path;
            }
        }
        
        $submission_date = date('Y-m-d H:i:s');
        
        // Check if submissions table exists
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'submissions'");
        if (mysqli_num_rows($table_check) > 0) {
            // Check if already submitted
            $check_sql = "SELECT id FROM submissions WHERE student_id = '$user_id' AND assessment_id = '$assessment_id'";
            $check_result = mysqli_query($conn, $check_sql);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error_message = "You have already submitted this assessment.";
            } else {
                $insert_sql = "INSERT INTO submissions (assessment_id, student_id, answer_text, file_path, file_name, submission_date, submission_status) 
                              VALUES ('$assessment_id', '$user_id', '$answer_text', '$file_path', '$file_name', '$submission_date', 'Submitted')";
                
                if (mysqli_query($conn, $insert_sql)) {
                    $success_message = "Assessment submitted successfully!";
                    echo "<script>setTimeout(function(){ window.location.reload(); }, 1000);</script>";
                } else {
                    $error_message = "Error: " . mysqli_error($conn);
                }
            }
        } else {
            $error_message = "Database tables not set up. Using demo mode.";
            $use_demo_data = true;
        }
    } else {
        $success_message = "Assessment submitted successfully! (Demo Mode)";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 1000);</script>";
    }
}

// Fetch assessments for the student
$assessments = [];

if (!$use_demo_data) {
    // Check if assessments table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'assessments'");
    if (mysqli_num_rows($table_check) > 0) {
      
        $sql = "SELECT a.*, 
                       s.obtained_marks,
                       s.submission_status
                FROM assessments a
                LEFT JOIN submissions s ON a.id = s.assessment_id AND s.student_id = '$user_id'
                WHERE a.assigned_to_all = 1
                ORDER BY a.due_date ASC";
        
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Add missing fields if they don't exist
                if (!isset($row['duration'])) $row['duration'] = 60;
                if (!isset($row['is_overdue'])) {
                    $due_date = strtotime($row['due_date']);
                    $current_time = time();
                    $row['is_overdue'] = ($due_date < $current_time && empty($row['submission_status']));
                }
                $assessments[] = $row;
            }
        } else {
           
            $use_demo_data = true;
        }
    } else {
        $use_demo_data = true;
    }
}


if ($use_demo_data || empty($assessments)) {
    $assessments = [
        [
            'id' => 1,
            'title' => 'Math Quiz 1',
            'description' => 'Basic algebra and geometry quiz',
            'subject' => 'Mathematics',
            'type' => 'Quiz',
            'total_marks' => 20,
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'duration' => 60,
            'submission_status' => null,
            'obtained_marks' => null,
            'is_overdue' => false
        ],
        [
            'id' => 2,
            'title' => 'Science Project',
            'description' => 'Solar system model project',
            'subject' => 'Science',
            'type' => 'Project',
            'total_marks' => 100,
            'due_date' => date('Y-m-d', strtotime('+14 days')),
            'duration' => null,
            'submission_status' => ($user_id == 4) ? 'Submitted' : null,
            'obtained_marks' => null,
            'is_overdue' => false
        ],
        [
            'id' => 3,
            'title' => 'English Essay',
            'description' => 'Write an essay on climate change',
            'subject' => 'English',
            'type' => 'Essay',
            'total_marks' => 50,
            'due_date' => date('Y-m-d', strtotime('-2 days')),
            'duration' => null,
            'submission_status' => null,
            'obtained_marks' => null,
            'is_overdue' => true
        ],
        [
            'id' => 4,
            'title' => 'History Assignment',
            'description' => 'World War II causes and effects',
            'subject' => 'History',
            'type' => 'Assignment',
            'total_marks' => 75,
            'due_date' => date('Y-m-d', strtotime('+5 days')),
            'duration' => null,
            'submission_status' => 'Graded',
            'obtained_marks' => 68,
            'is_overdue' => false
        ],
        [
            'id' => 5,
            'title' => 'Physics Lab Report',
            'description' => 'Newton\'s laws experiment report',
            'subject' => 'Physics',
            'type' => 'Lab Report',
            'total_marks' => 50,
            'due_date' => date('Y-m-d', strtotime('+3 days')),
            'duration' => null,
            'submission_status' => 'Submitted',
            'obtained_marks' => null,
            'is_overdue' => false
        ]
    ];
}

// Calculate statistics
$total_assessments = count($assessments);
$submitted_count = 0;
$pending_count = 0;
$graded_count = 0;

foreach ($assessments as $assessment) {
    if (!empty($assessment['submission_status'])) {
        $submitted_count++;
        if (!empty($assessment['obtained_marks'])) {
            $graded_count++;
        }
    } else {
        $pending_count++;
    }
}


if (!$use_demo_data && $conn) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Dashboard</title>
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