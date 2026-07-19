/**
 * Ширины viewport — те же числа, что в assets/css/breakpoints.css (Tailwind sm/md/lg/xl/2xl + tight-desktop).
 * @type {{sm:number,md:number,lg:number,xl:number,xxl:number,tightDesktop:number}}
 */
var yogaViewportBp = { sm: 640, md: 768, lg: 1025, xl: 1280, xxl: 1536, tightDesktop: 1320 };

jQuery(document).ready(function($) {

	window.yogaResetSmartCaptchaMount = function(mountEl) {
		if (!mountEl || typeof window.smartCaptcha === 'undefined' || typeof window.smartCaptcha.reset !== 'function') {
			return;
		}
		try {
			var wid = mountEl.dataset.yogaWidgetId;
			if (wid !== undefined && wid !== '') {
				window.smartCaptcha.reset(wid);
			} else {
				window.smartCaptcha.reset();
			}
		} catch (ignore) {}
	};
	
	new WOW().init();
    
	$('.no_link').on('click', function (e) { e.preventDefault() }); /* Отключаем ссылку */
    
	$('.ws_box').matchHeight(); /* Блоки одинаковой высоты */
	
	
	$("#header").removeClass("header_fixed");
	$(window).scroll(function(){
        if ($(window).scrollTop() > 1) {
			$("#header").addClass("header_fixed");
			} else {
			$("#header").removeClass("header_fixed");
		};
	});   
	
	$(".body_main .logo-header").click(function () {
		var elementClick = $(this).attr("href");
		var destination = $(elementClick).offset().top;
		$('html, body').animate({ scrollTop: destination }, 1200);
		return false;
	});
	
	/* Библиотека практик: пункт и .modal-menu не соседи (зазор до fixed-панели).
	 * Один обработчик .hover() вызывается и на enter, и на leave → toggle гасит меню при уходе с пункта.
	 * Закрытие с задержкой + отмена при наведении на панель. */
	var yogaLibraryMenuCloseTimer = null;
	function yogaOpenLibraryMenu() {
		if (yogaLibraryMenuCloseTimer) {
			clearTimeout(yogaLibraryMenuCloseTimer);
			yogaLibraryMenuCloseTimer = null;
		}
		$('.main-menu-active-item').addClass('active');
		$('.modal-menu').addClass('active');
	}
	function yogaScheduleCloseLibraryMenu() {
		if (yogaLibraryMenuCloseTimer) {
			clearTimeout(yogaLibraryMenuCloseTimer);
		}
		yogaLibraryMenuCloseTimer = setTimeout(function () {
			$('.main-menu-active-item').removeClass('active');
			$('.modal-menu').removeClass('active');
			yogaLibraryMenuCloseTimer = null;
		}, 320);
	}
	$('.main-menu-active-item').on('mouseenter', yogaOpenLibraryMenu);
	$('.main-menu-active-item').on('mouseleave', yogaScheduleCloseLibraryMenu);
	$('.modal-menu').on('mouseenter', yogaOpenLibraryMenu);
	$('.modal-menu').on('mouseleave', yogaScheduleCloseLibraryMenu);
	
	// Кастомные «чекбоксы» (не мобильный оверлей библиотеки — там свой перехват по строке)
	$(document).on('click', '.checkbox:not(.library-filters-screen__box):not(.library-filter-faux-checkbox)', function () {
		$(this).toggleClass("active");
	});
	
	/* Бургер главного сайта: без класса modal-call (иначе конфликт с общим обработчиком .modal-call). */
	$(document).on('click', '.body_main .burger', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $panel = $('.modal-mobile-menu');
		if (!$panel.length) {
			return;
		}
		$panel.addClass('active');
		$('.overlay').addClass('active');
		$('.body').addClass('body-fixed');
		$('.modal').removeClass('active');
		$('.modal-login').removeClass('active');
	});
	
	
	/*
		$('.login-icon').click(function () {
		$('.overlay').addClass("active");
		$('.modal-login').addClass("active");
		});    
	*/
	
	/* Section-tariffs */
	
	$('.switches-item').click(function () {
		$(this).closest('.switches').find('.switches-item').removeClass("active");
		$(this).addClass("active");
		
		var switchcurrent = $(this).attr('data-target');
		$('.tariffs-items__slide').removeClass("active");
		$(this).closest('.tariffs').find('.tariffs-items__slide[data-target=' + switchcurrent + ']').addClass("active");
	});      
	
	
	$('.modal-call_login').click(function () {
		$('.overlay').addClass("active");
		$('.modal-login').addClass("active");
		$('.modal-mobile-menu').removeClass("active");
	});
	
	/* Section-reviews */
	
	$('.reviews-slider').slick({
		infinite: true,
		dots: false,
		arrows: true,
		slidesToShow: 1,
		slidesToScroll: 1,
		
		prevArrow: ".section-reviews .slick-prev",
		nextArrow: ".section-reviews .slick-next",
	});
    
	
	jQuery(function($){
		let menuItems = document.querySelectorAll(".review-people__item");
		
		for (let i = 0; i < menuItems.length; i++) {
			menuItems[i].style.zIndex = i*-1;
		}
	});
	
	
	/* Section-videos */
	
	$('.videos-slider').slick({
		infinite: true,
		dots: true,
		arrows: true,
		slidesToShow: 5,
		slidesToScroll: 5,
		
		prevArrow: ".section-videos .slick-prev",
		nextArrow: ".section-videos .slick-next",
		responsive: [
			{
				breakpoint: yogaViewportBp.xl,
				settings: {   
					slidesToShow: 4,
					slidesToScroll: 4,   
				}
			},
			{
				breakpoint: yogaViewportBp.lg,
				settings: {
					slidesToShow: 3,
					slidesToScroll: 3,
				}
			},
			{
				breakpoint: yogaViewportBp.md,
				settings: {
					slidesToShow: 1,
					slidesToScroll: 1,  
					variableWidth: true,
				}
			}
		]
	});
	
	
	/* Section-popular-practicess */
	
	$('.popular-practices-slider').slick({
		infinite: true,
		dots: true,
		arrows: true,
		slidesToShow: 4,
		slidesToScroll: 4,
		
		prevArrow: ".section-popular-practices .slick-prev",
		nextArrow: ".section-popular-practices .slick-next",
		responsive: [
			{
				breakpoint: yogaViewportBp.xl,
				settings: {   
					slidesToShow: 3,
					slidesToScroll: 3,   
				}
			},
			{
				breakpoint: yogaViewportBp.lg,
				settings: {
					slidesToShow: 2,
					slidesToScroll: 2,
				}
			},
			{
				breakpoint: yogaViewportBp.sm,
				settings: {
					slidesToShow: 1,
					slidesToScroll: 1,
				}
			}
		]
	});
	
	/* Section-questions */
	
	$('.question__main').click(function () {
		$(this).closest('.question').toggleClass("active");
	});   
	
	
	
	
	/* Section-subscription */
	
	
	$('.section-subscription .input').keyup(function(){
		var $this = $(this),
		vall = $this.val();
		
		if(vall.length >= 1){
			$('.input:valid').closest('.form').addClass("active");
			$('.input:invalid').closest('.form').removeClass("active");
			
			$('.input:valid').closest('.form').removeClass("disabled");
			$('.input:invalid').closest('.form').removeClass("disabled");
			}else {
			
		}
	});
	
	
	$('.section-subscription .form-btn').click(function () {
		$(this).closest('.form').find('.input:invalid').closest('.form').addClass("disabled");
	});   
	
	$(".section-subscription .form:not(.subscription-form)").submit(function(e) {
		e.preventDefault();
		$(this).closest('.subscription').addClass("succes");
	});
	
	
	
	/* Section-library */
	
	/* Делегирование: надёжнее на мобилке; z-index у .filter-btn выше .form-search (20), иначе тапы уходят в строку поиска */
	$(document).on('click', '.library-form-main .filter-btn, .kriyi-form-main .filter-btn', function () {
		var $btn = $(this);
		var $lib = $btn.closest('.section-library, .section-kriyi');
		var $screen = $('#library-filters-screen');
		if ($lib.length && $(window).width() < yogaViewportBp.lg && $screen.length) {
			$btn.toggleClass('active');
			if ($btn.hasClass('active')) {
				openLibraryFiltersScreen();
			} else {
				closeLibraryFiltersScreen();
			}
			return;
		}
		$btn.toggleClass('active');
		var $contextForm = $btn.closest('form');
		if ($contextForm.length) {
			$contextForm.find('.filter').first().toggleClass('active');
		} else {
			$('.filter').toggleClass('active');
		}
	});
	
	
	if ($(window).width() >= yogaViewportBp.lg ) {
        jQuery(function($){
			$(document).mouseup(function (e){ // событие клика по веб-документу
				$('.filter-item__list .checkbox-item').not('.active').closest('.library-form').find('.filter-btn span').removeClass("active");
				$('.filter-item__list .checkbox-item.active').closest('.library-form').find('.filter-btn span').addClass("active");
			});
		});
	};
	
	jQuery(function($){
		$(document).on('mouseup', function (e) {
			/* Закрываем все выпадающие части виджета поиска только при клике ВНЕ .form-search
			 * (внутрь входят инпут, подсказки и список категорий .form-cat-list).
			 * Раньше проверялись только инпут и .form-search-list__item — при открытом списке категорий
			 * снимался .active только с .form-search, а .form-cat-list оставался active → «ломалась» строка поиска
			 * после клика по практике или другому месту страницы. */
			if ($(e.target).closest('.form-search').length) {
				return;
			}
			$('.form-search-list').removeClass('active');
			$('.form-search').removeClass('active');
			$('.form-cat-list').removeClass('active');
			$('.form-categories').removeClass('active');
		});
	});
	
	
	$('.form-search .input').keyup(function(){
		var $this = $(this),
		vall = $this.val();
		var $search = $(this).closest('.form-search');
		var $form = $(this).closest('form');
		
		if(vall.length >= 1){
			$search.find('.form-search-list').addClass("active");
			$search.addClass("active");
			$search.find('.form-cat-list').removeClass("active");
			$search.find('.form-categories').removeClass("active");
			if ($form.length) {
				$form.find('.filter-item').removeClass("active");
				$form.find('.filter-item__list').removeClass("active");
			}
			}else {
			$search.find('.form-search-list').removeClass("active");
			$search.removeClass("active");
		}
	});
	
	
	$('.form-search-list__item').click(function () {
		var $search = $(this).closest('.form-search');
		$search.removeClass("active");
		$(this).closest('.form-search-list').removeClass("active");
		var libsearchtext = $(this).find('span').text();
		$search.find('.input').val(libsearchtext);
	}); 
	
	
	$('.form-categories').click(function () {
		var $search = $(this).closest('.form-search');
		var $form = $(this).closest('form');
		$(this).toggleClass("active");
		$search.toggleClass("active");
		$search.find('.form-cat-list').toggleClass("active");
		$search.find('.form-search-list').removeClass("active");
		if ($form.length) {
			$form.find('.filter-item').removeClass("active");
			$form.find('.filter-item__list').removeClass("active");
		}
	}); 
	
	
	
	$('.form-cat-list__item').click(function () {
		var $search = $(this).closest('.form-search');
		$search.removeClass("active");
		$search.find('.form-categories').removeClass("active");
		$(this).closest('.form-cat-list').removeClass("active");
		$(this).closest('.form-cat-list').find('.form-cat-list__item').removeClass("active");
		$(this).addClass("active");
		
		var libcat = $(this).attr('data-target');
		$search.find('.form-categories__value span').removeClass("active");
		$search.find('.form-categories__value span[data-target=' + libcat + ']').addClass("active");
	}); 
	
	
	
	jQuery(function($){
		
		$(document).mouseup(function (e){ // событие клика по веб-документу
			var div = $(".filter-item"); // тут указываем ID элемента
			var val = div.val();
			if (!div.is(e.target) // если клик был не по нашему блоку
				&& div.has(e.target).length === 0 ) { // и не по его дочерним элементам
				$('.filter-item').removeClass("active");
				$('.filter-item__list').removeClass("active");
				
				$('.filter-item__list .checkbox-item').not('.active').closest('.filter-item').removeClass("focused");
				$('.filter-item__list .checkbox-item.active').closest('.filter-item').addClass("focused");
			}
		});
	});
	
	
	$(document).on('click', '.filter-item__main', function (e) {
		e.preventDefault();
		e.stopPropagation();

		const $currentMain = $(this);
		const $currentFilter = $currentMain.closest('.filter');
		const $currentSection = $currentMain.closest('.section-library, .section-kriyi');

		if ($currentSection.length) {
			$currentSection.find('.form-search').removeClass("active");
			$currentSection.find('.form-categories').removeClass("active");
			$currentSection.find('.form-cat-list').removeClass("active");
			$currentSection.find('.form-search-list').removeClass("active");
		} else {
			$('.form-search').removeClass("active");
			$('.form-categories').removeClass("active");
			$('.form-cat-list').removeClass("active");
			$('.form-search-list').removeClass("active");
		}

		$currentFilter.find('.filter-item').not($currentMain.closest('.filter-item')).removeClass("active");
		$currentFilter.find('.filter-item__list').not($currentMain.closest('.filter-item').find('.filter-item__list')).removeClass("active");

		$currentMain.closest('.filter-item').toggleClass("active");
		$currentMain.closest('.filter-item').find('.filter-item__list').toggleClass("active");

		$currentFilter.find('.filter-item__list .checkbox-item').not('.active').closest('.filter-item').removeClass("focused");
		$currentFilter.find('.filter-item__list .checkbox-item.active').closest('.filter-item').addClass("focused");
	});
	
	
	
	$(document).on('click', '.filter-item__list .checkbox-item', function (e) {
		
		$('.form-categories').removeClass("active");
		$('.form-search').removeClass("active");
		$('.form-cat-list').removeClass("active");
		$('.form-search-list').removeClass("active");
		if ($(this).closest('#practice-filter-form').length) {
			var inputId = $(this).attr('data-filter-input');
			var input = inputId ? document.getElementById(inputId) : null;
			if (input && $(input).hasClass('library-filter-input')) {
				e.preventDefault();
				YogaLibraryFiltersCore.toggle(input);
				$(input).trigger('change');
			}
			return;
		}
		$(this).toggleClass("active");
		$(this).find('.checkbox').toggleClass("active");
		$('.filter-item__list .checkbox-item').not('.active').closest('.library-form').find('.form-reset').removeClass("active");
		$('.filter-item__list .checkbox-item.active').closest('.library-form').find('.form-reset').addClass("active");
		/*
			var filttext = $(this).find('span').text();
			$(this).closest('.filter-item').find('.filter-item__main span').html(filttext);
		*/
	});

	$('.form-reset').click(function (e) {
		e.preventDefault();
		$(this).removeClass("active");
		$('.filter-item').removeClass("focused");
		$('.filter-item__list .checkbox-item').removeClass("active");
		$('.filter-item__list .checkbox-item .checkbox').removeClass("active");
		YogaLibraryFiltersCore.clear();
		syncLibraryFilterCheckboxLabels();
		if ($(this).closest('.section-kriyi').length) {
			loadPractices();
		} else if ($(this).closest('.section-library').length) {
			loadLibraryPractices();
		}
	});
	
	
	/* $('.kriya-fav').click(function () {
		$(this).toggleClass("active");
	}); */
	
	$('.kriyi > .btn').click(function () {
		$(this).find('span').toggleClass("active");
		$('.kriyi-item_last').toggleClass("hidden");
		
	});
	
	
	
	
	
	/* Section-praktika */
	
	
	
	/* $('.praktika-fav').click(function () {
		$(this).find('.praktika-fav__icon img').toggleClass("active");
	}); */
	
	function initPraktikaMenuSync() {
		var $section = $('.section-praktika');
		var $menu = $section.find('.praktika-menu').first();
		var $menuLinks = $menu.find('nav ul li a.ref[href^="#"]');
		var $content = $section.find('.praktika-info').first();

		if (!$section.length || !$menu.length || !$menuLinks.length) {
			return;
		}

		var sections = [];
		var sectionOffsets = [];
		var currentActive = -1;
		var rafScheduled = false;
		var suppressSpyUntil = 0;
		var recalcRafScheduled = false;
		var contentResizeObserver = null;

		function getScrollOffset() {
			var $header = $('.header').first();
			var headerHeight = $header.length ? ($header.outerHeight() || 0) : 0;
			var minOffset = $(window).width() < 1025 ? 70 : 95;
			return Math.max(minOffset, headerHeight + 10);
		}

		function rebuildSections() {
			sections = [];
			$menuLinks.each(function () {
				var $link = $(this);
				var sectionKey = String($link.attr('data-section-key') || '').trim();
				var href = $link.attr('href');
				var $target = $();

				if (sectionKey !== '') {
					if (sectionKey === 'section-form-questions') {
						$target = $('#section-form-questions').first();
					} else {
						$target = $section.find('.js-praktika-section-marker[data-section-key="' + sectionKey + '"]').first();
					}
				}

				if ((!$target || !$target.length) && href && href !== '#') {
					$target = $(href);
				}

				if ($target && $target.length) {
					sections.push({
						$link: $link,
						$target: $target
					});
				}
			});

			rebuildSectionOffsets();
		}

		function rebuildSectionOffsets() {
			sectionOffsets = [];
			for (var i = 0; i < sections.length; i++) {
				sectionOffsets.push(sections[i].$target.offset().top || 0);
			}
		}

		function setActive(index) {
			if (!sections.length) {
				return;
			}

			var next = Math.max(0, Math.min(index, sections.length - 1));
			if (next === currentActive) {
				return;
			}

			currentActive = next;
			$menuLinks.removeClass('active');
			sections[next].$link.addClass('active');
		}

		function resolveIndexByProbe(probeY) {
			if (!sectionOffsets.length) {
				return 0;
			}

			if (probeY <= sectionOffsets[0]) {
				return 0;
			}

			var last = sectionOffsets.length - 1;
			if (probeY >= sectionOffsets[last]) {
				return last;
			}

			var lo = 0;
			var hi = last;
			var mid = 0;

			while (lo <= hi) {
				mid = Math.floor((lo + hi) / 2);
				if (sectionOffsets[mid] <= probeY) {
					lo = mid + 1;
				} else {
					hi = mid - 1;
				}
			}

			return Math.max(0, hi);
		}

		function resolveActiveByScroll() {
			if (!sections.length) {
				return;
			}

			var now = Date.now();
			if (now < suppressSpyUntil) {
				return;
			}

			var scrollTop = $(window).scrollTop() || 0;
			var viewportHeight = $(window).height() || 0;
			var documentHeight = $(document).height() || 0;
			var headerProbe = getScrollOffset() + 20;
			// Смещаем точку активации глубже в экран, чтобы секция
			// переключалась не "по касанию шапки", а когда реально вошла в viewport.
			var viewportProbe = Math.floor(viewportHeight * 0.35);
			var probeY = scrollTop + Math.max(headerProbe, viewportProbe);

			// На самом низу страницы всегда активируем последний пункт
			// (иначе высокий offset шапки может не дать "дотянуться" до последнего якоря).
			if (scrollTop + viewportHeight >= documentHeight - 4) {
				setActive(sections.length - 1);
				return;
			}

			var nextIndex = resolveIndexByProbe(probeY);

			setActive(nextIndex);
		}

		function onFrame() {
			rafScheduled = false;
			resolveActiveByScroll();
		}

		function requestUpdate() {
			if (rafScheduled) {
				return;
			}
			rafScheduled = true;
			window.requestAnimationFrame(onFrame);
		}

		function requestRecalc() {
			if (recalcRafScheduled) {
				return;
			}
			recalcRafScheduled = true;
			window.requestAnimationFrame(function () {
				recalcRafScheduled = false;
				rebuildSectionOffsets();
				requestUpdate();
			});
		}

		$menuLinks.on('click.praktikaMenuSync', function (e) {
			var sectionKey = String($(this).attr('data-section-key') || '').trim();
			var href = $(this).attr('href');
			var $target = $();

			if (sectionKey !== '') {
				if (sectionKey === 'section-form-questions') {
					$target = $('#section-form-questions').first();
				} else {
					$target = $section.find('.js-praktika-section-marker[data-section-key="' + sectionKey + '"]').first();
				}
			}

			if ((!$target || !$target.length) && href && href !== '#') {
				$target = $(href);
			}

			if (!$target.length) {
				return;
			}

			e.preventDefault();

			var index = -1;
			for (var i = 0; i < sections.length; i++) {
				if (sections[i].$target.is($target)) {
					index = i;
					break;
				}
			}
			if (index >= 0) {
				setActive(index);
			}

			var destination = Math.max(0, $target.offset().top - getScrollOffset());
			var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			var duration = reduceMotion ? 0 : 420;
			suppressSpyUntil = Date.now() + duration + 80;
			$('html, body').stop(true).animate({ scrollTop: destination }, duration);
		});

		rebuildSections();
		resolveActiveByScroll();

		$(window).on('scroll.praktikaMenuSync resize.praktikaMenuSync orientationchange.praktikaMenuSync load.praktikaMenuSync', function (e) {
			if (e.type !== 'scroll') {
				requestRecalc();
			}
			requestUpdate();
		});

		if (window.ResizeObserver && $content.length) {
			contentResizeObserver = new ResizeObserver(function () {
				requestRecalc();
			});
			contentResizeObserver.observe($content[0]);
		}
	}

	initPraktikaMenuSync();
	
	
	$('.exercise-slider_active').each(function () {
		var $slider = $(this);

		if ($slider.hasClass('slick-initialized')) {
			return;
		}

		$slider.slick({
			infinite: false,
			dots: true,
			arrows: true,
			slide: '.exercise-slider__item',
			slidesToShow: 1,
			slidesToScroll: 1
		});
	});

	/* После смены ширины контейнера (мобильная вёрстка) slick оставляет старую геометрию списка */
	var exerciseSliderLayoutTimer;
	function refreshExerciseSliders() {
		$('.exercise-slider_active.slick-initialized').each(function () {
			$(this).slick('setPosition');
		});
	}
	requestAnimationFrame(refreshExerciseSliders);
	$(window).on('resize orientationchange', function () {
		clearTimeout(exerciseSliderLayoutTimer);
		exerciseSliderLayoutTimer = setTimeout(refreshExerciseSliders, 150);
	});
	
	/* $('.exercise-switches__item').click(function () {
		
		$(this).closest('.praktika-exercise').find('.exercise-item').removeClass("active");
		
		var exsw = $(this).attr('data-target');
		$(this).closest('.praktika-exercise').find('.exercise-item[data-target=' + exsw + ']').addClass("active");
	});  */
	
	
	
	
	$('.textarea-resize').keydown(function () {
		var el = this;
		setTimeout(function() {
			el.style.cssText = 'height:auto; padding:0';
			el.style.cssText = 'height:' + el.scrollHeight + 'px';
		}, 1);
	}); 
	
	$('.comment-form-main textarea').keyup(function(){
		var $this = $(this),
		vall = $this.val();
		
		if(vall.length >= 1){
			$(this).closest('form').addClass("active");
			}else {
			$(this).closest('form').removeClass("active");
		}
	});
	
	$('.answer-main textarea').keyup(function(){
		var $this = $(this),
		vall = $this.val();
		
		if(vall.length >= 1){
			$(this).closest('.answer-main').addClass("active");
			}else {
			$(this).closest('.answer-main').removeClass("active");
		}
	});
	
	
	$('.praktika-comment-item__edit .textarea-resize').focus(function () {
		var el = this;
		setTimeout(function() {
			el.style.cssText = 'height:auto; padding:0';
			el.style.cssText = 'height:' + el.scrollHeight + 'px';
		}, 1);
	}); 
	
	
	$(".comment-form-main form, .praktika-comment__answer, .praktika-comment-item__edit").submit(function(e) {
		e.preventDefault();
	});
	
	
	
	
	
	/* Section-form-questions */
	
	$(".form-questions__main-form").not('#faqContactForm').submit(function(e) {
		e.preventDefault();
	});
	
	$('.form-questions__main-form').not('#faqContactForm').submit(function () {
		$(this).closest('.form-questions').addClass("active");
	});
	
	$('.form-questions__succes .btn').click(function () {
		$(this).closest('.form-questions').removeClass("active");
	});
	
	
	
	
	
	/* ЛК использует общий .burger, но открывает свою панель .modal-mobile-menu-lk. */
	$(document).on('click', '.body_lk .burger', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $panel = $('.modal-mobile-menu-lk');
		if (!$panel.length) {
			return;
		}
		var opening = !$panel.hasClass('active');
		if (opening) {
			$panel.addClass('active');
			$('.overlay').addClass('active');
			$('.body').addClass('body-fixed');
			$('.modal').removeClass('active');
			$('.modal-login').removeClass('active');
			$(this).addClass('active');
			$(this).closest('.header').addClass('active');
		} else {
			$panel.removeClass('active');
			$('.overlay').removeClass('active');
			$('.body').removeClass('body-fixed');
			$(this).removeClass('active');
			$(this).closest('.header').removeClass('active');
		}
	});

	$(document).on('keydown', '.body_lk .burger', function (e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			$(this).trigger('click');
		}
	});

	function closeLkNotifications() {
		$('.notification-icon').removeClass('active').attr('aria-expanded', 'false');
		$('.lk-notifications-popup').attr('aria-hidden', 'true');
	}

	$('.notification-icon').on('click', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var isActive = $(this).hasClass('active');
		closeLkNotifications();
		if (!isActive) {
			$(this).addClass('active').attr('aria-expanded', 'true');
			$(this).find('.lk-notifications-popup').attr('aria-hidden', 'false');
		}
	});

	$('.notification-icon').on('keydown', function (e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			$(this).trigger('click');
		}
	});

	$('.lk-notifications-popup').on('click', function (e) {
		e.stopPropagation();
	});

	$(document).on('click', function (e) {
		if (!$(e.target).closest('.notification-icon').length) {
			closeLkNotifications();
		}
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape') {
			closeLkNotifications();
		}
	});
	
	
	/* Sidebar */
	
	$('.sidebar-menu__item').not('.sidebar-menu__item_exit').click(function () {
		$(this).closest('.sidebar-menu').find('.sidebar-menu__item').removeClass("active");
		$(this).addClass("active");
	});
	
	$('.sidebar-menu__item').not('.sidebar-menu__item_exit').click(function () {
		var smt = $(this).attr('data-target');
		$('.lk-slide').removeClass("active");
		$('.lk-slide[data-target=' + smt + ']').addClass("active");
	}); 
	
	
	if ($(window).width() < yogaViewportBp.lg ) {
		$('.sidebar-menu__item').click(function () {
			$('.overlay').removeClass("active");
			$('.modal').removeClass("active");
			$('.modal-login').removeClass("active");
			$('.modal-mobile-menu').removeClass("active")
			$('.modal-mobile-menu-lk').removeClass("active")
			$('.body').removeClass("body-fixed");
			closeLibraryFiltersScreen(true);
		});
		$('.sidebar-menu-secondary__link').on('click', function () {
			$('.overlay').removeClass("active");
			$('.modal').removeClass("active");
			$('.modal-login').removeClass("active");
			$('.modal-mobile-menu').removeClass("active");
			$('.modal-mobile-menu-lk').removeClass("active");
			$('.body').removeClass("body-fixed");
			closeLibraryFiltersScreen(true);
		});
	};
	
	if ($(window).width() < yogaViewportBp.lg ) {
		$(".sidebar-menu__item").click(function () {
			var elementClick = $('#section-lk');
			var destination = $(elementClick).offset().top - 400;
			$('html, body').animate({ scrollTop: destination }, 400);
			return false;
		});
	};
	
	
	
	/* Section-lk */
	
	$('.lk-gender-item').click(function () {
		$(this).closest('.lk-gender').find('.lk-gender-item').removeClass("active");
		$(this).addClass("active");
	});
	
	$(".lk-form").submit(function(e) {
		e.preventDefault();
	});
	
	/* «Показать еще» для вопросов ЛК — см. делегированный обработчик .show-more-questions во втором $(document).ready */
	
	$('.lk-questions-form .input').keydown(function () {
		var el = this;
		setTimeout(function() {
			el.style.cssText = 'height:auto; padding:0';
			el.style.cssText = 'height:' + el.scrollHeight + 'px';
		}, 1);
	}); 
	
	$('.lk-questions-form .input').focus(function () {
		var el = this;
		setTimeout(function() {
			el.style.cssText = 'height:auto; padding:0';
			el.style.cssText = 'height:' + el.scrollHeight + 'px';
		}, 1);
	}); 
	
	
	$(".lk-questions-form form").submit(function(e) {
		e.preventDefault();
	});
	
	
	$('.lk-form-safe .btn').on('click', function () {
		
		var val1 = $('.lk-form-item_newpass .input').val();
		var val2 = $('.lk-form-item_newpassrepeat .input').val();
		
		if (val1 !== val2) {
			$('.lk-form-item_newpassrepeat').addClass('error');
			} else {
			$('.lk-form-item_newpassrepeat').removeClass('error');
		}
	});
	
	
	
	$('.lk-form-item .input').on('input', function () {
		$(this).closest('.lk-form-item').removeClass("error");
	});
	
	
	$('.lk-settings__slide_main .lk-settings-item_action').click(function () {
		$('.lk-settings__slide').removeClass("active");
		$('.lk-settings__slide_payment').addClass("active");
		
	});
	
	$('.lk-settings__slide_payment .form-back').click(function () {
		$('.lk-settings__slide').removeClass("active");
		$('.lk-settings__slide_main').addClass("active");
		
	});
	
	
	/* Section-blog */
	
	
	$('.blog-radios label').click(function () {
		$(this).closest('.blog-radios').find('label').removeClass("active");
		$(this).addClass("active");
		
		
	});
	
	// Поиск блога: обычный GET на action формы (/blog/?s=…). Раньше был preventDefault() —
	// страница не перезагружалась, .blog-main скрывался — визуально «ничего не найдено».
	
	$('.blog-articles__more > .btn, .popular-articles__media-more > .btn').click(function () {
		$(this).find('span').toggleClass("active");
		$('.blog-article-item_last').toggleClass("hidden");
		
	});
	
	if ($(window).width() >= yogaViewportBp.lg ) {
		$(window).scroll(function(){
			if ($(window).scrollTop() > 107) {
				$(".section-blog-form").addClass("active");
				} else {
				$(".section-blog-form").removeClass("active");
			};
		});  
		if ($(window).scrollTop() > 107) {
			$(".section-blog-form").addClass("active");
			} else {
			$(".section-blog-form").removeClass("active");
		};
	};
	
	
	if ($(window).width() > yogaViewportBp.tightDesktop ) {
		$(window).scroll(function(){
			if ($(window).scrollTop() > 119) {
				$(".section-blog-form").addClass("active");
				} else {
				$(".section-blog-form").removeClass("active");
			};
		});  
		if ($(window).scrollTop() > 119) {
			$(".section-blog-form").addClass("active");
			} else {
			$(".section-blog-form").removeClass("active");
		};
	};
	
	$(document).ready(function () {
		$('.blog-search input').on('input', function () {
			const $parent = $(this).parent();
			const value = $(this).val();
			
			if (value.length >= 1) {
				$parent.addClass('active');
				$parent.removeClass('search-active');
				
				} else {
				$parent.removeClass('active');
				$parent.removeClass('search-active');
			}
		});
	});
	
	$(document).ready(function () {
		$('.blog-search__delete-btn').on('click', function () {
			const $parent = $(this).parent();
			const value = $(this).val();
			
			if (value.length >= 1) {
				$parent.addClass('active');
				$parent.removeClass('search-active');
				
				} else {
				$parent.removeClass('active');
				$parent.removeClass('search-active');
			}
		});
	});
	
	
	/* Section-post — липкая колонка автора только на ширине > lg (≤1024: вертикальная вёрстка, без sticky) */
	$(document).ready(function () {
		var $postAside = $('.post-author-fixed');
		if (!$postAside.length) {
			return;
		}

		function postStickyIsDesktop() {
			return $(window).width() >= yogaViewportBp.lg;
		}

		function postAsideClearSticky() {
			$postAside.attr('style', '');
		}

		var block_pos_03 = 0;
		var wrap_pos_03 = 0;
		var block_height_03 = 0;

		function postAsideRefreshMetrics() {
			if (!$postAside.length) {
				return;
			}
			block_height_03 = $postAside.outerHeight();
			var $author = $('.post-author');
			block_pos_03 = ($author.offset()?.top) || ($postAside.offset()?.top) || 0;
			wrap_pos_03 = $('.post-layout').offset()?.top || 0;
		}

		postAsideRefreshMetrics();

		$(window).on('resize', function () {
			postAsideRefreshMetrics();
			if (!postStickyIsDesktop()) {
				postAsideClearSticky();
			}
		});

		$(window).scroll(function () {
			if (!postStickyIsDesktop()) {
				postAsideClearSticky();
				return;
			}

			postAsideRefreshMetrics();
			var wrap_height_03 = $('.post-layout').outerHeight();

			if (!block_height_03 || !wrap_height_03) {
				return;
			}

			var pos_absolute_03 = wrap_pos_03 + wrap_height_03 - block_height_03;
			var st = $(window).scrollTop();

			if (st > pos_absolute_03 - 105) {
				$postAside.css({
					position: 'absolute',
					top: 'calc(100% + 0px)',
					transform: 'translateY(-100%)'
				});
			} else if (st > block_pos_03 - 105) {
				$postAside.css({
					position: 'fixed',
					top: '105px',
					transform: 'translateY(0%)'
				});
			} else {
				$postAside.css({
					position: 'absolute',
					top: '0px',
					transform: 'translateY(0%)'
				});
			}
		});
	});
	
	
	
	$(document).ready(function () {
		function checkWidthAndInitSlick() {
			var $popularArticlesSlider = $('.popular-articles-slider');

			if (!$popularArticlesSlider.length) {
				return;
			}

			if ($(window).width() >= yogaViewportBp.sm) {
				if (!$popularArticlesSlider.hasClass('slick-initialized')) {
					$popularArticlesSlider.slick({
						// твои настройки слайдера
						infinite: true,
						dots: true,
						arrows: true,
						slidesToShow: 3,
						slidesToScroll: 1,
						
						prevArrow: ".popular-articles__intro .slick-prev",
						nextArrow: ".popular-articles__intro .slick-next",
						responsive: [
							{
								breakpoint: yogaViewportBp.lg,
								settings: {   
									slidesToShow: 2,
									slidesToScroll: 1,   
								}
							}
						]
					});
				}
			} else {
				if ($popularArticlesSlider.hasClass('slick-initialized')) {
					$popularArticlesSlider.slick('unslick');
				}
			}
		}
		
		// Инициализация при загрузке
		checkWidthAndInitSlick();
		
		// Проверка при изменении размера окна
		$(window).on('resize', function () {
			checkWidthAndInitSlick();
		});
	});
	
	
	
	/* Section-popular-articles */
	
	
	/* Section-contacts */
	
	/* $(".section-form-questions_contacts .form-questions__main-form").submit(function(e) {
		e.preventDefault();
		$('.body').addClass("body-fixed");
		$('.overlay').addClass("active");
		$('.modal').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-default_formsucces').addClass("active");
	}); */
	
	
	/* Section- */
	
	
	
	
	/* Modals */
	
	$('.overlay').click(function () {
		$(this).removeClass("active");
		$('.modal').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-mobile-menu').removeClass("active");
		$('.modal-mobile-menu-lk').removeClass("active");
		$('.body').removeClass("body-fixed");
		$('.modal-addnewcard').removeClass("active");
		$('.body_lk .header').removeClass("active");
		$('.body_lk .burger').removeClass("active");
		closeLibraryFiltersScreen(true);
	});
	
	$('.modal-close').click(function () {
		$('.overlay').removeClass("active");
		$('.modal').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-mobile-menu').removeClass("active");
		$('.modal-mobile-menu-lk').removeClass("active");
		$('.modal-addnewcard').removeClass("active");
		$('.body').removeClass("body-fixed");
		$('.body_lk .header').removeClass("active");
		$('.body_lk .burger').removeClass("active");
		closeLibraryFiltersScreen(true);
	});

	var pendingCartRemoveForm = null;

	function closeCartClearModal() {
		pendingCartRemoveForm = null;
		$('#yoga-cart-clear-modal').removeClass('active').attr('aria-hidden', 'true');
		$('.overlay').removeClass('active');
		$('.body').removeClass('body-fixed');
	}

	$(document).on('submit', '.yoga-checkout-tariff__remove-form, .yoga-checkout-summary__remove-form', function(event) {
		var $modal = $('#yoga-cart-clear-modal');
		if (!$modal.length) {
			return;
		}
		event.preventDefault();
		pendingCartRemoveForm = this;
		$('.modal, .modal-login').removeClass('active');
		$modal.addClass('active').attr('aria-hidden', 'false');
		$('.overlay').addClass('active');
		$('.body').addClass('body-fixed');
		$modal.find('.yoga-cart-clear__button_cancel').trigger('focus');
	});

	$(document).on('click', '.yoga-cart-clear__button_confirm', function() {
		if (!pendingCartRemoveForm) {
			return;
		}
		var form = pendingCartRemoveForm;
		pendingCartRemoveForm = null;
		form.submit();
	});

	$(document).on('click', '.yoga-cart-clear__button_cancel, #yoga-cart-clear-modal .modal-close', closeCartClearModal);
	$(document).on('click', '.overlay', function() {
		if (pendingCartRemoveForm) {
			closeCartClearModal();
		}
	});
	$(document).on('keydown', function(event) {
		if (event.key === 'Escape' && $('#yoga-cart-clear-modal').hasClass('active')) {
			closeCartClearModal();
		}
	});

	function applyCheckoutCoupon() {
		var $promo = $('.yoga-checkout-promo');
		var $input = $promo.find('.yoga-checkout-promo__input');
		var $button = $promo.find('.yoga-checkout-promo__apply');
		var couponCode = $.trim($input.val());

		if (!$promo.length || $button.prop('disabled')) {
			return;
		}
		if (!couponCode) {
			showNotification('Введите промокод.', 'error');
			$input.trigger('focus');
			return;
		}

		var originalLabel = $button.text();
		var couponApplied = false;
		$button.prop('disabled', true).text('Применяем…');

		$.ajax({
			url: $promo.data('ajax-url'),
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'yoga_apply_checkout_coupon',
				nonce: $promo.data('coupon-nonce'),
				coupon_code: couponCode
			}
		}).done(function(response) {
			if (!response || !response.success || !response.data) {
				showNotification('Не удалось применить промокод.', 'error');
				return;
			}

			$('.yoga-checkout-summary__line_discount .yoga-checkout-summary__line-value').text(response.data.discount);
			$('.yoga-checkout-summary__total-value').text(response.data.total);
			$('.yoga-checkout-summary__submit > span').text(response.data.pay_label);
			$input.prop('readonly', true);
			couponApplied = true;
			showNotification(response.data.message);
			$(document.body).trigger('updated_checkout');
		}).fail(function(xhr) {
			var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
				? xhr.responseJSON.data.message
				: 'Не удалось применить промокод.';
			showNotification(message, 'error');
		}).always(function() {
			$button.prop('disabled', couponApplied).text(couponApplied ? 'Применён' : originalLabel);
		});
	}

	$(document).on('click', '.yoga-checkout-promo__apply', applyCheckoutCoupon);
	$(document).on('keydown', '.yoga-checkout-promo__input', function(event) {
		if (event.key === 'Enter') {
			event.preventDefault();
			applyCheckoutCoupon();
		}
	});
	
	jQuery(function($){
		$(".input_phone").mask("+7 (999) 999 99 99");
	});
	
	jQuery(function($){
		$(".input_birth").mask("99.99.9999");
	});
	
	jQuery(function($){
		$(".input_card").mask("9999 9999 9999 9999");
	});
	
	jQuery(function($){
		$(".input_carddate").mask("99/99");
	});
	
	jQuery(function($){
		$(".input_cardcode").mask("999");
	});
	
	
	$('.modal-login-inner__slide .input').keyup(function(){
		var $this = $(this),
		vall = $this.val();
		
		if(vall.length >= 1){
			$('.input:valid').closest('.form').addClass("active");
			$('.input:invalid').closest('.form').removeClass("active");
			
			$('.input:valid').closest('.form').removeClass("disabled");
			$('.input:invalid').closest('.form').removeClass("disabled");
			}else {
			
		}
	});
	
	$('.modal-login-inner button').click(function () {
		$(this).closest('.form').find('.input:invalid').closest('.form').addClass("disabled");
	});   
	
	
	function yogaOpenLoginModal(slideTarget) {
		$('.overlay').addClass('active');
		$('.body').addClass('body-fixed');
		$('.modal').removeClass('active');
		$('.modal-login').addClass('active');
		if (slideTarget) {
			yogaSwitchLoginSlide(slideTarget);
		} else {
			$('.modal-login').removeClass('modal-login--recovery');
		}
	}

	function yogaSwitchLoginSlide(target) {
		if (!target) {
			return;
		}
		$('.modal-login-inner__slide').removeClass('active');
		$('.modal-login-inner__slide[data-target="' + target + '"]').addClass('active');
		$('.modal-login').toggleClass('modal-login--recovery', target === '3');
		$('.modal-login').toggleClass('modal-login--success', target === '4');
		$('.yoga-form-login-message').removeClass('is-visible').empty();

		if (target === '3') {
			var $loginEmail = $('.yoga-form-login input[name="log"]').val();
			if ($loginEmail) {
				$('.yoga-form-recovery input[name="user_login"]').val($loginEmail);
			}
		}
	}

	$(document).on('click', '.modal-login .ml-sl-switch', function (e) {
		e.preventDefault();
		yogaSwitchLoginSlide($(this).attr('data-target'));
	});

	(function yogaMaybeOpenLoginModalFromUrl() {
		var params = new URLSearchParams(window.location.search);
		var openLogin = params.get('open_login');
		var isLostPasswordPath = /\/lost-password\/?$/i.test(window.location.pathname);

		if (openLogin === 'recovery' || isLostPasswordPath) {
			yogaOpenLoginModal('3');
			if (openLogin) {
				var cleanUrl = window.location.pathname + window.location.hash;
				window.history.replaceState({}, document.title, cleanUrl);
			}
		} else if (openLogin === 'checkout') {
			yogaOpenLoginModal('1');
			var cleanCheckoutUrl = window.location.pathname + window.location.hash;
			window.history.replaceState({}, document.title, cleanCheckoutUrl);
		}
	})();
	
	$(".modal-login .form").submit(function(e) {
		e.preventDefault();
	});
	
	$(document).on('input focus', '.yoga-form-login .input', function() {
		var $msg = $(this).closest('.yoga-form-login').find('.yoga-form-login-message');
		$msg.removeClass('is-visible').empty();
	});

	// AJAX: форма входа по почте
	$(document).on('submit', '.yoga-form-login', function(e) {
		e.preventDefault();
		var $form = $(this);
		var $msg = $form.find('.yoga-form-login-message');
		var showLoginErr = function(html) {
			$msg.html(html).addClass('is-visible');
		};
		var messageFromPayload = function(d, fallbackText) {
			if (d === undefined || d === null || d === '') return fallbackText;
			if (typeof d === 'string') return d;
			if (typeof d === 'object' && d.code === 'not_found') return 'Пользователь не найден';
			if (typeof d === 'object' && d.message) return d.message;
			return fallbackText;
		};
		$msg.removeClass('is-visible').empty();
		var $btn = $form.find('.btn');
		if (typeof yoga_ajax === 'undefined') return;

		if (yoga_ajax.smartcaptcha_enabled && (!$form.find('input[name="smart-token"]').val() || !$form.find('input[name="smart-token"]').val().trim())) {
			showLoginErr('Подтвердите, что вы не робот');
			return;
		}

		var resetSmartCaptcha = function() {
			$form.find('.yoga-smart-captcha-mount').each(function() {
				window.yogaResetSmartCaptchaMount(this);
			});
		};

		$btn.prop('disabled', true);
		$.ajax({
			url: yoga_ajax.ajax_url,
			method: 'POST',
			data: $form.serialize(),
			dataType: 'json'
		})
			.done(function(r) {
				if (r && r.success) {
					$('.modal-login').removeClass("active");
					location.reload();
				} else {
					showLoginErr(messageFromPayload(r && r.data, 'Ошибка входа'));
					resetSmartCaptcha();
				}
			})
			.fail(function(xhr) {
				var json = xhr && xhr.responseJSON ? xhr.responseJSON : null;
				var d = json && json.data;
				if (d === undefined && xhr && xhr.responseText) {
					try {
						var parsed = JSON.parse(xhr.responseText);
						if (parsed) d = parsed.data;
					} catch (err) {}
				}
				showLoginErr(messageFromPayload(d, 'Ошибка соединения'));
				resetSmartCaptcha();
			})
			.always(function() { $btn.prop('disabled', false); });
	});
	
	// AJAX: форма регистрации по почте
	$(document).on('submit', '.yoga-form-register', function(e) {
		e.preventDefault();
		var $form = $(this);
		var $btn = $form.find('label[for="login-reg-btn"]');
		if (typeof yoga_ajax === 'undefined') return;
		var extractAjaxError = function(xhr, fallbackText) {
			var json = xhr && xhr.responseJSON ? xhr.responseJSON : null;
			if (json && json.data) {
				if (typeof json.data === 'string') return json.data;
				if (json.data.message) return json.data.message;
			}
			if (xhr && xhr.responseText) {
				try {
					var parsed = JSON.parse(xhr.responseText);
					if (parsed && parsed.data) {
						if (typeof parsed.data === 'string') return parsed.data;
						if (parsed.data.message) return parsed.data.message;
					}
				} catch (err) {}
			}
			return fallbackText;
		};

		if (yoga_ajax.smartcaptcha_enabled && (!$form.find('input[name="smart-token"]').val() || !$form.find('input[name="smart-token"]').val().trim())) {
			alert('Подтвердите, что вы не робот');
			return;
		}

		var resetSmartCaptchaRegister = function() {
			$form.find('.yoga-smart-captcha-mount').each(function() {
				window.yogaResetSmartCaptchaMount(this);
			});
		};

		$btn.prop('disabled', true);
		$.post(yoga_ajax.ajax_url, $form.serialize())
			.done(function(r) {
				if (r.success) {
					$('.modal-login').removeClass('active');
					location.reload();
				} else {
					var message = (r && r.data && r.data.message) ? r.data.message : (r.data || 'Ошибка регистрации');
					alert(message);
					resetSmartCaptchaRegister();
				}
			})
			.fail(function(xhr) {
				alert(extractAjaxError(xhr, 'Ошибка соединения'));
				resetSmartCaptchaRegister();
			})
			.always(function() { $btn.prop('disabled', false); });
	});
	
	// AJAX: восстановление пароля — после успеха показываем слайд 4
	$(document).on('submit', '.yoga-form-recovery', function(e) {
		e.preventDefault();
		var $form = $(this);
		var $btn = $form.find('label[for="recovery-btn"]');
		if (typeof yoga_ajax === 'undefined') return;
		var extractAjaxError = function(xhr, fallbackText) {
			var json = xhr && xhr.responseJSON ? xhr.responseJSON : null;
			if (json && json.data) {
				if (typeof json.data === 'string') return json.data;
				if (json.data.message) return json.data.message;
			}
			if (xhr && xhr.responseText) {
				try {
					var parsed = JSON.parse(xhr.responseText);
					if (parsed && parsed.data) {
						if (typeof parsed.data === 'string') return parsed.data;
						if (parsed.data.message) return parsed.data.message;
					}
				} catch (err) {}
			}
			return fallbackText;
		};

		if (yoga_ajax.smartcaptcha_enabled && (!$form.find('input[name="smart-token"]').val() || !$form.find('input[name="smart-token"]').val().trim())) {
			alert('Подтвердите, что вы не робот');
			return;
		}

		var resetSmartCaptchaRecovery = function() {
			$form.find('.yoga-smart-captcha-mount').each(function() {
				window.yogaResetSmartCaptchaMount(this);
			});
		};

		$btn.prop('disabled', true);
		$.post(yoga_ajax.ajax_url, $form.serialize())
			.done(function(r) {
				if (r.success) {
					$('.modal-login-inner__slide').removeClass("active");
					$('.modal-login-inner__slide[data-target=4]').addClass("active");
					$('.modal-login').removeClass('modal-login--recovery').addClass('modal-login--success');
				} else {
					var message = (r && r.data && r.data.message) ? r.data.message : (r.data || 'Не удалось отправить письмо');
					alert(message);
					resetSmartCaptchaRecovery();
				}
			})
			.fail(function(xhr) {
				alert(extractAjaxError(xhr, 'Ошибка соединения'));
				resetSmartCaptchaRecovery();
			})
			.always(function() { $btn.prop('disabled', false); });
	});
	
	
	
	$('.modal-call').click(function () {
		$('.body').addClass("body-fixed");
		$('.overlay').addClass("active");
		$('.modal').removeClass("active");
		$('.modal-login').removeClass("active");
	});
	
	
	// Обработка клика по "Развернуть" в отзывах
	jQuery(document).on('click', '.modal-call_review', function(e) {
		e.preventDefault();
		
		var reviewData = {
			name: jQuery(this).data('review-name'),
			age: jQuery(this).data('review-age'),
			job: jQuery(this).data('review-job'),
			image: jQuery(this).data('review-image'),
			text: jQuery(this).data('review-text')
		};
		
		// Заполняем модальное окно данными
		jQuery('.review-modal-name').text(reviewData.name);
		jQuery('.review-modal-age').text(reviewData.age);
		jQuery('.review-modal-job').text(reviewData.job);
		jQuery('.review-modal__main-image img').attr('src', reviewData.image);
		jQuery('.review-modal__text').html('<p>' + reviewData.text + '</p>');
		
		// Показываем модальное окно
		jQuery('.modal_review').addClass('active');
		jQuery('body').addClass('modal-open');
	});
	
	
	
	
	/*
		$('.mobile-menu-switches__item').click(function () {
		$(this).closest('.mobile-menu-switches').find('.mobile-menu-switches__item').removeClass("active");
		$(this).addClass("active");
		
		var mobsw = $(this).attr('data-target');
		$('.mobile-menu-sub').removeClass("active");
		$('.mobile-menu-sub[data-target=' + mobsw + ']').addClass("active");
		}); 
	*/
	
	$('.mobile-menu-main-item_sw').click(function () {
		var $menu = $(this).closest('.mobile-menu-inner');
		$menu.addClass('mobile-menu-inner_library');
		$menu.find('.mobile-menu__slide_sub').addClass("active");
		$menu.find('.mobile-menu__slide_main').removeClass("active");
	});
	
	$('.mobile-menu-back').click(function () {
		var $menu = $(this).closest('.mobile-menu-inner');
		$menu.removeClass('mobile-menu-inner_library');
		$menu.find('.mobile-menu__slide_sub').removeClass("active");
		$menu.find('.mobile-menu__slide_main').addClass("active");
	});
	
	
	
	
	
	
	
	
	$('.modal-call_delcomm').click(function () {
		$('.modal-default_delcomm').addClass("active");
	});
	
	(function cookieBannerInit() {
		var cookieName = 'yoga_cookie_consent';
		var legacyLsKey = 'yoga_cookie_consent_v1';
		var maxAgeSec = 365 * 24 * 60 * 60;
		var $banner = $('#yoga-modal-cookie');
		if (!$banner.length) {
			return;
		}
		function readConsentValue() {
			var raw = '';
			try {
				var parts = ('; ' + document.cookie).split('; ' + cookieName + '=');
				if (parts.length === 2) {
					raw = parts.pop().split(';').shift() || '';
				}
			} catch (e) {
				raw = '';
			}
			if (raw !== '') {
				try {
					return decodeURIComponent(raw);
				} catch (e2) {
					return raw;
				}
			}
			try {
				if (window.localStorage) {
					return window.localStorage.getItem(legacyLsKey) || '';
				}
			} catch (e3) {
				// ignore
			}
			return '';
		}
		function writeConsentValue(val) {
			var secure = window.location.protocol === 'https:' ? '; Secure' : '';
			document.cookie = cookieName + '=' + encodeURIComponent(val)
				+ '; Path=/; Max-Age=' + maxAgeSec + '; SameSite=Lax' + secure;
			try {
				if (window.localStorage) {
					window.localStorage.setItem(legacyLsKey, val);
				}
			} catch (e) {
				// ignore
			}
		}
		function hideCookieBanner() {
			$banner.removeClass('active');
		}
		function tryShowBanner() {
			var v = readConsentValue();
			if (v === 'accept' || v === 'decline') {
				return;
			}
			$banner.addClass('active');
		}
		tryShowBanner();
		$banner.on('click', '.cookie__btn-accept', function () {
			writeConsentValue('accept');
			hideCookieBanner();
		});
		$banner.on('click', '.cookie__btn-decline', function () {
			writeConsentValue('decline');
			hideCookieBanner();
		});
	})();
	
	
	(function initLkUnsavedChangesGuard() {
		var $profileForm = $('#profile-form');
		if (!$profileForm.length) {
			return;
		}

		var initialState = '';

		function getProfileState() {
			return $profileForm.find('input, select, textarea').map(function () {
				var $field = $(this);
				var type = String($field.attr('type') || '').toLowerCase();
				var value;

				if (type === 'file') {
					var file = this.files && this.files[0];
					value = file ? [file.name, file.size, file.lastModified].join(':') : '';
				} else if (type === 'checkbox' || type === 'radio') {
					value = this.checked ? '1' : '0';
				} else {
					value = $field.val();
				}

				return String($field.attr('name') || '') + '=' + String(value == null ? '' : value);
			}).get().join('\u001e');
		}

		function updateDirtyState() {
			window.yogaLkHasUnsavedChanges = window.yogaLkProfileHasUnsavedChanges();
		}

		window.yogaLkProfileHasUnsavedChanges = function () {
			return getProfileState() !== initialState;
		};

		window.yogaMarkLkProfileClean = function () {
			initialState = getProfileState();
			window.yogaLkHasUnsavedChanges = false;
		};

		$profileForm.on('input change keyup', 'input, select, textarea', updateDirtyState);
		window.yogaMarkLkProfileClean();
	})();

	var pendingLkNavigationUrl = '';

	function showLkUnsavedChangesModal() {
		$('.modal-default_logout').removeClass('active');
		$('.modal-mobile-menu-lk').removeClass('active');
		$('.overlay').addClass('active');
		$('#lk-unsaved-changes-modal').addClass('active').attr('aria-hidden', 'false');
		$('body').addClass('body-fixed');
	}

	function closeLkUnsavedChangesModal() {
		$('#lk-unsaved-changes-modal').removeClass('active').attr('aria-hidden', 'true');
		$('.overlay').removeClass('active');
		$('body').removeClass('body-fixed');
		pendingLkNavigationUrl = '';
	}

	$(document).on('click', '.lk-unsaved-changes-modal__cancel', function () {
		closeLkUnsavedChangesModal();
	});

	$(document).on('click', '.lk-unsaved-changes-modal__leave', function () {
		if (pendingLkNavigationUrl) {
			window.location.href = pendingLkNavigationUrl;
		}
	});

	$(document).on('click', 'a[href]', function (event) {
		if (event.isDefaultPrevented() || event.which > 1 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}

		if (typeof window.yogaLkProfileHasUnsavedChanges !== 'function' || !window.yogaLkProfileHasUnsavedChanges()) {
			return;
		}

		var $link = $(this);
		var href = String($link.attr('href') || '').trim();
		if (!href || href.charAt(0) === '#' || /^(?:javascript:|mailto:|tel:)/i.test(href) || $link.attr('target') === '_blank' || $link.is('[download]')) {
			return;
		}

		var destination;
		try {
			destination = new URL(href, window.location.href);
		} catch (ignore) {
			return;
		}

		if (destination.pathname === window.location.pathname && destination.search === window.location.search) {
			return;
		}

		event.preventDefault();
		pendingLkNavigationUrl = destination.href;
		showLkUnsavedChangesModal();
	});

	$(document).on('click', '.modal-call_logout', function (event) {
		event.preventDefault();
		$('.modal-mobile-menu-lk').removeClass('active');
		$('.body_lk .burger').removeClass('active');
		$('.body_lk .header').removeClass('active');

		var hasUnsavedChanges = typeof window.yogaLkProfileHasUnsavedChanges === 'function'
			? window.yogaLkProfileHasUnsavedChanges()
			: Boolean(window.yogaLkHasUnsavedChanges);

		if (hasUnsavedChanges) {
			pendingLkNavigationUrl = $('.modal-default_logout .btn_dark').first().attr('href') || '';
			showLkUnsavedChangesModal();
			return;
		}

		$('.overlay').addClass('active');
		$('.modal-default_logout').addClass('active');
		$('.body').addClass('body-fixed');
	});
	
	// Закрытие модального окна выхода по кнопке "Нет, остаться" или крестику
	$('.modal-default_logout .modal-close, .modal-default_logout .modal-close-logout').click(function () {
		$('.overlay').removeClass("active");
		$('.modal').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-mobile-menu').removeClass("active");
		$('.modal-mobile-menu-lk').removeClass("active");
		$('.body').removeClass("body-fixed");
	});
	
	// Кнопка "Да, выйти" - редирект на wp_logout_url уже настроен в HTML
	
	// Инициализация fancybox для видео
	/* jQuery(document).ready(function($) {
		// Настройки для fancybox
		$('[data-fancybox="videos"]').fancybox({
		type: 'iframe',
		iframe: {
		preload: false,
		css: {
		width: '80%',
		height: '80%'
		}
		},
		beforeShow: function() {
		// Для MP4 видео используем HTML5 video
		var href = this.src;
		if (href.includes('.mp4') || href.includes('.webm') || href.includes('.ogg')) {
		this.type = 'html';
		this.content = $('<video controls autoplay style="width:100%;height:100%">' +
		'<source src="' + href + '" type="video/mp4">' +
		'Your browser does not support the video tag.' +
		'</video>');
		}
		},
		afterClose: function() {
		// Останавливаем видео при закрытии
		$('video').each(function() {
		this.pause();
		this.currentTime = 0;
		});
		}
		});
	}); */
	
	// Обработка формы подписки
	jQuery(document).ready(function($) {
		if ($.fn.fancybox) {
			$('[data-fancybox^="practice-"]').fancybox({
				loop: true,
				protect: true,
				buttons: [
					'zoom',
					'close'
				]
			});
		}

		$('#subscription-form').on('submit', function(e) {
			e.preventDefault();
			
			var $form = $(this);
			var $email = $form.find('input[type="email"]');
			var $success = $form.next('.form__succes');
			var formAction = $form.attr('action');
			
			// Валидация email
			if (!isValidEmail($email.val())) {
				$email.addClass('error');
				return false;
			}
			
			$email.removeClass('error');
			
			// Если указан action формы, отправляем стандартным способом
			if (formAction && formAction !== '#') {
				$form[0].submit();
				return true;
			}
			
			// AJAX отправка формы
			$.ajax({
				type: 'POST',
				url: yoga_ajax.ajax_url,
				data: {
					action: 'yoga_subscribe',
					email: $email.val(),
					security: yoga_ajax.nonce
				},
				beforeSend: function() {
					$form.find('button').prop('disabled', true);
				},
				success: function(response) {
					if (response.success) {
						// Показываем сообщение об успехе
						$form.closest('.subscription').addClass("succes");
						//$success.fadeIn(300);
						$email.val('');
						
						// Скрываем сообщение через 3 секунды
						/* setTimeout(function() {
							$success.fadeOut(300);
							}, 3000);
							} else {
						alert('Ошибка: ' + response.data); */
					}
				},
				error: function() {
					alert('Произошла ошибка при отправке формы.');
				},
				complete: function() {
					$form.find('button').prop('disabled', false);
				}
			});
			
			return false;
		});
		
		
		// Валидация в реальном времени
		$('input[type="email"]').on('blur', function() {
			if (!isValidEmail($(this).val())) {
				$(this).addClass('error');
				} else {
				$(this).removeClass('error');
			}
		});
	});
	
	document.addEventListener('DOMContentLoaded', function() {
		// Обработка избранного
		/* const favElements = document.querySelectorAll('.praktika-fav');
			
			favElements.forEach(fav => {
			fav.addEventListener('click', function() {
			this.classList.toggle('active');
			// Здесь можно добавить AJAX запрос для сохранения в избранное
			});
		}); */
		
		// Плавная прокрутка только к якорям (#...), без перехвата обычных ссылок.
		document.querySelectorAll('.ref[href^="#"]').forEach(link => {
			link.addEventListener('click', function(e) {
				const targetId = this.getAttribute('href');
				if (!targetId || targetId === '#') {
					return;
				}

				const targetElement = document.querySelector(targetId);
				
				if (targetElement) {
					e.preventDefault();
					targetElement.scrollIntoView({
						behavior: 'smooth',
						block: 'start'
					});
				}
			});
		});
	});
	
	// Обработка формы подписки (DOM уже готов в этом jQuery.ready — второй DOMContentLoaded уже не сработает)
	(function initSubscriptionForms() {
		const subscriptionForms = document.querySelectorAll('.subscription-form');

		document.querySelectorAll('.subscription-form .form-btn').forEach(button => {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const form = this.closest('.subscription-form');
				if (!form) {
					return;
				}
				const emailInput = form.querySelector('input[type="email"]');
				const nonceField = form.querySelector('input[name="subscription_nonce_field"]');
				if (!emailInput || !nonceField) {
					return;
				}
				const nonce = nonceField.value;

				if (!isValidSubscriptionEmail(emailInput.value)) {
					showSubscriptionError('Пожалуйста, введите корректный email (не более 30 символов)');
					return;
				}

				subscribeUser(emailInput.value.trim(), nonce, form);
			});
		});

		subscriptionForms.forEach(form => {
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				const emailInput = this.querySelector('input[type="email"]');
				const nonceField = this.querySelector('input[name="subscription_nonce_field"]');
				if (!emailInput || !nonceField) {
					return;
				}

				if (!isValidSubscriptionEmail(emailInput.value)) {
					showSubscriptionError('Пожалуйста, введите корректный email (не более 30 символов)');
					return;
				}

				subscribeUser(emailInput.value.trim(), nonceField.value, this);
			});
		});
	})();
	
	
	function yogaSubscriptionAjaxMessage(payload, fallback) {
		if (!payload || typeof payload !== 'object') {
			return fallback;
		}
		const nested = payload.data;
		if (nested && typeof nested === 'object' && typeof nested.message === 'string') {
			return nested.message;
		}
		if (typeof payload.message === 'string') {
			return payload.message;
		}
		return fallback;
	}

	function subscribeUser(email, nonce, form) {
		const button = form.querySelector('.form-btn');
		const originalHtml = button.innerHTML;
		
		// Показываем лоадер
		button.innerHTML = '<span class="spinner"></span>';
		button.style.pointerEvents = 'none';
		
		fetch(yoga_ajax.ajax_url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				'action': 'process_subscription',
				'email': email,
				'nonce': nonce,
				'consent': '1',
				'source': form.classList.contains('footer-subscribe') ? 'footer' : 'subscription-section',
				'page_url': window.location.href
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				const msg = yogaSubscriptionAjaxMessage(data, 'Подписка оформлена!');
				showSubscriptionSuccess(msg, form);
				form.reset();
				} else {
				showSubscriptionError(yogaSubscriptionAjaxMessage(data, 'Не удалось оформить подписку.'));
			}
		})
		.catch(error => {
			showSubscriptionError('Ошибка сети. Попробуйте еще раз.');
		})
		.finally(() => {
			// Восстанавливаем кнопку
			button.innerHTML = originalHtml;
			button.style.pointerEvents = 'auto';
		});
	}
	
	function showSubscriptionSuccess(message, form) {
		openSubscriptionSuccessModal();
		const subscription = form ? form.closest('.subscription') : null;
		const successElement = subscription
			? subscription.querySelector('.form__succes')
			: document.querySelector('.section-subscription .form__succes');
		if (subscription) {
			subscription.classList.add('succes');
		}
		if (successElement && message) {
			successElement.textContent = message;
		}
	}

	function openSubscriptionSuccessModal() {
		const modal = document.getElementById('yoga-subscription-success-modal');
		if (!modal) return;
		document.querySelectorAll('.modal.active, .modal-login.active').forEach(item => item.classList.remove('active'));
		modal.classList.add('active');
		modal.setAttribute('aria-hidden', 'false');
		document.querySelector('.overlay')?.classList.add('active');
		document.body.classList.add('body-fixed');
		modal.querySelector('.modal-close')?.focus();
	}

	function setFooterSubscriptionComplete(form) {
		if (!form) return;
		const email = form.querySelector('input[type="email"]');
		const agree = form.querySelector('input[type="checkbox"]');
		const button = form.querySelector('button[type="submit"]');

		form.classList.add('is-subscribed');
		form.setAttribute('aria-label', 'Подписка оформлена');
		if (email) {
			email.value = '';
			email.disabled = true;
		}
		if (agree) {
			agree.checked = true;
			agree.disabled = true;
		}
		if (button) {
			button.disabled = true;
			button.setAttribute('aria-label', 'Подписка оформлена');
		}
	}

	document.querySelectorAll('.footer-subscribe').forEach(form => {
		form.addEventListener('submit', function(event) {
			event.preventDefault();
			const email = this.querySelector('input[type="email"]');
			const nonce = this.querySelector('input[name="subscription_nonce_field"]');
			const agree = this.querySelector('input[type="checkbox"]');
			const button = this.querySelector('button[type="submit"]');
			if (!email || !nonce || !agree || !button) return;
			if (!agree.checked) { showSubscriptionError('Подтвердите согласие на обработку персональных данных.'); return; }
			if (!isValidSubscriptionEmail(email.value)) { showSubscriptionError('Пожалуйста, введите корректный email (не более 30 символов)'); return; }
			button.disabled = true;
			fetch(yoga_ajax.ajax_url, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'process_subscription',email:email.value.trim(),nonce:nonce.value,consent:'1',source:'footer',page_url:window.location.href})})
				.then(response => response.json())
				.then(data => {
					if (!data.success) throw new Error(yogaSubscriptionAjaxMessage(data, 'Не удалось оформить подписку.'));
					this.reset();
					setFooterSubscriptionComplete(this);
					openSubscriptionSuccessModal();
				})
				.catch(error => showSubscriptionError(error.message || 'Ошибка сети. Попробуйте еще раз.'))
				.finally(() => { if (!this.classList.contains('is-subscribed')) button.disabled = false; });
		});
	});
	
	function showSubscriptionError(message) {
		console.error('Subscription error:', message);
		alert(message);
	}
	
	console.log('Contact form handler initialized');
	
	// Проверяем, загружен ли DOM полностью
	if (document.readyState === 'loading') {
		// DOM ещё загружается, ждём события
		document.addEventListener('DOMContentLoaded', function() {
			initContactForm();
		});
		} else {
		// DOM уже загружен, выполняем сразу
		initContactForm();
	}
	
	function initContactForm() {
		console.log('Initializing contact form handlers...');
		
		// Маска для телефона
		const phoneInputs = document.querySelectorAll('.input_phone');
		phoneInputs.forEach(input => {
			input.addEventListener('input', function(e) {
				this.value = this.value.replace(/[^\d+\(\)\s-]/g, '');
			});
		});
		
		// Обработка отправки формы
		const contactForms = document.querySelectorAll('.contacts-form');
		const submitLabels = document.querySelectorAll('label[for="form-questions-submit"]');

		contactForms.forEach(form => {
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				processContactForm(form);
			});
		});
		
		// Клик по кнопке отправки
		submitLabels.forEach(label => {
			label.addEventListener('click', function(e) {
				e.preventDefault();
				const form = this.closest('.contacts-form');
				processContactForm(form);
			});
		});
		
		// Отправка по Enter в textarea
		const textareas = document.querySelectorAll('.contacts-form textarea');
		textareas.forEach(textarea => {
			textarea.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' && !e.shiftKey) {
					e.preventDefault();
					const form = this.closest('.contacts-form');
					processContactForm(form);
				}
			});
		});
		
		console.log('Contact form handlers initialized successfully');
	}
	
	// Обработка формы контактов
	function processContactForm(form) {
		const formData = new FormData(form);
		const name = formData.get('contacts_name');
		const email = formData.get('contacts_email');
		const phone = formData.get('contacts_phone');
		const message = formData.get('contacts_message');
		const nonce = formData.get('contacts_nonce_field');
		
		formData.append('action', 'process_contact_form');
		
		// Валидация
		if (!name || !email || !message) {
			alert('Пожалуйста, заполните все поля');
			return;
		}

		const phoneField = form.querySelector('[name="contacts_phone"]');
		if (phoneField && phoneField.required && (!phone || !String(phone).trim())) {
			alert('Пожалуйста, укажите телефон');
			return;
		}
		
		if (!isValidEmail(email)) {
			alert('Пожалуйста, введите корректный email');
			return;
		}
		
		// Отправка AJAX
		submitContactForm(formData, form);
	}
	
	// AJAX отправка формы контактов
	function submitContactForm(formData, form) {
		const submitControl = form.querySelector('.contacts-form-layout__submit[type="submit"]')
			|| form.querySelector('label[for="form-questions-submit"]');
		if (!submitControl) {
			return;
		}
		const originalHtml = submitControl.innerHTML;
		
		// Показываем лоадер
		submitControl.innerHTML = '<span class="spinner"></span>';
		submitControl.style.pointerEvents = 'none';
		
		fetch(yoga_ajax.ajax_url, {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				showContactSuccess('Сообщение отправлено! Мы свяжемся с вами в ближайшее время.', form);
				form.reset();
				} else {
				const errorMessage = (data && data.data && data.data.message)
					|| data.message
					|| 'Ошибка отправки. Попробуйте еще раз.';
				showContactError(errorMessage);
			}
		})
		.catch(error => {
			showContactError('Ошибка сети. Попробуйте еще раз.');
		})
		.finally(() => {
			submitControl.innerHTML = originalHtml;
			submitControl.style.pointerEvents = 'auto';
		});
	}
	
	function showContactSuccess(message, form) {
		if (form && form.closest('.section-form-questions_contacts')) {
			openContactSuccessModal();
			return;
		}

		openQuestionSuccessModal();
	}

	function openContactSuccessModal() {
		$('.body').addClass("body-fixed");
		$('.overlay').addClass("active");
		$('.modal, .modal-login').removeClass("active").attr('aria-hidden', 'true');
		$('#yoga-contact-success-modal').addClass("active").attr('aria-hidden', 'false');
	}

	function openQuestionSuccessModal() {
		$('.body').addClass("body-fixed");
		$('.overlay').addClass("active");
		$('.modal').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-default_formsucces').addClass("active");
	}
	
	function showContactError(message) {
		alert(message);
	}
	
	jQuery(document).ready(function($) {
		$('.mobile-menu-switches__item').on('click', function() {
			if ($(this).hasClass('mobile-menu-switches__item_unavailable') || $(this).attr('data-unavailable') === '1') {
				return;
			}
			var target = $(this).data('target');
			
			// Активируем переключатель
			$('.mobile-menu-switches__item').removeClass('active');
			$(this).addClass('active');
			
			// Показываем соответствующее меню
			$('.mobile-menu-sub').removeClass('active');
			$('.mobile-menu-sub[data-target="' + target + '"]').addClass('active');
		});
	});
	
	// Обработка формы FAQ
	$(document).on('click', '#faqContactForm label[for="faq-form-submit"]', function(e) {
		e.preventDefault();
		const faqForm = this.closest('form');
		if (faqForm) {
			$(faqForm).trigger('submit');
		}
	});
	
	$(document).on('submit', '#faqContactForm', function(e) {
		e.preventDefault();
		
		const faqForm = this;
		const submitLabel = faqForm.querySelector('label[for="faq-form-submit"]');
		if (!submitLabel) {
			return;
		}
		
		const formData = new FormData(faqForm);
		formData.append('action', 'faq_contact_form');
		
		const name = (formData.get('name') || '').toString().trim();
		const email = (formData.get('email') || '').toString().trim();
		const message = (formData.get('message') || '').toString().trim();
		
		if (!name || !email || !message) {
			alert('Пожалуйста, заполните все поля');
			return;
		}
		
		if (!isValidEmail(email)) {
			alert('Пожалуйста, введите корректный email');
			return;
		}
		
		const originalHtml = submitLabel.innerHTML;
		submitLabel.innerHTML = '<span class="spinner"></span>';
		submitLabel.style.pointerEvents = 'none';
		
		fetch((typeof yoga_ajax !== 'undefined' && yoga_ajax.ajax_url) ? yoga_ajax.ajax_url : '/wp-admin/admin-ajax.php', {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				const redirectUrl = data && data.data ? data.data.redirect_url : '';
				if (redirectUrl) {
					window.location.assign(redirectUrl);
					return;
				}

				openQuestionSuccessModal();
			} else {
				alert((data && data.data && data.data.message) || data.message || 'Ошибка отправки. Попробуйте еще раз.');
			}
		})
		.catch(() => {
			alert('Ошибка сети. Попробуйте еще раз.');
		})
		.finally(() => {
			submitLabel.innerHTML = originalHtml;
			submitLabel.style.pointerEvents = 'auto';
		});
	});
	
	function isValidEmail(email) {
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return emailRegex.test(email);
	}

	/** Форма подписки: до 30 символов и базовый формат email (итог проверяет сервер через is_email). */
	function isValidSubscriptionEmail(email) {
		const trimmed = (email || '').trim();
		if (trimmed.length < 1 || trimmed.length > 30) {
			return false;
		}
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed);
	}

    function getLibraryDefaultTermId() {
        const rawDefaultTerm = parseInt($('.section-library').data('default-term-id'), 10);
        return Number.isNaN(rawDefaultTerm) ? 0 : rawDefaultTerm;
    }

    function getActiveLibraryTermId() {
        const $activeCategory = $('.section-library .form-categories__value span.active');
        if (!$activeCategory.length) {
            return getLibraryDefaultTermId();
        }
        const rawTermId = parseInt($activeCategory.data('target'), 10);
        if (Number.isNaN(rawTermId) || rawTermId <= 0) {
            return getLibraryDefaultTermId();
        }
        return rawTermId;
    }

    function updateLibraryFilterCount() {
		var selectedCount = YogaLibraryFiltersCore.selectedCount();
		$('.section-library .filter-btn__count, .section-kriyi .filter-btn__count')
			.text(selectedCount)
			.toggleClass('active', selectedCount > 0);
	}

    function syncLibraryFilterCheckboxLabels() {
        $('input.library-filter-input').each(function() {
            var id = $(this).attr('id');
            if (!id) return;
            var on = $(this).prop('checked');
            var $mobileRow = $(this).closest('.library-filters-screen__row');
            var $targets = $('[data-filter-input="' + id + '"]');
            if ($mobileRow.length) {
                $targets = $targets.add($mobileRow);
            }
            $targets.toggleClass('active', on).find('.checkbox').toggleClass('active', on);
        });

		$('.section-kriyi .filter input[type="checkbox"]').each(function () {
			var id = this.id;
			if (!id) return;
			var on = Boolean(this.checked);
			var $label = $('.section-kriyi .filter label[for="' + id + '"]');
			$label.toggleClass('active', on).find('.checkbox').toggleClass('active', on);
		});
		$('.section-kriyi .filter-item').removeClass('focused');
		$('.section-kriyi .filter-item__list .checkbox-item.active').closest('.filter-item').addClass('focused');
		$('.section-kriyi .form-reset').toggleClass(
			'active',
			$('.section-kriyi .filter input[type="checkbox"]:checked').length > 0
		);

		updateLibraryFilterCount();
    }

    function setCurrentLibraryFilter(input) {
        var $input = $(input);
        var id = $input.attr('id');
        if (!id) return;

        var $rows = $('.section-library .filter-item__list .checkbox-item, .library-filters-screen__row');
        if ($input.prop('checked')) {
            $rows.removeClass('is-current');
            $('[data-filter-input="' + id + '"]').add($input.closest('.library-filters-screen__row')).addClass('is-current');
        } else {
            $('[data-filter-input="' + id + '"]').add($input.closest('.library-filters-screen__row')).removeClass('is-current');
        }
    }

    function finalizeLibraryFiltersScreenUi() {
        $('.library-filters-screen').removeClass('active library-filters-screen--closing').attr('aria-hidden', 'true');
        $('.section-library .filter-btn, .section-kriyi .filter-btn').removeClass('active');
        $('.overlay').removeClass('active');
        $('.body').removeClass('body-fixed');
    }

    /**
     * @param {boolean} immediate — без анимации (другие модалки, оверлей, ниже lg init).
     */
    function closeLibraryFiltersScreen(immediate) {
        var $screen = $('.library-filters-screen');
        if (!$screen.length) {
            return;
        }

        if (immediate || $(window).width() >= yogaViewportBp.lg) {
            $screen.find('.library-filters-screen__panel').off('transitionend.libraryFiltersPanel');
            finalizeLibraryFiltersScreenUi();
            return;
        }

        if (!$screen.hasClass('active')) {
            if ($screen.hasClass('library-filters-screen--closing')) {
                $screen.find('.library-filters-screen__panel').off('transitionend.libraryFiltersPanel');
                finalizeLibraryFiltersScreenUi();
            }
            return;
        }

        if ($screen.hasClass('library-filters-screen--closing')) {
            return;
        }

        var $panel = $screen.find('.library-filters-screen__panel');
        $screen.addClass('library-filters-screen--closing');

        function onPanelTransitionEnd(e) {
            if (e.target !== $panel[0]) {
                return;
            }
            var pn = e.originalEvent && e.originalEvent.propertyName;
            if (pn && pn !== 'transform') {
                return;
            }
            $panel.off('transitionend.libraryFiltersPanel', onPanelTransitionEnd);
            finalizeLibraryFiltersScreenUi();
        }

        $panel.on('transitionend.libraryFiltersPanel', onPanelTransitionEnd);
        window.setTimeout(function () {
            if ($screen.hasClass('library-filters-screen--closing')) {
                $panel.off('transitionend.libraryFiltersPanel', onPanelTransitionEnd);
                finalizeLibraryFiltersScreenUi();
            }
        }, 450);
    }

    function openLibraryFiltersScreen() {
        var $screen = $('.library-filters-screen');
        $screen.removeClass('library-filters-screen--closing');
        $screen.find('.library-filters-screen__panel').off('transitionend.libraryFiltersPanel');
        $screen.addClass('active').attr('aria-hidden', 'false');
        $('.overlay').addClass('active');
        $('.body').addClass('body-fixed');
        syncLibraryFilterCheckboxLabels();
    }

    function loadLibraryPractices() {
		if (typeof yoga_ajax === 'undefined' || !yoga_ajax.ajax_url) {
			return;
		}
		let data = {
			action: 'filter_practices',
			filters: {},
			search: $('.section-library input[name="s"]').val(),
			term_id: getActiveLibraryTermId()
		};

		data.filters = YogaLibraryFiltersCore.selectedByTaxonomy();
		data.nonce = yoga_ajax.nonce;

		YogaLibraryFiltersCore.request('practices', {
			url: yoga_ajax.ajax_url,
			type: 'POST',
			data: data,
			success: function(response) {
				$('.section-library .library').html(response);
			}
		});
	}

    function requestLibrarySuggestions() {
        const query = $('.section-library .form-search .input').val().trim();
        const termId = getActiveLibraryTermId();
        const $search = $('.section-library .form-search');
        const $list = $search.find('.form-search-list');

        if (query.length < 2) {
            $list.removeClass('active').empty();
            return;
        }

        YogaLibraryFiltersCore.request('suggestions', {
            url: yoga_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'search_practices_suggest',
                nonce: yoga_ajax.nonce,
                query: query,
                term_id: termId
            },
            success: function(response) {
                if (!(response && response.success && response.data && Array.isArray(response.data.items))) {
                    $list.html('<div class="form-search-list__item form-search-list__item_empty"><span>Ничего не найдено</span></div>').addClass('active');
                    $search.addClass('active');
                    return;
                }

                const items = response.data.items;
                if (!items.length) {
                    $list.html('<div class="form-search-list__item form-search-list__item_empty"><span>Ничего не найдено</span></div>').addClass('active');
                    $search.addClass('active');
                    return;
                }

                const html = items.map(function(item) {
                    return '<div class="form-search-list__item" data-url="' + escapeHtml(item.url || '') + '">' +
                        '<span>' + highlightSuggestionMatch(item.title || '', query) + '</span>' +
                    '</div>';
                }).join('');
                $list.html(html).addClass('active');
                $search.addClass('active');
            },
            error: function(xhr, status) {
                if (status === 'abort') return;
                $list.html('<div class="form-search-list__item form-search-list__item_empty"><span>Ничего не найдено</span></div>').addClass('active');
                $search.addClass('active');
            }
        });
    }
	
	/** Заголовок h2 в #section-ways синхронизировать с выбранной категорией practice-type (AJAX без перезагрузки). */
	function syncPracticeTaxonomyPageHeading() {
		var $active = $('.section-library .form-categories__value span.active').first();
		if (!$active.length) {
			$active = $('.section-kriyi .form-categories__value span.active').first();
		}
		if (!$active.length) {
			return;
		}
		var title = $.trim($active.text());
		if (!title) {
			return;
		}
		var $h = $('#section-ways .ways-heading');
		if ($h.length) {
			$h.text(title);
		}
	}

	// Функция для установки активного элемента по term ID
	function setActiveLibraryTerm(termId) {
		// Убираем активный класс у всех элементов
		$('.section-library .form-categories__value span, .section-library .form-cat-list__item').removeClass('active');
		
		// Добавляем активный класс элементам с соответствующим data-target
		$(`.section-library .form-categories__value span[data-target="${termId}"]`).addClass('active');
		$(`.section-library .form-cat-list__item[data-target="${termId}"]`).addClass('active');
		syncPracticeTaxonomyPageHeading();
	}
	
	// Выбор подкатегории в поиске библиотеки → переход на URL архива термина (список практик),
	// а не подмена сетки AJAX тем же видом карточек library-item.
	$(document).on('click', '.section-library .form-cat-list__item', function(e) {
		e.preventDefault();
		var $item = $(this);
		var rawLink = ($item.attr('data-link') || '').trim();
		if (!rawLink) {
			var $a = $item.find('a[href]').first();
			rawLink = ($a.attr('href') || '').trim();
		}
		if (rawLink && rawLink !== '#') {
			try {
				var targetUrl = new URL(rawLink, window.location.href);
				var cur = new URL(window.location.href);
				var normPath = function (pathname) {
					var p = pathname.replace(/\/+$/, '');
					return p === '' ? '/' : p;
				};
				var same = normPath(targetUrl.pathname) === normPath(cur.pathname) && targetUrl.search === cur.search;
				if (same) {
					var $search = $item.closest('.form-search');
					$search.removeClass('active');
					$search.find('.form-categories').removeClass('active');
					$search.find('.form-cat-list').removeClass('active');
					setActiveLibraryTerm($item.data('target'));
					return;
				}
			} catch (ignore) {}
			window.location.href = rawLink;
			return;
		}
		setActiveLibraryTerm($item.data('target'));
		loadLibraryPractices();
		requestLibrarySuggestions();
	});
	
	// Также можно вызвать при загрузке страницы для установки начального активного элемента
	$(document).ready(function() {
		const initialActiveTerm = $('.section-library .form-categories__value span.active').data('target') || 
		$('.section-library .form-cat-list__item.active').data('target') ||
		getLibraryDefaultTermId();
		if (initialActiveTerm) {
			setActiveLibraryTerm(initialActiveTerm);
		} else {
			syncPracticeTaxonomyPageHeading();
		}
		syncLibraryFilterCheckboxLabels();
		if ($('.section-library').length && YogaLibraryFiltersCore.selectedCount() > 0) {
			loadLibraryPractices();
		}
	});
	
	// поиск
	$('.section-library #practice-filter-form').on('submit', function(e) {
		e.preventDefault();
		loadLibraryPractices();
	});

    $(document).on('input', '.section-library .form-search .input', function() {
        YogaLibraryFiltersCore.debounce('suggestions', requestLibrarySuggestions, 300);
    });

    $(document).on('click', '.section-library .form-search-list__item[data-url]', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        if (url) {
            window.location.href = url;
        }
    });
	
	// Мобильный оверлей фильтров: тап по строке — программный toggle (делегирование на .library-filters-screen__row + проверка .active)
	(function ($) {
		var touchStart = { x: 0, y: 0, row: null };
		var suppressRowClickUntil = 0;

		function screenIsActive(row) {
			var screen = row.closest('#library-filters-screen');
			return !!(screen && screen.classList.contains('active'));
		}

		function activateLibraryFilterRow(row) {
			if (!screenIsActive(row)) return;
			var inp = row.querySelector('input.library-filter-input');
			if (!inp) return;
			$('.form-categories').removeClass('active');
			$('.form-search').removeClass('active');
			$('.form-cat-list').removeClass('active');
			$('.form-search-list').removeClass('active');
			YogaLibraryFiltersCore.toggle(inp);
			$(inp).trigger('change');
		}

		$(document).on('touchstart', '.library-filters-screen__row', function (e) {
			if (!screenIsActive(this)) return;
			if ($(e.target).closest('button, a').length) {
				touchStart.row = null;
				return;
			}
			var o = e.originalEvent;
			if (!o || !o.touches || !o.touches[0]) return;
			touchStart.x = o.touches[0].clientX;
			touchStart.y = o.touches[0].clientY;
			touchStart.row = this;
		});

		$(document).on('touchend', '.library-filters-screen__row', function (e) {
			if (!screenIsActive(this)) return;
			if ($(e.target).closest('button, a').length) return;
			if (touchStart.row !== this) {
				if (touchStart.row) {
					touchStart.row = null;
				}
				return;
			}
			var o = e.originalEvent;
			var t = o && o.changedTouches && o.changedTouches[0];
			var x0 = touchStart.x;
			var y0 = touchStart.y;
			touchStart.row = null;
			if (!t) return;
			var dx = t.clientX - x0;
			var dy = t.clientY - y0;
			if (dx * dx + dy * dy > 120) return;
			if (e.cancelable) e.preventDefault();
			suppressRowClickUntil = Date.now() + 650;
			activateLibraryFilterRow(this);
		});

		$(document).on('click', '.library-filters-screen__row', function (e) {
			if (!screenIsActive(this)) return;
			if (Date.now() < suppressRowClickUntil) return;
			if ($(e.target).closest('button, a').length) return;
			activateLibraryFilterRow(this);
		});
	})(jQuery);

	$(document).on('change', 'input.library-filter-input', function() {
		YogaLibraryFiltersCore.set(this, this.checked);
		syncLibraryFilterCheckboxLabels();
		setCurrentLibraryFilter(this);
		if ($(window).width() < yogaViewportBp.lg && $(this).closest('.library-filters-screen.active').length) {
			return;
		}
		loadLibraryPractices();
	});

	$(document).on('click', '.library-filters-screen__close', function() {
		closeLibraryFiltersScreen();
	});

	$(document).on('click', '.library-filters-screen__backdrop', function() {
		closeLibraryFiltersScreen();
	});

	$(document).on('click', '.js-library-filters-apply', function() {
		if ($(window).width() < yogaViewportBp.lg && $('.section-kriyi').length) {
			loadPractices();
		} else {
			loadLibraryPractices();
		}
		closeLibraryFiltersScreen();
	});

	$(document).on('click', '.js-library-filters-reset', function() {
		YogaLibraryFiltersCore.clear();
		$('.section-library .filter-item__list .checkbox-item, .library-filters-screen__row').removeClass('is-current');
		syncLibraryFilterCheckboxLabels();
		if (!$(this).closest('.library-filters-screen.active').length && !$('.section-kriyi').length) {
			loadLibraryPractices();
		}
	});

    function getActivePracticeTermId() {
        const $activeCategory = $('.section-kriyi .form-categories__value span.active');
        if (!$activeCategory.length) {
            return 0;
        }

        const rawTermId = parseInt($activeCategory.data('target'), 10);
        return Number.isNaN(rawTermId) ? 0 : rawTermId;
    }

    // Функция загрузки практик
    function loadPractices() {
        // Показываем индикатор загрузки
        $('.kriyi__items').addClass('loading');
        
        let data = {
            action: 'filter_practices_kriyi',
            nonce: yoga_ajax.nonce,
            filters: {},
            search: $('.section-kriyi .input').val(),
            term_id: getActivePracticeTermId()
		};
		
        // Собираем чекбоксы
		if ($(window).width() < yogaViewportBp.lg) {
			data.filters = YogaLibraryFiltersCore.selectedByTaxonomy();
		} else {
			$('.section-kriyi .filter input[type=checkbox]:checked').each(function() {
				let taxonomy = $(this).attr('name');
				if (!data.filters[taxonomy]) data.filters[taxonomy] = [];
				data.filters[taxonomy].push($(this).val());
			});
		}
		
        YogaLibraryFiltersCore.request('practices', {
            url: yoga_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('.kriyi__items').html(response.data.html);
                    
                    // Показываем/скрываем кнопку "Показать еще"
                    if (response.data.count > 10) {
                        $('.section-kriyi .kriyi > .btn').show();
						} else {
                        $('.section-kriyi .kriyi > .btn').hide();
					}
					} else {
                    console.error('Ошибка загрузки данных');
				}
                $('.kriyi__items').removeClass('loading');
			},
            error: function() {
                console.error('Ошибка AJAX запроса');
                $('.kriyi__items').removeClass('loading');
			}
		});
	}

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeRegExp(text) {
        return String(text || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlightSuggestionMatch(text, query) {
        const source = String(text || '');
        const cleanQuery = String(query || '').trim();
        if (!cleanQuery) {
            return escapeHtml(source);
        }

        const regex = new RegExp(escapeRegExp(cleanQuery), 'ig');
        let result = '';
        let lastIndex = 0;
        let match;

        while ((match = regex.exec(source)) !== null) {
            const start = match.index;
            const end = start + match[0].length;
            result += escapeHtml(source.slice(lastIndex, start));
            result += '<b>' + escapeHtml(source.slice(start, end)) + '</b>';
            lastIndex = end;

            if (match[0].length === 0) {
                regex.lastIndex += 1;
            }
        }

        result += escapeHtml(source.slice(lastIndex));
        return result;
    }

    function renderPracticeSuggestions(items, query) {
        const $search = $('.section-kriyi .form-search');
        const $list = $search.find('.form-search-list');
        const cleanQuery = String(query || '').trim();

        if (cleanQuery.length < 2) {
            $list.removeClass('active').empty();
            return;
        }

        if (!Array.isArray(items) || !items.length) {
            $list
                .html('<div class="form-search-list__item form-search-list__item_empty"><span>Ничего не найдено</span></div>')
                .addClass('active');
            $search.addClass('active');
            return;
        }

        const html = items.map(function(item) {
            return '<div class="form-search-list__item" data-url="' + escapeHtml(item.url || '') + '" data-title="' + escapeHtml(item.title || '') + '">' +
                '<span>' + highlightSuggestionMatch(item.title || '', cleanQuery) + '</span>' +
            '</div>';
        }).join('');

        $list.html(html).addClass('active');
        $search.addClass('active');
    }

    function requestPracticeSuggestions() {
        const query = $('.section-kriyi .input').val().trim();
        const termId = getActivePracticeTermId();

        if (query.length < 2) {
            renderPracticeSuggestions([], '');
            return;
        }

        YogaLibraryFiltersCore.request('suggestions', {
            url: yoga_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'search_practices_suggest',
                nonce: yoga_ajax.nonce,
                query: query,
                term_id: termId
            },
            success: function(response) {
                if (response && response.success && response.data) {
                    renderPracticeSuggestions(response.data.items || [], query);
                } else {
                    renderPracticeSuggestions([], query);
                }
            },
            error: function(xhr, status) {
                if (status === 'abort') return;
                renderPracticeSuggestions([], query);
            }
        });
    }
	
    // Функция для установки активного элемента по term ID
    function setActiveTerm(termId) {
        // Убираем активный класс у всех элементов
        $('.section-kriyi .form-categories__value span, .section-kriyi .form-cat-list__item').removeClass('active');
        
        // Добавляем активный класс элементам с соответствующим data-target
        $(`.section-kriyi .form-categories__value span[data-target="${termId}"]`).addClass('active');
        $(`.section-kriyi .form-cat-list__item[data-target="${termId}"]`).addClass('active');
        syncPracticeTaxonomyPageHeading();
	}
	
    // Обработчики кликов по категориям: переход на канонический URL архива (роутинг), иначе остаёмся на странице с AJAX.
    $(document).on('click', '.section-kriyi .form-categories__value span, .section-kriyi .form-cat-list__item', function(e) {
        e.preventDefault();
        var $el = $(this);
        var rawLink = ($el.attr('data-link') || '').trim();
        if (rawLink && rawLink !== '#') {
            try {
                var targetUrl = new URL(rawLink, window.location.href);
                var cur = new URL(window.location.href);
                var normPath = function (pathname) {
                    var p = pathname.replace(/\/+$/, '');
                    return p === '' ? '/' : p;
                };
                var same = normPath(targetUrl.pathname) === normPath(cur.pathname) && targetUrl.search === cur.search;
                if (same) {
                    var $search = $el.closest('.form-search');
                    $search.removeClass('active');
                    $search.find('.form-categories').removeClass('active');
                    $search.find('.form-cat-list').removeClass('active');
                    setActiveTerm($el.data('target'));
                    return;
                }
            } catch (ignore) {}
            window.location.href = rawLink;
            return;
        }
        setActiveTerm($el.data('target'));
        loadPractices();
        requestPracticeSuggestions();
	});
	
    // Поиск
    $('.section-kriyi form').on('submit', function(e) {
        e.preventDefault();
        loadPractices();
	});

    $(document).on('input', '.section-kriyi .form-search .input', function() {
        YogaLibraryFiltersCore.debounce('suggestions', requestPracticeSuggestions, 300);
    });

    $(document).on('click', '.section-kriyi .form-search-list__item[data-url]', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        if (url) {
            window.location.href = url;
        }
    });
	
    // Чекбоксы
    $('.section-kriyi .filter input[type=checkbox]').on('change', function() {
		YogaLibraryFiltersCore.set(this, this.checked);
		syncLibraryFilterCheckboxLabels();
        loadPractices();
	});
	
    // Кнопка "Показать еще/Свернуть"
    $('.section-kriyi .btn').on('click', function() {
        $(this).toggleClass('active');
        $('.section-kriyi .kriyi-item.hidden').toggleClass('hidden');
        
        // Меняем текст кнопки
        if ($(this).hasClass('active')) {
            $(this).find('span:first').text('Свернуть');
            $(this).find('span:last').text('Свернуть');
			} else {
            $(this).find('span:first').text('Показать еще');
            $(this).find('span:last').text('Показать еще');
		}
	});

	function applyPracticeFiltersFromUrl() {
		if (typeof URLSearchParams === 'undefined') {
			return false;
		}

		const params = new URLSearchParams(window.location.search || '');
		const filterValues = [];

		['practice-difficulty', 'practice-difficulty[]', 'difficulty'].forEach(function(key) {
			params.getAll(key).forEach(function(rawValue) {
				String(rawValue || '')
					.split(',')
					.map(function(part) { return part.trim(); })
					.filter(Boolean)
					.forEach(function(value) {
						filterValues.push(value);
					});
			});
		});

		if (!filterValues.length) {
			return false;
		}

		let applied = false;
		const uniqueValues = Array.from(new Set(filterValues));

		uniqueValues.forEach(function(value) {
			let $checkbox = $('.section-kriyi .filter input[type=checkbox][name="practice-difficulty"]').filter(function() {
				return String($(this).val()) === value;
			});

			if (!$checkbox.length) {
				const $labelByKey = $('.section-kriyi .filter label.checkbox-item[data-level-key="' + value + '"]').first();
				if ($labelByKey.length) {
					$checkbox = $('.section-kriyi .filter input[type=checkbox][name="practice-difficulty"]#' + $labelByKey.attr('for'));
				}
			}

			if (!$checkbox.length) {
				return;
			}

			YogaLibraryFiltersCore.set($checkbox[0], true);
			const checkboxId = $checkbox.attr('id');
			const $label = checkboxId
				? $('.section-kriyi .filter label[for="' + checkboxId + '"]')
				: $checkbox.next('.checkbox-item');
			$label.addClass('active');
			$label.find('.checkbox').addClass('active');
			applied = true;
		});

		if (applied) {
			$('.section-kriyi .form-reset').addClass('active');
			$('.section-kriyi .filter-item').removeClass('focused');
			$('.section-kriyi .filter-item__list .checkbox-item.active').closest('.filter-item').addClass('focused');
		}

		return applied;
	}
	
    // Добавление в избранное
    /* $(document).on('click', '.section-kriyi .kriya-fav', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).find('img').toggleClass('active');
        // Здесь можно добавить AJAX запрос для сохранения в избранное
	}); */
	
    // Инициализация при загрузке страницы
    const initialActiveTerm = $('.section-kriyi .form-categories__value span.active').data('target') || 
	$('.section-kriyi .form-cat-list__item.active').data('target');
    if (initialActiveTerm) {
        setActiveTerm(initialActiveTerm);
	}

	const hasUrlPracticeFilters = applyPracticeFiltersFromUrl();
	updateLibraryFilterCount();
	if (hasUrlPracticeFilters || YogaLibraryFiltersCore.selectedCount() > 0) {
		loadPractices();
	}
	
	
});

jQuery(document).ready(function($) {
	var LK_LAST_SLIDE_KEY = 'yoga_lk_last_slide';

	function persistLkSlide(target) {
		var value = String(target || '').trim();
		if (value === '') {
			return;
		}
		try {
			window.sessionStorage.setItem(LK_LAST_SLIDE_KEY, value);
		} catch (e) {
			// ignore storage errors
		}
	}

	function readPersistedLkSlide() {
		try {
			return String(window.sessionStorage.getItem(LK_LAST_SLIDE_KEY) || '').trim();
		} catch (e) {
			return '';
		}
	}

	function syncLkSectionUrl(target) {
		if (!$('#section-lk').length || typeof yoga_ajax === 'undefined' || !yoga_ajax.lk_section_by_target || !window.history || !window.history.replaceState) {
			return;
		}
		var section = yoga_ajax.lk_section_by_target[String(target || '')];
		if (!section) {
			return;
		}
		var url = new URL(window.location.href);
		url.searchParams.set('lk-section', section);
		url.searchParams.delete('lk-slide');
		url.hash = '';
		window.history.replaceState({}, '', url.toString());
	}

	function cleanNotificationActionUrl() {
		if (!window.history || !window.history.replaceState) {
			return;
		}
		var url = new URL(window.location.href);
		if (!url.searchParams.has('read-notification') && !url.searchParams.has('_yoga-notification-nonce')) {
			return;
		}
		url.searchParams.delete('read-notification');
		url.searchParams.delete('_yoga-notification-nonce');
		window.history.replaceState({}, '', url.toString());
	}

	cleanNotificationActionUrl();

	function getCurrentLkSlideTarget() {
		var activeSlide = String($('.lk-slide.active').attr('data-target') || '').trim();
		if (activeSlide !== '') {
			return activeSlide;
		}
		return String($('.sidebar-menu__item.active').attr('data-target') || '').trim();
	}

	function updateHeaderNotificationBell(unreadCount) {
		var $bell = $('.header-lk .notification-icon_header');
		if (!$bell.length) {
			return;
		}
		var $bellIcon = $bell.find('.notification-icon__img use');
		var currentIconHref = String($bellIcon.attr('href') || '');
		var spriteUrl = typeof yoga_ajax !== 'undefined' && yoga_ajax.sprite_url
			? String(yoga_ajax.sprite_url).replace(/#.*$/, '')
			: currentIconHref.replace(/#.*$/, '');
		var $count = $bell.find('.notification-icon__count');
		if (unreadCount > 0) {
			$bell.addClass('notification-icon_header--has-notifications');
			$bellIcon.attr('href', spriteUrl + '#notification-bell-filled-icon');
			if ($count.length) {
				$count.text(unreadCount);
			} else {
				$bell.append($('<span class="notification-icon__count" aria-hidden="true"></span>').text(unreadCount));
			}
			return;
		}
		$bell.removeClass('notification-icon_header--has-notifications');
		$count.remove();
		$bellIcon.attr('href', spriteUrl + '#notification-bell-icon');
	}

	function renderHeaderNotificationsEmpty() {
		var $popup = $('#header-notifications-popup');
		if (!$popup.length) {
			return;
		}
		$popup.addClass('lk-notifications-popup--empty');
		$popup.find('.lk-notifications-popup__read-all, .lk-notifications-popup__list, .lk-notifications-popup__all').remove();
		if (!$popup.find('.lk-notifications-popup__empty').length) {
			var spriteUrl = (typeof yoga_ajax !== 'undefined' && yoga_ajax.sprite_url) ? yoga_ajax.sprite_url : '';
			$popup.append(
				'<div class="lk-notifications-popup__empty">' +
					'<span class="lk-notifications-popup__empty-icon"><svg aria-hidden="true"><use href="' + spriteUrl + '#notification-bell-icon"></use></svg></span>' +
					'<strong>Здесь пока ничего нет</strong>' +
					'<span>Здесь появятся уведомления</span>' +
				'</div>'
			);
		}
	}

	function renderHeaderNotificationsRead() {
		var $popup = $('#header-notifications-popup');
		if (!$popup.length) {
			return;
		}
		$popup.find('.lk-notifications-popup__item--unread').removeClass('lk-notifications-popup__item--unread');
		$popup.find('.lk-notifications-popup__unread-dot').remove();
		$popup.find('.lk-notifications-popup__read-all').remove();
	}

	function renderLkNotificationsEmpty() {
		var $list = $('.lk-notifications-list');
		if ($list.length) {
			$list.find('.lk-notification--unread').removeClass('lk-notification--unread');
			$list.find('.lk-notification__meta i').remove();
		}
		$('.lk-notifications-page__read-all').remove();
	}

	function updateNotificationMenuCount(target, count) {
		var $items = $('.sidebar-menu__item[data-target="' + target + '"]');
		$items.each(function() {
			var $item = $(this);
			var $count = $item.find('.sidebar-menu__count');
			if (count <= 0) {
				$count.remove();
				return;
			}
			if ($count.length) {
				$count.text(count);
			} else {
				$item.append($('<span class="sidebar-menu__count" aria-hidden="true"></span>').text(count));
			}
		});
	}

	function applyNotificationReadCounts(data) {
		var unreadCount = Number(data.unread_count || 0);
		var questionCount = Number(data.unread_question_answers_count || 0);
		updateHeaderNotificationBell(unreadCount);
		updateNotificationMenuCount('8', unreadCount);
		updateNotificationMenuCount('5', questionCount);
		if (unreadCount === 0) {
			renderHeaderNotificationsRead();
			renderLkNotificationsEmpty();
		}
	}

	function navigateAfterNotificationRead(href) {
		if (!href || href === '#') {
			return;
		}

		var targetUrl;
		try {
			targetUrl = new URL(href, window.location.href);
		} catch (error) {
			window.location.href = href;
			return;
		}

		var section = targetUrl.searchParams.get('lk-section');
		var sectionByTarget = typeof yoga_ajax !== 'undefined' ? yoga_ajax.lk_section_by_target : null;
		if (targetUrl.origin === window.location.origin && section && sectionByTarget && $('#section-lk').length) {
			var target = '';
			$.each(sectionByTarget, function(candidateTarget, candidateSection) {
				if (String(candidateSection) === section) {
					target = String(candidateTarget);
					return false;
				}
			});
			if (target !== '') {
				switchLkSlide(target);
				$('.notification-icon').removeClass('active').attr('aria-expanded', 'false');
				$('.lk-notifications-popup').attr('aria-hidden', 'true');
				return;
			}
		}

		window.location.href = targetUrl.toString();
	}

	function markAllNotificationsRead($button) {
		if (typeof yoga_ajax === 'undefined' || !yoga_ajax.user_logged_in) {
			return;
		}
		$button.prop('disabled', true);
		$.post(yoga_ajax.ajax_url, {
			action: 'yoga_mark_question_answer_notifications_read',
			nonce: yoga_ajax.nonce,
			mark_all: 1
		}).done(function(response) {
			if (!response || !response.success) {
				return;
			}
			$('.lk-notification--unread').removeClass('lk-notification--unread').find('.lk-notification__meta i').remove();
			$('.lk-notifications-popup__unread-dot').remove();
			applyNotificationReadCounts(response.data || {});
		}).always(function() {
			$button.prop('disabled', false);
		});
	}

	$(document).on('click', '.lk-slide--notifications .lk-notifications-page__read-all', function() {
		markAllNotificationsRead($(this));
	});
	$('.lk-notifications-popup').on('click', '.lk-notifications-page__read-all', function() {
		markAllNotificationsRead($(this));
	});

	function markNotificationRead(event) {
		var $notification = $(this);
		var notificationId = String($notification.data('notification-id') || '');
		var href = $notification.attr('href') || '';
		var isUnread = $notification.hasClass('lk-notification--unread') || $notification.hasClass('lk-notifications-popup__item--unread');

		if (!isUnread || !notificationId || typeof yoga_ajax === 'undefined' || !yoga_ajax.user_logged_in) {
			return;
		}

		event.preventDefault();
		$.post(yoga_ajax.ajax_url, {
			action: 'yoga_mark_question_answer_notifications_read',
			nonce: yoga_ajax.nonce,
			notification_id: notificationId
		}).done(function(response) {
			if (!response || !response.success) {
				return;
			}
			var $copies = $('.lk-notification[data-notification-id="' + notificationId + '"]');
			$copies.removeClass('lk-notification--unread lk-notifications-popup__item--unread');
			$copies.find('.lk-notification__meta i, .lk-notifications-popup__unread-dot').remove();
			applyNotificationReadCounts(response.data || {});
		}).always(function() {
			navigateAfterNotificationRead(href);
		});
	}

	$(document).on('click', '.lk-slide--notifications .lk-notification[data-notification-id]', markNotificationRead);
	$('.lk-notifications-popup').on('click', '.lk-notification[data-notification-id]', markNotificationRead);

	$(document).on('click', '.notification-settings__back', function() { switchLkSlide('8'); });
	$(document).on('click', '.lk-notifications-page__settings', function() { switchLkSlide('9'); });
	$(document).on('click', '.notification-toggle[data-preference-key]', function() {
		var $toggle = $(this);
		var wasOn = $toggle.hasClass('is-on');
		var isOn = !wasOn;
		var key = $toggle.data('preference-key');

		if (!key || typeof yoga_ajax === 'undefined' || $toggle.prop('disabled')) {
			return;
		}

		$toggle
			.toggleClass('is-on', isOn)
			.attr('aria-pressed', isOn ? 'true' : 'false')
			.attr('aria-busy', 'true')
			.prop('disabled', true);

		$.post(yoga_ajax.ajax_url, {
			action: 'yoga_save_notification_preference',
			nonce: yoga_ajax.nonce,
			key: key,
			enabled: isOn ? 1 : 0
		}).done(function(response) {
			if (!response || response.success !== true) {
				$toggle.toggleClass('is-on', wasOn).attr('aria-pressed', wasOn ? 'true' : 'false');
			}
		}).fail(function() {
			$toggle.toggleClass('is-on', wasOn).attr('aria-pressed', wasOn ? 'true' : 'false');
		}).always(function() {
			$toggle.removeAttr('aria-busy').prop('disabled', false);
		});
	});

    function switchLkSlide(target) {
        var normalizedTarget = String(target || '').trim();
        if (normalizedTarget === '') {
            return;
        }

        $('.lk-slide').removeClass('active');
        $('.lk-slide[data-target="' + normalizedTarget + '"]').addClass('active');
        $('.sidebar-menu__item').removeClass('active');
        $('.sidebar-menu__item[data-target="' + normalizedTarget + '"]').addClass('active');
		persistLkSlide(normalizedTarget);
		syncLkSectionUrl(normalizedTarget);
    }

    function applyLkDeepLinkHash() {
        var raw = window.location.hash.replace(/^#/, '');
        if (raw === 'lk-slide-favorites') {
            switchLkSlide('3');
			return true;
        } else if (raw === 'lk-slide-settings') {
            switchLkSlide('6');
			return true;
		} else if (raw === 'lk-slide-notifications') {
			switchLkSlide('8');
			return true;
		} else if (raw === 'lk-slide-questions') {
			switchLkSlide('5');
			return true;
		} else if (raw === 'lk-slide-notification-settings') {
			switchLkSlide('9');
			return true;
        }
		return false;
    }

	var $lkSection = $('#section-lk');
	if ($lkSection.length) {
		var serverRouted = $lkSection.attr('data-server-routed') === '1';
		if (serverRouted) {
			persistLkSlide($lkSection.attr('data-initial-target'));
		} else {
			var hashApplied = applyLkDeepLinkHash();
			if (!hashApplied) {
				var persistedTarget = readPersistedLkSlide();
				if (persistedTarget !== '') {
					switchLkSlide(persistedTarget);
				}
			}
		}
		$(window).on('hashchange', applyLkDeepLinkHash);
	} else if (window.history && window.history.replaceState) {
		var nonLkUrl = new URL(window.location.href);
		if (nonLkUrl.searchParams.has('lk-section')) {
			nonLkUrl.searchParams.delete('lk-section');
			window.history.replaceState({}, '', nonLkUrl.toString());
		}
	}

    // Переключение между слайдами
    $('.sidebar-menu__item').on('click', function() {
        var target = $(this).data('target');
		if (!$('#section-lk').length) {
			var section = typeof yoga_ajax !== 'undefined' && yoga_ajax.lk_section_by_target
				? yoga_ajax.lk_section_by_target[String(target || '')]
				: '';
			var lkPageUrl = typeof yoga_ajax !== 'undefined' ? String(yoga_ajax.lk_page_url || '') : '';
			if (section && lkPageUrl) {
				var destination = new URL(lkPageUrl, window.location.origin);
				destination.searchParams.set('lk-section', section);
				window.location.href = destination.toString();
			}
			return;
		}
        switchLkSlide(target);
	});

    // Переход в "Мои данные" при клике по аватару в шапке ЛК
    $('.body_lk .login-icon_logged').on('click', function(e) {
		e.preventDefault();
        switchLkSlide(1);
    });

	$(window).on('pagehide beforeunload', function () {
		if (!$lkSection.length) {
			return;
		}
		var activeTarget = getCurrentLkSlideTarget();
		if (activeTarget !== '') {
			persistLkSlide(activeTarget);
		}
	});
    
    // Отправка формы через AJAX
	$('#profile-form').on('submit', function(e) {
		e.preventDefault();
		// В начале скрипта добавьте проверку
		console.log('AJAX URL:', yoga_ajax.ajax_url);
		console.log('Nonce:', yoga_ajax.nonce);
		console.log('User logged in:', yoga_ajax.user_logged_in);
		
		var $form = $(this);
		var $submitBtn = $form.find('.lk-form-safe label[for="lk-safe-btn"]').first();
		var $submitInput = $form.find('#lk-safe-btn');
		var $submitText = $submitBtn.children('span').first();
		var originalText = $submitText.text();
		var $notification = $('.lk-form-safe__text');
		
		// Валидация паролей
		var newPassword = $form.find('input[name="new_password"]').val();
		var repeatPassword = $form.find('input[name="repeat_password"]').val();
		
		if (newPassword && newPassword !== repeatPassword) {
			showNotification('Пароли не совпадают', 'error');
			return false;
		}
		
		// Показываем индикатор загрузки
		$submitText.text('Сохранение...');
		$submitInput.prop('disabled', true);
		$submitBtn.attr('aria-busy', 'true');
		
		// Создаем FormData
		var formData = new FormData(this);
		formData.append('action', 'update_user_profile');
		formData.append('nonce', yoga_ajax.nonce);

		// Пустой или неполный телефон (маска) не отправляем на сервер
		var $phoneInput = $form.find('input[name="phone"]');
		if ($phoneInput.length) {
			var phoneVal = $phoneInput.val() || '';
			var phoneDigits = phoneVal.replace(/\D/g, '');
			if (phoneDigits.length < 10) {
				formData.set('phone', '');
			}
		}

		$.ajax({
			url: yoga_ajax.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					if (typeof window.yogaMarkLkProfileClean === 'function') {
						window.yogaMarkLkProfileClean();
					}
					// Если загружен аватар — обновляем страницу (как принято)
					if (response.data && response.data.avatar_url) {
						location.reload();
						return;
					}
					// Показываем уведомление об успехе
					if (typeof window.yogaShowLkSuccessToast === 'function') {
						window.yogaShowLkSuccessToast(response.data);
					} else {
						$notification.addClass('active').text(response.data);
						setTimeout(function() { $notification.removeClass('active'); }, 3000);
					}
					} else {
					showNotification(response.data, 'error');
				}
			},
			error: function(xhr, status, error) {
				console.group('AJAX Error Details');
				console.error('Status:', status);
				console.error('Error:', error);
				console.error('Response:', xhr.responseText);
				console.error('ReadyState:', xhr.readyState);
				console.error('Status Code:', xhr.status);
				console.error('Status Text:', xhr.statusText);
				console.groupEnd();
				
				// Показываем подробное сообщение об ошибке
				var errorMessage = 'Ошибка при сохранении данных';
				
				if (xhr.responseText) {
					try {
						var response = JSON.parse(xhr.responseText);
						if (response.data) {
							errorMessage = response.data;
						}
						} catch (e) {
						errorMessage = 'Ошибка сервера: ' + xhr.status + ' ' + xhr.statusText;
					}
				}
				
				showNotification(errorMessage, 'error');
				
				// Не пытаемся отправлять стандартным способом - убираем эту строку
				// $form.off('submit').submit();
			},
			complete: function() {
				// Восстанавливаем кнопку
				$submitText.text(originalText);
				$submitInput.prop('disabled', false);
				$submitBtn.removeAttr('aria-busy');
			}
		});
	});
	
	// Функция показа уведомлений
	function showNotification(message, type = 'success') {
		$('.practice-notification').remove();

		var $notification = $('<div>', {
			'class': 'practice-notification ' + type,
			'role': 'status',
			'aria-live': 'polite',
			text: message
		});
		$('body').append($notification);

		$notification.hide().fadeIn(300);

		setTimeout(function() {
			$notification.fadeOut(300, function() {
				$(this).remove();
			});
		}, 3000);
	}
	
	// Загрузка аватара
	$(document).on('click', '.photo-input-custom', function(e) {
		e.stopPropagation(); // Останавливаем всплытие
		e.preventDefault(); // Отменяем действие по умолчанию
		
		setTimeout(function() {
			$('#avatar-upload').click();
		}, 50); // Небольшая задержка
	});

	$(document).on('keydown', '.photo-input-custom', function(e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			$(this).trigger('click');
		}
	});
	
	$(document).on('change', '#avatar-upload', function() {
		var file = this.files && this.files[0];
		if (!file) return;
		var $photo = $(this).closest('.photo-input').find('.photo-input-custom__inner-photo');
		if (!$photo.length) return;
		var dataUrl = URL.createObjectURL(file);
		var $img = $photo.find('img');
		if ($img.length && $img[0].src && $img[0].src.startsWith('blob:')) {
			URL.revokeObjectURL($img[0].src);
		}
		$photo.find('img').remove();
		$photo.addClass('has-avatar').append($('<img>', { src: dataUrl, alt: '', class: 'avatar' }));
		var $deleteButton = $photo.find('.photo-input-delete');
		$deleteButton.removeAttr('hidden').attr('data-preview-only', '1');

		var uploadData = new FormData();
		uploadData.append('action', 'upload_user_avatar');
		uploadData.append('nonce', yoga_ajax.nonce);
		uploadData.append('avatar', file);
		$deleteButton.prop('disabled', true).attr('aria-busy', 'true');

		$.ajax({
			url: yoga_ajax.ajax_url,
			type: 'POST',
			data: uploadData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				if (response && response.success && response.data && response.data.avatar_url) {
					var avatarUrl = response.data.avatar_url;
					var $previewImage = $photo.find('img');
					if ($previewImage.length && $previewImage[0].src && $previewImage[0].src.startsWith('blob:')) {
						URL.revokeObjectURL($previewImage[0].src);
					}
					$previewImage.attr('src', avatarUrl);
					$('#avatar-upload').val('');
					$deleteButton.removeAttr('data-preview-only aria-busy').prop('disabled', false);

					var $headerAvatar = $('.login-icon_logged');
					if ($headerAvatar.length) {
						$headerAvatar.find('.login-icon__avatar, .login-icon__initial').remove();
						$headerAvatar.prepend($('<img>', {
							src: avatarUrl,
							alt: '',
							'class': 'login-icon__avatar',
							decoding: 'async'
						}));
					}
					return;
				}
				showNotification((response && response.data) ? response.data : 'Не удалось загрузить аватар', 'error');
			},
			error: function(xhr) {
				var message = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : 'Ошибка загрузки аватара';
				showNotification(message, 'error');
			},
			complete: function(xhr) {
				if (!xhr.responseJSON || !xhr.responseJSON.success) {
					$('#avatar-upload').val('');
					$photo.find('img').remove();
					$photo.removeClass('has-avatar');
					$deleteButton.attr('hidden', 'hidden').removeAttr('data-preview-only aria-busy').prop('disabled', false);
				}
			}
		});
	});
	
	// Удаление аватара
	$(document).on('click', '.photo-input-delete', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var $button = $(this);
		if ($button.attr('data-preview-only') === '1') {
			var $photo = $button.closest('.photo-input-custom__inner-photo');
			var $fileInput = $button.closest('.photo-input').find('#avatar-upload');
			var $preview = $photo.find('img');
			if ($preview.length && $preview[0].src && $preview[0].src.startsWith('blob:')) {
				URL.revokeObjectURL($preview[0].src);
			}
			$fileInput.val('');
			$preview.remove();
			$photo.removeClass('has-avatar');
			$button.attr('hidden', 'hidden').removeAttr('data-preview-only');
			return;
		}
		if ($button.prop('disabled')) return;
		$button.prop('disabled', true).attr('aria-busy', 'true');

		$.ajax({
				url: yoga_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'delete_avatar',
					nonce: yoga_ajax.nonce
				},
				success: function(response) {
					if (response.success) {
						location.reload();
					} else {
						showNotification((response && response.data) ? response.data : 'Не удалось удалить аватар', 'error');
					}
				},
				error: function() {
					showNotification('Ошибка соединения', 'error');
				},
				complete: function() {
					$button.prop('disabled', false).removeAttr('aria-busy');
				}
			});
	});
	
	// Показать/скрыть пароль
	$('.input-password__btn').on('click', function() {
		var $input = $(this).closest('.input-password').find('input');
		var $showBtn = $(this).closest('.input-password').find('.input-password__btn_show');
		var $hideBtn = $(this).closest('.input-password').find('.input-password__btn_hide');
		
		if ($input.attr('type') === 'password') {
			$input.attr('type', 'text');
			$showBtn.removeClass('active');
			$hideBtn.addClass('active');
			} else {
			$input.attr('type', 'password');
			$showBtn.addClass('active');
			$hideBtn.removeClass('active');
		}
	});
	
	
	// Обработка избранного в рекомендациях
	function updateHeaderFavorites(favoritesCount) {
		var count = Math.max(0, parseInt(favoritesCount, 10) || 0);
		var $link = $('.header-lk .header-favorites-link');
		if (!$link.length) {
			return;
		}

		$link
			.toggleClass('header-favorites-link--active', count > 0)
			.attr('aria-label', 'Избранное: ' + count);
		// Иконка подключена из внешнего SVG-спрайта. Сохраняем URL спрайта,
		// иначе после AJAX-обновления остаётся только #id и сердце исчезает.
		$link.find('svg use').each(function() {
			var $use = $(this);
			var currentHref = $use.attr('href') || '';
			var spriteUrl = currentHref.indexOf('#') !== -1
				? currentHref.split('#')[0]
				: (yoga_ajax.sprite_url || '');
			$use.attr('href', spriteUrl + (count > 0 ? '#header-heart-filled' : '#header-heart'));
		});

		var $counter = $link.find('.header-favorites-link__count');
		if (count > 0) {
			if ($counter.length) {
				$counter.text(count);
			} else {
				$link.append($('<span class="header-favorites-link__count" aria-hidden="true"></span>').text(count));
			}
		} else {
			$counter.remove();
		}
	}

	$(document).on('yoga:favorites-updated', function(event, payload) {
		if (payload && payload.favorites_count !== undefined) {
			updateHeaderFavorites(payload.favorites_count);
		}
	});

	function renderLkFavoritesEmpty($content) {
		var spriteUrl = yoga_ajax.sprite_url || '';
		var libraryUrl = yoga_ajax.library_url || yoga_ajax.site_url || '/';
		$content.html(
			'<div class="no-favorites lk-favorites-empty">' +
				'<div class="lk-favorites-empty__message">' +
					'<span class="lk-favorites-empty__icon" aria-hidden="true"><svg><use href="' + spriteUrl + '#noun-heart"></use></svg></span>' +
					'<div class="lk-favorites-empty__text"><h3>Здесь пока ничего нет</h3><p>Здесь появятся крийи, когда вы их добавите в избранное</p></div>' +
				'</div>' +
				'<a class="lk-favorites-empty__button" href="' + libraryUrl + '"><span>В библиотеку практик</span><i aria-hidden="true"><svg><use href="' + spriteUrl + '#footer-arrow-up-right"></use></svg></i></a>' +
			'</div>'
		);
	}

	$(document).on('click', '.lk-favorites-clear', function(e) {
		e.preventDefault();
		var $button = $(this);
		var $slide = $button.closest('#lk-slide-favorites');
		$button.prop('disabled', true);

		$.ajax({
			url: yoga_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'yoga_clear_favorite_practices',
				security: yoga_ajax.nonce
			},
			success: function(response) {
				if (!response || !response.success) {
					showNotification(response && response.data && response.data.message ? response.data.message : 'Не удалось очистить избранное', 'error');
					return;
				}

				$(document).trigger('yoga:favorites-updated', [response.data || { favorites_count: 0 }]);
				$('.fav.active').each(function() {
					var $favorite = $(this).removeClass('active').attr('aria-pressed', 'false').attr('aria-label', 'В избранное');
					$favorite.find('img, svg').toggleClass('active');
				});
				$slide.find('.lk-kriyi').remove();
				renderLkFavoritesEmpty($slide.find('.lk-slide__content'));
				$button.remove();
				showNotification('Избранное очищено');
			},
			error: function(xhr) {
				var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
					? xhr.responseJSON.data.message
					: 'Не удалось очистить избранное';
				showNotification(message, 'error');
			},
			complete: function() {
				$button.prop('disabled', false);
			}
		});
	});

	$(document).on('click', '.fav', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('.practice-notification').remove();
		
		var $this = $(this);
		var practiceId = $this.data('practice-id'); 
		if (!practiceId) {
			return;
		}
		
		$.ajax({
			url: yoga_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'toggle_favorite_practice',
				practice_id: practiceId,
				security: yoga_ajax.nonce
			},
			success: function(response) {
				if (response.success) {
					$(document).trigger('yoga:favorites-updated', [response.data || {}]);
					$this.toggleClass('active');
					$this.find('img, svg').toggleClass('active');
					if ($this.attr('role') === 'button') {
						var pressed = $this.hasClass('active');
						$this.attr('aria-pressed', pressed ? 'true' : 'false');
						$this.attr('aria-label', pressed ? 'Убрать' : 'В избранное');
					}
					$('.practice-notification').remove();
					var favoriteMessage = (response.data && response.data.message) ? response.data.message : 'Избранное обновлено';
					var isLkFavoritesContext = $this.closest('.lk-slide[data-target="3"]').length > 0;
					if (isLkFavoritesContext && favoriteMessage === 'Удалено из избранного') {
						var $card = $this.closest('.kriyi-item');
						var $items = $this.closest('.kriyi__items');
						var $content = $this.closest('.lk-slide__content');
						$card.remove();
						if ($items.length && $items.find('.kriyi-item').length === 0) {
							$content.find('.lk-kriyi').remove();
							$content.closest('#lk-slide-favorites').find('.lk-favorites-clear').remove();
							if ($content.find('.no-favorites').length === 0) {
								renderLkFavoritesEmpty($content);
							}
						}
						showNotification('Удалено из избранного');
						return;
					}
					showNotification(favoriteMessage);
				} else if (response.data && response.data.message) {
					showNotification(response.data.message);
				}
			},
			error: function(xhr) {
				if (xhr.status === 401) {
					showNotification('Для добавления в избранное авторизуйтесь');
				} else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					showNotification(xhr.responseJSON.data.message);
				} else {
					showNotification('Избранное не обновлено');
				}
			}
		});
	});
	
	// Обработка формы вопроса
	$('#question-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $submitBtn = $form.find('.btn');
		var $nativeSubmit = $form.find('[type="submit"]');
		var originalText = $submitBtn.text();
		var restoreSubmitState = function() {
			$submitBtn.text(originalText).removeClass('is-loading');
			$nativeSubmit.prop('disabled', false);
		};
		
		$submitBtn.text('Отправка...').addClass('is-loading');
		$nativeSubmit.prop('disabled', true);
		
		$.ajax({
			url: (typeof yoga_ajax !== 'undefined' ? yoga_ajax.ajax_url : $form.attr('action')),
			type: 'POST',
			dataType: 'json',
			timeout: 20000,
			data: $form.serialize(),
			success: function(response) {
				try {
					if (!response || !response.success) {
						showNotification(response && response.data && response.data.message ? response.data.message : 'Ошибка при отправке вопроса', 'error');
						return;
					}

					if (response.data && typeof response.data.questions_html === 'string') {
						$('.lk-questions').html(response.data.questions_html);
					}

					$('.body').addClass('body-fixed');
					$('.overlay').addClass('active');
					$('.modal').removeClass('active').attr('aria-hidden', 'true');
					$('.modal-default_formsucces').addClass('active').attr('aria-hidden', 'false');
					$form.find('textarea').val('');
				} catch (error) {
					if (window.console && typeof window.console.error === 'function') {
						window.console.error('Question form success handler failed:', error);
					}
					showNotification('Вопрос отправлен, но окно подтверждения не открылось', 'error');
				} finally {
					restoreSubmitState();
				}
			},
			error: function(xhr) {
				try {
					var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
						? xhr.responseJSON.data.message
						: 'Ошибка при отправке вопроса';
					showNotification(message, 'error');
				} finally {
					restoreSubmitState();
				}
			}
		});
	});
	
	// Показать/скрыть дополнительные вопросы (делегирование; скрытый span с absolute не должен перехватывать клик — см. lk.css pointer-events)
	$(document).on('click', '.lk-questions .show-more-questions', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var $wrap = $btn.closest('.lk-questions');
		var $extraItems = $wrap.find('.lk-questions-item_extra');
		var isExpanded = $btn.hasClass('is-expanded');

		if (!isExpanded) {
			$extraItems.stop(true, true).removeClass('hidden').hide().slideDown(300);
			$btn.addClass('is-expanded');
			$btn.find('span').toggleClass('active');
			return;
		}

		$extraItems.stop(true, true).slideUp(300, function() {
			$(this).addClass('hidden').removeAttr('style');
		});
		$btn.removeClass('is-expanded');
		$btn.find('span').toggleClass('active');
	});
	
	// Управление настройками подписки — только пункты с data-target (например «Карты»)
	$('.lk-settings-item_action[data-target]').on('click', function() {
		var target = $(this).data('target');
		if (!target) {
			return;
		}
		$('.lk-settings__slide').removeClass('active');
		$('.lk-settings__slide[data-target="' + target + '"]').addClass('active');
	});
	
	$('.form-back').on('click', function() {
		var target = $(this).data('target');
		$('.lk-settings__slide').removeClass('active');
		$('.lk-settings__slide[data-target="' + target + '"]').addClass('active');
	});
	
	// Показать/скрыть дополнительные статьи
	document.querySelectorAll('.blog-articles__more .btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const hiddenItems = document.querySelectorAll('.blog-article-item.hidden');
			const activeSpan = this.querySelector('.active');
			const inactiveSpan = this.querySelector('span:not(.active)');
			
			hiddenItems.forEach(item => {
				item.classList.toggle('hidden');
			});
			
			activeSpan.classList.remove('active');
			inactiveSpan.classList.add('active');
		});
	});
	
	// Обработка радио-кнопок
	document.querySelectorAll('input[name="category"]').forEach(radio => {
		radio.addEventListener('change', function() {
			document.querySelectorAll('.blog-radios label').forEach(label => {
				label.classList.remove('active');
			});
			this.parentElement.classList.add('active');
			
			// Здесь можно добавить AJAX загрузку постов по категории
		});
	});
	
	// Отправка номера телефона
    $('#phone-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var formData = $form.serialize();
        var phone = $form.find('input[name="phone"]').val();
		
        $.ajax({
            type: 'POST',
            url: yoga_ajax.ajax_url,
            data: formData,
            beforeSend: function() {
                $('.btn span').text('Отправка...');
			},
            success: function(response) {
				console.log(response);
                if (response.success) {
                    // Переход ко второму слайду
                    $form.closest('.modal, .modal-login, .modal-default, .phone-form-wrapper').find('[data-target="1"]').removeClass('active');
                    $form.closest('.modal, .modal-login, .modal-default, .phone-form-wrapper').find('[data-target="2"]').addClass('active');
                    $('#phone-number-display').text('Отправили на номер: ' + phone);
                    $('#verify-phone').val(phone);
                    startTimer();
					} else {
                    alert(response.data);
				}
			}
		});
	});
	
    // Проверка SMS кода
    $('#code-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
		
        $.ajax({
            type: 'POST',
            url: yoga_ajax.ajax_url,
            data: formData,
            beforeSend: function() {
                $('.btn span').text('Проверка...');
			},
            success: function(response) {
                if (response.success) {
                    window.location.reload();
					} else {
                    alert(response.data);
				}
			}
		});
	});
	
    // Повторная отправка кода
    $('#resend-code').on('click', function() {
        $.ajax({
            type: 'POST',
            url: yoga_ajax.ajax_url,
            data: {
                action: 'resend_sms_code',
                phone: $('#verify-phone').val(),
                security: $('#code-form input[name="security"]').val()
			},
            success: function(response) {
                if (response.success) {
                    startTimer();
				}
			}
		});
	});
	
    // Таймер для повторной отправки
    function startTimer() {
        var timeLeft = 60;
        var timer = setInterval(function() {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(timer);
                $('#resend-code').show();
                $('#timer').hide();
				} else {
                $('#timer').text('через ' + ('00:' + (timeLeft < 10 ? '0' : '') + timeLeft));
			}
		}, 1000);
	}
});

jQuery(document).ready(function($) {
    // Отправка основного комментария
    $('#custom-comment-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var formData = $form.serialize();
        
        $.ajax({
            type: 'POST',
            url: yoga_ajax.ajax_url,
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $button.prop('disabled', true).addClass('is-loading');
            },
            success: function(response) {
                if (response.success && response.data && response.data.html) {
                    appendPracticeComment(response.data);
                    $form[0].reset();
                    $form.removeClass('active');
                    $form.find('textarea').css('height', '');
                } else {
                    alert('Ошибка при отправке комментария: ' + practiceCommentError(response));
                }
            },
            error: function() {
                alert('Ошибка соединения');
            },
            complete: function() {
                $button.prop('disabled', false).removeClass('is-loading');
            }
        });
    });

    // Обработчики для кнопок ответа
    $(document).on('click', '.answer-btn', function() {
        if (typeof yoga_ajax === 'undefined' || !yoga_ajax.user_logged_in) {
            alert('Для ответа необходимо авторизоваться');
            return;
        }
        
        var commentId = $(this).closest('.praktika-comment').attr('id').replace('comment-', '');
        toggleReplyForm(commentId);
    });

    // Обработчики для кнопок редактирования
    $(document).on('click', '.your-comm__btn_edit', function() {
        var commentId = $(this).closest('.praktika-comment').attr('id').replace('comment-', '');
        toggleEditForm(commentId);
    });

    // Обработчики для кнопок удаления
    $(document).on('click', '.your-comm__btn_del', function() {
        var commentId = $(this).closest('.praktika-comment').attr('id').replace('comment-', '');
        deleteComment(commentId);
    });

    // Обработчики для кнопок отправки ответов
    $(document).on('click', '.praktika-comment__answer .btn', function() {
        var parentId = $(this).closest('.praktika-comment__answer').attr('id').replace('reply-form-', '');
        submitReply(parentId);
    });

    // Обработчики для кнопки «Обновить» в режиме редактирования
    $(document).on('click', '.praktika-comment-item__edit .btn_comment-update', function() {
        var commentId = $(this).closest('.praktika-comment-item__edit').attr('id').replace('edit-form-', '');
        updateComment(commentId);
    });
});

// Функции для работы с комментариями
function practiceCommentError(response) {
    if (response && typeof response.data === 'string') {
        return response.data;
    }
    if (response && response.data && response.data.message) {
        return response.data.message;
    }
    return 'Неизвестная ошибка';
}

function appendPracticeComment(data) {
    var $items = jQuery('.praktika-comments .comments-items').first();
    var $list = $items.find('.praktika-comments-list').first();
    if (!$list.length) {
        $items.empty();
        $list = jQuery('<div class="praktika-comments-list"></div>').appendTo($items);
    }
    $list.append(data.html).append('<div class="praktika-comment__sub-answers"></div>');
}

function appendPracticeCommentReply(parentId, data) {
    var $parent = jQuery('#comment-' + parentId);
    var $children = $parent.next('.praktika-comment__sub-answers');
    if (!$children.length) {
        $children = jQuery('<div class="praktika-comment__sub-answers"></div>').insertAfter($parent);
    }
    $children.append(data.html).append('<div class="praktika-comment__sub-answers"></div>');
}

function toggleReplyForm(commentId) {
    var $form = jQuery('#reply-form-' + commentId);
    var isOpen = $form.hasClass('active');

    // Скрываем другие открытые формы ответа
    jQuery('.praktika-comment__answer').not($form).removeClass('active').addClass('hidden');

    if (isOpen) {
        $form.removeClass('active').addClass('hidden');
        return;
    }

    $form.removeClass('hidden').addClass('active');
    $form.find('.textarea-resize').trigger('focus');
}

function closeAllPracticeCommentEdits() {
    jQuery('.praktika-comment-item__edit').each(function () {
        var $form = jQuery(this);
        $form.addClass('hidden');
        $form.find('.answer-main_comment-edit').removeClass('active');
        $form.closest('.praktika-comment-item').find('.praktika-comment-item__text').removeClass('hidden');
    });
}

function toggleEditForm(commentId) {
    var $form = jQuery('#edit-form-' + commentId);
    var $item = $form.closest('.praktika-comment-item');
    var $text = $item.find('.praktika-comment-item__text');
    var $main = $form.find('.answer-main_comment-edit');
    var wasHidden = $form.hasClass('hidden');

    jQuery('.praktika-comment-item__edit').each(function () {
        var $o = jQuery(this);
        if (!$o.is($form)) {
            $o.addClass('hidden');
            $o.find('.answer-main_comment-edit').removeClass('active');
            $o.closest('.praktika-comment-item').find('.praktika-comment-item__text').removeClass('hidden');
        }
    });

    if (wasHidden) {
        $form.removeClass('hidden');
        $main.addClass('active');
        $text.addClass('hidden');
        $form.find('.textarea-resize').trigger('focus');
    } else {
        $form.addClass('hidden');
        $main.removeClass('active');
        $text.removeClass('hidden');
    }
}

function submitReply(parentId) {
    if (typeof yoga_ajax !== 'undefined' && !yoga_ajax.user_logged_in) {
        alert('Для ответа необходимо авторизоваться');
        return;
    }

    var $form = jQuery('#reply-form-' + parentId);
    var $button = $form.find('.btn');
    var content = $form.find('textarea').val();
    
    if (!content.trim()) {
        alert('Введите текст ответа');
        return;
    }
    
    var data = {
        action: 'submit_comment_reply',
        parent_id: parentId,
        post_id: yoga_ajax.post_id,
        content: content,
        security: yoga_ajax.nonce
    };
    
    $button.prop('disabled', true).addClass('is-loading');
    jQuery.post(yoga_ajax.ajax_url, data, function(response) {
        if (response.success && response.data && response.data.html) {
            appendPracticeCommentReply(parentId, response.data);
            $form.find('textarea').val('').css('height', '');
            $form.removeClass('active').addClass('hidden');
        } else {
            alert('Ошибка при отправке ответа: ' + practiceCommentError(response));
        }
    }, 'json').fail(function() {
        alert('Ошибка соединения');
    }).always(function() {
        $button.prop('disabled', false).removeClass('is-loading');
    });
}

function updateComment(commentId) {
    var $form = jQuery('#edit-form-' + commentId);
    var $button = $form.find('.btn_comment-update');
    var content = $form.find('textarea').val();
    
    if (!content.trim()) {
        alert('Введите текст комментария');
        return;
    }
    
    var data = {
        action: 'update_comment',
        comment_id: commentId,
        content: content,
        security: yoga_ajax.nonce
    };
    
    $button.prop('disabled', true).addClass('is-loading');
    jQuery.post(yoga_ajax.ajax_url, data, function(response) {
        if (response.success && response.data && response.data.html) {
            jQuery('#comment-' + commentId).replaceWith(response.data.html);
        } else {
            alert('Ошибка при обновлении комментария: ' + practiceCommentError(response));
        }
    }, 'json').fail(function() {
        alert('Ошибка соединения');
    }).always(function() {
        $button.prop('disabled', false).removeClass('is-loading');
    });
}

var pendingCommentDeleteId = null;

function setCommentDeleteModalState($modal, isOpen) {
    $modal.toggleClass('active', isOpen).attr('aria-hidden', isOpen ? 'false' : 'true');
}

function closeCommentDeleteConfirm() {
    pendingCommentDeleteId = null;
    setCommentDeleteModalState(jQuery('#yoga-comment-delete-confirm'), false);
    jQuery('.overlay').removeClass('active');
    jQuery('.body').removeClass('body-fixed');
}

function deleteComment(commentId) {
    var $modal = jQuery('#yoga-comment-delete-confirm');
    if (!$modal.length) {
        return;
    }

    pendingCommentDeleteId = commentId;
    jQuery('.modal, .modal-login').removeClass('active').attr('aria-hidden', 'true');
    setCommentDeleteModalState($modal, true);
    jQuery('.overlay').addClass('active');
    jQuery('.body').addClass('body-fixed');
    $modal.find('.yoga-comment-delete-modal__button_cancel').trigger('focus');
}

jQuery(document).on('click', '.yoga-comment-delete-modal__button_cancel, #yoga-comment-delete-confirm .modal-close', function() {
    closeCommentDeleteConfirm();
});

jQuery(document).on('click', '.yoga-comment-delete-modal__button_confirm', function() {
    if (!pendingCommentDeleteId) {
        return;
    }

    var commentId = pendingCommentDeleteId;
    var $button = jQuery(this);
    $button.prop('disabled', true);

    jQuery.post(yoga_ajax.ajax_url, {
        action: 'delete_comment',
        comment_id: commentId,
        security: yoga_ajax.nonce
    }, function(response) {
        if (!response.success) {
            alert('Ошибка при удалении комментария: ' + (response.data || 'Неизвестная ошибка'));
            return;
        }

        jQuery('#comment-' + commentId).remove();
        pendingCommentDeleteId = null;
        setCommentDeleteModalState(jQuery('#yoga-comment-delete-confirm'), false);
        setCommentDeleteModalState(jQuery('#yoga-comment-delete-success'), true);
        jQuery('#yoga-comment-delete-success .modal-close').trigger('focus');
    }, 'json').fail(function() {
        alert('Ошибка соединения');
    }).always(function() {
        $button.prop('disabled', false);
    });
});

jQuery(document).on('click', '.overlay', function() {
    if (jQuery('#yoga-comment-delete-confirm').hasClass('active')) {
        pendingCommentDeleteId = null;
    }
});

// Закрытие форм при клике вне области
jQuery(document).on('click', function(e) {
    if (!jQuery(e.target).closest('.praktika-comment__answer, .answer-btn').length) {
        jQuery('.praktika-comment__answer').removeClass('active').addClass('hidden');
    }
    
    if (!jQuery(e.target).closest('.praktika-comment-item__edit, .your-comm__btn_edit').length) {
        closeAllPracticeCommentEdits();
    }
});

(function () {
    function applyResponsiveTableLabels() {
        var tables = document.querySelectorAll('.post-main .wp-block-table table');

        tables.forEach(function (table) {
            var headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
                return th.textContent.trim();
            });
            var headerRowFromBody = false;

            if (!headers.length) {
                headers = Array.from(table.querySelectorAll('tbody tr:first-child td')).map(function (td) {
                    return td.textContent.trim();
                });
                headerRowFromBody = headers.length > 0;
            }

            if (!headers.length) return;

            table.querySelectorAll('tbody tr').forEach(function (row, rowIndex) {
                if (headerRowFromBody && rowIndex === 0) {
                    row.classList.add('responsive-table-label-row');
                }
                row.querySelectorAll('td').forEach(function (cell, index) {
                    if (rowIndex === 0 && !table.querySelector('thead')) {
                        cell.setAttribute('data-label', '');
                        return;
                    }

                    cell.setAttribute('data-label', headers[index] || '');
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyResponsiveTableLabels);
    } else {
        applyResponsiveTableLabels();
    }
})();

window.yogaShowLkSuccessToast = function(message) {
    var $toast = jQuery('.lk-form-safe__text').first();
    if (!$toast.length) return;

    window.clearTimeout($toast.data('hideTimer'));
    $toast.text(message || '').addClass('active');
    $toast.data('hideTimer', window.setTimeout(function() {
        $toast.removeClass('active');
    }, 3000));
};

window.yogaQueueLkSuccessToast = function(message) {
    try {
        window.sessionStorage.setItem('yoga_lk_success_toast', message || '');
    } catch (error) {
        window.yogaShowLkSuccessToast(message);
    }
};

jQuery(function() {
    var queuedMessage = '';
    try {
        queuedMessage = window.sessionStorage.getItem('yoga_lk_success_toast') || '';
        window.sessionStorage.removeItem('yoga_lk_success_toast');
    } catch (error) {
        queuedMessage = '';
    }
    if (queuedMessage) window.yogaShowLkSuccessToast(queuedMessage);
});

// Подтверждение e-mail в личном кабинете.
(function($) {
    'use strict';

    function verificationMessage($box, text, isError) {
        $box.find('.lk-email-verification__message')
            .text(text || '')
            .toggleClass('is-error', !!isError);
    }

    function responseMessage(response, fallback) {
        if (response && response.data) {
            if (typeof response.data === 'string') return response.data;
            if (response.data.message) return response.data.message;
        }
        return fallback;
    }

    function startEmailResendTimer($button, seconds) {
        var remaining = parseInt(seconds, 10) || 60;
        window.clearInterval($button.data('emailTimer'));
        $button.prop('disabled', true).text('Отправить повторно через ' + remaining + ' сек.');
        var timer = window.setInterval(function() {
            remaining--;
            if (remaining <= 0) {
                window.clearInterval(timer);
                $button.prop('disabled', false).text('Отправить код повторно');
                return;
            }
            $button.text('Отправить повторно через ' + remaining + ' сек.');
        }, 1000);
        $button.data('emailTimer', timer);
    }

    $(document).on('click', '.lk-email-confirmation__link', function(e) {
        e.preventDefault();
        $('.email-confirmation-modal__email').text(yoga_ajax.user_email || '');
        $('.email-confirmation-modal__message').empty().removeClass('is-error');
        $('.email-confirmation-modal__code').val('');
        $('.email-confirmation-overlay').addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('email-confirmation-is-open');
        $('.email-confirmation-modal__code').trigger('focus');
        $('.email-confirmation-modal__resend').trigger('click');
    });

    $(document).on('input', '.lk-email-verification__code', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    $(document).on('click', '.lk-email-verification__resend', function() {
        if (typeof yoga_ajax === 'undefined') return;
        var $button = $(this);
        var $box = $button.closest('.lk-email-verification');
        $button.prop('disabled', true);
        $.post(yoga_ajax.ajax_url, {
            action: 'yoga_send_email_verification_code',
            nonce: yoga_ajax.email_verification_nonce
        }).done(function(response) {
            verificationMessage($box, responseMessage(response, 'Код отправлен.'), !response.success);
            if (response.success) startEmailResendTimer($button, response.data.retry_after);
        }).fail(function(xhr) {
            var response = xhr.responseJSON;
            verificationMessage($box, responseMessage(response, 'Не удалось отправить код.'), true);
            var retryAfter = response && response.data && response.data.retry_after;
            if (retryAfter) startEmailResendTimer($button, retryAfter);
            else $button.prop('disabled', false);
        });
    });

    $(document).on('click', '.lk-email-verification__verify', function() {
        if (typeof yoga_ajax === 'undefined') return;
        var $button = $(this);
        var $box = $button.closest('.lk-email-verification');
        var code = $box.find('.lk-email-verification__code').val();
        if (!/^\d{6}$/.test(code)) {
            verificationMessage($box, 'Введите 6 цифр из письма.', true);
            return;
        }
        $button.prop('disabled', true);
        $.post(yoga_ajax.ajax_url, {
            action: 'yoga_verify_email_code',
            nonce: yoga_ajax.email_verification_nonce,
            code: code
        }).done(function(response) {
            if (response.success) {
                window.yogaQueueLkSuccessToast('Электронная почта подтверждена');
                location.reload();
                return;
            }
            verificationMessage($box, responseMessage(response, 'Неверный код.'), true);
        }).fail(function(xhr) {
            verificationMessage($box, responseMessage(xhr.responseJSON, 'Не удалось проверить код.'), true);
        }).always(function() {
            $button.prop('disabled', false);
        });
    });
})(jQuery);

(function($) {
    'use strict';

    function modalEmailMessage(text, error) {
        $('.email-confirmation-modal__message').text(text || '').toggleClass('is-error', !!error);
    }

    function modalResponseMessage(response, fallback) {
        return response && response.data && response.data.message ? response.data.message : fallback;
    }

    function modalResendTimer($button, seconds) {
        var remaining = parseInt(seconds, 10) || 60;
        window.clearInterval($button.data('timer'));
        $button.prop('disabled', true).text('Отправить повторно через ' + remaining + ' сек.');
        var timer = window.setInterval(function() {
            remaining--;
            if (remaining <= 0) {
                window.clearInterval(timer);
                $button.prop('disabled', false).text('Отправить код повторно');
            } else {
                $button.text('Отправить повторно через ' + remaining + ' сек.');
            }
        }, 1000);
        $button.data('timer', timer);
    }

    $(document).on('input', '.email-confirmation-modal__code', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    function closeEmailConfirmationModal() {
        $('.email-confirmation-overlay').removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('email-confirmation-is-open');
        $('.lk-email-confirmation__link').trigger('focus');
    }

    $(document).on('click', '.email-confirmation-modal__cancel, .email-confirmation-modal__close', function() {
        closeEmailConfirmationModal();
    });

    $(document).on('click', '.email-confirmation-overlay', function(e) {
        if (e.target === this) closeEmailConfirmationModal();
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('.email-confirmation-overlay').hasClass('is-open')) {
            closeEmailConfirmationModal();
        }
    });

    $(document).on('click', '.email-confirmation-modal__resend', function() {
        var $button = $(this);
        $button.prop('disabled', true);
        $.post(yoga_ajax.ajax_url, {
            action: 'yoga_send_email_verification_code',
            nonce: yoga_ajax.email_verification_nonce
        }).done(function(response) {
            modalEmailMessage(modalResponseMessage(response, 'Код отправлен.'), !response.success);
            if (response.success) modalResendTimer($button, response.data.retry_after);
        }).fail(function(xhr) {
            modalEmailMessage(modalResponseMessage(xhr.responseJSON, 'Не удалось отправить код.'), true);
            var retryAfter = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.retry_after;
            if (retryAfter) modalResendTimer($button, retryAfter);
            else $button.prop('disabled', false);
        });
    });

    $(document).on('submit', '.email-confirmation-modal__form', function(e) {
        e.preventDefault();
        var code = $('.email-confirmation-modal__code').val();
        var $button = $('.email-confirmation-modal__confirm');
        if (!/^\d{6}$/.test(code)) {
            modalEmailMessage('Введите 6 цифр из письма.', true);
            return;
        }
        $button.prop('disabled', true);
        $.post(yoga_ajax.ajax_url, {
            action: 'yoga_verify_email_code',
            nonce: yoga_ajax.email_verification_nonce,
            code: code
        }).done(function(response) {
            if (response.success) {
                window.yogaQueueLkSuccessToast('Электронная почта подтверждена');
                location.reload();
            } else {
                modalEmailMessage(modalResponseMessage(response, 'Неверный код.'), true);
            }
        }).fail(function(xhr) {
            modalEmailMessage(modalResponseMessage(xhr.responseJSON, 'Не удалось проверить код.'), true);
        }).always(function() {
            $button.prop('disabled', false);
        });
    });
})(jQuery);
