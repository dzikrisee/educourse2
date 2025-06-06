<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$course_filter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Get courses and users for filters
$coursesStmt = $pdo->query("SELECT id, title FROM courses ORDER BY title");
$courses = $coursesStmt->fetchAll();

// Build query with search and filters
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(u.name LIKE :search OR u.email LIKE :search2 OR c.title LIKE :search3)";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
    $params['search3'] = "%$search%";
}

if (!empty($status_filter)) {
    $whereConditions[] = "e.status = :status";
    $params['status'] = $status_filter;
}

if ($course_filter > 0) {
    $whereConditions[] = "e.course_id = :course_id";
    $params['course_id'] = $course_filter;
}

if ($user_filter > 0) {
    $whereConditions[] = "e.user_id = :user_id";
    $params['user_id'] = $user_filter;
}

$whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

// Get total count for pagination
$countSql = "
    SELECT COUNT(*) 
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    $whereClause
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalEnrollments = $countStmt->fetchColumn();

// Get enrollments with pagination
$sql = "
    SELECT e.*, u.name as user_name, u.email as user_email,
           c.title as course_title, c.price as course_price,
           cat.name as category_name
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    $whereClause
    ORDER BY e.enrollment_date DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// Pagination data
$pagination = getPagination($totalEnrollments, $page, $perPage);

// Handle status update
if ($_POST && isset($_POST['update_status'])) {
    $enrollment_id = (int)$_POST['enrollment_id'];
    $new_status = sanitize($_POST['new_status']);

    if (in_array($new_status, ['active', 'completed', 'cancelled'])) {
        $stmt = $pdo->prepare("UPDATE enrollments SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $enrollment_id])) {
            setFlashMessage('Enrollment status updated successfully!');
        } else {
            setFlashMessage('Failed to update status', 'error');
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Enrollments - Admin</title>
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
                <li><a href="../categories/index.php">Categories</a></li>
                <li><a href="../users/index.php">Users</a></li>
                <li><a href="index.php" class="active">Enrollments</a></li>
                <li><a href="report.php">Reports</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="d-flex justify-between align-center mb-2">
                <h1>Manage Enrollments</h1>
                <a href="report.php" class="btn btn-primary">📊 View Reports</a>
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

            <!-- Search and Filter -->
            <div class="card mb-2">
                <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                    <div>
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" class="form-control"
                            placeholder="Student name, email, or course..." value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div>
                        <label for="course_id">Course:</label>
                        <select name="course_id" id="course_id" class="form-control">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>" <?= $course_filter == $course['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="status">Status:</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>

                    <?php if (!empty($search) || !empty($status_filter) || $course_filter > 0): ?>
                        <div>
                            <a href="index.php" class="btn">Clear</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Results Info -->
            <p class="text-center">Showing <?= count($enrollments) ?> of <?= $totalEnrollments ?> enrollments</p>

            <!-- Enrollments Table -->
            <div class="table-container">
                <table class="table" id="enrollmentsTable">
                    <thead>
                        <tr>
                            <th class="sortable">ID</th>
                            <th class="sortable">Student</th>
                            <th class="sortable">Course</th>
                            <th class="sortable">Category</th>
                            <th class="sortable">Price</th>
                            <th class="sortable">Status</th>
                            <th class="sortable">Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enrollments)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <?php if (!empty($search) || !empty($status_filter) || $course_filter > 0): ?>
                                        No enrollments found with current filters
                                    <?php else: ?>
                                        No enrollments found
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr>
                                    <td><?= $enrollment['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($enrollment['user_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($enrollment['user_email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($enrollment['course_title']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($enrollment['category_name'] ?? 'Uncategorized') ?></td>
                                    <td><?= formatCurrency($enrollment['course_price']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="enrollment_id" value="<?= $enrollment['id'] ?>">
                                            <select name="new_status" class="form-control" style="width: auto; display: inline; font-size: 0.8rem;"
                                                onchange="if(confirm('Update status?')) this.form.submit();">
                                                <option value="active" <?= $enrollment['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="completed" <?= $enrollment['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $enrollment['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td><?= formatDate($enrollment['enrollment_date']) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="../../course-detail.php?id=<?= $enrollment['course_id'] ?>"
                                                class="btn btn-sm" title="View Course">👁️</a>
                                            <a href="../users/edit.php?id=<?= $enrollment['user_id'] ?>"
                                                class="btn btn-primary btn-sm" title="Edit User">👤</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Statistics Summary -->
            <?php if (!empty($enrollments)): ?>
                <div class="card-grid mt-2">
                    <?php
                    $active_count = count(array_filter($enrollments, fn($e) => $e['status'] === 'active'));
                    $completed_count = count(array_filter($enrollments, fn($e) => $e['status'] === 'completed'));
                    $cancelled_count = count(array_filter($enrollments, fn($e) => $e['status'] === 'cancelled'));
                    $total_revenue = array_sum(array_column($enrollments, 'course_price'));
                    ?>
                    <div class="card text-center">
                        <h3><?= $active_count ?></h3>
                        <p>Active</p>
                    </div>
                    <div class="card text-center">
                        <h3><?= $completed_count ?></h3>
                        <p>Completed</p>
                    </div>
                    <div class="card text-center">
                        <h3><?= $cancelled_count ?></h3>
                        <p>Cancelled</p>
                    </div>
                    <div class="card text-center">
                        <h3><?= formatCurrency($total_revenue) ?></h3>
                        <p>Revenue (Current Page)</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php
                    $query_params = http_build_query([
                        'search' => $search,
                        'status' => $status_filter,
                        'course_id' => $course_filter,
                        'user_id' => $user_filter
                    ]);
                    ?>

                    <?php if ($pagination['has_prev']): ?>
                        <a href="?page=<?= $pagination['current_page'] - 1 ?>&<?= $query_params ?>">
                            « Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <?php if ($i == $pagination['current_page']): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&<?= $query_params ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?= $pagination['current_page'] + 1 ?>&<?= $query_params ?>">
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
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: white;
        }

        .badge-active {
            background: #00b894;
        }

        .badge-completed {
            background: #74b9ff;
        }

        .badge-cancelled {
            background: #fd79a8;
        }
    </style>
</body>

</html>