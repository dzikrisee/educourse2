<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

$error = '';
$success = '';

// Get categories for dropdown
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categoriesStmt->fetchAll();

if ($_POST) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $instructor = sanitize($_POST['instructor']);
    $price = (float)$_POST['price'];
    $duration = sanitize($_POST['duration']);
    $category_id = (int)$_POST['category_id'];
    
    // Validation
    if (empty($title) || empty($description) || empty($instructor) || $price <= 0) {
        $error = 'Please fill in all required fields with valid data';
    } else {
        // Handle image upload
        $imageName = 'default-course.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['image'], '../../assets/uploads/courses/');
            if ($uploadResult['success']) {
                $imageName = $uploadResult['filename'];
            } else {
                $error = $uploadResult['message'];
            }
        }
        
        if (empty($error)) {
            // Insert course
            $stmt = $pdo->prepare("
                INSERT INTO courses (title, description, instructor, price, duration, image, category_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$title, $description, $instructor, $price, $duration, $imageName, $category_id ?: null])) {
                setFlashMessage('Course created successfully!');
                header('Location: index.php');
                exit();
            } else {
                $error = 'Failed to create course. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Course - Admin</title>
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
                <h1>Add New Course</h1>
                <a href="index.php" class="btn">← Back to Courses</a>
            </div>

            <div class="form-container" style="max-width: 800px;">
                <?php if ($error): ?>
                    <div class="flash-message flash-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="courseForm">
                    <div class="form-group">
                        <label for="title">Course Title *</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="instructor">Instructor Name *</label>
                        <input type="text" id="instructor" name="instructor" class="form-control" 
                               value="<?= htmlspecialchars($_POST['instructor'] ?? '') ?>" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="price">Price (Rp) *</label>
                            <input type="number" id="price" name="price" class="form-control" 
                                   min="0" step="1000" value="<?= $_POST['price'] ?? '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="duration">Duration</label>
                            <input type="text" id="duration" name="duration" class="form-control" 
                                   placeholder="e.g., 8 weeks" value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($categories)): ?>
                            <small><a href="../categories/create.php">Create categories first</a></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="image">Course Image</label>
                        <input type="file" id="image" name="image" class="form-control" 
                               accept="image/*" data-preview="image-preview">
                        <img id="image-preview" style="display:none; width:200px; height:150px; 
                             object-fit:cover; margin-top:10px; border-radius:8px;">
                        <small>Recommended size: 800x600px</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create Course</button>
                        <a href="index.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="../../assets/js/script.js"></script>
</body>
</html>