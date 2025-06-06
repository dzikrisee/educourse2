<?php
// partials/navbar.php
?>
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="<?= SITE_URL ?>/index.php" class="logo">EduCourse</a>
        <div class="nav-toggle" id="nav-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-links" id="nav-links">
            <li><a href="<?= SITE_URL ?>/index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Home
                </a></li>
            <li><a href="<?= SITE_URL ?>/courses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : '' ?>">
                    <i class="fas fa-book"></i> Courses
                </a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="<?= SITE_URL ?>/profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
                        <i class="fas fa-user"></i> Profile
                    </a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="<?= SITE_URL ?>/admin/index.php" class="admin-link">
                            <i class="fas fa-cog"></i> Admin
                        </a></li>
                <?php endif; ?>
                <li><a href="<?= SITE_URL ?>/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a></li>
            <?php else: ?>
                <li><a href="<?= SITE_URL ?>/login.php" class="<?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a></li>
                <li><a href="<?= SITE_URL ?>/register.php" class="<?= basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-plus"></i> Register
                    </a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<style>
    /* Enhanced Navbar Styles */
    .navbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .navbar.scrolled {
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: transform 0.3s ease;
    }

    .logo::before {
        content: "🎓";
        font-size: 1.8rem;
    }

    .logo:hover {
        transform: scale(1.05);
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 1.5rem;
        align-items: center;
        margin: 0;
        padding: 0;
    }

    .nav-links a {
        color: #333;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        padding: 0.8rem 1.2rem;
        border-radius: 25px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
    }

    .nav-links a:hover,
    .nav-links a.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .nav-links a.admin-link:hover {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
    }

    .nav-links a.logout-link:hover {
        background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
        box-shadow: 0 8px 25px rgba(252, 70, 107, 0.3);
    }

    /* Mobile Navigation Toggle */
    .nav-toggle {
        display: none;
        flex-direction: column;
        cursor: pointer;
        gap: 4px;
    }

    .nav-toggle span {
        width: 25px;
        height: 3px;
        background: #667eea;
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .nav-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }

    .nav-toggle.active span:nth-child(2) {
        opacity: 0;
    }

    .nav-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .nav-container {
            padding: 1rem;
        }

        .nav-toggle {
            display: flex;
        }

        .nav-links {
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            height: calc(100vh - 80px);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-top: 2rem;
            gap: 1rem;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .nav-links.active {
            transform: translateX(0);
        }

        .nav-links a {
            width: 80%;
            justify-content: center;
            padding: 1rem;
            font-size: 1.1rem;
        }

        .logo {
            font-size: 1.6rem;
        }
    }

    @media (max-width: 480px) {
        .nav-container {
            padding: 0.8rem;
        }

        .logo {
            font-size: 1.4rem;
        }

        .nav-links a {
            width: 90%;
            font-size: 1rem;
        }
    }
</style>

<script>
    // Mobile Navigation Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const navToggle = document.getElementById('nav-toggle');
        const navLinks = document.getElementById('nav-links');
        const navbar = document.getElementById('navbar');

        // Mobile menu toggle
        if (navToggle) {
            navToggle.addEventListener('click', function() {
                navToggle.classList.toggle('active');
                navLinks.classList.toggle('active');
            });
        }

        // Close mobile menu when clicking on link
        const navLinkItems = navLinks.querySelectorAll('a');
        navLinkItems.forEach(link => {
            link.addEventListener('click', function() {
                navToggle.classList.remove('active');
                navLinks.classList.remove('active');
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navbar.contains(e.target)) {
                navToggle.classList.remove('active');
                navLinks.classList.remove('active');
            }
        });
    });
</script>