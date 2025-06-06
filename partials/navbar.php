<!-- Enhanced Header -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">EduCourse</a>
        <ul class="nav-links">
            <li><a href="index.php" class="active">Home</a></li>
            <li><a href="courses.php">Courses</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="admin/index.php"><i class="fas fa-cog"></i> Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            <?php else: ?>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>


<style>
    /* Modern Education Theme CSS */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        --dark-bg: #1a1a2e;
        --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        --hover-transform: translateY(-10px);
    }

    body {
        font-family: 'Inter', 'Segoe UI', sans-serif;
        overflow-x: hidden;
    }

    /* Enhanced Navbar */
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
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .logo::before {
        content: "🎓";
        font-size: 1.8rem;
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 2rem;
        align-items: center;
    }

    .nav-links a {
        color: #333;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        padding: 0.5rem 1rem;
        border-radius: 25px;
    }

    .nav-links a:hover,
    .nav-links a.active {
        background: var(--primary-gradient);
        color: white;
        transform: translateY(-2px);
    }
</style>

<script>
    // Enhanced JavaScript for better UX
    document.addEventListener('DOMContentLoaded', function() {
        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Fade in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = Math.random() * 0.3 + 's';
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.stat-card, .course-card, .feature-card').forEach(el => {
            observer.observe(el);
        });

        // Auto-hide flash messages
        const flashMessage = document.querySelector('.flash-message');
        if (flashMessage) {
            setTimeout(() => {
                flashMessage.style.transform = 'translateX(100%)';
                setTimeout(() => flashMessage.remove(), 300);
            }, 5000);
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
</script>