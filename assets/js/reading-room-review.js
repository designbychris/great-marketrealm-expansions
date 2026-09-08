(() => {
    'use strict';

    const parseJson = (value, fallback = {}) => {
        try {
            const parsed = JSON.parse(value || '');
            return parsed && typeof parsed === 'object' ? parsed : fallback;
        } catch (_) {
            return fallback;
        }
    };

    const prettyJson = (value, fallback) => {
        if (value === undefined || value === null || value === '') {
            return fallback;
        }
        if (typeof value === 'string') {
            return value;
        }
        try {
            return JSON.stringify(value, null, 2);
        } catch (_) {
            return fallback;
        }
    };

    const rememberCurrentValues = (container, values) => {
        container.querySelectorAll('[data-schema-field]').forEach((label) => {
            const field = label.getAttribute('data-schema-field');
            const control = label.querySelector('input, select, textarea');
            if (!field || !control) return;

            if (control.tagName === 'SELECT' && (control.value === '1' || control.value === '0')) {
                values[field] = control.value === '1';
            } else {
                values[field] = control.value;
            }
        });
    };

    const makeHelp = (text) => {
        const help = document.createElement('small');
        help.className = 'gmrexp-reading-room__field-help';
        help.textContent = text;
        return help;
    };

    const makeField = (field, value) => {
        const label = document.createElement('label');
        label.className = 'gmrexp-reading-room__schema-field';
        label.setAttribute('data-schema-field', field.name);

        const heading = document.createElement('span');
        heading.append(document.createTextNode(`${field.label} `));
        const required = document.createElement('strong');
        required.setAttribute('aria-hidden', 'true');
        required.textContent = '*';
        heading.append(required, document.createTextNode(' '));
        const type = document.createElement('small');
        type.textContent = field.type;
        heading.append(type);
        label.append(heading);

        const name = `gmrexp_review_schema[${field.name}]`;
        let control;

        if (field.type === 'map' || field.type === 'array') {
            control = document.createElement('textarea');
            control.rows = 5;
            control.spellcheck = false;
            control.value = prettyJson(value, field.type === 'map' ? '{}' : '[]');
            label.append(control);
            label.append(makeHelp(field.type === 'map'
                ? 'Enter a JSON object, for example {"walk": 30}.'
                : 'Enter a JSON array, for example ["Common"].'));
        } else if (field.type === 'boolean') {
            control = document.createElement('select');
            const choose = new Option('Choose…', '');
            const yes = new Option('Yes', '1');
            const no = new Option('No', '0');
            control.add(choose, yes, no);
            if (value === true || value === '1') control.value = '1';
            if (value === false || value === '0') control.value = '0';
        } else {
            control = document.createElement('input');
            if (field.type === 'integer' || field.type === 'number') {
                control.type = 'number';
                control.step = field.type === 'number' ? 'any' : '1';
            } else {
                control.type = 'text';
            }
            control.value = value === undefined || value === null ? '' : String(value);
        }

        control.name = name;
        control.required = true;
        label.insertBefore(control, label.querySelector('.gmrexp-reading-room__field-help'));
        return label;
    };

    const initialise = (form) => {
        const typeSelect = form.querySelector('[data-gmrexp-review-type]');
        const wrapper = form.querySelector('[data-gmrexp-schema-requirements]');
        const fieldsContainer = form.querySelector('[data-gmrexp-schema-fields]');
        const count = form.querySelector('[data-gmrexp-schema-count]');
        const help = form.querySelector('[data-gmrexp-schema-help]');
        if (!typeSelect || !wrapper || !fieldsContainer || !count || !help) return;

        const schemas = parseJson(wrapper.getAttribute('data-schema'), {});
        const values = parseJson(wrapper.getAttribute('data-existing'), {});

        const render = () => {
            rememberCurrentValues(fieldsContainer, values);
            fieldsContainer.innerHTML = '';

            const type = typeSelect.value;
            const fields = Array.isArray(schemas[type]) ? schemas[type] : [];
            count.textContent = `${fields.length} required`;

            if (!type) {
                help.textContent = 'Choose a content type and the Review Desk will show its required canonical fields here.';
                return;
            }

            if (fields.length === 0) {
                help.textContent = 'This content type has no additional required fields beyond Name. Optional schema fields remain available in Advanced content data.';
                return;
            }

            help.textContent = 'Complete the required fields below before accepting this classification. Complex map/array fields use JSON.';
            fields.forEach((field) => fieldsContainer.append(makeField(field, values[field.name])));
        };

        typeSelect.addEventListener('change', render);
        render();
    };

    const boot = () => {
        document.querySelectorAll('[data-gmrexp-review-form]').forEach(initialise);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
