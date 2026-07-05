document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-telegram-form]');
    if (!form) return;

    event.preventDefault();

    const button = form.querySelector('[type="submit"]');
    const status = form.querySelector('[data-form-status]');
    const data = new FormData(form);
    const getValue = (name) => data.has(name) ? data.get(name) : '';

    if (button) button.disabled = true;
    if (status) status.textContent = 'Отправляем…';

    try {
        const response = await fetch(form.dataset.endpoint || '/telegram-contact.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: getValue('name'),
                phone: getValue('phone'),
                service: getValue('service'),
                number: getValue('number'),
                message: getValue('message'),
            }),
        });

        const result = await response.json();
        if (!response.ok || !result.ok) {
            throw new Error(result.message || 'Ошибка отправки');
        }

        form.reset();
        if (status) status.textContent = 'Спасибо! Заявка отправлена.';
        if (typeof window.showToast === 'function') {
            window.showToast('Обращение отправлено. Мы свяжемся с вами в ближайшее время.');
        }
        if (form.hasAttribute('data-hide-on-success')) {
            form.hidden = true;
        }
    } catch (error) {
        if (status) status.textContent = error.message || 'Не удалось отправить заявку.';
    } finally {
        if (button) button.disabled = false;
    }
});
