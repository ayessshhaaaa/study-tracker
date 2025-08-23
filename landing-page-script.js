document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu toggle logic
    const mobileMenuToggle = document.querySelector('.lp-mobile-menu-toggle');
    const nav = document.querySelector('.lp-nav');

    if (mobileMenuToggle && nav) {
        mobileMenuToggle.addEventListener('click', () => {
            nav.classList.toggle('lp-nav-open');
            const icon = mobileMenuToggle.querySelector('i');
            if (nav.classList.contains('lp-nav-open')) {
                icon.classList.replace('fa-bars', 'fa-times');
            } else {
                icon.classList.replace('fa-times', 'fa-bars');
            }
        });

        // Close menu when a nav item is clicked (only for smaller screens)
        document.querySelectorAll('.lp-nav-item').forEach(item => {
            item.addEventListener('click', () => {
                // Check if the menu is currently open on mobile
                // This condition helps prevent unintended collapsing on desktop clicks
                if (nav.classList.contains('lp-nav-open') && window.innerWidth <= 768) {
                    nav.classList.remove('lp-nav-open');
                    mobileMenuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
                }
            });
        });
    }

    // --- Interactive Sparkle Effect (on mousemove/touchmove) ---
    // Create a container for sparkles to manage them easily
    const sparkleContainer = document.createElement('div');
    sparkleContainer.classList.add('sparkle-effect-container'); // Use the class defined in CSS
    document.body.appendChild(sparkleContainer);

    const colors = [
        'var(--sparkle-color-1)', // Defined in landing-page.css
        'var(--sparkle-color-2)', // Defined in landing-page.css
        'var(--sparkle-color-3)'  // Defined in landing-page.css
    ];

    let sparkleCount = 0; // To limit the total number of sparkles in the DOM if needed

    function createSparkle(x, y) {
        // Optional: Limit total sparkles to prevent performance issues on very long sessions
        if (sparkleCount > 200) {
            const oldestSparkle = sparkleContainer.firstElementChild;
            if (oldestSparkle) {
                oldestSparkle.remove();
                sparkleCount--;
            }
        }

        const sparkle = document.createElement('div');
        sparkle.classList.add('lp-sparkle'); // Class for sparkle styling
        sparkleContainer.appendChild(sparkle);

        const size = Math.random() * 2 + 1; // Size between 1px and 3px
        const color = colors[Math.floor(Math.random() * colors.length)];
        const animationDuration = Math.random() * 0.7 + 0.5; // Duration between 0.5s and 1.2s

        sparkle.style.width = `${size}px`;
        sparkle.style.height = `${size}px`;
        sparkle.style.left = `${x}px`;
        sparkle.style.top = `${y}px`;
        sparkle.style.backgroundColor = color; // Set initial color (though shadow is main visual)
        sparkle.style.animationDuration = `${animationDuration}s`;

        // Remove the sparkle after its animation completes to keep the DOM clean
        sparkle.addEventListener('animationend', () => {
            sparkle.remove();
            sparkleCount--; // Decrement count when removed
        });
        sparkleCount++; // Increment count when created
    }

    // Event listener for mouse movement to create sparkles
    document.addEventListener('mousemove', (e) => {
        // Create fewer sparkles if moving very fast or too many already exist
        // Adjust Math.random() < 0.7 for density (lower number = fewer sparkles)
        if (Math.random() < 0.7 && sparkleCount < 200) {
            createSparkle(e.clientX + Math.random() * 5 - 2.5, e.clientY + Math.random() * 5 - 2.5); // Add slight random offset
        }
    });

    // Event listener for touch movement (for mobile devices)
    document.addEventListener('touchmove', (e) => {
        if (e.touches.length > 0) {
            const touch = e.touches[0];
            if (Math.random() < 0.7 && sparkleCount < 200) {
                 createSparkle(touch.clientX + Math.random() * 5 - 2.5, touch.clientY + Math.random() * 5 - 2.5);
            }
        }
    });

});