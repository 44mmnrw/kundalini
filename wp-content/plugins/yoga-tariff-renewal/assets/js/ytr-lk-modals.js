(function ($) {
	'use strict';

	var config = typeof ytrLkModals !== 'undefined' ? ytrLkModals : {};
	var pendingCardDelete = null;

	var modalSelectors = [
		'#ytr-modal-remove-card',
		'#ytr-modal-remove-card-success',
		'#ytr-modal-unsubscribe',
		'#ytr-modal-unsubscribe-success',
		'#ytr-modal-bind-card',
	].join(', ');

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
		window.setTimeout(function () {
			$notification.fadeOut(300, function () {
				$(this).remove();
			});
		}, 4000);
	}

	function openModal($modal) {
		if (!$modal || !$modal.length) {
			return false;
		}

		if ($modal.is('#ytr-modal-remove-card, #ytr-modal-unsubscribe')) {
			$modal.find('.delcomm').removeClass('active');
		}
		if ($modal.is('#ytr-modal-remove-card-success, #ytr-modal-unsubscribe-success')) {
			$modal.find('.delcomm').addClass('active');
		}

		$('.modal').not($modal).removeClass('active').attr('aria-hidden', 'true');
		if ($modal.parent()[0] !== document.body) {
			$modal.appendTo('body');
		}
		$('.overlay').addClass('active');
		$modal.addClass('active').attr('aria-hidden', 'false');
		$('.body').addClass('body-fixed');
		return true;
	}

	function closeModals() {
		$('.overlay').removeClass('active');
		$(modalSelectors).removeClass('active').attr('aria-hidden', 'true');
		$('.modal-filter').removeClass('active');
		$('.modal-login').removeClass('active');
		$('.modal-mobile-menu').removeClass('active');
		$('.modal-mobile-menu-lk').removeClass('active');
		$('.body').removeClass('body-fixed');
		pendingCardDelete = null;
	}

	function resetOverlayState() {
		closeModals();
	}

	function getCardData($trigger) {
		return {
			cardId: String($trigger.attr('data-card-id') || $trigger.data('cardId') || ''),
			last4: String($trigger.attr('data-last4') || $trigger.data('last4') || '0000'),
			brandName: String($trigger.attr('data-brand-name') || $trigger.data('brandName') || 'Карта'),
		};
	}

	function formatBrandName(name) {
		var value = String(name || '').trim();
		return value.toLowerCase() === 'mir' ? 'Мир' : value;
	}

	function updateCardsCount() {
		var count = $('.lk-settings-part_cards .lk-settings-item_card').length;
		$('.lk-settings-item_action[data-target="2"] .lk-settings-item__col-action-numbers span').text(count);
	}

	function removeCardFromDom($cardItem) {
		if ($cardItem && $cardItem.length) {
			$cardItem.slideUp(300, function () {
				$(this).remove();
				updateCardsCount();
			});
			return;
		}

		$('.lk-settings-part_cards .lk-settings-item_card').slideUp(300, function () {
			$(this).remove();
			updateCardsCount();
		});
	}

	function ajaxPost(action, data) {
		if (!config.ajaxUrl || !config.nonce) {
			return $.Deferred().reject().promise();
		}

		return $.ajax({
			url: config.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: $.extend(
				{
					action: action,
					security: config.nonce,
				},
				data || {}
			),
		});
	}

	function removeSavedCard(cardId, $cardItem, onSuccess) {
		ajaxPost('ytr_remove_payment_method', { card_id: cardId })
			.done(function (response) {
				if (response && response.success) {
					if (typeof onSuccess === 'function') {
						onSuccess(response);
					}
					return;
				}

				var err =
					response && response.data && response.data.message
						? response.data.message
						: 'Не удалось удалить карту';
				notify(err, 'error');
			})
			.fail(function () {
				notify('Ошибка запроса', 'error');
			});
	}

	function openRemoveCardModal(cardData) {
		var label = formatBrandName(cardData.brandName) + ' •••• ' + cardData.last4;
		$('#ytr-remove-card-label').text(
			'Карта ' + label + ' будет удалена. Автопродление отключится.'
		);
		openModal($('#ytr-modal-remove-card'));
	}

	function handleSavedCardClick($trigger) {
		var cardData = getCardData($trigger);
		var $cardItem = $trigger.hasClass('lk-settings-item_card')
			? $trigger
			: $trigger.closest('.lk-settings-item_card, .lk-settings-item');

		if (!cardData.cardId) {
			return;
		}

		if (!$('#ytr-modal-remove-card').length) {
			if (!window.confirm('Удалить эту карту?')) {
				return;
			}
			removeSavedCard(cardData.cardId, $cardItem, function (response) {
				var notice =
					response.data && response.data.message ? response.data.message : 'Карта удалена';
				notify(notice);
				removeCardFromDom($cardItem);
			});
			return;
		}

		pendingCardDelete = {
			cardId: cardData.cardId,
			$cardItem: $cardItem,
		};
		openRemoveCardModal(cardData);
	}

	function renderAutoRenewStatusOff(message, accessEnd) {
		var endDate = accessEnd || '—';
		var detail =
			message ||
			'Доступ сохранится до ' + endDate + '. Тариф не продлится автоматически, списаний не будет.';
		var html =
			'<div class="lk-auto-renew-status lk-auto-renew-status_off" role="status">' +
			'<p class="lk-auto-renew-status__title">Автопродление отключено</p>' +
			'<p class="lk-auto-renew-status__text">' +
			$('<div>').text(detail).html() +
			'</p>' +
			'</div>';

		var $wrap = $('#ytr-auto-renew-status');
		if ($wrap.length) {
			$wrap.html(html);
			return;
		}

		$('.lk-settings-part_cancel')
			.first()
			.replaceWith(
				$('<div class="lk-settings-part lk-settings-part_cancel" id="ytr-auto-renew-status"></div>').html(
					html
				)
			);
	}

	function hideCancelAutoRenewButton() {
		$('#ytr-cancel-subscription-btn').closest('.lk-settings-part_cancel').remove();
	}

	window.ytrCloseLkModals = closeModals;

	$(function () {
		resetOverlayState();

		$(document).on('click', '.lk-settings-item_card', function (e) {
			e.stopPropagation();
			handleSavedCardClick($(this));
		});

		$(document).on('keydown', '.lk-settings-item_card', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				handleSavedCardClick($(this));
			}
		});

		$('.lk-settings-item_action[data-target="2"]').on('click', function () {
			closeModals();
		});

		$('#ytr-remove-card-cancel, #ytr-modal-remove-card .modal-close').on('click', function () {
			closeModals();
		});

		$('#ytr-modal-remove-card-success .modal-close').on('click', function () {
			closeModals();
		});

		$('#ytr-remove-card-confirm').on('click', function () {
			if (!pendingCardDelete) {
				return;
			}

			var pending = pendingCardDelete;
			var $btn = $(this);
			$btn.prop('disabled', true);

			removeSavedCard(pending.cardId, null, function (response) {
				$btn.prop('disabled', false);
				var notice =
					response.data && response.data.message ? response.data.message : 'Карта удалена';
				$('#ytr-remove-card-success-text').text(notice);
				$('#ytr-modal-remove-card').removeClass('active').attr('aria-hidden', 'true');
				removeCardFromDom(pending.$cardItem);
				openModal($('#ytr-modal-remove-card-success'));
				pendingCardDelete = null;
			});
		});

		$('#ytr-cancel-subscription-btn').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var endDate = String($(this).data('accessEnd') || $(this).attr('data-access-end') || '—');
			$('#ytr-unsubscribe-end-date').text(endDate);
			openModal($('#ytr-modal-unsubscribe'));
		});

		$('#ytr-unsubscribe-keep, #ytr-modal-unsubscribe .modal-close').on('click', function () {
			closeModals();
		});

		$('#ytr-modal-unsubscribe-success .modal-close').on('click', function () {
			closeModals();
			window.location.reload();
		});

		$('#ytr-unsubscribe-confirm').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();

			var $btn = $(this);
			$btn.prop('disabled', true);

			ajaxPost('ytr_cancel_auto_renew')
				.done(function (response) {
					$btn.prop('disabled', false);
					if (!response || !response.success) {
						var err =
							response && response.data && response.data.message
								? response.data.message
								: 'Не удалось отменить автопродление';
						notify(err, 'error');
						return;
					}

					var message =
						response.data && response.data.message
							? response.data.message
							: 'Автопродление отключено';
					var accessEnd = response.data && response.data.access_end ? response.data.access_end : '';

					$('#ytr-unsubscribe-success-text').text('Автопродление отключено');
					if (accessEnd !== '') {
						$('#ytr-unsubscribe-success-hint').text(
							'Доступ сохранится до ' +
								accessEnd +
								'. Тариф не продлится автоматически, списаний не будет.'
						);
					}

					$('#ytr-modal-unsubscribe').removeClass('active').attr('aria-hidden', 'true');
					renderAutoRenewStatusOff(message, accessEnd);
					hideCancelAutoRenewButton();
					removeCardFromDom();
					openModal($('#ytr-modal-unsubscribe-success'));
				})
				.fail(function () {
					$btn.prop('disabled', false);
					notify('Ошибка запроса', 'error');
				});
		});

		$('.overlay').on('click.ytrLkModals', function () {
			if (
				$(
					'#ytr-modal-remove-card.active, #ytr-modal-remove-card-success.active, #ytr-modal-unsubscribe.active, #ytr-modal-unsubscribe-success.active'
				).length
			) {
				closeModals();
			}
		});
	});
})(jQuery);
