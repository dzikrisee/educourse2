<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's enrolled courses
$stmt = $pdo->prepare("
    SELECT e.*, c.title, c.description, c.instructor, c.price, c.image,
           cat.name as category_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE e.user_id = ?
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$user_id]);
$enrolled_courses = $stmt->fetchAll();

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);

    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);

        if ($stmt->fetch()) {
            $error = 'Email address is already taken';
        } else {
            // Handle photo upload
            $photoName = $user['photo'];
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['photo'], 'assets/uploads/users/');
                if ($uploadResult['success']) {
                    // Delete old photo if not default
                    if ($user['photo'] !== 'default.jpg') {
                        $oldPhotoPath = 'assets/uploads/users/' . $user['photo'];
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
                // Update user profile
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, photo = ? WHERE id = ?");

                if ($stmt->execute([$name, $email, $photoName, $user_id])) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $success = 'Profile updated successfully!';

                    // Refresh user data
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['photo'] = $photoName;
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        }
    }
}

// Handle password change
if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif (!verifyPassword($current_password, $user['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else {
        // Update password
        $hashedPassword = hashPassword($new_password);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");

        if ($stmt->execute([$hashedPassword, $user_id])) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Failed to change password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EduCourse</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php
    include 'partials/navbar.php'
    ?>

    <div class="container" style="margin-top: 100px;">
        <h1>My Profile</h1>

        <!-- Flash Messages -->
        <?php if ($error): ?>
            <div class="flash-message flash-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash-message flash-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Profile Information -->
            <div class="card">
                <h3>Profile Information</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group text-center">
                        <img src="assets/uploads/users/<?= htmlspecialchars($user['photo']) ?>"
                            alt="Profile Photo" id="current-photo"
                            style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin-bottom: 1rem;"
                            onerror="this.src='assets/uploads/users/profile-default.jpg'">
                    </div>

                    <div class="form-group">
                        <label for="current_password">Old Password</label>
                        <input type="password" id="current_password" name="current_password"
                            class="form-control" minlength="6" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password"
                            class="form-control" minlength="6" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            class="form-control" required>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-primary">
                        Change Password
                    </button>
                </form>
            </div>
        </div>

        <!-- My Courses Section -->
        <div class="card mt-2">
            <h3>My Enrolled Courses (<?= count($enrolled_courses) ?>)</h3>

            <?php if (empty($enrolled_courses)): ?>
                <div class="text-center">
                    <p>You haven't enrolled in any courses yet.</p>
                    <a href="courses.php" class="btn btn-primary">Browse Courses</a>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($enrolled_courses as $enrollment): ?>
                        <div class="card">
                            <img src="assets/uploads/courses/<?= htmlspecialchars($enrollment['image']) ?>"
                                alt="<?= htmlspecialchars($enrollment['title']) ?>"
                                class="card-img"
                                onerror="this.src='assets/uploads/courses/default-course.jpg'">

                            <h4><?= htmlspecialchars($enrollment['title']) ?></h4>
                            <p><?= htmlspecialchars(substr($enrollment['description'], 0, 100)) ?>...</p>

                            <div class="course-info mb-1">
                                <small><strong>Instructor:</strong> <?= htmlspecialchars($enrollment['instructor']) ?></small><br>
                                <small><strong>Category:</strong> <?= htmlspecialchars($enrollment['category_name'] ?? 'Uncategorized') ?></small><br>
                                <small><strong>Enrolled:</strong> <?= formatDate($enrollment['enrollment_date']) ?></small><br>
                                <small><strong>Status:</strong>
                                    <span class="badge badge-<?= $enrollment['status'] ?>">
                                        <?= ucfirst($enrollment['status']) ?>
                                    </span>
                                </small>
                            </div>

                            <div class="course-meta">
                                <span class="price"><?= formatCurrency($enrollment['price']) ?></span>
                            </div>

                            <div class="mt-2">
                                <a href="course-detail.php?id=<?= $enrollment['course_id'] ?>"
                                    class="btn btn-primary btn-sm">View Course</a>

                                <?php if ($enrollment['status'] === 'active'): ?>
                                    <button onclick="updateEnrollmentStatus(<?= $enrollment['id'] ?>, 'completed')"
                                        class="btn btn-sm">Mark Complete</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Account Statistics -->
        <div class="card-grid mt-2">
            <div class="card text-center">
                <h3><?= count($enrolled_courses) ?></h3>
                <p>Total Courses</p>
            </div>
            <div class="card text-center">
                <h3><?= count(array_filter($enrolled_courses, fn($e) => $e['status'] === 'completed')) ?></h3>
                <p>Completed</p>
            </div>
            <div class="card text-center">
                <h3><?= count(array_filter($enrolled_courses, fn($e) => $e['status'] === 'active')) ?></h3>
                <p>In Progress</p>
            </div>
            <div class="card text-center">
                <h3><?= formatCurrency(array_sum(array_column($enrolled_courses, 'price'))) ?></h3>
                <p>Total Invested</p>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function updateEnrollmentStatus(enrollmentId, status) {
            if (confirm('Mark this course as completed?')) {
                fetch('update-enrollment-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `enrollment_id=${enrollmentId}&status=${status}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Failed to update status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred');
                    });
            }
        }
    </script>

    <style>
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: white;
        }

        .badge-active {
            background: #00b894;
        }

        .badge-completed {
            background: #74b9ff;
        }

        .badge-cancelled {
            background: #fd79a8;
        }
    </style>
</body>

</html>