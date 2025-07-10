<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["role"] !== 'master') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

require_once 'classes/User.php';
require_once 'classes/MercadoPagoPayment.php';
require_once 'classes/CreditTransaction.php';
require_once 'config/database.php'; // Garantir que o Database está disponível

// Verificar se o payment_id foi fornecido e não está vazio
if (empty($_POST['payment_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID do pagamento não fornecido ou vazio']);
    exit();
}

$paymentId = $_POST['payment_id'];
$credits = isset($_POST['credits']) ? intval($_POST['credits']) : 1;
$userId = $_SESSION['user_id'];

try {
    $mercadoPagoPayment = new MercadoPagoPayment();
    $creditTransaction = new CreditTransaction();
    
    // Log para depuração
    error_log("Verificando pagamento: payment_id=$paymentId, credits=$credits, user_id=$userId");
    
    // Verificar status do pagamento
    $result = $mercadoPagoPayment->checkPaymentStatus($paymentId);
    
    // Log do resultado
    error_log("Resultado da verificação: " . json_encode($result));
    
    if (!$result['success']) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Erro ao verificar status do pagamento'
        ]);
        exit();
    }
    
    $response = [
        'success' => true,
        'status' => $result['status'] ?? 'unknown',
        'message' => '',
        'is_processed' => $result['is_processed'] ?? false
    ];
    
    // Processar com base no status
    if ($result['status'] === 'approved') {
        error_log("Pagamento aprovado: is_processed=" . ($result['is_processed'] ? 'true' : 'false'));
        // Verificar se o pagamento já foi processado
        if ($result['is_processed']) {
            $response['message'] = "Pagamento já foi processado anteriormente. Seus créditos já foram adicionados.";
        } else {
            // O processamento real (adição de créditos) já foi feito no método checkPaymentStatus
            // Aqui apenas informamos ao usuário o que aconteceu
            $response['message'] = "Pagamento confirmado! {$credits} créditos foram adicionados à sua conta.";
            $response['should_clear_session'] = true;
            
            // Registrar a transação de crédito
            try {
                $creditTransaction->recordTransaction(
                    $userId,
                    'purchase',
                    $credits,
                    "Compra de {$credits} créditos via Mercado Pago",
                    null,
                    $paymentId
                );
                error_log("Transação de crédito registrada com sucesso");
            } catch (Exception $e) {
                error_log("Erro ao registrar transação de crédito: " . $e->getMessage());
            }
        }
    } elseif ($result['status'] === 'pending') {
        error_log("Pagamento pendente");
        $response['message'] = "Pagamento pendente. Aguardando confirmação do Mercado Pago.";
    } elseif ($result['status'] === 'rejected' || $result['status'] === 'cancelled') {
        error_log("Pagamento rejeitado ou cancelado: " . $result['status']);
        $response['message'] = "Pagamento " . ($result['status'] === 'rejected' ? 'rejeitado' : 'cancelado') . ". Por favor, tente novamente com outro método de pagamento.";
        $response['should_clear_session'] = true;
    } else {
        error_log("Status de pagamento desconhecido: " . $result['status']);
        $response['message'] = "Status do pagamento: " . $result['status'];
    }
    
    // Log da resposta final
    error_log("Resposta final: " . json_encode($response));
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
    
} catch (Exception $e) {
    error_log("Exceção ao processar pagamento: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
    ]);
    exit();
}
?>