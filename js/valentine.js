document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('js-enabled');
    // Scroll Reveals
    const reveals = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        },
        { threshold: 0.15 }
    );
    reveals.forEach(el => observer.observe(el));

    // Particle Systems (Floating hearts/stars)
    function createParticles(containerId, count) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const symbols = ['❤️', '✨', '💖', '⭐', '🌸'];
        for (let i = 0; i < count; i++) {
            const particle = document.createElement('span');
            particle.className = 'floater';
            particle.textContent = symbols[Math.floor(Math.random() * symbols.length)];
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.fontSize = Math.random() * (24 - 12) + 12 + 'px';
            container.appendChild(particle);
        }
    }

    // Twinkling Stars for Spiritual Section
    function createStars() {
        const container = document.getElementById('starsContainer');
        if (!container) return;

        for (let i = 0; i < 150; i++) {
            const star = document.createElement('div');
            star.className = 'star-particle';
            const size = Math.random() * 3 + 'px';
            star.style.width = size;
            star.style.height = size;
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 5 + 's';
            star.style.opacity = Math.random();
            container.appendChild(star);
        }
    }

    createParticles('heroFloaters', 20);
    createStars();

    // Gallery Modal Logic
    const galleryItems = Array.from(document.querySelectorAll('.gallery-item'));
    const modal = document.getElementById('valentineModal');
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const modalStory = document.getElementById('modalStory');
    const modalPrev = document.getElementById('modalPrev');
    const modalNext = document.getElementById('modalNext');
    const modalCloseBtn = modal ? modal.querySelector('.modal-close') : null;
    let currentIndex = 0;
    const quietStartTriggers = Array.from(document.querySelectorAll('[data-story-modal-open="quiet-start"]'));
    const quietStartModal = document.getElementById('quietStartModal');
    const quietStoryScroll = document.getElementById('quietStoryScroll');
    let quietAutoScrollTimer = null;

    function hasAnyOpenModal() {
        const activeModal = modal && modal.classList.contains('active');
        const activeQuietModal = quietStartModal && quietStartModal.classList.contains('active');
        return activeModal || activeQuietModal;
    }

    function lockPageScroll() {
        document.body.style.overflow = hasAnyOpenModal() ? 'hidden' : '';
    }

    function openModal(index) {
        const item = galleryItems[index];
        if (!item) return;
        currentIndex = index;
        modalImage.src = item.dataset.src;
        modalTitle.textContent = item.dataset.title;
        modalStory.textContent = item.dataset.story;
        modal.classList.add('active');
        lockPageScroll();
    }

    function closeModal() {
        modal.classList.remove('active');
        lockPageScroll();
    }

    function showNext() {
        currentIndex = (currentIndex + 1) % galleryItems.length;
        openModal(currentIndex);
    }

    function showPrev() {
        currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
        openModal(currentIndex);
    }

    galleryItems.forEach((item, index) => {
        item.addEventListener('click', () => openModal(index));
    });

    modalPrev.addEventListener('click', (e) => { e.stopPropagation(); showPrev(); });
    modalNext.addEventListener('click', (e) => { e.stopPropagation(); showNext(); });

    modal.addEventListener('click', event => {
        const clickedClose = event.target.closest('[data-close="true"]');
        if (event.target.id === 'valentineModal' || clickedClose) {
            closeModal();
        }
    });

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            closeModal();
        });
    }

    document.addEventListener('pointerdown', event => {
        const closeHit = event.target.closest('#valentineModal .modal-close');
        if (!closeHit) return;
        event.preventDefault();
        event.stopPropagation();
        closeModal();
    }, true);

    function startQuietAutoScroll() {
        if (!quietStoryScroll) return;
        stopQuietAutoScroll();
        quietAutoScrollTimer = window.setInterval(() => {
            const maxScroll = quietStoryScroll.scrollHeight - quietStoryScroll.clientHeight;
            if (maxScroll <= 0) return;
            if (quietStoryScroll.scrollTop >= maxScroll - 1) {
                quietStoryScroll.scrollTop = 0;
                return;
            }
            quietStoryScroll.scrollTop += 1.2;
        }, 24);
    }

    function stopQuietAutoScroll() {
        if (quietAutoScrollTimer) {
            window.clearInterval(quietAutoScrollTimer);
            quietAutoScrollTimer = null;
        }
    }

    function openQuietStartModal() {
        if (!quietStartModal || !quietStoryScroll) return;
        quietStartModal.classList.add('active');
        quietStoryScroll.scrollTop = 0;
        startQuietAutoScroll();
        lockPageScroll();
    }

    function closeQuietStartModal() {
        if (!quietStartModal) return;
        quietStartModal.classList.remove('active');
        stopQuietAutoScroll();
        lockPageScroll();
    }

    quietStartTriggers.forEach(trigger => {
        trigger.addEventListener('click', openQuietStartModal);
        trigger.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openQuietStartModal();
            }
        });
    });

    // Delegate clicks so it still works if cards are re-rendered or nested elements capture the click.
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-story-modal-open="quiet-start"]');
        if (!trigger) return;
        openQuietStartModal();
    });

    if (quietStartModal) {
        quietStartModal.addEventListener('click', event => {
            if (event.target.id === 'quietStartModal' || event.target.dataset.close === 'true') {
                closeQuietStartModal();
            }
        });
    }

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (modal.classList.contains('active')) closeModal();
            if (quietStartModal && quietStartModal.classList.contains('active')) closeQuietStartModal();
            return;
        }
        if (!modal.classList.contains('active')) return;
        if (event.key === 'ArrowRight') showNext();
        if (event.key === 'ArrowLeft') showPrev();
    });

    // Music Player Logic
    const audio = document.getElementById('valentineAudio');
    const musicToggle = document.getElementById('musicToggle');
    const musicText = musicToggle ? musicToggle.querySelector('.music-text') : null;
    const spotifyPlayerWrap = document.getElementById('spotifyPlayerWrap');
    const spotifyPlayerFrame = document.getElementById('spotifyPlayerFrame');
    const musicSource = musicToggle ? musicToggle.dataset.musicSource : 'audio';
    let isPlaying = false;

    if (musicToggle && musicSource === 'spotify' && spotifyPlayerWrap) {
        musicToggle.addEventListener('click', () => {
            const isHidden = spotifyPlayerWrap.hasAttribute('hidden');

            if (isHidden) {
                spotifyPlayerWrap.removeAttribute('hidden');

                // Rebuild src with autoplay request so playback starts in-app.
                if (spotifyPlayerFrame) {
                    const baseSrc = spotifyPlayerFrame.dataset.baseSrc || spotifyPlayerFrame.src;
                    try {
                        const url = new URL(baseSrc, window.location.origin);
                        url.searchParams.set('autoplay', '1');
                        spotifyPlayerFrame.src = url.toString();
                    } catch (_err) {
                        spotifyPlayerFrame.src = baseSrc;
                    }
                }

                musicText.textContent = 'Hide playlist';
            } else {
                spotifyPlayerWrap.setAttribute('hidden', 'hidden');
                musicText.textContent = 'Play our song';
            }
        });
    } else if (musicToggle && audio) {
        musicToggle.addEventListener('click', () => {
            if (isPlaying) {
                audio.pause();
                musicText.textContent = 'Play our song';
            } else {
                audio.play();
                musicText.textContent = 'Pause our song';
            }
            isPlaying = !isPlaying;
        });
    }
});
