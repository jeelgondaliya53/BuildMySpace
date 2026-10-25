<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json');

try {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $_GET['action'] ?? '';

    if ($action === 'calc') {
        echo json_encode(calculate($body));
    } elseif ($action === 'save') {
        $name = trim($body['name'] ?? '');
        $email = trim($body['email'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            exit(json_encode(['error' => 'Enter your name and a valid email address.']));
        }
        $r = calculate($body['inputs'] ?? []);
        $st = db()->prepare('INSERT INTO estimates (name, email, inputs, result, total) VALUES (?,?,?,?,?)');
        $st->execute([mb_substr($name, 0, 80), $email, json_encode($r['inputs']), json_encode($r), $r['total']]);
        echo json_encode(['id' => (int)db()->lastInsertId()]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong on the server. Check config.php and that database.sql was imported.']);
}
