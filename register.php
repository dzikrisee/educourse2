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
    <link rel="stylesheet" href="assets/css/register.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <?php
    include 'partials/navbar.php'
    ?>

    <div class="register-container">
        <!-- Floating Background Shapes -->
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <div class="register-card fade-in">
            <div class="register-header">
                <h1>Join EduCourse! 🎓</h1>
                <p>Create your account and start learning</p>
            </div>

            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="flash-message flash-success">
                    ✅ <?= htmlspecialchars($success) ?>
                    <div class="success-actions">
                        <a href="login.php" class="btn btn-primary">🚀 Login Now</a>
                        <a href="courses.php" class="btn btn-secondary">📚 Browse Courses</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" class="register-form" id="registerForm">
                    <div class="form-group">
                        <span class="form-icon">👤</span>
                        <input type="text" id="name" name="name" class="form-control"
                            placeholder="Enter your full name"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <span class="form-icon">📧</span>
                        <input type="email" id="email" name="email" class="form-control"
                            placeholder="Enter your email address"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group-row">
                        <div class="form-group">
                            <span class="form-icon">🔒</span>
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Create password" minlength="6" required>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <span class="form-icon">🔐</span>
                            <input type="password" id="confirm_password" name="confirm_password"
                                class="form-control" placeholder="Confirm password" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <span class="form-icon">📷</span>
                        <input type="file" id="photo" name="photo" class="form-control"
                            accept="image/*" data-preview="photo-preview">
                        <div class="photo-preview">
                            <img id="photo-preview" style="display:none;" alt="Photo preview">
                        </div>
                    </div>

                    <button type="submit" class="register-btn" id="submitBtn">
                        🚀 Create Account
                    </button>
                </form>
            <?php endif; ?>

            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <?php
    include 'partials/footer.php'
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrengthBar = document.getElementById('passwordStrengthBar');
            const photoInput = document.getElementById('photo');
            const photoPreview = document.getElementById('photo-preview');

            // Password strength indicator
            if (passwordInput && passwordStrengthBar) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;

                    if (password.length >= 6) strength++;
                    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                    if (password.match(/[0-9]/)) strength++;
                    if (password.match(/[^a-zA-Z0-9]/)) strength++;

                    passwordStrengthBar.className = 'password-strength-bar';

                    if (strength >= 4) {
                        passwordStrengthBar.classList.add('strength-strong');
                    } else if (strength >= 2) {
                        passwordStrengthBar.classList.add('strength-medium');
                    } else if (strength >= 1) {
                        passwordStrengthBar.classList.add('strength-weak');
                    }
                });
            }
        });
    </script>
</body>

</html>