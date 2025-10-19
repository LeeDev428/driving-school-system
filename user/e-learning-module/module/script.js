// Function to toggle card open/close state
function toggleCard(button) {
    const card = button.closest('.module-card');
    const btnText = button.querySelector('.btn-text');
    const isOpened = card.classList.contains('opened');
    
    // Toggle the opened class
    if (isOpened) {
        // Close the card
        card.classList.remove('opened');
        btnText.textContent = 'View Lessons';
        
        // Add closing animation
        const content = card.querySelector('.card-content');
        content.style.animation = 'slideUp 0.4s ease forwards';
        
        setTimeout(() => {
            content.style.animation = '';
        }, 400);
        
    } else {
        // Open the card
        card.classList.add('opened');
        btnText.textContent = 'Hide Lessons';
        
        // Add opening animation
        const content = card.querySelector('.card-content');
        content.style.animation = 'slideDown 0.4s ease forwards';
        
        setTimeout(() => {
            content.style.animation = '';
        }, 400);
    }
}

// Add click event listeners to all cards (alternative to inline onclick)
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.module-card');
    
    cards.forEach(card => {
        // Add click event to the entire card (optional)
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on the button (to avoid double triggering)
            if (!e.target.closest('.open-btn')) {
                const button = this.querySelector('.open-btn');
                toggleCard(button);
            }
        });
        
        // Add keyboard accessibility
        const button = card.querySelector('.open-btn');
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleCard(this);
            }
        });
    });
});

// Optional: Add smooth scroll behavior for better UX
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll to opened card if it's not fully visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target.classList.contains('opened')) {
                entry.target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        });
    }, {
        threshold: 0.8
    });
    
    const cards = document.querySelectorAll('.module-card');
    cards.forEach(card => observer.observe(card));
});

// Optional: Add loading animation
window.addEventListener('load', function() {
    const cards = document.querySelectorAll('.module-card');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
});
