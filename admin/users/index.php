<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query with search and filters
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(name LIKE :search OR email LIKE :search2)";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
}

if (!empty($role_filter)) {
    $whereConditions[] = "role = :role";
    $params['role'] = $role_filter;
}

$whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM users $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();

// Get users with pagination and enrollment count
$sql = "
    SELECT u.*, COUNT(e.id) as enrollment_count
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.user_id
    $whereClause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Pagination data
$pagination = getPagination($totalUsers, $page, $perPage);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
                <li><a href="index.php" class="active">Users</a></li>
                <li><a href="../enrollments/index.php">Enrollments</a></li>
                <li><a href="../enrollments/report.php">Reports</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="d-flex justify-between align-center mb-2">
                <h1>Manage Users</h1>
                <a href="create.php" class="btn">Add New User</a>
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
                <form method="GET" class="d-flex gap-2 align-center">
                    <div class="search-box" style="flex: 1;">
                        <input type="text" name="search" class="search-input"
                            placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="search-btn">🔍</button>
                    </div>

                    <select name="role" class="form-control" style="width: 150px;">
                        <option value="">All Roles</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>User</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>

                    <?php if (!empty($search) || !empty($role_filter)): ?>
                        <a href="index.php" class="btn">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Results Info -->
            <p class="text-center">Showing <?= count($users) ?> of <?= $totalUsers ?> users</p>

            <!-- Users Table -->
            <div class="table-container">
                <table class="table" id="usersTable">
                    <thead>
                        <tr>
                            <th class="sortable">ID</th>
                            <th class="sortable">Photo</th>
                            <th class="sortable">Name</th>
                            <th class="sortable">Email</th>
                            <th class="sortable">Role</th>
                            <th class="sortable">Enrollments</th>
                            <th class="sortable">Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <?php if (!empty($search)): ?>
                                        No users found for "<?= htmlspecialchars($search) ?>"
                                    <?php else: ?>
                                        No users found. <a href="create.php">Add the first user</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <img src="../../assets/uploads/users/<?= htmlspecialchars($user['photo']) ?>"
                                            alt="User Photo" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;"
                                            onerror="this.src='../../assets/uploads/users/default.jpg'">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $user['role'] ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['enrollment_count'] > 0): ?>
                                            <a href="../enrollments/index.php?user_id=<?= $user['id'] ?>">
                                                <?= $user['enrollment_count'] ?> courses
                                            </a>
                                        <?php else: ?>
                                            0 courses
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDate($user['created_at']) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="edit.php?id=<?= $user['id'] ?>"
                                                class="btn btn-primary btn-sm" title="Edit">✏️</a>

                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="delete.php?id=<?= $user['id'] ?>"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirmDelete('Are you sure you want to delete this user?')"
                                                    title="Delete">🗑️</a>
                                            <?php endif; ?>
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
                        <a href="?page=<?= $pagination['current_page'] - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>">
                            « Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <?php if ($i == $pagination['current_page']): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?= $pagination['current_page'] + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>">
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

        .badge-admin {
            background: #e74c3c;
        }

        .badge-user {
            background: #74b9ff;
        }
    </style>
</body>

</html>