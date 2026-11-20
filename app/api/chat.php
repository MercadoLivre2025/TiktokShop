<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $session_id = $input['session_id'] ?? '';
    $message = $input['message'] ?? '';
    $sender = $input['sender'] ?? 'customer';

    if (empty($session_id) || empty($message)) {
        echo json_encode(['error' => 'Missing session_id or message']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, sender, message) VALUES (?, ?, ?)");
    if ($stmt->execute([$session_id, $sender, $message])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Database error']);
    }
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? 'get_messages';

    if ($action === 'get_messages') {
        $session_id = $_GET['session_id'] ?? '';
        if (empty($session_id)) {
            echo json_encode(['error' => 'Missing session_id']);
            exit;
        }

        // Marcar mensagens do admin como lidas pelo cliente, ou do cliente como lidas pelo admin
        // O ideal é a lógica depender de quem chamou, mas para simplificar, 
        // vamos apenas retornar as mensagens.
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
        $stmt->execute([$session_id]);
        $messages = $stmt->fetchAll();
        
        echo json_encode(['messages' => $messages]);
    } elseif ($action === 'get_sessions') {
        // Para o painel admin
        session_start();
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Pega as sessões ordenadas pela data da última mensagem
        $stmt = $pdo->query("
            SELECT session_id, MAX(created_at) as last_message_date, 
            (SELECT message FROM chat_messages c2 WHERE c2.session_id = c1.session_id ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT sender FROM chat_messages c3 WHERE c3.session_id = c1.session_id ORDER BY created_at DESC LIMIT 1) as last_sender,
            SUM(CASE WHEN is_read = 0 AND sender = 'customer' THEN 1 ELSE 0 END) as unread_count
            FROM chat_messages c1 
            GROUP BY session_id 
            ORDER BY last_message_date DESC
        ");
        echo json_encode(['sessions' => $stmt->fetchAll()]);
    } elseif ($action === 'mark_read') {
        session_start();
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $session_id = $_GET['session_id'] ?? '';
        $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender = 'customer'");
        $stmt->execute([$session_id]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_session') {
        session_start();
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $session_id = $_GET['session_id'] ?? $_POST['session_id'] ?? '';
        if (empty($session_id)) {
            $input = json_decode(file_get_contents('php://input'), true);
            $session_id = $input['session_id'] ?? '';
        }
        if (empty($session_id)) {
            echo json_encode(['error' => 'Missing session_id']);
            exit;
        }
        if ($session_id === 'all') {
            $stmt = $pdo->prepare("DELETE FROM chat_messages");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE session_id = ?");
            $stmt->execute([$session_id]);
        }
        echo json_encode(['success' => true]);
    }
}
?>
