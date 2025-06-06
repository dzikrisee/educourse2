<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($user_id == 0) {
    header('Location: index.php');
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('User not found', 'error');
    header('Location: index.php');
    exit();
}

if ($_POST) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif (!in_array($role, ['admin', 'user'])) {
        $error = 'Invalid role selected';
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);

        if ($stmt->fetch()) {
            $error = 'Email address is already taken';
        } else {
            // Password validation if changing password
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match';
                }
            }

            if (empty($error)) {
                // Handle photo upload
                $photoName = $user['photo'];
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadFile($_FILES['photo'], '../../assets/uploads/users/');
                    if ($uploadResult['success']) {
                        // Delete old photo if not default
                        if ($user['photo'] !== 'default.jpg') {
                            $oldPhotoPath = '../../assets/uploads/users/' . $user['photo'];
                            if (file_exists($oldPhotoPath)) {
                                unlink($oldPhotoPath);
                            }
                        }
                        $photoName = $uploadResult['filename'];
                    } else {
                        $error = $uploadResult['message'];
                    }
                }

                if (empty($error)) {
                    // Update user
                    if (!empty($new_password)) {
                        // Update with new password
                        $hashedPassword = hashPassword($new_password);
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, photo = ? WHERE id = ?");
                        $result = $stmt->execute([$name, $email, $hashedPassword, $role, $photoName, $user_id]);
                    } else {
                        // Update without changing password
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, photo = ? WHERE id = ?");
                        $result = $stmt->execute([$name, $email, $role, $photoName, $user_id]);
                    }

                    if ($result) {
                        setFlashMessage('User updated successfully!');
                        header('Location: index.php');
                        exit();
                    } else {
                        $error = 'Failed to update user. Please try again.';
                    }
                }
            }
        }
    }
} else {
    // Pre-fill form with existing data
    $_POST = $user;
    $_POST['new_password'] = '';
    $_POST['confirm_password'] = '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
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
                <h1>Edit User</h1>
                <a href="index.php" class="btn">← Back to Users</a>
            </div>

            <div class="form-container">
                <?php if ($error): ?>
                    <div class="flash-message flash-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="userForm">
                    <!-- Current Photo Preview -->
                    <div class="form-group text-center">
                        <p>Current Photo:</p>
                        <img src="../../assets/uploads/users/<?= htmlspecialchars($user['photo']) ?>"
                            alt="Current user photo"
                            style="width:100px; height:100px; object-fit:cover; border-radius:50%;"
                            onerror="this.src='../../assets/uploads/users/default.jpg'">
                    </div>

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

                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="user" <?= $_POST['role'] === 'user' ? 'selected' : '' ?>>
                                User (Student)
                            </option>
                            <option value="admin" <?= $_POST['role'] === 'admin' ? 'selected' : '' ?>>
                                Admin
                            </option>
                        </select>
                        <?php if ($user_id == $_SESSION['user_id']): ?>
                            <small style="color: orange;">⚠️ You are editing your own account</small>
                        <?php endif; ?>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control"
                                minlength="6">
                            <small>Leave empty to keep current password</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="photo">Change Profile Photo</label>
                        <input type="file" id="photo" name="photo" class="form-control"
                            accept="image/*" data-preview="photo-preview">
                        <img id="photo-preview" style="display:none; width:100px; height:100px; 
                             object-fit:cover; margin-top:10px; border-radius:50%;">
                        <small>Leave empty to keep current photo</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="index.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="../../assets/js/script.js"></script>
</body>

</html>
setFlashMessage('User not found', 'error');
header('