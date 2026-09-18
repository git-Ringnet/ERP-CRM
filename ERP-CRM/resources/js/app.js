import './bootstrap';

// Form validation helper
document.addEventListener('DOMContentLoaded', function() {
    // Add loading state to buttons on form submit
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !form.classList.contains('delete-form')) {
                const originalText = submitBtn.innerHTML;
                submitBtn.classList.add('pointer-events-none', 'opacity-75');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
                
                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.classList.remove('pointer-events-none', 'opacity-75');
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
        });
    });

    // Real-time search with debounce
    const searchInputs = document.querySelectorAll('input[name="search"]');
    searchInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.value.length >= 2 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
    });

    // Navigation flags (kept for backward compatibility with existing views)
    window.formChanged = false;
    window.isSubmitting = false;

    // Add tooltips to truncated text
    document.querySelectorAll('.truncate').forEach(el => {
        if (el.scrollWidth > el.clientWidth) {
            el.title = el.textContent;
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });
});
