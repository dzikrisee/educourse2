<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_POST) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            setFlashMessage('Login successful! Welcome back, ' . $user['name']);

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduCourse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>

    <?php
    include 'partials/navbar.php'
    ?>

    <div class="login-container">
        <!-- Floating Background Shapes -->
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <div class="login-card fade-in">
            <div class="login-header">
                <h1>Welcome Back! 👋</h1>
                <p>Please sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <span class="form-icon">📧</span>
                    <input type="email" id="email" name="email" class="form-control"
                        placeholder="Enter your email address"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <span class="form-icon">🔒</span>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Enter your password" required>
                </div>

                <button type="submit" class="login-btn">
                    🚀 Sign In
                </button>
            </form>

            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Create one here</a></p>
            </div>

            <!-- Demo Accounts Info -->
            <div class="demo-accounts">
                <h4>🎯 Demo Accounts</h4>
                <div class="demo-account">
                    <span class="demo-label">Admin:</span>
                    <span class="demo-credentials">admin@educourse.com / admin123</span>
                </div>
                <div class="demo-account">
                    <span class="demo-label">User:</span>
                    <span class="demo-credentials">user@educourse.com / user123</span>
                </div>
            </div>
        </div>


    </div>

    <?php
    include 'partials/footer.php'
    ?>

    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading animation to form
            const form = document.getElementById('loginForm');
            const submitBtn = document.querySelector('.login-btn');

            form.addEventListener('submit', function() {
                submitBtn.innerHTML = '⏳ Signing In...';
                submitBtn.disabled = true;
            });

            // Add focus animations to form fields
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });

                control.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>

</html>