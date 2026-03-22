// Background Music Player
document.addEventListener('DOMContentLoaded', function () {
    const audio = document.getElementById('backgroundMusic');
    const playBtn = document.getElementById('playBtn');
    const pauseBtn = document.getElementById('pauseBtn');
    const nextBtn = document.getElementById('nextBtn');
    const volumeSlider = document.getElementById('volumeSlider');
    const playerTitle = document.querySelector('.player-title');
    const playerArtist = document.querySelector('.player-artist');

    // Lofi music playlist
    const playlist = [
        {
            title: "Lofi Chill Mix",
            artist: "Everest Radio",
            src: "assets/music/lofi-background.mp3"
        },
        {
            title: "Coffee Shop Vibes",
            artist: "Chillhop Music",
            src: "assets/music/chillhop.mp3"
        },
        {
            title: "Study Session",
            artist: "Lofi Girl",
            src: "assets/music/study-lofi.mp3"
        },
        {
            title: "Rainy Day",
            artist: "Ambient Mix",
            src: "assets/music/ambient-rain.mp3"
        }
    ];

    let currentTrackIndex = 0;

    // Initialize volume
    audio.volume = volumeSlider.value / 100;

    // Play button click
    playBtn.addEventListener('click', function () {
        audio.play();
        playBtn.style.display = 'none';
        pauseBtn.style.display = 'flex';
    });

    // Pause button click
    pauseBtn.addEventListener('click', function () {
        audio.pause();
        pauseBtn.style.display = 'none';
        playBtn.style.display = 'flex';
    });

    // Next button click
    nextBtn.addEventListener('click', function () {
        currentTrackIndex = (currentTrackIndex + 1) % playlist.length;
        loadTrack(currentTrackIndex);
        audio.play();
        playBtn.style.display = 'none';
        pauseBtn.style.display = 'flex';
    });

    // Volume slider
    volumeSlider.addEventListener('input', function () {
        audio.volume = this.value / 100;
    });

    // Load track
    function loadTrack(index) {
        const track = playlist[index];
        audio.src = track.src;
        playerTitle.textContent = track.title;
        playerArtist.textContent = track.artist;

        // Update localStorage for persistence
        localStorage.setItem('currentTrackIndex', index);
        localStorage.setItem('isPlaying', 'true');
    }

    // Auto-play next track when current ends
    audio.addEventListener('ended', function () {
        currentTrackIndex = (currentTrackIndex + 1) % playlist.length;
        loadTrack(currentTrackIndex);
        audio.play();
    });

    // Load saved state from localStorage
    const savedTrackIndex = localStorage.getItem('currentTrackIndex');
    const wasPlaying = localStorage.getItem('isPlaying') === 'true';

    if (savedTrackIndex !== null) {
        currentTrackIndex = parseInt(savedTrackIndex);
        loadTrack(currentTrackIndex);

        if (wasPlaying) {
            audio.play().then(() => {
                playBtn.style.display = 'none';
                pauseBtn.style.display = 'flex';
            }).catch(() => {
                // Autoplay was prevented
                console.log('Autoplay prevented');
            });
        }
    } else {
        loadTrack(0);
    }

    // Save state when page unloads
    window.addEventListener('beforeunload', function () {
        localStorage.setItem('isPlaying', !audio.paused);
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        // Space bar to play/pause
        if (e.code === 'Space' && !e.target.matches('input, textarea, button, [contenteditable="true"]')) {
            e.preventDefault();
            if (audio.paused) {
                audio.play();
                playBtn.style.display = 'none';
                pauseBtn.style.display = 'flex';
            } else {
                audio.pause();
                pauseBtn.style.display = 'none';
                playBtn.style.display = 'flex';
            }
        }

        // Right arrow for next track
        if (e.code === 'ArrowRight' && e.ctrlKey) {
            e.preventDefault();
            nextBtn.click();
        }

        // Up/Down arrow for volume
        if (e.code === 'ArrowUp' && e.ctrlKey) {
            e.preventDefault();
            volumeSlider.value = Math.min(100, parseInt(volumeSlider.value) + 10);
            audio.volume = volumeSlider.value / 100;
        }

        if (e.code === 'ArrowDown' && e.ctrlKey) {
            e.preventDefault();
            volumeSlider.value = Math.max(0, parseInt(volumeSlider.value) - 10);
            audio.volume = volumeSlider.value / 100;
        }
    });

    // Mobile touch controls
    let touchStartX = 0;
    let touchStartY = 0;

    document.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    });

    document.addEventListener('touchend', function (e) {
        const touchEndX = e.changedTouches[0].screenX;
        const touchEndY = e.changedTouches[0].screenY;

        // Swipe right for next track (if on music player)
        if (touchEndX - touchStartX > 100 && Math.abs(touchEndY - touchStartY) < 50) {
            if (e.target.closest('.music-player')) {
                nextBtn.click();
            }
        }
    });
});