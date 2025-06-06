<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if ($_POST && isset($_POST['search'])) {
    $search = sanitize($_POST['search']);
    
    if (strlen($search) >= 2) {
        // Search courses
        $sql = "
            SELECT c.*, cat.name as category_name,
                   COUNT(e.id) as enrollment_count
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE (c.title LIKE :search OR c.description LIKE :search2 OR c.instructor LIKE :search3)
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT 12
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'search' => "%$search%",
            'search2' => "%$search%",
            'search3' => "%$search%"
        ]);
        $courses = $stmt->fetchAll();
        
        if (empty($courses)) {
            echo '<div class="text-center"><p>No courses found for "' . htmlspecialchars($search) . '"</p></div>';
        } else {
            echo '<div class="card-grid">';
            foreach ($courses as $course) {
                echo '
                <div class="card course-card" data-category="' . $course['category_id'] . '">
                    <img src="assets/uploads/courses/' . htmlspecialchars($course['image']) . '" 
                         alt="' . htmlspecialchars($course['title']) . '" 
                         class="card-img"
                         onerror="this.src=\'assets/uploads/courses/default-course.jpg\'">
                    
                    <div class="card-content">
                        <h3>' . htmlspecialchars($course['title']) . '</h3>
                        <p>' . htmlspecialchars(substr($course['description'], 0, 120)) . '...</p>
                        
                        <div class="course-info mb-1">
                            <small><strong>Instructor:</strong> ' . htmlspecialchars($course['instructor']) . '</small><br>
                            <small><strong>Category:</strong> ' . htmlspecialchars($course['category_name'] ?? 'Uncategorized') . '</small><br>
                            <small><strong>Duration:</strong> ' . htmlspecialchars($course['duration']) . '</small>
                        </div>
                        
                        <div class="course-meta">
                            <span class="price">' . formatCurrency($course['price']) . '</span>
                            <span>' . $course['enrollment_count'] . ' students</span>
                        </div>
                        
                        <div class="mt-2">
                            <a href="course-detail.php?id=' . $course['id'] . '" 
                               class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>';
            }
            echo '</div>';
        }
    }
}
?>