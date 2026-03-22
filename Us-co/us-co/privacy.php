<?php
$page_title = "Privacy Policy";
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <p class="text-muted">Last updated: <?php echo date('F d, Y'); ?></p>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h3>1. Information We Collect</h3>
                    <p>We collect information you provide directly to us, including:</p>
                    <ul>
                        <li>Account information (username, email, password)</li>
                        <li>Profile information (bio, relationship status, anniversary date)</li>
                        <li>Content you create (photos, articles, messages)</li>
                        <li>Usage data (login times, features used)</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h3>2. How We Use Your Information</h3>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Provide, maintain, and improve our services</li>
                        <li>Send you technical notices and support messages</li>
                        <li>Respond to your comments and questions</li>
                        <li>Protect against fraudulent or illegal activity</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h3>3. Information Sharing</h3>
                    <p>We do not share your personal information with third parties except:</p>
                    <ul>
                        <li>With your consent</li>
                        <li>To comply with legal obligations</li>
                        <li>To protect our rights and safety</li>
                    </ul>
                    <p><strong>We never sell your data to advertisers or third parties.</strong></p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h3>4. Data Security</h3>
                    <p>We take reasonable measures to protect your information from unauthorized access, including:</p>
                    <ul>
                        <li>Encrypted passwords using industry-standard hashing</li>
                        <li>Secure database connections</li>
                        <li>Regular security updates and monitoring</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h3>5. Your Rights</h3>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your personal information</li>
                        <li>Correct inaccurate information</li>
                        <li>Delete your account and data</li>
                        <li>Export your data</li>
                    </ul>
                    <p>To exercise these rights, visit your <a href="settings.php">account settings</a>.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3>6. Contact Us</h3>
                    <p>If you have questions about this Privacy Policy, please <a href="contact.php">contact us</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
