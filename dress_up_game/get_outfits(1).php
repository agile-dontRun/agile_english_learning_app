<?php
session_start();
require_once '../../../db_connect.php';

header('Content-Type: application/json');
$outfits = [];
function ensureIsUsedColumn(mysqli $conn): bool {
    $columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'is_used'");
    if ($columnCheck instanceof mysqli_result) {
        $exists = $columnCheck->num_rows > 0;
        $columnCheck->free();
        if ($exists) {
            return true;
        }
    }

    return (bool) $conn->query("ALTER TABLE outfits ADD COLUMN is_used BOOLEAN NOT NULL DEFAULT FALSE");
}

$hasIsUsedColumn = ensureIsUsedColumn($conn);
$hasUserIdColumn = false;
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($columnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $columnCheck->num_rows > 0;
    $columnCheck->free();
}

$sql = $hasIsUsedColumn
    ? "SELECT * FROM outfits WHERE user_id = ? ORDER BY is_used DESC, created_at DESC"
    : "SELECT * FROM outfits WHERE user_id = ? ORDER BY created_at DESC";

if ($hasUserIdColumn) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $outfits = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} else {
    $sql = $hasIsUsedColumn
        ? "SELECT * FROM outfits ORDER BY is_used DESC, created_at DESC"
        : "SELECT * FROM outfits ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result instanceof mysqli_result) {
        $outfits = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
}

echo json_encode(['success' => true, 'data' => $outfits]);
?>
