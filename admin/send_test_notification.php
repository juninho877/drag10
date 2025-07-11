<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["role"] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

require_once 'classes/TelegramNotifier.php';

// Verificar se os parâmetros necessários foram fornecidos
if (!isset($_POST['bot_token']) || !isset($_POST['chat_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit();
}

$botToken = trim($_POST['bot_token']);
$chatId = trim($_POST['chat_id']);

// Validar parâmetros
if (empty($botToken) || empty($chatId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Token do bot e Chat ID são obrigatórios']);
    exit();
}

// Enviar notificação de teste
$result = TelegramNotifier::sendTestNotification($botToken, $chatId);

header('Content-Type: application/json');
if ($result) {
    echo json_encode([
        'success' => true, 
        'message' => 'Notificação de teste enviada com sucesso'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao enviar notificação de teste. Verifique o token do bot e o Chat ID.'
    ]);
}
exit();