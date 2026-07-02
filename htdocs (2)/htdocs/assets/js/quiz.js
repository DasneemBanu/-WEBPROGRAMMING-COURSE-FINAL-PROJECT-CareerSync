// assets/js/quiz.js
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('quiz-form');
    if (!form) {
        console.error('Quiz form not found!');
        return;
    }

    // Clear error styling on interaction
    form.querySelectorAll('fieldset.question').forEach(function(fs) {
        fs.addEventListener('change', function() {
            this.style.borderColor = 'var(--border)';
            // Remove error message if exists
            const errorMsg = this.querySelector('.error-msg');
            if (errorMsg) errorMsg.remove();
        });
    });

    form.addEventListener('submit', function (e) {
        // Support both data-required="1" and data-required="true"
        const fieldsets = form.querySelectorAll('fieldset[data-required="1"], fieldset[data-required="true"]');
        let firstUnanswered = null;

        for (let i = 0; i < fieldsets.length; i++) {
            const fs = fieldsets[i];
            const radios = fs.querySelectorAll('input[type="radio"]');
            const checkboxes = fs.querySelectorAll('input[type="checkbox"]');

            let answered = false;

            if (radios.length > 0) {
                // Single choice: at least one radio checked
                for (let j = 0; j < radios.length; j++) {
                    if (radios[j].checked) { answered = true; break; }
                }
            } else if (checkboxes.length > 0) {
                // Multi choice: at least one checkbox checked
                for (let j = 0; j < checkboxes.length; j++) {
                    if (checkboxes[j].checked) { answered = true; break; }
                }
            } else {
                // Scale or text: always has value, consider answered
                answered = true;
            }

            if (!answered) {
                fs.style.borderColor = '#ff4444';
                if (!firstUnanswered) firstUnanswered = fs;
            } else {
                fs.style.borderColor = 'var(--border)';
            }
        }

        if (firstUnanswered) {
            e.preventDefault();
            firstUnanswered.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Show inline error
            let errorMsg = firstUnanswered.querySelector('.error-msg');
            if (!errorMsg) {
                errorMsg = document.createElement('div');
                errorMsg.className = 'error-msg';
                errorMsg.style.cssText = 'color: #ff4444; font-size: 13px; margin-top: 8px;';
                errorMsg.textContent = '⚠ Please answer this question before submitting.';
                firstUnanswered.appendChild(errorMsg);
            }
            return false;
        }

        // Remove any old error messages from answered questions
        form.querySelectorAll('.error-msg').forEach(function(el) { el.remove(); });
    });
});