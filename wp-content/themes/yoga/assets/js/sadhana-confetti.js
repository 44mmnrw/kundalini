/**
 * Celebration shown after a sadhana is completed.
 *
 * Uses the core Kundalini Class / Yoga theme palette.
 */
(function (window) {
	'use strict';

	var COLORS = ['#9153e1', '#f8bdf6', '#e8ff57', '#1f1f1f'];
	var EFFECT_DURATION = 5400;
	var animationFrame = null;

	function stop() {
		if (animationFrame !== null) {
			window.cancelAnimationFrame(animationFrame);
			animationFrame = null;
		}

		if (typeof window.confetti === 'function' && typeof window.confetti.reset === 'function') {
			window.confetti.reset();
		}
	}

	function start() {
		stop();

		var reducedMotion = typeof window.matchMedia === 'function'
			&& window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (typeof window.confetti !== 'function' || reducedMotion) {
			return;
		}

		var commonOptions = {
			colors: COLORS,
			disableForReducedMotion: true,
			decay: 0.94,
			gravity: 0.55,
			scalar: 1.15,
			ticks: 420,
			zIndex: 1002
		};

		var finishAt = window.performance.now() + EFFECT_DURATION;
		var renderFrame = function (now) {
			window.confetti(Object.assign({}, commonOptions, {
				particleCount: 4,
				angle: 52,
				spread: 58,
				startVelocity: 38,
				origin: { x: 0, y: 0.7 }
			}));
			window.confetti(Object.assign({}, commonOptions, {
				particleCount: 4,
				angle: 128,
				spread: 58,
				startVelocity: 38,
				origin: { x: 1, y: 0.7 }
			}));

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
