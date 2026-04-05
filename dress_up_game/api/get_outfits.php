<?php
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

$sql = $hasIsUsedColumn
    ? "SELECT * FROM outfits ORDER BY is_used DESC, created_at DESC"
    : "SELECT * FROM outfits ORDER BY created_at DESC";

$result = $conn->query($sql);
if ($result instanceof mysqli_result) {
    $outfits = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

echo json_encode(['success' => true, 'data' => $outfits]);
?>
