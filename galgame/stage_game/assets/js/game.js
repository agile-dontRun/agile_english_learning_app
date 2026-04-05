document.addEventListener('DOMContentLoaded', function() {
    const TOTAL_QUESTIONS = 8;
    
    let questions = [];
    let endingMessages = [];
    
    try {
        const questionsData = document.getElementById('questions-data');
        const endingMessagesData = document.getElementById('ending-messages-data');
        
        if (questionsData) {
            questions = JSON.parse(questionsData.textContent);
        }
        if (endingMessagesData) {
            endingMessages = JSON.parse(endingMessagesData.textContent);
        }
    } catch(e) {
        console.error('Failed to parse data:', e);
    }
    
    let currentQuestionIndex = 0;
    let currentQuestion = null;
    let gameActive = true;
    let quizStarted = false;
    let isWaitingForGif = false;
    let correctCount = 0;
    let gifPlayCount = 0;
    let gifInterval = null;
    let isLoading = false;
    
    function updateScoreDisplay() {
        const scoreCountEl = document.getElementById('scoreCount');
        const progressFillEl = document.getElementById('progressFill');
        const progressTextEl = document.getElementById('progressText');
        
        if (scoreCountEl) scoreCountEl.innerText = correctCount;
        const progress = (currentQuestionIndex / TOTAL_QUESTIONS) * 100;
        if (progressFillEl) progressFillEl.style.width = `${progress}%`;
        if (progressTextEl) progressTextEl.innerText = `${currentQuestionIndex}/${TOTAL_QUESTIONS}`;
    }
    
    function disableOptions(disabled) {
        const optionBtns = document.querySelectorAll('.option-btn');
        optionBtns.forEach(btn => {
            btn.disabled = disabled;
        });
    }
    
    async function loadQuestion() {
        if (!gameActive || isLoading) return;
        if (currentQuestionIndex >= TOTAL_QUESTIONS) {
            finishGame();
            return;
        }
        
        isLoading = true;
        
        try {
            const formData = new FormData();
            formData.append('action', 'get_question');
            formData.append('index', currentQuestionIndex);
            
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            
            const text = await response.text();
            console.log('Load response:', text.substring(0, 200));
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', text);
                document.getElementById('feedbackMsg').innerHTML = '❌ Failed to load question.';
                isLoading = false;
                return;
            }
            
            currentQuestion = data;
            
            const questionTextEl = document.getElementById('questionText');
            const questionCounterEl = document.getElementById('questionCounter');
            const optionsContainer = document.getElementById('optionsContainer');
            const feedbackMsgEl = document.getElementById('feedbackMsg');
            
            if (questionTextEl) questionTextEl.innerHTML = `📖 ${data.question}`;
            if (questionCounterEl) questionCounterEl.innerHTML = `Question ${data.current}/${data.total}`;
            
            if (optionsContainer) {
                optionsContainer.innerHTML = '';
                data.options.forEach((opt, idx) => {
                    const btn = document.createElement('button');
                    btn.className = 'option-btn';
                    btn.textContent = opt;
                    btn.onclick = () => submitAnswer(idx);
                    optionsContainer.appendChild(btn);
                });
            }
            
            if (feedbackMsgEl) feedbackMsgEl.innerHTML = '';
            disableOptions(false);
            
            updateScoreDisplay();
            
        } catch (error) {
            console.error('Failed to load question', error);
            document.getElementById('feedbackMsg').innerHTML = '❌ Failed to load question. Please refresh.';
        } finally {
            isLoading = false;
        }
    }
    
    async function submitAnswer(answerIndex) {
        if (!gameActive || isWaitingForGif || isLoading) {
            console.log('Cannot submit');
            return;
        }
        
        console.log('Submitting answer:', answerIndex);
        
        disableOptions(true);
        
        const formData = new FormData();
        formData.append('action', 'answer');
        formData.append('question_index', currentQuestionIndex);
        formData.append('answer', answerIndex);
        
        try {
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            
            const text = await response.text();
            console.log('Response text:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON. Response was:', text);
                document.getElementById('feedbackMsg').innerHTML = '❌ Server error. Please check console.';
                disableOptions(false);
                return;
            }
            
            const feedbackMsgEl = document.getElementById('feedbackMsg');
            
            if (data.is_correct) {
                correctCount = data.correct_count;
                updateScoreDisplay();
                if (feedbackMsgEl) feedbackMsgEl.innerHTML = `✅ Correct!<br>📖 ${data.explanation}`;
            } else {
                if (feedbackMsgEl) feedbackMsgEl.innerHTML = `❌ Wrong! Correct answer: ${data.correct_answer}<br>📖 ${data.explanation}`;
            }
            
            currentQuestionIndex++;
            updateScoreDisplay();
            
            if (data.all_done) {
                finishGame();
            } else {
                setTimeout(() => {
                    loadQuestion();
                }, 1500);
            }
            
        } catch (error) {
            console.error('Failed to submit answer', error);
            document.getElementById('feedbackMsg').innerHTML = '❌ Error submitting answer. Please try again.';
            disableOptions(false);
        }
    }
    
    function finishGame() {
        gameActive = false;
        quizStarted = false;
        playGifByScore(correctCount);
    }
    
    function playGifByScore(score) {
        let gifPath;
        
        if (score === 0) {
            gifPath = `GIF/fail.gif`;
        } else if (score === 1) {
            gifPath = `GIF/GIF1.gif`;
        } else if (score === 2) {
            gifPath = `GIF/GIF2.gif`;
        } else if (score === 3) {
            gifPath = `GIF/GIF3.gif`;
        } else if (score === 4) {
            gifPath = `GIF/GIF4.gif`;
        } else if (score === 5) {
            gifPath = `GIF/GIF5.gif`;
        } else if (score === 6) {
            gifPath = `GIF/GIF6.gif`;
        } else {
            gifPath = `GIF/GIF7.gif`;
        }
        
        isWaitingForGif = true;
        gifPlayCount = 0;
        
        const gifOverlay = document.getElementById('gifOverlay');
        const gifImage = document.getElementById('gifImage');
        
        if (gifImage) gifImage.src = gifPath;
        if (gifOverlay) gifOverlay.classList.add('active');
        
        let scoreText = document.getElementById('gifScoreText');
        if (!scoreText && gifOverlay) {
            const gifContent = document.querySelector('.gif-content');
            if (gifContent) {
                scoreText = document.createElement('div');
                scoreText.id = 'gifScoreText';
                scoreText.style.cssText = 'color: #ffd966; font-size: 28px; font-weight: bold; margin-bottom: 15px; text-shadow: 2px 2px 4px black; text-align: center;';
                gifContent.insertBefore(scoreText, gifContent.firstChild);
            }
        }
        
        if (scoreText) {
            if (score === 0) {
                scoreText.innerHTML = '❌ PERFORMANCE FAILED! ❌<br>Better luck next time!';
            } else {
                scoreText.innerHTML = `🎭 YOUR SCORE: ${score} / 8 🎭`;
            }
        }
        
        if (gifInterval) clearInterval(gifInterval);
        gifInterval = setInterval(() => {
            gifPlayCount++;
            if (gifPlayCount >= 3) {
                clearInterval(gifInterval);
            } else {
                if (gifImage) gifImage.src = gifPath;
            }
        }, 2000);
    }
    
    function closeGifAndShowResult() {
        if (gifInterval) clearInterval(gifInterval);
        const gifOverlay = document.getElementById('gifOverlay');
        if (gifOverlay) gifOverlay.classList.remove('active');
        showResult();
    }
    
    function showResult() {
        const quizArea = document.getElementById('quizArea');
        const resultArea = document.getElementById('resultArea');
        const scoreDisplay = document.getElementById('scoreDisplay');
        const endingMessageEl = document.getElementById('endingMessage');
        
        if (quizArea) quizArea.classList.remove('active');
        if (resultArea) resultArea.classList.add('active');
        
        if (scoreDisplay) {
            scoreDisplay.innerHTML = '';
            for (let i = 0; i < 7; i++) {
                const numDiv = document.createElement('div');
                numDiv.className = 'score-number';
                numDiv.innerText = correctCount;
                scoreDisplay.appendChild(numDiv);
            }
        }
        
        let message = '';
        if (correctCount === 0) {
            message = '😢 You failed the performance! Keep practicing and try again!';
        } else if (correctCount <= 3) {
            message = '💪 Not bad! But you need more practice!';
        } else if (correctCount <= 5) {
            message = '🎭 Good job! The audience enjoyed your performance!';
        } else if (correctCount <= 7) {
            message = '🌟 Excellent! The crowd loved you!';
        } else {
            message = '🎭✨ PERFECT! You are a true star! Standing ovation! ✨🎭';
        }
        
        if (endingMessageEl) endingMessageEl.innerHTML = message;
        
        gameActive = false;
    }
    
    async function startQuiz() {
    console.log('Start quiz clicked');
    

    const formData = new FormData();
    formData.append('action', 'reset');
    
    try {
        await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });
    } catch(e) {
        console.error('Reset before start failed', e);
    }
    
    quizStarted = true;
    gameActive = true;
    currentQuestionIndex = 0;
    correctCount = 0;
    isLoading = false;
    isWaitingForGif = false;
    updateScoreDisplay();
    
    const dialogueArea = document.getElementById('dialogueArea');
    const quizArea = document.getElementById('quizArea');
    const resultArea = document.getElementById('resultArea');
    const feedbackMsgEl = document.getElementById('feedbackMsg');
    
    if (dialogueArea) dialogueArea.style.display = 'none';
    if (quizArea) quizArea.classList.add('active');
    if (resultArea) resultArea.classList.remove('active');
    if (feedbackMsgEl) feedbackMsgEl.innerHTML = '';
    
    loadQuestion();
}
    
    async function resetGame() {
        const formData = new FormData();
        formData.append('action', 'reset');
        
        try {
            await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            
            gameActive = true;
            quizStarted = false;
            currentQuestionIndex = 0;
            correctCount = 0;
            isWaitingForGif = false;
            isLoading = false;
            
            if (gifInterval) clearInterval(gifInterval);
            
            const scoreCountEl = document.getElementById('scoreCount');
            const progressFillEl = document.getElementById('progressFill');
            const progressTextEl = document.getElementById('progressText');
            const dialogueArea = document.getElementById('dialogueArea');
            const quizArea = document.getElementById('quizArea');
            const resultArea = document.getElementById('resultArea');
            const feedbackMsgEl = document.getElementById('feedbackMsg');
            const gifOverlay = document.getElementById('gifOverlay');
            const optionsContainer = document.getElementById('optionsContainer');
            
            if (scoreCountEl) scoreCountEl.innerText = '0';
            if (progressFillEl) progressFillEl.style.width = '0%';
            if (progressTextEl) progressTextEl.innerText = '0/8';
            if (dialogueArea) dialogueArea.style.display = 'block';
            if (quizArea) quizArea.classList.remove('active');
            if (resultArea) resultArea.classList.remove('active');
            if (feedbackMsgEl) feedbackMsgEl.innerHTML = '';
            if (gifOverlay) gifOverlay.classList.remove('active');
            if (optionsContainer) optionsContainer.innerHTML = '';
            
        } catch (error) {
            console.error('Reset failed', error);
            location.reload();
        }
    }
    
    const startBtn = document.getElementById('startQuizBtn');
    const resetBtn = document.getElementById('resetBtn');
    const playAgainBtn = document.getElementById('playAgainBtn');
    const closeGifBtn = document.getElementById('closeGifBtn');
    
    if (startBtn) {
        startBtn.removeEventListener('click', startQuiz);
        startBtn.addEventListener('click', startQuiz);
        console.log('Start button bound');
    } else {
        console.error('Start button not found');
    }
    
    if (resetBtn) {
        resetBtn.removeEventListener('click', resetGame);
        resetBtn.addEventListener('click', resetGame);
    }
    
    if (playAgainBtn) {
        playAgainBtn.removeEventListener('click', resetGame);
        playAgainBtn.addEventListener('click', resetGame);
    }
    
    if (closeGifBtn) {
        closeGifBtn.removeEventListener('click', closeGifAndShowResult);
        closeGifBtn.addEventListener('click', closeGifAndShowResult);
    }
    
    const stageImage = document.getElementById('stageImage');
    if (stageImage && (!stageImage.src || stageImage.src === window.location.href)) {
        stageImage.src = 'stage.jpg';
    }
    
    console.log('Game initialized');
});