// ==========================================
// STICKY TOPBAR SHADOW ON SCROLL
// ==========================================
document.addEventListener('scroll', function () {
    const topbar = document.querySelector('.topbar');
    if (!topbar) return;

    if (window.scrollY > 10) {
        topbar.classList.add('scrolled');
    } else {
        topbar.classList.remove('scrolled');
    }
}, { passive: true });

// ==========================================
// ESC KEY CLOSES MODALS
// ==========================================
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        ['guardModal', 'editGuardModal', 'visitorModal', 'editVisitorModal'].forEach(id => {
            const modal = document.getElementById(id);
            if (modal && modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        });
    }
});

// ==========================================
// PASSWORD SHOW/HIDE TOGGLE
// ==========================================
document.addEventListener('DOMContentLoaded', function () {
    const eye = document.querySelector(".eye");
    const password = document.querySelector("input[type='password']");

    if (eye && password) {
        eye.addEventListener("click", () => {
            if (password.type === "password") {
                password.type = "text";
                eye.classList.remove("fa-eye");
                eye.classList.add("fa-eye-slash");
            } else {
                password.type = "password";
                eye.classList.remove("fa-eye-slash");
                eye.classList.add("fa-eye");
            }
        });
    }
});

// ==========================================
// INPUT TYPE ENFORCEMENT
// Blocks digits in "text" fields and letters in "numbers" fields
// Also shows an inline error message when a blocked key is pressed
// ==========================================
document.addEventListener('DOMContentLoaded', function () {

    // Helper: show an inline error under the input
    function showFieldError(input, message) {
        const existing = input.parentNode.querySelector('.field-error');
        if (existing) existing.remove();

        const err = document.createElement('div');
        err.className = 'field-error';
        err.textContent = message;
        err.style.cssText = 'color: #c8102e; font-size: 12px; margin-top: 5px; font-weight: 500;';
        input.parentNode.appendChild(err);

        clearTimeout(input._errorTimer);
        input._errorTimer = setTimeout(() => {
            err.remove();
        }, 2000);
    }

    // Text-only fields
    document.querySelectorAll('input[data-type="text"]').forEach(function (input) {
        input.addEventListener('keypress', function (e) {
            const char = String.fromCharCode(e.which);
            if (!/[A-Za-z\s\-']/.test(char)) {
                e.preventDefault();
                showFieldError(input, 'Numbers are not allowed in this field');
            }
        });

        input.addEventListener('paste', function (e) {
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^[A-Za-z\s\-']+$/.test(pasted)) {
                e.preventDefault();
                showFieldError(input, 'Only letters are allowed in this field');
            }
        });
    });

    // Number-only fields
    document.querySelectorAll('input[data-type="numbers"]').forEach(function (input) {
        input.addEventListener('keypress', function (e) {
            const char = String.fromCharCode(e.which);
            if (!/[0-9+\s]/.test(char)) {
                e.preventDefault();
                showFieldError(input, 'Letters are not allowed in this field');
            }
        });

        input.addEventListener('paste', function (e) {
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^[0-9+\s]+$/.test(pasted)) {
                e.preventDefault();
                showFieldError(input, 'Only numbers are allowed in this field');
            }
        });
    });

    // Alphanumeric-only fields
    document.querySelectorAll('input[data-type="alphanumeric"]').forEach(function (input) {
        input.addEventListener('keypress', function (e) {
            const char = String.fromCharCode(e.which);
            if (!/[A-Za-z0-9]/.test(char)) {
                e.preventDefault();
                showFieldError(input, 'Only letters and numbers are allowed');
            }
        });

        input.addEventListener('paste', function (e) {
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^[A-Za-z0-9]+$/.test(pasted)) {
                e.preventDefault();
                showFieldError(input, 'Only letters and numbers are allowed');
            }
        });
    });
});