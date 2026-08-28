(function ($) {
	'use strict';

	if (typeof acf === 'undefined' || typeof yogaPracticeEditor === 'undefined') {
		return;
	}

	var config = yogaPracticeEditor;
	var labels = config.labels || {};
	var layoutLabels = config.layoutLabels || {};
	var editor = null;
	var refreshTimer = null;

	function spriteIcon(symbolId, className) {
		var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		var use = document.createElementNS('http://www.w3.org/2000/svg', 'use');

		svg.setAttribute('class', className || 'yoga-practice-editor__action-icon');
		svg.setAttribute('aria-hidden', 'true');
		svg.setAttribute('focusable', 'false');
		use.setAttribute('href', String(config.spriteUrl || '') + '#' + symbolId);
		svg.appendChild(use);

		return svg.outerHTML;
	}

	function directRows($field) {
		return $field
			.children('.acf-input')
			.children('.acf-repeater')
			.children('.acf-table')
			.children('tbody')
			.children('.acf-row:not(.acf-clone)');
	}

	function directField($container, name) {
		return $container.children('.acf-fields').children('.acf-field[data-name="' + name + '"]').first();
	}

	function inputValue($field) {
		var $input = $field.find('input:not([type="hidden"]), textarea, select').first();
		return $input.length ? $.trim(String($input.val() || '')) : '';
	}

	function storedFieldValue($field) {
		var value = inputValue($field);
		if (value !== '') {
			return value;
		}

		var $hidden = $field.find('input[type="hidden"]').filter(function () {
			return $.trim(String($(this).val() || '')) !== '';
		}).first();
		return $hidden.length ? $.trim(String($hidden.val() || '')) : '';
	}

	function isValidHttpsUrl(value, hostValidator) {
		try {
			var url = new window.URL(String(value || ''));
			return url.protocol === 'https:' && hostValidator(url.hostname.toLowerCase(), url);
		} catch (error) {
			return false;
		}
	}

	function anchor07HasValidVideo($layout) {
		var source = inputValue(directField($layout, 'video_source')) || 'file';
		if (source === 'file') {
			return storedFieldValue(directField($layout, 'media_file')) !== '';
		}
		if (source === 'kinescope') {
			return isValidHttpsUrl(inputValue(directField($layout, 'kinescope_url')), function (host) {
				return host === 'kinescope.io' || host.slice(-13) === '.kinescope.io';
			});
		}
		if (source === 'youtube') {
			return isValidHttpsUrl(inputValue(directField($layout, 'youtube_url')), function (host, url) {
				host = host.replace(/^(?:www\.|m\.)/, '');
				var path = url.pathname.replace(/^\/+|\/+$/g, '');
				var videoId = '';
				if (host === 'youtu.be') {
					videoId = path.split('/')[0] || '';
				} else if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
					videoId = url.searchParams.get('v') || '';
					if (!videoId) {
						var match = path.match(/^(?:embed|shorts|live|v)\/([^/]+)/);
						videoId = match ? match[1] : '';
					}
				}
				return /^[A-Za-z0-9_-]{11}$/.test(videoId);
			});
		}
		return false;
	}

	function rowFieldValue($row, name) {
		var $field = $row.children('td.acf-fields').children('.acf-field[data-name="' + name + '"]').first();
		return inputValue($field);
	}

	function nestedFieldInRow($row, name) {
		return $row
			.children('td.acf-fields')
			.find('.acf-field[data-name="' + name + '"]')
			.filter(function () {
				return $(this).closest('tr.acf-row').is($row);
			})
			.first();
	}

	function layoutId($layout) {
		var id = $layout.attr('data-id');
		if (!id) {
			id = 'yoga-layout-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
			$layout.attr('data-id', id);
		}
		return id;
	}

	function scheduleRefresh(preferredId) {
		window.clearTimeout(refreshTimer);
		refreshTimer = window.setTimeout(function () {
			if (!editor) {
				return;
			}
			editor.decorate();
			if (editor.modalStack.length) {
				editor.updateModalLayer();
				editor.positionModalSurfaces();
				return;
			}
			editor.refreshNavigation(preferredId);
			editor.positionSectionMenu();
		}, 80);
	}

	function PracticeEditor($sectionsField) {
		this.$sectionsField = $sectionsField;
		this.sectionsField = acf.getField($sectionsField);
		this.$fieldGroup = $sectionsField.closest('.acf-postbox');
		this.$postForm = $sectionsField.closest('form');
		this.postFormId = this.$postForm.attr('id') || ('yoga-practice-form-' + Date.now());
		if (this.$postForm.length && !this.$postForm.attr('id')) {
			this.$postForm.attr('id', this.postFormId);
		}
		this.$sourceFields = $sectionsField.parent('.acf-fields');
		this.activeId = 'general';
		this.$workspace = $();
		this.$navigation = $();
		this.$generalPanel = $();
		this.$titleField = $();
		this.$sectionPanel = $();
		this.$panelTitle = $();
		this.$openPanel = $();
		this.$visualMap = $();
		this.$modalBackdrop = $();
		this.$modalContext = $();
		this.$portalPlaceholder = $();
		this.$portalParent = $();
		this.portalNextSibling = null;
		this.portalScrollLeft = 0;
		this.portalScrollTop = 0;
		this.portalScrollPositions = [];
		this.modalStack = [];
		this.taxonomySyncing = false;
		this.taxonomySignature = '';
		this.taxonomyUnsubscribe = null;
		this.taxonomyPanelObserver = null;
		this.$durationTaxonomyInput = $();
		this.$taxonomyFields = {};
	}

	PracticeEditor.prototype.layouts = function () {
		if (this.sectionsField && typeof this.sectionsField.$layouts === 'function') {
			return this.sectionsField.$layouts();
		}

		return this.$sectionsField.find('> .acf-input > .acf-flexible-content > .values > .layout');
	};

	PracticeEditor.prototype.setSectionMenuOpen = function (isOpen) {
		var self = this;
		this.$addSectionMenu.prop('hidden', !isOpen);
		this.$addSectionButton.attr('aria-expanded', isOpen ? 'true' : 'false');

		if (!isOpen) {
			this.$addSectionMenu.removeClass('is-opening-up is-opening-down');
			return;
		}

		this.positionSectionMenu();
		window.requestAnimationFrame(function () {
			self.positionSectionMenu();
		});
	};

	PracticeEditor.prototype.positionSectionMenu = function () {
		var button = this.$addSectionButton.get(0);
		var menu = this.$addSectionMenu.get(0);
		if (!button || !menu || this.$addSectionMenu.prop('hidden')) {
			return;
		}

		var buttonRect = button.getBoundingClientRect();
		var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
		var menuHeight = menu.scrollHeight;
		var gap = 8;
		var spaceAbove = Math.max(0, buttonRect.top - gap);
		var spaceBelow = Math.max(0, viewportHeight - buttonRect.bottom - gap);
		var opensUp = spaceBelow < menuHeight && spaceAbove > spaceBelow;

		this.$addSectionMenu
			.toggleClass('is-opening-up', opensUp)
			.toggleClass('is-opening-down', !opensUp);
	};

	PracticeEditor.prototype.getEditedTaxonomyTermIds = function (settings) {
		if (!settings || !settings.restBase || !window.wp || !wp.data) {
			return [];
		}

		var store = wp.data.select('core/editor');
		if (!store) {
			return [];
		}

		var termIds = store.getEditedPostAttribute(settings.restBase);
		if (!Array.isArray(termIds)) {
			termIds = store.getCurrentPostAttribute(settings.restBase);
		}

		return Array.isArray(termIds)
			? termIds.map(function (termId) { return parseInt(termId, 10); }).filter(function (termId) { return termId > 0; })
			: [];
	};

	PracticeEditor.prototype.findTaxonomyTerm = function (settings, property, value) {
		var normalizedValue = String(value || '').toLowerCase();
		var terms = settings && Array.isArray(settings.terms) ? settings.terms : [];

		for (var index = 0; index < terms.length; index++) {
			if (String(terms[index][property] || '').toLowerCase() === normalizedValue) {
				return terms[index];
			}
		}

		return null;
	};

	PracticeEditor.prototype.setEditedTaxonomyTerms = function (settings, termIds) {
		if (!settings || !settings.restBase || !window.wp || !wp.data || !wp.data.dispatch('core/editor')) {
			return;
		}

		var currentIds = this.getEditedTaxonomyTermIds(settings).slice().sort(function (a, b) { return a - b; });
		var nextIds = termIds.slice().sort(function (a, b) { return a - b; });
		if (currentIds.join(',') === nextIds.join(',')) {
			return;
		}

		var edits = {};
		edits[settings.restBase] = nextIds;
		wp.data.dispatch('core/editor').editPost(edits);
	};

	PracticeEditor.prototype.syncTaxonomyFromGeneralField = function (type) {
		if (this.taxonomySyncing) {
			return;
		}

		var syncConfig = config.taxonomySync || {};
		var settings = syncConfig[type] || null;
		if (type === 'types' || type === 'goals') {
			var termIds = (this.$taxonomyFields[type] || $()).find('input[type="checkbox"]:checked').map(function () {
				return parseInt(this.value, 10);
			}).get().filter(function (termId) {
				return termId > 0;
			});
			this.setEditedTaxonomyTerms(settings, termIds);
			return;
		}

		var value = type === 'difficulty'
			? this.$generalPanel.find('.acf-field[data-key="field_practice_level"] select').first().val()
			: this.$durationTaxonomyInput.val();
		var term = this.findTaxonomyTerm(settings, type === 'difficulty' ? 'value' : 'name', value);

		this.setEditedTaxonomyTerms(settings, term ? [parseInt(term.id, 10)] : []);
	};

	PracticeEditor.prototype.syncGeneralFieldFromTaxonomy = function (type, termIds) {
		var syncConfig = config.taxonomySync || {};
		var settings = syncConfig[type] || null;
		var term = termIds.length ? this.findTaxonomyTerm(settings, 'id', termIds[0]) : null;
		var value = term ? String(type === 'difficulty' ? term.value : term.name) : '';

		this.taxonomySyncing = true;
		if (type === 'difficulty') {
			var $difficultyInput = this.$generalPanel.find('.acf-field[data-key="field_practice_level"] select').first();
			if ($difficultyInput.length && String($difficultyInput.val() || '') !== value) {
				$difficultyInput.val(value).trigger('change');
			}
		} else if (type === 'duration') {
			var $durationSource = this.$generalPanel.find('.acf-field[data-key="field_practice_time"] input:not([type="hidden"])').first();
			if (this.$durationTaxonomyInput.length && String(this.$durationTaxonomyInput.val() || '') !== value) {
				this.$durationTaxonomyInput.val(value);
			}
			if ($durationSource.length && String($durationSource.val() || '') !== value) {
				$durationSource.val(value).trigger('change');
			}
		} else {
			(this.$taxonomyFields[type] || $()).find('input[type="checkbox"]').each(function () {
				this.checked = termIds.indexOf(parseInt(this.value, 10)) !== -1;
			});
		}
		this.taxonomySyncing = false;
	};

	PracticeEditor.prototype.taxonomyStateSignature = function () {
		var syncConfig = config.taxonomySync || {};
		return ['difficulty', 'duration', 'types', 'goals'].map(function (type) {
			var settings = syncConfig[type] || null;
			return settings && settings.restBase ? this.getEditedTaxonomyTermIds(settings).join(',') : '';
		}, this).join('|');
	};

	PracticeEditor.prototype.createTaxonomyChecklist = function (type, settings) {
		var self = this;
		var terms = settings && Array.isArray(settings.terms) ? settings.terms : [];
		if (!terms.length) {
			return;
		}

		var $field = $('<div class="acf-field yoga-practice-taxonomy-field"><div class="acf-label"><label></label></div><div class="acf-input"><div class="yoga-practice-taxonomy-checklist"></div></div></div>');
		$field.find('.acf-label label').text(String(settings.label || ''));
		var $list = $field.find('.yoga-practice-taxonomy-checklist');
		var children = {};
		terms.forEach(function (term) {
			var parent = parseInt(term.parent, 10) || 0;
			children[parent] = children[parent] || [];
			children[parent].push(term);
		});

		function appendTerms(parentId, depth) {
			(children[parentId] || []).forEach(function (term) {
				var inputId = 'yoga-practice-' + type + '-' + term.id;
				var $label = $('<label class="yoga-practice-taxonomy-option"><input type="checkbox"><span></span></label>');
				$label.css('--yoga-taxonomy-depth', depth);
				$label.find('input').attr({
					id: inputId,
					name: 'yoga_practice_taxonomies[' + type + '][]',
					value: term.id
				});
				$label.find('span').text(term.name);
				$list.append($label);
				appendTerms(parseInt(term.id, 10), depth + 1);
			});
		}
		appendTerms(0, 0);

		$list.on('change.yogaTaxonomySync', 'input[type="checkbox"]', function () {
			self.syncTaxonomyFromGeneralField(type);
		});
		this.$taxonomyFields[type] = $field;
		this.$generalPanel.append($field);
	};

	PracticeEditor.prototype.syncGeneralFieldsFromTaxonomySidebar = function () {
		var self = this;
		var syncConfig = config.taxonomySync || {};
		var panelTypes = {
			'продолжительность': 'duration',
			'сложность': 'difficulty'
		};

		$('.interface-complementary-area .components-panel__body').each(function () {
			var $panel = $(this);
			var heading = $.trim(String($panel.children('.components-panel__body-title').text() || '')).toLowerCase();
			var type = panelTypes[heading] || '';
			if (!type) {
				return;
			}

			var settings = syncConfig[type] || null;
			var termIds = [];
			$panel.find('input[type="checkbox"]:checked').each(function () {
				var inputId = String(this.id || '');
				var label = inputId ? $.trim(String($('label[for="' + inputId + '"]').text() || '')) : '';
				var term = self.findTaxonomyTerm(settings, 'name', label);
				if (term) {
					termIds.push(parseInt(term.id, 10));
				}
			});
			self.syncGeneralFieldFromTaxonomy(type, termIds);
		});
	};

	PracticeEditor.prototype.initializeTaxonomySync = function () {
		var self = this;
		var syncConfig = config.taxonomySync || {};
		var durationSettings = syncConfig.duration || {};
		var durationTerms = Array.isArray(durationSettings.terms) ? durationSettings.terms : [];
		var $durationField = this.$generalPanel.find('.acf-field[data-key="field_practice_time"]').first();
		var $durationSource = $durationField.find('input:not([type="hidden"])').first();

		if ($durationField.length && $durationSource.length && durationTerms.length) {
			var currentDuration = String($durationSource.val() || '');
			this.$durationTaxonomyInput = $('<select class="yoga-practice-taxonomy-select" aria-label="Продолжительность практики"><option value="">Не выбрано</option></select>');
			durationTerms.forEach(function (term) {
				$('<option></option>').attr('value', term.name).text(term.name).appendTo(self.$durationTaxonomyInput);
			});
			if (currentDuration && !this.findTaxonomyTerm(durationSettings, 'name', currentDuration)) {
				$('<option></option>').attr('value', currentDuration).text(currentDuration + ' (старое значение)').appendTo(this.$durationTaxonomyInput);
			}
			this.$durationTaxonomyInput.val(currentDuration);
			$durationSource.addClass('yoga-practice-taxonomy-source');
			$durationSource.after(this.$durationTaxonomyInput);
		}

		this.createTaxonomyChecklist('types', syncConfig.types || {});
		this.createTaxonomyChecklist('goals', syncConfig.goals || {});
		if (!this.$generalPanel.find('input[name="_yoga_practice_taxonomy_nonce"]').length) {
			$('<input type="hidden" name="_yoga_practice_taxonomy_nonce">')
				.val(String(config.taxonomyNonce || ''))
				.appendTo(this.$generalPanel);
		}
		['types', 'goals'].forEach(function (type) {
			var settings = syncConfig[type] || {};
			var selectedIds = Array.isArray(settings.selectedIds)
				? settings.selectedIds.map(function (termId) { return parseInt(termId, 10); }).filter(function (termId) { return termId > 0; })
				: [];
			self.syncGeneralFieldFromTaxonomy(type, selectedIds);
		});

		this.$generalPanel
			.find('.acf-field[data-key="field_practice_level"] select')
			.off('change.yogaTaxonomySync')
			.on('change.yogaTaxonomySync', function () {
				self.syncTaxonomyFromGeneralField('difficulty');
			});
		this.$durationTaxonomyInput
			.off('change.yogaTaxonomySync')
			.on('change.yogaTaxonomySync', function () {
				var value = String($(this).val() || '');
				$durationSource.val(value).trigger('change');
				self.syncTaxonomyFromGeneralField('duration');
			});
		$(document)
			.off('change.yogaPracticeTaxonomySidebar')
			.on('change.yogaPracticeTaxonomySidebar', '.interface-complementary-area input[type="checkbox"]', function () {
				window.setTimeout(function () {
					self.syncGeneralFieldsFromTaxonomySidebar();
				}, 0);
			});

		if (!window.wp || !wp.data || !wp.data.select('core/editor')) {
			return;
		}

		['difficulty', 'duration', 'types', 'goals'].forEach(function (type) {
			var settings = syncConfig[type] || null;
			var termIds = self.getEditedTaxonomyTermIds(settings);
			if (termIds.length) {
				self.syncGeneralFieldFromTaxonomy(type, termIds);
			} else if (type === 'difficulty' || type === 'duration') {
				self.syncTaxonomyFromGeneralField(type);
			}
		});

		this.taxonomySignature = this.taxonomyStateSignature();
		this.taxonomyUnsubscribe = wp.data.subscribe(function () {
			var nextSignature = self.taxonomyStateSignature();
			if (nextSignature === self.taxonomySignature) {
				return;
			}
			self.taxonomySignature = nextSignature;
			['difficulty', 'duration', 'types', 'goals'].forEach(function (type) {
				var settings = syncConfig[type] || null;
				self.syncGeneralFieldFromTaxonomy(type, self.getEditedTaxonomyTermIds(settings));
			});
		});
	};

	PracticeEditor.prototype.hideDuplicatedTaxonomyPanels = function () {
		var taxonomies = Array.isArray(config.hiddenSidebarTaxonomies)
			? config.hiddenSidebarTaxonomies
			: [];

		if (!taxonomies.length) {
			return;
		}

		function normalized(value) {
			return $.trim(String(value || '')).replace(/\s+/g, ' ').toLowerCase();
		}

		var labelsToHide = taxonomies.map(function (taxonomy) {
			return normalized(taxonomy && taxonomy.label);
		}).filter(Boolean);

		function hidePanels() {
			if (window.wp && wp.data) {
				try {
					var editorStore = wp.data.dispatch('core/editor');
					if (editorStore && typeof editorStore.removeEditorPanel === 'function') {
						taxonomies.forEach(function (taxonomy) {
							if (taxonomy && taxonomy.slug) {
								editorStore.removeEditorPanel('taxonomy-panel-' + taxonomy.slug);
							}
						});
					}
				} catch (error) {
					// Keep the DOM fallback for older WordPress editor versions.
				}
			}

			$('.interface-complementary-area .components-panel__body').each(function () {
				var $panel = $(this);
				var title = normalized($panel.children('.components-panel__body-title').text());
				if (labelsToHide.indexOf(title) !== -1) {
					$panel.addClass('yoga-practice-hidden-sidebar-panel').attr('aria-hidden', 'true');
				}
			});
		}

		hidePanels();
		window.setTimeout(hidePanels, 250);
		window.setTimeout(hidePanels, 1000);

		if (!this.taxonomyPanelObserver && window.MutationObserver) {
			this.taxonomyPanelObserver = new MutationObserver(function () {
				hidePanels();
			});
			this.taxonomyPanelObserver.observe(document.body, {
				childList: true,
				subtree: true
			});
		}
	};

	PracticeEditor.prototype.build = function () {
		var self = this;
		var $generalFields = this.$sourceFields.children(
			'.acf-field[data-key="field_practice_level"], ' +
			'.acf-field[data-key="field_practice_time"], ' +
			'.acf-field[data-key="field_practice_download"]'
		);
		var $shortDescriptionField = $('.acf-field[data-key="field_practice_short_description"]').first();
		var $shortDescriptionPostbox = $shortDescriptionField.closest('.acf-postbox');
		var $guestField = $('.acf-field[data-key="field_practice_open_for_guests"]').first();
		var $guestPostbox = $guestField.closest('.acf-postbox');
		var currentTitle = '';
		if (window.wp && wp.data && wp.data.select('core/editor')) {
			currentTitle = String(wp.data.select('core/editor').getEditedPostAttribute('title') || '');
		} else {
			currentTitle = String($('#title').val() || '');
		}
		this.$titleField = $(
			'<div class="acf-field acf-field-text yoga-practice-title-field" data-name="practice_post_title">' +
				'<div class="acf-label"><label for="yoga-practice-post-title">Название практики <span class="acf-required">*</span></label></div>' +
				'<div class="acf-input"><div class="acf-input-wrap"><input id="yoga-practice-post-title" type="text" class="widefat" autocomplete="off" placeholder="Введите название практики"></div></div>' +
			'</div>'
		);
		this.$titleField.find('input').val(currentTitle).on('input change', function () {
			var value = String($(this).val() || '');
			if (window.wp && wp.data && wp.data.dispatch('core/editor')) {
				wp.data.dispatch('core/editor').editPost({ title: value });
			} else {
				$('#title').val(value).trigger('input');
			}
			self.renderVisualMap();
		});

		this.$workspace = $(
			'<div class="yoga-practice-editor">' +
				'<header class="yoga-practice-editor__hero">' +
					'<div>' +
						'<span class="yoga-practice-editor__eyebrow">Kundalini Class</span>' +
						'<h2>' + labels.editorTitle + '</h2>' +
						'<p>' + labels.editorDescription + '</p>' +
					'</div>' +
					'<a class="yoga-practice-editor__classic adminify-active" href="' + config.classicUrl + '">' + labels.classicView + '</a>' +
				'</header>' +
				'<div class="yoga-practice-editor__body">' +
					'<aside class="yoga-practice-editor__sidebar" aria-label="Разделы практики">' +
						'<button type="button" class="yoga-practice-editor__nav-button yoga-practice-editor__nav-button--general is-active" data-panel="general">' +
							'<span class="yoga-practice-editor__nav-index yoga-practice-editor__nav-index--settings">' + spriteIcon('lk-sidebar-settings', 'yoga-practice-editor__general-icon') + '</span>' +
							'<span class="yoga-practice-editor__nav-copy"><span class="yoga-practice-editor__nav-title">' + labels.general + '</span><small>Параметры практики</small></span>' +
						'</button>' +
						'<ol class="yoga-practice-editor__navigation"></ol>' +
						'<div class="yoga-practice-editor__add-section-wrap">' +
							'<div class="yoga-practice-editor__section-menu" id="yoga-practice-section-menu" role="menu" hidden></div>' +
							'<button type="button" class="yoga-practice-editor__add-section" aria-haspopup="menu" aria-controls="yoga-practice-section-menu" aria-expanded="false"><span aria-hidden="true">+</span>' + labels.addSection + '</button>' +
						'</div>' +
					'</aside>' +
					'<main class="yoga-practice-editor__content">' +
						'<div class="yoga-practice-editor__panel-heading">' +
							'<span class="yoga-practice-editor__panel-kicker">Редактирование</span>' +
							'<h3></h3>' +
							'<p>Поля откроются в отдельном окне без вложенных вкладок.</p>' +
							'<button type="button" class="yoga-practice-editor__open-panel"><span>Открыть редактор</span><span class="yoga-practice-editor__open-panel-icon" aria-hidden="true">' + spriteIcon('icon-edit') + '</span></button>' +
						'</div>' +
						'<div class="yoga-practice-visual-map" aria-live="polite"></div>' +
						'<section class="yoga-practice-editor__panel yoga-practice-editor__panel--general is-active" data-panel="general"></section>' +
						'<section class="yoga-practice-editor__panel yoga-practice-editor__panel--section" data-panel="section"></section>' +
					'</main>' +
				'</div>' +
			'</div>'
		);

		this.$navigation = this.$workspace.find('.yoga-practice-editor__navigation');
		this.$generalPanel = this.$workspace.find('.yoga-practice-editor__panel--general');
		this.$sectionPanel = this.$workspace.find('.yoga-practice-editor__panel--section');
		this.$panelTitle = this.$workspace.find('.yoga-practice-editor__panel-heading h3');
		this.$openPanel = this.$workspace.find('.yoga-practice-editor__open-panel');
		this.$visualMap = this.$workspace.find('.yoga-practice-visual-map');
		this.$addSectionButton = this.$workspace.find('.yoga-practice-editor__add-section');
		this.$addSectionMenu = this.$workspace.find('.yoga-practice-editor__section-menu');
		Object.keys(layoutLabels).forEach(function (type) {
			$('<button type="button" role="menuitem"></button>')
				.attr('data-layout', type)
				.text(layoutLabels[type])
				.appendTo(self.$addSectionMenu);
		});
		this.$modalBackdrop = $('<div class="yoga-practice-modal-backdrop" aria-hidden="true"></div>').appendTo(this.$workspace);
		this.$modalContext = this.$fieldGroup.closest('.edit-post-meta-boxes-main');

		this.$generalPanel.append(this.$titleField);
		if ($shortDescriptionField.length) {
			this.$generalPanel.append($shortDescriptionField);
			$shortDescriptionPostbox.addClass('yoga-practice-editor__source-postbox');
		}
		this.$generalPanel.append($generalFields);
		if ($guestField.length) {
			this.$generalPanel.append($guestField);
			$guestPostbox.addClass('yoga-practice-editor__source-postbox');
		}
		this.$sectionPanel.append(this.$sectionsField);
		this.$sourceFields.append(this.$workspace);
		this.$fieldGroup.addClass('yoga-practice-editor__host-postbox');

		this.$workspace.on('click', '.yoga-practice-editor__nav-button', function () {
			self.select($(this).attr('data-panel'));
		});

		this.$workspace.on('click', '.yoga-practice-editor__open-panel', function () {
			self.openSelectedPanel();
		});

		this.$workspace.on('click', '.yoga-practice-visual-map__node', function () {
			var fieldId = $(this).attr('data-field-id');
			if (fieldId) {
				self.openVisualMapField(fieldId);
				return;
			}
			var panelId = $(this).attr('data-panel-id');
			if (panelId) {
				self.select(panelId);
				self.openSelectedPanel();
				return;
			}
			var rowId = $(this).attr('data-row-id');
			if (rowId) {
				self.openVisualMapRow(rowId);
				return;
			}
			self.openSelectedPanel();
		});

		this.$workspace.on('click', '[data-yoga-action="add-exercise"]', function (event) {
			event.preventDefault();
			event.stopPropagation();
			self.addExercise($(this).attr('data-step-id') || '');
		});

		this.$workspace.on('click', '[data-yoga-action="remove-exercise"]', function (event) {
			event.preventDefault();
			event.stopImmediatePropagation();
			var $trigger = $(this);
			var rowId = $trigger.attr('data-row-id') || '';
			var $row = self.$workspace.find('tr.acf-row[data-yoga-row-id="' + rowId + '"]').first();
			self.removeRepeaterRow($row, $trigger, labels.confirmRemoveExercise);
		});

		this.$workspace.on('click', '[data-yoga-action="remove-step"]', function (event) {
			event.preventDefault();
			event.stopImmediatePropagation();
			var $trigger = $(this);
			var rowId = $trigger.attr('data-row-id') || '';
			var $row = self.$workspace.find('tr.acf-row[data-yoga-row-id="' + rowId + '"]').first();
			self.removeRepeaterRow($row, $trigger, labels.confirmRemoveStep);
		});

		this.$workspace.on('click', '.yoga-practice-editor__add-section', function (event) {
			event.preventDefault();
			event.stopPropagation();
			var willOpen = self.$addSectionMenu.prop('hidden');
			self.setSectionMenuOpen(willOpen);
		});

		this.$addSectionMenu.on('click', '[data-layout]', function (event) {
			event.preventDefault();
			event.stopPropagation();
			var type = String($(this).attr('data-layout') || '');
			if (!type || !self.sectionsField || typeof self.sectionsField.add !== 'function') {
				return;
			}
			if (type === 'anchor_07' && self.layouts().filter('[data-layout="anchor_07"]').length) {
				self.setSectionMenuOpen(false);
				return;
			}
			var $layout = self.sectionsField.add({ layout: type });
			if ($layout && $layout.length) {
				if (type === 'anchor_07') {
					var $technique = self.layouts().filter('[data-layout="anchor_05"]').last();
					if ($technique.length) {
						$layout.insertAfter($technique);
						self.commitOrder();
					}
				}
				scheduleRefresh(layoutId($layout));
			}
		});

		this.$workspace.on('click', '[data-yoga-action="duplicate"]', function (event) {
			event.stopPropagation();
			self.duplicateLayout($(this).closest('[data-layout-id]').attr('data-layout-id'));
		});

		this.$workspace.on('click', '[data-yoga-action="remove"]', function (event) {
			event.stopPropagation();
			self.removeLayout($(this).closest('[data-layout-id]').attr('data-layout-id'));
		});

		this.$workspace.on('click', '.yoga-row-card__toggle', function () {
			self.openRowModal($(this).closest('tr.acf-row'));
		});

		this.$workspace.on('click', '.yoga-accordion-card__edit', function (event) {
			event.preventDefault();
			event.stopPropagation();
			self.openAccordionModal($(this).closest('.acf-accordion'));
		});

		this.$workspace.on('click', '.yoga-accordion-card:not(.yoga-modal-surface) > .acf-accordion-title', function (event) {
			event.preventDefault();
			event.stopPropagation();
			self.openAccordionModal($(this).parent());
		});

		$(document).on('click.yogaPracticeEditor', '.yoga-practice-modal__back, .yoga-practice-modal__close', function () {
			self.closeTopModal();
		});

		$(document).on('click.yogaPracticeSectionMenu', function (event) {
			if (!$(event.target).closest('.yoga-practice-editor__add-section-wrap').length) {
				self.setSectionMenuOpen(false);
			}
		});

		$(document).on('keydown.yogaPracticeEditor', function (event) {
			if (event.key === 'Escape' && !self.$addSectionMenu.prop('hidden')) {
				event.preventDefault();
				self.setSectionMenuOpen(false);
				self.$addSectionButton.trigger('focus');
				return;
			}
			if (!self.modalStack.length) {
				return;
			}
			if (event.key === 'Escape') {
				event.preventDefault();
				self.closeTopModal();
				return;
			}
			if (event.key === 'Tab') {
				self.trapModalFocus(event);
			}
		});

		$(window).on('resize.yogaPracticeEditor scroll.yogaPracticeEditor', function () {
			self.positionModalSurfaces();
			self.positionSectionMenu();
		});
		this.$workspace.closest('.interface-interface-skeleton__content').on('scroll.yogaPracticeSectionMenu', function () {
			self.positionSectionMenu();
		});

		this.initializeNavigationSorting();
		this.decorate();
		this.refreshNavigation('general');

		// Optional Gutenberg integrations must never prevent the ACF editor from rendering.
		try {
			this.initializeTaxonomySync();
		} catch (error) {
			if (window.console && typeof window.console.error === 'function') {
				window.console.error('Practice taxonomy synchronization failed.', error);
			}
		}

		window.setTimeout(function () {
			try {
				self.hideDuplicatedTaxonomyPanels();
			} catch (error) {
				if (window.console && typeof window.console.error === 'function') {
					window.console.error('Practice sidebar cleanup failed.', error);
				}
			}
		}, 0);
	};

	PracticeEditor.prototype.initializeNavigationSorting = function () {
		var self = this;
		this.$navigation.sortable({
			items: '> li[data-layout-id]',
			handle: '.yoga-practice-editor__drag, .yoga-practice-editor__nav-button',
			cancel: '.yoga-practice-editor__nav-actions button',
			axis: 'y',
			placeholder: 'yoga-practice-editor__nav-placeholder',
			update: function () {
				var $wrap = self.layouts().parent();
				self.$navigation.children('[data-layout-id]').each(function () {
					var id = $(this).attr('data-layout-id');
					var $layout = self.layouts().filter('[data-id="' + id + '"]');
					if ($layout.length) {
						$wrap.append($layout);
					}
				});
				self.commitOrder();
			}
		});
	};

	PracticeEditor.prototype.commitOrder = function () {
		if (this.sectionsField && typeof this.sectionsField.render === 'function') {
			this.sectionsField.render();
		}
		if (this.sectionsField && typeof this.sectionsField.$input === 'function') {
			this.sectionsField.$input().trigger('change');
		}
		this.refreshNavigation(this.activeId);
	};

	PracticeEditor.prototype.layoutById = function (id) {
		return this.layouts().filter(function () {
			return layoutId($(this)) === id;
		}).first();
	};

	PracticeEditor.prototype.moveLayout = function (id, direction) {
		var $layout = this.layoutById(id);
		var $target = direction < 0 ? $layout.prev('.layout') : $layout.next('.layout');
		if (!$target.length) {
			return;
		}
		if (direction < 0) {
			$layout.insertBefore($target);
		} else {
			$layout.insertAfter($target);
		}
		this.commitOrder();
	};

	PracticeEditor.prototype.duplicateLayout = function (id) {
		var self = this;
		var $layout = this.layoutById(id);
		if (!$layout.length || !this.sectionsField || typeof this.sectionsField.duplicateLayout !== 'function') {
			return;
		}
		if (String($layout.attr('data-layout') || '') === 'anchor_07') {
			return;
		}
		var $duplicate = this.sectionsField.duplicateLayout($layout);
		window.setTimeout(function () {
			self.decorate();
			self.refreshNavigation($duplicate && $duplicate.length ? layoutId($duplicate) : id);
		}, 100);
	};

	PracticeEditor.prototype.removeLayout = function (id) {
		var $layout = this.layoutById(id);
		if (!$layout.length || !this.sectionsField || typeof this.sectionsField.removeLayout !== 'function') {
			return;
		}
		if (window.confirm(labels.confirmRemoveSection)) {
			this.sectionsField.removeLayout($layout);
			scheduleRefresh(this.activeId);
		}
	};

	PracticeEditor.prototype.removeRepeaterRow = function ($row, $trigger, confirmationMessage) {
		if (!$row || !$row.length) {
			return;
		}

		var self = this;
		var removeRow = function () {
			var $repeaterField = $row.closest('.acf-field-repeater');
			var repeater = acf.getField($repeaterField);
			if (repeater && typeof repeater.remove === 'function') {
				repeater.remove($row);
			} else {
				var $nativeRemove = $row.children('td.acf-row-handle.remove').find('[data-event="remove-row"]').first();
				if (!$nativeRemove.length) {
					return;
				}
				$nativeRemove.trigger($.Event('click', { shiftKey: true }));
			}

			window.setTimeout(function () {
				scheduleRefresh(self.activeId);
			}, 150);
		};

		if (typeof acf.newTooltip === 'function' && $trigger && $trigger.length) {
			$row.addClass('-hover');
			acf.newTooltip({
				confirmRemove: true,
				target: $trigger,
				context: this,
				confirm: function () {
					$row.removeClass('-hover');
					removeRow();
				},
				cancel: function () {
					$row.removeClass('-hover');
				}
			});
			return;
		}

		if (window.confirm(confirmationMessage)) {
			removeRow();
		}
	};

	PracticeEditor.prototype.layoutSummary = function ($layout) {
		var type = String($layout.attr('data-layout') || '');
		var typeLabel = layoutLabels[type] || labels.section;
		var sectionTitle = inputValue(directField($layout, 'section_title'));
		var title = sectionTitle || inputValue(directField($layout, 'main_title')) || inputValue(directField($layout, 'title')) || typeLabel;
		var meta = typeLabel;
		var attention = sectionTitle === '';
		var attentionText = attention ? 'Не заполнено: заголовок раздела' : '';

		if (type === 'anchor_05') {
			var $stepsField = directField($layout, 'steps');
			var $steps = directRows($stepsField);
			var exerciseCount = 0;
			$steps.each(function () {
				var $exerciseField = $(this).children('td.acf-fields').children('.acf-field[data-name="exercise_items"]').first();
				exerciseCount += directRows($exerciseField).length;
			});
			meta = $steps.length + ' шагов · ' + exerciseCount + ' упражнений';
		} else if (type === 'anchor_06') {
			meta = 'Заголовок раздела';
		} else if (type === 'anchor_07') {
			var source = inputValue(directField($layout, 'video_source')) || 'file';
			var sourceLabels = { file: 'Медиафайл', kinescope: 'Kinescope', youtube: 'YouTube' };
			var hasVideo = anchor07HasValidVideo($layout);
			meta = sourceLabels[source] || 'Источник видео';
			if (!hasVideo) {
				attention = true;
				attentionText = 'Не заполнено или некорректно: видео';
			}
		}

		return {
			title: title,
			meta: meta,
			attention: attention,
			attentionText: attentionText
		};
	};

	PracticeEditor.prototype.updateAnchor07Actions = function () {
		var self = this;
		var hasAnchor07 = this.layouts().filter('[data-layout="anchor_07"]').length > 0;
		this.$addSectionMenu.find('[data-layout="anchor_07"]')
			.prop('disabled', hasAnchor07)
			.attr('aria-disabled', hasAnchor07 ? 'true' : 'false')
			.attr('title', hasAnchor07 ? 'В практике уже есть секция «Видео выполнение»' : '');
		this.$navigation.children('[data-layout-id]').each(function () {
			var $item = $(this);
			var $layout = self.layoutById($item.attr('data-layout-id'));
			$item.find('[data-yoga-action="duplicate"]').toggle(String($layout.attr('data-layout') || '') !== 'anchor_07');
		});
	};

	PracticeEditor.prototype.refreshNavigation = function (preferredId) {
		var self = this;
		var $layouts = this.layouts();
		var activeExists = preferredId === 'general' || this.layoutById(preferredId || this.activeId).length;
		this.activeId = activeExists ? (preferredId || this.activeId) : ($layouts.length ? layoutId($layouts.first()) : 'general');
		this.$navigation.empty();

		$layouts.each(function (index) {
			var $layout = $(this);
			var id = layoutId($layout);
			var summary = self.layoutSummary($layout);
			var $item = $(
				'<li class="yoga-practice-editor__nav-item" data-layout-id="' + id + '">' +
					'<span class="yoga-practice-editor__drag" role="button" tabindex="0" aria-label="' + labels.move + '"><span></span><span></span><span></span></span>' +
					'<button type="button" class="yoga-practice-editor__nav-button" data-panel="' + id + '">' +
						'<span class="yoga-practice-editor__nav-index">' + String(index + 1).padStart(2, '0') + '</span>' +
						'<span class="yoga-practice-editor__nav-copy"><span class="yoga-practice-editor__nav-title"></span><small></small></span>' +
					'</button>' +
					'<div class="yoga-practice-editor__nav-actions">' +
						'<button type="button" data-yoga-action="duplicate" aria-label="' + labels.duplicate + '">' + spriteIcon('icon-copy', 'yoga-practice-editor__copy-icon') + '</button>' +
						'<button type="button" data-yoga-action="remove" aria-label="' + labels.remove + '">' + spriteIcon('icon-close') + '</button>' +
					'</div>' +
				'</li>'
			);

			$item.find('.yoga-practice-editor__nav-title').text(summary.title);
			$item.find('.yoga-practice-editor__nav-copy small').text(
				summary.meta + (summary.attention ? ' · ' + (summary.attentionText || 'Нужно проверить') : '')
			);
			$item.toggleClass('needs-attention', summary.attention);
			self.$navigation.append($item);
		});

		if (!$layouts.length) {
			this.$navigation.append('<li class="yoga-practice-editor__empty">' + labels.emptySections + '</li>');
		}

		this.updateAnchor07Actions();

		this.select(this.activeId);
	};

	PracticeEditor.prototype.select = function (id) {
		var $layout = id === 'general' ? $() : this.layoutById(id);
		if (id !== 'general' && !$layout.length) {
			id = 'general';
		}
		this.activeId = id;
		this.$workspace.find('.yoga-practice-editor__nav-item').removeClass('is-active');
		this.$workspace.find('.yoga-practice-editor__nav-button').removeClass('is-active').attr('aria-current', 'false');
		this.$workspace.find('.yoga-practice-editor__nav-button[data-panel="' + id + '"]')
			.addClass('is-active')
			.attr('aria-current', 'page')
			.closest('.yoga-practice-editor__nav-item')
			.addClass('is-active');
		this.$generalPanel.toggleClass('is-active', id === 'general');
		this.$sectionPanel.toggleClass('is-active', id !== 'general');

		this.layouts().removeClass('yoga-practice-editor__layout--active').addClass('yoga-practice-editor__layout--inactive');
		if (id === 'general') {
			this.$panelTitle.text(labels.general);
			this.renderVisualMap();
			return;
		}

		$layout = this.layoutById(id);
		$layout.removeClass('yoga-practice-editor__layout--inactive -collapsed').addClass('yoga-practice-editor__layout--active');
		this.$panelTitle.text(this.layoutSummary($layout).title);
		this.renderVisualMap();
		acf.doAction('show', $layout, 'yoga_practice_editor');
	};

	PracticeEditor.prototype.visualNode = function (title, meta, options) {
		options = options || {};
		var metaText = meta || 'Открыть настройки';
		var $node = $(
			'<button type="button" class="yoga-practice-visual-map__node">' +
				'<span class="yoga-practice-visual-map__number"></span>' +
				'<span class="yoga-practice-visual-map__copy"><span class="yoga-practice-visual-map__title"></span><small></small></span>' +
				'<span class="yoga-practice-visual-map__edit" aria-hidden="true">' + spriteIcon('icon-edit', 'yoga-practice-visual-map__edit-icon') + '</span>' +
			'</button>'
		);
		$node.find('.yoga-practice-visual-map__title').text(title);
		$node.find('small').text(metaText);
		$node.find('.yoga-practice-visual-map__number').text(options.number || '•');
		if (options.panelId) {
			$node.attr('data-panel-id', options.panelId);
		}
		if (options.rowId) {
			$node.attr('data-row-id', options.rowId);
		}
		if (options.fieldId) {
			$node.attr('data-field-id', options.fieldId);
		}
		if (options.attention) {
			var attentionText = options.attentionText || 'Не заполнено';
			if ($.trim(String(metaText)) === $.trim(String(attentionText))) {
				$node.find('small').remove();
			}
			$node.addClass('needs-attention').attr('title', attentionText);
			$('<span class="yoga-practice-visual-map__attention"></span>')
				.text(attentionText)
				.appendTo($node.find('.yoga-practice-visual-map__copy'));
		}
		return $node;
	};

	PracticeEditor.prototype.renderVisualMap = function () {
		var self = this;
		var $map = this.$visualMap.empty().removeClass('is-overview is-section is-technique');
		var $head = $('<div class="yoga-practice-visual-map__head"><span>Структура</span><h4></h4><p></p></div>');
		$map.append($head);

		if (this.activeId === 'general') {
			$map.addClass('is-overview');
			$head.find('h4').text('Схема практики');
			$head.find('p').text('Все разделы в порядке отображения. Нажмите на карточку, чтобы перейти к редактированию.');
			var $flow = $('<div class="yoga-practice-visual-map__flow"></div>');
			var practiceTitle = $.trim(String(this.$titleField.find('input').val() || ''));
			$flow.append(this.visualNode(labels.general, 'Название · уровень · время · файл · доступ', {
				number: '01',
				panelId: 'general',
				attention: practiceTitle === '',
				attentionText: 'Не заполнено: название практики'
			}).addClass('is-root'));
			this.layouts().each(function (index) {
				var $layout = $(this);
				var summary = self.layoutSummary($layout);
				$flow.append(self.visualNode(summary.title, summary.meta, {
					number: String(index + 2).padStart(2, '0'),
					panelId: layoutId($layout),
					attention: summary.attention,
					attentionText: summary.attentionText
				}));
			});
			$map.append($flow);
			return;
		}

		var $layout = this.layoutById(this.activeId);
		if (!$layout.length) {
			return;
		}
		var summary = this.layoutSummary($layout);
		var type = String($layout.attr('data-layout') || '');
		var sectionNumber = String(this.layouts().index($layout) + 1).padStart(2, '0');
		$map.addClass(type === 'anchor_05' ? 'is-technique' : 'is-section');
		$head.find('h4').text('Схема раздела');
		$head.find('p').text(type === 'anchor_05' ? 'Выберите шаг или упражнение — откроется нужный уровень редактора.' : 'Карточка показывает место раздела в структуре практики.');
		$map.append(this.visualNode(summary.title, summary.meta, { number: sectionNumber, panelId: this.activeId, attention: summary.attention, attentionText: summary.attentionText }).addClass('is-root'));

		if (type !== 'anchor_05') {
			this.renderSectionFields($map, $layout);
			return;
		}

		var $branches = $('<div class="yoga-practice-visual-map__branches"></div>');
		var $steps = directRows(directField($layout, 'steps'));
		$branches
			.addClass('has-' + Math.max(1, Math.min($steps.length, 3)) + '-columns')
			.toggleClass('is-empty', !$steps.length);
		$steps.each(function (stepIndex) {
			var $step = $(this);
			var stepId = self.rowVisualId($step);
			var stepTitle = rowFieldValue($step, 'section_title') || labels.step + ' ' + (stepIndex + 1);
			var $exerciseField = $step.children('td.acf-fields').children('.acf-field[data-name="exercise_items"]').first();
			var $exercises = directRows($exerciseField);
			var $branch = $('<section class="yoga-practice-visual-map__branch"></section>');
			var $stepItem = $('<div class="yoga-practice-visual-map__step-item"></div>');
			$stepItem.append(self.visualNode(stepTitle, $exercises.length + ' упражнений', {
				number: String(stepIndex + 1),
				rowId: stepId,
				attention: rowFieldValue($step, 'section_title') === '',
				attentionText: 'Не заполнено: название шага'
			}).addClass('is-step'));
			$stepItem.append(
				$('<button type="button" class="yoga-practice-visual-map__remove-step" data-yoga-action="remove-step">' + spriteIcon('checkout-trash', 'yoga-practice-visual-map__remove-icon') + '</button>')
					.attr({
						'data-row-id': stepId,
						'aria-label': labels.remove + ': ' + stepTitle,
						title: labels.remove
					})
			);
			$branch.append($stepItem);
			var $exerciseList = $('<div class="yoga-practice-visual-map__exercises"></div>');
			$exercises.each(function (exerciseIndex) {
				var $exercise = $(this);
				var exerciseTitle = rowFieldValue($exercise, 'title') || labels.exercise + ' ' + (exerciseIndex + 1);
				var modificationCount = directRows(nestedFieldInRow($exercise, 'modifications')).length;
				var exerciseRowId = self.rowVisualId($exercise);
				var $exerciseItem = $('<div class="yoga-practice-visual-map__exercise-item"></div>');
				$exerciseItem.append(self.visualNode(exerciseTitle, 'Основная + ' + modificationCount + ' дополнительных', {
					number: String(exerciseIndex + 1),
					rowId: exerciseRowId
				}).addClass('is-exercise'));
				$exerciseItem.append(
					$('<button type="button" class="yoga-practice-visual-map__remove-exercise" data-yoga-action="remove-exercise">' + spriteIcon('checkout-trash', 'yoga-practice-visual-map__remove-icon') + '</button>')
						.attr({
							'data-row-id': exerciseRowId,
							'aria-label': labels.remove + ': ' + exerciseTitle,
							title: labels.remove
						})
				);
				$exerciseList.append($exerciseItem);
			});
			$exerciseList.append(
				$('<button type="button" class="yoga-practice-visual-map__add-exercise" data-yoga-action="add-exercise"><span aria-hidden="true">+</span> Добавить упражнение</button>')
					.attr('data-step-id', stepId)
			);
			$branch.append($exerciseList);
			$branches.append($branch);
		});
		if (!$steps.length) {
			$branches.append(
				'<div class="yoga-practice-visual-map__empty-technique">' +
					'<p>В разделе пока нет упражнений.</p>' +
					'<button type="button" class="yoga-practice-visual-map__add-exercise" data-yoga-action="add-exercise"><span aria-hidden="true">+</span> Добавить упражнение</button>' +
				'</div>'
			);
		}
		$map.append($branches);
	};

	PracticeEditor.prototype.fieldVisualSummary = function ($field) {
		var name = String($field.attr('data-name') || '');
		var label = $.trim($field.children('.acf-label').find('label').first().clone().children().remove().end().text()) || name.replace(/_/g, ' ') || 'Поле';
		var count = null;
		var value = '';

		if ($field.hasClass('acf-field-repeater')) {
			count = directRows($field).length;
			value = count + ' элементов';
		} else if ($field.hasClass('acf-field-gallery')) {
			count = $field.find('.acf-gallery-attachment').length;
			value = count + ' изображений';
		} else if ($field.hasClass('acf-field-relationship')) {
			count = $field.find('.values .acf-rel-item').length;
			value = count + ' выбрано';
		} else if ($field.hasClass('acf-field-file') || $field.hasClass('acf-field-image')) {
			value = $.trim($field.find('.file-info strong, .image-wrap img').first().attr('alt') || $field.find('.file-info strong').first().text() || '');
		} else if ($field.hasClass('acf-field-true-false')) {
			value = $field.find('input[type="checkbox"]').first().prop('checked') ? 'Включено' : 'Выключено';
			count = 1;
		} else if ($field.find('select').length) {
			value = $.trim($field.find('select option:selected').map(function () { return $(this).text(); }).get().join(', '));
		} else {
			value = inputValue($field);
			if (value && /<[^>]+>/.test(value)) {
				var temporary = document.createElement('div');
				temporary.innerHTML = value;
				value = temporary.textContent || '';
			}
		}

		value = $.trim(String(value || '').replace(/\s+/g, ' '));
		if (value.length > 86) {
			value = value.slice(0, 83) + '…';
		}
		var filled = count !== null ? count > 0 : value !== '';
		return {
			label: label,
			meta: filled ? value : 'Не заполнено',
			filled: filled
		};
	};

	PracticeEditor.prototype.renderSectionFields = function ($map, $layout) {
		var self = this;
		var layoutType = String($layout.attr('data-layout') || '');
		var anchor07Source = layoutType === 'anchor_07'
			? (inputValue(directField($layout, 'video_source')) || 'file')
			: '';
		var anchor07SourceField = {
			file: 'media_file',
			kinescope: 'kinescope_url',
			youtube: 'youtube_url'
		}[anchor07Source] || 'media_file';
		var $fields = $layout.children('.acf-fields').children('.acf-field').filter(function () {
			var $field = $(this);
			if ($field.hasClass('acf-field-tab') || $field.hasClass('acf-field-message') || $field.hasClass('yoga-synced-menu-title-field') || $field.hasClass('yoga-service-field')) {
				return false;
			}

			if (layoutType !== 'anchor_07') {
				return true;
			}

			var name = String($field.attr('data-name') || '');
			return !['media_file', 'kinescope_url', 'youtube_url'].includes(name) || name === anchor07SourceField;
		});
		var $structure = $('<div class="yoga-practice-visual-map__section-fields"></div>');
		$fields.each(function (index) {
			var $field = $(this);
			var field = self.fieldVisualSummary($field);
			$structure.append(self.visualNode(field.label, field.meta, {
				number: String(index + 1),
				fieldId: self.fieldVisualId($field),
				attention: !field.filled
			}).addClass('is-field').toggleClass('is-empty', !field.filled));
		});
		if (!$fields.length) {
			$structure.append('<p class="yoga-practice-visual-map__empty">В этом разделе пока нет доступных полей.</p>');
		}
		$map.append($structure);
	};

	PracticeEditor.prototype.fieldVisualId = function ($field) {
		var id = $field.attr('data-yoga-field-id');
		if (!id) {
			id = 'yoga-field-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
			$field.attr('data-yoga-field-id', id);
		}
		return id;
	};

	PracticeEditor.prototype.openVisualMapField = function (fieldId) {
		var $field = this.$workspace.find('.acf-field[data-yoga-field-id="' + fieldId + '"]').first();
		var $layout = $field.closest('.layout');
		if (!$field.length || !$layout.length) {
			return;
		}
		var field = this.fieldVisualSummary($field);
		var section = this.layoutSummary($layout);
		$layout.addClass('yoga-field-modal-parent');
		$field.addClass('yoga-field-modal-surface');
		this.openModal($field, field.label, section.title, $layout);
	};

	PracticeEditor.prototype.rowVisualId = function ($row) {
		var id = $row.attr('data-yoga-row-id');
		if (!id) {
			id = 'yoga-row-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
			$row.attr('data-yoga-row-id', id);
		}
		return id;
	};

	PracticeEditor.prototype.syncLayoutTitles = function ($layout) {
		var $menuTitle = directField($layout, 'section_title');
		var $sourceTitle = directField($layout, 'main_title');
		if (!$sourceTitle.length) {
			$sourceTitle = directField($layout, 'title');
		}
		if (!$menuTitle.length || !$sourceTitle.length) {
			return;
		}
		$menuTitle.addClass('yoga-synced-menu-title-field');
		var sourceValue = inputValue($sourceTitle);
		var $menuInput = $menuTitle.find('input:not([type="hidden"]), textarea, select').first();
		if ($menuInput.length && String($menuInput.val() || '') !== sourceValue) {
			$menuInput.val(sourceValue);
		}
	};

	PracticeEditor.prototype.markServiceFields = function ($layout) {
		var layoutType = String($layout.attr('data-layout') || '');
		var serviceNames = [
			'anchor_id',
			'title_class',
			'has_modifications',
			'execution_name',
			'main_modification_name',
			'matter_mod',
			'details_mod',
			'timing_mod',
			'media_type_mod',
			'media_file_mod',
			'duration_mod',
			'gallery_mod',
			'content_mod',
			'additional_modifications',
			'allow_fullscreen',
			'restrict_scrub',
			'auto_play'
		];
		var serviceKeys = [
			'field_ex_execution_name',
			'field_ex_modification_name'
		];
		$layout.find('.acf-field').each(function () {
			var $field = $(this);
			var fieldName = String($field.attr('data-name') || '');
			var isUserCommentsField = layoutType === 'anchor_06' && fieldName === 'comments';
			var isServiceField = serviceNames.indexOf(fieldName) !== -1
				|| serviceKeys.indexOf(String($field.attr('data-key') || '')) !== -1;
			$field.toggleClass('yoga-service-field', isServiceField || isUserCommentsField);
		});
	};

	PracticeEditor.prototype.openVisualMapRow = function (rowId) {
		var $row = this.$workspace.find('tr.acf-row[data-yoga-row-id="' + rowId + '"]').first();
		if (!$row.length) {
			return;
		}
		var $layout = $row.closest('.layout');
		$layout.addClass('yoga-direct-row-modal-parent');
		if ($row.hasClass('yoga-row-card--step')) {
			this.openRowModal($row, true);
			return;
		}
		var $step = $row.parents('tr.yoga-row-card--step').first();
		if ($step.length) {
			var stepRepeater = acf.getField($step.closest('.acf-field-repeater'));
			if (stepRepeater && $step.hasClass('-collapsed') && typeof stepRepeater.expand === 'function') {
				stepRepeater.expand($step);
			}
			$step.addClass('yoga-direct-row-modal-path');
		}
		this.openRowModal($row, true);
	};

	PracticeEditor.prototype.addExercise = function (stepId) {
		var $layout = this.layoutById(this.activeId);
		if (!$layout.length || String($layout.attr('data-layout') || '') !== 'anchor_05') {
			return;
		}

		var $stepsField = directField($layout, 'steps');
		var $step = stepId
			? directRows($stepsField).filter('[data-yoga-row-id="' + stepId + '"]').first()
			: $();

		if (!$step.length) {
			var stepsRepeater = acf.getField($stepsField);
			if (!stepsRepeater || typeof stepsRepeater.add !== 'function') {
				return;
			}
			$step = stepsRepeater.add();
		}

		var $exerciseField = $step.children('td.acf-fields').children('.acf-field[data-name="exercise_items"]').first();
		var exercisesRepeater = acf.getField($exerciseField);
		if (!exercisesRepeater || typeof exercisesRepeater.add !== 'function') {
			return;
		}

		var $exercise = exercisesRepeater.add();
		if (!$exercise || !$exercise.length) {
			return;
		}

		this.decorate();
		this.renderVisualMap();
		this.openVisualMapRow(this.rowVisualId($exercise));
	};

	PracticeEditor.prototype.openSelectedPanel = function () {
		if (this.activeId === 'general') {
			this.openModal(this.$generalPanel, labels.general, 'Настройки', null);
			return;
		}

		var $layout = this.layoutById(this.activeId);
		if ($layout.length) {
			this.openModal($layout, this.layoutSummary($layout).title, layoutLabels[String($layout.attr('data-layout') || '')] || labels.section, null);
		}
	};

	PracticeEditor.prototype.modalTitleForRow = function ($row) {
		var $header = $row.children('td.acf-fields').children('.yoga-row-card__header');
		return {
			title: $header.find('.yoga-row-card__title').first().text(),
			kind: $header.find('.yoga-row-card__eyebrow').first().text()
		};
	};

	PracticeEditor.prototype.openRowModal = function ($row, ownsDirectPath) {
		var $repeaterField = $row.closest('.acf-field-repeater');
		var repeater = acf.getField($repeaterField);
		if (repeater && $row.hasClass('-collapsed') && typeof repeater.expand === 'function') {
			repeater.expand($row);
		}
		var heading = this.modalTitleForRow($row);
		this.openModal($row.children('td.acf-fields'), heading.title, heading.kind, $row);
		if (ownsDirectPath && this.modalStack.length) {
			this.modalStack[this.modalStack.length - 1].ownsDirectPath = true;
		}
	};

	PracticeEditor.prototype.openAccordionModal = function ($accordion) {
		var title = $.trim($accordion.children('.acf-accordion-title').find('label').first().text()) || labels.section;
		$accordion.addClass('-open');
		$accordion.children('.acf-accordion-content').show();
		this.openModal($accordion, title, 'Упражнение', $accordion);
	};

	PracticeEditor.prototype.openModal = function ($surface, title, kind, $owner) {
		if (!$surface || !$surface.length || $surface.hasClass('yoga-modal-surface')) {
			return;
		}
		if (!this.modalStack.length) {
			this.enterPortal();
		}

		var depth = this.modalStack.length;
		var $toolbar = $(
			'<div class="yoga-practice-modal__toolbar">' +
				'<button type="button" class="yoga-practice-modal__back"><span aria-hidden="true">' + spriteIcon('site-arrow', 'yoga-practice-editor__inline-arrow yoga-practice-editor__inline-arrow--back') + '</span> Назад</button>' +
				'<div class="yoga-practice-modal__heading"><span class="yoga-practice-modal__kind"></span><span class="yoga-practice-modal__title"></span></div>' +
				'<button type="button" class="yoga-practice-modal__close" aria-label="Закрыть">' + spriteIcon('icon-close', 'yoga-practice-modal__close-icon') + '</button>' +
			'</div>'
		);
		$toolbar.find('.yoga-practice-modal__kind').text(kind || labels.section);
		$toolbar.find('.yoga-practice-modal__title').text(title || labels.section);
		var self = this;
		$toolbar.find('.yoga-practice-modal__back, .yoga-practice-modal__close').on('click.yogaModal', function (event) {
			event.preventDefault();
			event.stopImmediatePropagation();
			self.closeTopModal();
		});
		$surface.prepend($toolbar);
		$surface
			.addClass('yoga-modal-surface')
			.attr({ role: 'dialog', 'aria-modal': 'true', 'aria-label': title || labels.section })
			.css('z-index', 100102 + depth * 2);
		if ($owner && $owner.length) {
			$owner.addClass('yoga-modal-owner');
		}

		this.modalStack.push({
			$surface: $surface,
			$owner: $owner || $(),
			$toolbar: $toolbar,
			focus: document.activeElement
		});
		$surface.scrollTop(0);
		this.positionModalSurfaces();
		this.updateModalLayer();
		acf.doAction('show', $surface, 'yoga_practice_modal');
		window.setTimeout(function () {
			self.useVisualWysiwygEditors($surface);
			$surface.scrollTop(0);
			$toolbar.find('.yoga-practice-modal__back').trigger('focus');
		}, 0);
	};

	PracticeEditor.prototype.closeTopModal = function () {
		this.pruneModalStack();
		var item = this.modalStack.pop();
		var self = this;
		if (!item) {
			this.updateModalLayer();
			this.exitPortal();
			return;
		}

		try {
			item.$surface.removeClass('yoga-modal-surface yoga-field-modal-surface').removeAttr('role aria-modal aria-label').css('z-index', '');
			['--yoga-modal-top', '--yoga-modal-right', '--yoga-modal-bottom', '--yoga-modal-left'].forEach(function (property) {
				var surface = item.$surface.get(0);
				if (surface) {
					surface.style.removeProperty(property);
				}
			});
			item.$owner.removeClass('yoga-modal-owner yoga-field-modal-parent');
			item.$toolbar.remove();

			if (item.$owner.is('tr.acf-row')) {
				var repeater = acf.getField(item.$owner.closest('.acf-field-repeater'));
				if (repeater && typeof repeater.collapse === 'function') {
					repeater.collapse(item.$owner);
				}
				this.updateRowHeader(item.$owner);
				if (item.ownsDirectPath) {
					this.$workspace.find('tr.yoga-direct-row-modal-path').each(function () {
						var $pathRow = $(this);
						var pathRepeater = acf.getField($pathRow.closest('.acf-field-repeater'));
						if (pathRepeater && typeof pathRepeater.collapse === 'function') {
							pathRepeater.collapse($pathRow);
						}
						$pathRow.removeClass('yoga-direct-row-modal-path');
					});
					this.$workspace.find('.yoga-direct-row-modal-parent').removeClass('yoga-direct-row-modal-parent');
				}
			} else if (item.$owner.hasClass('acf-accordion')) {
				item.$owner.removeClass('-open');
				item.$owner.children('.acf-accordion-content').hide();
			}
		} catch (error) {
			window.console.error('Practice editor modal cleanup failed.', error);
		} finally {
			// Never leave the backdrop active after its surface has been removed.
			// Newly appended ACF rows may throw while their repeater is collapsing.
			this.updateModalLayer();
			this.positionModalSurfaces();
			var restorePageScroll = !this.modalStack.length;
			if (restorePageScroll) {
				this.exitPortal();
			}
			if (item.focus && document.contains(item.focus)) {
				try {
					item.focus.focus({ preventScroll: true });
				} catch (focusError) {
					item.focus.focus();
				}
			}
			if (restorePageScroll) {
				this.restorePortalScroll();
				scheduleRefresh(this.activeId);
				window.requestAnimationFrame(function () {
					self.restorePortalScroll();
				});
			}
		}
	};

	PracticeEditor.prototype.pruneModalStack = function () {
		this.modalStack = this.modalStack.filter(function (item) {
			var surface = item.$surface && item.$surface.get(0);
			var isLive = surface && document.contains(surface) && item.$surface.hasClass('yoga-modal-surface');

			if (!isLive) {
				if (item.$toolbar) {
					item.$toolbar.remove();
				}
				if (item.$owner) {
					item.$owner.removeClass('yoga-modal-owner');
				}
			}

			return isLive;
		});
	};

	PracticeEditor.prototype.restorePortalScroll = function () {
		window.scrollTo(this.portalScrollLeft, this.portalScrollTop);
		this.portalScrollPositions.forEach(function (position) {
			if (position.element && document.contains(position.element)) {
				position.element.scrollLeft = position.left;
				position.element.scrollTop = position.top;
			}
		});
	};

	PracticeEditor.prototype.toggleWysiwygEditors = function (enable) {
		this.$workspace.find('.acf-field-wysiwyg').each(function () {
			var field = acf.getField($(this));
			if (!field) {
				return;
			}
			if (enable && typeof field.enableEditor === 'function') {
				field.enableEditor();
			} else if (!enable && typeof field.disableEditor === 'function') {
				field.disableEditor();
			}
		});
	};

	PracticeEditor.prototype.useVisualWysiwygEditors = function ($surface) {
		$surface.find('.acf-field-wysiwyg').addBack('.acf-field-wysiwyg').each(function () {
			var $wrap = $(this).find('.wp-editor-wrap').first();
			var $visualTab = $wrap.find('.wp-switch-editor.switch-tmce').first();
			if ($wrap.length && $visualTab.length && !$wrap.hasClass('tmce-active')) {
				$visualTab.trigger('click');
			}
		});
	};

	PracticeEditor.prototype.enterPortal = function () {
		if (this.$workspace.hasClass('yoga-practice-editor--portal')) {
			return;
		}
		this.portalScrollLeft = window.pageXOffset || document.documentElement.scrollLeft || 0;
		this.portalScrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
		this.portalScrollPositions = [];
		var self = this;
		this.$workspace.parents().each(function () {
			var style = window.getComputedStyle(this);
			var scrollableX = (style.overflowX === 'auto' || style.overflowX === 'scroll') && this.scrollWidth > this.clientWidth;
			var scrollableY = (style.overflowY === 'auto' || style.overflowY === 'scroll') && this.scrollHeight > this.clientHeight;
			if (scrollableX || scrollableY) {
				self.portalScrollPositions.push({
					element: this,
					left: this.scrollLeft,
					top: this.scrollTop
				});
			}
		});
		this.toggleWysiwygEditors(false);
		this.$portalParent = this.$workspace.parent();
		this.portalNextSibling = this.$workspace.next().get(0) || null;
		this.$portalPlaceholder = $('<div class="yoga-practice-editor__portal-placeholder" aria-hidden="true"></div>').height(this.$workspace.outerHeight());
		this.$workspace.before(this.$portalPlaceholder);
		this.$workspace.find('input, select, textarea').filter(function () {
			return !this.hasAttribute('form');
		}).attr('data-yoga-form-added', '1').attr('form', this.postFormId);
		this.$workspace.addClass('yoga-practice-editor--portal').appendTo('body');
		this.toggleWysiwygEditors(true);
	};

	PracticeEditor.prototype.exitPortal = function () {
		if (!this.$workspace.hasClass('yoga-practice-editor--portal') && !this.$portalPlaceholder.length) {
			return;
		}
		this.toggleWysiwygEditors(false);
		this.$workspace.find('[data-yoga-form-added="1"]').removeAttr('form data-yoga-form-added');
		this.$workspace.removeClass('yoga-practice-editor--portal');

		var placeholder = this.$portalPlaceholder.get(0);
		var parent = this.$portalParent.get(0);
		if (placeholder && document.contains(placeholder)) {
			this.$portalPlaceholder.replaceWith(this.$workspace);
		} else if (parent && document.contains(parent)) {
			if (this.portalNextSibling && this.portalNextSibling.parentNode === parent) {
				parent.insertBefore(this.$workspace.get(0), this.portalNextSibling);
			} else {
				this.$workspace.appendTo(parent);
			}
		} else {
			this.$workspace.appendTo(this.$sourceFields);
		}

		this.$portalPlaceholder.remove();
		this.$portalPlaceholder = $();
		this.$portalParent = $();
		this.portalNextSibling = null;
		this.toggleWysiwygEditors(true);
	};

	PracticeEditor.prototype.updateModalLayer = function () {
		this.pruneModalStack();
		var hasModal = this.modalStack.length > 0;
		var workspaceNode = this.$workspace.get(0);
		this.$workspace.find('.yoga-modal-layer-path').removeClass('yoga-modal-layer-path');
		if (hasModal && workspaceNode) {
			// Keep the backdrop in the stable workspace root: outside ACF repeaters,
			// but in the same stacking context as every modal surface.
			this.$modalBackdrop.prependTo(this.$workspace);
			this.modalStack.forEach(function (item, index) {
				if (item.$surface && item.$surface.length && document.contains(item.$surface.get(0))) {
					item.$surface
						.addClass('yoga-modal-layer-path')
						.css('z-index', 100102 + index * 2)
						.parentsUntil(workspaceNode).addClass('yoga-modal-layer-path');
				}
			});
		} else {
			this.$modalBackdrop.appendTo(this.$workspace);
		}
		this.$modalContext = this.$fieldGroup.closest('.edit-post-meta-boxes-main');
		$('body').toggleClass('yoga-practice-modal-open', hasModal);
		this.$modalContext.toggleClass('yoga-practice-modal-context', hasModal);
		this.$modalBackdrop.toggleClass('is-visible', hasModal).attr('aria-hidden', hasModal ? 'false' : 'true');
		if (hasModal) {
			this.$modalBackdrop.css('z-index', 100101);
		} else {
			this.$modalBackdrop.removeAttr('style');
		}
	};

	PracticeEditor.prototype.positionModalSurfaces = function () {
		var $context = this.$modalBackdrop.parent();
		var contextNode = $context.get(0);
		var backdropBlock = null;
		var backdropNode = contextNode;
		while (backdropNode && backdropNode !== document.body) {
			var backdropStyle = window.getComputedStyle(backdropNode);
			if (backdropStyle.transform !== 'none' || backdropStyle.filter !== 'none' || backdropStyle.perspective !== 'none' || backdropStyle.backdropFilter !== 'none') {
				backdropBlock = backdropNode;
				break;
			}
			backdropNode = backdropNode.parentElement;
		}
		var backdropRect = backdropBlock ? backdropBlock.getBoundingClientRect() : { top: 0, left: 0, right: window.innerWidth, bottom: window.innerHeight };
		var backdrop = this.$modalBackdrop.get(0);
		if (backdrop) {
			backdrop.style.setProperty('--yoga-backdrop-top', (-backdropRect.top) + 'px');
			backdrop.style.setProperty('--yoga-backdrop-left', (-backdropRect.left) + 'px');
			backdrop.style.setProperty('--yoga-backdrop-right', (backdropRect.right - window.innerWidth) + 'px');
			backdrop.style.setProperty('--yoga-backdrop-bottom', (backdropRect.bottom - window.innerHeight) + 'px');
		}

		this.modalStack.forEach(function (item) {
			var surface = item.$surface.get(0);
			if (!surface) {
				return;
			}
			var containingBlock = null;
			var node = surface.parentElement;
			while (node && node !== document.body) {
				var style = window.getComputedStyle(node);
				if (style.transform !== 'none' || style.filter !== 'none' || style.perspective !== 'none' || style.backdropFilter !== 'none') {
					containingBlock = node;
					break;
				}
				node = node.parentElement;
			}

			var rect = containingBlock ? containingBlock.getBoundingClientRect() : { top: 0, left: 0, right: window.innerWidth, bottom: window.innerHeight };
			var gutter = window.innerWidth <= 600 ? 8 : 28;
			var horizontalMargin = Math.max(gutter, (window.innerWidth - 1320) / 2);
			surface.style.setProperty('--yoga-modal-top', (gutter - rect.top) + 'px');
			surface.style.setProperty('--yoga-modal-left', (horizontalMargin - rect.left) + 'px');
			surface.style.setProperty('--yoga-modal-right', (rect.right - (window.innerWidth - horizontalMargin)) + 'px');
			surface.style.setProperty('--yoga-modal-bottom', (rect.bottom - (window.innerHeight - gutter)) + 'px');
		});
	};

	PracticeEditor.prototype.trapModalFocus = function (event) {
		var item = this.modalStack[this.modalStack.length - 1];
		var $focusable = item.$surface.find('a:visible, button:visible, input:visible, select:visible, textarea:visible, [tabindex]:visible').filter('[tabindex!="-1"]');
		if (!$focusable.length) {
			return;
		}
		var first = $focusable.get(0);
		var last = $focusable.get($focusable.length - 1);
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	};

	PracticeEditor.prototype.ensureRowHeader = function ($row, type, index, title, meta, emptyTitle) {
		var self = this;
		var $cell = $row.children('td.acf-fields');
		var $header = $cell.children('.yoga-row-card__header');
		if (!$header.length) {
			$header = $(
				'<div class="yoga-row-card__header">' +
					'<button type="button" class="yoga-row-card__toggle">' +
						'<span class="yoga-row-card__eyebrow"></span>' +
						'<span class="yoga-row-card__title"></span>' +
						'<small></small>' +
						'<span class="yoga-row-card__edit" aria-hidden="true">' + spriteIcon('icon-edit', 'yoga-row-card__edit-icon') + '</span>' +
					'</button>' +
				'</div>'
			);
			$cell.prepend($header);
		}

		$row.addClass('yoga-row-card yoga-row-card--' + type).toggleClass('needs-attention', emptyTitle);
		$header.find('.yoga-row-card__eyebrow').text(type === 'step' ? labels.step + ' ' + index : (type === 'exercise' ? labels.exercise + ' ' + index : (labels.additionalModification || 'Дополнительная модификация') + ' ' + index));
		$header.find('.yoga-row-card__title').text(title);
		var missingTitleLabels = {
			step: 'Не заполнено: название шага',
			modification: 'Не заполнено: название модификации'
		};
		var missingTitle = emptyTitle ? (missingTitleLabels[type] || labels.needsAttention) : '';
		$header.find('small').text((meta ? meta + (missingTitle ? ' · ' : '') : '') + missingTitle);
		$header.find('.yoga-row-card__toggle')
			.attr({ 'aria-haspopup': 'dialog', 'aria-label': 'Редактировать: ' + title })
			.off('click.yogaModal')
			.on('click.yogaModal', function (event) {
				event.preventDefault();
				event.stopImmediatePropagation();
				self.openRowModal($row);
			});
		$header.find('.yoga-row-card__edit')
			.html(spriteIcon('icon-edit', 'yoga-row-card__edit-icon'));

		var $removeButton = $header.children('.yoga-row-card__remove');
		var isRemovable = type === 'step' || type === 'exercise' || type === 'modification';
		if (isRemovable) {
			if (!$removeButton.length) {
				$removeButton = $('<button type="button" class="yoga-row-card__remove">' + spriteIcon('checkout-trash', 'yoga-row-card__remove-icon') + '</button>').appendTo($header);
			}
			$removeButton
				.attr({
					'aria-label': labels.remove + ': ' + title,
					title: labels.remove
				})
				.off('click.yogaRemoveRow')
				.on('click.yogaRemoveRow', function (event) {
					event.preventDefault();
					event.stopImmediatePropagation();
					var confirmationMessage = type === 'step'
						? labels.confirmRemoveStep
						: (type === 'exercise' ? labels.confirmRemoveExercise : labels.confirmRemoveModification);
					self.removeRepeaterRow(
						$row,
						$removeButton,
						confirmationMessage
					);
				});
		} else {
			$removeButton.remove();
		}
		this.updateRowHeader($row);
	};

	PracticeEditor.prototype.updateRowHeader = function ($row) {
		if ($row.hasClass('yoga-row-card--modification')) {
			$row.children('td.acf-fields').children('.acf-field.-collapsed-target').removeClass('-collapsed-target');
		}
		$row.children('td.acf-fields').children('.yoga-row-card__header').find('.yoga-row-card__toggle')
			.attr('aria-expanded', 'false')
			.attr('title', 'Открыть в отдельном окне');
	};

	PracticeEditor.prototype.ensureAccordionCard = function ($accordion) {
		var self = this;
		if ($accordion.hasClass('yoga-flat-modifications-accordion')) {
			return;
		}
		var $title = $accordion.children('.acf-accordion-title');
		if (!$title.length) {
			return;
		}
		var isMainModification = $accordion.hasClass('acf-field-ex-admin-main-modification');
		$accordion.addClass('yoga-accordion-card');
		if (!$accordion.hasClass('yoga-modal-surface')) {
			$accordion.removeClass('-open');
			$accordion.children('.acf-accordion-content').hide();
		}
		if (!$title.children('.yoga-accordion-card__edit').length) {
			$title.append('<button type="button" class="yoga-accordion-card__edit" aria-haspopup="dialog" aria-label="Редактировать"><span aria-hidden="true">' + spriteIcon('icon-edit', 'yoga-accordion-card__edit-icon') + '</span></button>');
		}
		if (isMainModification) {
			var mainLabel = labels.mainModification || 'Выполнение';
			var mainTitle = inputValue($accordion.find('.acf-field[data-name="main_modification_name"]').first()) || mainLabel;
			if (mainTitle === 'Основная модификация') {
				mainTitle = mainLabel;
			}
			var $summary = $title.children('.yoga-modification-card__summary');
			if (!$summary.length) {
				$summary = $('<span class="yoga-modification-card__summary"><small></small><span class="yoga-modification-card__title"></span></span>').prependTo($title);
			}
			$summary.children('small').text(mainLabel);
			$summary.children('.yoga-modification-card__title').text(mainTitle);
			$title.children('label').attr('aria-hidden', 'true');
		}
		$title.off('click.yogaModal').on('click.yogaModal', function (event) {
			event.preventDefault();
			event.stopImmediatePropagation();
			self.openAccordionModal($accordion);
		});
	};

	PracticeEditor.prototype.decorate = function () {
		var self = this;
		this.layouts().each(function () {
			var $layout = $(this);
			$layout.addClass('yoga-practice-editor__layout-card');
			self.markServiceFields($layout);
			self.syncLayoutTitles($layout);
			if (String($layout.attr('data-layout')) !== 'anchor_05') {
				return;
			}

			var $stepsField = directField($layout, 'steps');
			directRows($stepsField).each(function (stepIndex) {
				var $step = $(this);
				var stepTitle = rowFieldValue($step, 'section_title');
				var $exerciseField = $step.children('td.acf-fields').children('.acf-field[data-name="exercise_items"]').first();
				var $exercises = directRows($exerciseField);
				self.ensureRowHeader(
					$step,
					'step',
					stepIndex + 1,
					stepTitle || labels.step + ' ' + (stepIndex + 1),
					$exercises.length + ' упражнений',
					stepTitle === ''
				);
				var stepHasActiveModal = $step.hasClass('yoga-direct-row-modal-path') || $step.find('.yoga-modal-surface').length > 0;
				if (stepHasActiveModal) {
					var stepsRepeater = acf.getField($stepsField);
					if (stepsRepeater && $step.hasClass('-collapsed') && typeof stepsRepeater.expand === 'function') {
						stepsRepeater.expand($step);
					}
				} else {
					$step.addClass('-collapsed');
					self.updateRowHeader($step);
				}

				$exercises.each(function (exerciseIndex) {
					var $exercise = $(this);
					var exerciseTitle = rowFieldValue($exercise, 'title');
					var $modificationsField = nestedFieldInRow($exercise, 'modifications');
					var $modifications = directRows($modificationsField);
				self.ensureRowHeader(
						$exercise,
						'exercise',
						exerciseIndex + 1,
						exerciseTitle || labels.exercise + ' ' + (exerciseIndex + 1),
						'Основная + ' + $modifications.length + ' дополнительных',
					false
				);
					var exerciseHasActiveModal = $exercise.find('.yoga-modal-surface').length > 0;
					if (exerciseHasActiveModal) {
						var exercisesRepeater = acf.getField($exerciseField);
						if (exercisesRepeater && $exercise.hasClass('-collapsed') && typeof exercisesRepeater.expand === 'function') {
							exercisesRepeater.expand($exercise);
						}
					} else {
						$exercise.addClass('-collapsed');
						self.updateRowHeader($exercise);
					}

					$exercise.children('td.acf-fields').children('.acf-field-accordion').each(function () {
						self.ensureAccordionCard($(this));
					});

					$modifications.each(function (modificationIndex) {
						var $modification = $(this);
						var modificationTitle = rowFieldValue($modification, 'modification_name');
						self.ensureRowHeader(
							$modification,
							'modification',
							modificationIndex + 1,
							modificationTitle || (labels.additionalModification || 'Дополнительная модификация') + ' ' + (modificationIndex + 1),
							'',
							modificationTitle === ''
						);
						if (!$modification.children('td.acf-fields').hasClass('yoga-modal-surface')) {
							$modification.addClass('-collapsed');
							self.updateRowHeader($modification);
						}
					});
				});
			});
		});
	};

	function initialize() {
		if (editor || $('.yoga-practice-editor').length) {
			return;
		}

		var $sectionsField = $('.acf-field[data-key="field_practice_sections"]').first();
		if (!$sectionsField.length) {
			return;
		}

		editor = new PracticeEditor($sectionsField);
		editor.build();

		$(document).on('input change', '.yoga-practice-editor input, .yoga-practice-editor textarea, .yoga-practice-editor select', function () {
			scheduleRefresh(editor.activeId);
		});
	}

	acf.addAction('ready', initialize);
	acf.addAction('append', function ($element) {
		if (!editor) {
			initialize();
		}
		if (editor && $element.closest('.yoga-practice-editor').length) {
			scheduleRefresh(editor.activeId);
		}
	});
	acf.addAction('remove', function () {
		if (editor) {
			scheduleRefresh(editor.activeId);
		}
	});
	acf.addAction('sortstop', function () {
		if (editor) {
			scheduleRefresh(editor.activeId);
		}
	});
})(jQuery);
