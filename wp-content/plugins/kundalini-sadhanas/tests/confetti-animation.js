/* Run with: node tests/confetti-animation.js */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../../../themes/yoga/assets/js/sadhana-confetti.js'), 'utf8');
const frames = [];
const bursts = [];
let resets = 0;
const confetti = (options) => bursts.push(options);
confetti.reset = () => { resets += 1; };
const window = {
  confetti,
  performance: { now: () => 0 },
  requestAnimationFrame: (callback) => { frames.push(callback); return callback; },
  cancelAnimationFrame: (callback) => {
    const index = frames.indexOf(callback);
    if (index >= 0) frames.splice(index, 1);
  },
  matchMedia: () => ({ matches: false }),
  yogaSadhanaConfettiSettings: {
    enabled: true,
    duration: 5400,
    intensity: 6,
    colors: ['#123456'],
    size: 1.5,
    speed: 50,
    direction: 'left'
  }
};
vm.runInNewContext(source, { window });

window.yogaSadhanaConfetti.start();
assert.equal(frames.length, 1, 'The effect should schedule a frame.');
frames.shift()(100);
assert.equal(bursts.length, 1, 'The left setting should emit only one burst per frame.');
assert.equal(bursts[0].origin.x, 0);
assert.equal(bursts[0].particleCount, 6);
assert.equal(bursts[0].startVelocity, 50);
assert.equal(bursts[0].scalar, 1.5);
assert.equal(bursts[0].colors[0], '#123456');

window.yogaSadhanaConfettiSettings.enabled = false;
window.yogaSadhanaConfetti.start();
assert.equal(frames.length, 0, 'Disabled effect should not schedule another frame.');

window.yogaSadhanaConfetti.start({ enabled: true, direction: 'center' });
assert.equal(frames.length, 1, 'Admin preview should work with the saved effect disabled.');
frames.shift()(100);
assert.equal(bursts.at(-1).origin.x, 0.5, 'Center launch should originate from the middle.');

window.matchMedia = () => ({ matches: true });
window.yogaSadhanaConfetti.start({ enabled: true });
assert.equal(frames.length, 0, 'Reduced motion should prevent a new animation.');
assert.ok(resets >= 4, 'Starting or stopping should clear previous confetti.');

window.matchMedia = () => ({ matches: false });
window.yogaSadhanaConfettiSettings = undefined;
window.yogaSadhanaConfetti.start();
frames.shift()(100);
assert.equal(bursts.at(-2).angle, 52, 'The default effect should still launch from the left.');
assert.equal(bursts.at(-1).angle, 128, 'The default effect should still launch from the right.');
assert.equal(bursts.at(-1).particleCount, 4, 'The default intensity should match the original effect.');
window.yogaSadhanaConfetti.stop();

const fields = Object.fromEntries([
  ['kundalini-confetti-preview', ''],
  ['kundalini-confetti-stop', ''],
  ['kundalini-confetti-intensity', '7'],
  ['kundalini-confetti-intensity-value', '4'],
  ['kundalini-confetti-duration', '3.5'],
  ['kundalini-confetti-size', '1.25'],
  ['kundalini-confetti-speed', '45'],
  ['kundalini-confetti-direction', 'right']
].map(([id, value]) => [id, {
  value,
  textContent: '',
  listeners: {},
  addEventListener(type, listener) { this.listeners[type] = listener; }
}]));
let previewOptions;
let stopped = false;
const previewWindow = {
  yogaSadhanaConfetti: {
    start(options) { previewOptions = options; },
    stop() { stopped = true; }
  }
};
const document = {
  getElementById(id) { return fields[id]; },
  querySelectorAll() { return [{ value: '#abcdef' }, { value: '#123456' }]; }
};
const previewSource = fs.readFileSync(path.join(__dirname, '../assets/js/confetti-preview.js'), 'utf8');
vm.runInNewContext(previewSource, { window: previewWindow, document });
fields['kundalini-confetti-intensity'].listeners.input();
assert.equal(fields['kundalini-confetti-intensity-value'].textContent, '7', 'Slider output should update while dragging.');
fields['kundalini-confetti-preview'].listeners.click();
assert.equal(previewOptions.intensity, 7);
assert.equal(previewOptions.duration, 3500);
assert.equal(previewOptions.size, 1.25);
assert.equal(previewOptions.speed, 45);
assert.equal(previewOptions.direction, 'right');
assert.equal(previewOptions.enabled, true, 'Preview should play even when the saved effect is disabled.');
fields['kundalini-confetti-stop'].listeners.click();
assert.equal(stopped, true);

console.log('Confetti animation checks passed.');
