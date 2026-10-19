(() => {
    const selectors = '#contact-form-container form, #offerte-form-container form';
    const requiredQuote = ['services[]', 'project', 'goal', 'audience', 'features', 'content', 'budget', 'timing', 'name', 'email', 'consent'];
    const requiredContact = ['naam', 'email', 'bericht', 'toestemming'];
    const errorText = {
        valueMissing: 'Dit veld is verplicht.',
        typeMismatch: 'Vul een geldig e-mailadres of webadres in.',
        tooShort: 'De ingevulde waarde is te kort.',
        patternMismatch: 'Controleer de ingevulde waarde.'
    };
    const control = (field) => field.closest('.field, .choice') || field.parentElement;
    const messageFor = (field) => {
        if (field.type === 'checkbox' && !field.checked) return 'Bevestig je toestemming.';
        if (field.validity.valueMissing || (field.required && !field.value.trim())) return errorText.valueMissing;
        if (field.validity.typeMismatch) return errorText.typeMismatch;
        if (field.validity.tooShort) return errorText.tooShort;
        if (field.validity.patternMismatch) return errorText.patternMismatch;
        return '';
    };
    function setError(field, message) {
        const wrapper = control(field);
        if (!wrapper) return;
        const id = field.id ? field.id + '-error' : '';
        let error = [...wrapper.children].find((child) => child.classList.contains('form-field-error'));
        if (!message) {
            field.removeAttribute('aria-invalid');
            if (error) error.remove();
            return;
        }
        field.setAttribute('aria-invalid', 'true');
        if (!error) {
            error = document.createElement('span');
            error.className = 'form-field-error';
            error.setAttribute('role', 'alert');
            if (id) error.id = id;
            wrapper.appendChild(error);
        }
        error.textContent = message;
        if (id) {
            const described = (field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
            if (!described.includes(id)) field.setAttribute('aria-describedby', [...described, id].join(' '));
        }
    }
    function checkField(field) {
        const message = messageFor(field);
        setError(field, message);
        return !message;
    }
    function checkForm(form) {
        const quote = !!form.closest('#offerte-form-container');
        const required = quote ? requiredQuote : requiredContact;
        let firstInvalid = null;
        for (const name of required) {
            const fields = [...form.querySelectorAll('[name]')].filter((field) => field.name === name);
            if (!fields.length) continue;
            const field = fields[0];
            const isCheckbox = field.type === 'checkbox';
            const valid = name === 'services[]'
                ? fields.some((item) => item.checked)
                : isCheckbox ? field.checked : !!field.value.trim() && field.checkValidity();
            const message = valid ? '' : (name === 'services[]' ? 'Selecteer minimaal één dienst.' : isCheckbox ? 'Bevestig je toestemming.' : messageFor(field) || 'Dit veld is verplicht.');
            setError(field, message);
            if (!valid && !firstInvalid) firstInvalid = field;
        }
        for (const field of form.querySelectorAll('input[type=email], input[type=url]')) {
            if (field.value.trim() && !checkField(field) && !firstInvalid) firstInvalid = field;
        }
        if (firstInvalid && quote) {
            const panels = [...form.querySelectorAll('[data-wizard-step]')];
            const panel = firstInvalid.closest('[data-wizard-step]');
            const wizard = form.closest('#offerte-form-container');
            if (panel && window.Alpine && wizard) {
                const data = window.Alpine.$data(wizard);
                data.step = Number(panel.dataset.wizardStep);
            }
        }
        if (firstInvalid) {
            requestAnimationFrame(() => firstInvalid.focus({preventScroll: false}));
            return false;
        }
        return true;
    }
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches(selectors)) return;
        if (!checkForm(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);
    document.addEventListener('focusout', (event) => {
        const field = event.target;
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) return;
        if (!field.closest(selectors) || field.type === 'hidden' || field.name === 'honeypot_field') return;
        if (field.type === 'checkbox' && field.name === 'services[]') return;
        checkField(field);
    });
    document.addEventListener('input', (event) => {
        const field = event.target;
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) return;
        if (!field.closest(selectors)) return;
        if (field.getAttribute('aria-invalid') === 'true') checkField(field);
        if (field.name === 'services[]') {
            const group = field.closest('form');
            const first = group?.querySelector('[name="services[]"]');
            if (first && [...group.querySelectorAll('[name="services[]"]')].some((item) => item.checked)) setError(first, '');
        }
    });
    document.addEventListener('change', (event) => {
        const field = event.target;
        if (field instanceof HTMLInputElement && field.closest(selectors) && field.type === 'checkbox' && field.name !== 'services[]') checkField(field);
    });
})();