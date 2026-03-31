<?php
// about.php - About the project
require_once 'includes/config.php';
$page_title = "About Us - Buddhist Art Heritage";
require_once 'includes/navbar.php';
?>

<div class="container about-container">
    <!-- Hero Section -->
    <div class="about-hero">
        <h1><i class="fas fa-lotus"></i> About Buddhist Art Heritage</h1>
        <p class="subtitle">Preserving tradition, connecting artists, sharing wisdom</p>
    </div>
    
    <!-- Mission Section -->
    <div class="mission-section">
        <div class="mission-content">
            <h2>Our Mission</h2>
            <p>To create a digital sanctuary where traditional Buddhist artists can showcase their sacred work, share their knowledge, and connect with art enthusiasts who truly appreciate the depth and meaning behind each piece.</p>
            
            <div class="mission-stats">
                <div class="stat">
                    <i class="fas fa-paint-brush"></i>
                    <h3>50+</h3>
                    <p>Traditional Artists</p>
                </div>
                <div class="stat">
                    <i class="fas fa-palette"></i>
                    <h3>200+</h3>
                    <p>Sacred Artworks</p>
                </div>
                <div class="stat">
                    <i class="fas fa-book-open"></i>
                    <h3>30+</h3>
                    <p>Knowledge Articles</p>
                </div>
            </div>
        </div>
        <div class="mission-image">
            <img src="images/about-mandala.jpg" alt="Buddhist Mandala" onerror="this.src='https://via.placeholder.com/400x300?text=Buddhist+Art'">
        </div>
    </div>
    
    <!-- Story Section -->
    <div class="story-section">
        <h2><i class="fas fa-heart"></i> The Story Behind This Project</h2>
        
        <div class="story-content">
            <div class="story-text">
                <p>This project was born from a deeply personal inspiration. My father has been a Buddhist artist since the age of 11, dedicating over 40 years to mastering traditional Thangka painting, sculpture, and mandala art. Watching him preserve these sacred techniques while struggling to reach art lovers who truly understand their value sparked an idea.</p>
                
                <p>What if technology could help? Not to replace tradition, but to amplify it. Not to commercialize sacred art, but to connect it with people who seek its meaning.</p>
                
                <p><strong>This platform is for every artist like my father</strong> – masters who have spent lifetimes perfecting their craft, yet remain unknown beyond their local communities. It's for art lovers who want to understand the symbolism behind each brushstroke. And it's for future generations who will carry these traditions forward.</p>
            </div>
            
            <div class="father-quote">
                <i class="fas fa-quote-left"></i>
                <blockquote>
                    Buddhist art isn't just decoration. Every color has meaning. Every symbol tells a story. When someone understands this, they don't just see art – they receive a teaching.
                </blockquote>
                <cite>— My Father, Buddhist Artist for 40+ Years</cite>
            </div>
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="features-showcase">
        <h2>How We're Helping Artists</h2>
        
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
                <h3>Meaningful Commissions</h3>
                <p>Instead of quick sales, we facilitate thoughtful commissions where clients request pieces with personal significance.</p>
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
    
    <!-- Team Section (Simplified) -->
    <div class="team-section">
        <h2><i class="fas fa-users"></i> Behind the Project</h2>
        
        <div class="team-grid">
            <div class="team-member">
                <div class="member-photo">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3>Inspired By</h3>
                <p class="member-role">My Father, Senior Buddhist Artist</p>
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

<style>
.about-container {
    padding: 2rem 0;
}

.about-hero {
    text-align: center;
    margin-bottom: 4rem;
}

.about-hero h1 {
    color: var(--primary-color);
    font-size: 2.8rem;
    margin-bottom: 1rem;
}

.about-hero h1 i {
    color: var(--accent-color);
    margin-right: 10px;
}

.about-hero .subtitle {
    color: #666;
    font-size: 1.3rem;
    max-width: 600px;
    margin: 0 auto;
}

/* Mission Section */
.mission-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-bottom: 4rem;
    align-items: center;
}

.mission-content h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    font-size: 2rem;
}

.mission-content p {
    color: #444;
    line-height: 1.8;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.mission-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    text-align: center;
}

.mission-stats .stat i {
    font-size: 2rem;
    color: var(--secondary-color);
    margin-bottom: 0.5rem;
}

.mission-stats .stat h3 {
    color: var(--primary-color);
    font-size: 1.8rem;
}

.mission-stats .stat p {
    color: #666;
    font-size: 0.9rem;
}

.mission-image img {
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

/* Story Section */
.story-section {
    background: #f8f9fa;
    padding: 3rem;
    border-radius: 10px;
    margin-bottom: 4rem;
}

.story-section h2 {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 2rem;
    font-size: 2rem;
}

.story-content {
    max-width: 800px;
    margin: 0 auto;
}

.story-text p {
    color: #444;
    line-height: 1.8;
    margin-bottom: 1.5rem;
}

.father-quote {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    margin-top: 2rem;
    position: relative;
}

.father-quote i {
    font-size: 2rem;
    color: var(--accent-color);
    opacity: 0.3;
    position: absolute;
    top: 10px;
    left: 10px;
}

.father-quote blockquote {
    font-size: 1.2rem;
    font-style: italic;
    color: var(--primary-color);
    margin: 1rem 0;
    padding-left: 2rem;
}

.father-quote cite {
    color: #666;
    font-style: normal;
    display: block;
    text-align: right;
}

/* Features Showcase */
.features-showcase {
    margin-bottom: 4rem;
}

.features-showcase h2 {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 2rem;
    font-size: 2rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.feature-item {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s;
}

.feature-item:hover {
    transform: translateY(-5px);
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--accent-color), #f39c12);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.feature-icon i {
    font-size: 2rem;
    color: white;
}

.feature-item h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.feature-item p {
    color: #666;
    line-height: 1.6;
}

/* Team Section */
.team-section {
    margin-bottom: 4rem;
}

.team-section h2 {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 2rem;
    font-size: 2rem;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.team-member {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08);
}

.member-photo i {
    font-size: 5rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.team-member h3 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.member-role {
    color: var(--secondary-color);
    font-weight: 600;
    margin-bottom: 1rem;
}

.member-bio {
    color: #666;
    line-height: 1.6;
}

/* CTA Section */
.about-cta {
    text-align: center;
    padding: 3rem;
    background: linear-gradient(135deg, var(--primary-color), #4a6491);
    color: white;
    border-radius: 10px;
}

.about-cta h3 {
    font-size: 2rem;
    margin-bottom: 2rem;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.cta-buttons .btn-primary,
.cta-buttons .btn-secondary,
.cta-buttons .btn-outline {
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s;
}

.cta-buttons .btn-primary {
    background: var(--accent-color);
    color: var(--dark-color);
}

.cta-buttons .btn-secondary {
    background: white;
    color: var(--primary-color);
}

.cta-buttons .btn-outline {
    background: transparent;
    border: 2px solid white;
    color: white;
}

.cta-buttons a:hover {
    transform: translateY(-3px);
}

/* Responsive */
@media (max-width: 992px) {
    .mission-section {
        grid-template-columns: 1fr;
    }
    
    .mission-image {
        order: -1;
    }
}

@media (max-width: 768px) {
    .about-hero h1 {
        font-size: 2rem;
    }
    
    .mission-stats {
        grid-template-columns: 1fr;
    }
    
    .story-section {
        padding: 2rem;
    }
    
    .father-quote blockquote {
        font-size: 1rem;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>