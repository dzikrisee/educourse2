<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id == 0) {
    setFlashMessage('Invalid course ID', 'error');
    header('Location: index.php');
    exit();
}

// Get course data to check if it exists and get image name
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    setFlashMessage('Course not found', 'error');
    header('Location: index.php');
    exit();
}

// Check if course has enrollments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
$stmt->execute([$course_id]);
$enrollment_count = $stmt->fetchColumn();

if ($_POST && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();

        // Delete all enrollments first (due to foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM enrollments WHERE course_id = ?");
        $stmt->execute([$course_id]);

        // Delete the course
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);

        // Delete course image if not default
        if ($course['image'] !== 'default-course.jpg') {
            $imagePath = '../../assets/uploads/courses/' . $course['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $pdo->commit();
        setFlashMessage('Course deleted successfully!');
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollback();
        setFlashMessage('Failed to delete course: ' . $e->getMessage(), 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Course - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../../index.php" class="logo">EduCourse Admin</a>
            <ul class="nav-links">
                <li><a href="../../index.php">View Site</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="index.php" class="active">Courses</a></li>
                <li><a href="../categories/index.php">Categories</a></li>
                <li><a href="../users/index.php">Users</a></li>
                <li><a href="../enrollments/index.php">Enrollments</a></li>
                <li><a href="../enrollments/report.php">Reports</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="d-flex justify-between align-center mb-2">
                <h1>Delete Course</h1>
                <a href="index.php" class="btn">← Back to Courses</a>
            </div>

            <div class="form-container" style="max-width: 600px;">
                <div class="card">
                    <h3 style="color: #e74c3c;">⚠️ Confirm Deletion</h3>

                    <div class="mb-2">
                        <img src="../../assets/uploads/courses/<?= htmlspecialchars($course['image']) ?>"
                            alt="Course image"
                            style="width: 150px; height: 100px; object-fit: cover; border-radius: 8px; float: left; margin-right: 1rem;"
                            onerror="this.src='../../assets/uploads/courses/default-course.jpg'">

                        <div>
                            <h4><?= htmlspecialchars($course['title']) ?></h4>
                            <p><strong>Instructor:</strong> <?= htmlspecialchars($course['instructor']) ?></p>
                            <p><strong>Price:</strong> <?= formatCurrency($course['price']) ?></p>
                            <p><strong>Created:</strong> <?= formatDate($course['created_at']) ?></p>
                        </div>
                        <div style="clear: both;"></div>
                    </div>

                    <?php if ($enrollment_count > 0): ?>
                        <div class="flash-message flash-error">
                            <strong>Warning:</strong> This course has <?= $enrollment_count ?> active enrollment(s).
                            Deleting this course will also remove all enrollment records.
                        </div>
                    <?php endif; ?>

                    <p><strong>Are you sure you want to delete this course?</strong></p>
                    <p>This action cannot be undone. All related data will be permanently removed.</p>

                    <form method="POST" class="mt-2">
                        <div class="d-flex gap-2">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                Yes, Delete Course
                            </button>
                            <a href="index.php" class="btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/script.js"></script>
</body>

</html>