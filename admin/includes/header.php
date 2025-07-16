<?php
session_start();
// Verificar permissões
if (!isset($_SESSION["usuario"]) || $_SESSION["role"] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'classes/User.php';
require_once 'classes/CreditTransaction.php';

$user = new User();
require_once 'classes/AdminSettings.php';
$adminSettings = new AdminSettings();
$creditTransaction = new CreditTransaction();
$db = Database::getInstance()->getConnection();

// Processar filtros
$filters = [
    'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
    'role' => isset($_GET['role']) ? $_GET['role'] : 'all',
    'status' => isset($_GET['status']) ? $_GET['status'] : 'all'
];

// Obter usuários filtrados
$users = $user->getAllUsers($filters);
$stats = $user->getUserStats();

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'save_trial_days':
            $trialDays = intval($_POST['trial_days']);
            if ($trialDays < 1) $trialDays = 1;
            if ($trialDays > 30) $trialDays = 30;
            
            $result = $adminSettings->setSetting('trial_days', $trialDays);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Configuração salva com sucesso' : 'Erro ao salvar configuração',
                'trial_days' => $trialDays
            ]);
            exit;
            
        case 'create_trial_user':
            // Inicializar AdminSettings para obter o número de dias de teste
            $adminSettings = new AdminSettings();
            $trialDays = intval($adminSettings->getSetting('trial_days', 2)); // Padrão: 2 dias
            
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            
            if (empty($username) || empty($password) || empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Nome de usuário, email e senha são obrigatórios']);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Email inválido']);
                exit;
            }
            
            $data = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => 'user',
                'status' => 'trial',
                'expires_at' => date('Y-m-d', strtotime("+{$trialDays} days"))
            ];
            
            $result = $user->createUser($data);
            echo json_encode($result);
            exit;
            
        case 'change_status':
            $result = $user->changeStatus($_POST['user_id'], $_POST['status']);
            echo json_encode($result);
            exit;
            
        case 'delete_user':
            $result = $user->deleteUser($_POST['user_id']);
            echo json_encode($result);
            exit;
            
        case 'add_credits':
            $userId = intval($_POST['user_id']);
            $credits = intval($_POST['credits']);
            $description = isset($_POST['description']) ? $_POST['description'] : "Adição manual de créditos";
            
            $result = $user->addCredits($userId, $credits);
            
            if ($result['success']) {
                // Registrar a transação
                $creditTransaction->recordTransaction(
                    1, // Admin ID (assumindo que o admin tem ID 1)
                    'admin_add',
                    $credits,
                    $description,
                    $userId,
                    null
                );
            }
            
            echo json_encode($result);
            exit;
            
        case 'update_image_limits':
            $logoLimit = intval($_POST['logo_limit']);
            $movieLogoLimit = intval($_POST['movie_logo_limit']);
            $backgroundLimit = intval($_POST['background_limit']);
            
            $result = $user->updateAllImageChangeLimits($logoLimit, $movieLogoLimit, $backgroundLimit);
            echo json_encode($result);
            exit;
            
        case 'reset_image_counts':
            $result = $user->resetAllImageChangeCounts();
            echo json_encode($result);
            exit;
    }
}

// Buscar limites globais atuais (armazenados no usuário admin ID 1)
$stmt = $db->prepare("
    SELECT logo_change_limit, movie_logo_change_limit, background_change_limit
    FROM usuarios
    WHERE id = 1
");
$stmt->execute();
$globalLimits = $stmt->fetch(PDO::FETCH_ASSOC);

// Valores padrão se não encontrados
if (!$globalLimits) {
    $globalLimits = [
        'logo_change_limit' => 3,
        'movie_logo_change_limit' => 3,
        'background_change_limit' => 3
    ];
}

$trialDays = intval($adminSettings->getSetting('trial_days', 2)); // Padrão: 2 dias

$pageTitle = "Gerenciamento de Usuários";
include "includes/header.php";
?>

<style>
    /* === ESTILOS GERAIS === */
    html, body {
        width: 100%;
        overflow-x: hidden; /* Evita a barra de rolagem horizontal na página inteira */
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto; /* Permite rolar a tabela horizontalmente se ela for muito larga */
        -webkit-overflow-scrolling: touch; /* Melhora a rolagem em iOS */
    }
    
    .users-table {
        width: 100%;
        min-width: 900px; /* Define uma largura mínima para a tabela, forçando a rolagem em vez de espremer o conteúdo */
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .users-table th,
    .users-table td {
        padding: 1rem 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap; /* Impede que o conteúdo das células quebre linha */
    }
    
    .users-table th {
        background: var(--bg-secondary);
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .users-table tbody tr:hover {
        background: var(--bg-secondary);
    }
    
    .user-info { display: flex; align-items: center; gap: 0.75rem; }
    .user-avatar-small { width: 32px; height: 32px; background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem; flex-shrink: 0; }
    .role-badge, .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
    .role-admin { background: var(--warning-50); color: var(--warning-600); }
    .role-master { background: var(--primary-50); color: var(--primary-600); }
    .role-user { background: var(--success-50); color: var(--success-600); }
    .status-active { background: var(--success-50); color: var(--success-600); }
    .status-inactive { background: var(--danger-50); color: var(--danger-600); }
    .status-expired { background: var(--warning-50); color: var(--warning-600); }
    .action-buttons { display: flex; gap: 0.5rem; }
    .btn-action { width: 32px; height: 32px; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transition); text-decoration: none; }
    .limits-display { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .limit-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; background: var(--bg-tertiary); border-radius: var(--border-radius-sm); font-size: 0.75rem; color: var(--text-secondary); }
    .info-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: var(--bg-tertiary); border-radius: var(--border-radius-sm); color: var(--text-secondary); font-size: 0.875rem; }
    .form-actions { display: flex; gap: 1rem; margin-top: 1rem; }

    /* === LAYOUT MOBILE: CARDS DE USUÁRIO === */
    .mobile-users-grid {
        display: none; /* Escondido por padrão */
    }
    .mobile-user-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem; }
    .mobile-card-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
    .mobile-card-info { flex: 1; }
    .mobile-card-info h3 { font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem; }
    .mobile-card-info p { color: var(--text-secondary); font-size: 0.875rem; word-break: break-all; }
    .mobile-card-details { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .mobile-detail-item { display: flex; flex-direction: column; gap: 0.25rem; }
    .mobile-detail-label { font-size: 0.75rem; font-weight: 500; color: var(--text-muted); text-transform: uppercase; }
    .mobile-card-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-top: 1rem; }
    .mobile-action-btn { padding: 0.75rem 1rem; border: none; border-radius: var(--border-radius-sm); font-size: 0.875rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; text-decoration: none; }
    .mobile-action-primary { background: var(--primary-500); color: white; }
    .mobile-action-secondary { background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border-color); }
    .mobile-action-success { background: var(--success-500); color: white; }
    .mobile-action-warning { background: var(--warning-500); color: white; }
    .mobile-action-danger { background: var(--danger-500); color: white; }
    .mobile-credits-section { grid-column: 1 / -1; display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--bg-secondary); border-radius: var(--border-radius-sm); }
    .mobile-limits-display { display: flex; flex-direction: column; gap: 0.5rem; }


    /* === MEDIA QUERIES PARA RESPONSIVIDADE === */

    /* Estilos para Telas Médias e Pequenas (Tablets e Celulares) - max-width: 991px */
    @media (max-width: 991px) {
        /* ESCONDE a tabela tradicional */
        .table-responsive {
            display: none;
        }

        /* MOSTRA os cards para mobile */
        .mobile-users-grid {
            display: block;
        }
    }

    /* Estilos para Tablets - max-width: 768px */
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 1.5rem;
        }

        /* Faz os cards de estatísticas terem 2 colunas */
        .stats-mobile {
            grid-template-columns: repeat(2, 1fr);
        }
        
        /* Ajusta o layout dos formulários para uma única coluna */
        .grid.md\:grid-cols-3, .grid.md\:grid-cols-4 {
            grid-template-columns: 1fr;
        }

        .flex.items-center.gap-4 {
            flex-direction: column;
            align-items: stretch;
        }

        .flex.flex-wrap.gap-3 {
            flex-direction: column;
        }

        .actions-bar-mobile {
            flex-direction: column;
            align-items: stretch;
        }

        .actions-bar-mobile > div {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 0.75rem; /* Adiciona um espaço entre os botões */
        }

        .form-actions {
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .btn, .form-actions .btn, .actions-bar-mobile .btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* Estilos para Celulares Pequenos - max-width: 480px */
    @media (max-width: 480px) {
        /* Cards de estatísticas em uma única coluna */
        .stats-mobile {
            grid-template-columns: 1fr;
        }

        /* Detalhes do card de usuário em uma única coluna */
        .mobile-card-details {
            grid-template-columns: 1fr;
        }
        
        /* Botões de ação do card em uma única coluna */
        .mobile-card-actions {
            grid-template-columns: 1fr;
        }
    }
    
    /* === ESTILOS PARA MODAIS (SweetAlert) === */
    .trial-form-group input, .form-input, .form-select { 
        font-size: 16px !important; /* Evita zoom automático em iOS */
    }
    .swal2-popup {
        width: 95% !important;
        max-width: 500px !important;
    }
    .custom-modal { border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
    .trial-user-form { padding: 1rem 0; }
    .trial-form-group { margin-bottom: 1.25rem; text-align: left; }
    .trial-form-group label { display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; }
    .trial-form-group input { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); }
    .password-input-wrapper { position: relative; }
    .toggle-password { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; }
    .trial-info { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: var(--bg-tertiary); border-radius: 8px; font-size: 0.875rem; margin-top: 0.5rem; }
    .credentials-container { padding: 0; }
    .credentials-header { text-align: center; margin-bottom: 1.5rem; }
    .credentials-header i { font-size: 3rem; color: var(--success-500); margin-bottom: 1rem; }
    .credentials-header h3 { font-size: 1.25rem; font-weight: 600; }
    .credentials-body { background: var(--bg-secondary); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
    .credential-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
    .credential-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
    .credential-label { font-weight: 600; }
    .credential-value-container code { padding: 0.5rem 0.75rem; background: var(--bg-tertiary); border-radius: 6px; font-family: monospace; color: var(--primary-500); font-weight: 600; }
    .credentials-footer { text-align: center; font-size: 0.875rem; color: var(--text-secondary); }

    /* Estilos Dark Theme para os modais */
    [data-theme="dark"] .role-admin, [data-theme="dark"] .status-expired { background: rgba(245, 158, 11, 0.1); color: var(--warning-400); }
    [data-theme="dark"] .role-master { background: rgba(59, 130, 246, 0.1); color: var(--primary-400); }
    [data-theme="dark"] .role-user, [data-theme="dark"] .status-active { background: rgba(34, 197, 94, 0.1); color: var(--success-400); }
    [data-theme="dark"] .status-inactive { background: rgba(239, 68, 68, 0.1); color: var(--danger-400); }
    [data-theme="dark"] .btn-primary { background: rgba(59, 130, 246, 0.1); color: var(--primary-400); }
    [data-theme="dark"] .btn-secondary { background: var(--bg-tertiary); color: var(--text-muted); }
    [data-theme="dark"] .trial-info { background: rgba(51, 65, 85, 0.5); }
    [data-theme="dark"] .credential-value-container code { background: rgba(51, 65, 85, 0.8); color: var(--primary-400); }
</style>


<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-users text-primary-500 mr-3"></i>
        Gerenciamento de Usuários
    </h1>
    <p class="page-subtitle">Controle completo dos usuários do sistema</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6 stats-mobile">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Total de Usuários</p>
                    <p class="text-2xl font-bold text-primary"><?php echo $stats['total']; ?></p>
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
                    <p class="text-sm font-medium text-muted">Usuários Ativos</p>
                    <p class="text-2xl font-bold text-success-500"><?php echo $stats['active']; ?></p>
                </div>
                <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-check text-success-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Usuários Inativos</p>
                    <p class="text-2xl font-bold text-danger-500"><?php echo $stats['inactive']; ?></p>
                </div>
                <div class="w-12 h-12 bg-danger-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-times text-danger-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Masters</p>
                    <p class="text-2xl font-bold text-warning-500"><?php echo $stats['masters']; ?></p>
                </div>
                <div class="w-12 h-12 bg-warning-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-shield text-warning-500"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-alt text-primary-500 mr-2"></i>
            Configuração de Período de Teste
        </h3>
        <p class="card-subtitle">Defina o número de dias para o período de teste de novos usuários</p>
    </div>
    <div class="card-body">
        <div class="flex items-center gap-4">
            <div class="form-group mb-0 flex-1">
                <label for="trial_days" class="form-label">Dias de Teste</label>
                <input type="number" id="trial_days" class="form-input" value="<?php echo htmlspecialchars($trialDays); ?>" min="1" max="30">
                <p class="text-xs text-muted mt-1">Número de dias que novos usuários terão acesso gratuito ao sistema</p>
            </div>
            <div>
                <button id="saveTrialDaysBtn" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Salvar Configuração
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">Filtrar Usuários</h3>
        <p class="card-subtitle">Refine a lista de usuários</p>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="form-group">
                <label for="search" class="form-label">Buscar por Nome/Email</label>
                <input type="text" id="search" name="search" class="form-input"
                       value="<?php echo htmlspecialchars($filters['search']); ?>"
                       placeholder="Digite o nome ou email">
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Função</label>
                <select id="role" name="role" class="form-input form-select">
                    <option value="all" <?php echo $filters['role'] === 'all' ? 'selected' : ''; ?>>Todas as funções</option>
                    <option value="admin" <?php echo $filters['role'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="master" <?php echo $filters['role'] === 'master' ? 'selected' : ''; ?>>Master</option>
                    <option value="user" <?php echo $filters['role'] === 'user' ? 'selected' : ''; ?>>Usuário</option>
                </select>
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
            
            <div class="form-actions md:col-span-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Filtrar
                </button>
                
                <a href="user_management.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Limpar Filtros
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-images text-primary-500 mr-2"></i>
            Limites de Troca de Imagens
        </h3>
        <p class="card-subtitle">Configure os limites diários de troca de imagens para todos os usuários</p>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="form-group">
                <label for="global_logo_limit" class="form-label">
                    <i class="fas fa-image mr-2"></i>
                    Limite de Logos
                </label>
                <input type="number" id="global_logo_limit" class="form-input" min="0" value="<?php echo htmlspecialchars($globalLimits['logo_change_limit']); ?>">
                <p class="text-xs text-muted mt-1">Trocas de logo permitidas por dia</p>
            </div>
            
            <div class="form-group">
                <label for="global_movie_logo_limit" class="form-label">
                    <i class="fas fa-film mr-2"></i>
                    Limite de Logos de Filmes
                </label>
                <input type="number" id="global_movie_logo_limit" class="form-input" min="0" value="<?php echo htmlspecialchars($globalLimits['movie_logo_change_limit']); ?>">
                <p class="text-xs text-muted mt-1">Trocas de logo de filmes permitidas por dia</p>
            </div>
            
            <div class="form-group">
                <label for="global_background_limit" class="form-label">
                    <i class="fas fa-image mr-2"></i>
                    Limite de Fundos
                </label>
                <input type="number" id="global_background_limit" class="form-input" min="0" value="<?php echo htmlspecialchars($globalLimits['background_change_limit']); ?>">
                <p class="text-xs text-muted mt-1">Trocas de fundo permitidas por dia</p>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <button type="button" id="updateLimitsBtn" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Aplicar Limites para Todos
            </button>
            
            <button type="button" id="resetCountsBtn" class="btn btn-warning">
                <i class="fas fa-redo"></i>
                Resetar Contadores Diários
            </button>
            
            <div class="info-badge">
                <i class="fas fa-info-circle"></i>
                <span>Administradores não têm limites de troca de imagens</span>
            </div>
        </div>
    </div>
</div>

<div class="flex justify-between items-center mb-6 actions-bar-mobile">
    <div class="flex gap-3">
        <button id="refreshBtn" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i>
            Atualizar
        </button>
    </div>
    <div class="flex gap-3">
        <a href="add_user.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Adicionar Usuário
        </a>
        <button id="createTrialUserBtn" class="btn btn-warning">
            <i class="fas fa-user-clock"></i>
            Criar Teste
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Usuários</h3>
        <p class="card-subtitle">Gerencie todos os usuários do sistema</p>
    </div>
    <div class="card-body">
        
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Função</th>
                        <th>Status</th>
                        <th>Expira em</th>
                        <th>Créditos</th>
                        <th>Limites de Imagens</th>
                        <th>Último Login</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">Nenhum usuário encontrado</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $userData): 
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
                                    <span class="role-badge role-<?php echo $userData['role']; ?>">
                                        <?php 
                                        switch ($userData['role']) {
                                            case 'admin':
                                                echo 'Administrador';
                                                break;
                                            case 'master':
                                                echo 'Master';
                                                break;
                                            default:
                                                echo 'Usuário';
                                                break;
                                        }
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($isExpired): ?>
                                        <span class="status-badge status-expired">Expirado</span>
                                    <?php else: ?>
                                        <span class="status-badge status-<?php echo $userData['status']; ?>">
                                            <?php 
                                            if ($userData['status'] === 'active') {
                                                echo 'Ativo';
                                            } elseif ($userData['status'] === 'inactive') {
                                                echo 'Inativo';
                                            } elseif ($userData['status'] === 'trial') {
                                                echo 'Teste';
                                            } else {
                                                echo ucfirst($userData['status']);
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
                                        $isExpiredCheck = $expiresAt < $now;
                                        echo '<span class="' . ($isExpiredCheck ? 'text-danger-500' : 'text-muted') . '">';
                                        echo $expiresAt->format('d/m/Y');
                                        echo '</span>';
                                    } else {
                                        echo '<span class="text-muted">Nunca</span>';
                                    }
                                    ?>
                                </td>

                                <td>
                                    <?php if ($userData['role'] === 'master'): ?>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium"><?php echo $userData['credits'] ?? 0; ?></span>
                                            <button class="btn-action btn-primary add-credits" data-user-id="<?php echo $userData['id']; ?>" title="Adicionar Créditos">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                            <a href="user_credit_history.php?id=<?php echo $userData['id']; ?>" class="btn-action btn-secondary" title="Ver Histórico">
                                                <i class="fas fa-history"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($userData['role'] !== 'admin'): ?>
                                    <div class="limits-display">
                                        <span class="limit-badge" title="Logo: <?php echo $userData['logo_changes_today']; ?>/<?php echo $userData['logo_change_limit']; ?>">
                                            <i class="fas fa-image"></i> <?php echo $userData['logo_changes_today']; ?>/<?php echo $userData['logo_change_limit']; ?>
                                        </span>
                                        <span class="limit-badge" title="Logo Filme: <?php echo $userData['movie_logo_changes_today']; ?>/<?php echo $userData['movie_logo_change_limit']; ?>">
                                            <i class="fas fa-film"></i> <?php echo $userData['movie_logo_changes_today']; ?>/<?php echo $userData['movie_logo_change_limit']; ?>
                                        </span>
                                        <span class="limit-badge" title="Fundo: <?php echo $userData['background_changes_today']; ?>/<?php echo $userData['background_change_limit']; ?>">
                                            <i class="fas fa-image"></i> <?php echo $userData['background_changes_today']; ?>/<?php echo $userData['background_change_limit']; ?>
                                        </span>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">Sem limite</span>
                                    <?php endif; ?>
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
                                        <a href="edit_user.php?id=<?php echo $userData['id']; ?>" class="btn-action btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <button class="btn-action btn-primary renew-user-admin" data-user-id="<?php echo $userData['id']; ?>" data-username="<?php echo htmlspecialchars($userData['username']); ?>" title="Renovar">
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
                                        
                                        <?php if ($userData['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn-action btn-danger delete-user" data-user-id="<?php echo $userData['id']; ?>" data-username="<?php echo htmlspecialchars($userData['username']); ?>" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                </table>
        </div>
        
        <div class="mobile-users-grid">
            <?php if (empty($users)): ?>
                <div class="mobile-user-card">
                    <div class="text-center py-4 text-muted">Nenhum usuário encontrado</div>
                </div>
            <?php else: ?>
                <?php foreach ($users as $userData): 
                    $isExpired = $userData['expires_at'] && strtotime($userData['expires_at']) < time();
                ?>
                    <div class="mobile-user-card" data-user-id="<?php echo $userData['id']; ?>">
                        <div class="mobile-card-header">
                            <div class="user-avatar-small">
                                <?php echo strtoupper(substr($userData['username'], 0, 2)); ?>
                            </div>
                            <div class="mobile-card-info">
                                <h3><?php echo htmlspecialchars($userData['username']); ?></h3>
                                <p><?php echo htmlspecialchars($userData['email'] ?? 'Sem email'); ?></p>
                            </div>
                            <div class="mobile-card-id">
                                <span class="mobile-detail-label">ID</span>
                                <span class="mobile-detail-value">#<?php echo $userData['id']; ?></span>
                            </div>
                        </div>
                        
                        <div class="mobile-card-details">
                            <div class="mobile-detail-item">
                                <span class="mobile-detail-label">Função</span>
                                <span class="role-badge role-<?php echo $userData['role']; ?>">
                                    <?php 
                                    switch ($userData['role']) {
                                        case 'admin': echo 'Administrador'; break;
                                        case 'master': echo 'Master'; break;
                                        default: echo 'Usuário'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="mobile-detail-item">
                                <span class="mobile-detail-label">Status</span>
                                <?php if ($isExpired): ?>
                                    <span class="status-badge status-expired">Expirado</span>
                                <?php else: ?>
                                    <span class="status-badge status-<?php echo $userData['status']; ?>">
                                        <?php echo $userData['status'] === 'active' ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mobile-detail-item">
                                <span class="mobile-detail-label">Expira em</span>
                                <span class="mobile-detail-value">
                                    <?php 
                                    if ($userData['expires_at']) {
                                        $expiresAt = new DateTime($userData['expires_at']);
                                        $now = new DateTime();
                                        $isExpiredCheck = $expiresAt < $now;
                                        echo '<span class="' . ($isExpiredCheck ? 'text-danger-500' : 'text-muted') . '">';
                                        echo $expiresAt->format('d/m/Y');
                                        echo '</span>';
                                    } else {
                                        echo '<span class="text-muted">Nunca</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="mobile-detail-item">
                                <span class="mobile-detail-label">Último Login</span>
                                <span class="mobile-detail-value">
                                    <?php 
                                    if ($userData['last_login']) {
                                        $lastLogin = new DateTime($userData['last_login']);
                                        echo $lastLogin->format('d/m/Y H:i');
                                    } else {
                                        echo '<span class="text-muted">Nunca</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($userData['role'] === 'master'): ?>
                                <div class="mobile-credits-section">
                                    <span class="mobile-detail-label">Créditos:</span>
                                    <span class="font-medium"><?php echo $userData['credits']; ?></span>
                                    <button class="btn-action btn-primary add-credits" data-user-id="<?php echo $userData['id']; ?>" title="Adicionar Créditos">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                    <a href="user_credit_history.php?id=<?php echo $userData['id']; ?>" class="btn-action btn-secondary" title="Ver Histórico">
                                        <i class="fas fa-history"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($userData['role'] !== 'admin'): ?>
                            <div class="mobile-detail-item">
                                <span class="mobile-detail-label">Limites de Imagens</span>
                                <div class="mobile-limits-display">
                                    <span class="limit-badge" title="Logo: <?php echo $userData['logo_changes_today']; ?>/<?php echo $userData['logo_change_limit']; ?>">
                                        <i class="fas fa-image"></i> <?php echo $userData['logo_changes_today']; ?>/<?php echo $userData['logo_change_limit']; ?>
                                    </span>
                                    <span class="limit-badge" title="Logo Filme: <?php echo $userData['movie_logo_changes_today']; ?>/<?php echo $userData['movie_logo_change_limit']; ?>">
                                        <i class="fas fa-film"></i> <?php echo $userData['movie_logo_changes_today']; ?>/<?php echo $userData['movie_logo_change_limit']; ?>
                                    </span>
                                    <span class="limit-badge" title="Fundo: <?php echo $userData['background_changes_today']; ?>/<?php echo $userData['background_change_limit']; ?>">
                                        <i class="fas fa-image"></i> <?php echo $userData['background_changes_today']; ?>/<?php echo $userData['background_change_limit']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mobile-card-actions">
                            <a href="edit_user.php?id=<?php echo $userData['id']; ?>" class="mobile-action-btn mobile-action-secondary">
                                <i class="fas fa-edit"></i>
                                Editar
                            </a>
                            
                            <button class="mobile-action-btn mobile-action-primary renew-user-admin" data-user-id="<?php echo $userData['id']; ?>" data-username="<?php echo htmlspecialchars($userData['username']); ?>">
                                <i class="fas fa-sync-alt"></i>
                                Renovar
                            </button>
                            
                            <?php if ($userData['status'] === 'active'): ?>
                                <button class="mobile-action-btn mobile-action-warning toggle-status" data-user-id="<?php echo $userData['id']; ?>" data-status="inactive">
                                    <i class="fas fa-user-times"></i>
                                    Desativar
                                </button>
                            <?php else: ?>
                                <button class="mobile-action-btn mobile-action-success toggle-status" data-user-id="<?php echo $userData['id']; ?>" data-status="active">
                                    <i class="fas fa-user-check"></i>
                                    Ativar
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($userData['id'] != $_SESSION['user_id']): ?>
                                <button class="mobile-action-btn mobile-action-danger delete-user" data-user-id="<?php echo $userData['id']; ?>" data-username="<?php echo htmlspecialchars($userData['username']); ?>">
                                    <i class="fas fa-trash"></i>
                                    Excluir
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Criar usuário de teste
    document.getElementById('createTrialUserBtn').addEventListener('click', function() {
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
                
                fetch('user_management.php', {
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

    // Toggle Status
    document.querySelectorAll('.toggle-status').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const newStatus = this.getAttribute('data-status');
            const statusText = newStatus === 'active' ? 'ativar' : 'desativar';
            
            Swal.fire({
                title: 'Confirmar Ação',
                text: `Tem certeza que deseja ${statusText} este usuário?`,
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
    
    // Add Credits
    document.querySelectorAll('.add-credits').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            
            Swal.fire({
                title: 'Adicionar Créditos',
                text: 'Quantos créditos deseja adicionar?',
                input: 'number',
                inputAttributes: {
                    min: 1,
                    step: 1
                },
                inputValue: 1,
                showCancelButton: true,
                confirmButtonText: 'Adicionar',
                cancelButtonText: 'Cancelar',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
                inputValidator: (value) => {
                    if (!value || value == 0) {
                        return 'Você precisa adicionar ou remover pelo menos 1 crédito!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Pedir descrição
                    Swal.fire({
                        title: 'Descrição',
                        text: 'Informe uma descrição para esta adição de créditos:',
                        input: 'text',
                        inputPlaceholder: 'Descrição (opcional)',
                        showCancelButton: true,
                        confirmButtonText: 'Confirmar',
                        cancelButtonText: 'Cancelar',
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                    }).then((descResult) => {
                        if (descResult.isConfirmed) {
                            addCredits(userId, result.value, descResult.value);
                        }
                    });
                }
            });
        });
    });
    
    // Renew User (Admin)
    document.querySelectorAll('.renew-user-admin').forEach(button => {
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
                            adminRenewUser(userId, username, months);
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
    
    // Trial Days Settings
    document.getElementById('saveTrialDaysBtn').addEventListener('click', function() {
        const trialDays = document.getElementById('trial_days').value;
        
        if (trialDays < 1 || trialDays > 30) {
            Swal.fire({
                title: 'Valor Inválido',
                text: 'O número de dias deve estar entre 1 e 30',
                icon: 'error',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            });
            return;
        }
        
        fetch('user_management.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_trial_days&trial_days=${trialDays}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Sucesso!',
                    text: 'Configuração de dias de teste salva com sucesso',
                    icon: 'success',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
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
    });
    
    // Update Image Limits
    document.getElementById('updateLimitsBtn').addEventListener('click', function() {
        const logoLimit = document.getElementById('global_logo_limit').value;
        const movieLogoLimit = document.getElementById('global_movie_logo_limit').value;
        const backgroundLimit = document.getElementById('global_background_limit').value;
        
        if (logoLimit < 0 || movieLogoLimit < 0 || backgroundLimit < 0) {
            Swal.fire({
                title: 'Erro!',
                text: 'Os limites não podem ser negativos',
                icon: 'error',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            });
            return;
        }
        
        Swal.fire({
            title: 'Confirmar Ação',
            text: `Deseja aplicar estes limites para todos os usuários e masters?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, aplicar',
            cancelButtonText: 'Cancelar',
            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Processando...',
                    text: 'Atualizando limites para todos os usuários',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                });
                
                fetch('user_management.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_image_limits&logo_limit=${logoLimit}&movie_logo_limit=${movieLogoLimit}&background_limit=${backgroundLimit}`
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
    });
    
    // Reset Image Counts
    document.getElementById('resetCountsBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Confirmar Ação',
            text: `Deseja resetar todos os contadores diários de troca de imagens?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, resetar',
            cancelButtonText: 'Cancelar',
            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Processando...',
                    text: 'Resetando contadores para todos os usuários',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                });
                
                fetch('user_management.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=reset_image_counts'
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
    });

    // Funções auxiliares para as ações
    function changeUserStatus(userId, status) {
        fetch('user_management.php', {
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
        fetch('user_management.php', {
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
    
    function addCredits(userId, credits, description = '') {
        fetch('user_management.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add_credits&user_id=${userId}&credits=${credits}&description=${encodeURIComponent(description)}`
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
    
    function adminRenewUser(userId, username, months) {
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
        fetch('admin_renew_user_ajax.php', {
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
    
    // Função para alternar a visibilidade da senha
    window.togglePasswordVisibility = function(inputId) {
        const input = document.getElementById(inputId);
        const button = input.nextElementSibling;
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }
});
</script>


<?php include "includes/footer.php"; ?>