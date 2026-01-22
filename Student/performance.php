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


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'] ?? 'Student';

$success_message = '';
$error_message = '';


$tableExists = mysqli_query($conn, "SHOW TABLES LIKE 'performance'");


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_performance'])) {
    if ($tableExists && mysqli_num_rows($tableExists) > 0) {
        $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
        $assignment_name = mysqli_real_escape_string($conn, trim($_POST['assignment_name']));
        $score = (int)$_POST['score'];
        $max_score = (int)$_POST['max_score'];
        $date = mysqli_real_escape_string($conn, $_POST['date']);
        
        if (!empty($subject) && !empty($assignment_name) && $score > 0 && $max_score > 0) {
            $percentage = round(($score / $max_score) * 100, 2);
            
            $sql = "INSERT INTO performance (student_id, subject, assignment_name, score, max_score, percentage, date) 
                    VALUES ('$student_id', '$subject', '$assignment_name', '$score', '$max_score', '$percentage', '$date')";
            
            if (mysqli_query($conn, $sql)) {
                $success_message = "Performance record added successfully! 🎉";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        } else {
            $error_message = "All fields are required with valid values!";
        }
    } else {
        $error_message = "Performance tracking is not available yet. Database setup required.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_performance'])) {
    if ($tableExists && mysqli_num_rows($tableExists) > 0) {
        $performance_id = (int)$_POST['performance_id'];
        $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
        $assignment_name = mysqli_real_escape_string($conn, trim($_POST['assignment_name']));
        $score = (int)$_POST['score'];
        $max_score = (int)$_POST['max_score'];
        $date = mysqli_real_escape_string($conn, $_POST['date']);
        
        if (!empty($subject) && !empty($assignment_name) && $score > 0 && $max_score > 0) {
            $percentage = round(($score / $max_score) * 100, 2);
            
            $sql = "UPDATE performance SET 
                    subject = '$subject', 
                    assignment_name = '$assignment_name', 
                    score = '$score', 
                    max_score = '$max_score', 
                    percentage = '$percentage', 
                    date = '$date' 
                    WHERE id = '$performance_id' AND student_id = '$student_id'";
            
            if (mysqli_query($conn, $sql)) {
                $success_message = "Performance record updated successfully! ✨";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        }
    }
}


if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if ($tableExists && mysqli_num_rows($tableExists) > 0) {
        $performance_id = (int)$_GET['delete'];
        $sql = "DELETE FROM performance WHERE id = '$performance_id' AND student_id = '$student_id'";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Performance record deleted successfully! 🗑️";
            header("Location: performance.php");
            exit();
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}


$performances = [];
$average_score = 0;
$highest_score = 0;
$total_records = 0;
$subject_stats = [];
$chart_data = [];

if ($tableExists && mysqli_num_rows($tableExists) > 0) {
    $sql = "SELECT * FROM performance WHERE student_id = '$student_id' ORDER BY date DESC";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $performances[] = $row;
        }
    }
    

    $total_records = count($performances);
    
    if ($total_records > 0) {
        $total_percentage = 0;
        foreach ($performances as $perf) {
            $total_percentage += $perf['percentage'];
            if ($perf['percentage'] > $highest_score) {
                $highest_score = $perf['percentage'];
            }
            
            
            if (!isset($subject_stats[$perf['subject']])) {
                $subject_stats[$perf['subject']] = ['total' => 0, 'count' => 0];
            }
            $subject_stats[$perf['subject']]['total'] += $perf['percentage'];
            $subject_stats[$perf['subject']]['count']++;
        }
        $average_score = round($total_percentage / $total_records, 2);
        
        // Prepare chart data (last 6 records)
        $chart_data = array_slice($performances, 0, 6);
        $chart_data = array_reverse($chart_data);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Dashboard</title>
    <link rel="stylesheet" href="../Assets/css/common.css">
</head>
<body>

<div class="dashboard-layout">
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
                    <a href="performance.php" class="active">
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
                    <a href="assessment.php">
                        <span class="nav-icon">📝</span>
                        <span>Assessments</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="../auth/login.php" class="logout-btn">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

 
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">📈 Performance Dashboard</h1>
            <p class="page-subtitle">Track and manage your academic performance</p>
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
                    <div class="card-icon">📚</div>
                    <div class="card-value"><?php echo $average_score; ?>%</div>
                    <div class="card-label">Average Score</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">🏆</div>
                    <div class="card-value"><?php echo $highest_score; ?>%</div>
                    <div class="card-label">Highest Score</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">📝</div>
                    <div class="card-value"><?php echo $total_records; ?></div>
                    <div class="card-label">Total Records</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon">📖</div>
                    <div class="card-value"><?php echo count($subject_stats); ?></div>
                    <div class="card-label">Subjects</div>
                </div>
            </div>

            
            <button class="add-goal-btn" onclick="toggleForm()" style="margin-bottom: 2rem;">➕ Add Performance Record</button>

         
            <div class="goal-form hidden" id="performanceForm">
                <h3>Add Performance Record</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" placeholder="e.g., Mathematics" required>
                    </div>
                    <div class="form-group">
                        <label for="assignment_name">Assignment/Test Name</label>
                        <input type="text" id="assignment_name" name="assignment_name" placeholder="e.g., Mid-term Exam" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="score">Score Obtained</label>
                            <input type="number" id="score" name="score" placeholder="e.g., 85" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="max_score">Maximum Score</label>
                            <input type="number" id="max_score" name="max_score" placeholder="e.g., 100" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <button type="submit" name="add_performance" class="add-goal-btn">Add Record</button>
                </form>
            </div>

            <?php if (!empty($chart_data)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Performance Trend</h2>
                </div>
                <canvas id="performanceChart" style="max-height: 350px;"></canvas>
            </div>
            <?php endif; ?>

    
            <?php if (!empty($subject_stats)): ?>
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Subject-wise Performance</h2>
                </div>
                <div class="goals-grid">
                    <?php foreach ($subject_stats as $subject => $stats): ?>
                        <?php 
                            $avg = round($stats['total'] / $stats['count'], 2);
                        ?>
                        <div class="overview-card">
                            <h3 style="font-size: 1.125rem; margin-bottom: 1rem; color: var(--text-main);">
                                <?php echo htmlspecialchars($subject); ?>
                            </h3>
                            <div class="card-value"><?php echo $avg; ?>%</div>
                            <div class="card-label"><?php echo $stats['count']; ?> Assignments</div>
                            <div class="progress-bar" style="margin-top: 1rem;">
                                <div class="progress-fill" style="width: <?php echo $avg; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Performance Records</h2>
                </div>
                
                <?php if (empty($performances)): ?>
                    <div class="no-goals">
                        <h3>📊 No Performance Records Yet!</h3>
                        <p>Start by adding your first performance record.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Assignment</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($performances as $perf): ?>
                                <?php
                                    $grade = '';
                                    $badge_class = '';
                                    if ($perf['percentage'] >= 90) {
                                        $grade = 'A+';
                                        $badge_class = 'badge-success';
                                    } elseif ($perf['percentage'] >= 80) {
                                        $grade = 'A';
                                        $badge_class = 'badge-success';
                                    } elseif ($perf['percentage'] >= 70) {
                                        $grade = 'B';
                                        $badge_class = 'badge-info';
                                    } elseif ($perf['percentage'] >= 60) {
                                        $grade = 'C';
                                        $badge_class = 'badge-warning';
                                    } else {
                                        $grade = 'D';
                                        $badge_class = 'badge-warning';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($perf['date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($perf['subject']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($perf['assignment_name']); ?></td>
                                    <td><?php echo $perf['score']; ?> / <?php echo $perf['max_score']; ?></td>
                                    <td><strong><?php echo $perf['percentage']; ?>%</strong></td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $grade; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-edit" style="padding: 0.5rem 1rem; font-size: 0.8rem; margin-right: 0.5rem;" 
                                                onclick="openEditModal(<?php echo $perf['id']; ?>, '<?php echo htmlspecialchars($perf['subject'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($perf['assignment_name'], ENT_QUOTES); ?>', <?php echo $perf['score']; ?>, <?php echo $perf['max_score']; ?>, '<?php echo $perf['date']; ?>')">
                                            ✏️
                                        </button>
                                        <a href="?delete=<?php echo $perf['id']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this record?')" 
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
    </main>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeEditModal()">&times;</button>
        <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;">Edit Performance Record</h3>
        
        <form method="POST" action="">
            <input type="hidden" id="edit_performance_id" name="performance_id">
            <div class="form-group">
                <label for="edit_subject">Subject</label>
                <input type="text" id="edit_subject" name="subject" required>
            </div>
            <div class="form-group">
                <label for="edit_assignment_name">Assignment/Test Name</label>
                <input type="text" id="edit_assignment_name" name="assignment_name" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit_score">Score Obtained</label>
                    <input type="number" id="edit_score" name="score" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_max_score">Maximum Score</label>
                    <input type="number" id="edit_max_score" name="max_score" min="1" required>
                </div>
            </div>
            <div class="form-group">
                <label for="edit_date">Date</label>
                <input type="date" id="edit_date" name="date" required>
            </div>
            <button type="submit" name="edit_performance" class="add-goal-btn" style="width: 100%;">
                Save Changes
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function toggleForm() {
        const form = document.getElementById('performanceForm');
        form.classList.toggle('hidden');
    }

    function openEditModal(id, subject, assignment, score, maxScore, date) {
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_performance_id').value = id;
        document.getElementById('edit_subject').value = subject;
        document.getElementById('edit_assignment_name').value = assignment;
        document.getElementById('edit_score').value = score;
        document.getElementById('edit_max_score').value = maxScore;
        document.getElementById('edit_date').value = date;
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }

    <?php if (!empty($chart_data)): ?>
  
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.2)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.02)');

    const performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                <?php foreach ($chart_data as $data): ?>
                    '<?php echo htmlspecialchars($data['assignment_name']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Score %',
                data: [
                    <?php foreach ($chart_data as $data): ?>
                        <?php echo $data['percentage']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: gradient,
                borderColor: '#6366f1',
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointHoverBackgroundColor: '#6366f1',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(30, 41, 59, 0.95)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(148, 163, 184, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        },
                        color: '#64748b',
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
</script>

</body>
</html>
<?php
mysqli_close($conn);
?>