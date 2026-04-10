<?php
// stories.php - 英语趣味小故事模块
// 放在项目根目录，可直接访问，或通过悬浮按钮调用

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$user_id = intval($_SESSION['user_id']);
$nickname = $_SESSION['nickname'] ?? 'Learner';

// 10篇小故事（每篇约400字）
$stories = [
    1 => [
        'title' => 'The Clever Rabbit and the Lion',
        'content' => '<p>In a dense jungle, there lived a mighty lion who ruled over all the animals. He was powerful but also very cruel. Every day, he demanded that one animal come to his den to be his meal. The animals were terrified and lived in constant fear.</p>
<p>One day, it was the turn of a small rabbit. The rabbit was very clever and did not want to die. As he hopped slowly towards the lion\'s den, he thought of a plan. He found an old well deep in the forest and decided to use it to trick the lion.</p>
<p>When the rabbit arrived at the den, the lion was furious and roared, "You are late! I will eat you right now!" The rabbit pretended to be scared and said, "Please forgive me, Your Majesty. On my way here, I met another lion who wanted to eat me. He said he was the real king of the jungle."</p>
<p>The lion became angry and demanded to see this other lion. The rabbit led him to the old well. "He lives down there," said the rabbit. The lion looked into the well and saw his own reflection. Thinking it was another lion, he roared angrily. The reflection roared back. Enraged, the lion jumped into the well to attack his rival and drowned.</p>
<p>The rabbit returned to the other animals and told them what had happened. From that day on, the animals were free and lived in peace. The clever rabbit became a hero, and everyone learned that wisdom is often more powerful than strength.</p>'
    ],
    2 => [
        'title' => 'The Fox and the Grapes',
        'content' => '<p>On a warm summer afternoon, a hungry fox was wandering through the countryside in search of food. He had not eaten anything since morning, and his stomach growled with hunger. As he walked along a dusty path, he came across a vineyard.</p>
<p>The vineyard was filled with lush grapevines heavy with ripe, purple grapes. The grapes looked so juicy and sweet that the fox\'s mouth began to water. "Those would be perfect for my lunch," he thought, licking his lips.</p>
<p>The fox jumped up to reach the grapes, but they were too high. He took a few steps back and ran forward, leaping into the air with all his might. Again, his paws touched nothing but air. He tried again and again, each time failing to reach the tempting grapes.</p>
<p>After many attempts, the fox was exhausted and frustrated. He sat down beneath the vine, panting heavily. Finally, he stood up and said to himself, "Those grapes are probably sour anyway. I don\'t want them."</p>
<p>And with that, he walked away with his head held high, pretending he had never wanted the grapes in the first place. The moral of the story is that it is easy to despise what you cannot have. Sometimes, people pretend to dislike something simply because they cannot obtain it.</p>'
    ],
    3 => [
        'title' => 'The Ant and the Grasshopper',
        'content' => '<p>On a beautiful summer day, a grasshopper was hopping around the field, singing and playing his fiddle. He enjoyed the warm sunshine and the colorful flowers. He did not have a care in the world. As he played, he noticed an ant carrying a heavy grain of corn back to his nest.</p>
<p>"Come and play with me," said the grasshopper to the ant. "Why work so hard on such a lovely day? There is plenty of food everywhere. Let us enjoy the summer together." But the ant shook his head and said, "I am storing food for the winter. You should do the same, or you will be hungry when the cold weather comes."</p>
<p>The grasshopper laughed and said, "Winter is so far away! There is no need to worry now. I prefer to enjoy the present moment." So the ant continued working while the grasshopper continued playing. Day after day, the ant gathered food while the grasshopper sang and danced.</p>
<p>When winter finally arrived, the weather became freezing cold. Snow covered the ground, and there was no food to be found anywhere. The grasshopper was starving and freezing. He went to the ant\'s nest and begged for food. "Please help me," he said. "I have nothing to eat."</p>
<p>The ant replied, "What were you doing all summer while I was working?" The grasshopper said, "I was singing and playing." The ant said, "Well, now you can dance all winter long," and closed the door. The grasshopper learned a hard lesson that day: It is wise to prepare today for the needs of tomorrow.</p>'
    ],
    4 => [
        'title' => 'The Boy Who Cried Wolf',
        'content' => '<p>There once was a young shepherd boy who lived in a small village at the foot of a mountain. Every day, he took his sheep up the mountain to graze on the green grass. The boy was often bored and lonely, with no one to talk to but the sheep.</p>
<p>One afternoon, the boy had a mischievous idea. He decided to play a trick on the villagers. He ran down the mountain shouting at the top of his lungs, "Wolf! Wolf! A wolf is attacking my sheep!" The villagers heard his cries and rushed up the mountain with sticks and axes to help him.</p>
<p>When they arrived, they found no wolf. The boy laughed and said, "I fooled you all!" The villagers were angry but went back down the mountain. A few days later, the boy did it again. "Wolf! Wolf!" he cried. Again the villagers came running, and again there was no wolf. The boy laughed even harder.</p>
<p>Then one evening, a real wolf appeared. The boy screamed, "Wolf! Wolf! Please help me!" But this time, the villagers thought he was lying again. No one came to help. The wolf ate all the sheep, and the boy learned that if you lie too often, people will not believe you even when you tell the truth.</p>'
    ],
    5 => [
        'title' => 'The Tortoise and the Hare',
        'content' => '<p>In a peaceful forest, there lived a hare who was very proud of his speed. He would often boast to the other animals, "No one can beat me in a race! I am the fastest animal in the world!" The other animals grew tired of his arrogance, but no one dared to challenge him.</p>
<p>One day, the slow-moving tortoise spoke up. "I will race you," he said calmly. The hare burst out laughing. "You? Race me? That is the funniest thing I have ever heard!" But the tortoise insisted, and the other animals convinced the hare to accept the challenge.</p>
<p>The race began. The hare shot ahead and was soon far in front. He looked back and saw the tortoise barely moving. "This is too easy," thought the hare. "I have time for a nap." So he lay down under a tree and fell fast asleep.</p>
<p>Meanwhile, the tortoise kept moving slowly but steadily. He did not stop or give up. He passed the sleeping hare and continued toward the finish line. When the hare finally woke up, he ran as fast as he could, but it was too late. The tortoise had already crossed the finish line.</p>
<p>The hare learned that day that slow and steady wins the race. Speed is not everything; persistence and determination are just as important.</p>'
    ],
    6 => [
        'title' => 'The Greedy Dog',
        'content' => '<p>One afternoon, a hungry dog was searching for food. He wandered through the streets until he came to a butcher shop. The butcher had just left a juicy bone on the counter. The dog quickly grabbed the bone and ran away before anyone could catch him.</p>
<p>He ran to a quiet field near a river. He was about to enjoy his meal when he looked into the water. To his surprise, he saw another dog with a bone in its mouth. The dog did not know that he was looking at his own reflection.</p>
<p>"That bone looks bigger and better than mine," thought the greedy dog. "I want that bone too!" He opened his mouth to snatch the other dog\'s bone. But as soon as he opened his mouth, his own bone fell into the river and was swept away by the current.</p>
<p>The dog watched helplessly as his bone disappeared. He looked back into the water, but the other dog was gone too. He had lost everything because of his greed. The hungry dog walked away with nothing, having learned that it is better to be content with what you have than to lose everything by being greedy.</p>'
    ],
    7 => [
        'title' => 'The Wind and the Sun',
        'content' => '<p>One day, the Wind and the Sun were arguing about who was stronger. "I am the strongest," boasted the Wind. "I can blow down trees and destroy houses!" The Sun smiled and said, "Strength is not about destruction. Let us have a contest to see who can make a traveler take off his coat."</p>
<p>The Wind agreed. He went first. He blew with all his might, sending strong gusts of cold air at the traveler. But the harder the Wind blew, the tighter the traveler wrapped his coat around himself. The Wind grew tired and finally gave up.</p>
<p>Then it was the Sun\'s turn. The Sun gently shone his warm rays upon the traveler. The weather became pleasant and warm. The traveler, feeling the heat, unbuttoned his coat. As the Sun shone even brighter, the traveler took off his coat completely and sat down in the shade to rest.</p>
<p>The Sun had won without using any force. The Wind learned that kindness and gentleness are often more powerful than anger and force. The moral of the story is that persuasion is better than force, and warmth and kindness can achieve what aggression cannot.</p>'
    ],
    8 => [
        'title' => 'The Lion and the Mouse',
        'content' => '<p>One hot afternoon, a mighty lion was sleeping under a large tree. A little mouse came running by and accidentally ran across the lion\'s nose. The lion woke up with a start and grabbed the tiny mouse with his huge paw.</p>
<p>"How dare you wake me!" roared the lion. "I am going to eat you!" The mouse trembled with fear and begged, "Please, kind lion, let me go! If you spare my life, I will repay your kindness one day." The lion laughed loudly at the idea that a tiny mouse could ever help him.</p>
<p>"You? Help me? That is ridiculous!" said the lion. But he was amused and decided to let the mouse go. "Run along, little one," he said. "Your words have made me laugh." The mouse thanked the lion and ran away.</p>
<p>A few days later, the lion was walking through the forest when he stepped into a hunter\'s net. The more he struggled, the tighter the net became. He roared for help, but no one came. Then the little mouse heard the lion\'s cries and ran to help.</p>
<p>The mouse gnawed through the ropes with his sharp teeth and freed the lion. "You laughed at me once," said the mouse, "but now you see that even a small mouse can help a mighty lion." The lion thanked the mouse and learned that no act of kindness is ever wasted, no matter how small the creature.</p>'
    ],
    9 => [
        'title' => 'The Goose That Laid the Golden Eggs',
        'content' => '<p>Once upon a time, a poor farmer and his wife lived in a small cottage. They worked hard every day but barely had enough food to eat. One morning, the farmer went to check on his goose and found a wonderful surprise. The goose had laid a golden egg!</p>
<p>The farmer could not believe his eyes. He picked up the egg and found that it was made of pure gold. He sold the egg for a lot of money. The same thing happened the next day, and the day after that. Every morning, the goose laid a golden egg.</p>
<p>The farmer and his wife became rich. They bought a new house, new clothes, and plenty of food. But soon, they grew impatient. "Why wait for one egg each day?" said the greedy wife. "The goose must have many golden eggs inside her. Let us cut her open and take them all!"</p>
<p>The farmer agreed. He took a knife and killed the goose. But when he cut her open, he found no golden eggs at all. The goose was just like any other goose. Now they had no golden eggs, and they had killed the goose that had made them rich.</p>
<p>The foolish couple learned a hard lesson: Those who are greedy often lose everything they have. It is better to be patient and grateful for what you have than to destroy your source of good fortune.</p>'
    ],
    10 => [
        'title' => 'The Crow and the Pitcher',
        'content' => '<p>On a hot summer day, a thirsty crow flew across the countryside searching for water. The sun was blazing, and the crow had not had a drink in a long time. His throat was dry, and he was growing weak. Finally, he spotted a pitcher near a farmhouse.</p>
<p>The crow flew down to the pitcher with great hope. He looked inside and saw that there was water at the bottom. But the pitcher had a narrow neck, and the crow\'s beak could not reach the water. The crow tried and tried, but it was no use. He was about to give up when he had an idea.</p>
<p>Nearby, there were many small pebbles. The crow picked up one pebble in his beak and dropped it into the pitcher. Then he picked up another and dropped it in. Slowly, the water level began to rise. The crow continued dropping pebbles into the pitcher, one by one.</p>
<p>After many pebbles, the water rose high enough for the crow to reach. He drank the cool, refreshing water and flew away happily. The crow had solved his problem by thinking creatively. The moral is that necessity is the mother of invention. When you face a challenge, use your mind to find a solution.</p>'
    ]
];

// 获取当前选中的故事
$selected_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$current_story = $stories[$selected_id] ?? $stories[1];
$selected_id = array_key_exists($selected_id, $stories) ? $selected_id : 1;

// 获取该故事的批注
$annotations = [];
$anno_sql = "SELECT * FROM user_annotations WHERE user_id = $user_id AND article_id = -$selected_id ORDER BY created_at DESC";
// 使用负数article_id区分故事模块(-1到-10对应故事1-10)
$anno_result = $conn->query($anno_sql);
if ($anno_result) {
    while ($row = $anno_result->fetch_assoc()) {
        $annotations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>英语趣味小故事 - Word Garden</title>
    <style>
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --soft-green-bg: #f2f7f5;
            --warm-orange: #e67e22;
            --card-shadow: 0 10px 30px rgba(27, 67, 50, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Georgia', serif;
            background: linear-gradient(135deg, #fef9e6 0%, #f2f7f5 100%);
            margin: 0;
            min-height: 100vh;
            color: #2d3436;
        }
        .nav-header {
            width: 100%; height: 70px; background: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 50px; box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: fixed; top: 0; z-index: 1000;
        }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a { text-decoration: none; color: #666; font-size: 14px; padding: 5px 12px; border-radius: 8px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }
        
        .hero {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: white;
            padding: 110px 20px 60px;
            text-align: center;
        }
        .hero h1 {
            margin: 0;
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .hero p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 15px;
        }
        
        .main-content {
            max-width: 1200px;
            margin: -40px auto 60px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }
        
        .story-layout {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .story-sidebar {
            width: 280px;
            flex-shrink: 0;
            background: white;
            border-radius: 24px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            height: fit-content;
        }
        .story-sidebar h3 {
            color: var(--primary-green);
            font-size: 1.1rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--soft-green-bg);
        }
        .story-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .story-list li {
            margin-bottom: 8px;
        }
        .story-list a {
            display: block;
            padding: 10px 12px;
            background: var(--soft-green-bg);
            border-radius: 12px;
            text-decoration: none;
            color: #2d3436;
            font-size: 13px;
            transition: 0.2s;
        }
        .story-list a:hover {
            background: #e0f0e8;
        }
        .story-list a.active {
            background: var(--primary-green);
            color: white;
        }
        
        .story-panel {
            flex: 1;
            background: white;
            border-radius: 28px;
            padding: 40px 50px;
            box-shadow: var(--card-shadow);
        }
        .story-title {
            font-size: 2rem;
            color: var(--primary-green);
            margin-bottom: 15px;
            font-family: 'Georgia', serif;
            border-left: 5px solid var(--warm-orange);
            padding-left: 25px;
        }
        .story-tip {
            background: #fff8e7;
            padding: 10px 20px;
            border-radius: 30px;
            display: inline-block;
            font-size: 13px;
            color: #b86b1f;
            margin-bottom: 25px;
        }
        .story-content {
            line-height: 1.9;
            font-size: 1.05rem;
            color: #2d3e36;
            font-family: 'Georgia', serif;
        }
        .story-content p {
            margin-bottom: 1.2em;
        }
        
        .annotation-highlight {
            background-color: #fff3cd;
            cursor: pointer;
            border-radius: 4px;
            padding: 2px 0;
            transition: background 0.2s;
        }
        .annotation-highlight:hover {
            background-color: #ffe0a3;
        }
        
        .annotation-popup, .view-annotation-popup {
            position: absolute;
            background: white;
            border: 2px solid var(--accent-green);
            border-radius: 16px;
            padding: 15px;
            width: 300px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 1001;
        }
        .annotation-popup textarea {
            width: 100%;
            height: 80px;
            margin: 10px 0;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
        }
        .annotation-popup button, .view-annotation-popup button {
            background: var(--accent-green);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            margin-right: 8px;
        }
        .delete-btn {
            background: #dc3545 !important;
        }
        
        .word-popup {
            position: absolute;
            background: white;
            border: 2px solid var(--accent-green);
            border-radius: 16px;
            padding: 12px;
            max-width: 300px;
            z-index: 1000;
            display: none;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--soft-green-bg);
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            color: var(--primary-green);
            margin-bottom: 20px;
            transition: 0.2s;
        }
        .back-link:hover {
            background: var(--accent-green);
            color: white;
        }
        
        @media (max-width: 800px) {
            .story-layout { flex-direction: column; }
            .story-sidebar { width: 100%; }
            .story-panel { padding: 25px; }
            .story-title { font-size: 1.5rem; }
            .nav-header { padding: 0 20px; }
            .nav-links { gap: 8px; }
            .nav-links a { font-size: 11px; padding: 4px 8px; }
        }
    </style>
</head>
<body>

<nav class="nav-header">
    <a href="home.php" class="nav-logo">Word Garden</a>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="TED.php">TED Talk</a>
        <a href="ielts.php">IELTS</a>
        <a href="daily_talk.php">Daily Talk</a>
        <a href="reading.php">Reading</a>
        <a href="stories.php" class="active">📖 Stories</a>
        <a href="vocabulary.php">Vocabulary</a>
        <a href="calendar.php">Calendar</a>
        <a href="profile.php">Profile</a>
    </div>
</nav>

<header class="hero">
    <h1>📚 English Fun Stories</h1>
    <p>✨ 双击任意单词查词 · 选中文字右键添加批注 · 点击高亮查看笔记 ✨</p>
</header>

<main class="main-content">
    <a href="reading.php" class="back-link">← 返回文章花园</a>
    
    <div class="story-layout">
        <div class="story-sidebar">
            <h3>📖 故事列表 (10篇)</h3>
            <ul class="story-list">
                <?php foreach ($stories as $id => $story): ?>
                    <li>
                        <a href="stories.php?id=<?php echo $id; ?>" class="<?php echo ($selected_id == $id) ? 'active' : ''; ?>">
                            <?php echo $id . '. ' . htmlspecialchars($story['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; font-size: 12px; color: #888; text-align: center;">
                💡 小提示：双击单词查词<br>选中文字可添加批注
            </div>
        </div>
        
        <div class="story-panel">
            <div class="story-title"><?php echo htmlspecialchars($current_story['title']); ?></div>
            <div class="story-tip">📝 双击单词查看翻译 | 选中文字右键批注</div>
            <div class="story-content" id="story-content">
                <?php echo $current_story['content']; ?>
            </div>
        </div>
    </div>
</main>

<script>
const storyId = <?php echo $selected_id; ?>;
const actualArticleId = -storyId;  // 使用负数ID区分故事模块的批注
const existingAnnotations = <?php echo json_encode($annotations); ?>;

function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// 应用批注高亮
function applyAnnotations() {
    const contentDiv = document.getElementById('story-content');
    if (!contentDiv) return;
    let html = contentDiv.innerHTML;
    const sorted = [...existingAnnotations].sort((a, b) => b.selected_text.length - a.selected_text.length);
    sorted.forEach(anno => {
        const pattern = new RegExp(`(${escapeRegex(anno.selected_text)})`, 'g');
        html = html.replace(pattern, `<span class="annotation-highlight" data-annotation-id="${anno.annotation_id}" data-text="${escapeHtml(anno.selected_text)}" data-note="${escapeHtml(anno.note)}">$1</span>`);
    });
    contentDiv.innerHTML = html;
}

// 批注弹窗
let annotationPopup = null;
function createAnnotationPopup() {
    const div = document.createElement('div');
    div.className = 'annotation-popup';
    div.style.display = 'none';
    document.body.appendChild(div);
    return div;
}
annotationPopup = createAnnotationPopup();

function showAnnotationPopup(selectedText, x, y) {
    annotationPopup.innerHTML = `
        <strong>📝 添加批注</strong>
        <div style="font-size:12px; color:#666; margin:8px 0; background:#f5f5f5; padding:6px; border-radius:6px;">"${escapeHtml(selectedText.substring(0, 80))}${selectedText.length > 80 ? '...' : ''}"</div>
        <textarea id="annotation-note" placeholder="写下你的想法或翻译..."></textarea>
        <button onclick="saveAnnotation('${selectedText.replace(/'/g, "\\'")}')">保存批注</button>
        <button onclick="closeAnnotationPopup()">取消</button>
    `;
    annotationPopup.style.left = x + 'px';
    annotationPopup.style.top = y + 'px';
    annotationPopup.style.display = 'block';
}

function saveAnnotation(selectedText) {
    const note = document.getElementById('annotation-note')?.value || '';
    fetch('save_annotation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `article_id=${actualArticleId}&selected_text=${encodeURIComponent(selectedText)}&note=${encodeURIComponent(note)}`
    }).then(response => response.json()).then(data => {
        if (data.success) {
            closeAnnotationPopup();
            location.reload();
        } else {
            alert('保存失败：' + (data.error || '未知错误'));
        }
    });
}

function closeAnnotationPopup() {
    if (annotationPopup) annotationPopup.style.display = 'none';
}

// 查看批注弹窗
let viewPopup = null;
function createViewPopup() {
    const div = document.createElement('div');
    div.className = 'view-annotation-popup';
    div.style.display = 'none';
    document.body.appendChild(div);
    return div;
}
viewPopup = createViewPopup();

function showAnnotationDetail(text, note, annotationId, x, y) {
    viewPopup.innerHTML = `
        <strong>📝 批注内容</strong>
        <div style="font-size:12px; color:#666; margin:8px 0; background:#f5f5f5; padding:6px; border-radius:6px;">"${escapeHtml(text.substring(0, 100))}"</div>
        <div style="margin:10px 0; padding:8px; background:#f9f9f9; border-radius:8px;">${escapeHtml(note) || '<em>无批注内容</em>'}</div>
        <button onclick="deleteAnnotation(${annotationId})" class="delete-btn">删除批注</button>
        <button onclick="closeViewPopup()">关闭</button>
    `;
    viewPopup.style.left = x + 'px';
    viewPopup.style.top = y + 'px';
    viewPopup.style.display = 'block';
}

function deleteAnnotation(annotationId) {
    if (!confirm('确定要删除这条批注吗？')) return;
    fetch('delete_annotation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `annotation_id=${annotationId}`
    }).then(response => response.json()).then(data => {
        if (data.success) {
            closeViewPopup();
            location.reload();
        } else {
            alert('删除失败');
        }
    });
}

function closeViewPopup() {
    if (viewPopup) viewPopup.style.display = 'none';
}

// 查词功能（复用现有 vocabulary.php API）
let wordPopup = null;
function createWordPopup() {
    const div = document.createElement('div');
    div.className = 'word-popup';
    document.body.appendChild(div);
    return div;
}
wordPopup = createWordPopup();

async function lookupWord(word) {
    try {
        const response = await fetch(`vocabulary.php?view=search&search=${encodeURIComponent(word)}&ajax=1`);
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');
        const row = doc.querySelector('.data-table tbody tr');
        if (row && row.cells[2]) {
            return { definition: row.cells[2].innerText.trim() };
        }
        return { definition: null };
    } catch (err) {
        return { definition: null };
    }
}

function showWordPopup(word, x, y, definition) {
    wordPopup.innerHTML = `<strong>🔍 ${escapeHtml(word)}</strong><br>${definition ? escapeHtml(definition) : '📖 暂未收录此单词<br><small>你可以在词汇本中添加</small>'}`;
    wordPopup.style.left = (x + 10) + 'px';
    wordPopup.style.top = (y - 60) + 'px';
    wordPopup.style.display = 'block';
    setTimeout(() => {
        wordPopup.style.display = 'none';
    }, 4000);
}

// 事件绑定
document.addEventListener('DOMContentLoaded', () => {
    applyAnnotations();
    
    const contentDiv = document.getElementById('story-content');
    if (!contentDiv) return;
    
    // 右键添加批注
    contentDiv.addEventListener('contextmenu', (e) => {
        if (e.target.closest('.annotation-highlight')) return;
        const selection = window.getSelection();
        const selectedText = selection.toString().trim();
        if (selectedText && selectedText.length > 0) {
            e.preventDefault();
            const rect = selection.getRangeAt(0).getBoundingClientRect();
            showAnnotationPopup(selectedText, rect.left + window.scrollX + 10, rect.top + window.scrollY - 80);
        }
    });
    
    // 点击高亮文字查看批注
    contentDiv.addEventListener('click', (e) => {
        const highlight = e.target.closest('.annotation-highlight');
        if (highlight) {
            e.preventDefault();
            e.stopPropagation();
            const text = highlight.dataset.text;
            const note = highlight.dataset.note;
            const annoId = highlight.dataset.annotationId;
            const rect = highlight.getBoundingClientRect();
            showAnnotationDetail(text, note, annoId, rect.left + window.scrollX + 10, rect.top + window.scrollY - 60);
        } else {
            closeViewPopup();
        }
    });
    
    // 双击查词
    contentDiv.addEventListener('dblclick', async (e) => {
        const selection = window.getSelection();
        const selectedText = selection.toString().trim();
        if (selectedText && /^[a-zA-Z]+(?:[-\'][a-zA-Z]+)*$/.test(selectedText)) {
            const word = selectedText.toLowerCase();
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            const { definition } = await lookupWord(word);
            showWordPopup(word, rect.left + window.scrollX, rect.top + window.scrollY, definition);
        }
    });
});

// 点击其他地方关闭弹窗
document.addEventListener('click', (e) => {
    if (wordPopup && !wordPopup.contains(e.target)) {
        wordPopup.style.display = 'none';
    }
    if (viewPopup && !viewPopup.contains(e.target)) {
        closeViewPopup();
    }
});
</script>
</body>
</html>