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

$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
$success_message = '';
$error_message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_policy'])) {
    $policy_name = mysqli_real_escape_string($conn, trim($_POST['policy_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $criteria = mysqli_real_escape_string($conn, trim($_POST['criteria']));
    
    $sql = "INSERT INTO policies (policy_name, description, category, criteria, status) 
            VALUES ('$policy_name', '$description', '$category', '$criteria', 'Active')";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Policy created successfully! 🎉";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_policy'])) {
    $policy_id = (int)$_POST['policy_id'];
    $policy_name = mysqli_real_escape_string($conn, trim($_POST['policy_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $criteria = mysqli_real_escape_string($conn, trim($_POST['criteria']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $sql = "UPDATE policies SET policy_name = '$policy_name', description = '$description', 
            category = '$category', criteria = '$criteria', status = '$status' 
            WHERE id = '$policy_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Policy updated successfully! ✨";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// DELETE Policy
if (isset($_GET['delete_policy']) && is_numeric($_GET['delete_policy'])) {
    $policy_id = (int)$_GET['delete_policy'];
    $sql = "DELETE FROM policies WHERE id = '$policy_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Policy deleted successfully! 🗑️";
        header("Location: policy_manager.php");
        exit();
    }
}

// CREATE Notice/Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_notice'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $target_audience = mysqli_real_escape_string($conn, $_POST['target_audience']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $expiry_date = !empty($_POST['expiry_date']) ? mysqli_real_escape_string($conn, $_POST['expiry_date']) : NULL;
    
    $sql = "INSERT INTO notices (title, content, target_audience, priority, expiry_date, status) 
            VALUES ('$title', '$content', '$target_audience', '$priority', " . ($expiry_date ? "'$expiry_date'" : "NULL") . ", 'Active')";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Notice published successfully! 📢";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// UPDATE Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notice'])) {
    $notice_id = (int)$_POST['notice_id'];
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $target_audience = mysqli_real_escape_string($conn, $_POST['target_audience']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $expiry_date = !empty($_POST['expiry_date']) ? mysqli_real_escape_string($conn, $_POST['expiry_date']) : NULL;
    
    $sql = "UPDATE notices SET title = '$title', content = '$content', 
            target_audience = '$target_audience', priority = '$priority', status = '$status',
            expiry_date = " . ($expiry_date ? "'$expiry_date'" : "NULL") . "
            WHERE id = '$notice_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Notice updated successfully! ✨";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// DELETE Notice
if (isset($_GET['delete_notice']) && is_numeric($_GET['delete_notice'])) {
    $notice_id = (int)$_GET['delete_notice'];
    $sql = "DELETE FROM notices WHERE id = '$notice_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Notice deleted successfully! 🗑️";
        header("Location: policy_manager.php");
        exit();
    }
}

// Fetch Policies
$policies_sql = "SELECT * FROM policies ORDER BY created_at DESC";
$policies_result = mysqli_query($conn, $policies_sql);
$policies = array();
if ($policies_result) {
    while ($row = mysqli_fetch_assoc($policies_result)) {
        $policies[] = $row;
    }
}

// Fetch Notices
$notices_sql = "SELECT * FROM notices ORDER BY created_at DESC";
$notices_result = mysqli_query($conn, $notices_sql);
$notices = array();
if ($notices_result) {
    while ($row = mysqli_fetch_assoc($notices_result)) {
        $notices[] = $row;
    }
}

// Statistics
$active_policies = 0;
$inactive_policies = 0;
foreach ($policies as $policy) {
    if ($policy['status'] === 'Active') $active_policies++;
    else $inactive_policies++;
}

$active_notices = 0;
$expired_notices = 0;
foreach ($notices as $notice) {
    if ($notice['status'] === 'Active') $active_notices++;
    else $expired_notices++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy & Notice Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                    <a href="dashboard.php">
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
                    <a href="policy_manager.php" class="active">
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
            <h1 class="page-title">📋 Policy & Notice Manager</h1>
            <p class="page-subtitle">Define evaluation rules and publish announcements</p>
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
                    <div class="card-value"><?php echo count($policies); ?></div>
                    <div class="card-label">Total Policies</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">✅</div>
                    <div class="card-value"><?php echo $active_policies; ?></div>
                    <div class="card-label">Active Policies</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">📢</div>
                    <div class="card-value"><?php echo count($notices); ?></div>
                    <div class="card-label">Total Notices</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">🔔</div>
                    <div class="card-value"><?php echo $active_notices; ?></div>
                    <div class="card-label">Active Notices</div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                <button class="add-goal-btn" onclick="openModal('policyModal')">
                    ➕ Create Policy/Rule
                </button>
                <button class="add-goal-btn" onclick="openModal('noticeModal')" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    📢 Publish Notice
                </button>
            </div>


            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">📋 Performance Evaluation Policies</h2>
                </div>
                <?php if (empty($policies)): ?>
                    <div class="no-goals"><h3>No policies defined yet</h3></div>
                <?php else: ?>
                    <div class="goals-grid">
                        <?php foreach ($policies as $policy): ?>
                            <div class="goal-card">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($policy['category']); ?>
                                    </span>
                                    <span class="badge <?php echo $policy['status'] === 'Active' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $policy['status']; ?>
                                    </span>
                                </div>
                                <h3><?php echo htmlspecialchars($policy['policy_name']); ?></h3>
                                <p><?php echo htmlspecialchars($policy['description']); ?></p>
                                <div style="background: #f8fafc; padding: 1rem; border-radius: 10px; margin: 1rem 0; border-left: 3px solid var(--primary);">
                                    <strong style="color: var(--text-main); font-size: 0.875rem;">Criteria:</strong>
                                    <p style="margin: 0.5rem 0 0 0; color: var(--text-muted); font-size: 0.875rem;">
                                        <?php echo htmlspecialchars($policy['criteria']); ?>
                                    </p>
                                </div>
                                <div class="goal-meta">
                                    <span>Created: <?php echo date('M d, Y', strtotime($policy['created_at'])); ?></span>
                                </div>
                                <div class="goal-actions" style="margin-top: 1rem;">
                                    <button class="btn btn-edit" onclick="editPolicy(<?php echo $policy['id']; ?>, '<?php echo htmlspecialchars($policy['policy_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($policy['description'], ENT_QUOTES); ?>', '<?php echo $policy['category']; ?>', '<?php echo htmlspecialchars($policy['criteria'], ENT_QUOTES); ?>', '<?php echo $policy['status']; ?>')">
                                        ✏️ Edit
                                    </button>
                                    <a href="?delete_policy=<?php echo $policy['id']; ?>" onclick="return confirm('Delete this policy?')" class="btn btn-delete" style="text-decoration: none; text-align: center;">
                                        🗑️ Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

   
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">📢 Notices & Announcements</h2>
                </div>
                <?php if (empty($notices)): ?>
                    <div class="no-goals"><h3>No notices published yet</h3></div>
                <?php else: ?>
                    <div class="goals-grid">
                        <?php foreach ($notices as $notice): ?>
                            <?php
                                $priority_color = '';
                                if ($notice['priority'] === 'High') $priority_color = 'badge-success';
                                elseif ($notice['priority'] === 'Medium') $priority_color = 'badge-warning';
                                else $priority_color = 'badge-info';
                            ?>
                            <div class="goal-card">
                                <div style="display: flex; justify-content: space-between; gap: 0.5rem; margin-bottom: 1rem;">
                                    <span class="badge <?php echo $priority_color; ?>">
                                        <?php echo $notice['priority']; ?> Priority
                                    </span>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($notice['target_audience']); ?>
                                    </span>
                                    <span class="badge <?php echo $notice['status'] === 'Active' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $notice['status']; ?>
                                    </span>
                                </div>
                                <h3><?php echo htmlspecialchars($notice['title']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                                <div class="goal-meta" style="margin-top: 1rem;">
                                    <span>📅 Published: <?php echo date('M d, Y', strtotime($notice['created_at'])); ?></span>
                                    <?php if ($notice['expiry_date']): ?>
                                        <span>⏰ Expires: <?php echo date('M d, Y', strtotime($notice['expiry_date'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="goal-actions" style="margin-top: 1rem;">
                                    <button class="btn btn-edit" onclick="editNotice(<?php echo $notice['id']; ?>, '<?php echo htmlspecialchars($notice['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($notice['content'], ENT_QUOTES); ?>', '<?php echo $notice['target_audience']; ?>', '<?php echo $notice['priority']; ?>', '<?php echo $notice['status']; ?>', '<?php echo $notice['expiry_date']; ?>')">
                                        ✏️ Edit
                                    </button>
                                    <a href="?delete_notice=<?php echo $notice['id']; ?>" onclick="return confirm('Delete this notice?')" class="btn btn-delete" style="text-decoration: none; text-align: center;">
                                        🗑️ Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Policy Modal -->
<div id="policyModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal('policyModal')">&times;</button>
        <h3 id="policyModalTitle" style="margin-bottom: 1.5rem;">Create New Policy</h3>
        <form method="POST" action="">
            <input type="hidden" id="policy_id" name="policy_id">
            <div class="form-group">
                <label for="policy_name">Policy Name *</label>
                <input type="text" id="policy_name" name="policy_name" placeholder="e.g., Grade Risk Threshold" required>
            </div>
            <div class="form-group">
                <label for="policy_description">Description *</label>
                <textarea id="policy_description" name="description" placeholder="Explain what this policy governs..." required></textarea>
            </div>
            <div class="form-group">
                <label for="policy_category">Category *</label>
                <select id="policy_category" name="category" required>
                    <option value="">Select Category</option>
                    <option value="Performance Evaluation">Performance Evaluation</option>
                    <option value="Attendance">Attendance</option>
                    <option value="Risk Assessment">Risk Assessment</option>
                    <option value="Grading">Grading</option>
                    <option value="Academic">Academic</option>
                    <option value="Behavioral">Behavioral</option>
                </select>
            </div>
            <div class="form-group">
                <label for="policy_criteria">Criteria/Rules *</label>
                <textarea id="policy_criteria" name="criteria" placeholder="e.g., Students with average < 50% marked as Red risk" required></textarea>
            </div>
            <div class="form-group" id="policyStatusGroup" style="display: none;">
                <label for="policy_status">Status</label>
                <select id="policy_status" name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" id="policySubmitBtn" name="create_policy" class="add-goal-btn" style="width: 100%;">Create Policy</button>
        </form>
    </div>
</div>

<!-- Notice Modal -->
<div id="noticeModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal('noticeModal')">&times;</button>
        <h3 id="noticeModalTitle" style="margin-bottom: 1.5rem;">Publish New Notice</h3>
        <form method="POST" action="">
            <input type="hidden" id="notice_id" name="notice_id">
            <div class="form-group">
                <label for="notice_title">Title *</label>
                <input type="text" id="notice_title" name="title" placeholder="e.g., Exam Schedule Released" required>
            </div>
            <div class="form-group">
                <label for="notice_content">Content *</label>
                <textarea id="notice_content" name="content" placeholder="Write your announcement..." required style="min-height: 120px;"></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="notice_audience">Target Audience *</label>
                    <select id="notice_audience" name="target_audience" required>
                        <option value="All">All (Students & Teachers)</option>
                        <option value="Students">Students Only</option>
                        <option value="Teachers">Teachers Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notice_priority">Priority *</label>
                    <select id="notice_priority" name="priority" required>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="notice_expiry">Expiry Date (Optional)</label>
                <input type="date" id="notice_expiry" name="expiry_date">
            </div>
            <div class="form-group" id="noticeStatusGroup" style="display: none;">
                <label for="notice_status">Status</label>
                <select id="notice_status" name="status">
                    <option value="Active">Active</option>
                    <option value="Expired">Expired</option>
                </select>
            </div>
            <button type="submit" id="noticeSubmitBtn" name="create_notice" class="add-goal-btn" style="width: 100%;">Publish Notice</button>
        </form>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function editPolicy(id, name, desc, category, criteria, status) {
    document.getElementById('policyModal').style.display = 'block';
    document.getElementById('policyModalTitle').textContent = 'Edit Policy';
    document.getElementById('policy_id').value = id;
    document.getElementById('policy_name').value = name;
    document.getElementById('policy_description').value = desc;
    document.getElementById('policy_category').value = category;
    document.getElementById('policy_criteria').value = criteria;
    document.getElementById('policy_status').value = status;
    document.getElementById('policyStatusGroup').style.display = 'block';
    document.getElementById('policySubmitBtn').name = 'update_policy';
    document.getElementById('policySubmitBtn').textContent = 'Update Policy';
}

function editNotice(id, title, content, audience, priority, status, expiry) {
    document.getElementById('noticeModal').style.display = 'block';
    document.getElementById('noticeModalTitle').textContent = 'Edit Notice';
    document.getElementById('notice_id').value = id;
    document.getElementById('notice_title').value = title;
    document.getElementById('notice_content').value = content;
    document.getElementById('notice_audience').value = audience;
    document.getElementById('notice_priority').value = priority;
    document.getElementById('notice_status').value = status;
    document.getElementById('notice_expiry').value = expiry || '';
    document.getElementById('noticeStatusGroup').style.display = 'block';
    document.getElementById('noticeSubmitBtn').name = 'update_notice';
    document.getElementById('noticeSubmitBtn').textContent = 'Update Notice';
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

</body>
</html>
<?php
mysqli_close($conn);
?>