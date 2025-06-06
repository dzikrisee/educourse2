<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireAdmin();

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Get courses for filter dropdown
$coursesStmt = $pdo->query("SELECT id, title FROM courses ORDER BY title");
$courses = $coursesStmt->fetchAll();

// Build query for report data
$whereConditions = ["DATE(e.enrollment_date) BETWEEN ? AND ?"];
$params = [$start_date, $end_date];

if ($course_id > 0) {
    $whereConditions[] = "e.course_id = ?";
    $params[] = $course_id;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Get enrollment data
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
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// Calculate statistics
$total_enrollments = count($enrollments);
$total_revenue = array_sum(array_column($enrollments, 'course_price'));
$unique_students = count(array_unique(array_column($enrollments, 'user_id')));
$unique_courses = count(array_unique(array_column($enrollments, 'course_id')));

// Generate PDF if requested
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    // Simple PDF generation without external library
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="enrollment_report_' . date('Y-m-d') . '.pdf"');

    // Create a simple text-based report for PDF
    $pdfContent = "EduCourse Enrollment Report\n";
    $pdfContent .= "Generated on: " . date('Y-m-d H:i:s') . "\n";
    $pdfContent .= "Period: $start_date to $end_date\n\n";
    $pdfContent .= "SUMMARY\n";
    $pdfContent .= "Total Enrollments: $total_enrollments\n";
    $pdfContent .= "Total Revenue: " . formatCurrency($total_revenue) . "\n";
    $pdfContent .= "Unique Students: $unique_students\n";
    $pdfContent .= "Unique Courses: $unique_courses\n\n";
    $pdfContent .= "DETAILED ENROLLMENTS\n";

    foreach ($enrollments as $enrollment) {
        $pdfContent .= sprintf(
            "%s | %s | %s | %s | %s\n",
            $enrollment['enrollment_date'],
            $enrollment['user_name'],
            $enrollment['course_title'],
            formatCurrency($enrollment['course_price']),
            $enrollment['status']
        );
    }

    // For a simple implementation, we'll create a text file with PDF extension
    // In production, you'd use a proper PDF library like TCPDF or FPDF
    echo $pdfContent;
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Reports - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li><a href="index.php">Enrollments</a></li>
                <li><a href="report.php" class="active">Reports</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <h1>Enrollment Reports</h1>

            <!-- Filter Form -->
            <div class="card mb-2">
                <h3>Filter Report</h3>
                <form method="GET" class="d-flex gap-2 align-center">
                    <div>
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                            value="<?= htmlspecialchars($start_date) ?>">
                    </div>

                    <div>
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="<?= htmlspecialchars($end_date) ?>">
                    </div>

                    <div>
                        <label for="course_id">Course:</label>
                        <select id="course_id" name="course_id" class="form-control">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>" <?= $course_id == $course['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </form>
            </div>

            <!-- Summary Statistics -->
            <div class="card-grid mb-2">
                <div class="card text-center">
                    <h3><?= number_format($total_enrollments) ?></h3>
                    <p>Total Enrollments</p>
                </div>
                <div class="card text-center">
                    <h3><?= formatCurrency($total_revenue) ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="card text-center">
                    <h3><?= number_format($unique_students) ?></h3>
                    <p>Unique Students</p>
                </div>
                <div class="card text-center">
                    <h3><?= number_format($unique_courses) ?></h3>
                    <p>Courses Enrolled</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mb-2">
                <a href="?<?= http_build_query(array_merge($_GET, ['download' => 'pdf'])) ?>"
                    class="btn btn-primary">📄 Download PDF Report</a>
                <button onclick="window.print()" class="btn">🖨️ Print Report</button>
            </div>

            <!-- Enrollment Chart -->
            <?php if (!empty($enrollments)): ?>
                <div class="card mb-2">
                    <h3>Enrollment Trends</h3>
                    <canvas id="enrollmentChart" style="max-height: 400px;"></canvas>
                </div>
            <?php endif; ?>

            <!-- Detailed Table -->
            <div class="card">
                <h3>Detailed Enrollments (<?= $total_enrollments ?> records)</h3>

                <?php if (empty($enrollments)): ?>
                    <p class="text-center">No enrollments found for the selected period.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table" id="enrollmentsTable">
                            <thead>
                                <tr>
                                    <th class="sortable">Date</th>
                                    <th class="sortable">Student</th>
                                    <th class="sortable">Email</th>
                                    <th class="sortable">Course</th>
                                    <th class="sortable">Category</th>
                                    <th class="sortable">Price</th>
                                    <th class="sortable">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $enrollment): ?>
                                    <tr>
                                        <td><?= formatDate($enrollment['enrollment_date']) ?></td>
                                        <td><?= htmlspecialchars($enrollment['user_name']) ?></td>
                                        <td><?= htmlspecialchars($enrollment['user_email']) ?></td>
                                        <td><?= htmlspecialchars($enrollment['course_title']) ?></td>
                                        <td><?= htmlspecialchars($enrollment['category_name'] ?? 'Uncategorized') ?></td>
                                        <td><?= formatCurrency($enrollment['course_price']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $enrollment['status'] ?>">
                                                <?= ucfirst($enrollment['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../../assets/js/script.js"></script>

    <?php if (!empty($enrollments)): ?>
        <script>
            // Prepare chart data
            const enrollmentsByDate = {};
            <?php foreach ($enrollments as $enrollment): ?>
                const date = '<?= date('Y-m-d', strtotime($enrollment['enrollment_date'])) ?>';
                enrollmentsByDate[date] = (enrollmentsByDate[date] || 0) + 1;
            <?php endforeach; ?>

            const chartLabels = Object.keys(enrollmentsByDate).sort();
            const chartData = chartLabels.map(date => enrollmentsByDate[date]);

            // Create chart
            const ctx = document.getElementById('enrollmentChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Daily Enrollments',
                        data: chartData,
                        borderColor: '#74b9ff',
                        backgroundColor: 'rgba(116, 185, 255, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>

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

        @media print {

            .sidebar,
            .navbar,
            .btn {
                display: none !important;
            }

            .admin-layout {
                display: block !important;
            }

            .admin-content {
                padding: 0 !important;
            }
        }
    </style>
</body>

</html>