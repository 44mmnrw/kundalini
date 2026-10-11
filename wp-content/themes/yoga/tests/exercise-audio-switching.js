// Run with: node wp-content/themes/yoga/tests/exercise-audio-switching.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const context = { document: { addEventListener() {}, createElement() { return {}; }, head: { appendChild() {} } }, window: {}, console };
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../assets/js/practice-player.js'), 'utf8'), context);
const events = {};
const selector = { dataset: {}, value: 'https://example.test/second.ogg', addEventListener(type, fn) { events[type] = fn; } };
let resets = 0;
let pauses = 0;
let loads = 0;
const media = { autoplay: true, playbackRate: 1, pause() { pauses++; }, load() { loads++; } };
const player = { speed: 1.5, pause() { pauses++; } };
const element = {
    dataset: { mediaSrc: 'https://example.test/first.mp3' },
    previousElementSibling: { querySelector() { return selector; } },
    querySelector() { return media; }
};
context.setupPracticeAudioTrackSelector(element, player, () => { resets++; });
const originalHandler = events.change;
context.setupPracticeAudioTrackSelector(element, player, () => { resets++; });
assert.equal(events.change, originalHandler, 'Repeated setup should not bind twice');
events.change();
assert.equal(resets, 1);
assert.equal(pauses, 1);
assert.equal(loads, 1);
assert.equal(media.src, selector.value);
assert.equal(element.dataset.mediaSrc, selector.value);
assert.equal(media.autoplay, false);
assert.equal(media.playbackRate, 1.5);
assert.equal(media.defaultPlaybackRate, 1.5);
events.change();
assert.equal(loads, 1, 'Selecting the current track should not reset it');
selector.value = '';
events.change();
assert.equal(loads, 1, 'Empty choice should not remove current audio');
selector.dataset = {};
selector.value = 'https://example.test/native.mp3';
context.setupPracticeAudioTrackSelector(element, null, () => {});
events.change();
assert.equal(loads, 2, 'Native player fallback should also switch tracks');
assert.equal(media.playbackRate, 1.5);
console.log('Exercise audio switching tests passed.');
