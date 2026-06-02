(function ($) {
	'use strict';

	function getConfig() {
		return typeof yogaYooKassa !== 'undefined' ? yogaYooKassa : {};
	}

	function ensureGateway($form) {
		var gatewayId = getConfig().gatewayId;
		if (!gatewayId) {
			return;
		}

		var $gateway = $form.find('#payment input[name="payment_method"][value="' + gatewayId + '"]');
		if ($gateway.length) {
			$gateway.prop('checked', true).trigger('change');
		}
	}

	function getSelectedType() {
		var $checked = $('input[name="yoga_checkout_payment_type"]:checked');
		if (!$checked.length) {
			return '';
		}

		var yogaId = $checked.val();
		var map = getConfig().typeMap || {};
		return map[yogaId] || yogaId;
	}

	function syncWidgetMethod($form) {
		var yookassaType = getSelectedType();
		if (!yookassaType) {
			return;
		}

		var selectors = [
			'input[name="yookassa-widget-payment-method"][value="' + yookassaType + '"]',
			'input[name="yookassa_payment_method"][value="' + yookassaType + '"]',
			'input[data-payment-type="' + yookassaType + '"]',
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

	function init($form) {
		if (!$form.length) {
			return;
		}

		ensureGateway($form);
		syncWidgetMethod($form);

		$form.on('change', 'input[name="yoga_checkout_payment_type"]', function () {
			ensureGateway($form);
			syncWidgetMethod($form);
		});
	}

	$(function () {
		var $form = $('form.checkout.woocommerce-checkout');
		init($form);

		$(document.body).on('updated_checkout', function () {
			init($form);
		});
	});
})(jQuery);
