/**
 * GorillaMade progressive enhancement for contact and quote forms.
 * Native POST to /form.php remains available when JavaScript is disabled.
 */
(() => {
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.getAttribute('action') !== '/form.php' || form.method.toLowerCase() !== 'post') return;

        // Alpine's quote wizard validates the steps first. Respect its cancellation.
        if (event.defaultPrevented) return;
        event.preventDefault();

        if (form.dataset.submitting === 'true') return;
        form.dataset.submitting = 'true';

        const submitters = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        const previousDisabled = submitters.map((button) => button.disabled);
        submitters.forEach((button) => { button.disabled = true; });
        const feedback = form.querySelector('#contact-feedback, #offerte-feedback');

        const showError = (message) => {
            const element = document.createElement('p');
            element.className = 'form-error';
            element.setAttribute('role', 'alert');
            element.textContent = message;
            if (feedback) {
                feedback.replaceChildren(element);
                feedback.setAttribute('tabindex', '-1');
                feedback.focus();
            } else {
                form.prepend(element);
                element.setAttribute('tabindex', '-1');
                element.focus();
            }
        };

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'HX-Request': 'true', 'Accept': 'text/html' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            const documentHtml = new DOMParser().parseFromString(await response.text(), 'text/html');
            const result = documentHtml.querySelector(response.ok ? '.form-success' : '.form-error');

            if (!result) throw new Error('Ongeldige formulierrespons');

            if (response.ok) {
                // Preserve the page instead of navigating away to /form.php.
                form.replaceWith(result);
                const heading = result.querySelector('h2');
                if (heading) {
                    heading.setAttribute('tabindex', '-1');
                    heading.focus();
                }
            } else if (feedback) {
                feedback.replaceChildren(result);
                feedback.setAttribute('tabindex', '-1');
                feedback.focus();
            } else {
                showError(result.textContent || 'Verzenden is niet gelukt.');
            }
        } catch (_error) {
            showError('Verzenden is niet gelukt. Probeer het later opnieuw of mail rechtstreeks naar hallo@gorillamade.nl.');
        } finally {
            delete form.dataset.submitting;
            submitters.forEach((button, i) => { button.disabled = previousDisabled[i]; });
        }
    });
})();
