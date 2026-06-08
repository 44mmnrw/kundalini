(function ($) {
	'use strict';

	function syncPaymentCards($form) {
		var $cards = $form.find('.yoga-checkout-payment');
		$cards.each(function () {
			var $card = $(this);
			var checked = $card.find('.yoga-checkout-payment__input').is(':checked');
			$card.toggleClass('is-active', checked);
		});
	}

	function syncYooKassaWidget($form, yookassaType) {
		if (!yookassaType) {
			return;
		}

		var selectors = [
			'input[name="yookassa-widget-payment-method"][value="' + yookassaType + '"]',
			'input[name="yookassa_payment_method"][value="' + yookassaType + '"]',
			'input[name="payment_method_yookassa"][value="' + yookassaType + '"]',
			'input[data-payment-type="' + yookassaType + '"]',
			'.yookassa-payment-method[data-type="' + yookassaType + '"] input',
		];

		selectors.some(function (selector) {
			var $input = $form.find(selector).first();
			if ($input.length) {
				$input.prop('checked', true).trigger('change');
				return true;
			}
			return false;
		});
	}

	function ensureWooGatewaySelected($form) {
		var gatewayId = $form.find('input.yoga-checkout-payment-method').val();
		if (!gatewayId) {
			return;
		}

		var $gateway = $form.find('#payment input[name="payment_method"][value="' + gatewayId + '"]');
		if ($gateway.length && !$gateway.is(':checked')) {
			$gateway.prop('checked', true).trigger('change');
		}
	}

	function initCheckoutPayment($form) {
		if (!$form.length || !$form.find('.yoga-checkout-payments').length) {
			return;
		}

		ensureWooGatewaySelected($form);
		syncPaymentCards($form);

		var initialType = $form.find('input[name="yoga_checkout_payment_type"]:checked').data('yookassa-type');
		syncYooKassaWidget($form, initialType);

		$form.on('change', 'input[name="yoga_checkout_payment_type"]', function () {
			syncPaymentCards($form);
			syncYooKassaWidget($form, $(this).data('yookassa-type'));
		});

		$form.on('click', '.yoga-checkout-payment', function (event) {
			if ($(event.target).is('input[type="radio"]')) {
				return;
			}
			var $radio = $(this).find('.yoga-checkout-payment__input');
			if ($radio.length) {
				$radio.prop('checked', true).trigger('change');
			}
		});
	}

	$(function () {
		var $form = $('form.checkout.woocommerce-checkout');
		initCheckoutPayment($form);

		$(document.body).on('updated_checkout', function () {
			initCheckoutPayment($form);
		});
	});
})(jQuery);
