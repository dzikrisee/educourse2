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
</head>
<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">EduCourse</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php" class="active">Courses</a></li>
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
        <h1 class="text-center mb-2">All Courses</h1>
        
        <!-- Search and Filter Section -->
        <div class="search-container">
            <form method="GET" class="d-flex justify-between align-center gap-2" style="max-width: 800px; margin: 0 auto;">
                <div class="search-box" style="flex: 1;">
                    <input type="text" name="search" id="search-input" class="search-input" 
                           placeholder="Search courses..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">🔍</button>
                </div>
                
                <select name="category" class="form-control" style="width: 200px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="sort" class="form-control" style="width: 150px;">
                    <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Latest</option>
                    <option value="popular" <?= $sort == 'popular' ? 'selected' : '' ?>>Popular</option>
                    <option value="title" <?= $sort == 'title' ? 'selected' : '' ?>>Title A-Z</option>
                    <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                </select>
                
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>

        <!-- Results Info -->
        <div class="text-center mb-2">
            <p>Showing <?= count($courses) ?> of <?= $totalCourses ?> courses</p>
            <?php if (!empty($search)): ?>
                <p>Search results for: "<strong><?= htmlspecialchars($search) ?></strong>"</p>
            <?php endif; ?>
        </div>

        <!-- Courses Grid -->
        <div id="search-results">
            <?php if (empty($courses)): ?>
                <div class="text-center">
                    <h3>No courses found</h3>
                    <p>Try adjusting your search criteria or browse all courses.</p>
                    <a href="courses.php" class="btn">View All Courses</a>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="card course-card" data-category="<?= $course['category_id'] ?>">
                            <img src="assets/uploads/courses/<?= htmlspecialchars($course['image']) ?>" 
                                 alt="<?= htmlspecialchars($course['title']) ?>" 
                                 class="card-img"
                                 onerror="this.src='assets/uploads/courses/default-course.jpg'">
                            
                            <div class="card-content">
                                <h3><?= htmlspecialchars($course['title']) ?></h3>
                                <p><?= htmlspecialchars(substr($course['description'], 0, 120)) ?>...</p>
                                
                                <div class="course-info mb-1">
                                    <small><strong>Instructor:</strong> <?= htmlspecialchars($course['instructor']) ?></small><br>
                                    <small><strong>Category:</strong> <?= htmlspecialchars($course['category_name'] ?? 'Uncategorized') ?></small><br>
                                    <small><strong>Duration:</strong> <?= htmlspecialchars($course['duration']) ?></small>
                                </div>
                                
                                <div class="course-meta">
                                    <span class="price"><?= formatCurrency($course['price']) ?></span>
                                    <span><?= $course['enrollment_count'] ?> students</span>
                                </div>
                                
                                <div class="mt-2">
                                    <a href="course-detail.php?id=<?= $course['id'] ?>" 
                                       class="btn btn-primary btn-sm">View Details</a>
                                </div>
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
                        « Previous
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
                        Next »
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Initialize live search for courses page
        document.addEventListener('DOMContentLoaded', function() {
            initLiveSearch('search-input', 'search-results', 'search-ajax.php');
        });
    </script>
</body>
</html>