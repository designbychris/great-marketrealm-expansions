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

    const friendlyValue = (control, value) => {
        if (control === 'size') return typeof value === 'string' ? value : (value && typeof value === 'object' && typeof value.value === 'string' ? value.value : '');
        if (control === 'walking-speed') return typeof value === 'string' ? value : (value && typeof value === 'object' && Number.isInteger(value.walk) ? String(value.walk) : '');
        if (control === 'string-list' || control === 'canonical-string-list') return typeof value === 'string' ? value : (Array.isArray(value) ? value.filter((item) => typeof item === 'string').join(', ') : '');
        if (control === 'progression-lines' && typeof value === 'string') return value;
        if (control === 'progression-lines' && Array.isArray(value)) {
            return value.filter((row) => row && typeof row === 'object' && !Array.isArray(row) && Number.isInteger(row.level)).map((row) => {
                const features = Array.isArray(row.features) ? row.features.filter((feature) => typeof feature === 'string').join(', ') : '';
                return `${row.level} | ${features}`;
            }).join('\n');
        }
        if ((control === 'trait-lines' || control === 'feature-lines') && typeof value === 'string') return value;
        if ((control === 'trait-lines' || control === 'feature-lines') && Array.isArray(value)) {
            return value.filter((feature) => feature && typeof feature === 'object' && !Array.isArray(feature)).map((feature) => {
                const key = typeof feature.key === 'string' ? feature.key : '';
                const name = typeof feature.name === 'string' ? feature.name : '';
                const description = typeof feature.description === 'string' ? feature.description : '';
                return `${key} | ${name}${description ? ` | ${description}` : ''}`;
            }).join('\n');
        }
        if (control === 'proficiency-groups' && typeof value === 'string') return value;
        if (control === 'proficiency-groups' && value && typeof value === 'object' && !Array.isArray(value)) {
            return Object.entries(value).filter(([, entries]) => Array.isArray(entries)).map(([group, entries]) => {
                return `${group} | ${entries.filter((entry) => typeof entry === 'string').join(', ')}`;
            }).join('\n');
        }
        return value === undefined || value === null ? '' : String(value);
    };

    const makeDatalist = (id, values) => {
        if (document.getElementById(id)) return;
        const list = document.createElement('datalist');
        list.id = id;
        values.forEach((value) => list.append(new Option('', value)));
        document.body.append(list);
    };

    const makeField = (field, value) => {
        const label = document.createElement('label');
        label.className = 'gmrexp-reading-room__schema-field';
        label.setAttribute('data-schema-field', field.name);
        label.setAttribute('data-schema-control', field.control || 'schema');

        const heading = document.createElement('span');
        heading.append(document.createTextNode(`${field.label} `));
        const required = document.createElement('strong');
        required.setAttribute('aria-hidden', 'true');
        required.textContent = '*';
        heading.append(required, document.createTextNode(' '));
        const type = document.createElement('small');
        type.textContent = `${field.type}${field.allow_empty ? ' · may be empty' : ''}`;
        heading.append(type);
        label.append(heading);

        const name = `gmrexp_review_schema[${field.name}]`;
        const controlKind = field.control || 'schema';
        let control;

        if (controlKind === 'creature-type') {
            control = document.createElement('input');
            control.type = 'text';
            control.setAttribute('list', 'gmrexp-creature-types');
            control.placeholder = 'e.g. Humanoid';
            control.value = friendlyValue(controlKind, value);
            makeDatalist('gmrexp-creature-types', ['Humanoid','Fey','Construct','Undead','Monstrosity','Elemental','Plant','Ooze','Aberration','Beast','Celestial','Dragon','Fiend','Giant']);
            label.append(control, makeHelp('Choose the creature classification intended by the source. The Review Desk does not choose one for you.'));
        } else if (controlKind === 'size') {
            control = document.createElement('input');
            control.type = 'text';
            control.setAttribute('list', 'gmrexp-creature-sizes');
            control.placeholder = 'e.g. Medium';
            control.value = friendlyValue(controlKind, value);
            makeDatalist('gmrexp-creature-sizes', ['Tiny','Small','Medium','Large','Huge','Gargantuan']);
            label.append(control, makeHelp('Enter the fixed size. More complex size choices remain available in Advanced content data.'));
        } else if (controlKind === 'walking-speed') {
            control = document.createElement('input');
            control.type = 'number'; control.min = '1'; control.step = '1'; control.placeholder = '30';
            control.value = friendlyValue(controlKind, value);
            label.append(control, makeHelp('Walking speed in feet. Optional movement modes remain available in Advanced content data.'));
        } else if (controlKind === 'string-list') {
            control = document.createElement('input');
            control.type = 'text'; control.placeholder = 'e.g. Common, Market Tongue';
            control.value = friendlyValue(controlKind, value);
            label.append(control, makeHelp('Separate languages with commas. Only enter languages established for this race.'));
        } else if (controlKind === 'canonical-string-list') {
            control = document.createElement('input');
            control.type = 'text'; control.placeholder = 'e.g. Strength, Constitution';
            control.value = friendlyValue(controlKind, value);
            label.append(control, makeHelp('Separate canonical values with commas. The Review Desk preserves exactly what the Keeper enters.'));
        } else if (controlKind === 'progression-lines') {
            control = document.createElement('textarea'); control.rows = 8; control.spellcheck = false;
            control.value = friendlyValue(controlKind, value);
            label.append(control, makeHelp('One level per line: 3 | feature-key or 6 | feature-key, second-feature. Classes must still define every level through Max Level.'));
        } else if (controlKind === 'trait-lines') {
            control = document.createElement('textarea'); control.rows = 7; control.spellcheck = false;
            control.value = friendlyValue(controlKind, value);
            label.append(control, makeHelp('One trait per line: canonical-key | Trait Name | Description. The Keeper supplies the key.'));
        } else if (controlKind === 'proficiency-groups') {
            control = document.createElement('textarea'); control.rows = 5; control.spellcheck = false;
            control.value = friendlyValue(controlKind, value);
            label.append(control, makeHelp("One proficiency group per line: skills | Athletics, Survival or tools | Cook's Utensils."));
        } else if (controlKind === 'feature-lines') {
            control = document.createElement('textarea'); control.rows = 7; control.spellcheck = false;
            control.value = friendlyValue(controlKind, value);
            label.append(control, makeHelp('One feature per line: canonical-key | Feature Name | Description. The Keeper supplies the key.'));
        } else if (controlKind === 'empty-array-allowed') {
            control = document.createElement('textarea'); control.rows = 4; control.spellcheck = false;
            control.value = prettyJson(value, '[]');
            label.append(control, makeHelp('This canonical field may be empty. Leave [] (or blank) when the source specifies no starting equipment.'));
        } else if (field.type === 'map' || field.type === 'array') {
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
        control.required = field.required !== false && !field.allow_empty;
        if (!control.parentNode) label.append(control);
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
            const monsterContract = form.querySelector('[data-gmrexp-monster-contract]');
            if (monsterContract) monsterContract.hidden = type !== 'monster';
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

            help.textContent = 'Complete the required fields below before accepting this classification. The Review Desk uses friendly controls where the canonical shape is known; remaining complex fields use JSON.';
            fields.forEach((field) => fieldsContainer.append(makeField(field, values[field.name])));
        };

        typeSelect.addEventListener('change', render);
        render();
    };

    const initialiseBulkReview = (form) => {
        const checkboxes = Array.from(document.querySelectorAll('[data-gmrexp-review-select]'));
        const count = form.querySelector('[data-gmrexp-bulk-count]');
        const all = form.querySelector('[data-gmrexp-select-all]');
        const none = form.querySelector('[data-gmrexp-select-none]');
        const update = () => {
            if (count) count.textContent = String(checkboxes.filter((checkbox) => checkbox.checked).length);
        };
        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));
        if (all) all.addEventListener('click', () => { checkboxes.forEach((checkbox) => { checkbox.checked = true; }); update(); });
        if (none) none.addEventListener('click', () => { checkboxes.forEach((checkbox) => { checkbox.checked = false; }); update(); });
        form.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            const message = submitter && submitter.getAttribute('data-gmrexp-confirm');
            if (message && !window.confirm(message)) event.preventDefault();
        });
        update();
    };

    const boot = () => {
        document.querySelectorAll('[data-gmrexp-review-form]').forEach(initialise);
        document.querySelectorAll('[data-gmrexp-bulk-review]').forEach(initialiseBulkReview);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
