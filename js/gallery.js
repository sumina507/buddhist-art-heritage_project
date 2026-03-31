// gallery.js - Gallery interactions
document.addEventListener('DOMContentLoaded', function() {
    // Like button functionality
    const likeButtons = document.querySelectorAll('.like-btn');
    
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const artworkId = this.dataset.artwork;
            const button = this;
            const heartIcon = this.querySelector('i');
            
            // Toggle like
            fetch('ajax/like-artwork.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    artwork_id: artworkId,
                    action: 'toggle'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button appearance
                    if (data.liked) {
                        heartIcon.className = 'fas fa-heart';
                        button.style.background = '#e74c3c';
                        button.style.color = 'white';
                    } else {
                        heartIcon.className = 'far fa-heart';
                        button.style.background = 'white';
                        button.style.color = '#e74c3c';
                    }
                    
                    // Update like count on page
                    const likeCountElement = button.closest('.artwork-card').querySelector('.likes');
                    if (likeCountElement) {
                        const currentCount = parseInt(likeCountElement.textContent) || 0;
                        likeCountElement.textContent = data.liked ? currentCount + 1 : currentCount - 1;
                    }
                } else {
                    alert(data.message || 'Error liking artwork');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
            });
        });
    });
    
    // Filter by category on mobile
    const filterButtons = document.querySelectorAll('.filter-btn');
    const sortButtons = document.querySelectorAll('.sort-btn');
    
    // Add active state tracking
    function updateActiveButton(buttons, activeClass) {
        buttons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!this.href.includes('javascript')) {
                    // Show loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    
                    // Remove active class from all buttons
                    buttons.forEach(b => b.classList.remove(activeClass));
                    
                    // Add active class to clicked button
                    this.classList.add(activeClass);
                }
            });
        });
    }
    
    updateActiveButton(filterButtons, 'active');
    updateActiveButton(sortButtons, 'active');
    
    // Lazy load images
    const images = document.querySelectorAll('.artwork-image img');
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src || img.src;
                imageObserver.unobserve(img);
            }
        });
    }, { rootMargin: '50px' });
    
    images.forEach(img => {
        if (img.dataset.src) {
            imageObserver.observe(img);
        }
    });
    
    // Algorithm score animation
    const scoreBars = document.querySelectorAll('.score-bar');
    scoreBars.forEach(bar => {
        const originalWidth = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.transition = 'width 1s ease-in-out';
            bar.style.width = originalWidth;
        }, 300);
    });
    
    // Infinite scroll (optional)
    let isLoading = false;
    let currentPage = 1;
    
    window.addEventListener('scroll', function() {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500 && !isLoading) {
            loadMoreArtworks();
        }
    });
    
    function loadMoreArtworks() {
        if (isLoading) return;
        
        isLoading = true;
        currentPage++;
        
        // Get current filter and sort
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category') || 'all';
        const sort = urlParams.get('sort') || 'popular';
        
        // Show loading indicator
        const loader = document.createElement('div');
        loader.className = 'loading-indicator';
        loader.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading more artworks...';
        document.querySelector('.gallery-grid').appendChild(loader);
        
        // Load more artworks via AJAX
        fetch(`ajax/load-more.php?page=${currentPage}&category=${category}&sort=${sort}`)
            .then(response => response.text())
            .then(html => {
                loader.remove();
                
                if (html.trim()) {
                    document.querySelector('.gallery-grid').insertAdjacentHTML('beforeend', html);
                    isLoading = false;
                } else {
                    // No more artworks
                    const noMore = document.createElement('div');
                    noMore.className = 'no-more-artworks';
                    noMore.innerHTML = '<p>No more artworks to load</p>';
                    document.querySelector('.gallery-grid').appendChild(noMore);
                    window.removeEventListener('scroll', arguments.callee);
                }
            })
            .catch(error => {
                console.error('Error loading more artworks:', error);
                loader.remove();
                isLoading = false;
            });
    }
});