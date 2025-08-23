document.addEventListener('DOMContentLoaded', () => {
    // --- 1. Background Animation (Removed for Professional Look) ---
    // The .background-animation div is removed from HTML for a cleaner, static background.
    // No JavaScript for background animation (sparkles) is needed here.


    // --- 2. Motivational Quotes Functionality ---
    const quotes = [
        "The best way to predict the future is to create it. – Peter Drucker",
        "The mind is not a vessel to be filled, but a fire to be kindled. – Plutarch",
        "Success is not final, failure is not fatal: it is the courage to continue that counts. – Winston Churchill",
        "The only way to do great work is to love what you do. – Steve Jobs",
        "Believe you can and you're halfway there. – Theodore Roosevelt",
        "The expert in anything was once a beginner. – Helen Hayes",
        "Productivity is never an accident. It is always the result of a commitment to excellence, intelligent planning, and focused effort. – Paul J. Meyer",
        "The future belongs to those who believe in the beauty of their dreams. – Eleanor Roosevelt",
        "Don't watch the clock; do what it does. Keep going. – Sam Levenson",
        "Learning is not attained by chance, it must be sought for with ardor and diligence. – Abigail Adams"
    ];

    const quoteDisplay = document.getElementById("quote-display");
    const refreshQuoteBtn = document.getElementById("refresh-quote-btn");

    function displayRandomQuote() {
        const randomIndex = Math.floor(Math.random() * quotes.length);
        // Add a subtle fade effect for smoother transition
        quoteDisplay.style.opacity = 0;
        setTimeout(() => {
            quoteDisplay.textContent = quotes[randomIndex];
            quoteDisplay.style.opacity = 1;
        }, 300); // Match CSS transition duration for opacity
    }

    // Inject CSS for fade effect on quote (only once)
    if (!document.getElementById('quote-display-style')) {
        const quoteStyle = document.createElement('style');
        quoteStyle.id = 'quote-display-style';
        quoteStyle.innerHTML = `
            #quote-display {
                transition: opacity 0.3s ease-in-out;
            }
        `;
        document.head.appendChild(quoteStyle);
    }

    displayRandomQuote(); // Display initial quote on load
    refreshQuoteBtn.addEventListener('click', displayRandomQuote);


    // --- 3. Daily Mood Tracker with Animated Emojis ---
    const moodEmojis = document.querySelectorAll('.mood-emojis .emoji');
    const selectedMoodFeedback = document.getElementById("selected-mood-feedback");

    moodEmojis.forEach(emoji => {
        emoji.addEventListener('click', () => {
            // Remove 'selected' class and floating animation from all emojis
            moodEmojis.forEach(e => {
                e.classList.remove('selected');
                e.style.animation = ''; // Clear previous animation to allow re-triggering
            });

            // Add 'selected' class to the clicked emoji
            emoji.classList.add('selected');

            // Apply a temporary "float up" animation specific to the clicked emoji
            emoji.style.animation = 'emojiFloatUp 0.8s ease-out forwards'; // Animate up and stay

            // Reset animation after it finishes to allow re-triggering on subsequent clicks
            emoji.addEventListener('animationend', () => {
                emoji.style.animation = '';
            }, { once: true }); // 'once: true' ensures the listener is removed after one execution


            const mood = emoji.dataset.mood;
            let message = '';
            switch (mood) {
                case 'happy':
                    message = "Feeling positive. Keep up the great work!";
                    break;
                case 'neutral':
                    message = "Feeling neutral. Take it as it comes.";
                    break;
                case 'sad':
                    message = "Feeling down. Remember to take a break if needed.";
                    break;
                case 'motivated':
                    message = "Highly motivated! Channel this energy into your tasks.";
                    break;
                case 'stressed':
                    message = "Feeling stressed. Prioritize self-care and breaks.";
                    break;
                default:
                    message = "Mood logged.";
            }
            selectedMoodFeedback.textContent = message;

            // You can integrate this with your backend here:
            // Example: fetch('/api/logMood', { method: 'POST', body: JSON.stringify({ mood: mood }) })
            // .then(response => response.json())
            // .then(data => console.log('Mood logged:', data))
            // .catch(error => console.error('Error logging mood:', error));
            console.log(`Mood logged: ${mood}`); // For debugging
        });
    });

    // Inject CSS for emoji float-up animation dynamically (only once)
    if (!document.getElementById('emoji-float-keyframes')) {
        const emojiStyle = document.createElement('style');
        emojiStyle.id = 'emoji-float-keyframes';
        emojiStyle.innerHTML = `
            @keyframes emojiFloatUp {
                0% { transform: scale(1.3) translateY(0); opacity: 1; }
                50% { transform: scale(1.4) translateY(-15px); opacity: 1; }
                100% { transform: scale(1.3) translateY(-4px); opacity: 1; } /* Settle slightly above */
            }
        `;
        document.head.appendChild(emojiStyle);
    }


    // --- 4. Theme Toggle (Light/Dark Professional Theme) ---
    const htmlElement = document.documentElement; // Get the <html> element
    const themeSwitch = document.getElementById("themeSwitch");

    // Function to apply theme based on localStorage or default
    function applyTheme() {
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") {
            htmlElement.setAttribute('data-theme', 'dark');
            themeSwitch.checked = true;
        } else {
            // Default to light theme if no preference or 'light' is saved
            htmlElement.removeAttribute('data-theme');
            themeSwitch.checked = false;
        }
    }

    // Apply theme immediately when script loads
    applyTheme();

    // Add event listener for theme switch change
    themeSwitch.addEventListener("change", function() {
        if (this.checked) {
            htmlElement.setAttribute('data-theme', 'dark');
            localStorage.setItem("theme", "dark");
        } else {
            htmlElement.removeAttribute('data-theme');
            localStorage.setItem("theme", "light");
        }
    });

    // --- 5. Feature Card Click Handling (Optional, for routing or popups) ---
    // These are examples. You might replace `alert()` with your actual routing functions
    // or modal popups for Pomodoro, Notes, Goals.

    const pomodoroBtn = document.querySelector('.pomodoro-btn');
    if (pomodoroBtn) {
        pomodoroBtn.addEventListener('click', () => {
            alert('Opening Pomodoro Timer! (Implement your Pomodoro logic here)');
        });
    }

    const notesBtn = document.querySelector('.notes-btn');
    if (notesBtn) {
        notesBtn.addEventListener('click', () => {
            alert('Opening Notes Section! (Implement your Notes logic here)');
        });
    }

    const goalsBtn = document.querySelector('.goals-btn');
    if (goalsBtn) {
        goalsBtn.addEventListener('click', () => {
            alert('Opening Goals Tracking! (Implement your Goals logic here)');
        });
    }

    const calendarBtn = document.querySelector('.calendar-btn');
    if (calendarBtn) {
        calendarBtn.addEventListener('click', () => {
            window.location.href = 'calendar.html';
        });
    }

    // --- 6. Sidebar Navigation Scroll-to-Section ---
    // This makes the sidebar links scroll to the respective sections on the dashboard
    const sidebarMoodLink = document.querySelector('.nav-item.mood-link');
    if (sidebarMoodLink) {
        sidebarMoodLink.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default link behavior
            const moodTrackerSection = document.querySelector('.mood-tracker-box');
            if (moodTrackerSection) {
                moodTrackerSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    const sidebarQuoteLink = document.querySelector('.nav-item.quote-link');
    if (sidebarQuoteLink) {
        sidebarQuoteLink.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default link behavior
            const quoteSection = document.querySelector('.motivational-quotes');
            if (quoteSection) {
                quoteSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    // --- 7. Active Navigation Item Highlighting ---
    // This ensures the current page's sidebar item is highlighted
    const currentPath = window.location.pathname.split('/').pop(); // Gets 'dashboard.php' or ''
    const navItems = document.querySelectorAll('.main-nav .nav-item');

    navItems.forEach(item => {
        const itemHref = item.getAttribute('href');
        // Check if the item's href matches the current page, or if it's dashboard.php and the path is empty
        if (itemHref && (itemHref.endsWith(currentPath) || (currentPath === '' && itemHref === 'dashboard.php'))) {
            item.classList.add('active');
        } else {
             item.classList.remove('active'); // Remove active from others
        }
    });

});