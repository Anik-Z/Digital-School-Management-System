<?php

$student = [
    "name" => "MD Ashikuzzaman",
    "major" => "Computer Science",
    "semester" => "Fall 2025",
    "gpa" => 3.75,
    "gpa_trend" => "+0.15",
    "attendance" => "94%",
    "assignments_done" => 18,
    "assignments_total" => 22,
    "overdue" => 2
];
$courses = [
    ["id" => 1, "title" => "Algorithm Design", "progress" => 85, "grade" => "A", "modules" => 12],
    ["id" => 2, "title" => "Data Structures", "progress" => 70, "grade" => "B+", "modules" => 10],
    ["id" => 3, "title" => "Web Development", "progress" => 95, "grade" => "A+", "modules" => 15]
];


$activities = [
    ["date" => "Nov 15, 2024", "activity" => "Algorithm Design Submission", "course" => "CS 101", "status" => "Graded", "badge" => "success"],
    ["date" => "Nov 14, 2024", "activity" => "Calculus Quiz Attempted", "course" => "Math 202", "status" => "Pending", "badge" => "warning"],
    ["date" => "Nov 12, 2024", "activity" => "Physics Lab Report Uploaded", "course" => "Physics 150", "status" => "Graded", "badge" => "success"]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo $student['name']; ?></title>
    <link rel="stylesheet" href="../Assets/css/common.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>STUDENT!</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($student['name'], 0, 1) . substr(explode(' ', $student['name'])[1], 0, 1); ?>
                    </div>
                    <div class="user-details">
                        <h4><?php echo $student['name']; ?></h4>
                        <p><?php echo $student['major']; ?></p>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="#" class="active"><i>📊</i> Dashboard</a></li>
                    <li><a href="performance.php"><i>📈</i> Performance</a></li>
                    <li><a href="goal_tracker.php"><i>🎯</i> Goals</a></li>
                    <li><a href="assessment.php"><i>📝</i>Assessment</a></li>
                    <li><a href="notices.php"><i>🔔</i>Notice</a></li>
                    <li><a href="login.php"><i>🚪</i> Logout</a></li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <p>Term: <?php echo $student['semester']; ?><br>GPA: <?php echo $student['gpa']; ?></p>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1 class="page-title">Welcome back, <?php echo explode(' ', $student['name'])[0]; ?></h1>
            </header>

            <div class="content-body">
                <section class="overview-cards">
                    <div class="overview-card">
                        <div class="card-value"><?php echo $student['gpa']; ?></div>
                        <div class="card-label">Current GPA</div>
                        <div class="card-trend trend-up"><?php echo $student['gpa_trend']; ?></div>
                    </div>
                    <div class="overview-card">
                        <div class="card-value"><?php echo $student['attendance']; ?></div>
                        <div class="card-label">Attendance</div>
                    </div>
                    <div class="overview-card">
                        <div class="card-value"><?php echo $student['assignments_done'] . "/" . $student['assignments_total']; ?></div>
                        <div class="card-label">Assignments</div>
                    </div>
                </section>

                <section class="progress-summary">
                    <h2 class="section-title">Course Progress</h2>
                    <div class="progress-grid">
                        <?php foreach ($courses as $course): ?>
                        <div class="progress-item">
                            <div class="progress-header">
                                <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                <span><?php echo $course['progress']; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $course['progress']; ?>%;"></div>
                            </div>
                            <div style="display:flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                <small>Grade: <?php echo $course['grade']; ?> | <?php echo $course['modules']; ?> modules</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="card">
                    <h3>Recent Activity</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $act): ?>
                                <tr>
                                    <td><?php echo $act['date']; ?></td>
                                    <td><?php echo $act['activity']; ?></td>
                                    <td><span class="badge badge-<?php echo $act['badge']; ?>"><?php echo $act['status']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>