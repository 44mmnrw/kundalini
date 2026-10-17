/* Reviews and personal coupons in the account. */
(function () {
  'use strict';
  var dialog = document.querySelector('.lk-review-dialog');
  var form = dialog && dialog.querySelector('form');
  var previewUrl = '';
  var oldOverflow = '';
  function closeReview() { if (dialog && dialog.open) { dialog.close(); } }
  if (dialog) {
    document.querySelectorAll('[data-open-review]').forEach(function (button) {
      button.addEventListener('click', function () {
        oldOverflow = document.body.style.overflow;
        dialog.showModal();
        document.body.style.overflow = 'hidden';
      });
    });
    dialog.querySelectorAll('[data-close-review]').forEach(function (button) { button.addEventListener('click', closeReview); });
    dialog.addEventListener('close', function () { document.body.style.overflow = oldOverflow; });
    dialog.addEventListener('click', function (event) { if (event.target === dialog) { var r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) { closeReview(); } } });
    var photo = form.elements.photo;
    var message = form.querySelector('[role="status"]');
    photo.addEventListener('change', function () {
      var file = photo.files[0];
      var preview = form.querySelector('[data-review-photo-preview]');
      if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = ''; }
      preview.hidden = !preview.dataset.existingPhoto;
      if (preview.dataset.existingPhoto) { preview.src = preview.dataset.existingPhoto; }
      preview.previousElementSibling.hidden = !preview.hidden;
      message.hidden = true;
      if (!file) { return; }
      if (['image/jpeg', 'image/png'].indexOf(file.type) === -1 || file.size > 10 * 1024 * 1024) {
        photo.value = ''; message.textContent = 'Загрузите фото JPG или PNG размером до 10 МБ.'; message.hidden = false; return;
      }
      previewUrl = URL.createObjectURL(file);
      preview.src = previewUrl; preview.hidden = false; preview.previousElementSibling.hidden = true;
    });
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      var button = form.querySelector('[type="submit"]');
      if (button.disabled || !form.reportValidity()) { return; }
      button.disabled = true; message.hidden = true;
      try {
        var response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
        var result = await response.json();
        if (!result.success) { throw new Error(result.data && result.data.message || 'Не удалось отправить отзыв.'); }
        window.location.reload();
      } catch (error) {
        message.textContent = error.message || 'Не удалось отправить отзыв. Попробуйте ещё раз.';
        message.hidden = false; button.disabled = false;
      }
    });
  }
  var tabs = document.querySelectorAll('[data-coupon-tab]');
  tabs.forEach(function (tab, index) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (other) {
        var selected = other === tab;
        other.setAttribute('aria-selected', String(selected));
        other.tabIndex = selected ? 0 : -1;
        document.getElementById(other.getAttribute('aria-controls')).hidden = !selected;
      });
    });
    tab.addEventListener('keydown', function (event) {
      var next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : event.key === 'ArrowRight' ? (index + 1) % tabs.length : event.key === 'ArrowLeft' ? (index + tabs.length - 1) % tabs.length : -1;
      if (next >= 0) { event.preventDefault(); tabs[next].click(); tabs[next].focus(); }
    });
  });
  document.querySelectorAll('[data-copy-coupon]').forEach(function (button) {
    button.addEventListener('click', async function () {
      var status = document.querySelector('.lk-promo-copy-status');
      try {
        await navigator.clipboard.writeText(button.dataset.copyCoupon);
        status.textContent = 'Промокод скопирован.';
      } catch (error) { status.textContent = 'Не удалось скопировать автоматически. Выделите код и скопируйте его.'; }
      status.hidden = false;
    });
  });
})();
