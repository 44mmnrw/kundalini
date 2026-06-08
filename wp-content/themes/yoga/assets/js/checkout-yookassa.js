(function ($) {
	'use strict';

	function getConfig() {
		return typeof yogaYooKassa !== 'undefined' ? yogaYooKassa : {};
	}

	function getForm() {
		return $('form.checkout.woocommerce-checkout');
	}

	function resolveGatewayId() {
		var config = getConfig();
		var redirectTypes = config.redirectTypes || [];
		var widgetTypes = config.widgetTypes || ['bank_card', 'yoo_money'];
		var selectedType = getSelectedType();

		if (selectedType && redirectTypes.indexOf(selectedType) !== -1) {
			return 'yookassa_epl';
		}

		if (selectedType && widgetTypes.indexOf(selectedType) !== -1) {
			return 'yookassa_widget';
		}

		if (selectedType) {
			return 'yookassa_epl';
		}

		return config.gatewayId || 'yookassa_widget';
	}

	function ensureGateway($form) {
		var gatewayId = resolveGatewayId();
		if (!gatewayId || !$form.length) {
			return false;
		}

		var $hidden = $form.find('input.yoga-checkout-payment-method');
		if ($hidden.length) {
			$hidden.val(gatewayId);
		} else {
			$form.append(
				$('<input>', {
					type: 'hidden',
					name: 'payment_method',
					'class': 'yoga-checkout-payment-method',
					value: gatewayId,
				})
			);
		}

		var $gateway = $form.find('#payment input[name="payment_method"][value="' + gatewayId + '"]');
		if ($gateway.length) {
			$gateway.prop('checked', true).trigger('change');
		}

		return true;
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

		$form.off('change.yogaYooKassa', 'input[name="yoga_checkout_payment_type"]');
		$form.on('change.yogaYooKassa', 'input[name="yoga_checkout_payment_type"]', function () {
			ensureGateway($form);
			syncWidgetMethod($form);
		});
	}

	$(function () {
		var $form = getForm();
		init($form);

		$(document.body).on('updated_checkout', function () {
			init(getForm());
		});

		$(document.body).on('checkout_place_order', function () {
			var $form = getForm();
			ensureGateway($form);
			syncWidgetMethod($form);
		});

		$form.on('submit', function () {
			ensureGateway($form);
			syncWidgetMethod($form);
		});
	});
})(jQuery);
