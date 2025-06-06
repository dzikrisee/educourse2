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
    <link rel="stylesheet" href="assets/css/courses.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php
    include 'partials/navbar.php'
    ?>

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

    <?php
    include 'partials/footer.php'
    ?>

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