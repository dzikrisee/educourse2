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

// Check if user is already enrolled - FUNGSI YANG TERBUKTI BEKERJA
$is_enrolled = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $is_enrolled = (bool)$stmt->fetch();
}

// Handle enrollment - FUNGSI YANG TERBUKTI BEKERJA
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
    <link rel="stylesheet" href="assets/css/courses-detail.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'partials/navbar.php'; ?>

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
                                <!-- FORM ENROLLMENT YANG DIPERBAIKI -->
                                <form method="POST" id="enrollmentForm" style="margin-bottom: 1rem;">
                                    <!-- PASTIKAN ADA INPUT HIDDEN -->
                                    <input type="hidden" name="enroll" value="1">
                                    <input type="hidden" name="course_id" value="<?= $course_id ?>">

                                    <!-- BUTTON DENGAN STYLING YANG BENAR -->
                                    <button type="submit"
                                        id="enrollButton"
                                        class="btn-enroll btn-primary"
                                        style="width: 100%; border: none; cursor: pointer; pointer-events: auto !important;">
                                        <i class="fas fa-play-circle"></i>
                                        Book Now
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="enrollment-note" style="margin-bottom: 1.5rem;">
                                <p>Please login to booking in this course</p>
                            </div>
                            <a href="login.php" class="btn-enroll btn-login">
                                <i class="fas fa-sign-in-alt"></i>
                                Login to Booking
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

    <?php include 'partials/footer.php'; ?>

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

    <!-- JAVASCRIPT YANG DIPERBAIKI - MINIMAL DAN TIDAK CONFLICT -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in animation - AMAN
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((el, index) => {
                setTimeout(() => {
                    if (el.style) {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }
                }, index * 200);
            });

            // Auto-hide flash messages - AMAN
            const flashMessage = document.querySelector('.flash-message');
            if (flashMessage) {
                setTimeout(() => {
                    flashMessage.style.transform = 'translateX(100%)';
                    setTimeout(() => flashMessage.remove(), 300);
                }, 5000);
            }

            // ENROLLMENT FORM HANDLING - MINIMAL DAN AMAN
            const enrollForm = document.getElementById('enrollmentForm');
            const enrollButton = document.getElementById('enrollButton');

            if (enrollForm && enrollButton) {
                // PASTIKAN TIDAK ADA EVENT YANG PREVENT DEFAULT
                enrollForm.addEventListener('submit', function(e) {
                    // JANGAN PREVENT DEFAULT - BIARKAN FORM SUBMIT NORMAL
                    console.log('Form submitting normally...');

                    // HANYA UBAH TEXT BUTTON UNTUK FEEDBACK
                    enrollButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Booking...';
                    enrollButton.disabled = true;

                    // JANGAN ADA RETURN FALSE ATAU PREVENT DEFAULT
                });
            }

            // Smooth scroll - AMAN
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

    <!-- CSS OVERRIDE UNTUK MEMASTIKAN BUTTON BISA DIKLIK -->
    <style>
        /* PASTIKAN BUTTON ENROLLMENT BISA DIKLIK */
        #enrollmentForm,
        #enrollButton {
            pointer-events: auto !important;
            cursor: pointer !important;
            z-index: 999 !important;
            position: relative !important;
        }

        /* PASTIKAN TIDAK ADA OVERLAY YANG MENGHALANGI */
        .btn-enroll {
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        /* HAPUS KEMUNGKINAN CSS YANG MENGHALANGI */
        .enrollment-section form,
        .enrollment-section button {
            pointer-events: auto !important;
            cursor: pointer !important;
        }
    </style>
</body>

</html>