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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Course Detail Page CSS */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            --hover-transform: translateY(-10px);
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            overflow-x: hidden;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Enhanced Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo::before {
            content: "🎓";
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 1rem;
            border-radius: 25px;
        }

        .nav-links a:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 6rem 2rem 4rem;
            margin-top: 35px;
        }

        /* Course Header */
        .course-header {
            background: white;
            border-radius: 25px;
            padding: 3rem;
            margin-bottom: 3rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .course-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--primary-gradient);
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: #666;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .breadcrumb a:hover {
            opacity: 0.7;
        }

        .course-title {
            font-size: 3rem;
            font-weight: 800;
            color: #333;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .course-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .course-meta-header {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            align-items: center;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #555;
            font-weight: 600;
        }

        .meta-icon {
            color: #667eea;
            font-size: 1.1rem;
        }

        .course-category-tag {
            background: var(--primary-gradient);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Main Content Layout */
        .course-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            align-items: start;
        }

        /* Course Content */
        .course-main {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .course-image-container {
            position: relative;
            overflow: hidden;
        }

        .course-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .course-image:hover {
            transform: scale(1.05);
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .course-image-container:hover .image-overlay {
            opacity: 1;
        }

        .play-button {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #667eea;
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }

        .image-overlay:hover .play-button {
            transform: scale(1);
        }

        .course-content-section {
            padding: 3rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-icon {
            color: #667eea;
            font-size: 1.5rem;
        }

        .course-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 2rem;
        }

        .course-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9ff;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: #f0f2ff;
            transform: translateY(-3px);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .feature-text {
            flex: 1;
        }

        .feature-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .feature-description {
            font-size: 0.9rem;
            color: #666;
        }

        /* Sidebar */
        .course-sidebar {
            position: sticky;
            top: 6rem;
        }

        .sidebar-card {
            background: white;
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .sidebar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .price-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .price-badge {
            background: var(--success-gradient);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 2rem;
            font-weight: 800;
            display: inline-block;
            margin-bottom: 1rem;
            box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);
        }

        .enrollment-section {
            text-align: center;
        }

        .btn-enroll {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            width: 100%;
            padding: 1.2rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: var(--warning-gradient);
            color: white;
        }

        .btn-login {
            background: var(--secondary-gradient);
            color: white;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(240, 147, 251, 0.4);
        }

        .enrollment-note {
            font-size: 0.9rem;
            color: #666;
            text-align: center;
            margin-top: 1rem;
        }

        .course-includes {
            list-style: none;
            padding: 0;
        }

        .course-includes li {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 500;
            color: #555;
        }

        .course-includes li:last-child {
            border-bottom: none;
        }

        .check-icon {
            color: #43e97b;
            font-size: 1.1rem;
        }

        /* Flash Messages */
        .flash-message {
            position: fixed;
            top: 100px;
            right: 2rem;
            padding: 1.2rem 2rem;
            border-radius: 15px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .flash-success {
            background: var(--warning-gradient);
        }

        .flash-error {
            background: var(--secondary-gradient);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
            }

            to {
                transform: translateX(0);
            }
        }

        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(102, 126, 234, 0.2);
            margin: 2rem auto;
            max-width: 200px;
        }

        .back-button:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .back-container {
            text-align: center;
            margin-top: 3rem;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .course-layout {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .course-sidebar {
                position: static;
                order: -1;
            }

            .course-title {
                font-size: 2.2rem;
            }

            .course-header {
                padding: 2rem;
            }

            .course-content-section {
                padding: 2rem;
            }

            .sidebar-card {
                padding: 2rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 5rem 1rem 2rem;
            }

            .course-title {
                font-size: 1.8rem;
            }

            .course-meta-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .course-features {
                grid-template-columns: 1fr;
            }

            .course-image {
                height: 250px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease forwards;
        }

        .fade-in:nth-child(1) {
            animation-delay: 0.1s;
        }

        .fade-in:nth-child(2) {
            animation-delay: 0.2s;
        }

        .fade-in:nth-child(3) {
            animation-delay: 0.3s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <!-- Enhanced Header -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">EduCourse</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php">Courses</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin/index.php"><i class="fas fa-cog"></i> Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- Course Header -->
        <div class="course-header fade-in">
            <nav class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="courses.php">Courses</a>
                <i class="fas fa-chevron-right"></i>
                <span><?= htmlspecialchars($course['title']) ?></span>
            </nav>

            <h1 class="course-title"><?= htmlspecialchars($course['title']) ?></h1>
            <p class="course-subtitle"><?= htmlspecialchars(substr($course['description'], 0, 200)) ?>...</p>

            <div class="course-meta-header">
                <div class="meta-item">
                    <i class="fas fa-chalkboard-teacher meta-icon"></i>
                    <span><?= htmlspecialchars($course['instructor']) ?></span>
                </div>
                <?php if ($course['category_name']): ?>
                    <span class="course-category-tag"><?= htmlspecialchars($course['category_name']) ?></span>
                <?php endif; ?>
                <div class="meta-item">
                    <i class="fas fa-users meta-icon"></i>
                    <span><?= $course['enrollment_count'] ?> students enrolled</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar meta-icon"></i>
                    <span>Added <?= formatDate($course['created_at']) ?></span>
                </div>
            </div>
        </div>

        <!-- Main Content Layout -->
        <div class="course-layout">
            <!-- Main Content -->
            <div class="course-main fade-in">
                <div class="course-image-container">
                    <img src="assets/uploads/courses/<?= htmlspecialchars($course['image']) ?>"
                        alt="<?= htmlspecialchars($course['title']) ?>"
                        class="course-image"
                        onerror="this.src='assets/uploads/courses/default-course.jpg'">
                    <div class="image-overlay">
                        <div class="play-button">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                </div>

                <div class="course-content-section">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle section-icon"></i>
                        Course Description
                    </h2>
                    <div class="course-description">
                        <?= nl2br(htmlspecialchars($course['description'])) ?>
                    </div>

                    <h3 class="section-title">
                        <i class="fas fa-star section-icon"></i>
                        What You'll Learn
                    </h3>
                    <div class="course-features">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div class="feature-text">
                                <div class="feature-title">Expert Knowledge</div>
                                <div class="feature-description">Learn from industry professionals</div>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <div class="feature-text">
                                <div class="feature-title">Practical Skills</div>
                                <div class="feature-description">Hands-on experience with real projects</div>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="feature-text">
                                <div class="feature-title">Certification</div>
                                <div class="feature-description">Get recognized for your achievements</div>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="feature-text">
                                <div class="feature-title">Community</div>
                                <div class="feature-description">Connect with fellow learners</div>
                            </div>
                        </div>
                    </div>

                    <div class="course-meta mb-2">
                        <p><strong>Instructor:</strong> <?= htmlspecialchars($course['instructor']) ?></p>
                        <p><strong>Category:</strong> <?= htmlspecialchars($course['category_name'] ?? 'Uncategorized') ?></p>
                        <p><strong>Duration:</strong> <?= htmlspecialchars($course['duration']) ?></p>
                        <p><strong>Students Enrolled:</strong> <?= $course['enrollment_count'] ?></p>
                        <p><strong>Created:</strong> <?= formatDate($course['created_at']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="course-sidebar">
                <div class="sidebar-card fade-in">
                    <div class="price-section">
                        <div class="price-badge"><?= formatCurrency($course['price']) ?></div>
                    </div>

                    <div class="enrollment-section">
                        <?php if (isLoggedIn()): ?>
                            <?php if ($is_enrolled): ?>
                                <div style="text-align: center; margin-bottom: 1.5rem;">
                                    <div style="background: var(--warning-gradient); color: white; padding: 1rem; border-radius: 15px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                        <i class="fas fa-check-circle"></i>
                                        You are enrolled in this course
                                    </div>
                                </div>
                                <a href="profile.php" class="btn-enroll btn-success">
                                    <i class="fas fa-graduation-cap"></i>
                                    Go to My Courses
                                </a>
                            <?php else: ?>
                                <form method="POST">
                                    <button type="submit" name="enroll" class="btn-enroll btn-primary">
                                        <i class="fas fa-play-circle"></i>
                                        Enroll Now
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="enrollment-note" style="margin-bottom: 1.5rem;">
                                <p>Please login to enroll in this course</p>
                            </div>
                            <a href="login.php" class="btn-enroll btn-login">
                                <i class="fas fa-sign-in-alt"></i>
                                Login to Enroll
                            </a>
                        <?php endif; ?>

                        <p class="enrollment-note">
                            <i class="fas fa-shield-alt"></i>
                            30-day money-back guarantee
                        </p>
                    </div>
                </div>

                <div class="sidebar-card fade-in">
                    <h4 class="section-title">
                        <i class="fas fa-list-check section-icon"></i>
                        Course Includes:
                    </h4>
                    <ul class="course-includes">
                        <li>
                            <i class="fas fa-infinity check-icon"></i>
                            Lifetime access
                        </li>
                        <li>
                            <i class="fas fa-certificate check-icon"></i>
                            Certificate of completion
                        </li>
                        <li>
                            <i class="fas fa-headset check-icon"></i>
                            Expert instructor support
                        </li>
                        <li>
                            <i class="fas fa-mobile-alt check-icon"></i>
                            Mobile and desktop access
                        </li>
                        <li>
                            <i class="fas fa-download check-icon"></i>
                            Downloadable resources
                        </li>
                        <li>
                            <i class="fas fa-users check-icon"></i>
                            Community access
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Back to Courses -->
        <div class="back-container">
            <a href="courses.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to All Courses
            </a>
        </div>
    </div>

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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in animation on load
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Auto-hide flash messages
            const flashMessage = document.querySelector('.flash-message');
            if (flashMessage) {
                setTimeout(() => {
                    flashMessage.style.transform = 'translateX(100%)';
                    setTimeout(() => flashMessage.remove(), 300);
                }, 5000);
            }

            // Enrollment form loading state
            const enrollForm = document.querySelector('form[method="POST"]');
            if (enrollForm) {
                enrollForm.addEventListener('submit', function() {
                    const button = this.querySelector('button[type="submit"]');
                    const originalText = button.innerHTML;
                    button.innerHTML = '<div class="loading"></div> Enrolling...';
                    button.disabled = true;
                });
            }

            // Smooth scroll for internal links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>