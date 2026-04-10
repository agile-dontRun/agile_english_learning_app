<?php

require_once dirname(__DIR__, 3) . '/coin_common.php';

function applyConflictRules($outfit) {
    $result = $outfit;
    if (isset($result['dress'])) {
        unset($result['top']);
        unset($result['pants']);
        unset($result['suit']);
    }
    if (isset($result['suit'])) {
        unset($result['top']);
        unset($result['pants']);
        unset($result['dress']);
    }
    if (isset($result['character'])) {
        unset($result['eye']);
        unset($result['eyebrows']);
        unset($result['nose']);
        unset($result['mouse']);
        unset($result['hair']);
    }
    return $result;
}


function getLayerOrder() {
    return [
        'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
        'eye', 'eyebrows', 'nose', 'mouse', 'hair',
        'character', 'glass', 'head'
    ];
}

function dressUpCurrentUserId(): string {
    return isset($_SESSION['user_id']) ? (string)$_SESSION['user_id'] : '0';
}

function dressUpUserIdParam($userId): string {
    return (string)$userId;
}

function dressUpHasColumn(mysqli $conn, string $tableName, string $columnName): bool {
    $safeTable = $conn->real_escape_string($tableName);
    $safeColumn = $conn->real_escape_string($columnName);
    $columnCheck = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    if ($columnCheck instanceof mysqli_result) {
        $exists = $columnCheck->num_rows > 0;
        $columnCheck->free();
        return $exists;
    }

    return false;
}

function dressUpEnsureIsUsedColumn(mysqli $conn): bool {
    if (dressUpHasColumn($conn, 'outfits', 'is_used')) {
        return true;
    }

    return (bool)$conn->query("ALTER TABLE outfits ADD COLUMN is_used BOOLEAN NOT NULL DEFAULT FALSE");
}

function dressUpHasOutfitUserIdColumn(mysqli $conn): bool {
    return dressUpHasColumn($conn, 'outfits', 'user_id');
}

function dressUpSlugify(string $name): string {
    $name = trim($name);
    $name = preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', $name);
    $name = preg_replace('/-+/', '-', (string)$name);
    $name = trim((string)$name, '-_');
    return $name !== '' ? $name : 'look';
}

function dressUpEnsureAvatarColumn(mysqli $conn): bool {
    if (dressUpHasColumn($conn, 'outfits', 'avatar_image_path')) {
        return true;
    }

    return (bool)$conn->query("ALTER TABLE outfits ADD COLUMN avatar_image_path VARCHAR(255) NULL DEFAULT NULL");
}

function dressUpGeneratedAvatarDirAbsolute(): string {
    return dirname(__DIR__) . '/generated_avatars';
}

function dressUpGeneratedAvatarWebBase(): string {
    return '/galgame/dress_up_game/generated_avatars';
}

function dressUpResolveImageAbsolutePath(string $filePath): ?string {
    $normalized = str_replace('\\', '/', trim($filePath));
    $candidates = [];

    if ($normalized !== '') {
        if ($normalized[0] === '/') {
            $candidates[] = dirname(__DIR__) . $normalized;
        } else {
            $candidates[] = dirname(__DIR__) . '/' . $normalized;
        }
    }

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function dressUpLoadImageResource(string $absolutePath) {
    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

    if ($ext === 'png' && function_exists('imagecreatefrompng')) {
        return @imagecreatefrompng($absolutePath);
    }

    if (($ext === 'jpg' || $ext === 'jpeg') && function_exists('imagecreatefromjpeg')) {
        return @imagecreatefromjpeg($absolutePath);
    }

    if ($ext === 'gif' && function_exists('imagecreatefromgif')) {
        return @imagecreatefromgif($absolutePath);
    }

    if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
        return @imagecreatefromwebp($absolutePath);
    }

    return null;
}

function dressUpGetOutfitItems(mysqli $conn, int $outfitId): array {
    $items = [];
    $stmt = $conn->prepare("
        SELECT oi.layer_code, oi.image_id, i.name, i.file_path
        FROM outfit_items oi
        INNER JOIN images i ON i.id = oi.image_id
        WHERE oi.outfit_id = ?
    ");

    if (!$stmt) {
        return $items;
    }

    $stmt->bind_param("i", $outfitId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[$row['layer_code']] = [
            'image_id' => (int)$row['image_id'],
            'name' => $row['name'],
            'file_path' => $row['file_path'],
        ];
    }
    $stmt->close();

    return $items;
}

function dressUpWriteOutfitAvatarPath(mysqli $conn, int $outfitId, string $avatarPath): void {
    if (!dressUpEnsureAvatarColumn($conn)) {
        return;
    }

    $stmt = $conn->prepare("UPDATE outfits SET avatar_image_path = ? WHERE id = ?");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("si", $avatarPath, $outfitId);
    $stmt->execute();
    $stmt->close();
}

function dressUpClearOutfitAvatarPath(mysqli $conn, int $outfitId): void {
    if (!dressUpEnsureAvatarColumn($conn)) {
        return;
    }

    $stmt = $conn->prepare("UPDATE outfits SET avatar_image_path = NULL WHERE id = ?");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("i", $outfitId);
    $stmt->execute();
    $stmt->close();
}

function dressUpCleanupUserAvatarFiles(mysqli $conn, int $userId, ?int $keepOutfitId = null): void {
    if ($userId <= 0) {
        return;
    }

    $hasUserIdColumn = false;
    $columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
    if ($columnCheck instanceof mysqli_result) {
        $hasUserIdColumn = $columnCheck->num_rows > 0;
        $columnCheck->free();
    }

    if (!$hasUserIdColumn || !dressUpEnsureAvatarColumn($conn)) {
        return;
    }

    $stmt = $conn->prepare("
        SELECT id, avatar_image_path
        FROM outfits
        WHERE user_id = ?
          AND avatar_image_path IS NOT NULL
          AND avatar_image_path <> ''
    ");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $outfitId = (int)$row['id'];
        if ($keepOutfitId !== null && $outfitId === $keepOutfitId) {
            continue;
        }

        $avatarPath = (string)$row['avatar_image_path'];
        $relativePath = preg_replace('#^/galgame/dress_up_game/#', '', $avatarPath);
        $absolutePath = dirname(__DIR__) . '/' . ltrim((string)$relativePath, '/');
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        dressUpClearOutfitAvatarPath($conn, $outfitId);
    }

    $stmt->close();
}

function dressUpEnsureShopTables(mysqli $conn): bool {
    $shopSql = "
        CREATE TABLE IF NOT EXISTS dress_up_shop_items (
            image_id INT NOT NULL,
            price_coins INT NOT NULL DEFAULT 0,
            is_free TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (image_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    $unlockSql = "
        CREATE TABLE IF NOT EXISTS dress_up_user_unlocks (
            unlock_id BIGINT NOT NULL AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            image_id INT NOT NULL,
            unlock_source VARCHAR(32) NOT NULL DEFAULT 'purchase',
            unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (unlock_id),
            UNIQUE KEY uk_dress_up_unlock (user_id, image_id),
            KEY idx_dress_up_unlock_user (user_id),
            KEY idx_dress_up_unlock_image (image_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    return (bool)$conn->query($shopSql) && (bool)$conn->query($unlockSql);
}

function dressUpExtractNumericSuffix(string $name): int {
    if (preg_match('/_(\d+)$/', $name, $matches)) {
        return (int)$matches[1];
    }
    return 0;
}

function dressUpCalculateItemPrice(string $layerCode, string $name): int {
    $number = dressUpExtractNumericSuffix($name);

    switch ($layerCode) {
        case 'body':
            return 0;
        case 'eye':
        case 'eyebrows':
        case 'nose':
        case 'mouse':
            return $number === 1 ? 0 : 100;
        case 'hair':
            return in_array($number, [1, 9], true) ? 0 : 200;
        case 'top':
            return $number === 3 ? 0 : 100;
        case 'pants':
            return $number === 5 ? 0 : 100;
        case 'dress':
        case 'suit':
            return 1000;
        case 'shoes':
            return $number === 1 ? 0 : 888;
        case 'glass':
            return in_array($number, [1, 2], true) ? 0 : 100;
        case 'head':
            return $number === 11 ? 0 : 900;
        case 'character':
            return 999;
        case 'background':
            return 2000;
        case 'earings':
            return $number === 1 ? 0 : 100;
        default:
            return 0;
    }
}

function dressUpSyncShopCatalog(mysqli $conn): void {
    if (!dressUpEnsureShopTables($conn)) {
        return;
    }

    $images = [];
    $result = $conn->query("SELECT id, layer_code, name FROM images WHERE is_enabled = 1");
    if ($result instanceof mysqli_result) {
        $images = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }

    $stmt = $conn->prepare("
        INSERT INTO dress_up_shop_items (image_id, price_coins, is_free)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            price_coins = VALUES(price_coins),
            is_free = VALUES(is_free),
            updated_at = CURRENT_TIMESTAMP
    ");

    if (!$stmt) {
        return;
    }

    foreach ($images as $image) {
        $imageId = (int)$image['id'];
        $price = dressUpCalculateItemPrice((string)$image['layer_code'], (string)$image['name']);
        $isFree = $price === 0 ? 1 : 0;
        $stmt->bind_param("iii", $imageId, $price, $isFree);
        $stmt->execute();
    }

    $stmt->close();
}

function dressUpEnsureUserUnlocks(mysqli $conn, $userId): void {
    $userIdParam = dressUpUserIdParam($userId);
    if ($userIdParam === '' || $userIdParam === '0') {
        return;
    }

    dressUpSyncShopCatalog($conn);

    $freeUnlockStmt = $conn->prepare("
        INSERT IGNORE INTO dress_up_user_unlocks (user_id, image_id, unlock_source)
        SELECT ?, si.image_id, 'free'
        FROM dress_up_shop_items si
        INNER JOIN images i ON i.id = si.image_id
        WHERE si.price_coins = 0
          AND i.is_enabled = 1
    ");
    if ($freeUnlockStmt) {
        $freeUnlockStmt->bind_param("s", $userIdParam);
        $freeUnlockStmt->execute();
        $freeUnlockStmt->close();
    }

    if (!dressUpHasOutfitUserIdColumn($conn)) {
        return;
    }

    $legacyUnlockStmt = $conn->prepare("
        INSERT IGNORE INTO dress_up_user_unlocks (user_id, image_id, unlock_source)
        SELECT DISTINCT o.user_id, oi.image_id, 'legacy'
        FROM outfits o
        INNER JOIN outfit_items oi ON oi.outfit_id = o.id
        WHERE o.user_id = ?
    ");
    if ($legacyUnlockStmt) {
        $legacyUnlockStmt->bind_param("s", $userIdParam);
        $legacyUnlockStmt->execute();
        $legacyUnlockStmt->close();
    }
}

function dressUpGetShopCatalogForUser(mysqli $conn, $userId): array {
    dressUpEnsureUserUnlocks($conn, $userId);
    $userIdParam = dressUpUserIdParam($userId);

    $catalog = [];
    $stmt = $conn->prepare("
        SELECT
            i.id,
            i.layer_code,
            i.name,
            i.file_path,
            i.thumbnail_path,
            COALESCE(si.price_coins, 0) AS price_coins,
            COALESCE(si.is_free, 0) AS is_free,
            CASE WHEN uu.image_id IS NULL THEN 0 ELSE 1 END AS is_unlocked,
            COALESCE(uu.unlock_source, '') AS unlock_source
        FROM images i
        LEFT JOIN dress_up_shop_items si ON si.image_id = i.id
        LEFT JOIN dress_up_user_unlocks uu
            ON uu.image_id = i.id
           AND uu.user_id = ?
        WHERE i.is_enabled = 1
        ORDER BY i.layer_code, i.sort_order, i.id
    ");

    if (!$stmt) {
        return $catalog;
    }

    $stmt->bind_param("s", $userIdParam);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $layerCode = (string)$row['layer_code'];
        if (!isset($catalog[$layerCode])) {
            $catalog[$layerCode] = [];
        }

        $catalog[$layerCode][] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'layer_code' => $layerCode,
            'file_path' => (string)$row['file_path'],
            'thumbnail_path' => (string)$row['thumbnail_path'],
            'full_url' => '/galgame/dress_up_game' . (string)$row['file_path'],
            'thumbnail_url' => '/galgame/dress_up_game' . (string)($row['thumbnail_path'] ?: $row['file_path']),
            'price_coins' => (int)$row['price_coins'],
            'is_free' => (int)$row['is_free'],
            'is_unlocked' => (int)$row['is_unlocked'],
            'unlock_source' => (string)$row['unlock_source'],
        ];
    }

    $stmt->close();
    return $catalog;
}

function dressUpGetUnlockedImageIds(mysqli $conn, $userId): array {
    dressUpEnsureUserUnlocks($conn, $userId);
    $userIdParam = dressUpUserIdParam($userId);
    $ids = [];

    $stmt = $conn->prepare("SELECT image_id FROM dress_up_user_unlocks WHERE user_id = ?");
    if (!$stmt) {
        return $ids;
    }

    $stmt->bind_param("s", $userIdParam);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['image_id'];
    }
    $stmt->close();

    return $ids;
}

function dressUpValidateItemOwnership(mysqli $conn, $userId, array $items): array {
    $normalized = applyConflictRules($items);
    $imageIds = [];

    foreach ($normalized as $imageId) {
        $imageId = (int)$imageId;
        if ($imageId > 0) {
            $imageIds[] = $imageId;
        }
    }

    if (!$imageIds) {
        return [];
    }

    $unlockedMap = array_flip(dressUpGetUnlockedImageIds($conn, $userId));
    $missing = [];
    foreach ($imageIds as $imageId) {
        if (!isset($unlockedMap[$imageId])) {
            $missing[] = $imageId;
        }
    }

    return array_values(array_unique($missing));
}

function dressUpGetItemDetails(mysqli $conn, int $imageId): ?array {
    dressUpSyncShopCatalog($conn);
    $stmt = $conn->prepare("
        SELECT
            i.id,
            i.layer_code,
            i.name,
            i.file_path,
            COALESCE(si.price_coins, 0) AS price_coins,
            COALESCE(si.is_free, 0) AS is_free
        FROM images i
        LEFT JOIN dress_up_shop_items si ON si.image_id = i.id
        WHERE i.id = ?
          AND i.is_enabled = 1
        LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $row['id'] = (int)$row['id'];
    $row['price_coins'] = (int)$row['price_coins'];
    $row['is_free'] = (int)$row['is_free'];
    $row['full_url'] = '/galgame/dress_up_game' . (string)$row['file_path'];
    return $row;
}

function dressUpPurchaseItem(mysqli $conn, $userId, int $imageId): array {
    $userIdParam = dressUpUserIdParam($userId);
    if ($userIdParam === '' || $userIdParam === '0' || $imageId <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid purchase request.'
        ];
    }

    dressUpEnsureUserUnlocks($conn, $userIdParam);
    $item = dressUpGetItemDetails($conn, $imageId);
    if (!$item) {
        return [
            'success' => false,
            'error' => 'Item not found.'
        ];
    }

    try {
        $conn->begin_transaction();

        $checkStmt = $conn->prepare("
            SELECT unlock_id
            FROM dress_up_user_unlocks
            WHERE user_id = ?
              AND image_id = ?
            LIMIT 1
            FOR UPDATE
        ");
        if (!$checkStmt) {
            throw new Exception($conn->error);
        }
        $checkStmt->bind_param("si", $userIdParam, $imageId);
        $checkStmt->execute();
        $existingUnlock = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existingUnlock) {
            $conn->commit();
            return [
                'success' => true,
                'already_unlocked' => true,
                'price_coins' => 0,
                'balance' => coin_get_balance($conn, $userIdParam),
                'item' => $item
            ];
        }

        $price = (int)$item['price_coins'];
        $newBalance = coin_get_balance($conn, $userIdParam);

        if ($price > 0) {
            $txResult = coin_add_transaction_internal(
                $conn,
                $userIdParam,
                'dress_up',
                'item_purchase',
                $imageId,
                -$price,
                'Dress-up item purchase',
                [
                    'image_id' => $imageId,
                    'item_name' => (string)$item['name'],
                    'layer_code' => (string)$item['layer_code'],
                ]
            );

            $newBalance = (int)$txResult['balance'];
        }

        $unlockSource = $price > 0 ? 'purchase' : 'free';
        $unlockStmt = $conn->prepare("
            INSERT INTO dress_up_user_unlocks (user_id, image_id, unlock_source)
            VALUES (?, ?, ?)
        ");
        if (!$unlockStmt) {
            throw new Exception($conn->error);
        }
        $unlockStmt->bind_param("sis", $userIdParam, $imageId, $unlockSource);
        $unlockStmt->execute();
        $unlockStmt->close();

        $conn->commit();

        return [
            'success' => true,
            'already_unlocked' => false,
            'price_coins' => $price,
            'balance' => $newBalance,
            'item' => $item
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'success' => false,
            'error' => $e->getMessage() === 'Insufficient coin balance.'
                ? 'You do not have enough coins for this item.'
                : 'Failed to complete the purchase.'
        ];
    }
}

function dressUpGetActiveOutfitData(mysqli $conn, $userId): ?array {
    if (!dressUpHasOutfitUserIdColumn($conn) || !dressUpEnsureIsUsedColumn($conn)) {
        return null;
    }

    $userIdParam = dressUpUserIdParam($userId);
    $stmt = $conn->prepare("
        SELECT id, name, avatar_image_path
        FROM outfits
        WHERE user_id = ?
          AND is_used = 1
        ORDER BY id DESC
        LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $userIdParam);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $outfitItems = dressUpGetOutfitItems($conn, (int)$row['id']);
    $outfit = [];
    foreach ($outfitItems as $layerCode => $item) {
        $outfit[$layerCode] = (int)$item['image_id'];
    }

    return [
        'id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'avatar_image_path' => (string)($row['avatar_image_path'] ?? ''),
        'outfit' => applyConflictRules($outfit)
    ];
}

function dressUpGenerateAvatarForOutfit(mysqli $conn, int $userId, int $outfitId, string $outfitName, ?array $items = null): ?string {
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
        return null;
    }

    $items = is_array($items) ? $items : dressUpGetOutfitItems($conn, $outfitId);
    if (!$items) {
        return null;
    }

    $orderedLayers = [];
    foreach (getLayerOrder() as $layerCode) {
        if ($layerCode === 'background' || empty($items[$layerCode]['file_path'])) {
            continue;
        }

        $absolutePath = dressUpResolveImageAbsolutePath((string)$items[$layerCode]['file_path']);
        if (!$absolutePath) {
            continue;
        }

        $imageInfo = @getimagesize($absolutePath);
        if (!$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1])) {
            continue;
        }

        $orderedLayers[] = [
            'path' => $absolutePath,
            'width' => (int)$imageInfo[0],
            'height' => (int)$imageInfo[1],
        ];
    }

    if (!$orderedLayers) {
        return null;
    }

    $canvasWidth = 0;
    $canvasHeight = 0;
    foreach ($orderedLayers as $layer) {
        $canvasWidth = max($canvasWidth, $layer['width']);
        $canvasHeight = max($canvasHeight, $layer['height']);
    }

    if ($canvasWidth <= 0 || $canvasHeight <= 0) {
        return null;
    }

    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $canvasWidth, $canvasHeight, $transparent);

    foreach ($orderedLayers as $layer) {
        $resource = dressUpLoadImageResource($layer['path']);
        if (!$resource) {
            continue;
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);
        $dstX = (int)floor(($canvasWidth - $layer['width']) / 2);
        $dstY = (int)floor(($canvasHeight - $layer['height']) / 2);
        imagecopy($canvas, $resource, $dstX, $dstY, 0, 0, $layer['width'], $layer['height']);
        imagedestroy($resource);
    }

    $avatarDir = dressUpGeneratedAvatarDirAbsolute();
    if (!is_dir($avatarDir) && !@mkdir($avatarDir, 0777, true) && !is_dir($avatarDir)) {
        imagedestroy($canvas);
        return null;
    }

    $filename = $userId . '_' . $outfitId . '_' . dressUpSlugify($outfitName) . '.png';
    $absoluteAvatarPath = $avatarDir . '/' . $filename;
    $webAvatarPath = dressUpGeneratedAvatarWebBase() . '/' . rawurlencode($filename);

    $saved = @imagepng($canvas, $absoluteAvatarPath);
    imagedestroy($canvas);

    if (!$saved) {
        return null;
    }

    dressUpWriteOutfitAvatarPath($conn, $outfitId, $webAvatarPath);
    return $webAvatarPath;
}
?>
