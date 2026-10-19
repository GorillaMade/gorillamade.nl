// Progressive enhancement: without JavaScript, the browser submits to form.php normally.
(() => {
    const selector = '#contact-form-container form, #offerte-form-container form';

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches(selector)) return;
        // Let Alpine's wizard validation or the browser prevent an invalid submission.
        if (event.defaultPrevented || !form.checkValidity() && !form.noValidate) return;

        event.preventDefault();

        const container = form.closest('#contact-form-container, #offerte-form-container');
        const feedback = form.querySelector('#contact-feedback, #offerte-feedback');
        const button = form.querySelector('button[type="submit"]');
        if (!container || !feedback) return;

        const previousLabel = button?.textContent;
        if (button) {
            button.disabled = true;
            button.textContent = 'Verzenden…';
        }
        feedback.replaceChildren();

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'HX-Request': 'true',
                    'Accept': 'text/html'
                },
                credentials: 'same-origin'
            });
            const html = await response.text();
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const success = parsed.querySelector('.form-success');
            const error = parsed.querySelector('.form-error');

            if (response.ok && success) {
                container.replaceChildren(document.importNode(success, true));
                container.querySelector('[role="status"]')?.setAttribute('tabindex', '-1');
                container.querySelector('[role="status"]')?.focus();
                return;
            }

            const message = error?.textContent?.trim() ||
                (response.ok
                    ? 'De server gaf geen geldige bevestiging. Controleer of PHP actief is.'
                    : 'Verzenden is mislukt. Probeer het later opnieuw.');
            showError(feedback, message);
        } catch (error) {
            console.error('Formulier verzenden mislukt:', error);
            showError(feedback, 'Geen verbinding met de server. Probeer het opnieuw.');
        } finally {
            if (button?.isConnected) {
                button.disabled = false;
                button.textContent = previousLabel;
            }
        }
    });

    function showError(feedback, message) {
        const paragraph = document.createElement('p');
        paragraph.className = 'form-error';
        paragraph.setAttribute('role', 'alert');
        paragraph.textContent = message;
        feedback.replaceChildren(paragraph);
    }
})();
