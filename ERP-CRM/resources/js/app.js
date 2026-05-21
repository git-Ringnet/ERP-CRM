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
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
                
                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
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

    // Confirm navigation away from unsaved forms
    window.formChanged = false;
    window.isSubmitting = false;

    // Use event delegation for input changes to support dynamic forms
    document.addEventListener('change', function(e) {
        const input = e.target;
        if (input && input.closest) {
            const form = input.closest('form');
            if (form) {
                // Ignore GET forms (filters/search) and forms with data-no-dirty-check attribute
                const method = (form.getAttribute('method') || 'GET').toUpperCase();
                const isGetForm = method === 'GET';
                const hasNoDirtyCheck = form.hasAttribute('data-no-dirty-check') || form.classList.contains('no-dirty-check');
                
                if (!isGetForm && !hasNoDirtyCheck) {
                    window.formChanged = true;
                }
            }
        }
    });

    // Support tracking standard submits with event delegation
    document.addEventListener('submit', function(e) {
        window.isSubmitting = true;
        // Fallback: if submit is prevented, reset the flag in the next tick
        setTimeout(() => {
            if (e.defaultPrevented) {
                window.isSubmitting = false;
            }
        }, 0);
    });

    // Override HTMLFormElement.prototype.submit to track programmatic submits
    const originalSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function() {
        window.isSubmitting = true;
        originalSubmit.apply(this, arguments);
    };

    window.addEventListener('beforeunload', function(e) {
        if (window.formChanged && !window.isSubmitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

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
