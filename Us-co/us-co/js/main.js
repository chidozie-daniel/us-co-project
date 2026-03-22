// Main JavaScript for Everest Website

document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Form validation enhancement
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Image lazy loading
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Back to top button
    const backToTopButton = document.createElement('button');
    backToTopButton.innerHTML = '<span class="material-icons-round">keyboard_arrow_up</span>';
    backToTopButton.className = 'btn btn-primary back-to-top';
    backToTopButton.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: none;
        z-index: 999;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    document.body.appendChild(backToTopButton);

    backToTopButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTopButton.style.display = 'flex';
            backToTopButton.style.alignItems = 'center';
            backToTopButton.style.justifyContent = 'center';
        } else {
            backToTopButton.style.display = 'none';
        }
    });

    // Valentine's Day countdown update
    function updateCountdown() {
        const countdownElements = document.querySelectorAll('.countdown-timer');
        if (countdownElements.length > 0) {
            const now = new Date();
            const currentYear = now.getFullYear();
            let valentinesDay = new Date(currentYear, 1, 14); // February is month 1

            if (valentinesDay < now) {
                valentinesDay = new Date(currentYear + 1, 1, 14);
            }

            const diff = valentinesDay - now;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

            countdownElements.forEach(element => {
                const dayElement = element.querySelector('.countdown-number:first-child');
                const hourElement = element.querySelector('.countdown-number:nth-child(2)');

                if (dayElement) dayElement.textContent = days;
                if (hourElement) hourElement.textContent = hours;
            });
        }
    }

    // Update countdown every hour
    updateCountdown();
    setInterval(updateCountdown, 3600000); // Update every hour

    // Add confetti effect for Valentine's Day
    function createConfetti() {
        const isValentinesMonth = new Date().getMonth() === 1; // February
        const isValentinesDay = new Date().getDate() === 14 && isValentinesMonth;

        if (isValentinesDay || document.querySelector('.valentine-special')) {
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.cssText = `
                        position: fixed;
                        width: 10px;
                        height: 10px;
                        background: ${Math.random() > 0.5 ? '#e91e63' : '#ff4081'};
                        border-radius: 50%;
                        top: -20px;
                        left: ${Math.random() * 100}vw;
                        z-index: 9999;
                        pointer-events: none;
                    `;
                    document.body.appendChild(confetti);

                    // Animation
                    const animation = confetti.animate([
                        { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                        { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
                    ], {
                        duration: Math.random() * 3000 + 2000,
                        easing: 'cubic-bezier(0.215, 0.610, 0.355, 1)'
                    });

                    animation.onfinish = () => confetti.remove();
                }, i * 100);
            }
        }
    }

    // Trigger confetti on special pages or Valentine's Day
    const isImmersiveValentine = document.body && document.body.classList.contains('valentine-immersive');
    if (!isImmersiveValentine && (window.location.pathname.includes('valentine') ||
        window.location.pathname.includes('dashboard'))) {
        setTimeout(createConfetti, 1000);
    }

    // Enhance file upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            const previewId = this.dataset.preview;
            if (previewId) {
                const preview = document.getElementById(previewId);
                const file = this.files[0];

                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded">`;
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    });

    // Real-time notification bell
    function checkNotifications() {
        fetch('ajax/check_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.unread > 0) {
                    const notificationBell = document.querySelector('.notification-bell');
                    if (notificationBell) {
                        notificationBell.classList.add('has-notification');

                        // Add pulse animation
                        notificationBell.style.animation = 'pulse 2s infinite';

                        // Update badge count
                        const badge = notificationBell.querySelector('.badge');
                        if (badge) {
                            badge.textContent = data.unread;
                        }
                    }
                }
            });
    }

    // Check notifications every minute
    setInterval(checkNotifications, 60000);

    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        .notification-bell.has-notification {
            color: #e91e63;
        }
    `;
    document.head.appendChild(style);

    // Add loading spinner for AJAX requests
    const originalFetch = window.fetch;
    window.fetch = function (...args) {
        const loadingSpinner = document.getElementById('loadingSpinner');
        if (loadingSpinner) {
            loadingSpinner.style.display = 'block';
        }

        return originalFetch.apply(this, args).finally(() => {
            if (loadingSpinner) {
                loadingSpinner.style.display = 'none';
            }
        });
    };
});
