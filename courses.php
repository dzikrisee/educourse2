<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get search parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'latest';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(c.title LIKE :search OR c.description LIKE :search2 OR c.instructor LIKE :search3)";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
    $params['search3'] = "%$search%";
}

if ($category_id > 0) {
    $whereConditions[] = "c.category_id = :category_id";
    $params['category_id'] = $category_id;
}

$whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

// Sort options
$orderBy = 'ORDER BY ';
switch ($sort) {
    case 'price_low':
        $orderBy .= 'c.price ASC';
        break;
    case 'price_high':
        $orderBy .= 'c.price DESC';
        break;
    case 'popular':
        $orderBy .= 'enrollment_count DESC';
        break;
    case 'title':
        $orderBy .= 'c.title ASC';
        break;
    default:
        $orderBy .= 'c.created_at DESC';
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM courses c $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalCourses = $countStmt->fetchColumn();

// Get courses with pagination
$sql = "
    SELECT c.*, cat.name as category_name,
           COUNT(e.id) as enrollment_count
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN enrollments e ON c.id = e.course_id
    $whereClause
    GROUP BY c.id
    $orderBy
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Get categories for filter
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categoriesStmt->fetchAll();

// Pagination data
$pagination = getPagination($totalCourses, $page, $perPage);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Courses - EduCourse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Courses Page CSS */
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

        .nav-links a:hover,
        .nav-links a.active {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        /* Page Header */
        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 8rem 0 4rem;
            margin-top: 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.1)" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }

        .page-header-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 2rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 80px;
            z-index: 100;
        }

        .filter-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e1e5e9;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1.1rem;
        }

        .filter-select {
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 15px;
            font-size: 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-btn {
            padding: 1rem 2rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        /* Results Info */
        .results-info {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
        }

        .results-text {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .search-highlight {
            background: var(--warning-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }

        /* Courses Grid */
        .courses-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem 4rem;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .course-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.05);
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
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .course-title {
            font-size: 1.4rem;
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

        .course-info {
            margin-bottom: 1.5rem;
        }

        .course-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .course-info-icon {
            color: #667eea;
            width: 16px;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
            margin-bottom: 1.5rem;
        }

        .course-price {
            font-size: 1.6rem;
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: var(--primary-gradient);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-course:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1/-1;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }

        .empty-description {
            color: #666;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 3rem 0;
        }

        .pagination a,
        .pagination span {
            padding: 12px 16px;
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 44px;
            text-align: center;
        }

        .pagination a:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .pagination .current {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .filter-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .filter-btn {
                justify-content: center;
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }

            .pagination {
                flex-wrap: wrap;
                gap: 0.25rem;
            }

            .pagination a,
            .pagination span {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
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
    </style>
</head>

<body>
    <!-- Enhanced Header -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">EduCourse</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php" class="active">Courses</a></li>
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">Discover Amazing Courses</h1>
            <p class="page-subtitle">Explore our comprehensive collection of courses designed to advance your skills and career</p>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="filter-container">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label">Search Courses</label>
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text"
                            name="search"
                            id="search-input"
                            class="search-input"
                            placeholder="Search by title, instructor, or description..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Category</label>
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select name="sort" class="filter-select">
                        <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Latest</option>
                        <option value="popular" <?= $sort == 'popular' ? 'selected' : '' ?>>Most Popular</option>
                        <option value="title" <?= $sort == 'title' ? 'selected' : '' ?>>Title A-Z</option>
                        <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>
                </div>

                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i>
                    Apply Filters
                </button>
            </form>
        </div>
    </section>

    <!-- Results Info -->
    <div class="results-info">
        <p class="results-text">
            Showing <strong><?= count($courses) ?></strong> of <strong><?= $totalCourses ?></strong> courses
            <?php if (!empty($search)): ?>
                for "<span class="search-highlight"><?= htmlspecialchars($search) ?></span>"
            <?php endif; ?>
        </p>
    </div>

    <!-- Courses Grid -->
    <div class="courses-container">
        <div id="search-results">
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Loading courses...</p>
            </div>

            <?php if (empty($courses)): ?>
                <div class="courses-grid">
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <h3 class="empty-title">No Courses Found</h3>
                        <p class="empty-description">
                            <?php if (!empty($search)): ?>
                                We couldn't find any courses matching "<strong><?= htmlspecialchars($search) ?></strong>". Try adjusting your search criteria.
                            <?php else: ?>
                                No courses are available at the moment. Check back soon for new additions!
                            <?php endif; ?>
                        </p>
                        <a href="courses.php" class="btn-course" style="max-width: 200px; margin: 0 auto;">
                            <i class="fas fa-refresh"></i>
                            View All Courses
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card fade-in" data-category="<?= $course['category_id'] ?>">
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

                                <div class="course-info">
                                    <div class="course-info-item">
                                        <i class="fas fa-chalkboard-teacher course-info-icon"></i>
                                        <span><?= htmlspecialchars($course['instructor']) ?></span>
                                    </div>
                                    <div class="course-info-item">
                                        <i class="fas fa-clock course-info-icon"></i>
                                        <span><?= htmlspecialchars($course['duration']) ?></span>
                                    </div>
                                </div>

                                <div class="course-meta">
                                    <span class="course-price"><?= formatCurrency($course['price']) ?></span>
                                    <span class="course-students">
                                        <i class="fas fa-users"></i>
                                        <?= $course['enrollment_count'] ?> students
                                    </span>
                                </div>

                                <a href="course-detail.php?id=<?= $course['id'] ?>" class="btn-course">
                                    <i class="fas fa-eye"></i>
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['has_prev']): ?>
                    <a href="?page=<?= $pagination['current_page'] - 1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_id ?>&sort=<?= $sort ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <?php if ($i == $pagination['current_page']): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category_id ?>&sort=<?= $sort ?>">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['has_next']): ?>
                    <a href="?page=<?= $pagination['current_page'] + 1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_id ?>&sort=<?= $sort ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize live search for courses page
            initLiveSearch('search-input', 'search-results', 'search-ajax.php');

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

            document.querySelectorAll('.course-card').forEach(el => {
                observer.observe(el);
            });

            // Enhanced search with loading state
            const searchInput = document.getElementById('search-input');
            const loading = document.getElementById('loading');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    if (this.value.length >= 2) {
                        loading.classList.add('active');
                    }
                });
            }

            // Form submission with loading state
            document.querySelector('.filter-form').addEventListener('submit', function() {
                loading.classList.add('active');
            });
        });
    </script>
</body>

</html>