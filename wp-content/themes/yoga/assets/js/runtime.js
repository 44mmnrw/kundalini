(function () {
	'use strict';

	var config = window.yogaAppRuntime || {};
	if (!config.enabled || !Array.isArray(config.selectors) || !config.selectors.length) {
		return;
	}

	var selectors = config.selectors;
	var offlineMessage = config.offlineMessage || 'Контент доступен только на сайте. Сохранённая копия страницы недоступна.';
	var blockDevtools = config.blockDevtools === true || config.blockDevtools === '1' || config.blockDevtools === 1;
	var isOfflinePage = window.location.protocol === 'file:';

	function getElement(node) {
		if (!node) {
			return null;
		}
		return node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
	}

	function isAllowedElement(el) {
		if (!el) {
			return false;
		}
		return !!el.closest('[data-yoga-copy-allow], input, textarea, select, [contenteditable="true"]');
	}

	function isInProtectedArea(el) {
		if (!el) {
			return false;
		}
		for (var i = 0; i < selectors.length; i += 1) {
			if (el.closest(selectors[i])) {
				return true;
			}
		}
		return false;
	}

	function isProtectedTarget(el) {
		return !!(el && document.body && document.body.contains(el)) && !isAllowedElement(el);
	}

	function selectionTouchesProtectedArea() {
		var selection = window.getSelection();
		if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
			return false;
		}

		for (var i = 0; i < selection.rangeCount; i += 1) {
			var range = selection.getRangeAt(i);
			var nodes = [range.startContainer, range.endContainer, range.commonAncestorContainer];

			for (var j = 0; j < nodes.length; j += 1) {
				if (isProtectedTarget(getElement(nodes[j]))) {
					return true;
				}
			}
		}

		return false;
	}

	function blockIfProtected(event, target) {
		var el = getElement(target || event.target);
		if (!isProtectedTarget(el) && !selectionTouchesProtectedArea()) {
			return;
		}

		event.preventDefault();
	}

	function replaceOfflineProtectedContent() {
		document.documentElement.classList.add('yoga-copy-offline');

		for (var i = 0; i < selectors.length; i += 1) {
			var nodes = document.querySelectorAll(selectors[i]);

			for (var j = 0; j < nodes.length; j += 1) {
				if (nodes[j].getAttribute('data-yoga-offline-replaced') === '1') {
					continue;
				}

				nodes[j].setAttribute('data-yoga-offline-replaced', '1');
				nodes[j].innerHTML = '<p class="yoga-copy-offline-notice">' + offlineMessage + '</p>';
			}
		}
	}

	function initOfflineGuard() {
		if (!isOfflinePage) {
			return;
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', replaceOfflineProtectedContent);
		} else {
			replaceOfflineProtectedContent();
		}
	}

	function blockSaveShortcuts(event) {
		if (!event.ctrlKey && !event.metaKey) {
			return;
		}

		var key = String(event.key || '').toLowerCase();
		if (key !== 's') {
			return;
		}

		if (isAllowedElement(document.activeElement)) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();
	}

	function blockBrowserShortcuts(event) {
		var active = document.activeElement;
		if (isAllowedElement(active)) {
			return;
		}

		var key = String(event.key || '').toLowerCase();
		var code = String(event.code || '').toLowerCase();
		var hasModifier = event.ctrlKey || event.metaKey;

		var blocked = false;

		if (blockDevtools && (key === 'f12' || code === 'f12')) {
			blocked = true;
		}

		if (blockDevtools && hasModifier && event.shiftKey && ['i', 'j', 'c', 'k'].indexOf(key) !== -1) {
			blocked = true;
		}

		if (hasModifier && ['s', 'u', 'p'].indexOf(key) !== -1) {
			blocked = true;
		}

		if (hasModifier && ['c', 'a', 'x'].indexOf(key) !== -1) {
			blocked = isProtectedTarget(getElement(active)) || selectionTouchesProtectedArea() || isInProtectedArea(getElement(event.target));
		}

		if (!blocked) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();
	}

	initOfflineGuard();

	document.addEventListener('contextmenu', function (event) {
		blockIfProtected(event);
	}, true);

	document.addEventListener('copy', function (event) {
		blockIfProtected(event);
	}, true);

	document.addEventListener('cut', function (event) {
		blockIfProtected(event);
	}, true);

	document.addEventListener('dragstart', function (event) {
		blockIfProtected(event);
	}, true);

	document.addEventListener('selectstart', function (event) {
		blockIfProtected(event);
	}, true);

	document.addEventListener('keydown', blockSaveShortcuts, true);
	document.addEventListener('keydown', blockBrowserShortcuts, true);

	document.addEventListener('keydown', function (event) {
		if (!event.ctrlKey && !event.metaKey) {
			return;
		}

		var key = String(event.key || '').toLowerCase();
		if (['c', 'a', 'x', 'u', 'p'].indexOf(key) === -1) {
			return;
		}

		var active = document.activeElement;
		if (isAllowedElement(active)) {
			return;
		}

		if (isProtectedTarget(getElement(active)) || selectionTouchesProtectedArea()) {
			event.preventDefault();
		}
	}, true);

	window.addEventListener('beforeprint', function () {
		document.documentElement.classList.add('yoga-copy-printing');
	});

	window.addEventListener('afterprint', function () {
		document.documentElement.classList.remove('yoga-copy-printing');
	});
})();
