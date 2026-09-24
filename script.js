// ==========================================
// INPUT TYPE ENFORCEMENT
// Blocks digits in "text" fields and letters in "numbers" fields
// Also shows an inline error message when a blocked key is pressed
// ==========================================
document.addEventListener('DOMContentLoaded', function () {

    // Helper: show an inline error under the input
    function showFieldError(input, message) {
        // Remove any existing error first
        const existing = input.parentNode.querySelector('.field-error');
        if (existing) existing.remove();

        const err = document.createElement('div');
        err.className = 'field-error';
        err.textContent = message;
        err.style.cssText = 'color: #c8102e; font-size: 12px; margin-top: 5px; font-weight: 500;';
        input.parentNode.appendChild(err);

        // Auto-remove the message after 2 seconds
        clearTimeout(input._errorTimer);
        input._errorTimer = setTimeout(() => {
            err.remove();
        }, 2000);
    }

    // Text-only fields: letters, spaces, hyphens, apostrophes
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

    // Number-only fields: digits, spaces, +
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
});