(function (document, window) {
	'use strict';

	var previewButton = document.getElementById('kundalini-confetti-preview');
	var stopButton = document.getElementById('kundalini-confetti-stop');
	var intensityInput = document.getElementById('kundalini-confetti-intensity');
	var intensityValue = document.getElementById('kundalini-confetti-intensity-value');
	if (!previewButton || !intensityInput || !window.yogaSadhanaConfetti) {
		return;
	}

	intensityInput.addEventListener('input', function () {
		intensityValue.textContent = intensityInput.value;
	});

	previewButton.addEventListener('click', function () {
		var colors = Array.prototype.map.call(document.querySelectorAll('.kundalini-confetti-color'), function (input) {
			return input.value;
		});
		window.yogaSadhanaConfetti.start({
			enabled: true,
			duration: parseFloat(document.getElementById('kundalini-confetti-duration').value) * 1000,
			intensity: Number(intensityInput.value),
			colors: colors,
			size: parseFloat(document.getElementById('kundalini-confetti-size').value),
			speed: parseFloat(document.getElementById('kundalini-confetti-speed').value),
			direction: document.getElementById('kundalini-confetti-direction').value
		});
	});

	stopButton.addEventListener('click', function () {
		window.yogaSadhanaConfetti.stop();
	});
}(document, window));
