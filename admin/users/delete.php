<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id == 0) {
    setFlashMessage('Invalid user ID', 'error');
    header('Location: index.php');
    exit();
}

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    setFlashMessage('You cannot delete your own account', 'error');
    header('Location: index.php');
    exit();
}

// Get user data and enrollment count
$stmt = $pdo->prepare("
    SELECT u.*, COUNT(e.id) as enrollment_count
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.user_id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('User not found', 'error');
    header('Location: index.php');
    exit();
}

if ($_POST && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();

        // Delete all user's enrollments first (due to foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM enrollments WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        // Delete user photo if not default
        if ($user['photo'] !== 'default.jpg') {
            $photoPath = '../../assets/uploads/users/' . $user['photo'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        $pdo->commit();
        setFlashMessage('User deleted successfully!');
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollback();
        setFlashMessage('Failed to delete user: ' . $e->getMessage(), 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - Admin</title>
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
                <h1>Delete User</h1>
                <a href="index.php" class="btn">← Back to Users</a>
            </div>

            <div class="form-container" style="max-width: 600px;">
                <div class="card">
                    <h3 style="color: #e74c3c;">⚠️ Confirm Deletion</h3>

                    <div class="mb-2">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <img src="../../assets/uploads/users/<?= htmlspecialchars($user['photo']) ?>"
                                alt="User photo"
                                style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;"
                                onerror="this.src='../../assets/uploads/users/default.jpg'">

                            <div>
                                <h4><?= htmlspecialchars($user['name']) ?></h4>
                                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                <p><strong>Role:</strong>
                                    <span class="badge badge-<?= $user['role'] ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </p>
                                <p><strong>Joined:</strong> <?= formatDate($user['created_at']) ?></p>
                                <p><strong>Enrollments:</strong> <?= $user['enrollment_count'] ?> courses</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($user['enrollment_count'] > 0): ?>
                        <div class="flash-message flash-error">
                            <strong>Warning:</strong> This user has <?= $user['enrollment_count'] ?> active enrollment(s).
                            Deleting this user will also remove all enrollment records.
                        </div>
                    <?php endif; ?>

                    <p><strong>Are you sure you want to delete this user?</strong></p>
                    <p>This action cannot be undone. All user data and enrollment history will be permanently removed.</p>

                    <form method="POST" class="mt-2">
                        <div class="d-flex gap-2">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                Yes, Delete User
                            </button>
                            <a href="index.php" class="btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/script.js"></script>
    <style>
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: white;
        }

        .badge-admin {
            background: #e74c3c;
        }

        .badge-user {
            background: #74b9ff;
        }
    </style>
</body>

</html>