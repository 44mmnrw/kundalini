(function ($) {
	'use strict';

	$(function () {
		var config = window.yogaTermOrder || {};
		var $list = $('#the-list');
		var $notice = $('<div class="notice inline yoga-term-order-status" role="status" aria-live="polite"><p></p></div>');
		var isSaving = false;

		if (!$list.length || !config.ajaxUrl || !config.taxonomy || !config.nonce) {
			return;
		}

		$('.yoga-term-order-help').after($notice);

		if ($list.hasClass('ui-sortable')) {
			$list.sortable('destroy');
		}

		function saveOrder() {
			var termIds = [];

			if (isSaving) {
				return;
			}

			$list.children('tr[id^="tag-"]').each(function () {
				var termId = parseInt($(this).find('.yoga-term-order-handle').data('term-id'), 10);
				if (termId > 0) {
					termIds.push(termId);
				}
			});

			isSaving = true;
			$list.sortable('disable');
			$notice.removeClass('notice-success notice-error').addClass('notice-info').show().find('p').text(config.saving);

			$.post(config.ajaxUrl, {
				action: config.action,
				nonce: config.nonce,
				taxonomy: config.taxonomy,
				term_ids: termIds
			}).done(function (response) {
				if (response && response.success) {
					$notice.removeClass('notice-info notice-error').addClass('notice-success').find('p').text((response.data && response.data.message) || config.saved);
					return;
				}

				$notice.removeClass('notice-info notice-success').addClass('notice-error').find('p').text((response && response.data && response.data.message) || config.error);
			}).fail(function (request) {
				var response = request.responseJSON;
				$notice.removeClass('notice-info notice-success').addClass('notice-error').find('p').text((response && response.data && response.data.message) || config.error);
			}).always(function () {
				isSaving = false;
				$list.sortable('enable');
			});
		}

		$list.sortable({
			items: '> tr[id^="tag-"]',
			handle: '.yoga-term-order-handle',
			axis: 'y',
			cursor: 'grabbing',
			placeholder: 'yoga-term-order-placeholder',
			forcePlaceholderSize: true,
			helper: function (event, row) {
				row.children().each(function () {
					$(this).width($(this).width());
				});
				return row;
			},
			start: function (event, ui) {
				ui.placeholder.html('<td colspan="' + ui.item.children('td,th').length + '"></td>');
				$list.addClass('yoga-term-order-is-sorting');
			},
			stop: function () {
				$list.removeClass('yoga-term-order-is-sorting');
			},
			update: saveOrder
		});

		$list.on('keydown', '.yoga-term-order-handle', function (event) {
			var $row;
			var $target;

			if (isSaving || event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
				return;
			}

			$row = $(this).closest('tr[id^="tag-"]');
			$target = event.key === 'ArrowUp'
				? $row.prevAll('tr[id^="tag-"]').first()
				: $row.nextAll('tr[id^="tag-"]').first();
			if (!$target.length) {
				return;
			}

			event.preventDefault();
			if (event.key === 'ArrowUp') {
				$row.insertBefore($target);
			} else {
				$row.insertAfter($target);
			}
			$row.find('.yoga-term-order-handle').trigger('focus');
			saveOrder();
		});
	});
})(jQuery);
