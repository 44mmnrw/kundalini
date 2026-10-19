// No network or real clocks: execute the production event handlers against a small DOM fixture.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
function element(dataset = {}) {
    const classes = new Set();
    return { dataset, style: {}, events: {}, textContent: '',
        classList: { add: c => classes.add(c), remove: c => classes.delete(c), contains: c => classes.has(c), toggle(c, force) { if (force === undefined) force = !classes.has(c); force ? classes.add(c) : classes.delete(c); } },
        addEventListener(name, cb) { this.events[name] = cb; },
        click() { this.events.click.call(this, { preventDefault() {} }); }
    };
}
function exercise(id, versionType = 'base') {
    const span = element(), start = element(), preset = element({ duration: '180' }), timer = element(), display = element();
    start.querySelector = () => span;
    const lookup = { '.timer-display': display, '.timer-play-pause': start, '.exercise-timer': timer };
    const version = element({ version: versionType });
    version.querySelector = selector => lookup[selector] || null;
    version.querySelectorAll = selector => selector === '.timer-preset' ? [preset] : [];
    const node = element({ exerciseId: id });
    node.querySelectorAll = selector => selector === '.exercise-item' ? [version] : [];
    return { node, start, preset, span, display };
}
const a = exercise('1'), b = exercise('2');
let nextInterval = 0;
const intervals = new Map();
const context = { console, Plyr: function() {}, document: { createElement: () => element(), head: { appendChild() {} }, addEventListener() {}, querySelectorAll(selector) { return selector === '.praktika-exercise' ? [a.node, b.node] : []; } },
    window: { requestAnimationFrame: fn => fn() }, setInterval(fn) { intervals.set(++nextInterval, fn); return nextInterval; }, clearInterval(id) { intervals.delete(id); } };
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../assets/js/practice-player.js'), 'utf8'), context);
context.createAudioFullscreenContainer = () => {};
context.initializePracticeSystem();
a.preset.click(); a.start.click();
assert.equal(a.span.textContent, 'Пауза');
for (const tick of intervals.values()) tick();
assert.equal(a.display.textContent, '2:59');
b.preset.click(); b.start.click();
assert.equal(a.span.textContent, 'Старт', 'switching timers restores the stopped button');
assert.equal(a.preset.classList.contains('disabled'), false, 'duration selection unlocked');
assert.equal(intervals.size, 1, 'only the second timer runs');
a.preset.dataset.duration = '60'; a.preset.click();
assert.equal(a.display.textContent, '1:00', 'stopped timer accepts another duration');
context.stopAllTimersAndPlayers();
assert.equal(b.span.textContent, 'Старт');
assert.equal(intervals.size, 0);

// Run the sole production blog handler, including two full expand/collapse cycles.
const source = fs.readFileSync(path.join(__dirname, '../assets/js/script.js'), 'utf8');
const begin = source.indexOf("document.querySelectorAll('.blog-articles__more .btn,");
const end = source.indexOf('// Обработка радио-кнопок', begin);
const card = element(); card.classList.add('hidden');
const labels = [element(), element()]; labels[0].classList.add('active');
const button = element(), attributes = {};
button.getAttribute = key => attributes[key];
button.setAttribute = (key, value) => { attributes[key] = value; };
button.closest = () => ({ querySelectorAll: () => [card] });
button.querySelectorAll = () => labels;
vm.runInNewContext(source.slice(begin, end), { document: { querySelectorAll: () => [button] } });
for (let cycle = 0; cycle < 2; cycle++) {
    button.click(); assert.equal(card.classList.contains('hidden'), false); assert.equal(labels[1].classList.contains('active'), true);
    button.click(); assert.equal(card.classList.contains('hidden'), true); assert.equal(labels[0].classList.contains('active'), true);
}
console.log('PASS: switching timers, duration unlock, single running timer, blog expand/collapse.');
