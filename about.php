<?php
// about.php - About the project
require_once 'includes/config.php';
$page_title = "About Us - Buddhist Art Heritage";
require_once 'includes/navbar.php';
?>

<style>
body {
            background: linear-gradient(135deg, #f9f7f1 0%, #f5f5f0 100%);
    font-family: 'Inter', sans-serif;
}

.about-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* Hero Section */
.about-hero {
    text-align: center;
    margin-bottom: 3rem;
}

.about-hero h1 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.about-hero h1 i {
    color: #e74c3c;
    margin-right: 10px;
}

.about-hero .subtitle {
    font-size: 1.1rem;
    color: #6c757d;
    max-width: 600px;
    margin: 0 auto;
}

/* Mission Section */
.mission-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-bottom: 3rem;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.mission-content {
    padding: 2rem;
}

.mission-content h2 {
    color: #2c3e50;
    font-size: 1.8rem;
    margin-bottom: 1rem;
}

.mission-content h2 i {
    color: #e74c3c;
    margin-right: 10px;
}

.mission-content p {
    color: #6c757d;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.mission-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    text-align: center;
    margin-top: 1.5rem;
}

.stat {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 12px;
}

.stat i {
    font-size: 2rem;
    color: #e74c3c;
    margin-bottom: 0.5rem;
}

.stat h3 {
    font-size: 1.5rem;
    color: #2c3e50;
    margin-bottom: 0.2rem;
}

.stat p {
    color: #6c757d;
    font-size: 0.8rem;
    margin: 0;
}

.mission-image {
    background: linear-gradient(135deg, #2c3e50, #1a252f);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.mission-image img {
    max-width: 100%;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

/* Story Section */
.story-section {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 3rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.story-section h2 {
    text-align: center;
    color: #2c3e50;
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
}

.story-section h2 i {
    color: #e74c3c;
    margin-right: 10px;
}

.story-content {
    max-width: 800px;
    margin: 0 auto;
}

.story-content p {
    color: #6c757d;
    line-height: 1.7;
    margin-bottom: 1.2rem;
}

.father-quote {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    margin: 1.5rem 0;
    border-left: 4px solid #e74c3c;
}

.father-quote i {
    font-size: 2rem;
    color: #e74c3c;
    opacity: 0.5;
    margin-bottom: 0.5rem;
    display: block;
}

.father-quote blockquote {
    font-size: 1.1rem;
    font-style: italic;
    color: #2c3e50;
    margin: 0 0 0.5rem 0;
}

.father-quote cite {
    color: #6c757d;
    font-style: normal;
    font-size: 0.9rem;
}

/* Features Section */
.features-showcase {
    margin-bottom: 3rem;
}

.features-showcase h2 {
    text-align: center;
    color: #2c3e50;
    font-size: 1.8rem;
    margin-bottom: 2rem;
}

.features-showcase h2 i {
    color: #e74c3c;
    margin-right: 10px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

.feature-item {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    text-align: center;
    transition: all 0.3s;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.feature-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.feature-icon {
    width: 70px;
    height: 70px;
    background: #fef5f4;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.feature-icon i {
    font-size: 1.8rem;
    color: #e74c3c;
}

.feature-item h3 {
    color: #2c3e50;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.feature-item p {
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.5;
}

/* Team Section */
.team-section {
    margin-bottom: 3rem;
}

.team-section h2 {
    text-align: center;
    color: #2c3e50;
    font-size: 1.8rem;
    margin-bottom: 2rem;
}

.team-section h2 i {
    color: #e74c3c;
    margin-right: 10px;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.team-member {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.team-member:hover {
    transform: translateY(-5px);
}

.member-photo {
    width: 100px;
    height: 100px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    border: 3px solid #e74c3c;
}

.member-photo i {
    font-size: 3rem;
    color: #e74c3c;
}

.team-member h3 {
    color: #2c3e50;
    font-size: 1.1rem;
    margin-bottom: 0.3rem;
}

.member-role {
    color: #e74c3c;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.8rem;
}

.member-bio {
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.5;
}

/* CTA Section */
.about-cta {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.about-cta h3 {
    color: #2c3e50;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.about-cta p {
    color: #6c757d;
    margin-bottom: 1.5rem;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary, .btn-secondary, .btn-outline {
    padding: 0.8rem 1.5rem;
    border-radius: 40px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary {
    background: #27ae60;
    color: white;
}

.btn-primary:hover {
    background: #219653;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #3498db;
    color: white;
}

.btn-secondary:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    border: 2px solid #e74c3c;
    color: #e74c3c;
}

.btn-outline:hover {
    background: #e74c3c;
    color: white;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 992px) {
    .mission-section {
        grid-template-columns: 1fr;
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .team-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .about-hero h1 {
        font-size: 2rem;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .team-grid {
        grid-template-columns: 1fr;
    }
    
    .mission-stats {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
    }
    
    .btn-primary, .btn-secondary, .btn-outline {
        justify-content: center;
    }
}
</style>

<div class="about-container">
    <!-- Hero Section -->
    <div class="about-hero">
        <h1><i class="fas fa-lotus"></i> About Buddhist Art Heritage</h1>
        <p class="subtitle">Preserving tradition, connecting artists, sharing wisdom</p>
    </div>
    
    <!-- Mission Section -->
    <div class="mission-section">
        <div class="mission-content">
            <h2><i class="fas fa-heart"></i> Our Mission</h2>
            <p>To create a digital sanctuary where traditional Buddhist artists can showcase their sacred work, share their knowledge, and connect with art enthusiasts who truly appreciate the depth and meaning behind each piece.</p>
            
            <div class="mission-stats">
                <div class="stat">
                    <i class="fas fa-paint-brush"></i>
                    <h3>10+</h3>
                    <p>Traditional Artists</p>
                </div>
                <div class="stat">
                    <i class="fas fa-palette"></i>
                    <h3>50+</h3>
                    <p>Sacred Artworks</p>
                </div>
                <div class="stat">
                    <i class="fas fa-book-open"></i>
                    <h3>15+</h3>
                    <p>Knowledge Articles</p>
                </div>
            </div>
        </div>
        <div class="mission-image">
            <img src="https://media.app.happylandtreks.com/uploads/media/thangka-painting-art-shop-in-kathmandu-Nepal%20%281%29.webp" alt="Buddhist Mandala" onerror="this.src='https://via.placeholder.com/400x300?text=Buddhist+Art'">
        </div>
    </div>
    
    <!-- Story Section -->
    <div class="story-section">
        <h2><i class="fas fa-heart"></i> The Story Behind This Project</h2>
        <div class="story-content">
            <p>This project was born from a deeply personal inspiration. My father has been a Buddhist artist since the age of 11, dedicating over 40 years to mastering traditional Thangka painting, sculpture, and mandala art. Watching him preserve these sacred techniques while struggling to reach art lovers who truly understand their value sparked an idea.</p>
            
            <p>What if technology could help? Not to replace tradition, but to amplify it. Not to commercialize sacred art, but to connect it with people who seek its meaning.</p>
            
            <div class="father-quote">
                <i class="fas fa-quote-left"></i>
                <blockquote>"Buddhist art isn't just decoration. Every color has meaning. Every symbol tells a story. When someone understands this, they don't just see art – they receive a teaching."</blockquote>
                <cite>— My Father, Buddhist Artist for 40+ Years</cite>
            </div>
            
            <p>This platform is for every artist like my father – masters who have spent lifetimes perfecting their craft, yet remain unknown beyond their local communities. It's for art lovers who want to understand the symbolism behind each brushstroke. And it's for future generations who will carry these traditions forward.</p>
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="features-showcase">
        <h2><i class="fas fa-star"></i> Platform Features</h2>
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-globe-asia"></i>
                </div>
                <h3>Global Reach</h3>
                <p>Local artists can share their work with art enthusiasts worldwide, breaking geographical barriers.</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>Knowledge Sharing</h3>
                <p>Artists can explain the symbolism and techniques behind their work, educating the next generation.</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Custom Commissions</h3>
                <p>Request personalized artwork directly from artists with transparent process and secure payment.</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <h3>Smart Recommendations</h3>
                <p>Our algorithm helps art lovers discover artists and styles they'll genuinely appreciate.</p>
            </div>
        </div>
    </div>
    
    <!-- Team Section -->
    <div class="team-section">
        <h2><i class="fas fa-users"></i> Behind the Project</h2>
        <div class="team-grid">
            <div class="team-member">
                <div class="member-photo">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <h3>Inspired By</h3>
                <p class="member-role">Senior Buddhist Artist</p>
                <p class="member-bio">40+ years dedicated to preserving traditional Buddhist art forms including Thangka, Mandala, and sculpture.</p>
            </div>
            
            <div class="team-member">
                <div class="member-photo">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <h3>Built With</h3>
                <p class="member-role">Technology for Tradition</p>
                <p class="member-bio">PHP, MySQL, HTML/CSS, JavaScript - combining modern tools with ancient wisdom.</p>
            </div>
            
            <div class="team-member">
                <div class="member-photo">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>For</h3>
                <p class="member-role">Artists & Art Lovers</p>
                <p class="member-bio">Everyone who believes sacred art deserves to be seen, understood, and preserved.</p>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="about-cta">
        <h3>Join Us in Preserving Buddhist Art</h3>
        <p>Whether you're an artist looking to showcase your work or an art lover seeking authentic pieces, we welcome you.</p>
        <div class="cta-buttons">
            <a href="artists.php" class="btn-primary">
                <i class="fas fa-paint-brush"></i> Explore Artists
            </a>
            <a href="gallery.php" class="btn-secondary">
                <i class="fas fa-images"></i> View Gallery
            </a>
            <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="register.php?role=artist" class="btn-outline">
                <i class="fas fa-user-plus"></i> Join as Artist
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>