<?php
session_start();


$conn = mysqli_connect('localhost', 'root', '', 'digital_school_management_system');
if (!$conn) {
    die("Database connection failed. Please check your database credentials.");
}


if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Admin User';
}


if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('Access denied. Admins only.'); window.location.href='../auth/login.php';</script>";
    exit();
}

$admin_name = $_SESSION['full_name'];


$success_message = '';
$error_message = '';


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
    
    // Add demo data
    mysqli_query($conn, "INSERT INTO users (full_name, email, password, role) VALUES ('Admin User', 'admin@school.com', 'demo123', 'admin')");
    mysqli_query($conn, "INSERT INTO users (full_name, email, password, role, subject) VALUES ('John Smith', 'john.smith@school.com', 'demo123', 'teacher', 'Mathematics')");
    mysqli_query($conn, "INSERT INTO users (full_name, email, password, role, class) VALUES ('Alice Brown', 'alice@school.com', 'demo123', 'student', '10A')");
    mysqli_query($conn, "INSERT INTO users (full_name, email, password, role, class) VALUES ('Bob Wilson', 'bob@school.com', 'demo123', 'student', '10A')");
}

$check_classes = mysqli_query($conn, "SHOW TABLES LIKE 'classes'");
if (!$check_classes || mysqli_num_rows($check_classes) == 0) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        teacher_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    

    mysqli_query($conn, "INSERT INTO classes (name, teacher_id) VALUES ('Grade 10A', 2)");
    mysqli_query($conn, "INSERT INTO classes (name, teacher_id) VALUES ('Grade 10B', NULL)");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_student'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : password_hash('student123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (full_name, email, password, role, class) 
                VALUES ('$name', '$email', '$password', 'student', '$class_id')";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Student added successfully!";
        } else {
            $error_message = "Error adding student: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['update_student'])) {
        $student_id = intval($_POST['student_id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
        
        $sql = "UPDATE users SET 
                full_name = '$name',
                email = '$email',
                class = '$class_id'
                WHERE id = $student_id AND role = 'student'";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Student updated successfully!";
        } else {
            $error_message = "Error updating student: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['create_teacher'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $subject = mysqli_real_escape_string($conn, $_POST['subject']);
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : password_hash('teacher123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (full_name, email, password, role, subject) 
                VALUES ('$name', '$email', '$password', 'teacher', '$subject')";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Teacher added successfully!";
        } else {
            $error_message = "Error adding teacher: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['update_teacher'])) {
        $teacher_id = intval($_POST['teacher_id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $subject = mysqli_real_escape_string($conn, $_POST['subject']);
        
        $sql = "UPDATE users SET 
                full_name = '$name',
                email = '$email',
                subject = '$subject'
                WHERE id = $teacher_id AND role = 'teacher'";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Teacher updated successfully!";
        } else {
            $error_message = "Error updating teacher: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['create_class'])) {
        $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
        $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : NULL;
        
        $sql = "INSERT INTO classes (name, teacher_id) 
                VALUES ('$class_name', " . ($teacher_id ? "'$teacher_id'" : "NULL") . ")";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Class created successfully!";
        } else {
            $error_message = "Error creating class: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['assign_teacher'])) {
        $class_id = intval($_POST['class_id']);
        $teacher_id = intval($_POST['teacher_id']);
        
        $sql = "UPDATE classes SET teacher_id = '$teacher_id' WHERE id = $class_id";
        
        if (mysqli_query($conn, $sql)) {
            $success_message = "Teacher assigned to class successfully!";
        } else {
            $error_message = "Error assigning teacher: " . mysqli_error($conn);
        }
    }
}


if (isset($_GET['delete_student'])) {
    $student_id = intval($_GET['delete_student']);
    $delete_sql = "DELETE FROM users WHERE id = $student_id AND role = 'student'";
    if (mysqli_query($conn, $delete_sql)) {
        $success_message = "Student deleted successfully!";
    } else {
        $error_message = "Error deleting student: " . mysqli_error($conn);
    }
}

if (isset($_GET['delete_teacher'])) {
    $teacher_id = intval($_GET['delete_teacher']);
    $delete_sql = "DELETE FROM users WHERE id = $teacher_id AND role = 'teacher'";
    if (mysqli_query($conn, $delete_sql)) {
        $success_message = "Teacher deleted successfully!";
    } else {
        $error_message = "Error deleting teacher: " . mysqli_error($conn);
    }
}

if (isset($_GET['delete_class'])) {
    $class_id = intval($_GET['delete_class']);
    $delete_sql = "DELETE FROM classes WHERE id = $class_id";
    if (mysqli_query($conn, $delete_sql)) {
        $success_message = "Class deleted successfully!";
    } else {
        $error_message = "Error deleting class: " . mysqli_error($conn);
    }
}

// Get students with class names
$students = [];
$student_query = "SELECT u.id, u.full_name as name, u.email, u.class, u.risk_status, 
                         c.name as class_name 
                  FROM users u
                  LEFT JOIN classes c ON u.class = c.id
                  WHERE u.role = 'student' 
                  ORDER BY u.full_name ASC";
$student_result = mysqli_query($conn, $student_query);
if ($student_result && mysqli_num_rows($student_result) > 0) {
    while ($row = mysqli_fetch_assoc($student_result)) {
        $students[] = $row;
    }
}

// Get teachers
$teachers = [];
$teacher_query = "SELECT id, full_name as name, email, subject 
                  FROM users 
                  WHERE role = 'teacher' 
                  ORDER BY full_name ASC";
$teacher_result = mysqli_query($conn, $teacher_query);
if ($teacher_result && mysqli_num_rows($teacher_result) > 0) {
    while ($row = mysqli_fetch_assoc($teacher_result)) {
        $teachers[] = $row;
    }
}

// Get classes with teacher names and student counts
$classes = [];
$class_query = "SELECT c.*, 
                       u.full_name as teacher_name,
                       (SELECT COUNT(*) FROM users WHERE class = c.id AND role = 'student') as student_count
                FROM classes c
                LEFT JOIN users u ON c.teacher_id = u.id
                ORDER BY c.name ASC";
$class_result = mysqli_query($conn, $class_query);
if ($class_result && mysqli_num_rows($class_result) > 0) {
    while ($row = mysqli_fetch_assoc($class_result)) {
        $classes[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User & Class Management</title>
    <link rel="stylesheet" href="../Assets/css/common.css">
</head>
<body>

<div class="dashboard-layout">
    <!-- Sidebar -->
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
                    <a href="user_class_manage.php" class="active">
                        <span class="nav-icon">👥</span>
                        <span>Users & Classes</span>
                    </a>
                </li>
                <li>
                    <a href="policy_manager.php">
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">👥 User & Class Management</h1>
            <p class="page-subtitle">Manage students, teachers, and class assignments</p>
        </div>

        <div class="container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="overview-cards">
                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">👨‍🎓</div>
                    <div class="card-value"><?php echo count($students); ?></div>
                    <div class="card-label">Total Students</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">👨‍🏫</div>
                    <div class="card-value"><?php echo count($teachers); ?></div>
                    <div class="card-label">Total Teachers</div>
                </div>

                <div class="overview-card">
                    <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">🏫</div>
                    <div class="card-value"><?php echo count($classes); ?></div>
                    <div class="card-label">Total Classes</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                <button class="add-goal-btn" onclick="openModal('studentModal')">
                    ➕ Add Student
                </button>
                <button class="add-goal-btn" onclick="openModal('teacherModal')" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    ➕ Add Teacher
                </button>
                <button class="add-goal-btn" onclick="openModal('classModal')" style="background: linear-gradient(135deg, #10b981, #059669);">
                    ➕ Create Class
                </button>
                <button class="add-goal-btn" onclick="openModal('assignModal')" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    🔗 Assign Teacher to Class
                </button>
            </div>

            <!-- Students Table -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">👨‍🎓 Students</h2>
                </div>
                <?php if (empty($students)): ?>
                    <div class="no-goals"><h3>No students added yet</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Risk Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo $student['class_name'] ? htmlspecialchars($student['class_name']) : 'Not Assigned'; ?></td>
                                    <td>
                                        <span class="badge" style="background: <?php echo $student['risk_status'] === 'Green' ? '#dcfce7' : ($student['risk_status'] === 'Yellow' ? '#fef9c3' : '#fee2e2'); ?>; color: <?php echo $student['risk_status'] === 'Green' ? '#166534' : ($student['risk_status'] === 'Yellow' ? '#854d0e' : '#991b1b'); ?>;">
                                            <?php echo $student['risk_status'] === 'Green' ? '🟢' : ($student['risk_status'] === 'Yellow' ? '🟡' : '🔴'); ?>
                                            <?php echo $student['risk_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-edit" style="padding: 0.5rem 1rem; font-size: 0.8rem; margin-right: 0.25rem;" onclick="editStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['email'], ENT_QUOTES); ?>', <?php echo $student['class_id']; ?>)">
                                            ✏️
                                        </button>
                                        <a href="?delete_student=<?php echo $student['id']; ?>" onclick="return confirm('Delete this student?')" class="btn btn-delete" style="padding: 0.5rem 1rem; font-size: 0.8rem; text-decoration: none; display: inline-block;">
                                            🗑️
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Teachers Table -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">👨‍🏫 Teachers</h2>
                </div>
                <?php if (empty($teachers)): ?>
                    <div class="no-goals"><h3>No teachers added yet</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($teacher['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['subject']); ?></td>
                                    <td>
                                        <button class="btn btn-edit" style="padding: 0.5rem 1rem; font-size: 0.8rem; margin-right: 0.25rem;" onclick="editTeacher(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($teacher['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($teacher['subject'], ENT_QUOTES); ?>')">
                                            ✏️
                                        </button>
                                        <a href="?delete_teacher=<?php echo $teacher['id']; ?>" onclick="return confirm('Delete this teacher?')" class="btn btn-delete" style="padding: 0.5rem 1rem; font-size: 0.8rem; text-decoration: none; display: inline-block;">
                                            🗑️
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Classes Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">🏫 Classes</h2>
                </div>
                <?php if (empty($classes)): ?>
                    <div class="no-goals"><h3>No classes created yet</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Assigned Teacher</th>
                                <th>Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($class['name']); ?></strong></td>
                                    <td><?php echo $class['teacher_name'] ? htmlspecialchars($class['teacher_name']) : 'Not Assigned'; ?></td>
                                    <td><span class="badge badge-info"><?php echo $class['student_count']; ?> Students</span></td>
                                    <td>
                                        <a href="?delete_class=<?php echo $class['id']; ?>" onclick="return confirm('Delete this class?')" class="btn btn-delete" style="padding: 0.5rem 1rem; font-size: 0.8rem; text-decoration: none; display: inline-block;">
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

<!-- Student Modal -->
<div id="studentModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal('studentModal')">&times;</button>
        <h3 id="studentModalTitle" style="margin-bottom: 1.5rem;">Add New Student</h3>
        <form method="POST" action="">
            <input type="hidden" id="student_id" name="student_id">
            <div class="form-group">
                <label for="student_name">Name *</label>
                <input type="text" id="student_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="student_email">Email *</label>
                <input type="email" id="student_email" name="email" required>
            </div>
            <div class="form-group">
                <label for="student_class">Class *</label>
                <select id="student_class" name="class_id" required>
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="studentPasswordGroup">
                <label for="student_password">Password *</label>
                <input type="password" id="student_password" name="password">
            </div>
            <button type="submit" id="studentSubmitBtn" name="create_student" class="add-goal-btn" style="width: 100%;">Add Student</button>
        </form>
    </div>
</div>

<!-- Teacher Modal -->
<div id="teacherModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal('teacherModal')">&times;</button>
        <h3 id="teacherModalTitle" style="margin-bottom: 1.5rem;">Add New Teacher</h3>
        <form method="POST" action="">
            <input type="hidden" id="teacher_id" name="teacher_id">
            <div class="form-group">
                <label for="teacher_name">Name *</label>
                <input type="text" id="teacher_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="teacher_email">Email *</label>
                <input type="email" id="teacher_email" name="email" required>
            </div>
            <div class="form-group">
                <label for="teacher_subject">Subject *</label>
                <input type="text" id="teacher_subject" name="subject" required>
            </div>
            <div class="form-group" id="teacherPasswordGroup">
                <label for="teacher_password">Password *</label>
                <input type="password" id="teacher_password" name="password">
            </div>
            <button type="submit" id="teacherSubmitBtn" name="create_teacher" class="add-goal-btn" style="width: 100%;">Add Teacher</button>
        </form>
    </div>
</div>

<!-- Class Modal -->
<div id="classModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal('classModal')">&times;</button>
        <h3 style="margin-bottom: 1.5rem;">Create New Class</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="class_name">Class Name *</label>
                <input type="text" id="class_name" name="class_name" placeholder="e.g., Grade 10A" required>
            </div>
            <div class="form-group">
                <label for="class_teacher">Assign Teacher (Optional)</label>
                <select id="class_teacher" name="teacher_id">
                    <option value="">Select Teacher</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="create_class" class="add-goal-btn" style="width: 100%;">Create Class</button>
        </form>
    </div>
</div>

<!-- Assign Teacher Modal -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal('assignModal')">&times;</button>
        <h3 style="margin-bottom: 1.5rem;">Assign Teacher to Class</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="assign_class">Select Class *</label>
                <select id="assign_class" name="class_id" required>
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="assign_teacher">Select Teacher *</label>
                <select id="assign_teacher" name="teacher_id" required>
                    <option value="">Select Teacher</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['subject']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="assign_teacher" class="add-goal-btn" style="width: 100%;">Assign Teacher</button>
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

function editStudent(id, name, email, classId) {
    document.getElementById('studentModal').style.display = 'block';
    document.getElementById('studentModalTitle').textContent = 'Edit Student';
    document.getElementById('student_id').value = id;
    document.getElementById('student_name').value = name;
    document.getElementById('student_email').value = email;
    document.getElementById('student_class').value = classId;
    document.getElementById('studentPasswordGroup').style.display = 'none';
    document.getElementById('studentSubmitBtn').name = 'update_student';
    document.getElementById('studentSubmitBtn').textContent = 'Update Student';
}

function editTeacher(id, name, email, subject) {
    document.getElementById('teacherModal').style.display = 'block';
    document.getElementById('teacherModalTitle').textContent = 'Edit Teacher';
    document.getElementById('teacher_id').value = id;
    document.getElementById('teacher_name').value = name;
    document.getElementById('teacher_email').value = email;
    document.getElementById('teacher_subject').value = subject;
    document.getElementById('teacherPasswordGroup').style.display = 'none';
    document.getElementById('teacherSubmitBtn').name = 'update_teacher';
    document.getElementById('teacherSubmitBtn').textContent = 'Update Teacher';
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