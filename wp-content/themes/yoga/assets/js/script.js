jQuery(document).ready(function($) {
	
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
	
	$(".body_main .logo-header, .body_main .logo-footer").click(function () {
		var elementClick = $(this).attr("href");
		var destination = $(elementClick).offset().top;
		$('html, body').animate({ scrollTop: destination }, 1200);
		return false;
	});
	
	
	$('.main-menu-active-item').hover(function () {
		$(this).toggleClass("active");
		$('.modal-menu').toggleClass("active");
	});      
	
	$('.modal-menu').hover(function () {
		$(this).addClass("active");
		$('.main-menu-active-item').addClass("active");
	});  
	
	$('.modal-menu').mouseleave(function () {
		$(this).removeClass("active");
		$('.main-menu-active-item').removeClass("active");
	});  
	
	// Кастомные «чекбоксы» (не трогать .library-filters-screen__box — там связка с реальным input)
	$(document).on('click', '.checkbox:not(.library-filters-screen__box)', function () {
		$(this).toggleClass("active");
	});
	
	$('.burger').click(function () {
		$('.modal-mobile-menu').addClass("active");
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
		// Инициализация reCAPTCHA при открытии модального окна
		setTimeout(function() {
			if (typeof initRecaptcha === 'function') {
				initRecaptcha();
			}
		}, 300);
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
				breakpoint: 1199,
				settings: {   
					slidesToShow: 4,
					slidesToScroll: 4,   
				}
			},
			{
				breakpoint: 991,
				settings: {
					slidesToShow: 3,
					slidesToScroll: 3,
				}
			},
			{
				breakpoint: 767,
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
				breakpoint: 1199,
				settings: {   
					slidesToShow: 3,
					slidesToScroll: 3,   
				}
			},
			{
				breakpoint: 991,
				settings: {
					slidesToShow: 2,
					slidesToScroll: 2,
				}
			},
			{
				breakpoint: 575,
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
	
	$(".section-subscription .form").submit(function(e) {
		e.preventDefault();
		$(this).closest('.subscription').addClass("succes");
	});
	
	
	
	/* Section-library */
	
	$('.filter-btn').click(function () {
		var $lib = $(this).closest('.section-library');
		if ($lib.length && $(window).width() < 991 && $('#library-filters-screen').length) {
			$(this).toggleClass('active');
			if ($(this).hasClass('active')) {
				openLibraryFiltersScreen();
			} else {
				closeLibraryFiltersScreen();
			}
			return;
		}
		$(this).toggleClass("active");
		const $contextForm = $(this).closest('form');
		if ($contextForm.length) {
			$contextForm.find('.filter').first().toggleClass("active");
		} else {
			$('.filter').toggleClass("active");
		}
	}); 
	
	
	if ($(window).width() > 991 ) {
        jQuery(function($){
			$(document).mouseup(function (e){ // событие клика по веб-документу
				$('.filter-item__list .checkbox-item').not('.active').closest('.library-form').find('.filter-btn span').removeClass("active");
				$('.filter-item__list .checkbox-item.active').closest('.library-form').find('.filter-btn span').addClass("active");
			});
		});
	};
	
	if ($(window).width() < 991 ) {
        jQuery(function($){
			$(document).mouseup(function (e){ // событие клика по веб-документу
				$('.modal-filter .checkbox-item').not('.active').closest('body').find('.library-form').find('.filter-btn span').removeClass("active");
				$('.modal-filter .checkbox-item.active').closest('body').find('.library-form').find('.filter-btn span').addClass("active");
			});
		});
	};
	
	
	
	jQuery(function($){
		$(document).mouseup(function (e){ // событие клика по веб-документу
			var div = $(".form-search .input, .form-search-list__item"); // тут указываем ID элемента
			var val = div.val();
			if (!div.is(e.target) // если клик был не по нашему блоку
				&& div.has(e.target).length === 0 ) { // и не по его дочерним элементам
				$('.form-search-list').removeClass("active");
				$('.form-search').removeClass("active");
			}
		});
	});
	
	
	$('.form-search .input').keyup(function(){
		var $this = $(this),
		vall = $this.val();
		
		if(vall.length >= 1){
			$(this).closest('.form-search').find('.form-search-list').addClass("active");
			$('.form-search').addClass("active");
			$('.form-cat-list').removeClass("active");
			$('.filter-item').removeClass("active");
			$('.filter-item__list').removeClass("active");
			}else {
			$(this).closest('.form-search').find('.form-search-list').removeClass("active");
			$('.form-search').removeClass("active");
		}
	});
	
	
	$('.form-search-list__item').click(function () {
		$(this).closest('.form-search').removeClass("active");
		$(this).closest('.form-search-list').removeClass("active");
		var libsearchtext = $(this).find('span').text();
		$('.form-search').find('.input').val(libsearchtext);
	}); 
	
	
	$('.form-categories').click(function () {
		$(this).toggleClass("active");
		$('.form-search').toggleClass("active");
		$('.form-cat-list').toggleClass("active");
		$('.form-search-list').removeClass("active");
		$('.filter-item').removeClass("active");
		$('.filter-item__list').removeClass("active");
	}); 
	
	
	
	$('.form-cat-list__item').click(function () {
		$(this).closest('.form-search').removeClass("active");
		$(this).closest('.form-search').find('.form-categories').removeClass("active");
		$(this).closest('.form-cat-list').removeClass("active");
		$(this).closest('.form-cat-list').find('.form-cat-list__item').removeClass("active");
		$(this).addClass("active");
		
		var libcat = $(this).attr('data-target');
		$('.form-categories__value span').removeClass("active");
		$('.form-categories__value span[data-target=' + libcat + ']').addClass("active");
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
		$(this).toggleClass("active");
		$(this).find('.checkbox').toggleClass("active");
		$('.filter-item__list .checkbox-item').not('.active').closest('.library-form').find('.form-reset').removeClass("active");
		$('.filter-item__list .checkbox-item.active').closest('.library-form').find('.form-reset').addClass("active");
		/*
			var filttext = $(this).find('span').text();
			$(this).closest('.filter-item').find('.filter-item__main span').html(filttext);
		*/
	});
	
	
	
	$('.form-reset').click(function () {
		$(this).removeClass("active");
		$('.filter-item').removeClass("focused");
		$('.filter-item__list .checkbox-item').removeClass("active");
		$('.filter-item__list .checkbox-item .checkbox').removeClass("active")
	});
	
	
	/* Section-kriyi */
	
	jQuery(function($){
		
		$(document).mouseup(function (e){ // событие клика по веб-документу
			var div = $(".sorting-item"); // тут указываем ID элемента
			var val = div.val();
			if (!div.is(e.target) // если клик был не по нашему блоку
				&& div.has(e.target).length === 0 ) { // и не по его дочерним элементам
				$('.sorting-item').removeClass("active");
				$('.sorting-item__list').removeClass("active");
			}
		});
	});
	
	
	$('.sorting-item__main').click(function () {
		$('.form-search').removeClass("active");
		$('.form-categories').removeClass("active");
		$('.form-cat-list').removeClass("active");
		$('.form-search-list').removeClass("active");
		
		$(this).closest('.sorting').find('.sorting-item').find('.sorting-item__main').not(this).closest('.sorting-item').removeClass("active");
		$(this).closest('.sorting').find('.sorting-item').find('.sorting-item__main').not(this).closest('.sorting-item').find('.sorting-item__list').removeClass("active");
		
		$(this).closest('.sorting-item').toggleClass("active");
		$(this).closest('.sorting-item').find('.sorting-item__list').toggleClass("active");
		
	});
	
	
	$('.sorting-item__list-item').click(function () {
		$(this).closest('.sorting-item').toggleClass("active");
		$(this).closest('.sorting-item').find('.sorting-item__list').toggleClass("active");
		$(this).closest('.sorting-item__list').find('.sorting-item__list-item').removeClass("active");
		$(this).addClass("active");
		
		var sortcat = $(this).find('span').text();
		$(this).closest('.sorting-item').find('.sorting-item__main span').text(sortcat);
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
	
	$(".praktika-menu nav ul li a").click(function () {
		var elementClick = $(this).attr("href");
		var $target = $(elementClick);
		if (!$target.length) {
			return false;
		}

		var destination = $target.offset().top - 80;
		$('html, body').animate({ scrollTop: destination }, 800);
		return false;
	});

	function initPraktikaMenuScrollSpy() {
		var $menuLinks = $('.praktika-menu nav ul li a.ref[href^="#"]');
		if (!$menuLinks.length) {
			return;
		}

		var trackedSections = [];
		var topOffset = 120;
		var currentActiveIndex = 0;
		var pendingActiveIndex = null;
		var pendingStartedAt = 0;
		var switchDelayMs = 500;

		function rebuildTrackedSections() {
			trackedSections = [];

			$menuLinks.each(function () {
				var $link = $(this);
				var href = $link.attr('href');
				if (!href || href === '#') {
					return;
				}

				var $anchor = $(href);
				if (!$anchor.length) {
					return;
				}

				trackedSections.push({
					$link: $link,
					$anchor: $anchor
				});
			});
		}

		function getSectionBounds(index) {
			var currentTop = trackedSections[index].$anchor.offset().top;
			var sectionBottom = $(document).height();

			if (index < trackedSections.length - 1) {
				sectionBottom = trackedSections[index + 1].$anchor.offset().top;
			} else {
				var $questions = $('#section-form-questions');
				if ($questions.length) {
					sectionBottom = $questions.offset().top + $questions.outerHeight();
				}
			}

			if (sectionBottom <= currentTop) {
				sectionBottom = currentTop + 1;
			}

			return {
				top: currentTop,
				bottom: sectionBottom
			};
		}

		function applyActiveIndex(nextIndex) {
			if (!trackedSections.length) {
				return;
			}

			var safeIndex = Math.max(0, Math.min(nextIndex, trackedSections.length - 1));
			pendingActiveIndex = null;
			pendingStartedAt = 0;
			currentActiveIndex = safeIndex;
			$menuLinks.removeClass('active');
			trackedSections[safeIndex].$link.addClass('active');
		}

		function setActiveByVisibleArea() {
			if (!trackedSections.length) {
				return;
			}

			var scrollTop = $(window).scrollTop();
			var probeY = scrollTop + topOffset + 1;
			var targetIndex = 0;

			for (var i = 0; i < trackedSections.length; i++) {
				var bounds = getSectionBounds(i);
				if (probeY >= bounds.top && probeY < bounds.bottom) {
					targetIndex = i;
					break;
				}

				if (probeY >= bounds.bottom) {
					targetIndex = i;
				}
			}

			if (targetIndex === currentActiveIndex) {
				pendingActiveIndex = null;
				pendingStartedAt = 0;
				return;
			}

			var now = Date.now();
			if (pendingActiveIndex !== targetIndex) {
				pendingActiveIndex = targetIndex;
				pendingStartedAt = now;
				return;
			}

			if (now - pendingStartedAt >= switchDelayMs) {
				applyActiveIndex(targetIndex);
			}
		}

		var ticking = false;
		function requestRepaint() {
			if (ticking) {
				return;
			}

			ticking = true;
			window.requestAnimationFrame(function () {
				ticking = false;
				setActiveByVisibleArea();
			});
		}

		rebuildTrackedSections();
		var initialIndex = 0;
		$menuLinks.each(function (index) {
			if ($(this).hasClass('active')) {
				initialIndex = index;
			}
		});
		applyActiveIndex(initialIndex);
		setActiveByVisibleArea();

		$menuLinks.on('click.praktikaMenuSpy', function () {
			var clickedIndex = $menuLinks.index(this);
			if (clickedIndex >= 0) {
				applyActiveIndex(clickedIndex);
			}
		});

		$(window).on('scroll.praktikaMenuSpy resize.praktikaMenuSpy', requestRepaint);
	}

	initPraktikaMenuScrollSpy();
	
	
	$('.exercise-slider_active').slick({
		infinite: true,
		dots: true,
		arrows: true,
		slidesToShow: 1,
		slidesToScroll: 1
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
	
	$('.praktika-comment > .praktika-comment-item .answer-btn').click(function () {
		if (typeof yoga_ajax !== 'undefined' && !yoga_ajax.user_logged_in) {
			alert('Для ответа необходимо авторизоваться');
			return;
		}

		var commentId = $(this).closest('.praktika-comment').attr('id').replace('comment-', '');
		toggleReplyForm(commentId);
	});
	
	$('.sub-answer > .praktika-comment-item .answer-btn').click(function () {
		if (typeof yoga_ajax !== 'undefined' && !yoga_ajax.user_logged_in) {
			alert('Для ответа необходимо авторизоваться');
			return;
		}

		var commentId = $(this).closest('.praktika-comment').attr('id').replace('comment-', '');
		toggleReplyForm(commentId);
	});
	
	
	$('.your-comm__btn_edit').click(function () {
		$(this).closest('.praktika-comment-item').find('.praktika-comment-item__text').addClass("hidden");
		$(this).closest('.praktika-comment-item').find('.praktika-comment-item__edit').removeClass("hidden");
		
		$(this).closest('.praktika-comment-item').find('.praktika-comment-item__edit .textarea-resize').focus();
	});
	
	$('.praktika-comment-item__edit .textarea-resize').focus(function () {
		var el = this;
		setTimeout(function() {
			el.style.cssText = 'height:auto; padding:0';
			el.style.cssText = 'height:' + el.scrollHeight + 'px';
		}, 1);
	}); 
	
	
	$('.praktika-comment-item__edit .btn').click(function () {
		$(this).closest('.praktika-comment-item').find('.praktika-comment-item__text').removeClass("hidden");
		$(this).closest('.praktika-comment-item__edit').addClass("hidden");
		
	});
	
	$(".comment-form-main form, .praktika-comment__answer, .praktika-comment-item__edit").submit(function(e) {
		e.preventDefault();
	});
	
	
	$( document ).ready(function() {
		
		
		var block_pos_03 = $('.praktika-fixed').offset()?.top || 0;
		var wrap_pos_03 = $('.praktika-menu').offset()?.top || 0;
		var block_height_03 = $('.praktika-fixed').outerHeight();
		// высота блока
		var wrap_height_03 = $('.praktika-menu').outerHeight();
		// высота контейнера
		var pos_absolute_03 = wrap_pos_03 + wrap_height_03 - block_height_03;
		
		
		$(window).scroll(function () {
			
			var wrap_height_03 = $('.praktika-menu').outerHeight();
			// высота контейнера
			var pos_absolute_03 = wrap_pos_03 + wrap_height_03 - block_height_03;   
			
			if ($(window).scrollTop() > pos_absolute_03 - 105) {
				// Если страницу прокрутили дальше, чем высота родителя минус высота фикс. блока, то стопорим блок
				$('.praktika-fixed').css({
					'position': 'absolute',
					'top': 'calc(100% + 0px)',
					'transform': 'translateY(-100%)',
					
				});
			}
			else if ($(window).scrollTop() > block_pos_03 - 105) {
				// Если страницу прокрутили дальше, чем находится наш блок, то мы этот блок фиксируем и отображаем сверху
				$('.praktika-fixed').css({
					'position': 'fixed',
					'top': '105px',
					'transform': 'translateY(0%)',
				});
				
				} else {
				// Если же позиция скролла меньше (выше), чем наш блок, то возвращаем все назад
				$('.praktika-fixed').css({
					'position': 'absolute',
					'top': '0px',
					'transform': 'translateY(0%)',
					
				});
			}
			
		});
		
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
	
	
	
	
	
	/* Header-lk — бургер: панель .modal-mobile-menu-lk (разметки раньше не было; второй обработчик для ширины <991 снимал active и мешал). */
	$('.lk-burger').on('click', function (e) {
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
	
	
	if ($(window).width() < 991 ) {
		$('.sidebar-menu__item').click(function () {
			$('.overlay').removeClass("active");
			$('.modal').removeClass("active");
			$('.modal-filter').removeClass("active");
			$('.modal-login').removeClass("active");
			$('.modal-mobile-menu').removeClass("active")
			$('.modal-mobile-menu-lk').removeClass("active")
			$('.body').removeClass("body-fixed");
			$('.library-filters-screen').removeClass('active').attr('aria-hidden', 'true');
			$('.section-library .filter-btn').removeClass('active');
		});
	};
	
	if ($(window).width() < 991 ) {
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
	
	$('.lk-form').submit(function() {
		$(this).find('.lk-form-safe__text').addClass("active");
	});
	
	$('.lk-questions > .btn').click(function () {
		$(this).find('span').toggleClass("active");
		$('.lk-questions-item_other').toggleClass("hidden");
		
	});
	
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
	
	if ($(window).width() > 991 ) {
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
	
	
	if ($(window).width() > 1320 ) {
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
	
	
	/* Section-post */
	
	$( document ).ready(function() {
		
		
		var block_pos_03 = $('.post-author').offset()?.top || $('.post-author-fixed').offset()?.top || 0;
		var wrap_pos_03 = $('.post-layout').offset()?.top || 0;
		// позиция контейнера
		var block_height_03 = $('.post-author-fixed').outerHeight();
		// высота блока
		var wrap_height_03 = $('.post-layout').outerHeight();
		// высота контейнера
		var pos_absolute_03 = wrap_pos_03 + wrap_height_03 - block_height_03;
		
		
		$(window).scroll(function () {
			if (!$('.post-author-fixed').length || !block_height_03 || !wrap_height_03) {
				return;
			}
			
			var wrap_height_03 = $('.post-layout').outerHeight();
			// высота контейнера
			var pos_absolute_03 = wrap_pos_03 + wrap_height_03 - block_height_03;   
			
			if ($(window).scrollTop() > pos_absolute_03 - 105) {
				// Если страницу прокрутили дальше, чем высота родителя минус высота фикс. блока, то стопорим блок
				$('.post-author-fixed').css({
					'position': 'absolute',
					'top': 'calc(100% + 0px)',
					'transform': 'translateY(-100%)',
					
				});
			}
			else if ($(window).scrollTop() > block_pos_03 - 105) {
				// Если страницу прокрутили дальше, чем находится наш блок, то мы этот блок фиксируем и отображаем сверху
				$('.post-author-fixed').css({
					'position': 'fixed',
					'top': '105px',
					'transform': 'translateY(0%)',
				});
				
				} else {
				// Если же позиция скролла меньше (выше), чем наш блок, то возвращаем все назад
				$('.post-author-fixed').css({
					'position': 'absolute',
					'top': '0px',
					'transform': 'translateY(0%)',
					
				});
			}
			
		});
		
	}); 
	
	
	
	$(document).ready(function () {
		function checkWidthAndInitSlick() {
			if ($(window).width() > 575) {
				if (!$('.popular-articles-slider').hasClass('slick-initialized')) {
					$('.popular-articles-slider').slick({
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
								breakpoint: 991,
								settings: {   
									slidesToShow: 2,
									slidesToScroll: 1,   
								}
							}
						]
					});
				}
				} else {
				if ($('.slider').hasClass('slick-initialized')) {
					$('.slider').slick('unslick');
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
		$('.modal-filter').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-mobile-menu').removeClass("active");
		$('.modal-mobile-menu-lk').removeClass("active");
		$('.body').removeClass("body-fixed");
		$('.modal-addnewcard').removeClass("active");
		$('.body_lk .header').removeClass("active");
		$('.body_lk .lk-burger').removeClass("active");
		$('.library-filters-screen').removeClass('active').attr('aria-hidden', 'true');
		$('.section-library .filter-btn').removeClass('active');
	});
	
	$('.modal-close').click(function () {
		$('.overlay').removeClass("active");
		$('.modal').removeClass("active");
		$('.modal-filter').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-mobile-menu').removeClass("active");
		$('.modal-mobile-menu-lk').removeClass("active");
		$('.modal-addnewcard').removeClass("active");
		$('.body').removeClass("body-fixed");
		$('.body_lk .header').removeClass("active");
		$('.body_lk .lk-burger').removeClass("active");
		$('.library-filters-screen').removeClass('active').attr('aria-hidden', 'true');
		$('.section-library .filter-btn').removeClass('active');
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
	
	
	// Переключение слайдов формы входа/регистрации (Вход / Регистрация / Восстановление пароля)
	$('.ml-sl-switch').on('click', function () {
		var mlsw = $(this).attr('data-target');
		$('.modal-login-inner__slide').removeClass("active");
		$('.modal-login-inner__slide[data-target=' + mlsw + ']').addClass("active");
		// Инициализация reCAPTCHA при переключении слайдов
		setTimeout(function() {
			if (typeof initRecaptcha === 'function') {
				initRecaptcha();
			}
		}, 300);
	});
	
	$(".modal-login .form").submit(function(e) {
		e.preventDefault();
	});
	
	// AJAX: форма входа по почте
	$(document).on('submit', '.yoga-form-login', function(e) {
		e.preventDefault();
		var $form = $(this);
		var $btn = $form.find('.btn');
		if (typeof yoga_ajax === 'undefined') return;
		$btn.prop('disabled', true);
		$.post(yoga_ajax.ajax_url, $form.serialize())
			.done(function(r) {
				if (r.success) {
					$('.modal-login').removeClass("active");
					location.reload();
				} else {
					alert(r.data || 'Ошибка входа');
				}
			})
			.fail(function() { alert('Ошибка соединения'); })
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

		$btn.prop('disabled', true);
		$.post(yoga_ajax.ajax_url, $form.serialize())
			.done(function(r) {
				if (r.success) {
					$('.modal-login').removeClass("active");
					location.reload();
				} else {
					var message = (r && r.data && r.data.message) ? r.data.message : (r.data || 'Ошибка регистрации');
					alert(message);
				}
			})
			.fail(function(xhr) { alert(extractAjaxError(xhr, 'Ошибка соединения')); })
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

		$btn.prop('disabled', true);
		$.post(yoga_ajax.ajax_url, $form.serialize())
			.done(function(r) {
				if (r.success) {
					$('.modal-login-inner__slide').removeClass("active");
					$('.modal-login-inner__slide[data-target=4]').addClass("active");
				} else {
					var message = (r && r.data && r.data.message) ? r.data.message : (r.data || 'Не удалось отправить письмо');
					alert(message);
				}
			})
			.fail(function(xhr) { alert(extractAjaxError(xhr, 'Ошибка соединения')); })
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
		$('.mobile-menu__slide_sub').addClass("active");
		$('.mobile-menu__slide_main').removeClass("active");
	});
	
	$('.mobile-menu-back').click(function () {
		$('.mobile-menu__slide_sub').removeClass("active");
		$('.mobile-menu__slide_main').addClass("active");
	});
	
	
	
	
	
	
	
	
	$('.filter-mob-item .checkbox-item').click(function () {
		$(this).toggleClass("active");
		$(this).find('.checkbox').toggleClass("active");
		
	});
	
	$('.modal-filter-inner .btn').click(function () {
		$('.overlay').removeClass("active");
		$('.modal').removeClass("active");
		$('.modal-filter').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-mobile-menu').removeClass("active")
		$('.body').removeClass("body-fixed");
	});
	
	
	$('.modal-filter-inner .form-reset').click(function () {
		$('.modal-filter-inner .checkbox-item').removeClass("active");
		$('.modal-filter-inner .checkbox-item .checkbox').removeClass("active")
	});
	
	
	
	$('.modal-call_delcomm').click(function () {
		$('.modal-default_delcomm').addClass("active");
	});
	
	
	$('.modal-default_delcomm .btn_white, .modal-default_carddel .btn_white').click(function () {
		$('.overlay').removeClass("active");
		$('.modal').removeClass("active");
		$('.modal-filter').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-mobile-menu').removeClass("active")
		$('.body').removeClass("body-fixed");
	});
	
	$('.modal-default_delcomm .btn_dark, .modal-default_carddel .btn_dark').click(function () {
		$(this).closest('.delcomm').addClass("active");
	});
	
	
	$('.modal-cookie .btn').click(function () {
		$('.overlay').removeClass("active");
		$('.modal-cookie').removeClass("active");
		$('.body').removeClass("body-fixed");
	});
	
	
	$('.modal-call_logout').click(function () {
		$('.modal-mobile-menu-lk').removeClass('active');
		$('.body_lk .lk-burger').removeClass('active');
		$('.body_lk .header').removeClass('active');
		$('.overlay').addClass('active');
		$('.modal-default_logout').addClass('active');
		$('.body').addClass('body-fixed');
	});
	
	// Закрытие модального окна выхода по кнопке "Нет, остаться" или крестику
	$('.modal-default_logout .modal-close, .modal-default_logout .modal-close-logout').click(function () {
		$('.overlay').removeClass("active");
		$('.modal').removeClass("active");
		$('.modal-filter').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-mobile-menu').removeClass("active");
		$('.modal-mobile-menu-lk').removeClass("active");
		$('.body').removeClass("body-fixed");
	});
	
	// Кнопка "Да, выйти" - редирект на wp_logout_url уже настроен в HTML
	
	
	$('.modal-call_card').click(function () {
		$('.modal-default_card').addClass("active");
	});
	
	$('.modal-call_carddel').click(function () {
		$('.modal-default_carddel').addClass("active");
	});
	
	$('.modal-call_addnewcard').click(function () {
		$('.modal-addnewcard').addClass("active");
	});
	
	$('.modal-default_card .btn_white').click(function () {
		$('.overlay').removeClass("active");
		$('.modal').removeClass("active");
		$('.modal-filter').removeClass("active");
		$('.modal-login').removeClass("active");
		$('.modal-mobile-menu').removeClass("active");
		$('.modal-mobile-menu-lk').removeClass("active");
		$('.body').removeClass("body-fixed");
	});
	
	$('.modal-default_card .btn_dark').click(function () {
		$('.modal-default_card').removeClass("active");
	});
	
	
	$('.input-card-custom .input').on('focus input', function () {
		$(this).closest('.input-card-custom').addClass('active');
	});
	
	
	
	
	$('.input-card-custom .input').on('blur', function () {
		if ($(this).val().indexOf('_') !== -1) {
			$(this).closest('.input-card-custom').removeClass('active');
			} else {
			$(this).closest('.input-card-custom').addClass('active');
		}
	});
	
	$(".modal-addnewcard .form").submit(function(e) {
		e.preventDefault();
		$('.modal-addnewcard').removeClass("active");
		$('.modal-default_cardsucces').addClass("active");
	});
	
	
	
	
	$('.input-card-custom .input').on('input keyup change', function () {
		const requiredCount = 3;
		let filledCount = 0;
		
		$('.input-card-custom .input').each(function () {
			const val = $(this).val();
			// Проверим, нет ли символов маски (_) и поле не пустое
			if (val && val.indexOf('_') === -1) {
				filledCount++;
			}
		});
		
		if (filledCount === requiredCount) {
			$('.modal-addnewcard .btn').addClass('active');
			} else {
			$('.modal-addnewcard .btn').removeClass('active');
		}
	});
	
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
	
	/* // Обработка комментариев
	document.addEventListener('DOMContentLoaded', function() {
		// Показ/скрытие формы ответа
		document.addEventListener('click', function(e) {
			if (e.target.closest('.answer-btn')) {
				const commentItem = e.target.closest('.praktika-comment');
				const answerForm = commentItem.querySelector('.praktika-comment__answer');
				answerForm.style.display = answerForm.style.display === 'none' ? 'block' : 'none';
			}
		});
		
		// Автоматическое увеличение textarea
		const textareas = document.querySelectorAll('.textarea-resize');
		textareas.forEach(textarea => {
			textarea.addEventListener('input', function() {
				this.style.height = 'auto';
				this.style.height = (this.scrollHeight) + 'px';
			});
		});
		
		// Обработка отправки формы
		const commentForms = document.querySelectorAll('#commentform');
		commentForms.forEach(form => {
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				// AJAX отправка формы
				submitCommentForm(this);
			});
		});
	});
	
	// AJAX отправка комментария
	function submitCommentForm(form) {
		const formData = new FormData(form);
		
		fetch(yoga_ajax.ajax_url, {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				location.reload();
				} else {
				alert('Ошибка при отправке комментария');
			}
		})
		.catch(error => {
			console.error('Error:', error);
		});
	} */
	
	// Обработка формы подписки с специфической структурой
	document.addEventListener('DOMContentLoaded', function() {
		const subscriptionForms = document.querySelectorAll('.subscription-form');
		const subscriptionButtons = document.querySelectorAll('#subscription-btn');
		
		// Обработка клика по label
		const formButtons = document.querySelectorAll('.form-btn');
		formButtons.forEach(button => {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const form = this.closest('.subscription-form');
				const emailInput = form.querySelector('input[type="email"]');
				const nonce = form.querySelector('input[name="subscription_nonce_field"]').value;
				
				// Валидация email
				if (!isValidEmail(emailInput.value)) {
					showSubscriptionError('Пожалуйста, введите корректный email');
					return;
				}
				
				// AJAX отправка
				subscribeUser(emailInput.value, nonce, form);
			});
		});
		
		// Также обрабатываем отправку формы по Enter
		subscriptionForms.forEach(form => {
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				const emailInput = this.querySelector('input[type="email"]');
				const nonce = this.querySelector('input[name="subscription_nonce_field"]').value;
				
				if (!isValidEmail(emailInput.value)) {
					showSubscriptionError('Пожалуйста, введите корректный email');
					return;
				}
				
				subscribeUser(emailInput.value, nonce, this);
			});
		});
	});
	
	
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
				'nonce': nonce
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				showSubscriptionSuccess(data.message);
				form.reset();
				} else {
				showSubscriptionError(data.message);
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
	
	function showSubscriptionSuccess(message) {
		const successElement = document.querySelector('.form__succes');
		if (successElement) {
			successElement.textContent = message;
			successElement.style.display = 'block';
			
			setTimeout(() => {
				successElement.style.display = 'none';
			}, 5000);
		}
	}
	
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
		
		if (!isValidEmail(email)) {
			alert('Пожалуйста, введите корректный email');
			return;
		}
		
		// Отправка AJAX
		submitContactForm(formData, form);
	}
	
	// AJAX отправка формы контактов
	function submitContactForm(formData, form) {
		const submitLabel = form.querySelector('label[for="form-questions-submit"]');
		const originalHtml = submitLabel.innerHTML;
		
		// Показываем лоадер
		submitLabel.innerHTML = '<span class="spinner"></span>';
		submitLabel.style.pointerEvents = 'none';
		
		fetch(yoga_ajax.ajax_url, {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				showContactSuccess('Сообщение отправлено! Мы свяжемся с вами в ближайшее время.');
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
			submitLabel.innerHTML = originalHtml;
			submitLabel.style.pointerEvents = 'auto';
		});
	}
	
	function showContactSuccess(message) {
		//alert(message); // Можно заменить на красивый toast
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
				faqForm.reset();
				$(faqForm).find('input[type="text"], input[type="email"], textarea').val('');
				$('.body').addClass("body-fixed");
				$('.overlay').addClass("active");
				$('.modal').removeClass("active");
				$('.modal-login').removeClass("active");
				$('.modal-default_formsucces').addClass("active");
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

    function syncLibraryFilterCheckboxLabels() {
        $('#practice-filter-form input[type=checkbox]').each(function() {
            var id = $(this).attr('id');
            if (!id) return;
            var on = $(this).prop('checked');
            $('label[for="' + id + '"]').toggleClass('active', on).find('.checkbox').toggleClass('active', on);
        });
    }

    function updateLibraryFiltersApplyCountFromHtml(html) {
        var $c = $('.library-filters-apply-count');
        if (!$c.length) return;
        var n = $('<div>').html(html).find('.library-item').length;
        $c.text(n);
    }

    function closeLibraryFiltersScreen() {
        $('.library-filters-screen').removeClass('active').attr('aria-hidden', 'true');
        $('.section-library .filter-btn').removeClass('active');
        $('.overlay').removeClass('active');
        $('.body').removeClass('body-fixed');
    }

    function openLibraryFiltersScreen() {
        $('.library-filters-screen').addClass('active').attr('aria-hidden', 'false');
        $('.overlay').addClass('active');
        $('.body').addClass('body-fixed');
        syncLibraryFilterCheckboxLabels();
        var $applyCount = $('.library-filters-apply-count');
        if ($applyCount.length) {
            $applyCount.text($('.section-library .library .library-item').length);
        }
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
		
		// Собираем чекбоксы из формы (на мобильных .filter скрыт display:none — input всё равно в DOM)
		$('#practice-filter-form input[type=checkbox]:checked').each(function() {
			let name = $(this).attr('name').replace('[]','');
			if (!data.filters[name]) data.filters[name] = [];
			data.filters[name].push($(this).val());
		});
		
		$.ajax({
			url: yoga_ajax.ajax_url,
			type: 'POST',
			traditional: true,
			data: data,
			success: function(response) {
				$('.section-library .library').html(response);
				updateLibraryFiltersApplyCountFromHtml(response);
			}
		});
	}

	/* Мобильный оверлей: capture + поиск label по координатам (из-за pointer-events и hit-test под скроллом на iOS) */
	(function bindLibraryFiltersScreenCapture() {
		var screen = document.getElementById('library-filters-screen');
		if (!screen) return;

		var lastFilterUiToggle = 0;

		function findFilterLabel(e) {
			var t = e.target;
			if (t && t.closest) {
				var label = t.closest('label.library-filters-screen__row.checkbox-item');
				if (label && screen.contains(label)) return label;
			}
			var cx = typeof e.clientX === 'number' ? e.clientX : null;
			var cy = typeof e.clientY === 'number' ? e.clientY : null;
			if ((cx == null || cy == null) && e.changedTouches && e.changedTouches[0]) {
				cx = e.changedTouches[0].clientX;
				cy = e.changedTouches[0].clientY;
			}
			if (cx == null || cy == null || !document.elementsFromPoint) return null;
			var stack = document.elementsFromPoint(cx, cy);
			if (!stack || !stack.length) return null;
			for (var i = 0; i < stack.length; i++) {
				var el = stack[i];
				if (!screen.contains(el)) continue;
				if (el.matches && el.matches('label.library-filters-screen__row.checkbox-item')) return el;
				if (el.closest) {
					var lab = el.closest('label.library-filters-screen__row.checkbox-item');
					if (lab && screen.contains(lab)) return lab;
				}
			}
			return null;
		}

		function applyFilterLabelToggle(e, label) {
			var forId = label.getAttribute('for');
			if (!forId) return;
			var inputEl = document.getElementById(forId);
			var formEl = document.getElementById('practice-filter-form');
			if (!inputEl || !formEl || String(inputEl.type).toLowerCase() !== 'checkbox' || !formEl.contains(inputEl)) return;
			var now = Date.now();
			if (now - lastFilterUiToggle < 450) {
				if (e.cancelable) e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				return;
			}
			lastFilterUiToggle = now;
			if (e.cancelable) e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			$('.form-categories').removeClass('active');
			$('.form-search').removeClass('active');
			$('.form-cat-list').removeClass('active');
			$('.form-search-list').removeClass('active');
			inputEl.checked = !inputEl.checked;
			syncLibraryFilterCheckboxLabels();
			loadLibraryPractices();
		}

		screen.addEventListener('click', function (e) {
			var label = findFilterLabel(e);
			if (!label) return;
			applyFilterLabelToggle(e, label);
		}, true);

		screen.addEventListener('touchend', function (e) {
			var label = findFilterLabel(e);
			if (!label) return;
			applyFilterLabelToggle(e, label);
		}, { passive: false });
	})();

    function requestLibrarySuggestions() {
        const query = $('.section-library .form-search .input').val().trim();
        const termId = getActiveLibraryTermId();
        const $search = $('.section-library .form-search');
        const $list = $search.find('.form-search-list');

        if (query.length < 2) {
            $list.removeClass('active').empty();
            return;
        }

        $.ajax({
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
            error: function() {
                $list.html('<div class="form-search-list__item form-search-list__item_empty"><span>Ничего не найдено</span></div>').addClass('active');
                $search.addClass('active');
            }
        });
    }
	
	// Функция для установки активного элемента по term ID
	function setActiveLibraryTerm(termId) {
		// Убираем активный класс у всех элементов
		$('.section-library .form-categories__value span, .section-library .form-cat-list__item').removeClass('active');
		
		// Добавляем активный класс элементам с соответствующим data-target
		$(`.section-library .form-categories__value span[data-target="${termId}"]`).addClass('active');
		$(`.section-library .form-cat-list__item[data-target="${termId}"]`).addClass('active');
	}
	
	// Обработчики кликов
	$(document).on('click', '.section-library .form-cat-list__item', function() {
		const targetTerm = $(this).data('target');
		setActiveLibraryTerm(targetTerm);
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
		}
		var libCount = $('.section-library .library .library-item').length;
		if ($('.library-filters-apply-count').length && libCount) {
			$('.library-filters-apply-count').text(libCount);
		}
	});
	
	// поиск
	$('.section-library #practice-filter-form').on('submit', function(e) {
		e.preventDefault();
		loadLibraryPractices();
	});

    $(document).on('input keyup change', '.section-library .form-search .input', function() {
        requestLibrarySuggestions();
    });

    $(document).on('click', '.section-library .form-search-list__item[data-url]', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        if (url) {
            window.location.href = url;
        }
    });
	
	// чекбоксы (включая мобильный экран: те же input в .filter)
	$(document).on('change', '#practice-filter-form input[type=checkbox]', function() {
		syncLibraryFilterCheckboxLabels();
		loadLibraryPractices();
	});

	$(document).on('click', '.library-filters-screen__close', function() {
		$('.section-library .filter-btn').removeClass('active');
		closeLibraryFiltersScreen();
	});

	$(document).on('click', '.library-filters-screen__backdrop', function() {
		$('.section-library .filter-btn').removeClass('active');
		closeLibraryFiltersScreen();
	});

	$(document).on('click', '.js-library-filters-apply', function() {
		loadLibraryPractices();
		closeLibraryFiltersScreen();
	});

	$(document).on('click', '.js-library-filters-reset', function() {
		$('#practice-filter-form input[type=checkbox]').prop('checked', false);
		syncLibraryFilterCheckboxLabels();
		loadLibraryPractices();
	});

	$(document).on('click', '.js-library-filters-more-goals', function() {
		var $sec = $(this).closest('.library-filters-screen__section');
		$sec.find('.library-filters-screen__options_goals').addClass('library-filters-screen__options_goals_expanded');
		$(this).addClass('library-filters-screen__more_hidden');
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
            term_id: getActivePracticeTermId(),
            sort: $('.section-kriyi .sorting-item__list-item.active').data('target') || 'popularity'
		};
		
        // Собираем чекбоксы
        $('.section-kriyi .filter input[type=checkbox]:checked').each(function() {
            let taxonomy = $(this).attr('name');
            if (!data.filters[taxonomy]) data.filters[taxonomy] = [];
            data.filters[taxonomy].push($(this).val());
		});
		
        $.ajax({
            url: yoga_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('.kriyi__items').html(response.data.html);
                    $('.sorting__result').text('Найдено: ' + response.data.count);
                    
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

    const suggestState = {
        timer: null,
        requestSeq: 0
    };

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

        suggestState.requestSeq += 1;
        const requestId = suggestState.requestSeq;

        $.ajax({
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
                if (requestId !== suggestState.requestSeq) {
                    return;
                }
                if (response && response.success && response.data) {
                    renderPracticeSuggestions(response.data.items || [], query);
                } else {
                    renderPracticeSuggestions([], query);
                }
            },
            error: function() {
                if (requestId !== suggestState.requestSeq) {
                    return;
                }
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
	}
	
    // Обработчики кликов по категориям
    $(document).on('click', '.section-kriyi .form-categories__value span, .section-kriyi .form-cat-list__item', function(e) {
        e.preventDefault();
        const targetTerm = $(this).data('target');
        setActiveTerm(targetTerm);
        loadPractices();
        requestPracticeSuggestions();
	});
	
    // Поиск
    $('.section-kriyi form').on('submit', function(e) {
        e.preventDefault();
        loadPractices();
	});

    $(document).on('input keyup change', '.section-kriyi .form-search .input', function() {
        clearTimeout(suggestState.timer);
        suggestState.timer = setTimeout(function() {
            requestPracticeSuggestions();
        }, 220);
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
        loadPractices();
	});
	
    // Сортировка
    $(document).on('click', '.section-kriyi .sorting-item__list-item', function(e) {
        e.preventDefault();
        $('.section-kriyi .sorting-item__list-item').removeClass('active');
        $(this).addClass('active');
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

			$checkbox.prop('checked', true);
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
	if (hasUrlPracticeFilters) {
		loadPractices();
	}
	
	
});

jQuery(document).ready(function($) {
    function switchLkSlide(target) {
        $('.lk-slide').removeClass('active');
        $('.lk-slide[data-target="' + target + '"]').addClass('active');
        $('.sidebar-menu__item').removeClass('active');
        $('.sidebar-menu__item[data-target="' + target + '"]').addClass('active');
    }

    // Переключение между слайдами
    $('.sidebar-menu__item').on('click', function() {
        var target = $(this).data('target');
        switchLkSlide(target);
	});

    // Переход в "Мои данные" при клике по аватару в шапке ЛК
    $('.lk-login-btn').on('click', function() {
        switchLkSlide(1);
    });
    
    // Отправка формы через AJAX
	$('#profile-form').on('submit', function(e) {
		e.preventDefault();
		// В начале скрипта добавьте проверку
		console.log('AJAX URL:', yoga_ajax.ajax_url);
		console.log('Nonce:', yoga_ajax.nonce);
		console.log('User logged in:', yoga_ajax.user_logged_in);
		
		var $form = $(this);
		var $submitBtn = $form.find('.btn');
		var originalText = $submitBtn.find('span').text();
		var $notification = $('.lk-form-safe__text');
		
		// Валидация паролей
		var newPassword = $form.find('input[name="new_password"]').val();
		var repeatPassword = $form.find('input[name="repeat_password"]').val();
		
		if (newPassword && newPassword !== repeatPassword) {
			showNotification('Пароли не совпадают', 'error');
			return false;
		}
		
		// Показываем индикатор загрузки
		$submitBtn.find('span').text('Сохранение...');
		$submitBtn.prop('disabled', true);
		
		// Создаем FormData
		var formData = new FormData(this);
		formData.append('action', 'update_user_profile');
		formData.append('nonce', yoga_ajax.nonce);
		
		$.ajax({
			url: yoga_ajax.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					// Если загружен аватар — обновляем страницу (как принято)
					if (response.avatar_url) {
						location.reload();
						return;
					}
					// Показываем уведомление об успехе
					$notification.addClass('active').text(response.data);
					setTimeout(function() { $notification.removeClass('active'); }, 3000);
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
				$submitBtn.find('span').text(originalText);
				$submitBtn.prop('disabled', false);
			}
		});
	});
	
	// Функция показа уведомлений
	function showNotification(message, type = 'success') {
		// Удаляем предыдущие уведомления
		$('.practice-notification').remove();
		
		var $notification = $('<div class="practice-notification ' + type + '">' + message + '</div>');
		$('body').append($notification);
		
		// Позиционируем и показываем
		$notification.css({
			'position': 'fixed',
			'top': '20px',
			'right': '20px',
			'padding': '15px 20px',
			'background': type === 'success' ? '#00b894' : '#ff6b6b',
			'color': 'white',
			'border-radius': '5px',
			'z-index': '10000',
			'box-shadow': '0 4px 12px rgba(0,0,0,0.15)'
		});
		
		// Анимация
		$notification.hide().fadeIn(300);
		
		setTimeout(function() {
			$notification.fadeOut(300, function() {
				$(this).remove();
			});
		}, 5000);
	}
	
	// Загрузка аватара
	$(document).on('click', '.photo-input-custom', function(e) {
		e.stopPropagation(); // Останавливаем всплытие
		e.preventDefault(); // Отменяем действие по умолчанию
		
		setTimeout(function() {
			$('#avatar-upload').click();
		}, 50); // Небольшая задержка
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
		$photo.append($('<img>', { src: dataUrl, alt: '', class: 'avatar' }));
	});
	
	// Удаление аватара
	$(document).on('click', '.photo-input-delete', function() {
		if (confirm('Удалить аватар?')) {
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
				}
			});
		}
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
					//$this.toggleClass('active');
					$this.find('img, svg').toggleClass('active');
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
							if ($content.find('.no-favorites').length === 0) {
								$content.append('<div class="no-favorites">У вас пока нет избранных практик</div>');
							}
						}
						showNotification('Удалено из избранного');
						return;
					}
					showFavoriteModal(favoriteMessage);
				} else if (response.data && response.data.message) {
					showFavoriteModal(response.data.message);
				}
			},
			error: function(xhr) {
				if (xhr.status === 401) {
					showFavoriteModal('Для добавления в избранное авторизуйтесь');
				} else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					showFavoriteModal(xhr.responseJSON.data.message);
				} else {
					showFavoriteModal('Избранное не обновлено');
				}
			}
		});
	});
	
	function showFavoriteModal(message) {
		var $modal = $('.modal-default_favoritesucces');
		if (!$modal.length) {
			return;
		}
		$modal.find('.favorite-modal-message').text(message || 'Избранное обновлено');
		$('.body').addClass("body-fixed");
		$('.overlay').addClass("active");
		$('.modal').removeClass("active");
		$('.modal-login').removeClass("active");
		$modal.addClass("active");
	}
	
	// Функция показа уведомлений
	function showNotification(message) {
		var $notification = $('<div class="practice-notification">' + message + '</div>');
		$('body').append($notification);
		
		setTimeout(function() {
			$notification.fadeOut(300, function() {
				$(this).remove();
			});
		}, 3000);
	}
	
	// Обработка формы вопроса
	$('#question-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $submitBtn = $form.find('.btn');
		var originalText = $submitBtn.find('span').text();
		
		// Показываем индикатор загрузки
		$submitBtn.find('span').text('Отправка...');
		$submitBtn.prop('disabled', true);
		
		$.ajax({
			url: $form.attr('action'),
			type: 'POST',
			data: $form.serialize(),
			success: function(response) {
				// Показываем уведомление об успехе
				showNotification('Вопрос успешно отправлен!');
				$('.body').addClass("body-fixed");
				$('.overlay').addClass("active");
				$('.modal').removeClass("active");
				$('.modal-login').removeClass("active");
				$('.modal-default_formsucces').addClass("active");
				
				// Очищаем форму
				$form.find('textarea').val('');
				
				// Обновляем список вопросов
				setTimeout(function() {
					location.reload();
				}, 1500);
			},
			error: function() {
				showNotification('Ошибка при отправке вопроса', 'error');
				$submitBtn.find('span').text(originalText);
				$submitBtn.prop('disabled', false);
			}
		});
	});
	
	// Показать/скрыть дополнительные вопросы
	$('.show-more-questions').on('click', function() {
		var $hiddenItems = $('.lk-questions-item.hidden');
		var $btn = $(this);
		
		if ($btn.find('span.active').text() === 'Показать еще') {
			$hiddenItems.slideDown(300).removeClass('hidden');
			$btn.find('span').toggleClass('active');
			} else {
			$hiddenItems.slideUp(300).addClass('hidden');
			$btn.find('span').toggleClass('active');
		}
	});
	
	// Функция показа уведомлений
	function showNotification(message, type = 'success') {
		var $notification = $('<div class="practice-notification ' + type + '">' + message + '</div>');
		$('body').append($notification);
		
		// Анимация появления
		$notification.hide().fadeIn(300);
		
		setTimeout(function() {
			$notification.fadeOut(300, function() {
				$(this).remove();
			});
		}, 3000);
	}
	
	// Управление настройками подписки
	$('.lk-settings-item_action').on('click', function() {
		var target = $(this).data('target');
		$('.lk-settings__slide').removeClass('active');
		$('.lk-settings__slide[data-target="' + target + '"]').addClass('active');
	});
	
	$('.form-back').on('click', function() {
		var target = $(this).data('target');
		$('.lk-settings__slide').removeClass('active');
		$('.lk-settings__slide[data-target="' + target + '"]').addClass('active');
	});
	
	// Добавление новой карты
	$('#add-new-card').on('click', function() {
		$('.add-card-form').slideToggle();
	});
	
	$('.btn-cancel').on('click', function() {
		$('.add-card-form').slideUp();
	});

	function initStripeElementsForLk() {
		var $cardForm = $('#add-card-form');
		if (!$cardForm.length) {
			return;
		}

		if (typeof Stripe === 'undefined') {
			return;
		}

		if ($cardForm.data('stripeInitialized')) {
			return;
		}

		var stripeKey = ($cardForm.data('stripe-key') || '').toString().trim();
		if (!stripeKey) {
			return;
		}

		var stripe = Stripe(stripeKey);
		var elements = stripe.elements();
		var cardNumber = elements.create('cardNumber');
		var cardExpiry = elements.create('cardExpiry');
		var cardCvc = elements.create('cardCvc');

		cardNumber.mount('#card-number-element');
		cardExpiry.mount('#card-expiry-element');
		cardCvc.mount('#card-cvc-element');

		$cardForm.data('stripeInitialized', true);

		$cardForm.off('submit.lkStripe').on('submit.lkStripe', function(e) {
			e.preventDefault();

			stripe.createPaymentMethod({
				type: 'card',
				card: cardNumber,
				billing_details: {}
			}).then(function(result) {
				if (result.error) {
					showNotification(result.error.message, 'error');
					return;
				}

				$.ajax({
					url: yoga_ajax.ajax_url,
					type: 'POST',
					data: {
						action: 'add_payment_method',
						payment_method_id: result.paymentMethod.id,
						security: yoga_ajax.nonce
					},
					success: function(response) {
						if (response.success) {
							showNotification('Карта успешно добавлена');
							$('.add-card-form').slideUp();
							location.reload();
						} else {
							showNotification(response.data, 'error');
						}
					}
				});
			});
		});
	}

	initStripeElementsForLk();
	
	// Удаление карты
	$('.lk-settings-item__col-action-options').on('click', function(e) {
		e.stopPropagation();
		
		var cardId = $(this).data('card-id');
		var $cardItem = $(this).closest('.lk-settings-item');
		
		if (confirm('Вы уверены, что хотите удалить эту карту?')) {
			$.ajax({
				url: yoga_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'remove_payment_method',
					card_id: cardId,
					security: yoga_ajax.nonce
				},
				success: function(response) {
					if (response.success) {
						$cardItem.slideUp(300, function() {
							$(this).remove();
						});
						showNotification(response.data);
						} else {
						showNotification(response.data, 'error');
					}
				}
			});
		}
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
        
        var formData = $(this).serialize();
        var phone = $('input[name="phone"]').val();
		
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
                    $('[data-target="1"]').removeClass('active');
                    $('[data-target="2"]').addClass('active');
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
        
        var formData = $(this).serialize();
        
        $.ajax({
            type: 'POST',
            url: yoga_ajax.ajax_url,
            data: formData,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    var errorText = 'Неизвестная ошибка';
                    if (typeof response.data === 'string') {
                        errorText = response.data;
                    } else if (response.data && response.data.message) {
                        errorText = response.data.message;
                    }
                    alert('Ошибка при отправке комментария: ' + errorText);
                }
            },
            error: function() {
                alert('Ошибка соединения');
            }
        });
    });

    // Обработчики для кнопок ответа
    $(document).on('click', '.answer-btn', function() {
        if (!yoga_ajax.user_logged_in) {
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

    // Обработчики для кнопок обновления комментариев
    $(document).on('click', '.praktika-comment-item__edit .btn', function() {
        var commentId = $(this).closest('.praktika-comment-item__edit').attr('id').replace('edit-form-', '');
        updateComment(commentId);
    });
});

// Функции для работы с комментариями
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

function toggleEditForm(commentId) {
    var $form = jQuery('#edit-form-' + commentId);
    $form.toggleClass('hidden');
    
    // Скрываем другие открытые формы редактирования
    jQuery('.praktika-comment-item__edit').not($form).addClass('hidden');
}

function submitReply(parentId) {
    if (typeof yoga_ajax !== 'undefined' && !yoga_ajax.user_logged_in) {
        alert('Для ответа необходимо авторизоваться');
        return;
    }

    var content = jQuery('#reply-form-' + parentId + ' textarea').val();
    
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
    
    jQuery.post(yoga_ajax.ajax_url, data, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Ошибка при отправке ответа: ' + (response.data || 'Неизвестная ошибка'));
        }
    }).fail(function() {
        alert('Ошибка соединения');
    });
}

function updateComment(commentId) {
    var content = jQuery('#edit-form-' + commentId + ' textarea').val();
    
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
    
    jQuery.post(yoga_ajax.ajax_url, data, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Ошибка при обновлении комментария: ' + (response.data || 'Неизвестная ошибка'));
        }
    }).fail(function() {
        alert('Ошибка соединения');
    });
}

function deleteComment(commentId) {
    if (!confirm('Вы уверены, что хотите удалить комментарий?')) {
        return;
    }
    
    var data = {
        action: 'delete_comment',
        comment_id: commentId,
        security: yoga_ajax.nonce
    };
    
    jQuery.post(yoga_ajax.ajax_url, data, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Ошибка при удалении комментария: ' + (response.data || 'Неизвестная ошибка'));
        }
    }).fail(function() {
        alert('Ошибка соединения');
    });
}

// Закрытие форм при клике вне области
jQuery(document).on('click', function(e) {
    if (!jQuery(e.target).closest('.praktika-comment__answer, .answer-btn').length) {
        jQuery('.praktika-comment__answer').removeClass('active').addClass('hidden');
    }
    
    if (!jQuery(e.target).closest('.praktika-comment-item__edit, .your-comm__btn_edit').length) {
        jQuery('.praktika-comment-item__edit').addClass('hidden');
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