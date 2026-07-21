/**
 * Клиентский модуль: library filters.
 *
 * @package Yoga
 */
(function (window, $) {
	'use strict';

	var values = new Map();
	var requests = Object.create(null);
	var timers = Object.create(null);
	var storageKey = 'yoga.libraryFilters.v1';

	function normalizeName(input) {
		return String(input && input.name || '').replace(/\[\]$/, '');
	}

	function inputKey(input) {
		return normalizeName(input) + ':' + String(input && input.value || '');
	}

	function allInputs() {
		return $('input.library-filter-input, .section-kriyi .filter input[type="checkbox"]');
	}

	function storageScope() {
		return $('.section-kriyi').length ? 'practices' : 'library';
	}

	function readStorage() {
		try {
			var parsed = JSON.parse(window.sessionStorage.getItem(storageKey) || '{}');
			return parsed && typeof parsed === 'object' ? parsed : {};
		} catch (error) {
			return {};
		}
	}

	function writeStorage(selected) {
		try {
			var stored = readStorage();
			stored[storageScope()] = selected;
			window.sessionStorage.setItem(storageKey, JSON.stringify(stored));
		} catch (error) {

		}
	}

	function refreshValues() {
		values.clear();
		$('input.library-filter-input').each(function () {
			values.set(this.id, Boolean(this.checked));
		});
	}

	function persist() {
		var selected = [];
		var seen = Object.create(null);
		allInputs().each(function () {
			var key = inputKey(this);
			if (!this.checked || !normalizeName(this) || seen[key]) return;
			seen[key] = true;
			selected.push(key);
		});
		writeStorage(selected);
	}

	function registerFromDom() {
		var stored = readStorage();
		var selected = stored[storageScope()];
		if (Array.isArray(selected)) {
			var selectedMap = Object.create(null);
			selected.forEach(function (key) { selectedMap[key] = true; });
			allInputs().each(function () {
				this.checked = Boolean(selectedMap[inputKey(this)]);
			});
		}
		refreshValues();
	}

	function set(input, checked) {
		if (!input || !normalizeName(input)) return false;
		var key = inputKey(input);
		var nextValue = Boolean(checked);
		allInputs().each(function () {
			if (inputKey(this) === key) this.checked = nextValue;
		});
		refreshValues();
		persist();
		return nextValue;
	}

	function toggle(input) {
		return set(input, !Boolean(input && input.checked));
	}

	function selectedByTaxonomy() {
		var filters = {};
		$('input.library-filter-input').each(function () {
			if (!values.get(this.id)) return;
			var name = String(this.name || '').replace('[]', '');
			if (!name) return;
			if (!filters[name]) filters[name] = [];
			filters[name].push(this.value);
		});
		return filters;
	}

	function selectedCount() {
		var count = 0;
		var seen = Object.create(null);
		allInputs().each(function () {
			var key = inputKey(this);
			if (!this.checked || seen[key]) return;
			seen[key] = true;
			count += 1;
		});
		return count;
	}

	function request(channel, options) {
		if (requests[channel] && requests[channel].readyState !== 4) {
			requests[channel].abort();
		}
		requests[channel] = $.ajax(options);
		return requests[channel];
	}

	function debounce(channel, callback, delay) {
		window.clearTimeout(timers[channel]);
		timers[channel] = window.setTimeout(callback, delay);
	}

	function clear() {
		allInputs().prop('checked', false);
		refreshValues();
		persist();
	}

	window.YogaLibraryFiltersCore = {
		registerFromDom: registerFromDom,
		set: set,
		toggle: toggle,
		selectedByTaxonomy: selectedByTaxonomy,
		selectedCount: selectedCount,
		request: request,
		debounce: debounce,
		clear: clear
	};

	$(registerFromDom);
})(window, jQuery);
