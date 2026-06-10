(function ($) {
	'use strict';

	var $modal = null;
	var $form = null;
	var $submitBtn = null;

	function getConfig() {
		return typeof ytrCardBinding !== 'undefined' ? ytrCardBinding : {};
	}

	function notify(message, type) {
		type = type || 'success';

		if (typeof showNotification === 'function') {
			showNotification(message, type);
			return;
		}

		$('.practice-notification').remove();
		var $notification = $('<div class="practice-notification ' + type + '">' + message + '</div>');
		$('body').append($notification);
		$notification.css({
			position: 'fixed',
			top: '20px',
			right: '20px',
			padding: '15px 20px',
			background: type === 'success' ? '#00b894' : '#ff6b6b',
			color: 'white',
			'border-radius': '5px',
			'z-index': '10000',
			'box-shadow': '0 4px 12px rgba(0,0,0,0.15)',
		});
		$notification.hide().fadeIn(300);
		setTimeout(function () {
			$notification.fadeOut(300, function () {
				$(this).remove();
			});
		}, 4000);
	}

	function openPaymentMethodsSlide() {
		$('.lk-slide').removeClass('active');
		$('.lk-slide[data-target="6"]').addClass('active');
		$('.sidebar-menu__item').removeClass('active');
		$('.sidebar-menu__item[data-target="6"]').addClass('active');

		$('.lk-settings__slide').removeClass('active');
		$('.lk-settings__slide[data-target="2"]').addClass('active');
	}

	function handleReturnStatus() {
		var params = new URLSearchParams(window.location.search);
		var status = params.get('ytr_card');
		if (!status) {
			return;
		}

		openPaymentMethodsSlide();

		if (status === 'success') {
			notify('Карта привязана. Если у вас активный тариф, автопродление включено.');
		} else if (status === 'failed') {
			notify('Не удалось привязать карту. Попробуйте ещё раз или оплатите тариф с галочкой сохранения карты.', 'error');
		}

		params.delete('ytr_card');
		var query = params.toString();
		var nextUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
		window.history.replaceState({}, document.title, nextUrl);
	}

	function isFieldFilled($input) {
		var val = String($input.val() || '');
		return val !== '' && val.indexOf('_') === -1;
	}

	function updateSubmitState() {
		if (!$form || !$submitBtn) {
			return;
		}

		var filledCount = 0;
		$form.find('.input-card-custom .input').each(function () {
			if (isFieldFilled($(this))) {
				filledCount += 1;
			}
		});

		if (filledCount === 3) {
			$submitBtn.addClass('active');
		} else {
			$submitBtn.removeClass('active');
		}
	}

	function resetBindCardForm() {
		if (!$form || !$submitBtn) {
			return;
		}

		$form[0].reset();
		$form.find('.input-card-custom').removeClass('active');
		$submitBtn.removeClass('active is-loading').attr('aria-busy', 'false');
	}

	function openBindCardModal() {
		if (!$modal || !$modal.length) {
			startCardBinding($());
			return;
		}

		resetBindCardForm();
		$('.overlay').addClass('active');
		$('.body').addClass('body-fixed');
		$modal.addClass('active').attr('aria-hidden', 'false');
	}

	function closeBindCardModal() {
		if (!$modal || !$modal.length) {
			return;
		}

		$modal.removeClass('active').attr('aria-hidden', 'true');
		resetBindCardForm();

		if (
			!$('.modal.active').length &&
			!$('.modal-login.active').length &&
			!$('.modal-mobile-menu-lk.active').length
		) {
			$('.overlay').removeClass('active');
			$('.body').removeClass('body-fixed');
		}
	}

	function startCardBinding($trigger) {
		var config = getConfig();
		if (!config.ajaxUrl || !config.nonce) {
			notify('Не удалось начать привязку карты', 'error');
			return;
		}

		if ($submitBtn && $submitBtn.length) {
			$submitBtn.addClass('is-loading').attr('aria-busy', 'true');
		}
		if ($trigger && $trigger.length) {
			$trigger.addClass('is-loading').attr('aria-busy', 'true');
		}

		$.ajax({
			url: config.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ytr_bind_card_start',
				security: config.nonce,
			},
		})
			.done(function (response) {
				if (response && response.success && response.data && response.data.redirect_url) {
					window.location.href = response.data.redirect_url;
					return;
				}

				var message =
					response && response.data && response.data.message
						? response.data.message
						: 'Не удалось начать привязку карты';
				notify(message, 'error');
			})
			.fail(function (xhr) {
				var message = 'Ошибка запроса';
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					message = xhr.responseJSON.data.message;
				}
				notify(message, 'error');
			})
			.always(function () {
				if ($submitBtn && $submitBtn.length) {
					$submitBtn.removeClass('is-loading').attr('aria-busy', 'false');
				}
				if ($trigger && $trigger.length) {
					$trigger.removeClass('is-loading').attr('aria-busy', 'false');
				}
			});
	}

	$(function () {
		$modal = $('#ytr-modal-bind-card');
		$form = $('#ytr-bind-card-form');
		$submitBtn = $('#ytr-bind-card-btn');

		handleReturnStatus();

		$(document).on('click', '#add-new-card, .js-ytr-bind-card', function (e) {
			e.preventDefault();
			openBindCardModal();
		});

		if ($form.length) {
			$form.on('submit', function (e) {
				e.preventDefault();
				if (!$submitBtn.hasClass('active') || $submitBtn.hasClass('is-loading')) {
					return;
				}
				startCardBinding($submitBtn);
			});

			$submitBtn.on('click', function (e) {
				if (!$submitBtn.hasClass('active') || $submitBtn.hasClass('is-loading')) {
					e.preventDefault();
				}
			});

			$form.on('focus input blur keyup change', '.input-card-custom .input', function () {
				var $input = $(this);
				var $wrap = $input.closest('.input-card-custom');

				if ($input.is(':focus') || isFieldFilled($input)) {
					$wrap.addClass('active');
				} else if ($input.val().indexOf('_') !== -1) {
					$wrap.removeClass('active');
				}

				updateSubmitState();
			});
		}

		$(document).on('click', '#ytr-modal-bind-card .modal-close', function () {
			closeBindCardModal();
		});
	});
})(jQuery);
