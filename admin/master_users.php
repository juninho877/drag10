<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["role"] !== 'master') {
    header("Location: login.php");
    exit();
}

require_once 'classes/User.php';
require_once 'classes/AdminSettings.php';

$user = new User();
$adminSettings = new AdminSettings();
$masterId = $_SESSION['user_id'];

// Obter o número de dias de teste configurado
$trialDays = intval($adminSettings->getSetting('trial_days', 2)); // Padrão: 2 dias

// Processar filtros
$filters = [
    'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
    'status' => isset($_GET['status']) ? $_GET['status'] : 'all'
];

// Obter usuários filtrados
$users = $user->getUsersByParentId($masterId, $filters);
$masterCredits = $user->getUserCredits($masterId);

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'change_status':
            header('Content-Type: application/json');
            $result = $user->changeStatus($_POST['user_id'], $_POST['status']);
            echo json_encode($result);
            exit;
            
        case 'delete_user':
            header('Content-Type: application/json');
            $result = $user->deleteUser($_POST['user_id']);
            echo json_encode($result);
            exit;
            
        case 'create_trial_user':
            header('Content-Type: application/json');
            // Inicializar AdminSettings para obter o número de dias de teste
            $trialDays = intval($adminSettings->getSetting('trial_days', 2)); // Padrão: 2 dias
            
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            
            // Verificar se o master tem créditos suficientes
            if ($masterCredits < 1) {
                echo json_encode(['success' => false, 'message' => 'Você não tem créditos suficientes para criar um usuário de teste']);
                exit;
            }
            
            if (empty($username) || empty($password) || empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Nome de usuário, email e senha são obrigatórios']);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres']);
                exit;
            }
            
            $data = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => 'user',
                'status' => 'trial',
                'expires_at' => date('Y-m-d', strtotime("+{$trialDays} days")),
                'parent_user_id' => $masterId
            ];
            
            $result = $user->createUser($data);
            echo json_encode($result);
            exit;
    }
}

$pageTitle = "Gerenciamento de Usuários";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-users text-primary-500 mr-3"></i>
        Gerenciamento de Usuários
    </h1>
    <p class="page-subtitle">Gerencie os usuários que você criou</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Total de Usuários</p>
                    <p class="text-2xl font-bold text-primary"><?php echo count($users); ?></p>
                </div>
                <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-primary-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Créditos Disponíveis</p>
                    <p class="text-2xl font-bold text-success-500"><?php echo $masterCredits; ?></p>
                </div>
                <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-coins text-success-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Usuários Ativos</p>
                    <p class="text-2xl font-bold text-info-500">
                        <?php 
                        $activeUsers = array_filter($users, function($user) {
                            return $user['status'] === 'active' && (!$user['expires_at'] || $user['expires_at'] >= date('Y-m-d'));
                        });
                        echo count($activeUsers);
                        ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-info-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-check text-info-500"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">Filtrar Usuários</h3>
        <p class="card-subtitle">Refine a lista de usuários</p>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label for="search" class="form-label">Buscar por Nome/Email</label>
                <input type="text" id="search" name="search" class="form-input" 
                       value="<?php echo htmlspecialchars($filters['search']); ?>" 
                       placeholder="Digite o nome ou email">
            </div>
            
            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-input form-select">
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>Todos os status</option>
                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inativo</option>
                    <option value="expired" <?php echo $filters['status'] === 'expired' ? 'selected' : ''; ?>>Expirado</option>
                </select>
            </div>
            
            <div class="form-actions md:col-span-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Filtrar
                </button>
                
                <a href="master_users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Limpar Filtros
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Actions Bar -->
<div class="flex justify-between items-center mb-6">
    <div class="flex gap-3">
        <button id="refreshBtn" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i>
            Atualizar
        </button>
    </div>
    <div class="flex gap-3">
        <button id="showReferralLinkBtn" class="btn btn-success">
            <i class="fas fa-link"></i>
            Meu Link de Cadastro
        </button>
        <a href="master_add_user.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Adicionar Usuário
        </a>
        <button id="createTrialUserBtn" class="btn btn-warning">
            <i class="fas fa-user-clock"></i>
            Criar Teste
        </button>
    </div>
</div>

<!-- Credit Info -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-coins text-warning-500 mr-2"></i>
            Informações de Créditos
        </h3>
    </div>
    <div class="card-body">
        <div class="flex flex-col md:flex-row md:items-center gap-4">
            <div class="credit-info">
                <p class="text-lg font-semibold">Você tem <span class="text-success-500"><?php echo $masterCredits; ?></span> créditos disponíveis</p>
                <p class="text-sm text-muted">Cada crédito permite criar ou renovar um usuário por 1 mês</p>
            </div>
            <div class="ml-auto">
                <a href="buy_credits.php" class="btn btn-success">
                    <i class="fas fa-shopping-cart"></i>
                    Comprar Mais Créditos
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Usuários</h3>
        <p class="card-subtitle">Gerencie todos os usuários que você criou</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Expira em</th>
                        <th>Último Login</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Nenhum usuário encontrado</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $userData): 
                            // Verificar se o usuário está expirado
                            $isExpired = $userData['expires_at'] && strtotime($userData['expires_at']) < time();
                        ?>
                            <tr data-user-id="<?php echo $userData['id']; ?>">
                                <td><?php echo $userData['id']; ?></td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar-small">
                                            <?php echo strtoupper(substr($userData['username'], 0, 2)); ?>
                                        </div>
                                        <span class="font-medium"><?php echo htmlspecialchars($userData['username']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($userData['email'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($isExpired): ?>
                                        <span class="status-badge status-expired">Expirado</span>
                                    <?php else: ?>
                                        <span class="status-badge status-<?php echo $userData['status']; ?>">
                                            <?php 
                                            if ($userData['status'] === 'active') {
                                                echo 'Ativo';
                                            } elseif ($userData['status'] === 'trial') {
                                                echo 'Teste';
                                            } else {
                                                echo 'Inativo';
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($userData['expires_at']) {
                                        $expiresAt = new DateTime($userData['expires_at']);
                                        $now = new DateTime();
                                        $isExpired = $expiresAt < $now;
                                        echo '<span class="' . ($isExpired ? 'text-danger-500' : 'text-muted') . '">';
                                        echo $expiresAt->format('d/m/Y');
                                        echo '</span>';
                                    } else {
                                        echo '<span class="text-muted">Nunca</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($userData['last_login']) {
                                        $lastLogin = new DateTime($userData['last_login']);
                                        echo $lastLogin->format('d/m/Y H:i');
                                    } else {
                                        echo '<span class="text-muted">Nunca</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="master_edit_user.php?id=<?php echo $userData['id']; ?>" class="btn-action btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <button class="btn-action btn-primary renew-user" data-user-id="<?php echo $userData['id']; ?>" data-username="<?php echo htmlspecialchars($userData['username']); ?>" title="Renovar">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        
                                        <?php if ($userData['status'] === 'active'): ?>
                                            <button class="btn-action btn-warning toggle-status" data-user-id="<?php echo $userData['id']; ?>" data-status="inactive" title="Desativar">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-action btn-success toggle-status" data-user-id="<?php echo $userData['id']; ?>" data-status="active" title="Ativar">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn-action btn-danger delete-user" data-user-id="<?php echo $userData['id']; ?>" data-username="<?php echo htmlspecialchars($userData['username']); ?>" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .users-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .users-table th,
    .users-table td {
        padding: 1rem 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .users-table th {
        background: var(--bg-secondary);
        font-weight: 600;
        color: var(--text-primary);
    }

    .users-table tbody tr:hover {
        background: var(--bg-secondary);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .user-avatar-small {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .status-active {
        background: var(--success-50);
        color: var(--success-600);
    }

    .status-inactive {
        background: var(--danger-50);
        color: var(--danger-600);
    }
    
    .status-expired {
        background: var(--warning-50);
        color: var(--warning-600);
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .btn-action {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        text-decoration: none;
    }

    .btn-edit {
        background: var(--primary-50);
        color: var(--primary-600);
    }

    .btn-edit:hover {
        background: var(--primary-100);
    }

    .btn-success {
        background: var(--success-50);
        color: var(--success-600);
    }

    .btn-success:hover {
        background: var(--success-100);
    }

    .btn-warning {
        background: var(--warning-50);
        color: var(--warning-600);
    }

    .btn-warning:hover {
        background: var(--warning-100);
    }

    .btn-danger {
        background: var(--danger-50);
        color: var(--danger-600);
    }

    .btn-danger:hover {
        background: var(--danger-100);
    }
    
    .btn-primary {
        background: var(--primary-50);
        color: var(--primary-600);
    }
    
    .btn-primary:hover {
        background: var(--primary-100);
    }

    .credit-info {
        padding: 1rem;
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
    }

    .text-info-500 {
        color: var(--info-500);
    }

    .bg-info-50 {
        background-color: var(--info-50);
    }
    
    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .status-active {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success-400);
    }

    [data-theme="dark"] .status-inactive {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-400);
    }
    
    [data-theme="dark"] .status-expired {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-400);
    }
    
    [data-theme="dark"] .btn-primary {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-400);
    }

    /* Estilos para o modal de criação de usuário de teste */
    .custom-modal {
        border-radius: 16px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .trial-user-form {
        padding: 1rem 0;
    }

    .trial-form-group {
        margin-bottom: 1.25rem;
        text-align: left;
    }

    .trial-form-group label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .trial-form-group label i {
        margin-right: 0.5rem;
        color: var(--primary-500);
    }

    .trial-form-group input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        background: var(--bg-secondary);
        color: var(--text-primary);
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .trial-form-group input:focus {
        border-color: var(--primary-500);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .password-input-wrapper {
        position: relative;
    }

    .toggle-password {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
    }

    .toggle-password:hover {
        color: var(--primary-500);
    }

    .trial-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem;
        background: var(--bg-tertiary);
        border-radius: 8px;
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-top: 0.5rem;
    }

    .trial-info i {
        color: var(--primary-500);
    }

    .custom-confirm-button {
        background: var(--primary-500) !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        padding: 0.75rem 1.5rem !important;
        transition: all 0.3s ease !important;
    }

    .custom-confirm-button:hover {
        background: var(--primary-600) !important;
        transform: translateY(-1px) !important;
    }

    .custom-cancel-button {
        background: var(--bg-tertiary) !important;
        color: var(--text-primary) !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        padding: 0.75rem 1.5rem !important;
        transition: all 0.3s ease !important;
    }

    .custom-cancel-button:hover {
        background: var(--bg-secondary) !important;
    }
    
    /* Mobile Responsive Design */
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .btn-action {
            width: 100%;
            justify-content: center;
        }
        
        .filter-form .grid {
            grid-template-columns: 1fr !important;
        }
        
        .form-actions {
            flex-direction: column;
            width: 100%;
        }
        
        .form-actions .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .flex.justify-between {
            flex-direction: column;
            gap: 1rem;
        }
        
        .flex.justify-between .flex {
            width: 100%;
        }
        
        .flex.justify-between .btn {
            width: 100%;
        }
        
        .card-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .card-title {
            margin-bottom: 0.5rem;
        }
        
        .stats-mobile {
            grid-template-columns: 1fr !important;
        }
        
        .user-info {
            flex-direction: column;
            align-items: flex-start;
            text-align: center;
            width: 100%;
        }
        
        .user-avatar-small {
            margin: 0 auto 0.5rem;
        }
        
        .users-table th:nth-child(3),
        .users-table td:nth-child(3),
        .users-table th:nth-child(6),
        .users-table td:nth-child(6) {
            display: none;
        }
    }
    
    @media (max-width: 480px) {
        .users-table th:nth-child(5),
        .users-table td:nth-child(5) {
            display: none;
        }
        
        .card-body {
            padding: 1rem;
        }
    }

    /* Estilos para o modal de credenciais */
    .credentials-container {
        padding: 0;
    }

    .credentials-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .credentials-header i {
        font-size: 3rem;
        color: var(--success-500);
        margin-bottom: 1rem;
    }

    .credentials-header h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .credentials-body {
        background: var(--bg-secondary);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .credential-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .credential-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .credential-label {
        font-weight: 600;
        color: var(--text-primary);
    }

    .credential-value-container {
        display: flex;
        align-items: center;
    }

    .credential-value-container code {
        padding: 0.5rem 0.75rem;
        background: var(--bg-tertiary);
        border-radius: 6px;
        font-family: monospace;
        color: var(--primary-500);
        font-weight: 600;
    }

    .credentials-footer {
        text-align: center;
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .trial-info {
        background: rgba(51, 65, 85, 0.5);
    }

    [data-theme="dark"] .credential-value-container code {
        background: rgba(51, 65, 85, 0.8);
        color: var(--primary-400);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar Link de Referência
    document.getElementById('showReferralLinkBtn').addEventListener('click', function() {
        const masterId = <?php echo $masterId; ?>;
        const baseUrl = window.location.origin + '/admin/login.php?ref=' + masterId;
        
        Swal.fire({
            title: 'Seu Link de Cadastro',
            html: `
                <p class="mb-4">Compartilhe este link para que novos usuários sejam vinculados à sua conta:</p>
                <div class="flex items-center gap-2 mb-4">
                    <input type="text" id="referralLink" value="${baseUrl}" class="form-input" readonly style="width: 100%;">
                    <button type="button" id="copyLinkBtn" class="btn btn-primary" style="flex-shrink: 0;">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="text-sm text-muted">Quando um usuário se cadastrar através deste link, ele será automaticamente vinculado à sua conta.</p>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Fechar',
            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
            didOpen: () => {
                const copyBtn = document.getElementById('copyLinkBtn');
                const linkInput = document.getElementById('referralLink');
                
                copyBtn.addEventListener('click', function() {
                    linkInput.select();
                    document.execCommand('copy');
                    
                    // Feedback visual
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-copy"></i>';
                    }, 2000);
                    
                    // Notificação
                    const toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    
                    toast.fire({
                        icon: 'success',
                        title: 'Link copiado para a área de transferência!'
                    });
                });
            }
        });
    });

    // Toggle Status
    document.querySelectorAll('.toggle-status').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const newStatus = this.getAttribute('data-status');
            const statusText = newStatus === 'active' ? 'ativar' : 'desativar';
            
            Swal.fire({
                title: 'Confirmar Ação',
                text: `Deseja ${statusText} este usuário?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, ' + statusText,
                cancelButtonText: 'Cancelar',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            }).then((result) => {
                if (result.isConfirmed) {
                    changeUserStatus(userId, newStatus);
                }
            });
        });
    });

    // Delete User
    document.querySelectorAll('.delete-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            
            Swal.fire({
                title: 'Excluir Usuário',
                text: `Tem certeza que deseja excluir o usuário "${username}"? Esta ação não pode ser desfeita.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteUser(userId);
                }
            });
        });
    });
    
    // Renew User
    document.querySelectorAll('.renew-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            
            Swal.fire({
                title: 'Renovar Usuário',
                html: `
                    <p class="mb-4">Escolha por quantos meses deseja renovar o usuário <strong>${username}</strong>:</p>
                    <div class="renewal-options">
                        <button type="button" class="renewal-option" data-months="1">1 mês</button>
                        <button type="button" class="renewal-option" data-months="3">3 meses</button>
                        <button type="button" class="renewal-option" data-months="6">6 meses</button>
                        <button type="button" class="renewal-option" data-months="12">12 meses</button>
                    </div>
                    <p class="mt-4 text-sm">Créditos disponíveis: <strong>${<?php echo $masterCredits; ?>}</strong></p>
                `,
                showCancelButton: true,
                showConfirmButton: false,
                cancelButtonText: 'Cancelar',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
                didOpen: () => {
                    // Estilizar opções de renovação
                    const options = Swal.getPopup().querySelectorAll('.renewal-option');
                    options.forEach(option => {
                        option.style.margin = '5px';
                        option.style.padding = '10px 15px';
                        option.style.borderRadius = '8px';
                        option.style.border = '1px solid #e2e8f0';
                        option.style.background = document.body.getAttribute('data-theme') === 'dark' ? '#334155' : '#f8fafc';
                        option.style.cursor = 'pointer';
                        option.style.fontWeight = '500';
                        
                        option.addEventListener('mouseover', function() {
                            this.style.background = document.body.getAttribute('data-theme') === 'dark' ? '#475569' : '#f1f5f9';
                        });
                        
                        option.addEventListener('mouseout', function() {
                            this.style.background = document.body.getAttribute('data-theme') === 'dark' ? '#334155' : '#f8fafc';
                        });
                        
                        option.addEventListener('click', function() {
                            const months = parseInt(this.getAttribute('data-months'));
                            renewUser(userId, username, months);
                            Swal.close();
                        });
                    });
                }
            });
        });
    });

    // Refresh Button
    document.getElementById('refreshBtn').addEventListener('click', function() {
        location.reload();
    });
    
    // Criar usuário de teste
    document.getElementById('createTrialUserBtn').addEventListener('click', function() {
        // Verificar se o master tem créditos suficientes
        const masterCredits = <?php echo $masterCredits; ?>;
        if (masterCredits < 1) {
            Swal.fire({
                title: 'Créditos Insuficientes',
                html: `
                    <div class="p-3">
                        <p class="mb-4">Você precisa de pelo menos 1 crédito para criar um usuário de teste.</p>
                        <p class="text-sm text-muted mb-4">Seus créditos atuais: <strong>0</strong></p>
                        <a href="buy_credits.php" class="btn btn-success w-full">
                            <i class="fas fa-shopping-cart"></i>
                            Comprar Créditos
                        </a>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Fechar',
                customClass: {
                    popup: 'custom-modal',
                    cancelButton: 'custom-cancel-button'
                },
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            });
            return;
        }
        
        Swal.fire({
            title: 'Criar Usuário de Teste',
            html: `<div class="trial-user-form">
                    <div class="trial-form-group">
                        <label for="trial_username">
                            <i class="fas fa-user"></i>
                            Nome de Usuário
                        </label>
                        <input type="text" id="trial_username" placeholder="Digite o nome de usuário" required>
                    </div>
                    <div class="trial-form-group">
                        <label for="trial_email">
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <input type="email" id="trial_email" placeholder="Digite o email" required>
                    </div>
                    <div class="trial-form-group">
                        <label for="trial_password">
                            <i class="fas fa-lock"></i>
                            Senha
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" id="trial_password" placeholder="Mínimo de 6 caracteres" required>
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('trial_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="trial-info">
                        <i class="fas fa-info-circle"></i>
                        <span>O usuário terá acesso por <strong>${<?php echo $trialDays; ?>}</strong> dias de teste</span>
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'Criar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'custom-modal',
                confirmButton: 'custom-confirm-button',
                cancelButton: 'custom-cancel-button'
            },
            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
            preConfirm: () => {
                const username = document.getElementById('trial_username').value;
                const email = document.getElementById('trial_email').value;
                const password = document.getElementById('trial_password').value;
                
                if (!username || !password || !email) {
                    Swal.showValidationMessage('Preencha todos os campos obrigatórios');
                    return false;
                }
                
                if (password.length < 6) {
                    Swal.showValidationMessage('A senha deve ter pelo menos 6 caracteres');
                    return false;
                }

                if (!email.includes('@')) {
                    Swal.showValidationMessage('Digite um email válido');
                    return false;
                }
                
                return { username, email, password };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { username, email, password } = result.value;
                
                fetch('master_users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=create_trial_user&username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Usuário Criado!',
                            html: `<div class="credentials-container">
                                    <div class="credentials-header">
                                        <i class="fas fa-check-circle"></i>
                                        <h3>Usuário de teste criado com sucesso!</h3>
                                    </div>
                                    <div class="credentials-body">
                                        <div class="credential-item">
                                            <span class="credential-label">Usuário:</span>
                                            <div class="credential-value-container">
                                                <code>${username}</code>
                                            </div>
                                        </div>
                                        <div class="credential-item">
                                            <span class="credential-label">Email:</span>
                                            <div class="credential-value-container">
                                                <code>${email}</code>
                                            </div>
                                        </div>
                                        <div class="credential-item">
                                            <span class="credential-label">Senha:</span>
                                            <div class="credential-value-container">
                                                <code>${password}</code>
                                            </div>
                                        </div>
                                        <div class="credential-item">
                                            <span class="credential-label">Validade:</span>
                                            <div class="credential-value-container">
                                                <code>${<?php echo $trialDays; ?>} dias</code>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="credentials-footer">
                                        <p>Copie estas informações para compartilhar com o usuário.</p>
                                    </div>
                                </div>`,
                            icon: 'success',
                            confirmButtonText: 'Copiar Credenciais',
                            customClass: {
                                popup: 'custom-modal',
                                confirmButton: 'custom-confirm-button'
                            },
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const credentials = `Usuário: ${username}\nEmail: ${email}\nSenha: ${password}\nValidade: ${<?php echo $trialDays; ?>} dias`;
                                navigator.clipboard.writeText(credentials).then(() => {
                                    Swal.fire({
                                        title: 'Copiado!',
                                        text: 'Credenciais copiadas para a área de transferência',
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false,
                                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                                        color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                                    }).then(() => {
                                        location.reload();
                                    });
                                });
                            } else {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Erro!',
                            text: data.message,
                            icon: 'error',
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro de comunicação com o servidor',
                        icon: 'error',
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                    });
                });
            }
        });
    });

    function changeUserStatus(userId, status) {
        fetch('master_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=change_status&user_id=${userId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Sucesso!',
                    text: data.message,
                    icon: 'success',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Erro!',
                    text: data.message,
                    icon: 'error',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Erro!',
                text: 'Erro de comunicação com o servidor',
                icon: 'error',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            });
        });
    }

    function deleteUser(userId) {
        fetch('master_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_user&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Sucesso!',
                    text: data.message,
                    icon: 'success',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Erro!',
                    text: data.message,
                    icon: 'error',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Erro!',
                text: 'Erro de comunicação com o servidor',
                icon: 'error',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            });
        });
    }
    
    function renewUser(userId, username, months) {
        // Mostrar loading
        Swal.fire({
            title: 'Processando...',
            text: 'Aguarde enquanto renovamos o usuário',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
        });
        
        // Enviar solicitação para renovar usuário
        fetch('renew_user_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}&months=${months}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Sucesso!',
                    text: data.message,
                    icon: 'success',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Erro!',
                    text: data.message,
                    icon: 'error',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Erro!',
                text: 'Erro de comunicação com o servidor',
                icon: 'error',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            });
        });
    }
});

// Função para alternar a visibilidade da senha
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.querySelector(`.toggle-password i`);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<?php include "includes/footer.php"; ?>