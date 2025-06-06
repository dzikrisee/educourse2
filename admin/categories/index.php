<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

// Get search parameter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query with search
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE (name LIKE :search OR description LIKE :search2)";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM categories $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalCategories = $countStmt->fetchColumn();

// Get categories with pagination and course count
$sql = "
    SELECT c.*, COUNT(co.id) as course_count
    FROM categories c
    LEFT JOIN courses co ON c.id = co.category_id
    $whereClause
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Pagination data
$pagination = getPagination($totalCategories, $page, $perPage);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
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
                <li><a href="../courses/index.php">Courses</a></li>
                <li><a href="index.php" class="active">Categories</a></li>
                <li><a href="../users/index.php">Users</a></li>
                <li><a href="../enrollments/index.php">Enrollments</a></li>
                <li><a href="../enrollments/report.php">Reports</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="d-flex justify-between align-center mb-2">
                <h1>Manage Categories</h1>
                <a href="create.php" class="btn">Add New Category</a>
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
                        placeholder="Search categories..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">🔍</button>
                </form>
            </div>

            <!-- Results Info -->
            <p class="text-center">Showing <?= count($categories) ?> of <?= $totalCategories ?> categories</p>

            <!-- Categories Table -->
            <div class="table-container">
                <table class="table" id="categoriesTable">
                    <thead>
                        <tr>
                            <th class="sortable">ID</th>
                            <th class="sortable">Name</th>
                            <th class="sortable">Description</th>
                            <th class="sortable">Courses</th>
                            <th class="sortable">Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <?php if (!empty($search)): ?>
                                        No categories found for "<?= htmlspecialchars($search) ?>"
                                    <?php else: ?>
                                        No categories found. <a href="create.php">Add the first category</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?= $category['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($category['name']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(substr($category['description'] ?? '', 0, 80)) ?>
                                        <?= strlen($category['description'] ?? '') > 80 ? '...' : '' ?>
                                    </td>
                                    <td>
                                        <span class="badge"><?= $category['course_count'] ?> courses</span>
                                        <?php if ($category['course_count'] > 0): ?>
                                            <br><small><a href="../courses/index.php?category=<?= $category['id'] ?>">View courses</a></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDate($category['created_at']) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="edit.php?id=<?= $category['id'] ?>"
                                                class="btn btn-primary btn-sm" title="Edit">✏️</a>
                                            <a href="delete.php?id=<?= $category['id'] ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirmDelete('Are you sure you want to delete this category? This will remove the category from all associated courses.')"
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
    <style>
        .badge {
            background: #74b9ff;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
    </style>
</body>

</html>