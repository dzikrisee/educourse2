<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

// Get dashboard statistics
$stats = [];

// Total courses
$stmt = $pdo->query("SELECT COUNT(*) FROM courses");
$stats['total_courses'] = $stmt->fetchColumn();

// Total users (excluding admin)
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stats['total_users'] = $stmt->fetchColumn();

// Total enrollments
$stmt = $pdo->query("SELECT COUNT(*) FROM enrollments");
$stats['total_enrollments'] = $stmt->fetchColumn();

// Total categories
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$stats['total_categories'] = $stmt->fetchColumn();

// Recent enrollments
$stmt = $pdo->prepare("
    SELECT e.*, u.name as user_name, c.title as course_title
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY e.enrollment_date DESC
    LIMIT 5
");
$stmt->execute();
$recent_enrollments = $stmt->fetchAll();

// Popular courses
$stmt = $pdo->prepare("
    SELECT c.title, COUNT(e.id) as enrollment_count
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id, c.title
    ORDER BY enrollment_count DESC
    LIMIT 5
");
$stmt->execute();
$popular_courses = $stmt->fetchAll();

// Revenue calculation (total from enrollments)
$stmt = $pdo->query("
    SELECT SUM(c.price) as total_revenue
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
");
$stats['total_revenue'] = $stmt->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EduCourse</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">EduCourse Admin</a>
            <ul class="nav-links">
                <li><a href="../index.php">View Site</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="courses/index.php">Courses</a></li>
                <li><a href="categories/index.php">Categories</a></li>
                <li><a href="users/index.php">Users</a></li>
                <li><a href="enrollments/index.php">Enrollments</a></li>
                <li><a href="enrollments/report.php">Reports</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <h1>Dashboard</h1>

            <!-- Flash Messages -->
            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
                <div class="flash-message flash-<?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="card-grid">
                <div class="card text-center">
                    <h3><?= number_format($stats['total_courses']) ?></h3>
                    <p>Total Courses</p>
                    <a href="courses/index.php" class="btn btn-primary btn-sm">Manage</a>
                </div>

                <div class="card text-center">
                    <h3><?= number_format($stats['total_users']) ?></h3>
                    <p>Total Students</p>
                    <a href="users/index.php" class="btn btn-primary btn-sm">Manage</a>
                </div>

                <div class="card text-center">
                    <h3><?= number_format($stats['total_enrollments']) ?></h3>
                    <p>Total Enrollments</p>
                    <a href="enrollments/index.php" class="btn btn-primary btn-sm">View</a>
                </div>

                <div class="card text-center">
                    <h3><?= formatCurrency($stats['total_revenue']) ?></h3>
                    <p>Total Revenue</p>
                    <a href="enrollments/report.php" class="btn btn-primary btn-sm">Report</a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                <!-- Recent Enrollments -->
                <div class="card">
                    <h3>Recent Enrollments</h3>
                    <?php if (empty($recent_enrollments)): ?>
                        <p>No enrollments yet.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($enrollment['user_name']) ?></td>
                                            <td><?= htmlspecialchars($enrollment['course_title']) ?></td>
                                            <td><?= formatDate($enrollment['enrollment_date']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Popular Courses -->
                <div class="card">
                    <h3>Popular Courses</h3>
                    <?php if (empty($popular_courses)): ?>
                        <p>No courses yet.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Course Title</th>
                                        <th>Enrollments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popular_courses as $course): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($course['title']) ?></td>
                                            <td><?= $course['enrollment_count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-2">
                <h3>Quick Actions</h3>
                <div class="d-flex gap-2">
                    <a href="courses/create.php" class="btn">Add New Course</a>
                    <a href="categories/create.php" class="btn">Add Category</a>
                    <a href="users/create.php" class="btn">Add User</a>
                    <a href="enrollments/report.php" class="btn btn-primary">Generate Report</a>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>