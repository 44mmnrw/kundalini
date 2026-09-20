(function ($) {
	'use strict';

	if (typeof acf === 'undefined') {
		return;
	}

	var instances = new WeakMap();

	function clamp(value, minimum, maximum) {
		return Math.min(maximum, Math.max(minimum, value));
	}

	function normalizedFrame(frame) {
		frame = frame && typeof frame === 'object' ? frame : {};
		var zoom = Number(frame.zoom);
		var x = Number(frame.x);
		var y = Number(frame.y);
		return {
			zoom: clamp(Math.round(Number.isFinite(zoom) && zoom > 0 ? zoom : 100), 50, 300),
			x: clamp(Math.round(Number.isFinite(x) && frame.x !== undefined ? x : 50), 0, 100),
			y: clamp(Math.round(Number.isFinite(y) && frame.y !== undefined ? y : 50), 0, 100)
		};
	}

	function readFrames($storage) {
		try {
			var parsed = JSON.parse($storage.val() || '{}');
			return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
		} catch (error) {
			return {};
		}
	}

	function attachments($field) {
		return $field.find('.acf-gallery-attachments .acf-gallery-attachment.-image').map(function () {
			var $attachment = $(this);
			var id = parseInt($attachment.attr('data-id') || $attachment.find('input[type="hidden"]').first().val(), 10);
			if (!Number.isFinite(id) || id < 1) {
				return null;
			}
			return {
				id: String(id),
				thumbnail: $attachment.find('img').first().attr('src') || '',
				element: this
			};
		}).get();
	}

	function fieldInSameRow($field, name) {
		var accordion = $field.closest('.acf-accordion')[0];
		var cell = $field.closest('td.acf-fields')[0];
		var scope = accordion || cell;
		if (!scope) {
			return $();
		}
		return $(scope).find('.acf-field[data-name="' + name + '"]').filter(function () {
			return $(this).closest('.acf-accordion')[0] === accordion &&
				$(this).closest('td.acf-fields')[0] === cell;
		}).first();
	}

	function galleryHasTimer($field) {
		return fieldInSameRow($field, 'timing').find('input[type="checkbox"]:checked').length > 0;
	}

	function previewGeometry(instance, frame) {
		var image = instance.$previewImage[0];
		var width = instance.$preview[0].clientWidth;
		var height = instance.$preview[0].clientHeight;
		if (!image.naturalWidth || !image.naturalHeight || !width || !height) {
			return { minimum: 0.5, zoom: Math.max(1, frame.zoom / 100), x: frame.x, y: frame.y };
		}
		var ratio = image.naturalWidth / image.naturalHeight;
		var coverWidth = Math.max(width, height * ratio);
		var coverHeight = Math.max(height, width / ratio);
		var minimum = Math.min(width / coverWidth, height / coverHeight);
		var zoom = Math.max(frame.zoom / 100, minimum);
		return {
			minimum: minimum,
			zoom: zoom,
			width: width,
			height: height,
			coverWidth: coverWidth,
			coverHeight: coverHeight,
			x: frame.x,
			y: frame.y
		};
	}

	function frameImage($image, frame, geometry) {
		$image.css({
			'--exercise-image-x': frame.x + '%',
			'--exercise-image-y': frame.y + '%',
			'--exercise-image-zoom': frame.zoom / 100,
			'--exercise-image-effective-x': geometry.x + '%',
			'--exercise-image-effective-y': geometry.y + '%',
			'--exercise-image-effective-zoom': geometry.zoom
		});
		if (geometry.coverWidth) {
			var renderedWidth = geometry.coverWidth * geometry.zoom;
			var renderedHeight = geometry.coverHeight * geometry.zoom;
			$image.addClass('is-framed').css({
				width: renderedWidth + 'px',
				height: renderedHeight + 'px',
				left: (geometry.width - renderedWidth) * geometry.x / 100 + 'px',
				top: (geometry.height - renderedHeight) * geometry.y / 100 + 'px'
			});
		} else {
			$image.removeClass('is-framed').css({ width: '', height: '', left: '', top: '' });
		}
	}

	function setPreviewSource(instance, source) {
		instance.$previewImage.attr('src', source);
		instance.$previewBackdrop.attr('src', source);
	}

	function writeFrames(instance) {
		instance.$storage.val(JSON.stringify(instance.frames));
	}

	function updatePreview(instance) {
		if (!instance.selectedId) {
			return;
		}
		var frame = normalizedFrame(instance.frames[instance.selectedId]);
		instance.$preview.toggleClass('is-with-timer', galleryHasTimer(instance.$field));
		instance.$panel.find('.yoga-gallery-framing__screen-button').each(function () {
			var active = $(this).attr('data-screen') === instance.screen;
			$(this).toggleClass('is-active', active).attr('aria-pressed', active ? 'true' : 'false');
		});
		instance.$preview.toggleClass('is-phone', instance.screen === 'phone');
		var geometry = previewGeometry(instance, frame);
		frameImage(instance.$previewImage, frame, geometry);
		var minimum = Math.max(50, Math.floor(geometry.minimum * 100));
		instance.$zoom.attr('min', minimum).val(Math.max(frame.zoom, minimum));
		instance.$zoomValue.text(Math.round(geometry.zoom * 100) + '%');
		instance.$x.val(geometry.x);
		instance.$xValue.text(geometry.x + '%');
		instance.$y.val(geometry.y);
		instance.$yValue.text(geometry.y + '%');
	}

	function updateFrame(instance, frame) {
		if (!instance.selectedId) {
			return;
		}
		instance.frames[instance.selectedId] = normalizedFrame(frame);
		writeFrames(instance);
		updatePreview(instance);
	}

	function fetchImage(instance, id) {
		if (!window.wp || !wp.media || typeof wp.media.attachment !== 'function') {
			return;
		}
		var attachment = wp.media.attachment(Number(id));
		var applySource = function () {
			if (instance.selectedId !== id) {
				return;
			}
			var sizes = attachment.get('sizes') || {};
			var source = (sizes.large && sizes.large.url) || (sizes.full && sizes.full.url) || attachment.get('url');
			if (source) {
				setPreviewSource(instance, source);
			}
		};
		applySource();
		attachment.fetch().then(applySource, function () {});
	}

	function openFrame(instance, image) {
		instance.selectedId = image.id;
		setPreviewSource(instance, image.thumbnail);
		instance.$panel.prop('hidden', false);
		instance.$panel.find('.yoga-gallery-framing__heading').text('Кадр изображения ' + (instance.images.findIndex(function (item) {
			return item.id === image.id;
		}) + 1));
		updatePreview(instance);
		fetchImage(instance, image.id);
		if (instance.$panel[0].scrollIntoView) {
			instance.$panel[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	}

	function renderImages(instance) {
		instance.images = attachments(instance.$field);
		instance.$root.toggle(instance.images.length > 0);
		instance.images.forEach(function (image) {
			var $attachment = $(image.element);
			if (!$attachment.children('.yoga-gallery-framing__open').length) {
				$('<button type="button" class="yoga-gallery-framing__open">Кадр</button>')
					.attr('data-attachment-id', image.id)
					.appendTo($attachment);
			}
		});
		if (instance.selectedId && !instance.images.some(function (image) { return image.id === instance.selectedId; })) {
			instance.selectedId = null;
			instance.$panel.prop('hidden', true);
		}
	}

	function setupField(field) {
		if (instances.has(field) || $(field).closest('.acf-clone').length) {
			return;
		}
		var $field = $(field);
		var $storage = fieldInSameRow($field, 'gallery_framing').find('textarea').first();
		if (!$storage.length) {
			return;
		}

		var instance = {
			$field: $field,
			$storage: $storage,
			frames: readFrames($storage),
			images: [],
			selectedId: null,
			screen: 'desktop'
		};
		var $root = $('<div class="yoga-gallery-framing"></div>');
		$root.append('<p class="yoga-gallery-framing__intro">Нажмите «Кадр» на изображении, чтобы настроить видимую область. Изменения сохранятся вместе с практикой.</p>');
		var $panel = $('<div class="yoga-gallery-framing__panel"></div>').prop('hidden', true).appendTo($root);
		var $header = $('<div class="yoga-gallery-framing__header"></div>').appendTo($panel);
		$('<strong class="yoga-gallery-framing__heading"></strong>').appendTo($header);
		$('<button type="button" class="button">Готово</button>')
			.on('click', function () { $panel.prop('hidden', true); })
			.appendTo($header);
		var $screens = $('<div class="yoga-gallery-framing__screens" role="group" aria-label="Предпросмотр кадра"></div>').appendTo($panel);
		[['desktop', 'Широкий экран'], ['phone', 'Телефон']].forEach(function (screen) {
			$('<button type="button" class="button yoga-gallery-framing__screen-button"></button>')
				.attr('data-screen', screen[0]).text(screen[1])
				.on('click', function () { instance.screen = screen[0]; updatePreview(instance); })
				.appendTo($screens);
		});
		var $preview = $('<div class="yoga-gallery-framing__preview"></div>').appendTo($panel);
		var $previewBackdrop = $('<img class="yoga-gallery-framing__backdrop" alt="" aria-hidden="true" draggable="false">').appendTo($preview);
		var $previewImage = $('<img alt="Предпросмотр кадра" draggable="false">').appendTo($preview);
		$previewImage.on('load', function () { updatePreview(instance); });
		$('<p class="yoga-gallery-framing__hint">Перетащите изображение, чтобы плавно выбрать его положение внутри кадра.</p>').appendTo($panel);
		var $controls = $('<div class="yoga-gallery-framing__controls"></div>').appendTo($panel);
		var $zoomLabel = $('<label>Масштаб <output></output></label>').appendTo($controls);
		var $zoom = $('<input type="range" min="50" max="300" step="5" value="100">')
			.on('input', function () {
				var frame = normalizedFrame(instance.frames[instance.selectedId]);
				frame.zoom = Number(this.value);
				updateFrame(instance, frame);
			})
			.appendTo($zoomLabel);
		var $xLabel = $('<label>По горизонтали <output></output></label>').appendTo($controls);
		var $x = $('<input type="range" min="0" max="100" step="1" value="50">')
			.on('input', function () {
				var frame = normalizedFrame(instance.frames[instance.selectedId]);
				frame.x = Number(this.value);
				updateFrame(instance, frame);
			})
			.appendTo($xLabel);
		var $yLabel = $('<label>По вертикали <output></output></label>').appendTo($controls);
		var $y = $('<input type="range" min="0" max="100" step="1" value="50">')
			.on('input', function () {
				var frame = normalizedFrame(instance.frames[instance.selectedId]);
				frame.y = Number(this.value);
				updateFrame(instance, frame);
			})
			.appendTo($yLabel);
		$('<button type="button" class="button">Сбросить кадр</button>')
			.on('click', function () {
				delete instance.frames[instance.selectedId];
				writeFrames(instance);
				updatePreview(instance);
			})
			.appendTo($controls);
		var drag = null;
		$preview.on('pointerdown', function (event) {
			if (event.button !== 0 || !instance.selectedId || !$previewImage[0].naturalWidth) {
				return;
			}
			event.preventDefault();
			this.setPointerCapture(event.pointerId);
			var frame = normalizedFrame(instance.frames[instance.selectedId]);
			var geometry = previewGeometry(instance, frame);
			frame.x = geometry.x;
			frame.y = geometry.y;
			drag = { x: event.clientX, y: event.clientY, frame: frame };
		}).on('pointermove', function (event) {
			if (!drag) {
				return;
			}
			var width = this.clientWidth;
			var height = this.clientHeight;
			var ratio = $previewImage[0].naturalWidth / $previewImage[0].naturalHeight;
			var coverWidth = Math.max(width, height * ratio);
			var coverHeight = Math.max(height, width / ratio);
			var zoom = previewGeometry(instance, drag.frame).zoom;
			var overflowX = coverWidth * zoom - width;
			var overflowY = coverHeight * zoom - height;
			var frame = $.extend({}, drag.frame);
			if (Math.abs(overflowX) > 1) {
				frame.x = clamp(Math.round(drag.frame.x - (event.clientX - drag.x) * 100 / overflowX), 0, 100);
			}
			if (Math.abs(overflowY) > 1) {
				frame.y = clamp(Math.round(drag.frame.y - (event.clientY - drag.y) * 100 / overflowY), 0, 100);
			}
			updateFrame(instance, frame);
		}).on('pointerup pointercancel lostpointercapture', function () {
			drag = null;
		});

		$field.children('.acf-input').prepend($root);
		instance.$root = $root;
		instance.$panel = $panel;
		instance.$preview = $preview;
		instance.$previewBackdrop = $previewBackdrop;
		instance.$previewImage = $previewImage;
		instance.$zoom = $zoom;
		instance.$zoomValue = $zoomLabel.find('output');
		instance.$x = $x;
		instance.$xValue = $xLabel.find('output');
		instance.$y = $y;
		instance.$yValue = $yLabel.find('output');
		instances.set(field, instance);
		renderImages(instance);

		var container = $field.find('.acf-gallery-attachments').first()[0];
		if (container) {
			container.addEventListener('mousedown', function (event) {
				if (event.target.closest('.yoga-gallery-framing__open')) {
					event.stopPropagation();
				}
			}, true);
			container.addEventListener('click', function (event) {
				var button = event.target.closest('.yoga-gallery-framing__open');
				if (!button || !container.contains(button)) {
					return;
				}
				event.preventDefault();
				event.stopPropagation();
				var id = button.getAttribute('data-attachment-id');
				var image = instance.images.find(function (item) { return item.id === id; });
				if (image) {
					openFrame(instance, image);
				}
			}, true);
		}
		if (container && window.MutationObserver) {
			var refreshTimer = null;
			new MutationObserver(function () {
				window.clearTimeout(refreshTimer);
				refreshTimer = window.setTimeout(function () { renderImages(instance); }, 50);
			}).observe(container, { childList: true, subtree: true });
		}
		fieldInSameRow($field, 'timing').find('input[type="checkbox"]').on('change.yogaGalleryFraming', function () {
			updatePreview(instance);
		});
	}

	function initialize($element) {
		var $fields = $element.find('.acf-field-gallery');
		if ($element.is('.acf-field-gallery')) {
			$fields = $fields.add($element);
		}
		$fields.each(function () { setupField(this); });
	}

	acf.addAction('ready', function () { initialize($(document)); });
	acf.addAction('append', function ($element) { initialize($element); });
	acf.addAction('show', function ($element) { initialize($element); });
})(jQuery);
