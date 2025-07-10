<?php
/**
 * Script para atualizar os limites de troca de imagens para todos os usuários
 * Pode ser executado manualmente ou via interface de administração
 */

session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["role"] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'classes/User.php';

$user = new User();
$message = '';
$messageType = '';

// Buscar limites atuais do admin (ID 1)
$adminUser = $user->getUserById(1);
$currentLimits = [
    'logo' => $adminUser ? $adminUser['logo_change_limit'] : 3,
    'movie_logo' => $adminUser ? $adminUser['movie_logo_change_limit'] : 3,
    'background' => $adminUser ? $adminUser['background_change_limit'] : 3
];

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logoLimit = isset($_POST['logo_limit']) ? max(0, intval($_POST['logo_limit'])) : 3;
    $movieLogoLimit = isset($_POST['movie_logo_limit']) ? max(0, intval($_POST['movie_logo_limit'])) : 3;
    $backgroundLimit = isset($_POST['background_limit']) ? max(0, intval($_POST['background_limit'])) : 3;
    $applyToAll = isset($_POST['apply_to_all']) && $_POST['apply_to_all'] === 'yes';
    
    // Atualizar limites do admin (ID 1)
    $result = $user->updateImageChangeLimits(1, $logoLimit, $movieLogoLimit, $backgroundLimit);
    
    if ($result['success']) {
        $message = "Limites do administrador atualizados com sucesso.";
        $messageType = "success";
        
        // Se solicitado, aplicar a todos os usuários
        if ($applyToAll) {
            $allResult = $user->updateAllImageChangeLimits($logoLimit, $movieLogoLimit, $backgroundLimit);
            
            if ($allResult['success']) {
                $message .= " Limites aplicados a todos os usuários ({$allResult['affected_rows']} usuários afetados).";
            } else {
                $message .= " Porém houve um erro ao aplicar a todos os usuários: {$allResult['message']}";
                $messageType = "warning";
            }
        }
        
        // Atualizar limites atuais para exibição
        $currentLimits = [
            'logo' => $logoLimit,
            'movie_logo' => $movieLogoLimit,
            'background' => $backgroundLimit
        ];
    } else {
        $message = "Erro ao atualizar limites: {$result['message']}";
        $messageType = "error";
    }
}

$pageTitle = "Configurar Limites de Imagens";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-sliders-h text-primary-500 mr-3"></i>
        Configurar Limites de Troca de Imagens
    </h1>
    <p class="page-subtitle">Defina os limites padrão para todos os usuários do sistema</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Formulário de Configuração -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Limites Padrão</h3>
                <p class="card-subtitle">Configure os limites diários de troca de imagens</p>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> mb-6">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="form-group">
                            <label for="logo_limit" class="form-label">
                                <i class="fas fa-image mr-2"></i>
                                Limite de Logos
                            </label>
                            <input type="number" id="logo_limit" name="logo_limit" class="form-input" 
                                   value="<?php echo $currentLimits['logo']; ?>" min="0" required>
                            <p class="text-xs text-muted mt-1">Trocas de logo permitidas por dia</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="movie_logo_limit" class="form-label">
                                <i class="fas fa-film mr-2"></i>
                                Limite de Logos de Filmes
                            </label>
                            <input type="number" id="movie_logo_limit" name="movie_logo_limit" class="form-input" 
                                   value="<?php echo $currentLimits['movie_logo']; ?>" min="0" required>
                            <p class="text-xs text-muted mt-1">Trocas de logo de filmes permitidas por dia</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="background_limit" class="form-label">
                                <i class="fas fa-image mr-2"></i>
                                Limite de Fundos
                            </label>
                            <input type="number" id="background_limit" name="background_limit" class="form-input" 
                                   value="<?php echo $currentLimits['background']; ?>" min="0" required>
                            <p class="text-xs text-muted mt-1">Trocas de fundo permitidas por dia</p>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="apply_to_all" name="apply_to_all" value="yes" class="form-checkbox">
                            <label for="apply_to_all" class="ml-2 font-medium">
                                Aplicar estes limites a todos os usuários existentes
                            </label>
                        </div>
                        <p class="text-xs text-muted mt-1">
                            Se marcado, estes limites serão aplicados a todos os usuários. Caso contrário, apenas novos usuários receberão estes limites.
                        </p>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <p class="font-medium">Informações Importantes</p>
                            <p class="text-sm mt-1">
                                Estes limites serão aplicados automaticamente a todos os novos usuários criados no sistema.
                                Administradores não têm limites de troca de imagens.
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Salvar Configurações
                        </button>
                        
                        <a href="reset_image_limits.php" class="btn btn-warning" onclick="return confirm('Tem certeza que deseja resetar todos os contadores de troca de imagens?');">
                            <i class="fas fa-redo-alt"></i>
                            Resetar Contadores
                        </a>
                        
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Voltar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Painel de Informações -->
    <div class="space-y-6">
        <!-- Explicação -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle text-primary-500 mr-2"></i>
                    Como Funciona
                </h3>
            </div>
            <div class="card-body">
                <div class="space-y-4 text-sm">
                    <p>
                        O sistema de limites de troca de imagens controla quantas vezes um usuário pode alterar seus logos e fundos por dia.
                    </p>
                    
                    <div class="feature-item">
                        <i class="fas fa-check-circle text-success-500"></i>
                        <p>Os contadores são resetados automaticamente à meia-noite</p>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-check-circle text-success-500"></i>
                        <p>Administradores não têm limites de troca</p>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-check-circle text-success-500"></i>
                        <p>Cada tipo de imagem tem seu próprio limite</p>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-check-circle text-success-500"></i>
                        <p>Os limites são aplicados automaticamente a novos usuários</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dicas -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">💡 Dicas</h3>
            </div>
            <div class="card-body">
                <div class="space-y-3 text-sm">
                    <div class="tip-item">
                        <i class="fas fa-lightbulb text-warning-500"></i>
                        <p>Use limites maiores para usuários premium</p>
                    </div>
                    <div class="tip-item">
                        <i class="fas fa-lightbulb text-warning-500"></i>
                        <p>Você pode editar limites individuais na página de cada usuário</p>
                    </div>
                    <div class="tip-item">
                        <i class="fas fa-lightbulb text-warning-500"></i>
                        <p>Defina como 0 para desabilitar trocas de um tipo específico</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .alert {
        padding: 1rem;
        border-radius: 12px;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .alert-success {
        background: var(--success-50);
        color: var(--success-600);
        border: 1px solid rgba(34, 197, 94, 0.2);
    }
    
    .alert-error {
        background: var(--danger-50);
        color: var(--danger-600);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .alert-warning {
        background: var(--warning-50);
        color: var(--warning-600);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    
    .alert-info {
        background: var(--primary-50);
        color: var(--primary-600);
        border: 1px solid rgba(59, 130, 246, 0.2);
    }
    
    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }
    
    .feature-item, .tip-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .feature-item i, .tip-item i {
        margin-top: 0.125rem;
        flex-shrink: 0;
    }
    
    .form-checkbox {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 0.25rem;
        border: 2px solid var(--border-color);
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-color: var(--bg-primary);
        cursor: pointer;
        position: relative;
        transition: var(--transition);
    }
    
    .form-checkbox:checked {
        background-color: var(--primary-500);
        border-color: var(--primary-500);
    }
    
    .form-checkbox:checked::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(45deg);
        width: 0.25rem;
        height: 0.5rem;
        border: solid white;
        border-width: 0 2px 2px 0;
    }
    
    .form-checkbox:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
    }
    
    .ml-2 {
        margin-left: 0.5rem;
    }
    
    .mt-1 {
        margin-top: 0.25rem;
    }
    
    .mt-4 {
        margin-top: 1rem;
    }
    
    .mb-6 {
        margin-bottom: 1.5rem;
    }
    
    .mr-2 {
        margin-right: 0.5rem;
    }
    
    .space-y-3 > * + * {
        margin-top: 0.75rem;
    }
    
    .space-y-4 > * + * {
        margin-top: 1rem;
    }
    
    .space-y-6 > * + * {
        margin-top: 1.5rem;
    }
    
    /* Dark theme adjustments */
    [data-theme="dark"] .alert-success {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success-400);
    }
    
    [data-theme="dark"] .alert-error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-400);
    }
    
    [data-theme="dark"] .alert-warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-400);
    }
    
    [data-theme="dark"] .alert-info {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-400);
    }
</style>

<?php include "includes/footer.php"; ?>