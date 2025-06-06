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
    <title>EduCourse - Transform Your Future with Online Learning</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>

    </style>
</head>

<body>
    <?php
    include 'partials/navbar.php'
    ?>

    <!-- Enhanced Hero Section -->
    <section class="hero">
        <div class="floating-elements">
            <div class="floating-icon">📚</div>
            <div class="floating-icon">🎓</div>
            <div class="floating-icon">💡</div>
            <div class="floating-icon">🚀</div>
        </div>

        <div class="hero-content">
            <h1>Transform Your Future with Online Learning</h1>
            <p>Join thousands of students worldwide and unlock your potential with our comprehensive courses designed by industry experts</p>
            <div class="hero-buttons">
                <a href="courses.php" class="btn-hero primary">
                    <i class="fas fa-play"></i>
                    Start Learning Today
                </a>
                <a href="#features" class="btn-hero">
                    <i class="fas fa-info-circle"></i>
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- Enhanced Statistics Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card fade-in">
                <div class="stat-icon">📖</div>
                <div class="stat-number"><?= number_format($stats['courses']) ?>+</div>
                <div class="stat-label">Quality Courses</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-icon">👨‍🎓</div>
                <div class="stat-number"><?= number_format($stats['users']) ?>+</div>
                <div class="stat-label">Happy Students</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?= number_format($stats['enrollments']) ?>+</div>
                <div class="stat-label">Course Enrollments</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-icon">🏷️</div>
                <div class="stat-number"><?= number_format($stats['categories']) ?>+</div>
                <div class="stat-label">Course Categories</div>
            </div>
        </div>
    </section>

    <!-- Enhanced Popular Courses Section -->
    <section class="courses-section">
        <div class="section-header">
            <h2 class="section-title">Most Popular Courses</h2>
            <p class="section-subtitle">Discover our top-rated courses chosen by thousands of students worldwide</p>
        </div>

        <div class="courses-grid">
            <?php if (empty($popular_courses)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
                    <h3>No Courses Available Yet</h3>
                    <p style="color: #666; margin: 1rem 0;">Be the first to add amazing courses to our platform!</p>
                    <?php if (isAdmin()): ?>
                        <a href="admin/courses/create.php" class="btn-course">
                            <i class="fas fa-plus"></i>
                            Add First Course
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($popular_courses as $course): ?>
                    <div class="course-card fade-in">
                        <img src="assets/uploads/courses/<?= htmlspecialchars($course['image']) ?>"
                            alt="<?= htmlspecialchars($course['title']) ?>"
                            class="course-image"
                            onerror="this.src='assets/uploads/courses/default-course.jpg'">

                        <div class="course-content">
                            <?php if ($course['category_name']): ?>
                                <span class="course-category"><?= htmlspecialchars($course['category_name']) ?></span>
                            <?php endif; ?>

                            <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                            <p class="course-description"><?= htmlspecialchars(substr($course['description'], 0, 120)) ?>...</p>

                            <div class="course-instructor">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span><?= htmlspecialchars($course['instructor']) ?></span>
                            </div>

                            <div class="course-meta">
                                <div class="course-price"><?= formatCurrency($course['price']) ?></div>
                                <div class="course-students">
                                    <i class="fas fa-users"></i>
                                    <span><?= $course['enrollment_count'] ?> students</span>
                                </div>
                            </div>

                            <a href="course-detail.php?id=<?= $course['id'] ?>" class="btn-course">
                                <i class="fas fa-eye"></i>
                                View Course Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($popular_courses)): ?>
            <div style="text-align: center; margin-top: 3rem;">
                <a href="courses.php" class="btn-hero primary" style="display: inline-flex;">
                    <i class="fas fa-th-large"></i>
                    Explore All Courses
                </a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Enhanced Features Section -->
    <section class="features-section" id="features">
        <div class="section-header">
            <h2 class="section-title">Why Choose EduCourse?</h2>
            <p class="section-subtitle">We provide the best online learning experience with cutting-edge features</p>
        </div>

        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon">👨‍🏫</div>
                <h3 class="feature-title">Expert Instructors</h3>
                <p class="feature-description">Learn from industry professionals and experienced educators who bring real-world knowledge to every lesson</p>
            </div>
            <div class="feature-card fade-in">
                <div class="feature-icon">⏰</div>
                <h3 class="feature-title">Flexible Learning</h3>
                <p class="feature-description">Study at your own pace, anytime and anywhere. Our platform is available 24/7 on all devices</p>
            </div>
            <div class="feature-card fade-in">
                <div class="feature-icon">💰</div>
                <h3 class="feature-title">Affordable Prices</h3>
                <p class="feature-description">Get access to high-quality education at competitive prices. Invest in your future without breaking the bank</p>
            </div>
        </div>
    </section>

    <?php
    include 'partials/footer.php'
    ?>

    <!-- Flash Messages -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
        <div class="flash-message flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <script src="assets/js/script.js"></script>
</body>

</html>