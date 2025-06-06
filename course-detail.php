<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id == 0) {
    header('Location: courses.php');
    exit();
}

// Get course details
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name,
           COUNT(e.id) as enrollment_count
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE c.id = ?
    GROUP BY c.id
");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: courses.php');
    exit();
}

// Check if user is already enrolled
$is_enrolled = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $is_enrolled = (bool)$stmt->fetch();
}

// Handle enrollment
if ($_POST && isset($_POST['enroll']) && isLoggedIn() && !$is_enrolled) {
    $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $course_id])) {
        setFlashMessage('Successfully enrolled in the course!');
        $is_enrolled = true;
    } else {
        setFlashMessage('Enrollment failed. Please try again.', 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> - EduCourse</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">EduCourse</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php">Courses</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="profile.php">Profile</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin/index.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- Flash Messages -->
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
            <div class="flash-message flash-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Course Detail -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin: 2rem 0;">
            <!-- Main Content -->
            <div>
                <img src="assets/uploads/courses/<?= htmlspecialchars($course['image']) ?>"
                    alt="<?= htmlspecialchars($course['title']) ?>"
                    style="width: 100%; height: 300px; object-fit: cover; border-radius: 10px; margin-bottom: 2rem;"
                    onerror="this.src='assets/uploads/courses/default-course.jpg'">

                <h1><?= htmlspecialchars($course['title']) ?></h1>

                <div class="card">
                    <h3>Course Description</h3>
                    <p><?= nl2br(htmlspecialchars($course['description'])) ?></p>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <div class="card">
                    <h3 class="text-center"><?= formatCurrency($course['price']) ?></h3>

                    <?php if (isLoggedIn()): ?>
                        <?php if ($is_enrolled): ?>
                            <div class="text-center">
                                <p class="flash-message flash-success">✅ You are enrolled in this course</p>
                                <a href="profile.php" class="btn btn-primary" style="width: 100%;">
                                    Go to My Courses
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <button type="submit" name="enroll" class="btn" style="width: 100%;">
                                    Enroll Now
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center">
                            <p>Please login to enroll in this course</p>
                            <a href="login.php" class="btn btn-primary" style="width: 100%;">
                                Login to Enroll
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card mt-2">
                    <h4>Course Includes:</h4>
                    <ul>
                        <li>✓ Lifetime access</li>
                        <li>✓ Certificate of completion</li>
                        <li>✓ Expert instructor support</li>
                        <li>✓ Mobile and desktop access</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Back to Courses -->
        <div class="text-center mt-2">
            <a href="courses.php" class="btn btn-primary">← Back to All Courses</a>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>course-meta mb-2">
<p><strong>Instructor:</strong> <?= htmlspecialchars($course['instructor']) ?></p>
<p><strong>Category:</strong> <?= htmlspecialchars($course['category_name'] ?? 'Uncategorized') ?></p>
<p><strong>Duration:</strong> <?= htmlspecialchars($course['duration']) ?></p>
<p><strong>Students Enrolled:</strong> <?= $course['enrollment_count'] ?></p>
<p><strong>Created:</strong> <?= formatDate($course['created_at']) ?></p>
</div>

<div class="