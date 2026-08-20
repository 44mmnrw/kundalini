/**
 * Клиентский модуль: practice player.
 *
 * @package Yoga
 */

function getKinescopePlayerFactory(timeout = 10000) {
    const existingFactory = window.Kinescope && window.Kinescope.IframePlayer;
    if (existingFactory && typeof existingFactory.create === 'function') {
        return Promise.resolve(existingFactory);
    }
    if (window.kinescopeIframeApiPromise) {
        return window.kinescopeIframeApiPromise;
    }

    const startedAt = Date.now();

    window.kinescopeIframeApiPromise = new Promise((resolve, reject) => {
        const checkFactory = () => {
            const factory = window.Kinescope && window.Kinescope.IframePlayer;
            if (factory && typeof factory.create === 'function') {
                resolve(factory);
                return;
            }

            if (Date.now() - startedAt >= timeout) {
                reject(new Error('Kinescope IFrame API is not available'));
                return;
            }

            setTimeout(checkFactory, 100);
        };

        let script = document.getElementById('kinescope-iframe-api');
        if (!script) {
            script = document.createElement('script');
            script.id = 'kinescope-iframe-api';
            script.src = 'https://player.kinescope.io/latest/iframe.player.js';
            script.async = true;
            script.addEventListener('error', () => reject(new Error('Failed to load Kinescope IFrame API')));
            document.head.appendChild(script);
        }

        checkFactory();
    });

    return window.kinescopeIframeApiPromise;
}

function createKinescopePlayerAdapter(playerElement, callbacks = {}) {
    const target = playerElement.querySelector('.kinescope-player-container');
    const videoUrl = playerElement.dataset.mediaSrc || '';
    let apiPlayer = null;
    let playing = false;

    const adapter = {
        isKinescope: true,
        get playing() {
            return playing;
        },
        play() {
            playing = true;
            return readyPromise.then(player => player ? player.play() : undefined);
        },
        pause() {
            playing = false;
            return readyPromise.then(player => player ? player.pause() : undefined);
        },
        stop() {
            playing = false;
            return readyPromise.then(player => player ? player.stop() : undefined);
        },
        seekTo(time) {
            return readyPromise.then(player => player ? player.seekTo(time) : undefined);
        }
    };

    const readyPromise = getKinescopePlayerFactory()
        .then(factory => factory.create(target.id, {
            url: videoUrl,
            size: { width: '100%', height: '100%' },
            behavior: {
                autoPlay: false,
                playsInline: true
            }
        }))
        .then(player => {
            apiPlayer = player;

            player.on(player.Events.Play, () => {
                playing = true;
                if (typeof callbacks.onPlay === 'function') callbacks.onPlay();
            });
            player.on(player.Events.Pause, () => {
                playing = false;
                if (typeof callbacks.onPause === 'function') callbacks.onPause();
            });
            player.on(player.Events.Ended, () => {
                playing = false;
                if (typeof callbacks.onEnded === 'function') callbacks.onEnded();
            });

            if (playerElement.dataset.restrictScrub === 'true') {
                player.on(player.Events.Seeked, async () => {
                    const currentTime = await player.getCurrentTime();
                    if (currentTime > 60) await player.seekTo(60);
                });
            }

            player.on(player.Events.Error, event => {
                console.warn('Kinescope player error', event && event.data ? event.data : event);
                playing = false;
                if (typeof callbacks.onError === 'function') callbacks.onError();
            });

            return apiPlayer;
        })
        .catch(error => {
            playing = false;
            playerElement.classList.add('exercise-player--error');
            console.warn('Kinescope player initialization failed', error);
            if (typeof callbacks.onError === 'function') callbacks.onError();
            return null;
        });

    return adapter;
}

function initializePracticeSystem() {

    if (window.practiceSystemInitialized) {
        return;
    }

    if (typeof Plyr === 'undefined') {
        console.log('Plyr not loaded yet, retrying in 100ms...');
        setTimeout(initializePracticeSystem, 100);
        return;
    }

    console.log('Plyr loaded successfully, initializing players...');


    window.activeTimers = {};
    window.activePlayers = {};
    window.isFullscreenMode = false;
    window.currentFullscreenExercise = null;


    createAudioFullscreenContainer();


    const plyrAudioOptions = {
        controls: [
            'play-large',
            'play',
            'progress',
            'current-time',
            'duration',
            'mute',
            'volume',
            'settings',
            'pip',
            'fullscreen'
        ],
        hideControls: false,
        autoplay: false,
        debug: false
    };


    window.practiceSystemInitialized = true;


    document.querySelectorAll('.exercise-switches__item').forEach(switchItem => {
        switchItem.addEventListener('click', function() {
            const targetVersion = this.dataset.target;
            const exercise = this.closest('.praktika-exercise');
            const exerciseId = exercise.dataset.exerciseId;


            stopAllTimersAndPlayers();








             exercise.querySelectorAll('.exercise-item').forEach(item => {
                item.classList.remove('active');
            });
            exercise.querySelector(`.exercise-item[data-version="${targetVersion}"]`).classList.add('active');


            if (window.isFullscreenMode && window.currentFullscreenExercise === exerciseId) {
                const activeExercise = exercise.querySelector('.exercise-item.active');
                const activePlayer = activeExercise.querySelector('.exercise-player');
                updateAudioFullscreen(exercise, activePlayer);
            }
        });
    });


    document.querySelectorAll('.praktika-exercise').forEach(exercise => {
        const exerciseId = exercise.dataset.exerciseId;

        exercise.querySelectorAll('.exercise-item').forEach(version => {
            try {

            const versionType = version.dataset.version;
            const timerDisplay = version.querySelector('.timer-display');
            const playPauseBtn = version.querySelector('.timer-play-pause');
            const resetBtn = version.querySelector('.timer-reset');
            const presetBtns = version.querySelectorAll('.timer-preset');
            const timerElement = version.querySelector('.exercise-timer');
            const playerElement = version.querySelector('.exercise-player');
            const endSignalSrc = version.dataset.endSignal === 'true' ? (version.dataset.endSignalSrc || '') : '';

            let timerInterval = null;
            let remainingTime = 0;
            let isPlaying = false;
            let player = null;
            let suppressAutoPlayUntil = 0;
            let endSignalAudio = null;
            let endSignalPrimed = false;
            const initialDuration = parseDuration(timerDisplay?.textContent);
            const presetDefaultDuration = (() => {
                const firstPreset = presetBtns && presetBtns.length ? presetBtns[0] : null;
                const fromPreset = parseInt(firstPreset?.dataset?.duration, 10);
                return Number.isNaN(fromPreset) ? 0 : fromPreset;
            })();
            const fallbackDuration = initialDuration > 0 ? initialDuration : 180;
            let selectedDuration = presetDefaultDuration > 0 ? presetDefaultDuration : fallbackDuration;

            function parseDuration(value) {
                if (!value || typeof value !== 'string') return 0;
                const [minutesText, secondsText] = value.trim().split(':');
                const minutes = parseInt(minutesText, 10);
                const seconds = parseInt(secondsText, 10);
                if (Number.isNaN(minutes) || Number.isNaN(seconds)) return 0;
                return (minutes * 60) + seconds;
            }

            if (endSignalSrc) {
                endSignalAudio = new Audio(endSignalSrc);
                endSignalAudio.preload = 'auto';
            }

            function primeEndSignal() {
                if (!endSignalAudio || endSignalPrimed) {
                    return;
                }

                endSignalAudio.muted = true;
                const playPromise = endSignalAudio.play();
                if (playPromise && typeof playPromise.then === 'function') {
                    playPromise
                        .then(() => {
                            endSignalAudio.pause();
                            endSignalAudio.currentTime = 0;
                            endSignalAudio.muted = false;
                            endSignalPrimed = true;
                        })
                        .catch(() => {
                            endSignalAudio.muted = false;
                        });
                } else {
                    endSignalAudio.pause();
                    endSignalAudio.currentTime = 0;
                    endSignalAudio.muted = false;
                    endSignalPrimed = true;
                }
            }

            function playEndSignal() {
                if (!endSignalAudio) {
                    return;
                }

                try {
                    endSignalAudio.pause();
                    endSignalAudio.currentTime = 0;
                    endSignalAudio.muted = false;
                    endSignalAudio.volume = 1;
                    const playPromise = endSignalAudio.play();
                    if (playPromise && typeof playPromise.catch === 'function') {
                        playPromise.catch(() => {});
                    }
                } catch (err) {}
            }

            if (playerElement) {
                const isKinescope = playerElement.dataset.mediaProvider === 'kinescope';
                const mediaElement = playerElement.querySelector('audio, video, [data-plyr-provider="youtube"]');
                if (isKinescope) {
                    player = createKinescopePlayerAdapter(playerElement, {
                        onPlay: () => {
                            if (Date.now() < suppressAutoPlayUntil) {
                                player.pause();
                                return;
                            }
                            if (!isPlaying) {
                                primeEndSignal();
                                startTimer();
                            }
                        },
                        onPause: () => {
                            if (isPlaying) pauseTimer();
                        },
                        onEnded: () => {
                            stopTimer();
                            goToNextExercise(exercise);
                        },
                        onError: () => {
                            if (isPlaying) pauseTimer();
                        }
                    });
                    window.activePlayers[`${exerciseId}_${versionType}`] = player;
                } else if (mediaElement) {
                    const isVideo = mediaElement.tagName === 'VIDEO' || mediaElement.dataset.plyrProvider === 'youtube';
                    const playerOptions = isVideo
                        ? {
                            controls: ['play-large'],
                            clickToPlay: true,
                            hideControls: false
                        }
                        : plyrAudioOptions;
                    player = new Plyr(mediaElement, playerOptions);


                    window.activePlayers[`${exerciseId}_${versionType}`] = player;


                    const isRestricted = playerElement.dataset.restrictScrub === 'true';
                    if (isRestricted) {

                        player.on('seeked', function(event) {
                            const details = event.detail;

                            if (details.plyr.currentTime > 60) {
                                player.currentTime = Math.min(60, player.duration);
                            }
                        });
                    }

                    player.on('play', () => {
                        if (Date.now() < suppressAutoPlayUntil) {
                            player.pause();
                            return;
                        }


                        if (!isPlaying) {
                            primeEndSignal();
                            startTimer();
                        }
                    });

                    player.on('pause', () => {
                        if (isPlaying) {
                            pauseTimer();
                        }
                    });

                    player.on('ended', () => {
                        stopTimer();

                        goToNextExercise(exercise);
                    });


                    player.on('enterfullscreen', () => {
                        if (player.media.tagName === 'AUDIO') {

                            player.exitFullscreen();
                            openAudioFullscreen(exercise, playerElement);
                            return false;
                        }
                    });

                    player.on('exitfullscreen', () => {
                        if (window.isFullscreenMode && player.media.tagName === 'AUDIO') {
                            closeAudioFullscreen();
                        }
                    });


                    const blockUnexpectedPlay = (event) => {
                        if (Date.now() < suppressAutoPlayUntil) {
                            if (event && typeof event.preventDefault === 'function') {
                                event.preventDefault();
                            }
                            try { mediaElement.pause(); } catch (e) {}
                            try { mediaElement.currentTime = 0; } catch (e) {}
                            if (player) {
                                try { player.pause(); } catch (e) {}
                            }
                        }
                    };

                    mediaElement.addEventListener('play', blockUnexpectedPlay);
                    mediaElement.addEventListener('playing', blockUnexpectedPlay);
                }
            }


            let durationButtonsLocked = false;


            function toggleDurationButtons(locked) {
                durationButtonsLocked = locked;
                if (presetBtns) {
                    presetBtns.forEach(btn => {
                        if (locked) {
                            btn.classList.add('disabled');
                            btn.style.opacity = '0.5';
                            btn.style.cursor = 'not-allowed';
                        } else {
                            btn.classList.remove('disabled');
                            btn.style.opacity = '1';
                            btn.style.cursor = 'pointer';
                        }
                    });
                }
            }

            function startTimer() {
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }


                if (remainingTime <= 0) {
                    remainingTime = selectedDuration > 0 ? selectedDuration : initialDuration;
                    updateTimerDisplay();
                }

                isPlaying = true;
                document.querySelectorAll('.exercise-timer.timer-is-running').forEach(timer => {
                    timer.classList.remove('timer-is-running');
                });
                if (timerElement) {
                    timerElement.classList.add('timer-is-running');
                }
                if (playPauseBtn) {
                    playPauseBtn.querySelector('span').textContent = 'Пауза';
                }


                toggleDurationButtons(true);


                stopAllTimersAndPlayers(exerciseId, versionType);

                timerInterval = setInterval(() => {
                    if (!isPlaying) {
                        return;
                    }

                    if (remainingTime > 0) {
                        remainingTime--;
                        updateTimerDisplay();


                        if (window.isFullscreenMode && window.currentFullscreenExercise === exerciseId) {
                            updateFullscreenTimer();
                        }
                    } else {
                        playEndSignal();
                        stopTimer();
                        if (player) player.pause();


                        goToNextExercise(exercise);
                    }
                }, 1000);


                window.activeTimers[exerciseId] = {
                    interval: timerInterval,
                    version: versionType
                };
            }

            function pauseTimer() {
                isPlaying = false;
                if (timerElement) {
                    timerElement.classList.remove('timer-is-running');
                }
                if (playPauseBtn) {
                    playPauseBtn.querySelector('span').textContent = 'Старт';
                }
                clearInterval(timerInterval);
                timerInterval = null;
                if (player) {
                    player.pause();
                    if (player.media && typeof player.media.pause === 'function') {
                        player.media.pause();
                    }
                }


                toggleDurationButtons(false);


                if (window.isFullscreenMode && window.currentFullscreenExercise === exerciseId) {
                    updateFullscreenControls();
                }
            }

            function stopTimer() {
                clearInterval(timerInterval);
                timerInterval = null;
                isPlaying = false;
                if (timerElement) {
                    timerElement.classList.remove('timer-is-running');
                }
                if (playPauseBtn) {
                    playPauseBtn.querySelector('span').textContent = 'Старт';
                }


                toggleDurationButtons(false);


                if (window.activeTimers[exerciseId]) {
                    delete window.activeTimers[exerciseId];
                }
            }

            function resetTimer(duration, options = {}) {
                const { allowWhilePlaying = false } = options;


                if (isPlaying && !allowWhilePlaying) return;

                if (duration > 0) {
                    selectedDuration = duration;
                }


                suppressAutoPlayUntil = Date.now() + 1500;

                stopTimer();
                if (timerElement) {
                    timerElement.classList.add('timer-is-reset');
                    window.requestAnimationFrame(() => {
                        window.requestAnimationFrame(() => {
                            timerElement.classList.remove('timer-is-reset');
                        });
                    });
                }
                remainingTime = duration;
                updateTimerDisplay();

                if (player) {
                    resetMediaToStart();
                }


                if (window.isFullscreenMode && window.currentFullscreenExercise === exerciseId) {
                    updateFullscreenTimer();
                    updateFullscreenControls();
                }
            }

            function resetMediaToStart() {
                if (player && player.isKinescope) {
                    player.stop();
                    return;
                }

                const media = player?.media || playerElement?.querySelector('audio, video, [data-plyr-provider="youtube"]');
                if (!media) return;

                const forceSeekToStart = () => {
                    if (player) {
                        try { player.pause(); } catch (e) {}
                        try { player.currentTime = 0; } catch (e) {}
                    }
                    try { media.pause(); } catch (e) {}
                    try { media.currentTime = 0; } catch (e) {}
                    try { media.pause(); } catch (e) {}
                    if (player) {
                        try { player.pause(); } catch (e) {}
                    }
                };

                if (media.readyState === 0) {
                    media.addEventListener('loadedmetadata', forceSeekToStart, { once: true });
                }


                forceSeekToStart();
                setTimeout(forceSeekToStart, 0);
                setTimeout(forceSeekToStart, 120);
            }

            function updateTimerDisplay() {
                if (timerDisplay) {
                    const minutes = Math.floor(remainingTime / 60);
                    const seconds = remainingTime % 60;
                    timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }


            if (playPauseBtn) {
                playPauseBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    const shouldPause = isPlaying || (player && player.playing);
                    if (shouldPause) {
                        pauseTimer();
                    } else {

                        suppressAutoPlayUntil = 0;
                        primeEndSignal();
                        startTimer();
                        if (player) {
                            player.play();
                        }
                    }
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    resetTimer(0, { allowWhilePlaying: true });
                });
            }

            if (presetBtns) {
                presetBtns.forEach(btn => {
                    btn.addEventListener('click', (event) => {
                        event.preventDefault();


                        if (isPlaying || durationButtonsLocked) return;

                        const duration = parseInt(btn.dataset.duration) || 180;
                        resetTimer(duration);
                    });
                });
            }

            updateTimerDisplay();
            } catch (err) {
                console.warn('Practice: ошибка инициализации версии', exercise.dataset.exerciseId, version.dataset.version, err);
            }
        });
    });


}


function goToNextExercise(currentExercise) {
    const nextExercise = currentExercise.nextElementSibling;
    if (nextExercise && nextExercise.classList.contains('praktika-exercise')) {

        nextExercise.scrollIntoView({behavior: 'smooth', block: 'start'});









    }
}


function stopAllTimersAndPlayers(currentExerciseId = null, currentVersion = null) {

    for (const exerciseId in window.activeTimers) {
        if (currentExerciseId !== exerciseId) {
            clearInterval(window.activeTimers[exerciseId].interval);
            delete window.activeTimers[exerciseId];
        }
    }


    for (const playerKey in window.activePlayers) {
        const [exerciseId, version] = playerKey.split('_');

        if (currentExerciseId !== exerciseId || currentVersion !== version) {
            if (window.activePlayers[playerKey].playing) {
                window.activePlayers[playerKey].pause();
            }
        }
    }
}


function createAudioFullscreenContainer() {
    const container = document.createElement('div');
    container.id = 'audio-fullscreen-container';
    container.className = 'audio-fullscreen hidden';
    container.innerHTML = `
        <div class="audio-fullscreen__header">
            <button class="audio-fullscreen__close">&times;</button>
            <h3 class="audio-fullscreen__title"></h3>
        </div>
        <div class="audio-fullscreen__timer">
            <span class="audio-fullscreen__time">0:00</span>
        </div>
        <div class="audio-fullscreen__controls">
            <button class="audio-fullscreen__control audio-fullscreen__play-pause">Старт</button>
            <button class="audio-fullscreen__control audio-fullscreen__prev">Назад</button>
            <button class="audio-fullscreen__control audio-fullscreen__next">Далее</button>
        </div>
        <div class="audio-fullscreen__progress">
            <div class="audio-fullscreen__progress-bar"></div>
        </div>
    `;

    document.body.appendChild(container);


    container.querySelector('.audio-fullscreen__close').addEventListener('click', closeAudioFullscreen);
    container.querySelector('.audio-fullscreen__play-pause').addEventListener('click', toggleFullscreenPlayPause);
    container.querySelector('.audio-fullscreen__prev').addEventListener('click', goToPrevExercise);
    container.querySelector('.audio-fullscreen__next').addEventListener('click', goToNextExerciseFromFullscreen);
}


function openAudioFullscreen(exercise, playerElement) {
    const exerciseId = exercise.dataset.exerciseId;
    const activeVersion = exercise.querySelector('.exercise-item.active').dataset.version;
    const title = exercise.querySelector('h3')?.textContent || 'Аудио практика';
    const player = window.activePlayers[`${exerciseId}_${activeVersion}`];

    if (!player) return;


    window.isFullscreenMode = true;
    window.currentFullscreenExercise = exerciseId;
    window.currentFullscreenVersion = activeVersion;


    const container = document.getElementById('audio-fullscreen-container');
    container.querySelector('.audio-fullscreen__title').textContent = title;
    container.querySelector('.audio-fullscreen__time').textContent =
        exercise.querySelector('.timer-display').textContent;


    container.classList.remove('hidden');
    document.body.style.overflow = 'hidden';


    updateFullscreenControls();
}


function closeAudioFullscreen() {
    window.isFullscreenMode = false;
    window.currentFullscreenExercise = null;
    window.currentFullscreenVersion = null;

    const container = document.getElementById('audio-fullscreen-container');
    container.classList.add('hidden');
    document.body.style.overflow = '';
}


function updateFullscreenTimer() {
    if (!window.isFullscreenMode || !window.currentFullscreenExercise) return;

    const exercise = document.querySelector(`[data-exercise-id="${window.currentFullscreenExercise}"]`);
    if (!exercise) return;

    const timerDisplay = exercise.querySelector('.timer-display');
    if (!timerDisplay) return;

    const fullscreenTime = document.querySelector('.audio-fullscreen__time');
    if (fullscreenTime) {
        fullscreenTime.textContent = timerDisplay.textContent;
    }
}


function updateFullscreenControls() {
    if (!window.isFullscreenMode || !window.currentFullscreenExercise) return;

    const exerciseId = window.currentFullscreenExercise;
    const version = window.currentFullscreenVersion;
    const player = window.activePlayers[`${exerciseId}_${version}`];
    const playPauseBtn = document.querySelector('.audio-fullscreen__play-pause');

    if (player && playPauseBtn) {
        playPauseBtn.textContent = player.playing ? 'Пауза' : 'Старт';
    }
}


function toggleFullscreenPlayPause() {
    if (!window.isFullscreenMode || !window.currentFullscreenExercise) return;

    const exerciseId = window.currentFullscreenExercise;
    const version = window.currentFullscreenVersion;
    const player = window.activePlayers[`${exerciseId}_${version}`];
    const exercise = document.querySelector(`[data-exercise-id="${exerciseId}"]`);

    if (player && exercise) {
        const playPauseBtn = exercise.querySelector('.timer-play-pause');
        if (playPauseBtn) {
            playPauseBtn.click();
        }
    }
}


function goToPrevExercise() {
    if (!window.isFullscreenMode || !window.currentFullscreenExercise) return;

    const currentExercise = document.querySelector(`[data-exercise-id="${window.currentFullscreenExercise}"]`);
    if (!currentExercise) return;

    const prevExercise = currentExercise.previousElementSibling;
    if (prevExercise && prevExercise.classList.contains('praktika-exercise')) {
        closeAudioFullscreen();
        prevExercise.scrollIntoView({behavior: 'smooth', block: 'start'});
    }
}


function goToNextExerciseFromFullscreen() {
    if (!window.isFullscreenMode || !window.currentFullscreenExercise) return;

    const currentExercise = document.querySelector(`[data-exercise-id="${window.currentFullscreenExercise}"]`);
    if (!currentExercise) return;

    goToNextExercise(currentExercise);
    closeAudioFullscreen();
}


function updateAudioFullscreen(exercise, playerElement) {
    if (!window.isFullscreenMode) return;

    const exerciseId = exercise.dataset.exerciseId;
    const activeVersion = exercise.querySelector('.exercise-item.active').dataset.version;
    const title = exercise.querySelector('h3')?.textContent || 'Аудио практика';


    const container = document.getElementById('audio-fullscreen-container');
    container.querySelector('.audio-fullscreen__title').textContent = title;


    updateFullscreenTimer();


    updateFullscreenControls();
}


const audioFullscreenStyles = `
    .audio-fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
        color: white;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .audio-fullscreen.hidden {
        display: none;
    }

    .audio-fullscreen__header {
        position: absolute;
        top: 20px;
        left: 20px;
        right: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .audio-fullscreen__close {
        background: none;
        border: none;
        color: white;
        font-size: 30px;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .audio-fullscreen__title {
        font-size: 1.5rem;
        margin: 0;
    }

    .audio-fullscreen__timer {
        margin: 40px 0;
    }

    .audio-fullscreen__time {
        font-size: 4rem;
        font-weight: bold;
    }

    .audio-fullscreen__controls {
        display: flex;
        gap: 20px;
        margin-bottom: 40px;
    }

    .audio-fullscreen__control {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        padding: 15px 25px;
        border-radius: 50px;
        cursor: pointer;
        font-size: 1.1rem;
        transition: background 0.3s;
    }

    .audio-fullscreen__control:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .audio-fullscreen__progress {
        width: 80%;
        max-width: 500px;
        height: 6px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
        overflow: hidden;
    }

    .audio-fullscreen__progress-bar {
        height: 100%;
        background: white;
        width: 0%;
        transition: width 0.3s;
    }
`;


const styleSheet = document.createElement('style');
styleSheet.textContent = audioFullscreenStyles;
document.head.appendChild(styleSheet);


const disabledButtonStyles = `
    .timer-preset.disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none;
    }

    .timer-preset {
        transition: opacity 0.3s ease;
    }
`;


const disabledStyleSheet = document.createElement('style');
disabledStyleSheet.textContent = disabledButtonStyles;
document.head.appendChild(disabledStyleSheet);


document.addEventListener('DOMContentLoaded', function() {

    if (typeof Plyr !== 'undefined') {
        initializePracticeSystem();
    } else {

        let plyrCheckInterval = setInterval(function() {
            if (typeof Plyr !== 'undefined') {
                clearInterval(plyrCheckInterval);
                initializePracticeSystem();
            }
        }, 100);


        setTimeout(function() {
            clearInterval(plyrCheckInterval);
            if (typeof Plyr === 'undefined') {
                console.error('Plyr failed to load after 10 seconds');
            }
        }, 10000);
    }
});
