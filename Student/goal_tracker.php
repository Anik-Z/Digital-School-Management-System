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

// CREATE - Add new goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    
    if (!empty($title) && !empty($description) && !empty($deadline)) {
        $sql = "INSERT INTO goals (student_id, title, description, deadline, progress, status) 
                VALUES ('$student_id', '$title', '$description', '$deadline', 0, 'In Progress')";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Goal created successfully! 🎉";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    } else {
        $error_message = "All fields are required!";
    }
}

// UPDATE - Update goal progress
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_goal'])) {
    $goal_id = (int)$_POST['goal_id'];
    $progress = (int)$_POST['progress'];
    $status = $progress >= 100 ? 'Completed' : 'In Progress';
    
    $sql = "UPDATE goals SET progress = '$progress', status = '$status' 
            WHERE id = '$goal_id' AND student_id = '$student_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Goal updated successfully! ✨";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// UPDATE - Edit goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_goal'])) {
    $goal_id = (int)$_POST['goal_id'];
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    
    if (!empty($title) && !empty($description) && !empty($deadline)) {
        $sql = "UPDATE goals SET title = '$title', description = '$description', deadline = '$deadline' 
                WHERE id = '$goal_id' AND student_id = '$student_id'";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Goal edited successfully! 📝";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}

// DELETE - Delete goal
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $goal_id = (int)$_GET['delete'];
    $sql = "DELETE FROM goals WHERE id = '$goal_id' AND student_id = '$student_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Goal deleted successfully! 🗑️";
        header("Location: goal_tracker.php");
        exit();
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// READ - Fetch all goals
$sql = "SELECT * FROM goals WHERE student_id = '$student_id' ORDER BY deadline ASC";
$result = mysqli_query($conn, $sql);
$goals = array();

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $goals[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goal Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/css/common.css">
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
                    <a href="goal_tracker.php" class="active">
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
            <h1 class="page-title">🎯 Goal Tracker</h1>
            <p class="page-subtitle">Track and manage your academic goals</p>
        </div>

        <div class="container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <button class="add-goal-btn" onclick="toggleForm()">➕ Add New Goal</button>

            <div class="goal-form hidden" id="goalForm">
                <h3>Create New Goal</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Goal Title</label>
                        <input type="text" id="title" name="title" placeholder="Enter goal title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Describe your goal" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="deadline">Deadline</label>
                        <input type="date" id="deadline" name="deadline" required>
                    </div>
                    <button type="submit" name="add_goal" class="add-goal-btn">Create Goal</button>
                </form>
            </div>

            <?php if (empty($goals)): ?>
                <div class="no-goals">
                    <h3>🎯 No Goals Yet!</h3>
                    <p>Start by creating your first goal to track your progress.</p>
                </div>
            <?php else: ?>
                <div class="goals-grid">
                    <?php foreach ($goals as $goal): ?>
                        <div class="goal-card <?php echo $goal['status'] === 'Completed' ? 'completed' : ''; ?>">
                            <h3><?php echo htmlspecialchars($goal['title']); ?></h3>
                            <p><?php echo htmlspecialchars($goal['description']); ?></p>
                            
                            <div class="goal-meta">
                                <span>📅 <?php echo date('M d, Y', strtotime($goal['deadline'])); ?></span>
                                <span class="status-badge <?php echo $goal['status'] === 'Completed' ? 'completed' : 'in-progress'; ?>">
                                    <?php echo $goal['status']; ?>
                                </span>
                            </div>

                            <div class="progress-container">
                                <div class="progress-label">
                                    <span>Progress</span>
                                    <span><?php echo $goal['progress']; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $goal['progress']; ?>%;"></div>
                                </div>
                            </div>

                            <form method="POST" action="" style="margin-top: 15px;">
                                <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                <input type="range" name="progress" class="progress-input" 
                                       min="0" max="100" value="<?php echo $goal['progress']; ?>" 
                                       onchange="this.nextElementSibling.textContent = this.value + '%'">
                                <div style="text-align: center; color: var(--text-muted); font-size: 13px; margin-bottom: 10px;">
                                    <?php echo $goal['progress']; ?>%
                                </div>
                                <button type="submit" name="update_goal" class="add-goal-btn" style="width: 100%; margin: 0;">
                                    Update Progress
                                </button>
                            </form>

                            <div class="goal-actions">
                                <button class="btn btn-edit" onclick="openEditModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars($goal['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($goal['description'], ENT_QUOTES); ?>', '<?php echo $goal['deadline']; ?>')">
                                    ✏️ Edit
                                </button>
                                <a href="?delete=<?php echo $goal['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this goal?')" 
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
        <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;">Edit Goal</h3>
        
        <form method="POST" action="">
            <input type="hidden" id="edit_goal_id" name="goal_id">
            <div class="form-group">
                <label for="edit_title">Goal Title</label>
                <input type="text" id="edit_title" name="title" placeholder="Enter goal title" required>
            </div>
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" placeholder="Describe your goal" required></textarea>
            </div>
            <div class="form-group">
                <label for="edit_deadline">Deadline</label>
                <input type="date" id="edit_deadline" name="deadline" required>
            </div>
            <button type="submit" name="edit_goal" class="add-goal-btn" style="width: 100%;">
                Save Changes
            </button>
        </form>
    </div>
</div>

<script>
    function toggleForm() {
        const form = document.getElementById('goalForm');
        form.classList.toggle('hidden');
    }

    function openEditModal(id, title, description, deadline) {
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_goal_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_deadline').value = deadline;
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

    // Update progress display when slider moves
    document.querySelectorAll('.progress-input').forEach(function(slider) {
        slider.addEventListener('input', function() {
            this.nextElementSibling.textContent = this.value + '%';
        });
    });
</script>

</body>
</html>
<?php
// Close database connection
mysqli_close($conn);
?>