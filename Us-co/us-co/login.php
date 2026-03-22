<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if already logged in (before any HTML output)
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$page_title = "Login";
$page_hide_guest_header = true;

$error = '';
$success = '';

// Handle form submission (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Security token expired. Please try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $result = loginUser($username, $password);
        
        if ($result['success']) {
            redirect('dashboard.php');
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

<div class="w-full min-h-[calc(100vh-140px)] flex flex-col items-center justify-start p-4 md:p-6 pt-2 md:pt-3 pb-10">
    <div class="w-full max-w-md mx-auto bg-white/85 dark:bg-background-dark/85 backdrop-blur-xl rounded-2xl shadow-xl border border-primary/10 overflow-hidden transition-all">
        <!-- Header -->
        <div class="bg-primary p-7 text-center text-white relative">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
            <div class="relative z-10">
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 backdrop-blur-md">
                    <span class="material-icons-round text-white text-2xl">lock_open</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Login</h1>
                <p class="text-white/80 mt-1 font-medium">Us & Co</p>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6 md:p-7 bg-white">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg animate-pulse">
                    <div class="flex items-center gap-3">
                        <span class="material-icons-round text-red-500 text-sm">error_outline</span>
                        <p class="text-sm text-red-700 font-medium"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="space-y-2">
                    <label for="username" class="text-xs font-bold uppercase tracking-wider text-slate-500 px-1">Identify Yourself</label>
                    <div class="relative group">
                        <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors text-xl">person</span>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none bg-slate-50 focus:bg-white text-slate-800"
                               placeholder="Username or your email">
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <label for="password" class="text-xs font-bold uppercase tracking-wider text-slate-500">Our Secret</label>
                        <a href="forgot-password.php" class="text-xs font-bold text-primary hover:underline">Forgot?</a>
                    </div>
                    <div class="relative group">
                        <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors text-xl">key</span>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-12 pr-12 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none bg-slate-50 focus:bg-white text-slate-800"
                               placeholder="Enter the secret password">
                        <button type="button" id="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary transition-colors">
                            <span class="material-icons-round text-xl">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="pt-2 space-y-3">
                    <button type="submit" id="submitBtn" class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-3.5 rounded-xl shadow-lg shadow-primary/30 transform active:scale-95 transition-all flex items-center justify-center gap-3">
                        <span>Open Our World</span>
                        <span class="material-icons-round text-lg">arrow_forward</span>
                    </button>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="register.php" class="w-full inline-flex items-center justify-center gap-2 text-sm font-bold text-primary bg-white border border-primary/20 rounded-xl py-3.5 shadow-sm hover:shadow-md transition-all">
                            <span class="material-icons-round text-base">person_add</span>
                            <span>Create Account</span>
                        </a>
                        <a href="/index.php" class="w-full inline-flex items-center justify-center gap-2 text-sm font-bold text-primary bg-white border border-primary/20 rounded-xl py-3.5 shadow-sm hover:shadow-md transition-all">
                            <span class="material-icons-round text-base">arrow_back</span>
                            <span>Back to Home</span>
                        </a>
                    </div>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400 font-medium">New here? Create an account to get started.</p>
                <div class="flex items-center justify-center gap-1 mt-2 text-primary">
                    <span class="material-icons-round text-xs">favorite</span>
                    <span class="text-[10px] font-bold uppercase tracking-widest">Premium Space</span>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    // Password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        this.querySelector('span').textContent = type === 'password' ? 'visibility' : 'visibility_off';
    });

    // Loading state
    loginForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        submitBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Identifying...</span>
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
