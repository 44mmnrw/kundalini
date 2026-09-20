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
	return { width, height, left, top };
}

const defaultFrame = checkFrame(428, 351, 1600, 1000, 100, 50, 50);
assert.ok(defaultFrame.width >= 428 && defaultFrame.height >= 351, 'Default image must cover the frame');

const top = checkFrame(428, 351, 1600, 1000, 75, 50, 0);
const middle = checkFrame(428, 351, 1600, 1000, 75, 50, 50);
const bottom = checkFrame(428, 351, 1600, 1000, 75, 50, 100);
assert.ok(middle.width < 428 && middle.height < 351, 'Manual reduction must be able to add margins');
assert.ok(top.top < middle.top && middle.top < bottom.top, 'Reduced image must move smoothly within the frame');

checkFrame(733, 351, 800, 1200, 50, 50, 50);
checkFrame(320, 238, 1600, 1000, 200, 100, 0);
console.log('Practice gallery framing layout OK');
