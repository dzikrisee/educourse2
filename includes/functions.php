<?php
// Sanitize input
function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)));
}

// Validate email
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash password
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

// Upload file
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'])
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file parameters'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed with error code ' . $file['error']];
    }

    if ($file['size'] > 5000000) { // 5MB
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }

    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);

    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }

    $fileName = uniqid() . '.' . $extension;
    $targetPath = $targetDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    return ['success' => true, 'filename' => $fileName];
}

// Format currency
function formatCurrency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Format date
function formatDate($date)
{
    return date('d M Y', strtotime($date));
}

// Get pagination data
function getPagination($totalRecords, $currentPage = 1, $recordsPerPage = 10)
{
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $offset = ($currentPage - 1) * $recordsPerPage;

    return [
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'limit' => $recordsPerPage,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Search function
function searchData($pdo, $table, $searchFields, $searchTerm, $additionalWhere = '')
{
    $conditions = [];
    $params = [];

    foreach ($searchFields as $field) {
        $conditions[] = "$field LIKE :search_$field";
        $params["search_$field"] = "%$searchTerm%";
    }

    $whereClause = '(' . implode(' OR ', $conditions) . ')';
    if ($additionalWhere) {
        $whereClause .= " AND $additionalWhere";
    }

    $sql = "SELECT * FROM $table WHERE $whereClause ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// Enhanced search function with advanced filtering
function advancedSearchData($pdo, $table, $searchFields, $searchTerm, $filters = [], $sortBy = 'id', $sortOrder = 'DESC')
{
    $conditions = [];
    $params = [];

    // Add search conditions
    if (!empty($searchTerm)) {
        $searchConditions = [];
        foreach ($searchFields as $field) {
            $searchConditions[] = "$field LIKE :search_$field";
            $params["search_$field"] = "%$searchTerm%";
        }
        $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
    }

    // Add filter conditions
    foreach ($filters as $field => $value) {
        if (!empty($value)) {
            $conditions[] = "$field = :filter_$field";
            $params["filter_$field"] = $value;
        }
    }

    $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $orderClause = "ORDER BY $sortBy $sortOrder";

    $sql = "SELECT * FROM $table $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// Enhanced pagination with more info
function getAdvancedPagination($totalRecords, $currentPage = 1, $recordsPerPage = 10)
{
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $offset = ($currentPage - 1) * $recordsPerPage;

    $start = $offset + 1;
    $end = min($offset + $recordsPerPage, $totalRecords);

    return [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'records_per_page' => $recordsPerPage,
        'offset' => $offset,
        'limit' => $recordsPerPage,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'start_record' => $start,
        'end_record' => $end,
        'showing_text' => "Showing $start to $end of $totalRecords entries"
    ];
}

// Enhanced file upload with multiple validations
function enhancedUploadFile($file, $targetDir, $options = [])
{
    $defaultOptions = [
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'max_size' => 5000000, // 5MB
        'min_width' => 0,
        'max_width' => 0,
        'min_height' => 0,
        'max_height' => 0,
        'maintain_aspect' => false
    ];

    $options = array_merge($defaultOptions, $options);

    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file parameters'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed with error code ' . $file['error']];
    }

    if ($file['size'] > $options['max_size']) {
        $maxSizeMB = round($options['max_size'] / 1000000, 1);
        return ['success' => false, 'message' => "File size exceeds {$maxSizeMB}MB limit"];
    }

    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);

    if (!in_array($extension, $options['allowed_types'])) {
        $allowedStr = implode(', ', $options['allowed_types']);
        return ['success' => false, 'message' => "File type not allowed. Allowed: $allowedStr"];
    }

    // Check image dimensions if specified
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            if ($options['min_width'] > 0 && $width < $options['min_width']) {
                return ['success' => false, 'message' => "Image width must be at least {$options['min_width']}px"];
            }

            if ($options['max_width'] > 0 && $width > $options['max_width']) {
                return ['success' => false, 'message' => "Image width must not exceed {$options['max_width']}px"];
            }

            if ($options['min_height'] > 0 && $height < $options['min_height']) {
                return ['success' => false, 'message' => "Image height must be at least {$options['min_height']}px"];
            }

            if ($options['max_height'] > 0 && $height > $options['max_height']) {
                return ['success' => false, 'message' => "Image height must not exceed {$options['max_height']}px"];
            }
        }
    }

    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    return ['success' => true, 'filename' => $fileName, 'path' => $targetPath];
}

// Generate CSV export
function generateCSV($headers, $data, $filename = null)
{
    if ($filename === null) {
        $filename = 'export_' . date('Y-m-d_H-i-s') . '.csv';
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Write headers
    fputcsv($output, $headers);

    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// Enhanced logging function
function logActivity($pdo, $user_id, $action, $details = '', $ip_address = null)
{
    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $action, $details, $ip_address]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

// Data validation helpers
function validateRequired($fields, $data)
{
    $errors = [];
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $errors[] = ucfirst($field) . " is required";
        }
    }
    return $errors;
}

function validateNumeric($fields, $data)
{
    $errors = [];
    foreach ($fields as $field) {
        if (isset($data[$field]) && !is_numeric($data[$field])) {
            $errors[] = ucfirst($field) . " must be a number";
        }
    }
    return $errors;
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateURL($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// Security helpers
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function rateLimit($key, $limit = 10, $window = 3600)
{
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }

    $now = time();
    $windowStart = $now - $window;

    // Clean old entries
    if (isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = array_filter(
            $_SESSION['rate_limit'][$key],
            function ($timestamp) use ($windowStart) {
                return $timestamp > $windowStart;
            }
        );
    } else {
        $_SESSION['rate_limit'][$key] = [];
    }

    // Check limit
    if (count($_SESSION['rate_limit'][$key]) >= $limit) {
        return false;
    }

    // Add current request
    $_SESSION['rate_limit'][$key][] = $now;
    return true;
}

// Cache helpers
function setCache($key, $data, $ttl = 3600)
{
    $cacheData = [
        'data' => $data,
        'expires' => time() + $ttl
    ];
    $_SESSION['cache'][$key] = $cacheData;
}

function getCache($key)
{
    if (!isset($_SESSION['cache'][$key])) {
        return null;
    }

    $cacheData = $_SESSION['cache'][$key];
    if (time() > $cacheData['expires']) {
        unset($_SESSION['cache'][$key]);
        return null;
    }

    return $cacheData['data'];
}

function clearCache($key = null)
{
    if ($key === null) {
        $_SESSION['cache'] = [];
    } else {
        unset($_SESSION['cache'][$key]);
    }
}

// Flash message functions
function setFlashMessage($message, $type = 'success')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}
