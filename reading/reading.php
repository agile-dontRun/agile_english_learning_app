<?php
// reading.php - Word Garden Reading Module

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_connect.php';

$user_id = intval($_SESSION['user_id']);
$nickname = $_SESSION['nickname'] ?? 'Learner';

$username = '';
$db_avatar = '';
$user_stmt = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
if ($user_stmt) {
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_data = $user_stmt->get_result()->fetch_assoc();
    if ($user_data) {
        $username = $user_data['username'] ?? '';
        $nickname = !empty($user_data['nickname']) ? $user_data['nickname'] : ($username ?: $nickname);
        $db_avatar = $user_data['avatar_url'] ?? '';
    }
    $user_stmt->close();
}

$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . htmlspecialchars($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . htmlspecialchars($first_letter) . '</div>';
}

function build_passage_label($article) {
    $cambridge = isset($article['cambridge_book']) ? intval($article['cambridge_book']) : 0;
    $test = isset($article['test_number']) ? intval($article['test_number']) : 0;
    $part = isset($article['part_number']) ? intval($article['part_number']) : 0;

    if (!$cambridge && !$test && !$part) {
        return 'General Reading';
    }

    $parts = [];
    if ($cambridge) $parts[] = 'C' . $cambridge;
    if ($test) $parts[] = 'T' . $test;
    if ($part) $parts[] = 'P' . $part;

    return implode(' ', $parts);
}

$cambridge = isset($_GET['cambridge']) ? intval($_GET['cambridge']) : 0;
$test = isset($_GET['test']) ? intval($_GET['test']) : 0;
$part = isset($_GET['part']) ? intval($_GET['part']) : 0;

$filter_params = [];
if ($cambridge > 0) $filter_params['cambridge'] = $cambridge;
if ($test > 0) $filter_params['test'] = $test;
if ($part > 0) $filter_params['part'] = $part;
$filter_query = http_build_query($filter_params);

$sql = "SELECT a.*, 
        (SELECT COUNT(*) FROM user_favorites WHERE article_id = a.article_id AND user_id = $user_id) as is_favorited
        FROM articles a 
        WHERE 1=1";
if ($cambridge > 0) $sql .= " AND a.cambridge_book = $cambridge";
if ($test > 0) $sql .= " AND a.test_number = $test";
if ($part > 0) $sql .= " AND a.part_number = $part";
$sql .= " ORDER BY
            CASE WHEN a.cambridge_book IS NULL THEN 1 ELSE 0 END,
            a.cambridge_book DESC,
            a.test_number ASC,
            a.part_number ASC,
            a.created_at DESC";
$articles_result = $conn->query($sql);

$fav_sql = "SELECT a.article_id, a.title, a.cambridge_book, a.test_number, a.part_number
            FROM articles a 
            JOIN user_favorites f ON a.article_id = f.article_id 
            WHERE f.user_id = $user_id
            ORDER BY
                CASE WHEN a.cambridge_book IS NULL THEN 1 ELSE 0 END,
                a.cambridge_book DESC,
                a.test_number ASC,
                a.part_number ASC,
                f.created_at DESC";
$fav_result = $conn->query($fav_sql);

$cambridge_options = [];
$test_options = [];
$part_options = [];

$cambridge_result = $conn->query("SELECT DISTINCT cambridge_book FROM articles WHERE cambridge_book IS NOT NULL ORDER BY cambridge_book DESC");
if ($cambridge_result) {
    while ($row = $cambridge_result->fetch_assoc()) {
        $cambridge_options[] = intval($row['cambridge_book']);
    }
}

$test_where = $cambridge > 0
    ? " WHERE test_number IS NOT NULL AND cambridge_book = $cambridge"
    : " WHERE test_number IS NOT NULL";
$test_result = $conn->query("SELECT DISTINCT test_number FROM articles{$test_where} ORDER BY test_number ASC");
if ($test_result) {
    while ($row = $test_result->fetch_assoc()) {
        $test_options[] = intval($row['test_number']);
    }
}

$part_conditions = ["part_number IS NOT NULL"];
if ($cambridge > 0) $part_conditions[] = "cambridge_book = $cambridge";
if ($test > 0) $part_conditions[] = "test_number = $test";
$part_where = ' WHERE ' . implode(' AND ', $part_conditions);
$part_result = $conn->query("SELECT DISTINCT part_number FROM articles{$part_where} ORDER BY part_number ASC");
if ($part_result) {
    while ($row = $part_result->fetch_assoc()) {
        $part_options[] = intval($row['part_number']);
    }
}

$selected_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$current_article = null;
$annotations = [];
$is_favorited = false;

if ($selected_id) {
    $stmt = $conn->prepare("SELECT * FROM articles WHERE article_id = ?");
    $stmt->bind_param("i", $selected_id);
    $stmt->execute();
    $current_article = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $fav_check = $conn->query("SELECT * FROM user_favorites WHERE user_id = $user_id AND article_id = $selected_id");
    $is_favorited = $fav_check->num_rows > 0;
    
    $anno_sql = "SELECT * FROM user_annotations WHERE user_id = $user_id AND article_id = $selected_id ORDER BY created_at DESC";
    $anno_result = $conn->query($anno_sql);
    while ($row = $anno_result->fetch_assoc()) {
        $annotations[] = $row;
    }
    
    $conn->query("INSERT INTO user_reading_progress (user_id, article_id, last_read_at) 
                  VALUES ($user_id, $selected_id, NOW()) 
                  ON DUPLICATE KEY UPDATE last_read_at = NOW()");
} else {
    if ($articles_result && $articles_result->num_rows > 0) {
        $first = $articles_result->fetch_assoc();
        $selected_id = $first['article_id'];
        $current_article = $first;
        $articles_result->data_seek(0);
        
        $fav_check = $conn->query("SELECT * FROM user_favorites WHERE user_id = $user_id AND article_id = $selected_id");
        $is_favorited = $fav_check->num_rows > 0;
        
        $anno_sql = "SELECT * FROM user_annotations WHERE user_id = $user_id AND article_id = $selected_id";
        $anno_result = $conn->query($anno_sql);
        while ($row = $anno_result->fetch_assoc()) {
            $annotations[] = $row;
        }
    }
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading Room - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --oxford-blue: #002147;
            --oxford-blue-light: #003066;
            --oxford-gold: #c4a661;
            --oxford-gold-light: #d4b671;
            --white: #ffffff;
            --bg-light: #f4f7f6;
            --panel-bg: #fbfcfd;
            --text-dark: #333333;
            --text-light: #666666;
            --border-color: #e0e0e0;
            --card-shadow: 0 12px 30px rgba(0, 33, 71, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Open Sans', Arial, sans-serif;
            background: var(--bg-light);
            margin: 0;
            color: var(--text-dark);
        }
        h1, h2, h3, h4 {
            font-family: 'PT Serif', Georgia, serif;
            letter-spacing: 0.4px;
        }
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }
        .college-logo:hover { transform: scale(1.02); }
        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
        .navbar-links a {
            color: #ffffff;
            text-decoration: none;
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 800;
            padding: 0 20px;
            height: 100%;
            display: flex;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
            transition: all 0.3s ease;
            -webkit-font-smoothing: antialiased;
        }
        .navbar-links a:hover {
            color: var(--oxford-gold);
            background-color: rgba(255, 255, 255, 0.05);
        }
        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none !important; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown-menu li { list-style: none !important; margin: 0; padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .dropdown-menu li:last-child { border-bottom: none; }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; justify-content: flex-start; width: 100%; box-sizing: border-box; text-decoration: none !important; display: block; font-weight: 400; height: auto; }
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }
        .navbar-links li:hover .dropdown-menu, .dropdown:hover .dropdown-menu { display: block; }
        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; border: 2px solid var(--oxford-gold); line-height: 1; box-sizing: border-box; }
        .navbar-right .dropdown-menu {
            background-color: var(--white);
            border-top: none;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
            font-family: 'Playfair Display', serif;
        }
        .navbar-right .dropdown-menu li div[style*="font-size:12px"] {
            font-family: 'Playfair Display', serif !important;
            font-style: italic;
            letter-spacing: 0.5px;
            color: #888 !important;
        }
        .navbar-right .dropdown-menu li div[style*="font-size:16px"] {
            font-family: 'Playfair Display', serif !important;
            font-weight: 800;
            color: var(--oxford-blue) !important;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .navbar-right .dropdown-menu li a {
            font-family: 'Playfair Display', serif !important;
            font-weight: 700;
            font-size: 15px;
            color: var(--oxford-blue) !important;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }
        .navbar-right .dropdown-menu li a:hover {
            background-color: #f8fafc !important;
            color: var(--oxford-gold) !important;
            padding-left: 25px;
        }
        
        .hero-mini {
            background: linear-gradient(rgba(0, 33, 71, 0.78), rgba(0, 33, 71, 0.78)), linear-gradient(135deg, #00152d 0%, #003066 100%);
            color: var(--white);
            padding: 140px 20px 95px;
            text-align: center;
            text-shadow: 0 2px 8px rgba(0,0,0,0.35);
        }
        .hero-mini h1 {
            margin: 0 0 14px;
            font-size: 3rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .hero-mini p {
            margin: 0 auto;
            max-width: 820px;
            font-size: 1.05rem;
            opacity: 0.95;
        }
        
        .main-content { max-width: 1320px; margin: -55px auto 60px; padding: 0 20px; position: relative; z-index: 10; }
        .reading-layout { display: flex; gap: 30px; }
        
        .sidebar {
            width: 300px;
            flex-shrink: 0;
            background: var(--white);
            border-radius: 8px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            height: fit-content;
            border-top: 4px solid var(--oxford-gold);
        }
        .sidebar h3 {
            color: var(--oxford-blue);
            font-size: 1.2rem;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .filter-group { margin-bottom: 25px; }
        .filter-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--white);
            color: var(--text-dark);
            font-size: 14px;
        }
        
        .article-list, .favorite-list { list-style: none; padding: 0; margin: 15px 0 0; max-height: 300px; overflow-y: auto; }
        .article-list li, .favorite-list li { margin-bottom: 8px; }
        .article-list a, .favorite-list a {
            display: block;
            padding: 12px 14px;
            background: #f8fafc;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 13px;
            transition: 0.2s;
            border: 1px solid #edf1f5;
        }
        .article-list a:hover, .favorite-list a:hover { background: #eef3f9; border-color: #d9e4ef; }
        .article-list a.active { background: var(--oxford-blue); color: white; border-color: var(--oxford-blue); }
        .favorite-list a { background: #fcfaf4; border-left: 3px solid var(--oxford-gold); }
        .article-item-title { display: block; font-weight: 600; }
        .article-item-meta { display: block; margin-top: 5px; font-size: 11px; color: #7a8696; letter-spacing: 0.08em; text-transform: uppercase; }
        .article-list a.active .article-item-meta { color: rgba(255,255,255,0.8); }
        
        .article-panel {
            flex: 1;
            background: var(--white);
            border-radius: 8px;
            padding: 38px 46px;
            box-shadow: var(--card-shadow);
            min-height: 500px;
            border-top: 4px solid var(--oxford-gold);
        }
        .article-title { font-size: 2rem; color: var(--oxford-blue); margin-bottom: 8px; border-left: 4px solid var(--oxford-gold); padding-left: 20px; }
        .article-subtitle { margin: 0 0 22px 24px; font-size: 0.92rem; color: #7a8696; letter-spacing: 0.12em; text-transform: uppercase; }
        .article-meta { display: flex; gap: 15px; margin-bottom: 28px; color: var(--text-light); font-size: 13px; flex-wrap: wrap; }
        .difficulty-badge { padding: 4px 12px; border-radius: 20px; }
        .difficulty-badge.beginner { background: #d4edda; color: #155724; }
        .difficulty-badge.intermediate { background: #fff3cd; color: #856404; }
        .difficulty-badge.advanced { background: #f8d7da; color: #721c24; }
        .passage-badge { padding: 6px 12px; border-radius: 999px; background: #f5efe0; color: var(--oxford-blue); font-weight: 700; border: 1px solid #e7dcc0; }
        
        .article-content { line-height: 1.95; font-size: 1.05rem; color: #263444; }
        .article-content p { margin-bottom: 1.25em; }
        
        .annotation-highlight {
            background-color: #f5e7a9;
            cursor: pointer;
            border-radius: 4px;
            padding: 2px 0;
            transition: background 0.2s;
        }
        .annotation-highlight:hover { background-color: #eed783; }
        
        .action-bar { display: flex; gap: 15px; margin-top: 30px; padding-top: 22px; border-top: 1px solid var(--border-color); }
        .action-btn {
            border: none;
            padding: 11px 18px;
            border-radius: 4px;
            cursor: pointer;
            background: var(--oxford-blue);
            transition: all 0.3s;
            color: var(--white);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .action-btn.favorite.active {
            background: var(--oxford-gold);
            color: var(--white);
        }
        .action-btn:hover { transform: translateY(-2px); background: var(--oxford-blue-light); }
        
        .annotation-popup, .view-annotation-popup {
            position: absolute;
            background: white;
            border: 2px solid var(--oxford-blue);
            border-radius: 8px;
            padding: 15px;
            width: 300px;
            box-shadow: 0 12px 25px rgba(0,0,0,0.18);
            z-index: 1001;
        }
        .annotation-popup textarea { width: 100%; height: 80px; margin: 10px 0; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; }
        .annotation-popup button, .view-annotation-popup button { background: var(--oxford-blue); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-right: 8px; }
        .delete-btn { background: #dc3545 !important; }
        
        .word-popup {
            position: absolute;
            background: white;
            border: 2px solid var(--oxford-blue);
            border-radius: 8px;
            padding: 12px;
            max-width: 300px;
            z-index: 1000;
            display: none;
        }
        
        .resources-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            overflow: auto;
        }
        .resources-modal-content {
            background-color: var(--white);
            margin: 40px auto;
            padding: 0;
            width: 85%;
            max-width: 900px;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .resources-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
            background: var(--oxford-blue);
            border-radius: 8px 8px 0 0;
            color: white;
        }
        .resources-modal-header h2 { margin: 0; font-size: 1.6rem; }
        .resources-close { font-size: 32px; font-weight: bold; cursor: pointer; transition: 0.2s; line-height: 1; }
        .resources-close:hover { color: var(--oxford-gold-light); }
        .resources-modal-body { padding: 25px 30px; max-height: 60vh; overflow-y: auto; }
        .resource-category { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; }
        .resource-category:last-child { border-bottom: none; }
        .resource-category h3 { color: var(--oxford-blue); font-size: 1.2rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .resource-category ul { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px; }
        .resource-category li { padding: 8px 12px; background: #f8fafc; border-radius: 4px; font-size: 14px; }
        .resource-category li a { color: var(--oxford-blue); text-decoration: none; font-weight: 700; margin-right: 8px; }
        .resource-category li a:hover { text-decoration: underline; color: var(--oxford-gold); }
        .resources-modal-footer { padding: 15px 30px 25px; text-align: center; border-top: 1px solid #e0e0e0; }
        .resources-close-btn { background: var(--oxford-gold); color: white; border: none; padding: 10px 30px; border-radius: 4px; cursor: pointer; font-size: 14px; transition: 0.2s; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
        .resources-close-btn:hover { background: var(--oxford-blue); transform: translateY(-2px); }
        
        .story-fab {
            position: fixed;
            bottom: 40px;
            right: 40px;
            z-index: 100;
        }
        .story-button {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--oxford-gold), #b89447);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            font-size: 32px;
        }
        .story-button:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 28px rgba(0,0,0,0.25);
        }
        
        .story-modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            overflow: auto;
        }
        .story-modal-content {
            background-color: var(--white);
            margin: 30px auto;
            padding: 0;
            width: 90%;
            max-width: 1000px;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            animation: modalFadeIn 0.3s ease;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        .story-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
            background: var(--oxford-blue);
            border-radius: 8px 8px 0 0;
            color: white;
            flex-shrink: 0;
        }
        .story-modal-header h2 { margin: 0; font-size: 1.6rem; }
        .story-close { font-size: 32px; font-weight: bold; cursor: pointer; transition: 0.2s; line-height: 1; }
        .story-close:hover { color: var(--oxford-gold-light); }
        .story-modal-body {
            padding: 20px 30px;
            overflow-y: auto;
            flex: 1;
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }
        .story-sidebar-modal {
            width: 260px;
            flex-shrink: 0;
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            height: fit-content;
            border: 1px solid var(--border-color);
        }
        .story-sidebar-modal h3 {
            color: var(--oxford-blue);
            font-size: 1rem;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #ddd;
        }
        .story-list-modal {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .story-list-modal li {
            margin-bottom: 6px;
        }
        .story-list-modal a {
            display: block;
            padding: 8px 10px;
            background: white;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 12px;
            transition: 0.2s;
            border: 1px solid #edf1f5;
        }
        .story-list-modal a:hover {
            background: #eef3f9;
        }
        .story-list-modal a.active {
            background: var(--oxford-blue);
            color: white;
        }
        .story-panel-modal {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 25px;
            min-height: 400px;
            border: 1px solid var(--border-color);
        }
        .story-title-modal {
            font-size: 1.6rem;
            color: var(--oxford-blue);
            margin-bottom: 15px;
            font-family: 'Georgia', serif;
            border-left: 4px solid var(--oxford-gold);
            padding-left: 20px;
        }
        .story-tip-modal {
            background: #f5efe0;
            padding: 8px 16px;
            border-radius: 999px;
            display: inline-block;
            font-size: 12px;
            color: var(--oxford-blue);
            margin-bottom: 20px;
        }
        .story-content-modal {
            line-height: 1.9;
            font-size: 1rem;
            color: #263444;
            font-family: 'Georgia', serif;
        }
        .story-content-modal p {
            margin-bottom: 1em;
        }
        .story-footer {
            padding: 15px 30px 25px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            flex-shrink: 0;
        }
        .story-close-btn {
            background: var(--oxford-gold);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .story-close-btn:hover {
            background: var(--oxford-blue);
            transform: translateY(-2px);
        }
        
        @media (max-width: 980px) {
            .navbar { padding: 0 20px; height: auto; min-height: 80px; flex-wrap: wrap; }
            .navbar-left { width: 100%; flex-wrap: wrap; }
            .navbar-links { width: 100%; overflow-x: auto; padding-bottom: 10px; margin: 0; }
            .navbar-links a { padding: 14px 12px; white-space: nowrap; }
            .reading-layout { flex-direction: column; }
            .sidebar { width: 100%; }
            .article-panel { padding: 28px; }
            .story-modal-body { flex-direction: column; }
            .story-sidebar-modal { width: 100%; }
        }
        @media (max-width: 700px) {
            .hero-mini { padding: 150px 16px 85px; }
            .hero-mini h1 { font-size: 2.2rem; }
            .main-content { padding: 0 14px; }
            .resources-modal-content { width: 95%; margin: 20px auto; }
            .resource-category ul { grid-template-columns: 1fr; }
            .resources-modal-header h2 { font-size: 1.2rem; }
            .story-modal-content { width: 95%; margin: 20px auto; }
            .action-bar { flex-direction: column; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="home.php"><img src="college_logo.png" alt="Spires Academy Logo" class="college-logo"></a>
            
            <ul class="navbar-links">
                <li class="active"><a href="home.php">Home</a></li>
                <li class="dropdown">
                    <a href="#">Study &#9662;</a>
                    <ul class="dropdown-menu">
                        <li><a href="listening.php">Listening Center</a></li>
                        <li><a href="reading.php">Reading Room</a></li>
                        <li><a href="emma_server/speakAI.php">Emma Speaking</a></li>
                        <li><a href="writing.php">Writing Lab</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Games &#9662;</a>
                    <ul class="dropdown-menu">
                        <li><a href="vocabulary_game.php">Vocabulary Game</a></li>
                        <li><a href="galgame/galgame/index.html">Story game</a></li>
                    </ul>
                </li>
                <li><a href="forum.php">Community</a></li>
            </ul>
        </div>

        <div class="navbar-right dropdown">
            <?php echo $avatar_html; ?>
            <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?php echo htmlspecialchars($nickname); ?> &#9662;</span>
            <ul class="dropdown-menu" style="right:0; left:auto; margin-top:0; min-width:220px;">
                <li style="padding: 20px; background: #f8fafc; cursor:default;">
                    <div style="color:var(--text-light); font-size:12px; margin-bottom:5px;">Signed in as</div>
                    <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important; font-weight: 600;">Sign Out</a></li>
            </ul>
        </div>
    </nav>

<header class="hero-mini">
    <h1>Reading Room</h1>
    <p>Academic reading in the Spires Academy style. Double-click words to look them up, right-click selected text to annotate, and filter passages by Cambridge book, test and part.</p>
</header>

<main class="main-content">
    <div class="reading-layout">
        <div class="sidebar">
            <h3>Filter</h3>
            <div class="filter-group">
                <select id="cambridge-filter">
                    <option value="">All Cambridge</option>
                    <?php foreach ($cambridge_options as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $cambridge === $option ? 'selected' : ''; ?>>
                            <?php echo 'C' . $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select id="test-filter">
                    <option value="">All Tests</option>
                    <?php foreach ($test_options as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $test === $option ? 'selected' : ''; ?>>
                            <?php echo 'T' . $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select id="part-filter">
                    <option value="">All Parts</option>
                    <?php foreach ($part_options as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $part === $option ? 'selected' : ''; ?>>
                            <?php echo 'P' . $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="apply-filter" style="width:100%; padding:12px 16px; background:var(--oxford-gold); color:white; border:none; border-radius:4px; cursor:pointer; font-weight:700; text-transform:uppercase; letter-spacing:0.08em;">Apply Filter</button>
            
            <h3>Article List</h3>
            <ul class="article-list" id="article-list">
                <?php while ($row = $articles_result->fetch_assoc()): ?>
                    <li data-id="<?php echo $row['article_id']; ?>">
                        <a href="reading.php?id=<?php echo $row['article_id']; ?><?php echo $filter_query ? '&' . htmlspecialchars($filter_query) : ''; ?>" class="<?php echo ($selected_id == $row['article_id']) ? 'active' : ''; ?>">
                            <span class="article-item-title"><?php echo htmlspecialchars($row['title']); ?></span>
                            <span class="article-item-meta"><?php echo htmlspecialchars(build_passage_label($row)); ?></span>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
            
            <h3>My Favorites</h3>
            <ul class="favorite-list" id="favorite-list">
                <?php while ($row = $fav_result->fetch_assoc()): ?>
                    <li data-id="<?php echo $row['article_id']; ?>">
                        <a href="reading.php?id=<?php echo $row['article_id']; ?>">
                            <span class="article-item-title"><?php echo htmlspecialchars($row['title']); ?></span>
                            <span class="article-item-meta"><?php echo htmlspecialchars(build_passage_label($row)); ?></span>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        
        <div class="article-panel">
            <?php if ($current_article): ?>
                <div class="article-title"><?php echo htmlspecialchars($current_article['title']); ?></div>
                <div class="article-subtitle"><?php echo htmlspecialchars(build_passage_label($current_article)); ?></div>
                <div class="article-meta">
                    <span class="passage-badge"><?php echo htmlspecialchars(build_passage_label($current_article)); ?></span>
                    <span>IELTS Reading Passage</span>
                    <?php if (!empty($current_article['author'])): ?>
                        <span>By <?php echo htmlspecialchars($current_article['author']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="article-content" id="article-content">
                    <?php echo $current_article['content']; ?>
                </div>
                <div class="action-bar">
                    <button class="action-btn favorite <?php echo $is_favorited ? 'active' : ''; ?>" id="favorite-btn" data-id="<?php echo $current_article['article_id']; ?>">
                        <?php echo $is_favorited ? 'Favorited' : 'Favorite'; ?>
                    </button>
                    <button class="action-btn resources-btn" id="resources-btn">More Reading Resources</button>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:60px;">Please select an article from the left to start reading.</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<div id="resources-modal" class="resources-modal">
    <div class="resources-modal-content">
        <div class="resources-modal-header">
            <h2>More Reading Resources</h2>
            <span class="resources-close">&times;</span>
        </div>
        <div class="resources-modal-body">
            <div class="resource-category">
                <h3>Academic Journals & Papers</h3>
                <ul>
                    <li><a href="https://www.nature.com/" target="_blank">Nature</a> - Leading scientific journal</li>
                    <li><a href="https://www.science.org/" target="_blank">Science</a> - AAAS journal</li>
                    <li><a href="https://www.plos.org/" target="_blank">PLOS ONE</a> - Open access journal</li>
                    <li><a href="https://doaj.org/" target="_blank">DOAJ</a> - Directory of open access journals</li>
                    <li><a href="https://www.jstor.org/" target="_blank">JSTOR</a> - Academic journal database</li>
                </ul>
            </div>
            <div class="resource-category">
                <h3>Free eBooks & Classics</h3>
                <ul>
                    <li><a href="https://www.gutenberg.org/" target="_blank">Project Gutenberg</a> - 60k+ free public domain books</li>
                    <li><a href="https://standardebooks.org/" target="_blank">Standard Ebooks</a> - Beautifully formatted classics</li>
                    <li><a href="https://openlibrary.org/" target="_blank">Open Library</a> - Open library project</li>
                    <li><a href="https://www.literature.org/" target="_blank">The Literature Network</a> - Literature resources</li>
                </ul>
            </div>
            <div class="resource-category">
                <h3>University Open Courses</h3>
                <ul>
                    <li><a href="https://oyc.yale.edu/" target="_blank">Yale Open Courses</a> - Yale University</li>
                    <li><a href="https://ocw.mit.edu/" target="_blank">MIT OpenCourseWare</a> - MIT</li>
                    <li><a href="https://www.coursera.org/" target="_blank">Coursera</a> - Online courses</li>
                    <li><a href="https://www.edx.org/" target="_blank">edX</a> - University courses</li>
                    <li><a href="https://www.khanacademy.org/" target="_blank">Khan Academy</a> - Free education</li>
                </ul>
            </div>
            <div class="resource-category">
                <h3>News & Magazines</h3>
                <ul>
                    <li><a href="https://www.newyorker.com/" target="_blank">The New Yorker</a> - Deep reporting & literature</li>
                    <li><a href="https://www.theatlantic.com/" target="_blank">The Atlantic</a> - Ideas & culture</li>
                    <li><a href="https://www.bbc.com/news" target="_blank">BBC News</a> - International news</li>
                    <li><a href="https://www.economist.com/" target="_blank">The Economist</a> - Economics & current affairs</li>
                </ul>
            </div>
            <div class="resource-category">
                <h3>English Learning Resources</h3>
                <ul>
                    <li><a href="https://www.ted.com/" target="_blank">TED Talks</a> - Speeches & listening practice</li>
                    <li><a href="https://learningenglish.voanews.com/" target="_blank">VOA Learning English</a> - Slow English news</li>
                    <li><a href="https://www.bbc.co.uk/learningenglish/" target="_blank">BBC Learning English</a> - British English</li>
                    <li><a href="https://www.linguahouse.com/" target="_blank">Linguahouse</a> - ESL materials</li>
                </ul>
            </div>
        </div>
        <div class="resources-modal-footer">
            <button class="resources-close-btn">Close</button>
        </div>
    </div>
</div>

<div class="story-fab">
    <div class="story-button" id="story-button">R</div>
</div>

<div id="story-modal" class="story-modal">
    <div class="story-modal-content">
        <div class="story-modal-header">
            <h2>Reading Stories</h2>
            <span class="story-close">&times;</span>
        </div>
        <div class="story-modal-body" id="story-modal-body">
            <div class="story-sidebar-modal">
                <h3>Story List (10 stories)</h3>
                <ul class="story-list-modal" id="story-list-modal">
                    <?php foreach ($stories as $id => $story): ?>
                        <li>
                            <a href="#" data-story-id="<?php echo $id; ?>" class="story-link <?php echo ($id == 1) ? 'active' : ''; ?>">
                                <?php echo $id . '. ' . htmlspecialchars($story['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; font-size: 12px; color: #888; text-align: center;">
                    Tip: Double-click words to look up<br>Select text and right-click to annotate
                </div>
            </div>
            <div class="story-panel-modal" id="story-panel-modal">
                <div class="story-title-modal" id="story-title-modal"><?php echo htmlspecialchars($stories[1]['title']); ?></div>
                <div class="story-tip-modal">Double-click to look up | Right-click selected text to annotate</div>
                <div class="story-content-modal" id="story-content-modal">
                    <?php echo $stories[1]['content']; ?>
                </div>
            </div>
        </div>
        <div class="story-footer">
            <button class="story-close-btn">Close</button>
        </div>
    </div>
</div>

<script>
const articleId = <?php echo $selected_id ?: 0; ?>;
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

function applyAnnotations() {
    const contentDiv = document.getElementById('article-content');
    if (!contentDiv) return;
    let html = contentDiv.innerHTML;
    const sorted = [...existingAnnotations].sort((a, b) => b.selected_text.length - a.selected_text.length);
    sorted.forEach(anno => {
        const pattern = new RegExp(`(${escapeRegex(anno.selected_text)})`, 'g');
        html = html.replace(pattern, `<span class="annotation-highlight" data-annotation-id="${anno.annotation_id}" data-text="${escapeHtml(anno.selected_text)}" data-note="${escapeHtml(anno.note)}">$1</span>`);
    });
    contentDiv.innerHTML = html;
}

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
        <strong>📝 Add Annotation</strong>
        <div style="font-size:12px; color:#666; margin:8px 0; background:#f5f5f5; padding:6px; border-radius:6px;">"${escapeHtml(selectedText.substring(0, 80))}${selectedText.length > 80 ? '...' : ''}"</div>
        <textarea id="annotation-note" placeholder="Write your thoughts here..."></textarea>
        <button onclick="saveAnnotation('${selectedText.replace(/'/g, "\\'")}')">Save</button>
        <button onclick="closeAnnotationPopup()">Cancel</button>
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
        body: `article_id=${articleId}&selected_text=${encodeURIComponent(selectedText)}&note=${encodeURIComponent(note)}`
    }).then(response => response.json()).then(data => {
        if (data.success) {
            closeAnnotationPopup();
            location.reload();
        } else {
            alert('Save failed: ' + (data.error || 'Unknown error'));
        }
    });
}

function closeAnnotationPopup() { if (annotationPopup) annotationPopup.style.display = 'none'; }

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
        <strong>📝 Annotation</strong>
        <div style="font-size:12px; color:#666; margin:8px 0; background:#f5f5f5; padding:6px; border-radius:6px;">"${escapeHtml(text.substring(0, 100))}"</div>
        <div style="margin:10px 0; padding:8px; background:#f9f9f9; border-radius:8px;">${escapeHtml(note) || '<em>No annotation content</em>'}</div>
        <button onclick="deleteAnnotation(${annotationId})" class="delete-btn">Delete</button>
        <button onclick="closeViewPopup()">Close</button>
    `;
    viewPopup.style.left = x + 'px';
    viewPopup.style.top = y + 'px';
    viewPopup.style.display = 'block';
}

function deleteAnnotation(annotationId) {
    if (!confirm('Are you sure you want to delete this annotation?')) return;
    fetch('delete_annotation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `annotation_id=${annotationId}`
    }).then(response => response.json()).then(data => {
        if (data.success) { closeViewPopup(); location.reload(); }
        else alert('Delete failed');
    });
}
function closeViewPopup() { if (viewPopup) viewPopup.style.display = 'none'; }

function updateFavoriteList(articleId, title, label, action) {
    const favList = document.getElementById('favorite-list');
    const existingItem = favList.querySelector(`li[data-id="${articleId}"]`);
    if (action === 'added' && !existingItem) {
        const newItem = document.createElement('li');
        newItem.setAttribute('data-id', articleId);
        newItem.innerHTML = `
            <a href="reading.php?id=${articleId}">
                <span class="article-item-title">${escapeHtml(title)}</span>
                <span class="article-item-meta">${escapeHtml(label)}</span>
            </a>
        `;
        favList.appendChild(newItem);
    } else if (action === 'removed' && existingItem) {
        existingItem.remove();
    }
}

function getArticleInfo(articleId) {
    const articleLink = document.querySelector(`.article-list li[data-id="${articleId}"] a`);
    if (!articleLink) {
        return { title: '', label: '' };
    }

    const title = articleLink.querySelector('.article-item-title')?.innerText || '';
    const label = articleLink.querySelector('.article-item-meta')?.innerText || '';
    return { title, label };
}

document.getElementById('favorite-btn')?.addEventListener('click', function() {
    const articleId = this.dataset.id;
    const btn = this;
    const { title, label } = getArticleInfo(articleId);
    fetch('favorite_article.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `article_id=${articleId}`
    }).then(response => response.json()).then(data => {
        if (data.success) {
            if (data.action === 'added') {
                btn.classList.add('active');
                btn.innerHTML = 'Favorited';
                updateFavoriteList(articleId, title, label, 'added');
            } else {
                btn.classList.remove('active');
                btn.innerHTML = 'Favorite';
                updateFavoriteList(articleId, title, label, 'removed');
            }
        }
    });
});

let wordPopup = null;
function createWordPopup() {
    const div = document.createElement('div');
    div.className = 'word-popup';
    div.style.cssText = 'position:absolute; background:white; border:2px solid #40916c; border-radius:16px; padding:12px; max-width:300px; z-index:1000; display:none;';
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
        if (row && row.cells[2]) return { definition: row.cells[2].innerText.trim() };
        return { definition: null };
    } catch (err) { return { definition: null }; }
}
function showWordPopup(word, x, y, definition) {
    wordPopup.innerHTML = `<strong>${word}</strong><br>${definition || 'Not found in vocabulary'}`;
    wordPopup.style.left = (x + 10) + 'px';
    wordPopup.style.top = (y - 50) + 'px';
    wordPopup.style.display = 'block';
    setTimeout(() => wordPopup.style.display = 'none', 3000);
}

document.getElementById('apply-filter')?.addEventListener('click', () => {
    const cambridge = document.getElementById('cambridge-filter').value;
    const test = document.getElementById('test-filter').value;
    const part = document.getElementById('part-filter').value;
    const params = new URLSearchParams();
    if (cambridge) params.set('cambridge', cambridge);
    if (test) params.set('test', test);
    if (part) params.set('part', part);
    const query = params.toString();
    window.location.href = query ? `reading.php?${query}` : 'reading.php';
});

const modal = document.getElementById('resources-modal');
const openBtn = document.getElementById('resources-btn');
const closeBtn = document.querySelector('.resources-close');
const closeFooterBtn = document.querySelector('.resources-close-btn');
if (openBtn) {
    openBtn.onclick = function() { modal.style.display = 'block'; document.body.style.overflow = 'hidden'; };
}
function closeModal() { modal.style.display = 'none'; document.body.style.overflow = 'auto'; }
if (closeBtn) closeBtn.onclick = closeModal;
if (closeFooterBtn) closeFooterBtn.onclick = closeModal;
window.onclick = function(event) { if (event.target == modal) closeModal(); };

const storyModal = document.getElementById('story-modal');
const storyButton = document.getElementById('story-button');
const storyClose = document.querySelector('.story-close');
const storyCloseBtn = document.querySelector('.story-close-btn');

let currentStoryId = 1;
let storyAnnotations = [];

function loadStory(storyId) {
    const storiesData = <?php echo json_encode($stories); ?>;
    const story = storiesData[storyId];
    if (!story) return;
    
    currentStoryId = storyId;
    document.getElementById('story-title-modal').innerHTML = escapeHtml(story.title);
    document.getElementById('story-content-modal').innerHTML = story.content;
    
    document.querySelectorAll('.story-link').forEach(link => {
        if (parseInt(link.dataset.storyId) === storyId) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    loadStoryAnnotations(storyId);
}

async function loadStoryAnnotations(storyId) {
    const storyArticleId = -storyId;
    try {
        const response = await fetch(`get_story_annotations.php?story_id=${storyId}&user_id=<?php echo $user_id; ?>`);
        const data = await response.json();
        storyAnnotations = data.annotations || [];
        applyStoryAnnotations();
    } catch(err) {
        storyAnnotations = [];
    }
}

function applyStoryAnnotations() {
    const contentDiv = document.getElementById('story-content-modal');
    if (!contentDiv) return;
    let html = contentDiv.innerHTML;
    const sorted = [...storyAnnotations].sort((a, b) => b.selected_text.length - a.selected_text.length);
    sorted.forEach(anno => {
        const pattern = new RegExp(`(${escapeRegex(anno.selected_text)})`, 'g');
        html = html.replace(pattern, `<span class="annotation-highlight" data-annotation-id="${anno.annotation_id}" data-text="${escapeHtml(anno.selected_text)}" data-note="${escapeHtml(anno.note)}">$1</span>`);
    });
    contentDiv.innerHTML = html;
    
    attachStoryEventListeners();
}

function attachStoryEventListeners() {
    const contentDiv = document.getElementById('story-content-modal');
    if (!contentDiv) return;
    
    contentDiv.removeEventListener('contextmenu', storyContextMenuHandler);
    contentDiv.removeEventListener('click', storyClickHandler);
    contentDiv.removeEventListener('dblclick', storyDblClickHandler);
    
    contentDiv.addEventListener('contextmenu', storyContextMenuHandler);
    contentDiv.addEventListener('click', storyClickHandler);
    contentDiv.addEventListener('dblclick', storyDblClickHandler);
}

function storyContextMenuHandler(e) {
    if (e.target.closest('.annotation-highlight')) return;
    const selection = window.getSelection();
    const selectedText = selection.toString().trim();
    if (selectedText && selectedText.length > 0) {
        e.preventDefault();
        const rect = selection.getRangeAt(0).getBoundingClientRect();
        showStoryAnnotationPopup(selectedText, rect.left + window.scrollX + 10, rect.top + window.scrollY - 80);
    }
}

function storyClickHandler(e) {
    const highlight = e.target.closest('.annotation-highlight');
    if (highlight) {
        e.preventDefault();
        e.stopPropagation();
        const text = highlight.dataset.text;
        const note = highlight.dataset.note;
        const annoId = highlight.dataset.annotationId;
        const rect = highlight.getBoundingClientRect();
        showStoryAnnotationDetail(text, note, annoId, rect.left + window.scrollX + 10, rect.top + window.scrollY - 60);
    } else {
        closeStoryViewPopup();
    }
}

function storyDblClickHandler(e) {
    const selection = window.getSelection();
    const selectedText = selection.toString().trim();
    if (selectedText && /^[a-zA-Z]+(?:[-\'][a-zA-Z]+)*$/.test(selectedText)) {
        const word = selectedText.toLowerCase();
        const range = selection.getRangeAt(0);
        const rect = range.getBoundingClientRect();
        lookupWord(word).then(({ definition }) => {
            showWordPopup(word, rect.left + window.scrollX, rect.top + window.scrollY, definition);
        });
    }
}

let storyAnnotationPopup = null;
function createStoryAnnotationPopup() {
    const div = document.createElement('div');
    div.className = 'annotation-popup';
    div.style.display = 'none';
    document.body.appendChild(div);
    return div;
}
storyAnnotationPopup = createStoryAnnotationPopup();

function showStoryAnnotationPopup(selectedText, x, y) {
    storyAnnotationPopup.innerHTML = `
        <strong>📝 Add Annotation</strong>
        <div style="font-size:12px; color:#666; margin:8px 0; background:#f5f5f5; padding:6px; border-radius:6px;">"${escapeHtml(selectedText.substring(0, 80))}${selectedText.length > 80 ? '...' : ''}"</div>
        <textarea id="story-annotation-note" placeholder="Write your thoughts here..."></textarea>
        <button onclick="saveStoryAnnotation('${selectedText.replace(/'/g, "\\'")}')">Save</button>
        <button onclick="closeStoryAnnotationPopup()">Cancel</button>
    `;
    storyAnnotationPopup.style.left = x + 'px';
    storyAnnotationPopup.style.top = y + 'px';
    storyAnnotationPopup.style.display = 'block';
}

function closeStoryAnnotationPopup() {
    if (storyAnnotationPopup) storyAnnotationPopup.style.display = 'none';
}

function saveStoryAnnotation(selectedText) {
    const note = document.getElementById('story-annotation-note')?.value || '';
    const storyArticleId = -currentStoryId;
    fetch('save_annotation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `article_id=${storyArticleId}&selected_text=${encodeURIComponent(selectedText)}&note=${encodeURIComponent(note)}`
    }).then(response => response.json()).then(data => {
        if (data.success) {
            closeStoryAnnotationPopup();
            loadStory(currentStoryId);
        } else {
            alert('Save failed: ' + (data.error || 'Unknown error'));
        }
    });
}

let storyViewPopup = null;
function createStoryViewPopup() {
    const div = document.createElement('div');
    div.className = 'view-annotation-popup';
    div.style.display = 'none';
    document.body.appendChild(div);
    return div;
}
storyViewPopup = createStoryViewPopup();

function showStoryAnnotationDetail(text, note, annotationId, x, y) {
    storyViewPopup.innerHTML = `
        <strong>📝 Annotation</strong>
        <div style="font-size:12px; color:#666; margin:8px 0; background:#f5f5f5; padding:6px; border-radius:6px;">"${escapeHtml(text.substring(0, 100))}"</div>
        <div style="margin:10px 0; padding:8px; background:#f9f9f9; border-radius:8px;">${escapeHtml(note) || '<em>No annotation content</em>'}</div>
        <button onclick="deleteStoryAnnotation(${annotationId})" class="delete-btn">Delete</button>
        <button onclick="closeStoryViewPopup()">Close</button>
    `;
    storyViewPopup.style.left = x + 'px';
    storyViewPopup.style.top = y + 'px';
    storyViewPopup.style.display = 'block';
}

function deleteStoryAnnotation(annotationId) {
    if (!confirm('Are you sure you want to delete this annotation?')) return;
    fetch('delete_annotation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `annotation_id=${annotationId}`
    }).then(response => response.json()).then(data => {
        if (data.success) {
            closeStoryViewPopup();
            loadStory(currentStoryId);
        } else {
            alert('Delete failed');
        }
    });
}

function closeStoryViewPopup() {
    if (storyViewPopup) storyViewPopup.style.display = 'none';
}

if (storyButton) {
    storyButton.onclick = function() {
        storyModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        loadStory(1);
    };
}
function closeStoryModal() {
    storyModal.style.display = 'none';
    document.body.style.overflow = 'auto';
}
if (storyClose) storyClose.onclick = closeStoryModal;
if (storyCloseBtn) storyCloseBtn.onclick = closeStoryModal;
window.onclick = function(event) {
    if (event.target == storyModal) closeStoryModal();
    if (event.target == modal) closeModal();
};

document.querySelectorAll('.story-link').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const storyId = parseInt(link.dataset.storyId);
        loadStory(storyId);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    applyAnnotations();
    const contentDiv = document.getElementById('article-content');
    if (contentDiv) {
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
            } else closeViewPopup();
        });
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
    }
});
document.addEventListener('click', (e) => {
    if (wordPopup && !wordPopup.contains(e.target)) wordPopup.style.display = 'none';
    if (viewPopup && !viewPopup.contains(e.target)) closeViewPopup();
    if (storyViewPopup && !storyViewPopup.contains(e.target)) closeStoryViewPopup();
    if (storyAnnotationPopup && !storyAnnotationPopup.contains(e.target)) closeStoryAnnotationPopup();
    if (annotationPopup && !annotationPopup.contains(e.target)) closeAnnotationPopup();
});
</script>
</body>
</html>

