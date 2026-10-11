// Run with: node wp-content/themes/yoga/tests/exercise-audio-volume.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function element() {
    return {
        dataset: {}, attributes: {}, events: {}, children: [], classes: new Set(),
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        append(child) { this.children.push(child); },
        addEventListener(name, callback) { this.events[name] = callback; },
        focus() { this.focused = true; }
    };
}
const button = element();
const slider = element();
const volume = element();
volume.querySelector = selector => selector.includes('input') ? slider : button;
volume.contains = target => [volume, button, slider, ...volume.children].includes(target);
volume.classList = { toggle(name, enabled) { enabled ? volume.classes.add(name) : volume.classes.delete(name); } };
const document = element();
document.createElement = element;
document.head = { appendChild() {} };
const context = { document, window: {}, console };
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../assets/js/practice-player.js'), 'utf8'), context);
const player = { volume: 0.6, muted: false, events: {}, on(name, callback) { this.events[name] = callback; } };
const controls = { querySelector() { return volume; } };
context.setupPracticeAudioVolume(controls, player);
const popup = volume.children[0];
assert.equal(popup.hidden, true);
assert.equal(popup.children[0], slider, 'Keep the slider with its existing Plyr bindings');
let prevented = false;
let stopped = false;
const click = () => button.events.click({ preventDefault() { prevented = true; }, stopImmediatePropagation() { stopped = true; } });
click();
assert.equal(popup.hidden, false);
assert.equal(button.attributes['aria-expanded'], 'true');
assert.equal(slider.focused, true);
assert.equal(prevented && stopped, true, 'Do not invoke the original mute action');
assert.equal(player.volume, 0.6);
assert.equal(player.muted, false);
document.events.click({ target: slider });
assert.equal(popup.hidden, false, 'Changing the volume must not close the slider');
click();
assert.equal(popup.hidden, true);
click();
volume.events.keydown({ key: 'Escape', preventDefault() {}, stopPropagation() {} });
assert.equal(popup.hidden, true);
assert.equal(button.focused, true);
click();
document.events.click({ target: {} });
assert.equal(popup.hidden, true);
click();
volume.events.focusout({ relatedTarget: slider });
assert.equal(popup.hidden, false);
volume.events.focusout({ relatedTarget: null });
assert.equal(popup.hidden, true);
player.volume = 0;
player.events.volumechange();
assert.equal(volume.classes.has('is-muted'), true);
context.setupPracticeAudioVolume(controls, player);
assert.equal(volume.children.length, 1, 'Do not initialize the popup twice');
console.log('Exercise audio volume tests passed.');
