<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id == 0) {
    setFlashMessage('Invalid category ID', 'error');
    header('Location: index.php');
    exit();
}

// Get category data and count courses
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(co.id) as course_count
    FROM categories c
    LEFT JOIN courses co ON c.id = co.category_id
    WHERE c.id = ?
    GROUP BY c.id
");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    setFlashMessage('Category not found', 'error');
    header('Location: index.php');
    exit();
}

if ($_POST && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();

        // Update courses to remove category reference (set to NULL)
        $stmt = $pdo->prepare("UPDATE courses SET category_id = NULL WHERE category_id = ?");
        $stmt->execute([$category_id]);

        // Delete the category
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);

        $pdo->commit();
        setFlashMessage('Category deleted successfully! Associated courses are now uncategorized.');
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollback();
        setFlashMessage('Failed to delete category: ' . $e->getMessage(), 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Category - Admin</title>
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
                <li><a href="../courses/index.php">Courses</a></li>
                <li><a href="index.php" class="active">Categories</a></li>
                <li><a href="../users/index.php">Users</a></li>
                <li><a href="../enrollments/index.php">Enrollments</a></li>
                <li><a href="../enrollments/report.php">Reports</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="d-flex justify-between align-center mb-2">
                <h1>Delete Category</h1>
                <a href="index.php" class="btn">← Back to Categories</a>
            </div>

            <div class="form-container" style="max-width: 600px;">
                <div class="card">
                    <h3 style="color: #e74c3c;">⚠️ Confirm Deletion</h3>

                    <div class="mb-2">
                        <h4><?= htmlspecialchars($category['name']) ?></h4>
                        <p><strong>Description:</strong> <?= htmlspecialchars($category['description'] ?: 'No description') ?></p>
                        <p><strong>Created:</strong> <?= formatDate($category['created_at']) ?></p>
                        <p><strong>Associated Courses:</strong> <?= $category['course_count'] ?></p>
                    </div>

                    <?php if ($category['course_count'] > 0): ?>
                        <div class="flash-message flash-error">
                            <strong>Warning:</strong> This category has <?= $category['course_count'] ?> associated course(s).
                            Deleting this category will remove the category from these courses (they will become uncategorized).
                        </div>
                    <?php endif; ?>

                    <p><strong>Are you sure you want to delete this category?</strong></p>
                    <p>This action cannot be undone.</p>

                    <form method="POST" class="mt-2">
                        <div class="d-flex gap-2">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                Yes, Delete Category
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