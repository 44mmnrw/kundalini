/** Учёт выполнения: только фактический старт, без запросов при открытии страницы. */
(() => {
    'use strict';
    const config = window.yogaPracticeStatistics;
    if (!config || !config.practiceId || !config.userId) return;
    const storageKey = `yoga-practice:${config.userId}:${config.practiceId}`;
    const uuid = () => window.crypto?.randomUUID ? window.crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, character => {
        const value = Math.floor(Math.random() * 16);
        return (character === 'x' ? value : (value & 3) | 8).toString(16);
    });
    let saved = null;
    try { saved = JSON.parse(sessionStorage.getItem(storageKey)); } catch (_) {}
    let token = saved?.token && /^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/.test(saved.token) ? saved.token : uuid();
    let hasPlayed = Boolean(saved?.hasPlayed);
    let completed = Boolean(saved?.completed);
    let startRequest = null;
    let completionRequest = null;
    let controls = null;
    let errorMessage = '';
    const exercisesCompleted = new Set(Array.isArray(saved?.exercisesCompleted) ? saved.exercisesCompleted.filter(id => typeof id === 'string') : []);

    const persist = () => {
        try { sessionStorage.setItem(storageKey, JSON.stringify({token, hasPlayed, completed, exercisesCompleted:[...exercisesCompleted]})); } catch (_) {}
    };
    const render = message => {
        if (message !== undefined) errorMessage = message;
        if (!controls) return;
        controls.hidden = !hasPlayed;
        controls.querySelector('button').disabled = !hasPlayed || completed || Boolean(completionRequest);
        const status = controls.querySelector('[role="status"]');
        if (errorMessage) status.textContent = errorMessage;
        else status.textContent = completed ? 'Выполнение сохранено.' : 'После выполнения можно отметить практику завершённой.';
    };
    const post = async (event, sessionToken) => {
        const body = new URLSearchParams({action:'yoga_practice_activity', nonce:config.nonce, practice_id:String(config.practiceId), session_key:sessionToken, event});
        const response = await fetch(config.ajaxUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'}, body});
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.data?.message || 'Не удалось сохранить прогресс. Попробуйте ещё раз.');
        return result.data;
    };
    const start = () => {
        if (completed) {
            token = uuid();
            completed = false;
            startRequest = null;
            exercisesCompleted.clear();
        }
        hasPlayed = true;
        persist();
        if (startRequest) return startRequest;
        const requestedToken = token;
        startRequest = post('start', requestedToken).then(data => {
            if (token === requestedToken) {
                completed = Boolean(data.completed);
                persist();
                render('');
            }
            return true;
        }).catch(error => {
            if (token === requestedToken) { startRequest = null; render(error.message); }
            return false;
        });
        render('');
        return startRequest;
    };
    const complete = () => {
        if (!hasPlayed || completed) return Promise.resolve(false);
        if (completionRequest) return completionRequest;
        const requestedToken = token;
        completionRequest = (async () => {
            if (!await (startRequest || start())) return false;
            const result = await post('complete', requestedToken);
            if (token === requestedToken) { completed = Boolean(result.completed); persist(); }
            return completed;
        })().catch(error => { render(error.message); return false; }).finally(() => {
            completionRequest = null;
            render();
        });
        render('');
        return completionRequest;
    };
    const exerciseComplete = exerciseId => {
        if (!hasPlayed || completed || !exerciseId) return;
        exercisesCompleted.add(String(exerciseId));
        persist();
        const exercises = [...document.querySelectorAll('#section-praktika .praktika-exercise[data-exercise-id]')];
        if (exercises.length && exercises.every(exercise => exercisesCompleted.has(exercise.dataset.exerciseId))) complete();
    };
    window.yogaPracticeActivity = {start, complete, exerciseComplete};
    const initialize = () => {
        controls = document.querySelector('[data-practice-activity]');
        if (controls) { controls.querySelector('button').addEventListener('click', complete); render(); }
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, {once:true});
    else initialize();
})();
