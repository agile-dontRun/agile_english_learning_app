session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '未登录'
    ]);
    exit;
}
$userId = $_SESSION['user_id'];
require_once __DIR__ . '/../config/db.php';
try {
    // 6.1 预处理SQL更新语句（防注入）
    $stmt = $pdo->prepare("UPDATE user SET is_tutorial_completed = 1 WHERE id = ?");
    
    // 6.2 代入用户ID，执行更新
    $stmt->execute([$userId]);

    // 6.3 返回更新成功的结果
    echo json_encode([
        'success' => true,
        'message' => '教程完成状态已更新'
    ]);
    } catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '服务器错误：' . $e->getMessage()
    ]);
}