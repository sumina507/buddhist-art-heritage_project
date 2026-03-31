<?php
// footer.php - Reusable footer
?>
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
                        <li><a href="knowledge.php"><i class="fas fa-chevron-right"></i> Knowledge Base</a></li>
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
                        <li><i class="fas fa-map-marker-alt"></i> Banepa-8,Kavre,Nepal</li>
                        <li><i class="fas fa-envelope"></i> info@buddhistart.edu</li>
                        <li><i class="fas fa-phone"></i> +977 9876543210</li>
                        <li><i class="fas fa-user-graduate"></i>2026</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Buddhist Art Heritage Portal. All rights reserved. </p>
                <p>Developed with <i class="fas fa-heart" style="color: #e74c3c;"></i> for preserving cultural heritage</p>
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