<!-- Modern Footer -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-container">
            <!-- Footer Main Content -->
            <div class="footer-grid">
                <!-- Brand Section -->
                <div class="footer-section">
                    <div class="footer-brand">
                        <h3 class="footer-logo">🎓 EduCourse</h3>
                        <p class="footer-description">
                            Empowering learners worldwide with high-quality online education.
                            Join thousands of students in their journey to success.
                        </p>
                        <div class="footer-stats">
                            <div class="stat-item">
                                <span class="stat-number">1000+</span>
                                <span class="stat-label">Students</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">50+</span>
                                <span class="stat-label">Courses</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">15+</span>
                                <span class="stat-label">Instructors</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-section">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="courses.php"><i class="fas fa-book"></i> All Courses</a></li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="footer-section">
                    <h4 class="footer-title">Popular Categories</h4>
                    <ul class="footer-links">
                        <li><a href="courses.php?category=1"><i class="fas fa-code"></i> Programming</a></li>
                        <li><a href="courses.php?category=2"><i class="fas fa-palette"></i> Design</a></li>
                        <li><a href="courses.php?category=3"><i class="fas fa-chart-line"></i> Business</a></li>
                        <li><a href="courses.php?category=4"><i class="fas fa-language"></i> Languages</a></li>
                        <li><a href="courses.php?category=5"><i class="fas fa-laptop"></i> Technology</a></li>
                    </ul>
                </div>

                <!-- Contact & Social -->
                <div class="footer-section">
                    <h4 class="footer-title">Connect With Us</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>info@educourse.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+62 123 456 789</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Bandung, West Java, Indonesia</span>
                        </div>
                    </div>

                    <div class="social-links">
                        <a href="#" class="social-link facebook" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link twitter" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link instagram" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link linkedin" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link youtube" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="footer-container">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; 2025 EduCourse. All rights reserved. Made with ❤️ for education.</p>
                </div>
                <div class="footer-bottom-links">
                    <a href="#" class="footer-link">Privacy Policy</a>
                    <a href="#" class="footer-link">Terms of Service</a>
                    <a href="#" class="footer-link">Support</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Modern Footer Styles */
    .footer {
        background: linear-gradient(135deg, #2d3436 0%, #636e72 100%);
        color: white;
        margin-top: auto;
    }

    .footer-content {
        padding: 3rem 0 1rem;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr;
        gap: 3rem;
        margin-bottom: 2rem;
    }

    .footer-section {
        display: flex;
        flex-direction: column;
    }

    /* Brand Section */
    .footer-brand {
        max-width: 350px;
    }

    .footer-logo {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .footer-description {
        font-size: 1rem;
        line-height: 1.6;
        color: #ddd;
        margin-bottom: 1.5rem;
    }

    .footer-stats {
        display: flex;
        gap: 1.5rem;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
        color: #74b9ff;
        margin-bottom: 0.2rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #bbb;
    }

    /* Footer Titles */
    .footer-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: #74b9ff;
        position: relative;
    }

    .footer-title::after {
        content: '';
        position: absolute;
        bottom: -0.5rem;
        left: 0;
        width: 30px;
        height: 2px;
        background: linear-gradient(90deg, #74b9ff 0%, #0984e3 100%);
        border-radius: 1px;
    }

    /* Footer Links */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 0.8rem;
    }

    .footer-links a {
        color: #ddd;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .footer-links a:hover {
        color: #74b9ff;
        transform: translateX(5px);
    }

    .footer-links a i {
        width: 16px;
        text-align: center;
        opacity: 0.7;
    }

    /* Contact Info */
    .contact-info {
        margin-bottom: 1.5rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 0.8rem;
        color: #ddd;
        font-size: 0.95rem;
    }

    .contact-item i {
        width: 16px;
        text-align: center;
        color: #74b9ff;
    }

    /* Social Links */
    .social-links {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 1.1rem;
    }

    .social-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .social-link.facebook {
        background: linear-gradient(135deg, #3b5998 0%, #8b9dc3 100%);
    }

    .social-link.twitter {
        background: linear-gradient(135deg, #1da1f2 0%, #0d8bd9 100%);
    }

    .social-link.instagram {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .social-link.linkedin {
        background: linear-gradient(135deg, #0077b5 0%, #00a0dc 100%);
    }

    .social-link.youtube {
        background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%);
    }

    /* Footer Bottom */
    .footer-bottom {
        background: rgba(0, 0, 0, 0.2);
        padding: 1.5rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .footer-bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
    }

    .copyright {
        color: #bbb;
        font-size: 0.9rem;
    }

    .footer-bottom-links {
        display: flex;
        gap: 2rem;
    }

    .footer-link {
        color: #ddd;
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }

    .footer-link:hover {
        color: #74b9ff;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .footer-stats {
            justify-content: space-between;
        }
    }

    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 2rem;
            text-align: center;
        }

        .footer-brand {
            max-width: 100%;
        }

        .footer-stats {
            justify-content: center;
            gap: 2rem;
        }

        .footer-bottom-content {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .footer-bottom-links {
            justify-content: center;
        }

        .social-links {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .footer-container {
            padding: 0 1rem;
        }

        .footer-content {
            padding: 2rem 0 1rem;
        }

        .footer-bottom-links {
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-stats {
            gap: 1rem;
        }

        .stat-number {
            font-size: 1.2rem;
        }
    }

    /* Scroll to Top Button */
    .scroll-top {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 5px 15px rgba(116, 185, 255, 0.3);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .scroll-top:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(116, 185, 255, 0.4);
    }

    .scroll-top.show {
        display: flex;
    }
</style>

<!-- Scroll to Top Button -->
<button class="scroll-top" id="scrollTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
    // Scroll to Top Functionality
    window.addEventListener('scroll', function() {
        const scrollTop = document.getElementById('scrollTop');
        if (window.pageYOffset > 300) {
            scrollTop.classList.add('show');
        } else {
            scrollTop.classList.remove('show');
        }
    });

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Add smooth scroll to all footer links
    document.querySelectorAll('.footer-links a[href^="#"]').forEach(anchor => {
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
</script>