/**
 * Celebration shown after a sadhana is completed.
 *
 * Uses the palette and motion settings configured in the Sadhanas plugin.
 */
(function (window) {
	'use strict';

	var DEFAULTS = {
		enabled: true,
		duration: 5400,
		intensity: 4,
		colors: ['#9153e1', '#f8bdf6', '#e8ff57', '#1f1f1f'],
		size: 1.15,
		speed: 38,
		direction: 'both'
	};
	var animationFrame = null;

	function boundedNumber(value, fallback, minimum, maximum) {
		var number = Number(value);
		return Number.isFinite(number) ? Math.max(minimum, Math.min(maximum, number)) : fallback;
	}

	function optionsForRun(overrides) {
		var options = Object.assign({}, DEFAULTS, window.yogaSadhanaConfettiSettings || {}, overrides || {});
		options.duration = boundedNumber(options.duration, DEFAULTS.duration, 1000, 15000);
		options.intensity = Math.round(boundedNumber(options.intensity, DEFAULTS.intensity, 1, 8));
		options.size = boundedNumber(options.size, DEFAULTS.size, 0.5, 2);
		options.speed = boundedNumber(options.speed, DEFAULTS.speed, 15, 70);
		options.colors = Array.isArray(options.colors)
			? options.colors.filter(function (color) { return /^#[0-9a-f]{6}$/i.test(color); }).slice(0, 8)
			: [];
		if (!options.colors.length) {
			options.colors = DEFAULTS.colors;
		}
		if (['both', 'left', 'right', 'center'].indexOf(options.direction) === -1) {
			options.direction = DEFAULTS.direction;
		}
		return options;
	}

	function stop() {
		if (animationFrame !== null) {
			window.cancelAnimationFrame(animationFrame);
			animationFrame = null;
		}

		if (typeof window.confetti === 'function' && typeof window.confetti.reset === 'function') {
			window.confetti.reset();
		}
	}

	function start(overrides) {
		stop();

		var options = optionsForRun(overrides);
		var reducedMotion = typeof window.matchMedia === 'function'
			&& window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (!options.enabled || typeof window.confetti !== 'function' || reducedMotion) {
			return;
		}

		var commonOptions = {
			colors: options.colors,
			disableForReducedMotion: true,
			decay: 0.94,
			gravity: 0.55,
			scalar: options.size,
			ticks: 420,
			zIndex: 1002
		};

		var finishAt = window.performance.now() + options.duration;
		var renderFrame = function (now) {
			var launchOptions = {
				particleCount: options.intensity,
				spread: 58,
				startVelocity: options.speed
			};
			if (options.direction === 'both' || options.direction === 'left') {
				window.confetti(Object.assign({}, commonOptions, launchOptions, {
					angle: 52,
					origin: { x: 0, y: 0.7 }
				}));
			}
			if (options.direction === 'both' || options.direction === 'right') {
				window.confetti(Object.assign({}, commonOptions, launchOptions, {
					angle: 128,
					origin: { x: 1, y: 0.7 }
				}));
			}
			if (options.direction === 'center') {
				window.confetti(Object.assign({}, commonOptions, launchOptions, {
					angle: 90,
					origin: { x: 0.5, y: 0.95 }
				}));
			}

			if (now < finishAt) {
				animationFrame = window.requestAnimationFrame(renderFrame);
			} else {
				animationFrame = null;
			}
		};

		animationFrame = window.requestAnimationFrame(renderFrame);
	}

	window.yogaSadhanaConfetti = {
		start: start,
		stop: stop
	};
}(window));
