<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if already logged in (before any HTML output)
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$page_title = "Register";

$error = '';
$success = '';

// Handle form submission (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Security token expired. Please try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $result = registerUser($username, $email, $password, $confirmPassword);

        if ($result['success']) {
            redirect('login.php?registered=true');
        } else {
            $error = implode('<br>', $result['errors']);
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Now include header after all redirects are handled
require_once 'includes/header.php';
?>

<div class="min-h-[80vh] flex flex-col items-center justify-center p-4 md:p-6">
    <div class="w-full max-w-4xl bg-white/80 dark:bg-background-dark/80 backdrop-blur-xl rounded-lg shadow-xl border border-primary/10 overflow-hidden transform hover:scale-[1.01] transition-all">
        <!-- Header -->
        <div class="bg-primary p-8 text-center text-white relative">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-md">
                    <span class="material-icons-round text-white text-3xl">person_add</span>
                </div>
                <h1 class="text-3xl font-bold tracking-tight">Create Your Space</h1>
                <p class="text-white/80 mt-1 font-medium">Join Us & Co.</p>
            </div>
        </div>

        <!-- Body -->
        <div class="p-8 lg:p-10 bg-white">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                    <div class="flex items-center gap-3">
                        <span class="material-icons-round text-red-500 text-sm">error_outline</span>
                        <p class="text-sm text-red-700 font-medium"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label for="username" class="text-xs font-bold uppercase tracking-wider text-slate-500 px-1">Username</label>
                        <div class="relative group">
                            <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors text-xl">person</span>
                            <input type="text" id="username" name="username" required 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none bg-slate-50 focus:bg-white text-slate-800"
                                   placeholder="Choose a username">
                        </div>
                        <p class="text-[10px] text-slate-400 px-1">3-20 characters, letters, numbers, underscores</p>
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="text-xs font-bold uppercase tracking-wider text-slate-500 px-1">Email</label>
                        <div class="relative group">
                            <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors text-xl">email</span>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none bg-slate-50 focus:bg-white text-slate-800"
                                   placeholder="your@email.com">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label for="password" class="text-xs font-bold uppercase tracking-wider text-slate-500 px-1">Password</label>
                        <div class="relative group">
                            <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors text-xl">lock</span>
                            <input type="password" id="password" name="password" required
                                   class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none bg-slate-50 focus:bg-white text-slate-800"
                                   placeholder="Minimum 8 characters">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="confirm_password" class="text-xs font-bold uppercase tracking-wider text-slate-500 px-1">Confirm Password</label>
                        <div class="relative group">
                            <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors text-xl">lock</span>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none bg-slate-50 focus:bg-white text-slate-800"
                                   placeholder="Re-enter password">
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-3 pt-2">
                    <input type="checkbox" id="terms" name="terms" required
                           class="mt-1 w-4 h-4 text-primary bg-slate-50 border-slate-300 rounded focus:ring-primary/20">
                    <label for="terms" class="text-sm text-slate-500">
                        I agree to the <a href="terms.php" class="text-primary font-bold hover:underline">Terms of Service</a> 
                        and <a href="privacy.php" class="text-primary font-bold hover:underline">Privacy Policy</a>
                    </label>
                </div>

                <div class="pt-4">
                    <button type="submit" id="submitBtn" class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/30 transform active:scale-95 transition-all flex items-center justify-center gap-3">
                        <span>Create My Space</span>
                        <span class="material-icons-round text-lg">arrow_forward</span>
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-8 border-t border-slate-100 text-center">
                <p class="text-sm text-slate-500 mb-3">Already have an account?</p>
                <a href="login.php" class="inline-flex items-center gap-2 text-primary font-bold hover:underline">
                    <span class="material-icons-round text-lg">login</span>
                    <span>Login to Your World</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');

    // Loading state
    registerForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        submitBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Creating...</span>
        `;
    });

    // Auto focus
    const usernameInput = document.getElementById('username');
    if (usernameInput && !usernameInput.value) {
        usernameInput.focus();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
