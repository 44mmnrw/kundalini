(function ($) {
	'use strict';

	var i18n = window.yogaPracticeDownload || {};

	function parseFilename(contentDisposition) {
		if (!contentDisposition) {
			return 'protocol.pdf';
		}

		var utfMatch = /filename\*=UTF-8''([^;]+)/i.exec(contentDisposition);
		if (utfMatch && utfMatch[1]) {
			try {
				return decodeURIComponent(utfMatch[1].trim());
			} catch (ignore) {}
		}

		var quotedMatch = /filename="([^"]+)"/i.exec(contentDisposition);
		if (quotedMatch && quotedMatch[1]) {
			return quotedMatch[1];
		}

		var plainMatch = /filename=([^;]+)/i.exec(contentDisposition);
		if (plainMatch && plainMatch[1]) {
			return plainMatch[1].replace(/"/g, '').trim();
		}

		return 'protocol.pdf';
	}

	function triggerBlobDownload(blob, filename) {
		var url = window.URL.createObjectURL(blob);
		var link = document.createElement('a');
		link.href = url;
		link.download = filename;
		link.style.display = 'none';
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
		window.setTimeout(function () {
			window.URL.revokeObjectURL(url);
		}, 1000);
	}

	function setExhaustedState($root, alreadyDownloaded) {
		var wasDownloadable = $root.attr('data-can-download') === '1';
		var rawRemaining = String($root.attr('data-remaining-downloads') || '');
		var remainingIsUnlimited = rawRemaining === 'unlimited';
		var remainingValue = parseInt(rawRemaining, 10);

		if (!remainingIsUnlimited && !isNaN(remainingValue)) {
			if (alreadyDownloaded && wasDownloadable) {
				remainingValue = Math.max(0, remainingValue - 1);
			} else if (!alreadyDownloaded) {
				remainingValue = 0;
			}
		}

		var label = alreadyDownloaded
			? (i18n.downloadedLabel || 'Протокол уже скачан')
			: (i18n.downloadLabel || 'Скачать протокол практики');

		var html = '<span class="btn praktika-download__btn" aria-disabled="true"><span></span></span>';
		$root.addClass('praktika-download--exhausted')
			.attr('data-can-download', '0')
			.attr('data-already-downloaded', alreadyDownloaded ? '1' : '0')
			.attr('data-remaining-downloads', remainingIsUnlimited ? 'unlimited' : String(Math.max(0, isNaN(remainingValue) ? 0 : remainingValue)))
			.html(html);
		$root.find('.praktika-download__btn span').first().text(label);

		if (!alreadyDownloaded && i18n.limitExceededMessage) {
			$root.append(
				$('<p>', {
					class: 'praktika-download__note',
					text: i18n.limitExceededMessage,
				})
			);
		}

		var remainingText = remainingIsUnlimited
			? (i18n.unlimitedLabel || 'безлимит')
			: String(Math.max(0, isNaN(remainingValue) ? 0 : remainingValue));
		$root.append(
			$('<p>', {
				class: 'praktika-download__remaining',
				html:
					(i18n.remainingDownloadsLabel || 'Осталось скачиваний:') +
					' <span>' +
					remainingText +
					'</span>',
			})
		);
	}

	function setLoadingState($btn, isLoading) {
		$btn.toggleClass('praktika-download__btn--loading', isLoading);
		$btn.attr('aria-busy', isLoading ? 'true' : 'false');
	}

	function buildFetchUrl(downloadUrl) {
		var url = new URL(downloadUrl, window.location.origin);
		url.searchParams.set('yoga_fetch', '1');
		return url.toString();
	}

	function parseJsonError(response) {
		return response.json().then(function (payload) {
			var data = payload && payload.data ? payload.data : payload;
			var code = data && data.code ? data.code : '';
			var message = data && data.message ? data.message : (i18n.errorMessage || 'Не удалось скачать файл.');

			return {
				code: code,
				message: message,
			};
		}).catch(function () {
			return {
				code: '',
				message: i18n.errorMessage || 'Не удалось скачать файл.',
			};
		});
	}

	$(document).on('click', '.praktika-download[data-can-download="1"] .praktika-download__btn', function (event) {
		event.preventDefault();

		var $btn = $(this);
		if ($btn.hasClass('praktika-download__btn--loading')) {
			return;
		}

		var $root = $btn.closest('.praktika-download');
		var downloadUrl = $root.attr('data-download-url');
		if (!downloadUrl) {
			return;
		}

		setLoadingState($btn, true);

		fetch(buildFetchUrl(downloadUrl), {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			},
		})
			.then(function (response) {
				var contentType = response.headers.get('Content-Type') || '';

				if (!response.ok) {
					if (contentType.indexOf('application/json') !== -1) {
						return parseJsonError(response).then(function (errorData) {
							throw {
								code: errorData.code,
								limitExceeded: errorData.code === 'limit_exceeded',
								alreadyDownloaded: errorData.code === 'already_downloaded',
								message: errorData.message,
							};
						});
					}
					throw { message: i18n.errorMessage || 'Не удалось скачать файл.' };
				}

				if (contentType.indexOf('application/json') !== -1) {
					return parseJsonError(response).then(function (errorData) {
						throw { message: errorData.message };
					});
				}

				var filename = parseFilename(response.headers.get('Content-Disposition'));
				return response.blob().then(function (blob) {
					return { blob: blob, filename: filename };
				});
			})
			.then(function (result) {
				triggerBlobDownload(result.blob, result.filename);
				setExhaustedState($root, true);
			})
			.catch(function (error) {
				if (error && (error.limitExceeded || error.alreadyDownloaded)) {
					setExhaustedState($root, !!error.alreadyDownloaded);
					return;
				}
				window.alert((error && error.message) || i18n.errorMessage || 'Не удалось скачать файл.');
			})
			.finally(function () {
				setLoadingState($btn, false);
			});
	});
})(jQuery);
