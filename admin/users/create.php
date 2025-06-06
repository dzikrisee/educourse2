<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

$error = '';
$success = '';

if ($_POST) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!in_array($role, ['admin', 'user'])) {
        $error = 'Invalid role selected';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Email address is already registered';
        } else {
            // Handle photo upload
            $photoName = 'default.jpg';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['photo'], '../../assets/uploads/users/');
                if ($uploadResult['success']) {
                    $photoName = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['message'];
                }
            }

            if (empty($error)) {
                // Insert new user
                $hashedPassword = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, photo) VALUES (?, ?, ?, ?, ?)");

                if ($stmt->execute([$name, $email, $hashedPassword, $role, $photoName])) {
                    setFlashMessage("User '$name' created successfully!");
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Failed to create user. Please try again.';
                }
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
    <title>Add New User - Admin</title>
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
                <li><a href="../categories/index.php">Categories</a></li>
                <li><a href="index.php" class="active">Users</a></li>
                <li><a href="../enrollments/index.php">Enrollments</a></li>
                <li><a href="../enrollments/report.php">Reports</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="d-flex justify-between align-center mb-2">
                <h1>Add New User</h1>
                <a href="index.php" class="btn">← Back to Users</a>
            </div>

            <div class="form-container">
                <?php if ($error): ?>
                    <div class="flash-message flash-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="userForm">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control"
                                minlength="6" required>
                            <small>Minimum 6 characters</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="user" <?= isset($_POST['role']) && $_POST['role'] === 'user' ? 'selected' : '' ?>>
                                User (Student)
                            </option>
                            <option value="admin" <?= isset($_POST['role']) && $_POST['role'] === 'admin' ? 'selected' : '' ?>>
                                Admin
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="photo">Profile Photo</label>
                        <input type="file" id="photo" name="photo" class="form-control"
                            accept="image/*" data-preview="photo-preview">
                        <img id="photo-preview" style="display:none; width:100px; height:100px; 
                             object-fit:cover; margin-top:10px; border-radius:50%;">
                        <small>Optional. Recommended size: 300x300px</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create User</button>
                        <a href="index.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="../../assets/js/script.js"></script>
</body>

</html>