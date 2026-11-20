<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // Prevent empty submissions
    if (empty($input['name']) && empty($input['phone'])) {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }

    $cpf = $input['cpf'] ?? '';
    $phone = $input['phone'] ?? '';

    try {
        $existingId = 0;
        if (!empty($cpf) || !empty($phone)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM customers WHERE ((cpf != '' AND cpf = ?) OR (phone != '' AND phone = ?)) AND created_at >= NOW() - INTERVAL 180 SECOND ORDER BY id DESC LIMIT 1");
            $stmtCheck->execute([$cpf, $phone]);
            $existingId = $stmtCheck->fetchColumn();
        }

        if ($existingId) {
            $stmt = $pdo->prepare("
                UPDATE customers SET name = ?, email = ?, cep = ?, address = ?, number = ?, neighborhood = ?, city = ?, state = ?, product_name = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $input['name'] ?? '',
                $input['email'] ?? '',
                $input['cep'] ?? '',
                $input['address'] ?? '',
                $input['number'] ?? '',
                $input['neighborhood'] ?? '',
                $input['city'] ?? '',
                $input['state'] ?? '',
                $input['product_name'] ?? '',
                $existingId
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO customers (name, cpf, phone, email, cep, address, number, neighborhood, city, state, product_name, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $input['name'] ?? '',
                $cpf,
                $phone,
                $input['email'] ?? '',
                $input['cep'] ?? '',
                $input['address'] ?? '',
                $input['number'] ?? '',
                $input['neighborhood'] ?? '',
                $input['city'] ?? '',
                $input['state'] ?? '',
                $input['product_name'] ?? ''
            ]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>

