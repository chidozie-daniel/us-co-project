<?php
$page_title = "About Everest";
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="fw-bold mb-4">Sharing the human experience.</h1>
            <p class="lead text-muted">Everest was founded on the belief that social networking should be about real connections, not just algorithms. We build tools that help you stay close to the people who matter most.</p>
        </div>
        <div class="col-lg-6">
            <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" class="img-fluid rounded-lg shadow">
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="bg-white p-4 rounded-lg shadow-sm border-top border-primary border-4 h-100">
                <h4 class="fw-bold">Our Mission</h4>
                <p class="text-muted">To provide a secure, beautiful, and functional platform where memories are preserved and friendships flourish.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white p-4 rounded-lg shadow-sm border-top border-success border-4 h-100">
                <h4 class="fw-bold">Our Community</h4>
                <p class="text-muted">Over 1 million users trust Everest for their daily social interactions and life storytelling.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white p-4 rounded-lg shadow-sm border-top border-warning border-4 h-100">
                <h4 class="fw-bold">Our Values</h4>
                <p class="text-muted">Transparency, privacy, and user-centric design are the pillars of everything we build at Everest.</p>
            </div>
        </div>
    </div>

    <div class="card bg-primary text-white p-5 rounded-lg border-0 text-center shadow">
        <h2 class="fw-bold mb-3">Ready to join the network?</h2>
        <p class="mb-4 opacity-75">Be part of the next generation of social networking. It's free and always will be.</p>
        <a href="register.php" class="btn btn-light btn-lg rounded-pill px-5">Create Your Account</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
