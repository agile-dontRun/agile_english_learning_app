<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_connect.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]);
    exit;
}

// ========== 新增：随机获取单词列表（用于干扰词） ==========
if (isset($_GET['random']) && $_GET['random'] == 1) {
    $count = isset($_GET['count']) ? intval($_GET['count']) : 30;
    $exclude = isset($_GET['exclude']) ? $_GET['exclude'] : '';
    
    $excludeSql = '';
    $params = [];
    $types = '';
    
    if ($exclude) {
        $excludeArray = explode(',', $exclude);
        $placeholders = implode(',', array_fill(0, count($excludeArray), '?'));
        $excludeSql = "AND english_word NOT IN ($placeholders)";
        $params = $excludeArray;
        $types = str_repeat('s', count($excludeArray));
    }
    
    $sql = "SELECT english_word FROM words WHERE LENGTH(english_word) <= 12 $excludeSql ORDER BY RAND() LIMIT $count";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database prepare failed: ' . $conn->error
        ]);
        exit;
    }
    
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $words = [];
    while ($row = $result->fetch_assoc()) {
        $words[] = $row['english_word'];
    }
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'words' => $words,
        'count' => count($words)
    ]);
    exit;
}

// ========== 原有：单词查询功能 ==========
$lookup = isset($_GET['search']) ? trim($_GET['search']) : (isset($_GET['word']) ? trim($_GET['word']) : '');
if ($lookup === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing search term'
    ]);
    exit;
}

function ends_with($haystack, $needle) {
    $needleLength = strlen($needle);
    if ($needleLength === 0) {
        return true;
    }
    return substr($haystack, -$needleLength) === $needle;
}

function candidate_words($word) {
    $word = strtolower(trim($word));
    $candidates = [$word];

    if (ends_with($word, 'ies') && strlen($word) > 3) {
        $candidates[] = substr($word, 0, -3) . 'y';
    }
    if (ends_with($word, 'es') && strlen($word) > 2) {
        $candidates[] = substr($word, 0, -2);
    }
    if (ends_with($word, 's') && strlen($word) > 1) {
        $candidates[] = substr($word, 0, -1);
    }
    if (ends_with($word, 'ied') && strlen($word) > 3) {
        $candidates[] = substr($word, 0, -3) . 'y';
    }
    if (ends_with($word, 'ed') && strlen($word) > 2) {
        $base = substr($word, 0, -2);
        $candidates[] = $base;
        $candidates[] = $base . 'e';
    }
    if (ends_with($word, 'ing') && strlen($word) > 3) {
        $base = substr($word, 0, -3);
        $candidates[] = $base;
        $candidates[] = $base . 'e';
    }

    return array_values(array_unique(array_filter($candidates)));
}

try {
    // 精确匹配查询
    $stmt = $conn->prepare("SELECT word_id, english_word, phonetic, chinese_meaning FROM words WHERE LOWER(english_word) = LOWER(?) LIMIT 1");
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    foreach (candidate_words($lookup) as $candidate) {
        $stmt->bind_param("s", $candidate);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $stmt->close();
            echo json_encode([
                'status' => 'success',
                'word' => $result['english_word'],
                'phonetic' => $result['phonetic'],
                'definition' => $result['chinese_meaning'],
                'word_id' => $result['word_id'],
                'searched' => $lookup
            ]);
            exit;
        }
    }

    $stmt->close();

    // 模糊匹配查询（前缀）
    $likeStmt = $conn->prepare("SELECT word_id, english_word, phonetic, chinese_meaning FROM words WHERE english_word LIKE ? ORDER BY english_word ASC LIMIT 1");
    if (!$likeStmt) {
        throw new Exception($conn->error);
    }
    $likeParam = strtolower($lookup) . '%';
    $likeStmt->bind_param("s", $likeParam);
    $likeStmt->execute();
    $likeResult = $likeStmt->get_result()->fetch_assoc();

    if ($likeResult) {
        $likeStmt->close();
        echo json_encode([
            'status' => 'success',
            'word' => $likeResult['english_word'],
            'phonetic' => $likeResult['phonetic'],
            'definition' => $likeResult['chinese_meaning'],
            'word_id' => $likeResult['word_id'],
            'searched' => $lookup
        ]);
        exit;
    }

    $likeStmt->close();

    // 未找到
    echo json_encode([
        'status' => 'not_found',
        'word' => $lookup,
        'definition' => null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lookup failed: ' . $e->getMessage()
    ]);
}
?>