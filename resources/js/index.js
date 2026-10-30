import $ from 'jquery';
import { Modal } from 'bootstrap';

window.$ = window.jQuery = $;
window.__wt3EnhancedBundle = true; // Сборка уже включает новую логику форм и Modal.

// Фильтры работают по уже полученным из БД карточкам.
function filterGods() {
    const query = String($('#godSearch').val() || '').trim().toLocaleLowerCase('ru');
    const type = $('#godType').val();
    let count = 0;
    $('.god-column').each(function () {
        const visible = this.dataset.search.toLocaleLowerCase('ru').includes(query)
            && (!type || this.dataset.type === type);
        this.hidden = !visible;
        if (visible) count++;
    });
    $('#resultCount').text(`Показано: ${count}`);
    $('#noResults').prop('hidden', count > 0);
}
$('#godSearch').on('input', filterGods);
$('#godType').on('change', filterGods);
$('#resetFilters').on('click', () => {
    $('#godSearch').val('');
    $('#godType').val('');
    filterGods();
    document.getElementById('godSearch')?.focus();
});

// HTML5-проверка помогает сразу, но все правила обязательно повторяются на сервере.
document.querySelectorAll('.needs-validation').forEach((form) => {
    const textFields = form.querySelectorAll('[data-requires-letters]');
    const validateMeaningfulText = (field) => {
        const value = field.value.trim();
        const hasLetters = /\p{L}/u.test(value);
        field.setCustomValidity(value && !hasLetters
            ? 'Поле должно содержать буквы, а не только цифры или знаки.' : '');
    };
    textFields.forEach((field) => {
        field.addEventListener('input', () => validateMeaningfulText(field));
        validateMeaningfulText(field);
    });
    form.addEventListener('submit', (event) => {
        textFields.forEach(validateMeaningfulText);
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.querySelector(':invalid')?.focus();
        }
        form.classList.add('was-validated');
    });
    form.querySelectorAll('input,textarea,select').forEach((field) => {
        field.addEventListener('input', () => {
            if (field.validity.valid) field.classList.remove('is-invalid');
        });
    });
});

// Удаление требует подтверждения; форма защищена CSRF и отправляет DELETE.
document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const name = form.dataset.godName || 'этого бога';
        if (!window.confirm(`Удалить ${name} из коллекции? Запись сохранится в БД (Soft Delete).`)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-counter-for]').forEach((counter) => {
    const field = document.getElementById(counter.dataset.counterFor);
    function updateCounter() { counter.textContent = `${field.value.length} / ${field.maxLength}`; }
    field.addEventListener('input', updateCounter);
    updateCounter();
});

const imageInput = document.getElementById('image');
if (imageInput) {
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('imagePlaceholder');
    const original = preview.getAttribute('src');
    let objectUrl;
    function previewImage() {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        const file = imageInput.files[0];
        const valid = !file || (['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 5 * 1024 * 1024);
        imageInput.setCustomValidity(valid ? '' : 'Выберите JPG, PNG или WebP размером до 5 МБ.');
        imageInput.classList.toggle('is-invalid', !valid);
        if (!valid) document.getElementById('imageError').textContent = imageInput.validationMessage;
        else document.getElementById('imageError').textContent = 'Выберите изображение JPG, PNG или WebP до 5 МБ.';
        objectUrl = file && valid ? URL.createObjectURL(file) : null;
        const source = objectUrl || original;
        if (source) preview.src = source;
        else preview.removeAttribute('src');
        preview.hidden = !source;
        placeholder.hidden = !!source;
    }
    imageInput.addEventListener('change', previewImage);
}

const modalElement = document.getElementById('godModal');
if (modalElement) {
    const modal = new Modal(modalElement);
    const body = document.getElementById('godModalBody');
    const title = document.getElementById('godModalTitle');
    const edit = document.getElementById('godModalEdit');
    const deleteForm = document.getElementById('godModalDeleteForm');
    let activeLink;
    let controller;

    async function openGod(link) {
        activeLink = link;
        controller?.abort();
        controller = new AbortController();
        const signal = controller.signal;
        const card = link.closest('.god-card');
        const actionLink = card.querySelector('.card-more');
        const name = card.querySelector('.card-title').textContent.trim();
        title.textContent = name;
        edit.href = actionLink.dataset.editUrl;
        deleteForm.action = actionLink.dataset.deleteUrl;
        deleteForm.dataset.godName = name;
        body.textContent = 'Загружаем историю…';
        body.setAttribute('aria-busy', 'true');
        modal.show();
        try {
            const response = await fetch(link.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                signal,
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const html = await response.text();
            if (!signal.aborted) {
                body.innerHTML = html;
                body.scrollTop = 0;
            }
        } catch (error) {
            if (!signal.aborted) body.textContent = 'Не удалось загрузить историю. Закройте окно и попробуйте снова.';
        } finally {
            if (!signal.aborted) body.setAttribute('aria-busy', 'false');
        }
    }
    function stepGod(direction) {
        const links = [...document.querySelectorAll('.god-column:not([hidden]) .js-god-detail')];
        if (links.length) openGod(links[(links.indexOf(activeLink) + direction + links.length) % links.length]);
    }
    $('.js-god-detail').on('click', function (event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        openGod(this);
    });
    $('#previousGod').on('click', () => stepGod(-1));
    $('#nextGod').on('click', () => stepGod(1));
    $(document).on('keydown', (event) => {
        if (!modalElement.classList.contains('show') || /INPUT|TEXTAREA|SELECT/.test(event.target.tagName)) return;
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            stepGod(event.key === 'ArrowRight' ? 1 : -1);
        }
    });
    modalElement.addEventListener('hidden.bs.modal', () => {
        controller?.abort();
        activeLink?.focus();
    });
}
