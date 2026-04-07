const LEVELS = [
    { id:1, name:"big", targets:["big","large","huge","giant","enormous","massive","vast","immense","gigantic","tremendous"], time:0, speed:0.8, totalWords:50 },
    { id:2, name:"small", targets:["small","tiny","little","miniature","minute","petite","compact","microscopic","negligible","minuscule"], time:90, speed:1.0, totalWords:50 },
    { id:3, name:"good", targets:["good","great","excellent","superb","outstanding","fantastic","wonderful","brilliant","marvelous","exceptional"], time:90, speed:1.2, totalWords:55 },
    { id:4, name:"bad", targets:["bad","poor","terrible","awful","horrible","dreadful","nasty","lousy","atrocious","abysmal"], time:90, speed:1.4, totalWords:60 },
    { id:5, name:"fast", targets:["fast","quick","rapid","swift","speedy","hasty","hurried","fleet","express","accelerate"], time:75, speed:1.6, totalWords:60 },
    { id:6, name:"slow", targets:["slow","sluggish","gradual","leisurely","unhurried","poky","dilatory","tardy","lagging","snail-like"], time:75, speed:1.8, totalWords:65 },
    { id:7, name:"start", targets:["start","begin","initiate","launch","commence","trigger","activate","originate","embark","kickoff"], time:75, speed:2.0, totalWords:70 },
    { id:8, name:"end", targets:["end","stop","finish","complete","conclude","cease","terminate","halt","quit","abolish"], time:60, speed:2.2, totalWords:70 },
    { id:9, name:"although", targets:["although","though","despite","however","nevertheless","nonetheless","whereas","while","albeit","regardless"], time:60, speed:2.4, totalWords:75 },
    { id:10,name:"destroy", targets:["destroy","damage","demolish","wreck","ruin","shatter","smash","crush","devastate","obliterate"], time:60, speed:2.6, totalWords:80 }
];

const PROGRESS_KEY = 'word_hunter_progress';

function getUnlockedLevel() {
    const saved = localStorage.getItem(PROGRESS_KEY);
    return saved ? parseInt(saved) : 1;
}

function saveProgress(levelCompleted) {
    const current = getUnlockedLevel();
    if (levelCompleted + 1 > current) {
        localStorage.setItem(PROGRESS_KEY, levelCompleted + 1);
    }
}

const FALLBACK_DISTRACTORS = [
    "apple","car","house","happy","walk","blue","red","desk","chair","book","phone","table","water","light","sound",
    "flower","tree","mountain","river","cloud","star","moon","sun","bird","fish","cat","dog","run","jump","sleep"
];

let currentLevel = 0;
let currentScore = 0;
let timeLeft = 0;
let timerInterval = null;
let isGameActive = false;
let wordsArray = [];
let animationId = null;
let canvas = null;
let ctx = null;
let canvasWidth = 0, canvasHeight = 0;
let isLoading = false;

let levelSelectScreen, gameScreen;
let levelGrid;

const BUILTIN_DEFINITIONS = {
    "big":["/bɪɡ/","big"],"large":["/lɑːrdʒ/","large"],"huge":["/hjuːdʒ/","huge"],"giant":["/ˈdʒaɪənt/","giant"],"enormous":["/ɪˈnɔːrməs/","enormous"],"massive":["/ˈmæsɪv/","massive"],"vast":["/væst/","vast"],"immense":["/ɪˈmens/","immense"],"gigantic":["/dʒaɪˈɡæntɪk/","gigantic"],"tremendous":["/trəˈmendəs/","tremendous"],
    "small":["/smɔːl/","small"],"tiny":["/ˈtaɪni/","tiny"],"little":["/ˈlɪtl/","little"],"miniature":["/ˈmɪnətʃər/","miniature"],"minute":["/maɪˈnjuːt/","minute"],"petite":["/pəˈtiːt/","petite"],"compact":["/kəmˈpækt/","compact"],"microscopic":["/ˌmaɪkrəˈskɑːpɪk/","microscopic"],"negligible":["/ˈneɡlɪdʒəbl/","negligible"],"minuscule":["/ˈmɪnəskjuːl/","minuscule"],
    "good":["/ɡʊd/","good"],"great":["/ɡreɪt/","great"],"excellent":["/ˈeksələnt/","excellent"],"superb":["/suːˈpɜːrb/","superb"],"outstanding":["/aʊtˈstændɪŋ/","outstanding"],"fantastic":["/fænˈtæstɪk/","fantastic"],"wonderful":["/ˈwʌndərfl/","wonderful"],"brilliant":["/ˈbrɪliənt/","brilliant"],"marvelous":["/ˈmɑːrvələs/","marvelous"],"exceptional":["/ɪkˈsepʃnəl/","exceptional"],
    "bad":["/bæd/","bad"],"poor":["/pʊr/","poor"],"terrible":["/ˈterəbl/","terrible"],"awful":["/ˈɔːfl/","awful"],"horrible":["/ˈhɔːrəbl/","horrible"],"dreadful":["/ˈdredfl/","dreadful"],"nasty":["/ˈnæsti/","nasty"],"lousy":["/ˈlaʊzi/","lousy"],"atrocious":["/əˈtroʊʃəs/","atrocious"],"abysmal":["/əˈbɪzməl/","abysmal"],
    "fast":["/fæst/","fast"],"quick":["/kwɪk/","quick"],"rapid":["/ˈræpɪd/","rapid"],"swift":["/swɪft/","swift"],"speedy":["/ˈspiːdi/","speedy"],"hasty":["/ˈheɪsti/","hasty"],"hurried":["/ˈhɜːrid/","hurried"],"fleet":["/fliːt/","fleet"],"express":["/ɪkˈspres/","express"],"accelerate":["/əkˈseləreɪt/","accelerate"],
    "slow":["/sloʊ/","slow"],"sluggish":["/ˈslʌɡɪʃ/","sluggish"],"gradual":["/ˈɡrædʒuəl/","gradual"],"leisurely":["/ˈliːʒərli/","leisurely"],"unhurried":["/ʌnˈhɜːrid/","unhurried"],"poky":["/ˈpoʊki/","poky"],"dilatory":["/ˈdɪlətɔːri/","dilatory"],"tardy":["/ˈtɑːrdi/","tardy"],"lagging":["/ˈlæɡɪŋ/","lagging"],"snail-like":["/sneɪl laɪk/","snail-like"],
    "start":["/stɑːrt/","start"],"begin":["/bɪˈɡɪn/","begin"],"initiate":["/ɪˈnɪʃieɪt/","initiate"],"launch":["/lɔːntʃ/","launch"],"commence":["/kəˈmens/","commence"],"trigger":["/ˈtrɪɡər/","trigger"],"activate":["/ˈæktɪveɪt/","activate"],"originate":["/əˈrɪdʒɪneɪt/","originate"],"embark":["/ɪmˈbɑːrk/","embark"],"kickoff":["/ˈkɪk ɔːf/","kickoff"],
    "end":["/end/","end"],"stop":["/stɑːp/","stop"],"finish":["/ˈfɪnɪʃ/","finish"],"complete":["/kəmˈpliːt/","complete"],"conclude":["/kənˈkluːd/","conclude"],"cease":["/siːs/","cease"],"terminate":["/ˈtɜːrmɪneɪt/","terminate"],"halt":["/hɔːlt/","halt"],"quit":["/kwɪt/","quit"],"abolish":["/əˈbɑːlɪʃ/","abolish"],
    "although":["/ɔːlˈðoʊ/","although"],"though":["/ðoʊ/","though"],"despite":["/dɪˈspaɪt/","despite"],"however":["/haʊˈevər/","however"],"nevertheless":["/ˌnevərðəˈles/","nevertheless"],"nonetheless":["/ˌnʌnðəˈles/","nonetheless"],"whereas":["/werˈæz/","whereas"],"while":["/waɪl/","while"],"albeit":["/ɔːlˈbiːɪt/","albeit"],"regardless":["/rɪˈɡɑːrdləs/","regardless"],
    "destroy":["/dɪˈstrɔɪ/","destroy"],"damage":["/ˈdæmɪdʒ/","damage"],"demolish":["/dɪˈmɑːlɪʃ/","demolish"],"wreck":["/rek/","wreck"],"ruin":["/ˈruːɪn/","ruin"],"shatter":["/ˈʃætər/","shatter"],"smash":["/smæʃ/","smash"],"crush":["/krʌʃ/","crush"],"devastate":["/ˈdevəsteɪt/","devastate"],"obliterate":["/əˈblɪtəreɪt/","obliterate"]
};

window.onload = function() {
    canvas = document.getElementById('game-canvas');
    if (!canvas) return;
    ctx = canvas.getContext('2d');
    
    levelSelectScreen = document.getElementById('level-select-screen');
    gameScreen = document.getElementById('game-screen');
    levelGrid = document.getElementById('level-grid');
    
    renderLevelSelect();
    
    document.getElementById('back-to-select-btn')?.addEventListener('click', showLevelSelect);
    document.getElementById('back-to-main-from-select')?.addEventListener('click', function() {
        window.location.href = '../galgame/index.html?view=floor6';
    });
    document.getElementById('back-to-select-after-complete')?.addEventListener('click', function() {
        closeAllModals();
        showLevelSelect();
    });
    document.getElementById('back-to-select-after-gameover')?.addEventListener('click', function() {
        closeAllModals();
        showLevelSelect();
    });
    document.getElementById('continue-btn')?.addEventListener('click', function() {
        closeAllModals();
        goToNextLevel();
    });
    document.getElementById('restart-btn')?.addEventListener('click', function() {
        startGameFromLevel(currentLevel);
    });
    document.getElementById('restart-game-btn')?.addEventListener('click', function() {
        closeAllModals();
        startGameFromLevel(currentLevel);
    });
    
    canvas.addEventListener('click', handleCanvasClick);
    
    document.querySelectorAll('.close-modal').forEach(function(btn) {
        btn.addEventListener('click', function() { closeAllModals(); });
    });
    
    window.addEventListener('resize', function() {
        if (canvas) {
            resizeCanvas();
            if (isGameActive) repositionWords();
        }
    });
};

function renderLevelSelect() {
    if (!levelGrid) return;
    const unlockedLevel = getUnlockedLevel();
    levelGrid.innerHTML = '';
    
    for (let i = 0; i < LEVELS.length; i++) {
        const level = LEVELS[i];
        const levelNum = i + 1;
        const isUnlocked = levelNum <= unlockedLevel;
        const isCompleted = levelNum < unlockedLevel;
        
        const card = document.createElement('div');
        card.className = 'level-card';
        if (!isUnlocked) card.classList.add('locked');
        if (isCompleted) card.classList.add('completed');
        
        card.innerHTML = '<div class="level-number">' + levelNum + '</div>' +
            '<div class="level-name">' + level.name + '</div>' +
            '<div class="level-status">' + (isCompleted ? '✅' : (isUnlocked ? '🔓' : '🔒')) + '</div>';
        
        if (isUnlocked) {
            card.addEventListener('click', (function(idx) {
                return function() { startLevel(idx); };
            })(i));
        }
        
        levelGrid.appendChild(card);
    }
}

function startLevel(levelIndex) {
    currentLevel = levelIndex;
    showGameScreen();
    startGameFromLevel(levelIndex);
}

function showLevelSelect() {
    if (timerInterval) clearInterval(timerInterval);
    if (animationId) cancelAnimationFrame(animationId);
    isGameActive = false;
    renderLevelSelect();
    levelSelectScreen.classList.remove('hidden');
    gameScreen.classList.add('hidden');
}

function showGameScreen() {
    levelSelectScreen.classList.add('hidden');
    gameScreen.classList.remove('hidden');
    resizeCanvas();
}

function resizeCanvas() {
    if (!canvas) return;
    const topBar = document.getElementById('top-bar');
    const taskHeader = document.getElementById('task-header');
    const bottomBar = document.getElementById('bottom-bar');
    const topBarHeight = topBar ? topBar.offsetHeight : 60;
    const taskHeight = taskHeader ? taskHeader.offsetHeight : 80;
    const bottomBarHeight = bottomBar ? bottomBar.offsetHeight : 70;
    const availableHeight = window.innerHeight - topBarHeight - taskHeight - bottomBarHeight;
    
    canvas.width = window.innerWidth;
    canvas.height = Math.max(availableHeight, 400);
    canvasWidth = canvas.width;
    canvasHeight = canvas.height;
}

function repositionWords() {
    for (let w of wordsArray) {
        w.x = Math.min(Math.max(w.x, w.radius + 5), canvasWidth - w.radius - 5);
        w.y = Math.min(Math.max(w.y, w.radius + 5), canvasHeight - w.radius - 5);
    }
}

function startGameFromLevel(levelIndex) {
    if (timerInterval) clearInterval(timerInterval);
    if (animationId) cancelAnimationFrame(animationId);
    
    currentLevel = Math.min(levelIndex, LEVELS.length - 1);
    const level = LEVELS[currentLevel];
    currentScore = 0;
    updateScoreUI();
    
    timeLeft = level.time === 0 ? 9999 : level.time;
    updateTimerUI();
    
    isGameActive = true;
    const nextBtn = document.getElementById('next-level-btn');
    if (nextBtn) nextBtn.disabled = true;
    
    if (canvasWidth === 0 || canvasHeight === 0) {
        resizeCanvas();
    }
    
    const levelNumEl = document.getElementById('level-num');
    const currentTargetDescEl = document.getElementById('current-target-desc');
    const targetTotalEl = document.getElementById('target-total');
    const timerEl = document.getElementById('timer');
    
    if (levelNumEl) levelNumEl.innerText = level.id;
    if (currentTargetDescEl) currentTargetDescEl.innerText = level.name;
    if (targetTotalEl) targetTotalEl.innerText = level.targets.length;
    if (level.time === 0 && timerEl) timerEl.innerText = '∞';
    
    showLoadingMessage();
    generateWordsFromDatabase(level);
    
    if (level.time > 0) {
        timerInterval = setInterval(function() {
            if (!isGameActive) return;
            if (timeLeft <= 1) {
                clearInterval(timerInterval);
                isGameActive = false;
                showGameOverModal('Time is up!');
            } else {
                timeLeft--;
                updateTimerUI();
            }
        }, 1000);
    }
    
    function animate() {
        if (!isGameActive) return;
        updateWordPositions();
        drawCanvas();
        animationId = requestAnimationFrame(animate);
    }
    animate();
}

function showLoadingMessage() {
    if (!ctx) return;
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
    ctx.fillStyle = '#1f3127';
    ctx.font = '24px "Trebuchet MS"';
    ctx.textAlign = 'center';
    ctx.fillText('Loading words...', canvasWidth/2, canvasHeight/2);
}

async function generateWordsFromDatabase(level) {
    if (isLoading) return;
    isLoading = true;
    
    const targetWords = [...level.targets];
    const distractorCount = level.totalWords - targetWords.length;
    const excludeStr = targetWords.join(',');
    const apiUrl = `../../vocabulary_lookup.php?random=1&count=${distractorCount}&exclude=${encodeURIComponent(excludeStr)}`;
    
    try {
        const response = await fetch(apiUrl);
        const data = await response.json();
        
        let distractors = [];
        if (data.status === 'success' && data.words && data.words.length > 0) {
            distractors = data.words;
        } else {
            distractors = getFallbackDistractors(distractorCount, targetWords);
        }
        
        if (distractors.length < distractorCount) {
            const more = getFallbackDistractors(distractorCount - distractors.length, targetWords);
            distractors.push(...more);
        }
        
        const allWordTexts = [...targetWords, ...distractors.slice(0, distractorCount)];
        for (let i = allWordTexts.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [allWordTexts[i], allWordTexts[j]] = [allWordTexts[j], allWordTexts[i]];
        }
        
        generateBubbles(allWordTexts, level);
        isLoading = false;
        
    } catch (error) {
        const distractors = getFallbackDistractors(distractorCount, targetWords);
        const allWordTexts = [...targetWords, ...distractors];
        for (let i = allWordTexts.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [allWordTexts[i], allWordTexts[j]] = [allWordTexts[j], allWordTexts[i]];
        }
        generateBubbles(allWordTexts, level);
        isLoading = false;
    }
}

function getFallbackDistractors(count, excludeWords) {
    const available = FALLBACK_DISTRACTORS.filter(function(d) {
        return !excludeWords.includes(d);
    });
    const result = [];
    for (let i = 0; i < count; i++) {
        result.push(available[i % available.length]);
    }
    for (let i = result.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [result[i], result[j]] = [result[j], result[i]];
    }
    return result;
}

function generateBubbles(wordTexts, level) {
    wordsArray = [];
    const speedBase = LEVELS[currentLevel].speed;
    const safeWidth = Math.max(canvasWidth, 500);
    const safeHeight = Math.max(canvasHeight, 400);
    
    for (let text of wordTexts) {
        const radius = Math.min(55, Math.max(38, 28 + text.length * 2.5));
        wordsArray.push({
            text: text,
            x: Math.random() * (safeWidth - 2 * radius) + radius,
            y: Math.random() * (safeHeight - 2 * radius) + radius,
            vx: (Math.random() - 0.5) * speedBase * 1.2,
            vy: (Math.random() - 0.5) * speedBase * 1.2,
            radius: radius,
            isTarget: level.targets.includes(text)
        });
    }
}

function updateWordPositions() {
    const speed = LEVELS[currentLevel].speed;
    for (let w of wordsArray) {
        w.x += w.vx;
        w.y += w.vy;
        if (w.x - w.radius <= 0) { w.x = w.radius; w.vx = Math.abs(w.vx); }
        if (w.x + w.radius >= canvasWidth) { w.x = canvasWidth - w.radius; w.vx = -Math.abs(w.vx); }
        if (w.y - w.radius <= 0) { w.y = w.radius; w.vy = Math.abs(w.vy); }
        if (w.y + w.radius >= canvasHeight) { w.y = canvasHeight - w.radius; w.vy = -Math.abs(w.vy); }
    }
}

function drawCanvas() {
    if (!ctx) return;
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
    
    for (let w of wordsArray) {
        ctx.save();
        ctx.shadowBlur = 8;
        ctx.shadowColor = "rgba(0,0,0,0.3)";
        ctx.beginPath();
        ctx.arc(w.x, w.y, w.radius, 0, Math.PI * 2);
        
        const grad = ctx.createLinearGradient(w.x - 10, w.y - 10, w.x + 10, w.y + 10);
        grad.addColorStop(0, '#e0f2fe');
        grad.addColorStop(1, '#bae6fd');
        
        ctx.fillStyle = grad;
        ctx.fill();
        ctx.strokeStyle = '#fff5e6';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.fillStyle = '#1e293b';
        ctx.font = 'bold ' + Math.min(20, w.radius / 2.2) + 'px "Segoe UI", "Microsoft YaHei"';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(w.text, w.x, w.y);
        ctx.restore();
    }
}

async function handleCanvasClick(e) {
    if (!isGameActive) return;
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    let mouseX = (e.clientX - rect.left) * scaleX;
    let mouseY = (e.clientY - rect.top) * scaleY;
    
    for (let i = wordsArray.length-1; i >= 0; i--) {
        const w = wordsArray[i];
        const dx = mouseX - w.x;
        const dy = mouseY - w.y;
        const dist = Math.sqrt(dx*dx + dy*dy);
        if (dist <= w.radius) {
            if (w.isTarget) {
                currentScore++;
                updateScoreUI();
                showFloatPlus(w.x, w.y, '+1');
                wordsArray.splice(i,1);
                if (currentScore === LEVELS[currentLevel].targets.length) {
                    isGameActive = false;
                    if (timerInterval) clearInterval(timerInterval);
                    saveProgress(currentLevel);
                    showLevelCompleteModal();
                }
            } else {
                if (LEVELS[currentLevel].time > 0) {
                    timeLeft = Math.max(0, timeLeft - 2);
                    updateTimerUI();
                    if (timeLeft <= 0) {
                        isGameActive = false;
                        clearInterval(timerInterval);
                        showGameOverModal('Time is up!');
                    }
                }
                shakeWord(w);
            }
            fetchWordDefinition(w.text);
            break;
        }
    }
}

function shakeWord(wordObj) {
    const originalX = wordObj.x, originalY = wordObj.y;
    let count = 0;
    const shakeInterval = setInterval(function() {
        if (count >= 4) {
            wordObj.x = originalX;
            wordObj.y = originalY;
            clearInterval(shakeInterval);
        } else {
            wordObj.x = originalX + (Math.random() - 0.5) * 8;
            wordObj.y = originalY + (Math.random() - 0.5) * 8;
            count++;
        }
    }, 40);
}

function showFloatPlus(x, y, text) {
    const rect = canvas.getBoundingClientRect();
    const div = document.createElement('div');
    div.innerText = text;
    div.className = 'float-plus';
    div.style.left = (rect.left + x - 15) + 'px';
    div.style.top = (rect.top + y - 20) + 'px';
    div.style.position = 'fixed';
    div.style.zIndex = '1000';
    document.body.appendChild(div);
    setTimeout(function() { div.remove(); }, 600);
}

function fetchWordDefinition(word) {
    const apiUrl = `../../vocabulary_lookup.php?search=${encodeURIComponent(word)}`;
    
    fetch(apiUrl)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                showDefinitionModal(data.word, data.phonetic, data.definition);
            } else {
                useBuiltinDefinition(word);
            }
        })
        .catch(function() { useBuiltinDefinition(word); });
}

function useBuiltinDefinition(word) {
    const lowerWord = word.toLowerCase();
    if (BUILTIN_DEFINITIONS[lowerWord]) {
        const phonetic = BUILTIN_DEFINITIONS[lowerWord][0];
        const meaning = BUILTIN_DEFINITIONS[lowerWord][1];
        showDefinitionModal(word, phonetic, meaning);
    } else {
        showDefinitionModal(word, '', 'Definition not found');
    }
}

function showDefinitionModal(word, phonetic, meaning) {
    const modal = document.getElementById('definition-modal');
    if (!modal) return;
    const defWord = document.getElementById('def-word');
    const defPhonetic = document.getElementById('def-phonetic');
    const defMeaning = document.getElementById('def-meaning');
    if (defWord) defWord.innerText = word;
    if (defPhonetic) defPhonetic.innerText = phonetic || '';
    if (defMeaning) defMeaning.innerText = meaning;
    modal.classList.remove('hidden');
    const closeSpan = modal.querySelector('.close-modal');
    if (closeSpan) closeSpan.onclick = function() { modal.classList.add('hidden'); };
    modal.onclick = function(e) { if(e.target === modal) modal.classList.add('hidden'); };
}

function showLevelCompleteModal() {
    const msgEl = document.getElementById('complete-message');
    if (msgEl) msgEl.innerHTML = 'Level ' + LEVELS[currentLevel].id + ' Complete!<br>Theme: ' + LEVELS[currentLevel].name;
    const modal = document.getElementById('level-complete-modal');
    if (modal) modal.classList.remove('hidden');
    if (timerInterval) clearInterval(timerInterval);
    const nextBtn = document.getElementById('next-level-btn');
    if (nextBtn) nextBtn.disabled = false;
}

function showGameOverModal(msg) {
    const msgEl = document.getElementById('gameover-message');
    if (msgEl) msgEl.innerHTML = msg + '<br>Level ' + LEVELS[currentLevel].id + ' | Score: ' + currentScore + '/' + LEVELS[currentLevel].targets.length;
    const modal = document.getElementById('game-over-modal');
    if (modal) modal.classList.remove('hidden');
    const nextBtn = document.getElementById('next-level-btn');
    if (nextBtn) nextBtn.disabled = true;
    isGameActive = false;
}

function goToNextLevel() {
    if (currentLevel + 1 < LEVELS.length) {
        startGameFromLevel(currentLevel + 1);
    } else {
        alert('🎉 Congratulations! You completed all 10 levels!');
        showLevelSelect();
    }
    closeAllModals();
}

function closeAllModals() {
    document.querySelectorAll('.modal').forEach(function(m) {
        m.classList.add('hidden');
    });
}

function updateScoreUI() {
    const scoreEl = document.getElementById('score');
    if (scoreEl) scoreEl.innerText = currentScore;
}

function updateTimerUI() {
    const timerEl = document.getElementById('timer');
    if (!timerEl) return;
    if (LEVELS[currentLevel]?.time === 0) timerEl.innerText = '∞';
    else timerEl.innerText = timeLeft;
}