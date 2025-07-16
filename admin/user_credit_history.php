<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["role"] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'classes/User.php';
require_once 'classes/CreditTransaction.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user_management.php");
    exit();
}

$userId = (int)$_GET['id'];
$userClass = new User();
$userData = $userClass->getUserById($userId);

if (!$userData) {
    header("Location: user_management.php?error=user_not_found");
    exit();
}

$creditTransaction = new CreditTransaction();
$transactions = $creditTransaction->getUserTransactions($userId, 100); // Obter até 100 transações

$pageTitle = "Histórico de Créditos - " . $userData['username'];
include "includes/header.php";
?>

<style>
    /* === ESTILOS GERAIS E DE COMPONENTES === */
    html, body {
        width: 100%;
        overflow-x: hidden;
    }
    .user-avatar-large {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .credit-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--success-50);
        color: var(--success-600);
        border-radius: var(--border-radius);
        font-weight: 600;
    }
    .ml-auto { margin-left: auto; }
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-muted);
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    .form-input {
        font-size: 16px !important; /* Evita zoom em iOS */
    }

    /* === TABELA DESKTOP E SEUS ESTILOS === */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .transactions-table {
        width: 100%;
        min-width: 700px; /* Largura mínima para forçar rolagem */
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .transactions-table th,
    .transactions-table td {
        padding: 1rem 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .transactions-table th {
        background: var(--bg-secondary);
        font-weight: 600;
    }
    .transaction-type { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
    .type-purchase { background: var(--success-50); color: var(--success-600); }
    .type-admin_add { background: var(--warning-50); color: var(--warning-600); }
    .type-user_creation, .type-user_renewal { background: var(--danger-50); color: var(--danger-600); }
    .user-link { color: var(--primary-500); text-decoration: none; font-weight: 500; }
    .payment-id { font-family: monospace; background: var(--bg-tertiary); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }

    /* === CARDS MOBILE (ESCONDIDOS POR PADRÃO) === */
    .mobile-transactions-grid {
        display: none;
        flex-direction: column;
        gap: 1rem;
    }
    .mobile-card {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 1rem;
        box-shadow: var(--shadow-sm);
    }
    .mobile-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
    .mobile-card-header .transaction-type { font-size: 0.8rem; }
    .mobile-card-header .amount { font-size: 1.1rem; font-weight: 600; }
    .mobile-card-body p { margin: 0; word-break: break-word; }
    .mobile-card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; font-size: 0.8rem; color: var(--text-muted); }

    /* === MEDIA QUERIES PARA RESPONSIVIDADE === */
    @media (max-width: 991px) {
        .table-responsive { display: none; }
        .mobile-transactions-grid { display: flex; }
    }

    @media (max-width: 768px) {
        .card-body .flex.items-center {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        .card-body .ml-auto {
            margin-left: 0;
            display: flex;
            gap: 0.75rem;
            width: 100%;
        }
        .card-body .ml-auto .btn {
            flex: 1;
            justify-content: center;
        }
        #addCreditForm {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-history text-primary-500 mr-3"></i>
        Histórico de Créditos
    </h1>
    <p class="page-subtitle">Transações de créditos do usuário <?php echo htmlspecialchars($userData['username']); ?></p>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">Informações do Usuário</h3>
    </div>
    <div class="card-body">
        <div class="flex items-center gap-4">
            <div class="user-avatar-large">
                <?php echo strtoupper(substr($userData['username'], 0, 2)); ?>
            </div>
            <div>
                <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($userData['username']); ?></h2>
                <p class="text-sm text-muted">
                    <?php 
                    switch ($userData['role']) {
                        case 'admin': echo 'Administrador'; break;
                        case 'master': echo 'Master'; break;
                        default: echo 'Usuário'; break;
                    }
                    ?>
                </p>
                <?php if ($userData['role'] === 'master'): ?>
                <p class="mt-2">
                    <span class="credit-badge">
                        <i class="fas fa-coins"></i>
                        <?php echo $userData['credits']; ?> créditos
                    </span>
                </p>
                <?php endif; ?>
            </div>
            <div class="ml-auto">
                <a href="edit_user.php?id=<?php echo $userId; ?>" class="btn btn-secondary">
                    <i class="fas fa-user-edit"></i>
                    Editar Usuário
                </a>
                <a href="user_management.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Histórico de Transações</h3>
        <p class="card-subtitle">Mostrando até 100 transações mais recentes</p>
    </div>
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <i class="fas fa-history text-muted"></i>
                <p>Nenhuma transação encontrada para este usuário</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Descrição</th>
                            <th>Relacionado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo $transaction['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                <td>
                                    <span class="transaction-type type-<?php echo $transaction['transaction_type']; ?>">
                                        <?php 
                                        switch ($transaction['transaction_type']) {
                                            case 'purchase': echo 'Compra'; break;
                                            case 'admin_add': echo 'Adição Manual'; break;
                                            case 'user_creation': echo 'Criação de Usuário'; break;
                                            case 'user_renewal': echo 'Renovação de Usuário'; break;
                                            default: echo ucfirst($transaction['transaction_type']); break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $transaction['amount'] > 0 ? 'text-success-500' : 'text-danger-500'; ?>">
                                        <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo $transaction['amount']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td>
                                    <?php if ($transaction['related_entity_id']): ?>
                                        <a href="edit_user.php?id=<?php echo $transaction['related_entity_id']; ?>" class="user-link">
                                            <?php echo htmlspecialchars($transaction['related_username']); ?>
                                        </a>
                                    <?php elseif ($transaction['related_payment_id']): ?>
                                        <span class="payment-id" title="ID do Pagamento">
                                            <?php echo substr($transaction['related_payment_id'], 0, 10) . '...'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mobile-transactions-grid">
                <?php foreach ($transactions as $transaction): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header">
                            <span class="transaction-type type-<?php echo $transaction['transaction_type']; ?>">
                                <?php 
                                switch ($transaction['transaction_type']) {
                                    case 'purchase': echo 'Compra'; break;
                                    case 'admin_add': echo 'Adição Manual'; break;
                                    case 'user_creation': echo 'Criação'; break;
                                    case 'user_renewal': echo 'Renovação'; break;
                                    default: echo ucfirst($transaction['transaction_type']); break;
                                }
                                ?>
                            </span>
                            <span class="amount <?php echo $transaction['amount'] > 0 ? 'text-success-500' : 'text-danger-500'; ?>">
                                <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo $transaction['amount']; ?>
                            </span>
                        </div>
                        <div class="mobile-card-body">
                            <p><?php echo htmlspecialchars($transaction['description']); ?></p>
                        </div>
                        <div class="mobile-card-footer">
                            <span>
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?>
                            </span>
                            <?php if ($transaction['related_entity_id']): ?>
                                <a href="edit_user.php?id=<?php echo $transaction['related_entity_id']; ?>" class="user-link">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($transaction['related_username']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php if ($userData['role'] === 'master'): ?>
<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-title">Adicionar Créditos</h3>
        <p class="card-subtitle">Adicione créditos manualmente a este usuário</p>
    </div>
    <div class="card-body">
        <form id="addCreditForm" class="flex items-end gap-4">
            <div class="form-group flex-1 mb-0">
                <label for="credit_amount" class="form-label">Quantidade de Créditos</label>
                <input type="number" id="credit_amount" name="credit_amount" class="form-input" value="1" required>
            </div>
            <div class="form-group flex-1 mb-0">
                <label for="credit_description" class="form-label">Descrição (opcional)</label>
                <input type="text" id="credit_description" name="credit_description" class="form-input" placeholder="Motivo da adição">
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-plus-circle"></i>
                Adicionar
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add Credit Form
    const addCreditForm = document.getElementById('addCreditForm');
    if (addCreditForm) {
        addCreditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const creditAmount = document.getElementById('credit_amount').value;
            const creditDescription = document.getElementById('credit_description').value;
            
            if (!creditAmount || creditAmount == 0) {
                Swal.fire({
                    title: 'Erro!',
                    text: 'A quantidade de créditos deve ser pelo menos 1',
                    icon: 'error',
                });
                return;
            }
            
            Swal.fire({
                title: 'Confirmar Adição',
                text: `Deseja adicionar ${creditAmount} créditos para este usuário?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, adicionar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Enviar solicitação para adicionar créditos
                    fetch('user_management.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=add_credits&user_id=<?php echo $userId; ?>&credits=${creditAmount}&description=${encodeURIComponent(creditDescription)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Sucesso!',
                                text: data.message,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro!',
                                text: data.message,
                                icon: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Erro!', 'Erro de comunicação com o servidor.', 'error');
                    });
                }
            });
        });
    }
});
</script>

<?php include "includes/footer.php"; ?>