<?php
// Start the session so the current user can be identified
session_start();

// Load the database connection
require_once '../../../db_connect.php';

// Return the response in JSON format
header('Content-Type: application/json');

// Store the final outfit list
$outfits = [];

// Make sure the outfits table has the is_used column
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

// Check whether the is_used column exists or can be created
$hasIsUsedColumn = ensureIsUsedColumn($conn);

// Track whether the outfits table has a user_id column
$hasUserIdColumn = false;

// Get the current user ID from the session
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Check whether the outfits table supports user-specific outfit ownership
$columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($columnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $columnCheck->num_rows > 0;
    $columnCheck->free();
}

// Build the query for loading outfits for the current user
$sql = $hasIsUsedColumn
    ? "SELECT * FROM outfits WHERE user_id = ? ORDER BY is_used DESC, created_at DESC"
    : "SELECT * FROM outfits WHERE user_id = ? ORDER BY created_at DESC";

// If user_id exists, load only outfits that belong to the current user
if ($hasUserIdColumn) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $outfits = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

// Otherwise, fall back to loading all outfits without user filtering
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

// Return the final outfit list
echo json_encode(['success' => true, 'data' => $outfits]);
?>