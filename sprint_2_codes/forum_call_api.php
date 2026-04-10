<?php
require_once 'forum_common.php';

header('Content-Type: application/json; charset=utf-8');

ensure_private_call_tables($conn);

function call_json_response(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function call_pair(int $a, int $b): array {
    return $a < $b ? [$a, $b] : [$b, $a];
}

function find_call_session(mysqli $conn, int $callId): ?array {
    $sql = "SELECT * FROM private_call_sessions WHERE call_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $callId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function current_call_partner(array $call, int $userId): int {
    return ((int)$call['user_id_1'] === $userId) ? (int)$call['user_id_2'] : (int)$call['user_id_1'];
}

function insert_signal(mysqli $conn, int $callId, int $senderId, int $receiverId, string $type, ?string $payload = null): void {
    $sql = "INSERT INTO private_call_signals (call_id, sender_id, receiver_id, signal_type, payload)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiss", $callId, $senderId, $receiverId, $type, $payload);
    $stmt->execute();
    $stmt->close();
}

$currentId = current_user_id();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action !== 'poll') {
        call_json_response(['ok' => false, 'error' => 'invalid_action'], 400);
    }

    $otherId = (int)($_GET['other_id'] ?? 0);
    $signals = [];

    $sql = "
        SELECT pcs.*, cs.call_type, cs.status, cs.initiated_by
        FROM private_call_signals pcs
        JOIN private_call_sessions cs ON cs.call_id = pcs.call_id
        WHERE pcs.receiver_id = ?
          AND pcs.is_delivered = 0
          AND (? = 0 OR pcs.sender_id = ?)
        ORDER BY pcs.signal_id ASC
        LIMIT 50
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $currentId, $otherId, $otherId);
    $stmt->execute();
    $result = $stmt->get_result();

    $signalIds = [];
    while ($row = $result->fetch_assoc()) {
        $payload = null;
        if (!empty($row['payload'])) {
            $payload = json_decode($row['payload'], true);
        }

        $signals[] = [
            'signal_id' => (int)$row['signal_id'],
            'call_id' => (int)$row['call_id'],
            'sender_id' => (int)$row['sender_id'],
            'receiver_id' => (int)$row['receiver_id'],
            'signal_type' => $row['signal_type'],
            'payload' => $payload,
            'call_type' => $row['call_type'],
            'status' => $row['status'],
            'initiated_by' => (int)$row['initiated_by'],
            'created_at' => $row['created_at']
        ];
        $signalIds[] = (int)$row['signal_id'];
    }
    $stmt->close();

    if (!empty($signalIds)) {
        $idList = implode(',', $signalIds);
        $conn->query("UPDATE private_call_signals SET is_delivered = 1 WHERE signal_id IN ({$idList})");
    }

    call_json_response(['ok' => true, 'signals' => $signals]);
}

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true);

if (!is_array($input)) {
    call_json_response(['ok' => false, 'error' => 'invalid_json'], 400);
}

$action = $input['action'] ?? '';

if ($action === 'start_call') {
    $receiverId = (int)($input['receiver_id'] ?? 0);
    $callType = ($input['call_type'] ?? '') === 'video' ? 'video' : 'audio';
    $offer = $input['offer'] ?? null;

    if ($receiverId <= 0 || $receiverId === $currentId || !is_array($offer)) {
        call_json_response(['ok' => false, 'error' => 'invalid_request'], 400);
    }

    $receiver = find_user($conn, $receiverId);
    if (!$receiver) {
        call_json_response(['ok' => false, 'error' => 'receiver_not_found'], 404);
    }

    [$u1, $u2] = call_pair($currentId, $receiverId);

    $sql = "UPDATE private_call_sessions
            SET status = 'ended', ended_at = NOW()
            WHERE user_id_1 = ? AND user_id_2 = ? AND status IN ('ringing', 'active')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $u1, $u2);
    $stmt->execute();
    $stmt->close();

    $sql = "INSERT INTO private_call_sessions (user_id_1, user_id_2, initiated_by, call_type, status)
            VALUES (?, ?, ?, ?, 'ringing')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $u1, $u2, $currentId, $callType);
    $stmt->execute();
    $callId = (int)$stmt->insert_id;
    $stmt->close();

    insert_signal($conn, $callId, $currentId, $receiverId, 'offer', json_encode($offer, JSON_UNESCAPED_UNICODE));

    call_json_response([
        'ok' => true,
        'call_id' => $callId,
        'receiver' => [
            'user_id' => (int)$receiver['user_id'],
            'name' => $receiver['nickname'] ?: $receiver['username']
        ]
    ]);
}

if (!in_array($action, ['answer_call', 'send_candidate', 'end_call'], true)) {
    call_json_response(['ok' => false, 'error' => 'invalid_action'], 400);
}

$callId = (int)($input['call_id'] ?? 0);
$call = find_call_session($conn, $callId);

if (!$call) {
    call_json_response(['ok' => false, 'error' => 'call_not_found'], 404);
}

$isParticipant = ((int)$call['user_id_1'] === $currentId || (int)$call['user_id_2'] === $currentId);
if (!$isParticipant) {
    call_json_response(['ok' => false, 'error' => 'forbidden'], 403);
}

$otherId = current_call_partner($call, $currentId);

if ($action === 'answer_call') {
    $answer = $input['answer'] ?? null;

    if (!is_array($answer)) {
        call_json_response(['ok' => false, 'error' => 'invalid_answer'], 400);
    }

    $sql = "UPDATE private_call_sessions
            SET status = 'active', started_at = COALESCE(started_at, NOW()), ended_at = NULL
            WHERE call_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $callId);
    $stmt->execute();
    $stmt->close();

    insert_signal($conn, $callId, $currentId, $otherId, 'answer', json_encode($answer, JSON_UNESCAPED_UNICODE));
    call_json_response(['ok' => true]);
}

if ($action === 'send_candidate') {
    $candidate = $input['candidate'] ?? null;

    if (!is_array($candidate)) {
        call_json_response(['ok' => false, 'error' => 'invalid_candidate'], 400);
    }

    insert_signal($conn, $callId, $currentId, $otherId, 'candidate', json_encode($candidate, JSON_UNESCAPED_UNICODE));
    call_json_response(['ok' => true]);
}

$endReason = $input['reason'] ?? 'ended';
$statusMap = [
    'declined' => 'declined',
    'missed' => 'missed',
    'failed' => 'failed',
    'ended' => 'ended'
];
$nextStatus = $statusMap[$endReason] ?? 'ended';

$sql = "UPDATE private_call_sessions
        SET status = ?, ended_at = NOW()
        WHERE call_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nextStatus, $callId);
$stmt->execute();
$stmt->close();

insert_signal($conn, $callId, $currentId, $otherId, 'hangup', json_encode(['reason' => $nextStatus], JSON_UNESCAPED_UNICODE));
call_json_response(['ok' => true, 'status' => $nextStatus]);
?>
