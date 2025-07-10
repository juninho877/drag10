<?php
/**
 * Script para resetar os contadores de troca de imagens para todos os usuários
 * Pode ser executado manualmente ou via cron job
 */

// Verificar se é execução via linha de comando ou web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Se for via web, verificar autenticação
    session_start();
    if (!isset($_SESSION["usuario"]) || $_SESSION["role"] !== 'admin') {
        http_response_code(403);
        die(json_encode(['error' => 'Acesso negado']));
    }
    
    header('Content-Type: application/json');
}

require_once 'classes/User.php';

try {
    $user = new User();
    $result = $user->resetAllImageChangeCounts();
    
    if ($isCLI) {
        echo "Resultado do reset de contadores:\n";
        echo "Sucesso: " . ($result['success'] ? "Sim" : "Não") . "\n";
        echo "Mensagem: " . $result['message'] . "\n";
        if (isset($result['affected_rows'])) {
            echo "Usuários afetados: " . $result['affected_rows'] . "\n";
        }
    } else {
        echo json_encode($result);
    }
} catch (Exception $e) {
    $error = [
        'success' => false,
        'message' => 'Erro ao resetar contadores: ' . $e->getMessage()
    ];
    
    if ($isCLI) {
        echo "ERRO: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode($error);
    }
}
?>