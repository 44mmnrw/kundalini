// practice-player.js - Доработанная версия
function initializePracticeSystem() {
    // Axecode.tech: Этап 2 стабилизации - предотвращение повторной инициализации.
    if (window.practiceSystemInitialized) {
        return;
    }
    // Проверяем что Plyr загружен
    if (typeof Plyr === 'undefined') {
        console.log('Plyr not loaded yet, retrying in 100ms...');
        setTimeout(initializePracticeSystem, 100);
        return;
    }

    console.log('Plyr loaded successfully, initializing players...');

    // Глобальные переменные для управления состоянием
    window.activeTimers = {};
    window.activePlayers = {};
    window.isFullscreenMode = false;
    window.currentFullscreenExercise = null;

    // Создаем контейнер для полноэкранного режима аудио
    createAudioFullscreenContainer();

    /** Plyr только через `new Plyr` внутри цикла по версиям. Глобальный `Plyr.setup` здесь давал второй init на том же `<audio>/<video>` и ломал forEach до версии «без медиа». */
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

    // Axecode.tech: Этап 2 стабилизации - отмечаем успешную однократную инициализацию.
    window.practiceSystemInitialized = true;

    // Обработка переключателей версий
    document.querySelectorAll('.exercise-switches__item').forEach(switchItem => {
        switchItem.addEventListener('click', function() {
            const targetVersion = this.dataset.target;
            const exercise = this.closest('.praktika-exercise');
            const exerciseId = exercise.dataset.exerciseId;
            
            // Останавливаем все активные таймеры и плееры
            stopAllTimersAndPlayers();
            
            // Переключение активного класса у переключателей
            /* this.closest('.exercise-switches').querySelectorAll('.exercise-switches__item').forEach(item => {
                item.classList.remove('active');
            });
            this.classList.add('active'); */
            
            // Переключение версий упражнения
             exercise.querySelectorAll('.exercise-item').forEach(item => {
                item.classList.remove('active');
            });
            exercise.querySelector(`.exercise-item[data-version="${targetVersion}"]`).classList.add('active'); 
            
            // Если был активен полноэкранный режим, обновляем его
            if (window.isFullscreenMode && window.currentFullscreenExercise === exerciseId) {
                const activeExercise = exercise.querySelector('.exercise-item.active');
                const activePlayer = activeExercise.querySelector('.exercise-player');
                updateAudioFullscreen(exercise, activePlayer);
            }
        });
    });
    
    // Инициализация таймеров и плееров для каждой версии
    document.querySelectorAll('.praktika-exercise').forEach(exercise => {
        const exerciseId = exercise.dataset.exerciseId;
        
        exercise.querySelectorAll('.exercise-item').forEach(version => {
            try {

            const versionType = version.dataset.version;
            const timerDisplay = version.querySelector('.timer-display');
            const playPauseBtn = version.querySelector('.timer-play-pause');
            const resetBtn = version.querySelector('.timer-reset');
            const presetBtns = version.querySelectorAll('.timer-preset');
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
                const mediaElement = playerElement.querySelector('audio, video');
                if (mediaElement) {
                    const isVideo = mediaElement.tagName === 'VIDEO';
                    const playerOptions = isVideo
                        ? {
                            controls: ['play-large'],
                            clickToPlay: true,
                            hideControls: false
                        }
                        : plyrAudioOptions;
                    player = new Plyr(mediaElement, playerOptions);
                    
                    // Сохраняем ссылку на плеер для глобального доступа
                    window.activePlayers[`${exerciseId}_${versionType}`] = player;
                    
                    // Обработка ограничений для закрытого контента
                    const isRestricted = playerElement.dataset.restrictScrub === 'true';
                    if (isRestricted) {
                        // Запрещаем перемотку
                        player.on('seeked', function(event) {
                            const details = event.detail;
                            // Разрешаем перемотку только в первых 60 секундах для демо
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

                        // Запуск через встроенную кнопку Plyr также должен запускать таймер.
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
                        // Автопереход к следующему упражнению
                        goToNextExercise(exercise);
                    });
                    
                    // Обработка полноэкранного режима
                    player.on('enterfullscreen', () => {
                        if (player.media.tagName === 'AUDIO') {
                            // Для аудио используем наш кастомный полноэкранный режим
                            player.exitFullscreen();
                            openAudioFullscreen(exercise, playerElement);
                            return false; // Отменяем стандартное поведение
                        }
                    });
                    
                    player.on('exitfullscreen', () => {
                        if (window.isFullscreenMode && player.media.tagName === 'AUDIO') {
                            closeAudioFullscreen();
                        }
                    });

                    // Перехват нативного autoplay/playing после сброса (в обход Plyr events).
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
            
			 // Добавляем состояние блокировки переключателей
            let durationButtonsLocked = false;
            
            // Функция для блокировки/разблокировки переключателей длительности
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

                // После ручного сброса до 00:00 запускаем с последней выбранной длительности.
                if (remainingTime <= 0) {
                    remainingTime = selectedDuration > 0 ? selectedDuration : initialDuration;
                    updateTimerDisplay();
                }

                isPlaying = true;
                if (playPauseBtn) {
                    playPauseBtn.querySelector('span').textContent = 'Пауза';
                }
				
                // Блокируем переключатели длительности при старте
                toggleDurationButtons(true);
				
                // Останавливаем все другие таймеры и плееры
                stopAllTimersAndPlayers(exerciseId, versionType);
                
                timerInterval = setInterval(() => {
                    if (!isPlaying) {
                        return;
                    }

                    if (remainingTime > 0) {
                        remainingTime--;
                        updateTimerDisplay();
                        
                        // Обновляем полноэкранный таймер, если активен
                        if (window.isFullscreenMode && window.currentFullscreenExercise === exerciseId) {
                            updateFullscreenTimer();
                        }
                    } else {
                        playEndSignal();
                        stopTimer();
                        if (player) player.pause();
                        
                        // Автопереход к следующему упражнению
                        goToNextExercise(exercise);
                    }
                }, 1000);
                
                // Сохраняем ссылку на интервал для возможности остановки при переключении
                window.activeTimers[exerciseId] = {
                    interval: timerInterval,
                    version: versionType
                };
            }
            
            function pauseTimer() {
                isPlaying = false;
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
				
                 // Разблокируем переключатели длительности при паузе
                toggleDurationButtons(false);
				
                // Обновляем полноэкранный режим, если активен
                if (window.isFullscreenMode && window.currentFullscreenExercise === exerciseId) {
                    updateFullscreenControls();
                }
            }
            
            function stopTimer() {
                clearInterval(timerInterval);
                timerInterval = null;
                isPlaying = false;
                if (playPauseBtn) {
                    playPauseBtn.querySelector('span').textContent = 'Старт';
                }
                
                // Разблокируем переключатели длительности при остановке
                toggleDurationButtons(false);
				
                // Удаляем из активных таймеров
                if (window.activeTimers[exerciseId]) {
                    delete window.activeTimers[exerciseId];
                }
            }
            
            function resetTimer(duration, options = {}) {
                const { allowWhilePlaying = false } = options;

                // Для пресетов сохраняем защиту от изменения во время воспроизведения.
                if (isPlaying && !allowWhilePlaying) return;

                if (duration > 0) {
                    selectedDuration = duration;
                }

                // После сброса блокируем любые случайные auto-play события плеера.
                suppressAutoPlayUntil = Date.now() + 1500;
                
                stopTimer();
                remainingTime = duration;
                updateTimerDisplay();
                
                if (player) {
                    resetMediaToStart();
                }
                
                // Обновляем полноэкранный режим, если активен
                if (window.isFullscreenMode && window.currentFullscreenExercise === exerciseId) {
                    updateFullscreenTimer();
                    updateFullscreenControls();
                }
            }

            function resetMediaToStart() {
                const media = player?.media || playerElement?.querySelector('audio, video');
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

                // Выполняем несколько попыток, чтобы обойти асинхронные состояния плеера.
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
            
            // Обработчики событий
            if (playPauseBtn) {
                playPauseBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    const shouldPause = isPlaying || (player && player.playing);
                    if (shouldPause) {
                        pauseTimer();
                    } else {
                        // Явный клик "Старт" должен обходить анти-автозапуск после сброса.
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
					
                        // Блокируем изменение длительности во время воспроизведения
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

    // Слайдеры упражнений инициализируются в script.js через Slick.
}

// Функция для перехода к следующему упражнению
function goToNextExercise(currentExercise) {
    const nextExercise = currentExercise.nextElementSibling;
    if (nextExercise && nextExercise.classList.contains('praktika-exercise')) {
        // Плавная прокрутка к следующему упражнению
        nextExercise.scrollIntoView({behavior: 'smooth', block: 'start'});
        
        // Автозапуск следующего упражнения (опционально)
        // const nextPlayer = nextExercise.querySelector('.exercise-player');
        // if (nextPlayer && nextPlayer.dataset.autoPlay === 'true') {
        //     const nextPlyr = Plyr.setup(nextPlayer.querySelector('audio, video'))[0];
        //     if (nextPlyr) {
        //         nextPlyr.play();
        //     }
        // }
    }
}

// Функция для остановки всех таймеров и плееров
function stopAllTimersAndPlayers(currentExerciseId = null, currentVersion = null) {
    // Останавливаем все таймеры
    for (const exerciseId in window.activeTimers) {
        if (currentExerciseId !== exerciseId) {
            clearInterval(window.activeTimers[exerciseId].interval);
            delete window.activeTimers[exerciseId];
        }
    }
    
    // Останавливаем все плееры
    for (const playerKey in window.activePlayers) {
        const [exerciseId, version] = playerKey.split('_');
        
        if (currentExerciseId !== exerciseId || currentVersion !== version) {
            if (window.activePlayers[playerKey].playing) {
                window.activePlayers[playerKey].pause();
            }
        }
    }
}

// Функция для создания контейнера полноэкранного режима аудио
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
    
    // Обработчики событий для кнопок полноэкранного режима
    container.querySelector('.audio-fullscreen__close').addEventListener('click', closeAudioFullscreen);
    container.querySelector('.audio-fullscreen__play-pause').addEventListener('click', toggleFullscreenPlayPause);
    container.querySelector('.audio-fullscreen__prev').addEventListener('click', goToPrevExercise);
    container.querySelector('.audio-fullscreen__next').addEventListener('click', goToNextExerciseFromFullscreen);
}

// Функция для открытия полноэкранного режима аудио
function openAudioFullscreen(exercise, playerElement) {
    const exerciseId = exercise.dataset.exerciseId;
    const activeVersion = exercise.querySelector('.exercise-item.active').dataset.version;
    const title = exercise.querySelector('h3')?.textContent || 'Аудио практика';
    const player = window.activePlayers[`${exerciseId}_${activeVersion}`];
    
    if (!player) return;
    
    // Устанавливаем состояние полноэкранного режима
    window.isFullscreenMode = true;
    window.currentFullscreenExercise = exerciseId;
    window.currentFullscreenVersion = activeVersion;
    
    // Заполняем данные в контейнере
    const container = document.getElementById('audio-fullscreen-container');
    container.querySelector('.audio-fullscreen__title').textContent = title;
    container.querySelector('.audio-fullscreen__time').textContent = 
        exercise.querySelector('.timer-display').textContent;
    
    // Показываем контейнер
    container.classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Блокируем прокрутку страницы
    
    // Обновляем состояние кнопки play/pause
    updateFullscreenControls();
}

// Функция для закрытия полноэкранного режима аудио
function closeAudioFullscreen() {
    window.isFullscreenMode = false;
    window.currentFullscreenExercise = null;
    window.currentFullscreenVersion = null;
    
    const container = document.getElementById('audio-fullscreen-container');
    container.classList.add('hidden');
    document.body.style.overflow = ''; // Восстанавливаем прокрутку страницы
}

// Функция для обновления таймера в полноэкранном режиме
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

// Функция для обновления элементов управления в полноэкранном режиме
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

// Функция переключения play/pause в полноэкранном режиме
function toggleFullscreenPlayPause() {
    if (!window.isFullscreenMode || !window.currentFullscreenExercise) return;
    
    const exerciseId = window.currentFullscreenExercise;
    const version = window.currentFullscreenVersion;
    const player = window.activePlayers[`${exerciseId}_${version}`];
    const exercise = document.querySelector(`[data-exercise-id="${exerciseId}"]`);
    
    if (player && exercise) {
        const playPauseBtn = exercise.querySelector('.timer-play-pause');
        if (playPauseBtn) {
            playPauseBtn.click(); // Эмулируем клик по кнопке в основном интерфейсе
        }
    }
}

// Функция перехода к предыдущему упражнению из полноэкранного режима
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

// Функция перехода к следующему упражнению из полноэкранного режима
function goToNextExerciseFromFullscreen() {
    if (!window.isFullscreenMode || !window.currentFullscreenExercise) return;
    
    const currentExercise = document.querySelector(`[data-exercise-id="${window.currentFullscreenExercise}"]`);
    if (!currentExercise) return;
    
    goToNextExercise(currentExercise);
    closeAudioFullscreen();
}

// Функция для обновления полноэкранного режима при переключении версий
function updateAudioFullscreen(exercise, playerElement) {
    if (!window.isFullscreenMode) return;
    
    const exerciseId = exercise.dataset.exerciseId;
    const activeVersion = exercise.querySelector('.exercise-item.active').dataset.version;
    const title = exercise.querySelector('h3')?.textContent || 'Аудио практика';
    
    // Обновляем заголовок
    const container = document.getElementById('audio-fullscreen-container');
    container.querySelector('.audio-fullscreen__title').textContent = title;
    
    // Обновляем таймер
    updateFullscreenTimer();
    
    // Обновляем элементы управления
    updateFullscreenControls();
}

// Стили для полноэкранного режима аудио
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

// Добавляем стили в документ
const styleSheet = document.createElement('style');
styleSheet.textContent = audioFullscreenStyles;
document.head.appendChild(styleSheet);

// Стили для блокировки переключателей
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

// Добавляем стили в документ
const disabledStyleSheet = document.createElement('style');
disabledStyleSheet.textContent = disabledButtonStyles;
document.head.appendChild(disabledStyleSheet);

// Запускаем инициализацию когда DOM готов
document.addEventListener('DOMContentLoaded', function() {
    // Если Plyr уже загружен, инициализируем сразу
    if (typeof Plyr !== 'undefined') {
        initializePracticeSystem();
    } else {
        // Если еще не загружен, ждем
        let plyrCheckInterval = setInterval(function() {
            if (typeof Plyr !== 'undefined') {
                clearInterval(plyrCheckInterval);
                initializePracticeSystem();
            }
        }, 100);
        
        // Таймаут на случай если Plyr никогда не загрузится
        setTimeout(function() {
            clearInterval(plyrCheckInterval);
            if (typeof Plyr === 'undefined') {
                console.error('Plyr failed to load after 10 seconds');
            }
        }, 10000);
    }
});

// Axecode.tech: Этап 2 стабилизации - единая точка входа инициализации (DOMContentLoaded).
