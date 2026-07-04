    </main>

    <!-- Footer / Signature -->
    <footer class="<?php echo isLoggedIn() ? 'fixed bottom-4 right-6 z-40 pointer-events-none' : 'p-8 text-center'; ?> text-slate-400 text-sm">
        <p class="flex items-center justify-center gap-2">
            Built with <span class="material-icons-round text-primary text-xs">favorite</span> for Us & Co.
        </p>
    </footer>

    <!-- Bootstrap JS Bundle (Keep for compatibility) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="js/main.js"></script>
    
    <?php if (isset($page_scripts) && is_array($page_scripts)): ?>
        <?php foreach ($page_scripts as $script_path): ?>
            <script src="<?php echo htmlspecialchars($script_path); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
