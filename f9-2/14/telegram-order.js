(function () {
  const token = '8686784975:AAFi-nQ8k19jjZlEC8a4SV0PVmhaSFN1-GI';
  const chatId = '-5120181560';
  const endpoint = 'https://api.telegram.org/bot' + token + '/sendMessage';
  var orderSubmitted = false;
  var orderSending = false;

  function field(form, names) {
    for (var i = 0; i < names.length; i++) {
      var el = form.querySelector('[name="' + names[i] + '"]');
      if (el && (el.value || '').trim()) return el.value.trim();
    }
    return '';
  }

  function localPhoneDigits(value) {
    var digits = String(value || '').replace(/\D/g, '');
    if (digits.indexOf('998') === 0) digits = digits.slice(3);
    return digits.slice(0, 9);
  }

  function formatPhone(value) {
    var local = localPhoneDigits(value);
    var parts = ['+998'];
    if (local.length > 0) parts.push(local.slice(0, 2));
    if (local.length > 2) parts.push(local.slice(2, 5));
    if (local.length > 5) parts.push(local.slice(5, 7));
    if (local.length > 7) parts.push(local.slice(7, 9));
    return parts.join(' ');
  }

  function setupPhoneInput(input) {
    function applyMask() {
      input.value = formatPhone(input.value);
    }

    if (!input.value.trim()) input.value = '+998 ';
    applyMask();

    input.addEventListener('focus', function () {
      if (!input.value.trim()) input.value = '+998 ';
    });
    input.addEventListener('input', applyMask);
    input.addEventListener('keydown', function (event) {
      var allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
      if (allowed.indexOf(event.key) !== -1 || event.ctrlKey || event.metaKey) return;
      if (!/^\d$/.test(event.key)) event.preventDefault();
    });
  }

  function setupPhoneInputs() {
    var inputs = document.querySelectorAll('input[name="Phone"]');
    Array.prototype.forEach.call(inputs, setupPhoneInput);
  }

  function formatShortDate(date) {
    return two(date.getDate()) + ' ' + two(date.getMonth() + 1) + ' ' + date.getFullYear();
  }

  function setupCurrentDates() {
    var today = formatShortDate(new Date());
    var dates = document.querySelectorAll('.html-text');
    Array.prototype.forEach.call(dates, function (dateEl) {
      dateEl.textContent = today;
    });
  }

  function orderCardElements(form) {
    var section = form.closest('.section');
    if (!section) return [form];

    var formTop = form.offsetTop;
    var formLeft = form.offsetLeft;
    var formRight = formLeft + form.offsetWidth;
    var formBottom = formTop + form.offsetHeight;
    var elements = Array.prototype.filter.call(section.children, function (el) {
      if (el === form) return true;
      if (!el.classList || !el.classList.contains('item')) return false;
      var left = el.offsetLeft;
      var top = el.offsetTop;
      var right = left + el.offsetWidth;
      var bottom = top + el.offsetHeight;
      var sameColumn = left >= formLeft - 45 && right <= formRight + 45;
      var nearCard = top >= formTop - 230 && bottom <= formBottom + 35;
      return sameColumn && nearCard;
    });

    return elements.length ? elements : [form];
  }

  function showSuccess(form) {
    var items = orderCardElements(form);
    var section = form.closest('.section') || form.parentElement;
    if (!section) return;
    var message = document.createElement('div');
    message.className = form.className;
    message.style.cssText = window.getComputedStyle(form).cssText;
    message.innerHTML = `<div style="width:300px;padding:28px 20px;border-radius:20px;background:#f7eee8;color:#0f0f0f;font-family:Arial,sans-serif;font-size:20px;font-weight:700;line-height:1.35;text-align:center;">Buyurtma yuborildi!<br>Tez orada siz bilan bog'lanamiz.</div>`;
    section.appendChild(message);
    items.forEach(function (el) {
      if (el !== message) el.remove();
    });
  }

  function disableAllForms() {
    orderSubmitted = true;
    var forms = document.querySelectorAll('form');
    Array.prototype.forEach.call(forms, function (otherForm) {
      if (!otherForm.parentElement) return;
      otherForm.dataset.sent = 'true';
      showSuccess(otherForm);
    });
  }

  function setFormsBusy(isBusy) {
    orderSending = isBusy;
    var forms = document.querySelectorAll('form');
    Array.prototype.forEach.call(forms, function (otherForm) {
      otherForm.dataset.sending = isBusy ? 'true' : 'false';
      var controls = otherForm.querySelectorAll('input, select, textarea, button');
      Array.prototype.forEach.call(controls, function (control) {
        control.disabled = isBusy;
      });
    });
  }

  function two(value) {
    return String(value).padStart(2, '0');
  }

  function formatDate(date) {
    return two(date.getDate()) + '/' + two(date.getMonth() + 1) + '/' + date.getFullYear() + ', ' +
      two(date.getHours()) + ':' + two(date.getMinutes()) + ':' + two(date.getSeconds());
  }

  function handleSubmit(event) {
    var form = event.target;
    if (!form || !form.matches('form')) return;
    event.preventDefault();
    if (orderSubmitted || orderSending || form.dataset.sent === 'true') return;

    var fullName = field(form, ['Name', 'name', 'Ism', 'Ism Familiya']) || 'Kiritilmagan';
    var phoneInput = form.querySelector('[name="Phone"]');
    var phone = phoneInput ? formatPhone(phoneInput.value) : 'Kiritilmagan';
    if (phoneInput && localPhoneDigits(phoneInput.value).length < 9) {
      alert('Telefon raqamini to\'liq kiriting.');
      phoneInput.focus();
      return;
    }
    var address = field(form, ['Address', 'address', 'Turar joyi', 'Region', 'City']) || '-';
    var age = field(form, ['Age', 'age', 'Yosh']) || '-';

    var text = '🆕 Yangi buyurtma: Fatizon 34\n' +
      '👤 Ism Familiya: ' + fullName + '\n' +
      '📱 Telefon: ' + phone + '\n' +
      '📍 Turar joyi: ' + address + '\n' +
      '🎂 Yosh: ' + age + '\n' +
      '🕒 Sana: ' + formatDate(new Date());

    setFormsBusy(true);
    fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ chat_id: chatId, text: text })
    }).then(function (response) {
      if (!response.ok) throw new Error('Telegram error');
      disableAllForms();
    }).catch(function () {
      setFormsBusy(false);
      alert('Xatolik yuz berdi. Qayta urinib ko\'ring.');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      setupPhoneInputs();
      setupCurrentDates();
    });
  } else {
    setupPhoneInputs();
    setupCurrentDates();
  }

  document.addEventListener('submit', handleSubmit, true);
})();

