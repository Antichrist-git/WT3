// Поддержка собранной JS-версии из присланного архива, пока npm run dev не выполнен.
// После пересборки resources/js/index.js выставляет __wt3EnhancedBundle и этот файл ничего не делает.
if (!window.__wt3EnhancedBundle) {
    document.querySelectorAll('.god-form [data-requires-letters]').forEach((field) => {
        const validate = () => {
            const value = field.value.trim();
            field.setCustomValidity(value && !/\p{L}/u.test(value)
                ? 'Введите текст с буквами, а не только цифры и знаки.' : '');
        };
        field.addEventListener('input', validate);
        validate();
    });
    const form = document.querySelector('.god-form');
    form?.addEventListener('submit', (event) => {
        form.querySelectorAll('[data-requires-letters]').forEach((field) => {
            const value = field.value.trim();
            field.setCustomValidity(value && !/\p{L}/u.test(value)
                ? 'Введите текст с буквами, а не только цифры и знаки.' : '');
        });
        if (!form.checkValidity()) {
            event.preventDefault();
            form.classList.add('was-validated');
            form.querySelector(':invalid')?.focus();
        }
    }, true);

    // Старая сборка показывает Modal, но ещё не умеет выставлять CRUD-маршруты.
    document.querySelectorAll('.js-god-detail').forEach((link) => {
        link.addEventListener('click', () => {
            const card = link.closest('.god-card');
            const action = card?.querySelector('.card-more');
            const edit = document.getElementById('godModalEdit');
            const del = document.getElementById('godModalDeleteForm');
            if (action && edit && del) {
                edit.href = action.dataset.editUrl;
                del.action = action.dataset.deleteUrl;
                del.dataset.godName = card.querySelector('.card-title').textContent.trim();
            }
        }, true);
    });
    document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(`Удалить ${form.dataset.godName || 'этого бога'} из коллекции?`)) {
                event.preventDefault();
            }
        });
    });
}
