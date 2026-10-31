// node tests/practice-statistics.js — события выполнения без браузера и сети.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/practice-statistics.js'), 'utf8');
let sequence = 0;

function fixture({storage = new Map(), sessions = new Map(), guest = false, denyStorage = false} = {}) {
    const requests = [];
    const button = {disabled: true, addEventListener() {}};
    const status = {textContent: ''};
    const controls = {hidden: true, querySelector: selector => selector === 'button' ? button : status};
    const test = {requests, storage, sessions, button, status, controls, failNext: null};
    const context = vm.createContext({
        URLSearchParams,
        window: {
            yogaPracticeStatistics: guest ? undefined : {ajaxUrl: '/ajax', nonce: 'nonce', userId: 7, practiceId: 42},
            crypto: {randomUUID: () => `${String(++sequence).padStart(8, '0')}-0000-4000-8000-000000000000`}
        },
        document: {
            readyState: 'complete',
            querySelector: () => controls,
            querySelectorAll: () => ['first', 'second'].map(id => ({dataset: {exerciseId: id}}))
        },
        sessionStorage: {
            getItem: key => { if (denyStorage) throw Error('Denied'); return storage.get(key); },
            setItem: (key, value) => { if (denyStorage) throw Error('Denied'); storage.set(key, value); }
        },
        fetch: async (url, options) => {
            const data = Object.fromEntries(options.body);
            requests.push(data);
            assert.equal(options.credentials, 'same-origin');
            assert.equal(data.practice_id, '42');
            assert.equal(data.nonce, 'nonce');
            if (test.failNext === data.event) {
                test.failNext = null;
                return {ok: false, json: async () => ({success: false, data: {message: 'Ошибка сохранения'}})};
            }
            if (!sessions.has(data.session_key)) {
                assert.equal(data.event, 'start', 'completion must follow an actual start');
                sessions.set(data.session_key, false);
            }
            if (data.event === 'complete') sessions.set(data.session_key, true);
            return {ok: true, json: async () => ({success: true, data: {completed: sessions.get(data.session_key)}})};
        }
    });
    vm.runInContext(source, context);
    test.api = context.window.yogaPracticeActivity;
    return test;
}

const flush = () => new Promise(resolve => setImmediate(resolve));
(async () => {
    const first = fixture();
    assert.equal(first.requests.length, 0, 'page view is not a start');
    assert.equal(first.controls.hidden, true);
    assert.equal(await first.api.complete(), false);
    assert.equal(first.requests.length, 0, 'cannot finish without playing');
    await Promise.all([first.api.start(), first.api.start(), first.api.start()]);
    assert.equal(first.requests.length, 1, 'play/resume/timer events share one session');
    assert.equal(first.controls.hidden, false);
    assert.equal(first.button.disabled, false);
    first.api.exerciseComplete('first');
    first.api.exerciseComplete('first');
    await flush();
    assert.equal(first.requests.length, 1, 'one exercise or duplicate end is not the whole practice');

    const resumed = fixture({storage: first.storage, sessions: first.sessions});
    assert.equal(resumed.requests.length, 0, 'reload sends no page-view event');
    resumed.api.exerciseComplete('second');
    await flush();
    assert.deepEqual(resumed.requests.map(item => item.event), ['start', 'complete']);
    assert.equal(resumed.requests[0].session_key, first.requests[0].session_key, 'reload resumes the same server session');
    assert.equal(resumed.button.disabled, true);
    assert.match(resumed.status.textContent, /сохранено/);
    await resumed.api.complete();
    assert.equal(resumed.requests.length, 2, 'duplicate finish does not send another request');
    await resumed.api.start();
    assert.notEqual(resumed.requests[2].session_key, resumed.requests[0].session_key, 'a new performance after completion has a new token');
    resumed.api.exerciseComplete('second');
    await flush();
    assert.equal(resumed.requests.length, 3, 'completed exercises from the prior performance are cleared');
    await Promise.all([resumed.api.complete(), resumed.api.complete()]);
    assert.equal(resumed.requests.length, 4, 'manual and automatic finish cannot duplicate a completion');

    const retry = fixture();
    retry.failNext = 'start';
    assert.equal(await retry.api.start(), false);
    assert.match(retry.status.textContent, /Ошибка/);
    await retry.api.complete();
    assert.deepEqual(retry.requests.map(item => item.event), ['start', 'start', 'complete']);
    assert.equal(new Set(retry.requests.map(item => item.session_key)).size, 1, 'a failed request retries the same session');
    assert.match(retry.status.textContent, /сохранено/);

    const failedFinish = fixture();
    await failedFinish.api.start();
    failedFinish.failNext = 'complete';
    assert.equal(await failedFinish.api.complete(), false);
    assert.equal(failedFinish.status.textContent, 'Ошибка сохранения', 'the completion error remains visible');
    assert.equal(failedFinish.button.disabled, false, 'user can retry a failed completion');
    assert.equal(await failedFinish.api.complete(), true);
    assert.equal(failedFinish.requests.filter(item => item.event === 'start').length, 1);
    assert.equal(failedFinish.sessions.size, 1);

    const privateMode = fixture({denyStorage: true});
    await privateMode.api.start();
    await privateMode.api.complete();
    assert.equal(privateMode.requests.length, 2, 'storage restrictions do not break counting');
    const guest = fixture({guest: true});
    assert.equal(guest.api, undefined);
    assert.equal(guest.requests.length, 0);
    console.log('PASS: real-start semantics, pause/resume deduplication, reload, all-exercise completion, new performances, retries and guests.');
})().catch(error => { console.error(error); process.exitCode = 1; });
