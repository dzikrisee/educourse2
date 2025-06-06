<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

// Get search and pagination parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query with search
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE (c.title LIKE :search OR c.instructor LIKE :search2 OR cat.name LIKE :search3)";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
    $params['search3'] = "%$search%";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM courses c LEFT JOIN categories cat ON c.category_id = cat.id $whereClause";
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
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Pagination data
$pagination = getPagination($totalCourses, $page, $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../../index.php" class="logo">EduCourse Admin</a>
            <ul class="nav-links">
                <li><a href="../../index.php">View Site</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="index.php" class="active">Courses</a></li>
                <li><a href="../categories/index.php">Categories</a></li>
                <li><a href="../users/index.php">Users</a></li>
                <li><a href="../enrollments/index.php">Enrollments</a></li>
                <li><a href="../enrollments/report.php">Reports</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="d-flex justify-between align-center mb-2">
                <h1>Manage Courses</h1>
                <a href="create.php" class="btn">Add New Course</a>
            </div>

            <!-- Flash Messages -->
            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
                <div class="flash-message flash-<?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Search -->
            <div class="search-container">
                <form method="GET" class="search-box" style="max-width: 400px;">
                    <input type="text" name="search" class="search-input" 
                           placeholder="Search courses..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">🔍</button>
                </form>
            </div>

            <!-- Results Info -->
            <p class="text-center">Showing <?= count($courses) ?> of <?= $totalCourses ?> courses</p>

            <!-- Courses Table -->
            <div class="table-container">
                <table class="table" id="coursesTable">
                    <thead>
                        <tr>
                            <th class="sortable">ID</th>
                            <th class="sortable">Image</th>
                            <th class="sortable">Title</th>
                            <th class="sortable">Instructor</th>
                            <th class="sortable">Category</th>
                            <th class="sortable">Price</th>
                            <th class="sortable">Enrollments</th>
                            <th class="sortable">Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    <?php if (!empty($search)): ?>
                                        No courses found for "<?= htmlspecialchars($search) ?>"
                                    <?php else: ?>
                                        No courses found. <a href="create.php">Add the first course</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= $course['id'] ?></td>
                                    <td>
                                        <img src="../../assets/uploads/courses/<?= htmlspecialchars($course['image']) ?>" 
                                             alt="Course Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;"
                                             onerror="this.src='../../assets/uploads/courses/default-course.jpg'">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($course['title']) ?></strong><br>
                                        <small><?= htmlspecialchars(substr($course['description'], 0, 50)) ?>...</small>
                                    </td>
                                    <td><?= htmlspecialchars($course['instructor']) ?></td>
                                    <td><?= htmlspecialchars($course['category_name'] ?? 'Uncategorized') ?></td>
                                    <td><?= formatCurrency($course['price']) ?></td>
                                    <td><?= $course['enrollment_count'] ?></td>
                                    <td><?= formatDate($course['created_at']) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="../../course-detail.php?id=<?= $course['id'] ?>" 
                                               class="btn btn-sm" title="View">👁️</a>
                                            <a href="edit.php?id=<?= $course['id'] ?>" 
                                               class="btn btn-primary btn-sm" title="Edit">✏️</a>
                                            <a href="delete.php?id=<?= $course['id'] ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirmDelete('Are you sure you want to delete this course?')"
                                               title="Delete">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?page=<?= $pagination['current_page'] - 1 ?>&search=<?= urlencode($search) ?>">
                            « Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <?php if ($i == $pagination['current_page']): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?= $pagination['current_page'] + 1 ?>&search=<?= urlencode($search) ?>">
                            Next »
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../../assets/js/script.js"></script>
</body>
</html>