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
    <link rel="stylesheet" href="assets/css/profile.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'partials/navbar.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">Manage your account settings and track your learning progress</p>
        </div>
    </section>

    <div class="container">
        <!-- Flash Messages -->
        <?php if ($error): ?>
            <div class="flash-message flash-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash-message flash-success fade-in">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Layout -->
        <div class="profile-layout">
            <!-- Profile Info Card -->
            <div class="profile-main fade-in">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <img src="assets/uploads/users/<?= htmlspecialchars($user['photo']) ?>"
                                alt="Profile Photo" id="current-photo"
                                onerror="this.src='assets/uploads/users/default.jpg'">
                            <div class="avatar-overlay">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <div class="profile-info">
                            <h2 class="profile-name"><?= htmlspecialchars($user['name']) ?></h2>
                            <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
                            <div class="profile-meta">
                                <span class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    Joined <?= formatDate($user['created_at']) ?>
                                </span>
                                <span class="meta-item">
                                    <i class="fas fa-user-tag"></i>
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Update Form -->
                    <form method="POST" enctype="multipart/form-data" class="profile-form" id="profileForm">
                        <h3 class="section-title">
                            <i class="fas fa-edit"></i>
                            Update Profile Information
                        </h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="photo">Profile Photo</label>
                            <input type="file" id="photo" name="photo" class="form-control"
                                accept="image/*" data-preview="photo-preview">
                            <small class="form-hint">Leave empty to keep current photo</small>
                        </div>

                        <!-- PASTIKAN ADA INPUT HIDDEN -->
                        <input type="hidden" name="update_profile" value="1">

                        <button type="submit" id="updateProfileBtn" class="btn-profile btn-primary">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                    </form>

                    <!-- Password Change Form -->
                    <form method="POST" class="profile-form password-form" id="passwordForm">
                        <h3 class="section-title">
                            <i class="fas fa-lock"></i>
                            Change Password
                        </h3>

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password"
                                class="form-control" required>
                        </div>

                        <div class="form-row">
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
                        </div>

                        <!-- PASTIKAN ADA INPUT HIDDEN -->
                        <input type="hidden" name="change_password" value="1">

                        <button type="submit" id="changePasswordBtn" class="btn-profile btn-secondary">
                            <i class="fas fa-key"></i>
                            Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="profile-sidebar">
                <!-- Account Statistics -->
                <div class="sidebar-card stats-card fade-in">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i>
                        Learning Statistics
                    </h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?= count($enrolled_courses) ?></div>
                            <div class="stat-label">Total Courses</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= count(array_filter($enrolled_courses, fn($e) => $e['status'] === 'completed')) ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= count(array_filter($enrolled_courses, fn($e) => $e['status'] === 'active')) ?></div>
                            <div class="stat-label">In Progress</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= formatCurrency(array_sum(array_column($enrolled_courses, 'price'))) ?></div>
                            <div class="stat-label">Total Invested</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="sidebar-card actions-card fade-in">
                    <h3 class="card-title">
                        <i class="fas fa-rocket"></i>
                        Quick Actions
                    </h3>
                    <div class="actions-list">
                        <a href="courses.php" class="action-btn">
                            <i class="fas fa-search"></i>
                            Browse More Courses
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/index.php" class="action-btn">
                                <i class="fas fa-cog"></i>
                                Admin Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="action-btn logout">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Courses Section -->
        <div class="courses-section fade-in">
            <div class="section-header">
                <h2 class="section-title-main">
                    <i class="fas fa-graduation-cap"></i>
                    My Enrolled Courses (<?= count($enrolled_courses) ?>)
                </h2>
                <p class="section-subtitle">Track your learning progress and continue your studies</p>
            </div>

            <?php if (empty($enrolled_courses)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📚</div>
                    <h3 class="empty-title">No Courses Yet</h3>
                    <p class="empty-description">
                        You haven't enrolled in any courses yet. Start your learning journey today!
                    </p>
                    <a href="courses.php" class="btn-course">
                        <i class="fas fa-search"></i>
                        Browse Courses
                    </a>
                </div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($enrolled_courses as $enrollment): ?>
                        <div class="course-card fade-in">
                            <img src="assets/uploads/courses/<?= htmlspecialchars($enrollment['image']) ?>"
                                alt="<?= htmlspecialchars($enrollment['title']) ?>"
                                class="course-image"
                                onerror="this.src='assets/uploads/courses/default-course.jpg'">

                            <div class="course-content">
                                <?php if ($enrollment['category_name']): ?>
                                    <span class="course-category"><?= htmlspecialchars($enrollment['category_name']) ?></span>
                                <?php endif; ?>

                                <h3 class="course-title"><?= htmlspecialchars($enrollment['title']) ?></h3>
                                <p class="course-description"><?= htmlspecialchars(substr($enrollment['description'], 0, 100)) ?>...</p>

                                <div class="course-info">
                                    <div class="info-item">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span><?= htmlspecialchars($enrollment['instructor']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span>Enrolled <?= formatDate($enrollment['enrollment_date']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-info-circle"></i>
                                        <span class="status-badge status-<?= $enrollment['status'] ?>">
                                            <?= ucfirst($enrollment['status']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="course-meta">
                                    <span class="course-price"><?= formatCurrency($enrollment['price']) ?></span>
                                </div>

                                <div class="course-actions">
                                    <a href="course-detail.php?id=<?= $enrollment['course_id'] ?>"
                                        class="btn-course btn-primary">
                                        <i class="fas fa-eye"></i>
                                        View Course
                                    </a>

                                    <?php if ($enrollment['status'] === 'active'): ?>
                                        <button onclick="updateEnrollmentStatus(<?= $enrollment['id'] ?>, 'completed')"
                                            class="btn-course btn-secondary">
                                            <i class="fas fa-check"></i>
                                            Mark Complete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        // File preview functionality
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('current-photo').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Update enrollment status
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

        // Form handling - PERBAIKAN UTAMA
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide flash messages
            const flashMessages = document.querySelectorAll('.flash-message');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    message.style.transform = 'translateX(100%)';
                    setTimeout(() => message.remove(), 300);
                }, 5000);
            });

            // Profile Form Handling - MINIMAL DAN AMAN
            const profileForm = document.getElementById('profileForm');
            const updateBtn = document.getElementById('updateProfileBtn');

            if (profileForm && updateBtn) {
                profileForm.addEventListener('submit', function(e) {
                    // JANGAN PREVENT DEFAULT - BIARKAN FORM SUBMIT NORMAL
                    console.log('Profile form submitting...');

                    // HANYA UBAH TEXT BUTTON UNTUK FEEDBACK
                    updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                    updateBtn.disabled = true;

                    // JANGAN ADA RETURN FALSE ATAU PREVENT DEFAULT
                });
            }

            // Password Form Handling - MINIMAL DAN AMAN
            const passwordForm = document.getElementById('passwordForm');
            const passwordBtn = document.getElementById('changePasswordBtn');

            if (passwordForm && passwordBtn) {
                passwordForm.addEventListener('submit', function(e) {
                    // JANGAN PREVENT DEFAULT - BIARKAN FORM SUBMIT NORMAL
                    console.log('Password form submitting...');

                    // HANYA UBAH TEXT BUTTON UNTUK FEEDBACK
                    passwordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
                    passwordBtn.disabled = true;

                    // JANGAN ADA RETURN FALSE ATAU PREVENT DEFAULT
                });
            }
        });
    </script>

    <!-- CSS OVERRIDE UNTUK MEMASTIKAN FORM BISA DISUBMIT -->
    <style>
        /* PASTIKAN FORM DAN BUTTON BISA DIKLIK */
        #profileForm,
        #passwordForm,
        #updateProfileBtn,
        #changePasswordBtn {
            pointer-events: auto !important;
            cursor: pointer !important;
            z-index: 999 !important;
            position: relative !important;
        }

        /* PASTIKAN TIDAK ADA OVERLAY YANG MENGHALANGI */
        .btn-profile {
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        /* HAPUS KEMUNGKINAN CSS YANG MENGHALANGI */
        .profile-form form,
        .profile-form button {
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        /* PASTIKAN FORM ELEMENTS VISIBLE DAN CLICKABLE */
        .profile-form {
            position: relative !important;
            z-index: 10 !important;
        }

        .form-control {
            pointer-events: auto !important;
        }
    </style>
</body>

</html>