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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Education Theme CSS */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --dark-bg: #1a1a2e;
            --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            --hover-transform: translateY(-10px);
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            overflow-x: hidden;
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

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
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

        .nav-links a:hover,
        .nav-links a.active {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8rem 0 4rem;
            position: relative;
            overflow: hidden;
            margin-top: 80px;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.1)" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-hero {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-hero:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-hero.primary {
            background: white;
            color: #667eea;
        }

        .btn-hero.primary:hover {
            background: #f8f9ff;
            color: #5a6fd8;
        }

        /* Floating Elements */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .floating-icon {
            position: absolute;
            font-size: 2rem;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        .floating-icon:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-icon:nth-child(2) {
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-icon:nth-child(3) {
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }

        .floating-icon:nth-child(4) {
            top: 40%;
            right: 30%;
            animation-delay: 1s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(5deg);
            }
        }

        /* Stats Section */
        .stats-section {
            padding: 4rem 0;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .stat-card {
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: var(--hover-transform);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 600;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        /* Popular Courses Section */
        .courses-section {
            padding: 5rem 0;
            background: white;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #333;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .course-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            position: relative;
        }

        .course-card:hover {
            transform: var(--hover-transform);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .course-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .course-card:hover .course-image {
            transform: scale(1.05);
        }

        .course-content {
            padding: 2rem;
        }

        .course-category {
            display: inline-block;
            background: var(--primary-gradient);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .course-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.8rem;
            line-height: 1.4;
        }

        .course-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .course-instructor {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .course-price {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .course-students {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #666;
            font-size: 0.9rem;
        }

        .btn-course {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: var(--primary-gradient);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 1rem;
            width: 100%;
            justify-content: center;
        }

        .btn-course:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        /* Features Section */
        .features-section {
            padding: 5rem 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .feature-description {
            opacity: 0.9;
            line-height: 1.6;
        }

        /* Flash Messages */
        .flash-message {
            position: fixed;
            top: 100px;
            right: 2rem;
            padding: 1rem 2rem;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            animation: slideIn 0.3s ease;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                padding: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .hero {
                padding: 6rem 0 3rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .courses-grid {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                padding: 0 1rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-gradient);
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <!-- Enhanced Header -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">EduCourse</a>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
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
        // Enhanced JavaScript for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Navbar scroll effect
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Fade in animation on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animationDelay = Math.random() * 0.3 + 's';
                        entry.target.classList.add('fade-in');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.stat-card, .course-card, .feature-card').forEach(el => {
                observer.observe(el);
            });

            // Auto-hide flash messages
            const flashMessage = document.querySelector('.flash-message');
            if (flashMessage) {
                setTimeout(() => {
                    flashMessage.style.transform = 'translateX(100%)';
                    setTimeout(() => flashMessage.remove(), 300);
                }, 5000);
            }

            // Smooth scroll for anchor links
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