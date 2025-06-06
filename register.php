<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_POST) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email address is already registered';
        } else {
            // Handle photo upload
            $photoName = 'default.jpg';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['photo'], 'assets/uploads/users/');
                if ($uploadResult['success']) {
                    $photoName = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['message'];
                }
            }
            
            if (empty($error)) {
                // Insert new user
                $hashedPassword = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, photo) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$name, $email, $hashedPassword, $photoName])) {
                    $success = 'Registration successful! You can now login.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EduCourse</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">EduCourse</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-2">Create Your Account</h2>
            
            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="flash-message flash-success">
                    <?= htmlspecialchars($success) ?>
                    <div class="mt-1">
                        <a href="login.php" class="btn btn-primary btn-sm">Login Now</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="registerForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           minlength="6" required>
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="photo">Profile Photo (Optional)</label>
                    <input type="file" id="photo" name="photo" class="form-control" 
                           accept="image/*" data-preview="photo-preview">
                    <img id="photo-preview" style="display:none; width:100px; height:100px; 
                         object-fit:cover; margin-top:10px; border-radius:50%;">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
            </form>
            
            <div class="text-center mt-2">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>