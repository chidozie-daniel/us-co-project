<?php
// Romantic Whispers widget for dashboard (formerly News widget)

function getNewsWidget() {
    $whispers = [
        "Your smile is the only thing I need to start my day.",
        "In your eyes, I found my favorite home.",
        "Every moment with you is a beautiful page in our story.",
        "You are the best thing that ever happened to me.",
        "My heart beats a little faster every time I think of you.",
        "You make the world feel like a safer, softer place.",
        "I love the way you see the world, and I love the way you see me.",
        "To the moon and back, and then some more."
    ];
    
    // Pick 3 random whispers
    shuffle($whispers);
    $selected = array_slice($whispers, 0, 3);
    
    $html = '<div class="card news-widget border-0 shadow-sm overflow-hidden" style="border-radius: 30px;">';
    $html .= '<div class="card-header border-0 py-3" style="background: var(--gradient-primary); color: #fff;">';
    $html .= '<h6 class="mb-0 fw-bold"><i class="fas fa-feather-alt me-2 text-white-50"></i>Romantic Whispers</h6>';
    $html .= '</div>';
    $html .= '<div class="card-body p-0">';
    
    foreach ($selected as $whisper) {
        $html .= '<div class="news-item p-4 border-bottom" style="background: #fff8f9;">';
        $html .= '<div class="d-flex align-items-center gap-3">';
        $html .= '<div class="whisper-icon text-primary"><i class="fas fa-heart"></i></div>';
        $html .= '<div class="flex-grow-1">';
        $html .= '<p class="mb-0 italic text-muted" style="font-style: italic;">"' . htmlspecialchars($whisper) . '"</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '<div class="card-footer bg-white border-0 text-center py-3">';
    $html .= '<span class="text-primary small" style="font-family: \'Great Vibes\', cursive; font-size: 1.2rem;">Our private world...</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
?>
