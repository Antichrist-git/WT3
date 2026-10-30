// Пошаговая валидация God Form. Не требует npm: работает с готовым public/js/index.js.
// Серверные правила GodController::validated() всегда остаются окончательными.
(function () {
    if (window.__wt3LiveValidationInitialized) return;
    const form = document.querySelector('form.god-form');
    if (!form) return;
    window.__wt3LiveValidationInitialized = true;

    const fields = Array.from(form.querySelectorAll('input[name], textarea[name], select[name]'))
        .filter(field => ['name', 'type', 'published_at', 'short_description', 'description', 'fact', 'image'].includes(field.name));
    const nameField = form.elements.namedItem('name');
    const submitButton = form.querySelector('button[type="submit"]');
    const nameWarning = document.getElementById('nameTypingNotice');
    const nameStatus = document.getElementById('nameLiveStatus');
    const initialMessages = new Map(fields.map(field => [field, document.getElementById(field.id + 'Error')?.textContent.trim() || 'Некорректное значение.']));
    const touched = new WeakSet();
    let duplicateMessage = '';
    let nameVerified = false;
    let nameRequestId = 0;
    let nameController = null;
    let nameTimer = null;
    let imageDimensionError = '';
    let imageTask = Promise.resolve();
    let submitting = false;
    let composingName = false;

    function textLength(value) { return Array.from(value).length; }
    function nameIsComplete(value) {
        return /^(?:[А-Яа-яЁё]+(?:[ -][А-Яа-яЁё]+)*|[A-Za-z]+(?:[ -][A-Za-z]+)*)$/u.test(value);
    }
    function nameIsPossible(value) {
        return /^(?:[А-Яа-яЁё]+(?:[ -][А-Яа-яЁё]+)*[ -]?|[A-Za-z]+(?:[ -][A-Za-z]+)*[ -]?)?$/u.test(value);
    }
    function showNameWarning(message) {
        if (!nameWarning) return;
        nameWarning.textContent = message;
        nameWarning.hidden = !message;
    }
    function showNameStatus(message, kind) {
        if (!nameStatus) return;
        nameStatus.textContent = message;
        nameStatus.hidden = !message;
        nameStatus.dataset.state = kind || '';
    }
    function localError(field) {
        const value = field.value.trim();
        const length = textLength(value);
        switch (field.name) {
            case 'name':
                if (!value) return 'Введите имя бога.';
                if (length < 2) return 'Имя должно содержать минимум 2 буквы.';
                if (length > 60) return 'Имя не должно быть длиннее 60 символов.';
                if (!nameIsComplete(value)) return 'Только русские ИЛИ английские буквы, одиночные пробелы и дефисы. Без цифр и специальных символов.';
                return duplicateMessage;
            case 'type':
                if (!value || !Array.from(field.options).some(option => option.value === value && value)) return 'Выберите сферу покровительства из списка.';
                return '';
            case 'published_at': {
                if (!value) return 'Выберите дату публикации.';
                const date = new Date(value + 'T12:00:00');
                if (!/^\d{4}-\d{2}-\d{2}$/.test(value) || Number.isNaN(date.getTime()) || date.getFullYear() !== Number(value.slice(0, 4)) || date.getMonth() + 1 !== Number(value.slice(5, 7)) || date.getDate() !== Number(value.slice(8, 10))) return 'Укажите существующую дату.';
                if (value < field.min) return 'Дата не может быть раньше 01.01.2000.';
                if (value > field.max) return 'Нельзя указывать дату в будущем.';
                return '';
            }
            case 'short_description':
            case 'description':
            case 'fact': {
                const minimum = Number(field.minLength);
                const maximum = Number(field.maxLength);
                if (!value) return 'Это поле обязательно.';
                if (length < minimum) return `Слишком короткий текст: минимум ${minimum} символов, сейчас ${length}.`;
                if (length > maximum) return `Превышен лимит: максимум ${maximum} символов.`;
                if (!/\p{L}/u.test(value)) return 'Введите осмысленный текст с буквами, не только цифры или знаки.';
                return '';
            }
            case 'image': {
                const file = field.files?.[0];
                if (!file) return field.required ? 'Загрузите фотографию бога.' : '';
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) return 'Разрешены только JPG, PNG и WebP.';
                if (file.size > 5 * 1024 * 1024) return 'Фотография не должна превышать 5 МБ.';
                return imageDimensionError;
            }
            default: return '';
        }
    }

    function render(field) {
        // Стираем старый customValidity, затем применяем наши актуальные правила.
        field.setCustomValidity('');
        const error = localError(field);
        field.setCustomValidity(error);
        if (!touched.has(field)) return !error;
        const invalid = Boolean(error);
        field.classList.toggle('is-invalid', invalid);
        field.classList.toggle('is-valid', !invalid && (field !== nameField || nameVerified));
        field.setAttribute('aria-invalid', String(invalid));
        const feedback = document.getElementById(field.id + 'Error');
        if (feedback) feedback.textContent = invalid ? error : initialMessages.get(field);
        return !invalid;
    }

    // Фильтруем каждую вставляемую букву/символ: не принимаем цифры, emoji, знаки,
    // двойные дефисы/пробелы и смешение кириллицы с латиницей.
    function cleanName(raw) {
        let result = '';
        for (const character of raw.replace(/[\u00a0\u2007\u202f\t\r\n]/g, ' ')) {
            if (nameIsPossible(result + character)) result += character;
        }
        return result;
    }
    function applyNameText(inserted) {
        const start = nameField.selectionStart ?? nameField.value.length;
        const end = nameField.selectionEnd ?? start;
        const before = nameField.value.slice(0, start);
        const after = nameField.value.slice(end);
        let accepted = '';
        for (const character of inserted.replace(/[\u00a0\u2007\u202f\t\r\n]/g, ' ')) {
            if (nameIsPossible(before + accepted + character + after)) accepted += character;
        }
        nameField.setRangeText(accepted, start, end, 'end');
        nameField.dispatchEvent(new Event('input', { bubbles: true }));
        if (accepted !== inserted) showNameWarning('Цифры, спецсимволы, повторные разделители и смешанные алфавиты запрещены. Недопустимые символы не введены.');
    }
    nameField.addEventListener('beforeinput', event => {
        if (composingName || event.isComposing || !event.cancelable || !event.inputType.startsWith('insert') || !event.data) return;
        const start = nameField.selectionStart ?? nameField.value.length;
        const end = nameField.selectionEnd ?? start;
        const candidate = nameField.value.slice(0, start) + event.data + nameField.value.slice(end);
        if (!nameIsPossible(candidate) || textLength(candidate) > 60) {
            event.preventDefault();
            showNameWarning('Имя: только буквы одного алфавита, пробел и дефис. Цифры и спецсимволы не допускаются.');
        }
    });
    nameField.addEventListener('paste', event => {
        if (!event.clipboardData) return;
        event.preventDefault();
        applyNameText(event.clipboardData.getData('text'));
    });
    nameField.addEventListener('drop', event => {
        event.preventDefault();
        if (event.dataTransfer) applyNameText(event.dataTransfer.getData('text'));
    });
    nameField.addEventListener('compositionstart', () => { composingName = true; });
    nameField.addEventListener('compositionend', () => { composingName = false; sanitizeName(); });
    function sanitizeName() {
        if (composingName) return false;
        const original = nameField.value;
        if (!nameIsPossible(original)) {
            const caret = nameField.selectionStart ?? original.length;
            const cleaned = cleanName(original);
            nameField.value = cleaned.slice(0, 60);
            nameField.setSelectionRange(Math.min(caret, nameField.value.length), Math.min(caret, nameField.value.length));
            showNameWarning('Недопустимые символы автоматически удалены.');
            return true;
        }
        return false;
    }

    async function checkNameUnique() {
        clearTimeout(nameTimer);
        const value = nameField.value.trim();
        if (!value || !nameIsComplete(value) || textLength(value) < 2 || textLength(value) > 60) {
            nameVerified = false;
            return false;
        }
        const ticket = ++nameRequestId;
        nameController?.abort();
        nameController = new AbortController();
        showNameStatus('Проверяем, свободно ли имя…', 'loading');
        const url = new URL(form.dataset.checkNameUrl, window.location.href);
        url.searchParams.set('name', value);
        if (form.dataset.editingId) url.searchParams.set('exclude_id', form.dataset.editingId);
        try {
            const response = await fetch(url, { signal: nameController.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Сервис проверки временно недоступен');
            const result = await response.json();
            if (ticket !== nameRequestId || nameField.value.trim() !== value) return false;
            nameVerified = Boolean(result.available);
            duplicateMessage = result.available ? '' : (result.message || 'Имя уже занято.');
            showNameStatus(result.available ? 'Имя свободно.' : duplicateMessage, result.available ? 'success' : 'error');
            render(nameField);
            return nameVerified;
        } catch (error) {
            if (ticket !== nameRequestId) return false;
            if (error.name === 'AbortError') return false;
            // При недоступном AJAX Laravel всё равно проверит имя на сервере при сохранении.
            duplicateMessage = '';
            nameVerified = false;
            showNameStatus('Не удалось проверить имя онлайн. При сохранении его проверит сервер.', 'warning');
            render(nameField);
            return true;
        }
    }

    function scheduleNameCheck() {
        clearTimeout(nameTimer);
        nameController?.abort();
        ++nameRequestId;
        duplicateMessage = '';
        nameVerified = false;
        if (touched.has(nameField) && !localError(nameField)) {
            showNameStatus('Проверим имя через мгновение…', 'loading');
            nameTimer = setTimeout(checkNameUnique, 450);
        } else showNameStatus('', '');
    }

    fields.forEach(field => {
        // Ошибки сервера, вернувшиеся после отправки формы, остаются видны до изменения поля.
        if (field.classList.contains('is-invalid')) touched.add(field);
        field.addEventListener('blur', () => {
            if (field === nameField) nameField.value = nameField.value.trim().replace(/\s+/g, ' ');
            touched.add(field);
            render(field);
            if (field === nameField && !localError(field)) checkNameUnique();
        });
        const handler = () => {
            if (field === nameField) {
                const corrected = sanitizeName();
                if (!corrected) showNameWarning('');
                scheduleNameCheck();
            }
            if (touched.has(field)) render(field);
        };
        field.addEventListener(field.type === 'file' || field.tagName === 'SELECT' ? 'change' : 'input', handler);
    });

    const imageField = form.elements.namedItem('image');
    imageField.addEventListener('change', () => {
        touched.add(imageField);
        imageDimensionError = '';
        const file = imageField.files?.[0];
        if (!file || localError(imageField)) { render(imageField); return; }
        imageTask = new Promise(resolve => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                if (imageField.files?.[0] === file && img.naturalWidth * img.naturalHeight > 12_000_000) {
                    imageDimensionError = 'Слишком большое разрешение: максимум 12 мегапикселей.';
                }
                URL.revokeObjectURL(url);
                render(imageField);
                resolve();
            };
            img.onerror = () => {
                if (imageField.files?.[0] === file) imageDimensionError = 'Не удалось прочитать изображение.';
                URL.revokeObjectURL(url);
                render(imageField);
                resolve();
            };
            img.src = url;
        });
    });

    // Проверяем последовательно все поля. Запрещаем отправку при ошибках, не полагаясь
    // на один лишь HTML5 (сервер перепроверит всё независимо от JavaScript).
    form.addEventListener('submit', async event => {
        event.preventDefault();
        event.stopImmediatePropagation(); // Старая собранная версия JS не должна дублировать проверку.
        if (submitting) return;
        nameController?.abort();
        clearTimeout(nameTimer);
        let firstInvalid = null;
        for (const field of fields) {
            touched.add(field);
            if (!render(field) && !firstInvalid) firstInvalid = field;
        }
        if (firstInvalid) { firstInvalid.focus(); return; }
        submitting = true;
        if (submitButton) { submitButton.disabled = true; submitButton.dataset.originalText = submitButton.textContent; submitButton.textContent = 'Проверяем данные…'; }
        try {
            await imageTask;
            const nameOkay = await checkNameUnique();
            if (!nameOkay || fields.some(field => !render(field))) {
                const invalid = fields.find(field => !field.checkValidity());
                invalid?.focus();
                return;
            }
            HTMLFormElement.prototype.submit.call(form);
        } finally {
            submitting = false;
            if (submitButton) { submitButton.disabled = false; submitButton.textContent = submitButton.dataset.originalText; }
        }
    }, true);
})();
