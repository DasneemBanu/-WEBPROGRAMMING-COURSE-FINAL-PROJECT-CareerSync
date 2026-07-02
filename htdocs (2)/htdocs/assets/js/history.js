// assets/js/history.js
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.btn-delete');

    buttons.forEach(function(button) {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // Stop the link from navigating normally

            const resultDate = this.dataset.date || 'this record';
            const resultCareer = this.dataset.career || '';
            const message = resultCareer 
                ? 'Delete result from ' + resultDate + ' (' + resultCareer + ')?' 
                : 'Delete ' + resultDate + '?';

            if (!confirm(message)) {
                return;
            }

            // Disable clicking again to prevent double-submit
            this.style.pointerEvents = 'none';
            this.textContent = 'Deleting...';

            // Create a form dynamically
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.getAttribute('href'); // Targets delete_result.php

            // Secure Token matching what's stored in session
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = this.dataset.csrf || ''; // Reads data-csrf attribute

            // Record ID
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = this.dataset.id || '';

            form.appendChild(csrfInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        });
    });
});