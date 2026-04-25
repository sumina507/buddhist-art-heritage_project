<?php
// footer.php - Reusable footer with BRIGHT THEME (same as navbar)
?>

<style>
/* ===== FOOTER STYLES - BRIGHT THEME (Same as Navbar) ===== */
footer {
    background: linear-gradient(135deg, #ffffff 0%, #fff8e7 100%);
    color: #2c3e50;
    padding: 3rem 0 1.5rem;
    border-top: 3px solid #f1c40f;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.footer-col h3, .footer-col h4 {
    margin-bottom: 1.5rem;
    color: #2c3e50;
}

.footer-col h3 i {
    margin-right: 10px;
    color: #e74c3c;
}

.footer-col p {
    color: #555;
    line-height: 1.6;
}

.footer-col ul {
    list-style: none;
}

.footer-col ul li {
    margin-bottom: 0.8rem;
}

.footer-col ul li a {
    color: #555;
    text-decoration: none;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.footer-col ul li a:hover {
    color: #e74c3c;
    transform: translateX(5px);
}

.footer-col ul li a i {
    color: #f1c40f;
}

.social-links {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.social-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(231, 76, 60, 0.1);
    border-radius: 50%;
    color: #e74c3c;
    text-decoration: none;
    transition: all 0.3s;
}

.social-links a:hover {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    transform: translateY(-3px);
}

.contact-info li {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1rem;
    color: #555;
}

.contact-info i {
    color: #e74c3c;
    width: 20px;
}

.footer-bottom {
    text-align: center;
    padding-top: 2rem;
    border-top: 1px solid rgba(231, 76, 60, 0.2);
    color: #777;
    font-size: 0.9rem;
}

.footer-bottom p {
    margin-bottom: 0.5rem;
}

.footer-bottom i {
    color: #e74c3c;
}

/* Mobile Responsive for Footer */
@media (max-width: 768px) {
    .footer-grid {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .footer-col ul li a {
        justify-content: center;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .contact-info li {
        justify-content: center;
    }
}
</style>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h3><i class="fas fa-lotus"></i> Buddhist Art Heritage</h3>
                <p>Preserving traditional Buddhist art forms through digital innovation and community collaboration.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="gallery.php"><i class="fas fa-chevron-right"></i> Gallery</a></li>
                    <li><a href="artists.php"><i class="fas fa-chevron-right"></i> Artists</a></li>
                    <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4>Art Categories</h4>
                <ul>
                    <li><a href="gallery.php?category=thangka"><i class="fas fa-chevron-right"></i> Thangka Paintings</a></li>
                    <li><a href="gallery.php?category=sculpture"><i class="fas fa-chevron-right"></i> Sculptures</a></li>
                    <li><a href="gallery.php?category=mandala"><i class="fas fa-chevron-right"></i> Mandalas</a></li>
                    <li><a href="gallery.php?category=painting"><i class="fas fa-chevron-right"></i> Traditional Paintings</a></li>
                    <li><a href="gallery.php?category=other"><i class="fas fa-chevron-right"></i> Other Art Forms</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4>Contact Info</h4>
                <ul class="contact-info">
                    <li><i class="fas fa-map-marker-alt"></i> Banepa-8, Kavre, Nepal</li>
                    <li><i class="fas fa-envelope"></i> info@buddhistart.edu</li>
                    <li><i class="fas fa-phone"></i> +977 9876543210</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Buddhist Art Heritage. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="back-to-top">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- JavaScript -->
<script src="js/main.js"></script>
<?php if (isset($additional_js)): ?>
    <script src="<?php echo $additional_js; ?>"></script>
<?php endif; ?>
</body>
</html>