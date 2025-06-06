<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($category_id == 0) {
    header('Location: index.php');
    exit();
}

// Get category data
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    setFlashMessage('Category not found', 'error');
    header('Location: index.php');
    exit();
}

if ($_POST) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);

    // Validation
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        // Check if category name already exists (excluding current category)
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $category_id]);

        if ($stmt->fetch()) {
            $error = 'Category name already exists';
        } else {
            // Update category
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");

            if ($stmt->execute([$name, $description, $category_id])) {
                setFlashMessage('Category updated successfully!');
                header('Location: index.php');
                exit();
            } else {
                $error = 'Failed to update category. Please try again.';
            }
        }
    }
} else {
    // Pre-fill form with existing data
    $_POST = $category;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - Admin</title>
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
                <h1>Edit Category</h1>
                <a href="index.php" class="btn">← Back to Categories</a>
            </div>

            <div class="form-container">
                <?php if ($error): ?>
                    <div class="flash-message flash-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="categoryForm">
                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control"
                            rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <small>Brief description of what courses this category contains</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Category</button>
                        <a href="index.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="../../assets/js/script.js"></script>
</body>

</html>