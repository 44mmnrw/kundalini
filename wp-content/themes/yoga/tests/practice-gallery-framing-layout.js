const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const document = {
	createElement: () => ({}),
	head: { appendChild() {} },
	addEventListener() {}
};
const context = vm.createContext({ document, window: {} });
vm.runInContext(fs.readFileSync(path.join(__dirname, '../assets/js/practice-player.js'), 'utf8'), context);

function checkFrame(frameWidth, frameHeight, imageWidth, imageHeight, zoom, x, y) {
	const properties = {
		'--exercise-image-x': x + '%',
		'--exercise-image-y': y + '%'
	};
	const style = {
		getPropertyValue(name) { return properties[name] || ''; },
		setProperty(name, value) { properties[name] = value; }
	};
	const image = {
		naturalWidth: imageWidth,
		naturalHeight: imageHeight,
		dataset: { practiceZoom: String(zoom) },
		style,
		closest: () => ({ clientWidth: frameWidth, clientHeight: frameHeight }),
		classList: { add() {} }
	};
	context.updatePracticeImageFraming(image);
	const width = parseFloat(style.width);
	const height = parseFloat(style.height);
	const left = parseFloat(style.left);
	const top = parseFloat(style.top);
	const epsilon = 0.02;
	assert.ok(width >= frameWidth - epsilon || height >= frameHeight - epsilon, 'Image must touch a pair of frame edges');
	return { width, height, left, top };
}

const top = checkFrame(428, 351, 1600, 1000, 75, 50, 0).top;
const middle = checkFrame(428, 351, 1600, 1000, 75, 50, 50).top;
const bottom = checkFrame(428, 351, 1600, 1000, 75, 50, 100).top;
assert.ok(top < middle && middle < bottom, 'Vertical movement must be continuous between edges');
checkFrame(428, 351, 1600, 1000, 50, 100, 100);
checkFrame(733, 351, 800, 1200, 60, 50, 50);
checkFrame(320, 238, 1600, 1000, 90, 100, 0);
console.log('Practice gallery framing layout OK');
