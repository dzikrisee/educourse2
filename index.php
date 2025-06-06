<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get popular courses (limit 6)
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name,
           COUNT(e.id) as enrollment_count
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id
    ORDER BY enrollment_count DESC, c.created_at DESC
    LIMIT 6
");
$stmt->execute();
$popular_courses = $stmt->fetchAll();

// Get total statistics
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total_courses FROM courses");
$stats['courses'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$stats['users'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total_enrollments FROM enrollments");
$stats['enrollments'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM categories");
$stats['categories'] = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduCourse - Online Learning Platform</title>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Learn New Skills Online</h1>
            <p>Discover thousands of courses from expert instructors and advance your career</p>
            <a href="courses.php" class="btn btn-primary">Browse Courses</a>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="container">
        <div class="card-grid">
            <div class="card text-center">
                <h3><?= number_format($stats['courses']) ?></h3>
                <p>Total Courses</p>
            </div>
            <div class="card text-center">
                <h3><?= number_format($stats['users']) ?></h3>
                <p>Students</p>
            </div>
            <div class="card text-center">
                <h3><?= number_format($stats['enrollments']) ?></h3>
                <p>Enrollments</p>
            </div>
            <div class="card text-center">
                <h3><?= number_format($stats['categories']) ?></h3>
                <p>Categories</p>
            </div>
        </div>
    </section>

    <!-- Popular Courses Section -->
    <section class="container">
        <h2 class="text-center mb-2">Popular Courses</h2>
        <div class="card-grid">
            <?php foreach ($popular_courses as $course): ?>
                <div class="card">
                    <img src="assets/uploads/courses/<?= htmlspecialchars($course['image']) ?>"
                        alt="<?= htmlspecialchars($course['title']) ?>"
                        class="card-img"
                        onerror="this.src='assets/uploads/courses/default-course.jpg'">
                    <h3><?= htmlspecialchars($course['title']) ?></h3>
                    <p><?= htmlspecialchars(substr($course['description'], 0, 100)) ?>...</p>
                    <div class="course-meta">
                        <span class="price"><?= formatCurrency($course['price']) ?></span>
                        <span><?= $course['enrollment_count'] ?> students</span>
                    </div>
                    <div class="mt-2">
                        <a href="course-detail.php?id=<?= $course['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($popular_courses) == 0): ?>
            <div class="text-center">
                <p>No courses available yet.</p>
                <?php if (isAdmin()): ?>
                    <a href="admin/courses/create.php" class="btn">Add First Course</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Features Section -->
    <section class="container">
        <h2 class="text-center mb-2">Why Choose EduCourse?</h2>
        <div class="card-grid">
            <div class="card text-center">
                <h3>Expert Instructors</h3>
                <p>Learn from industry professionals with years of experience</p>
            </div>
            <div class="card text-center">
                <h3>Flexible Learning</h3>
                <p>Study at your own pace, anytime and anywhere</p>
            </div>
            <div class="card text-center">
                <h3>Affordable Prices</h3>
                <p>High-quality education at competitive prices</p>
            </div>
        </div>
    </section>

    <!-- Flash Messages -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
        <div class="flash-message flash-<?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <script src="assets/js/script.js"></script>
</body>

</html>