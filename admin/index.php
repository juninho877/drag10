<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'classes/User.php';
require_once 'classes/BannerStats.php';
require_once 'classes/AdminSettings.php';

// Inicializar variáveis para o popup
$popupEnabled = false;
$popupMessage = '';
$popupButtonText = '';
$popupButtonUrl = '';
$showPopup = false;

// Verificar se o usuário acabou de fazer login
if (isset($_SESSION["just_logged_in"]) && $_SESSION["just_logged_in"]) {
    // Carregar configurações do popup apenas se o usuário acabou de fazer login
    if ($_SESSION["role"] === 'admin') {
        $adminSettings = new AdminSettings();
        $popupEnabled = $adminSettings->getSetting('popup_enabled', '0') === '1';
        $popupMessage = $adminSettings->getSetting('popup_message', '');
        $popupButtonText = $adminSettings->getSetting('popup_button_text', '');
        $popupButtonUrl = $adminSettings->getSetting('popup_button_url', '');
    }
    
    // Determinar se o popup deve ser exibido
    $showPopup = $popupEnabled && !empty($popupMessage);
    
    // Limpar a flag de sessão para que o popup não seja exibido novamente
    unset($_SESSION["just_logged_in"]);
}

// Obter estatísticas do usuário
$userId = $_SESSION['user_id'];
$bannerStats = new BannerStats();
$userStats = $bannerStats->getUserBannerStats($userId);

// Obter banners recentes
$recentBanners = $bannerStats->getRecentBanners($userId);

// Obter estatísticas globais (apenas para admin)
$globalStats = null;
if ($_SESSION["role"] === 'admin') {
    $globalStats = $bannerStats->getGlobalBannerStats();
}

// Obter dados do usuário
$user = new User();
$userData = $user->getUserById($userId);

// Verificar se a conta está prestes a expirar
$expiryWarning = false;
$daysRemaining = 0;
if ($userData && !empty($userData['expires_at'])) {
    $expiryDate = new DateTime($userData['expires_at']);
    $today = new DateTime();
    $daysRemaining = $today->diff($expiryDate)->days;
    $expiryWarning = $daysRemaining <= 5 && $expiryDate > $today;
}

$pageTitle = "Página Inicial";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-tachometer-alt text-primary-500 mr-3"></i>
        Dashboard
    </h1>
    <p class="page-subtitle">Bem-vindo, <?php echo htmlspecialchars($_SESSION["usuario"]); ?>!</p>
</div>

<?php if ($expiryWarning): ?>
<div class="alert alert-warning mb-6">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <p class="font-medium">Sua assinatura está prestes a expirar</p>
        <p class="text-sm mt-1">
            Restam apenas <?php echo $daysRemaining; ?> dias de acesso. 
            <a href="payment.php" class="text-warning-700 hover:underline">Renove agora</a> para continuar utilizando o sistema.
        </p>
    </div>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Total de Banners</p>
                    <p class="text-2xl font-bold text-primary"><?php echo $userStats['total_banners']; ?></p>
                </div>
                <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-images text-primary-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Banners Hoje</p>
                    <p class="text-2xl font-bold text-success-500"><?php echo $userStats['today_banners']; ?></p>
                </div>
                <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-day text-success-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Banners de Futebol</p>
                    <p class="text-2xl font-bold text-warning-500"><?php echo $userStats['football_banners']; ?></p>
                </div>
                <div class="w-12 h-12 bg-warning-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-futbol text-warning-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Banners de Filmes</p>
                    <p class="text-2xl font-bold text-info-500"><?php echo $userStats['movie_banners']; ?></p>
                </div>
                <div class="w-12 h-12 bg-info-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-film text-info-500"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Quick Access -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Acesso Rápido</h3>
            <p class="card-subtitle">Atalhos para as principais funcionalidades</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
                <a href="futbanner.php" class="quick-access-card">
                    <div class="quick-access-icon bg-warning-50 text-warning-500">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <div class="quick-access-text">
                        <h4>Banner Futebol</h4>
                        <p>Gere banners com jogos do dia</p>
                    </div>
                </a>
                
                <a href="painel.php" class="quick-access-card">
                    <div class="quick-access-icon bg-info-50 text-info-500">
                        <i class="fas fa-film"></i>
                    </div>
                    <div class="quick-access-text">
                        <h4>Banner Filmes</h4>
                        <p>Crie banners de filmes e séries</p>
                    </div>
                </a>
                
                <a href="logo.php" class="quick-access-card">
                    <div class="quick-access-icon bg-primary-50 text-primary-500">
                        <i class="fas fa-image"></i>
                    </div>
                    <div class="quick-access-text">
                        <h4>Gerenciar Logos</h4>
                        <p>Configure seus logos personalizados</p>
                    </div>
                </a>
                
                <a href="background.php" class="quick-access-card">
                    <div class="quick-access-icon bg-success-50 text-success-500">
                        <i class="fas fa-photo-video"></i>
                    </div>
                    <div class="quick-access-text">
                        <h4>Gerenciar Fundos</h4>
                        <p>Configure seus fundos personalizados</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Banners -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Banners Recentes</h3>
            <p class="card-subtitle">Últimos banners gerados</p>
        </div>
        <div class="card-body">
            <?php if (empty($recentBanners)): ?>
                <div class="empty-state">
                    <i class="fas fa-images text-muted"></i>
                    <p>Nenhum banner gerado recentemente</p>
                    <a href="futbanner.php" class="btn btn-primary btn-sm mt-3">
                        <i class="fas fa-plus"></i>
                        Gerar Primeiro Banner
                    </a>
                </div>
            <?php else: ?>
                <div class="recent-banners-list">
                    <?php foreach ($recentBanners as $banner): ?>
                        <div class="recent-banner-item">
                            <div class="banner-icon">
                                <i class="fas fa-<?php echo $banner['banner_type'] === 'football' ? 'futbol' : 'film'; ?>"></i>
                            </div>
                            <div class="banner-info">
                                <h4><?php echo htmlspecialchars($banner['content_name'] ?? ($banner['banner_type'] === 'football' ? 'Banner de Futebol' : 'Banner de Filme/Série')); ?></h4>
                                <p>
                                    <span class="banner-theme"><?php echo htmlspecialchars($banner['banner_theme'] ?? 'Tema Padrão'); ?></span>
                                    <span class="banner-date"><?php echo date('d/m/Y H:i', strtotime($banner['generated_at'])); ?></span>
                                </p>
                            </div>
                            <div class="banner-type <?php echo $banner['banner_type'] === 'football' ? 'football' : 'movie'; ?>">
                                <?php echo $banner['banner_type'] === 'football' ? 'Futebol' : 'Filme'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Admin Stats (only for admin) -->
<?php if ($_SESSION["role"] === 'admin' && $globalStats): ?>
<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-title">Estatísticas do Sistema</h3>
        <p class="card-subtitle">Visão geral de uso do sistema</p>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="admin-stat-info">
                    <h4>Usuários Ativos</h4>
                    <p class="admin-stat-value"><?php echo $globalStats['active_users']; ?></p>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="admin-stat-info">
                    <h4>Total de Banners</h4>
                    <p class="admin-stat-value"><?php echo $globalStats['total_banners']; ?></p>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="admin-stat-info">
                    <h4>Banners Hoje</h4>
                    <p class="admin-stat-value"><?php echo $globalStats['today_banners']; ?></p>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="admin-stat-info">
                    <h4>Banners este Mês</h4>
                    <p class="admin-stat-value"><?php echo $globalStats['month_banners']; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Master Stats (only for master) -->
<?php if ($_SESSION["role"] === 'master'): ?>
<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-title">Gerenciamento de Usuários</h3>
        <p class="card-subtitle">Informações sobre seus usuários e créditos</p>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php
            // Obter créditos do master
            $masterCredits = $userData['credits'] ?? 0;
            
            // Obter usuários do master
            $masterUsers = $user->getUsersByParentId($userId);
            $totalUsers = count($masterUsers);
            
            // Contar usuários ativos e expirados
            $activeUsers = 0;
            $expiredUsers = 0;
            foreach ($masterUsers as $masterUser) {
                if ($masterUser['status'] === 'active') {
                    if (empty($masterUser['expires_at']) || strtotime($masterUser['expires_at']) >= time()) {
                        $activeUsers++;
                    } else {
                        $expiredUsers++;
                    }
                }
            }
            ?>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="admin-stat-info">
                    <h4>Créditos Disponíveis</h4>
                    <p class="admin-stat-value"><?php echo $masterCredits; ?></p>
                </div>
                <a href="buy_credits.php" class="btn btn-sm btn-primary mt-2 w-full">
                    <i class="fas fa-plus"></i>
                    Comprar Mais
                </a>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="admin-stat-info">
                    <h4>Total de Usuários</h4>
                    <p class="admin-stat-value"><?php echo $totalUsers; ?></p>
                </div>
                <a href="master_users.php" class="btn btn-sm btn-primary mt-2 w-full">
                    <i class="fas fa-user-cog"></i>
                    Gerenciar
                </a>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="admin-stat-info">
                    <h4>Usuários Ativos</h4>
                    <p class="admin-stat-value"><?php echo $activeUsers; ?></p>
                    <?php if ($expiredUsers > 0): ?>
                    <p class="text-xs text-danger-500 mt-1"><?php echo $expiredUsers; ?> expirados</p>
                    <?php endif; ?>
                </div>
                <a href="master_add_user.php" class="btn btn-sm btn-primary mt-2 w-full">
                    <i class="fas fa-user-plus"></i>
                    Adicionar Usuário
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .quick-access-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        transition: var(--transition);
        text-decoration: none;
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }

    .quick-access-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        background: var(--bg-primary);
        border-color: var(--primary-300);
    }

    .quick-access-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .quick-access-text h4 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: var(--text-primary);
    }

    .quick-access-text p {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin: 0;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--text-muted);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .empty-state p {
        margin-bottom: 1rem;
    }

    .recent-banners-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .recent-banner-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
    }

    .banner-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--border-radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: var(--primary-50);
        color: var(--primary-500);
        flex-shrink: 0;
    }

    .banner-info {
        flex: 1;
        min-width: 0;
    }

    .banner-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .banner-info p {
        font-size: 0.75rem;
        color: var(--text-muted);
        display: flex;
        gap: 0.5rem;
    }

    .banner-theme {
        background: var(--bg-tertiary);
        padding: 0.125rem 0.375rem;
        border-radius: 9999px;
    }

    .banner-type {
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        flex-shrink: 0;
    }

    .banner-type.football {
        background: var(--warning-50);
        color: var(--warning-600);
    }

    .banner-type.movie {
        background: var(--info-50);
        color: var(--info-600);
    }

    .admin-stat-card {
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border-color);
    }

    .admin-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: var(--primary-50);
        color: var(--primary-500);
        margin-bottom: 1rem;
    }

    .admin-stat-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }

    .admin-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .alert {
        padding: 1rem;
        border-radius: 12px;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .alert-warning {
        background: var(--warning-50);
        color: var(--warning-600);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    
    .text-warning-700 {
        color: var(--warning-700);
    }
    
    .text-danger-500 {
        color: var(--danger-500);
    }
    
    .mt-1 {
        margin-top: 0.25rem;
    }
    
    .mt-2 {
        margin-top: 0.5rem;
    }
    
    .mt-3 {
        margin-top: 0.75rem;
    }
    
    .mt-6 {
        margin-top: 1.5rem;
    }
    
    .mb-6 {
        margin-bottom: 1.5rem;
    }
    
    .w-full {
        width: 100%;
    }
    
    .text-xs {
        font-size: 0.75rem;
    }
    
    .btn-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
    }
    
    /* Dark theme adjustments */
    [data-theme="dark"] .banner-type.football {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-400);
    }
    
    [data-theme="dark"] .banner-type.movie {
        background: rgba(59, 130, 246, 0.1);
        color: var(--info-400);
    }
    
    [data-theme="dark"] .admin-stat-icon {
        background: rgba(59, 130, 246, 0.1);
    }
    
    [data-theme="dark"] .alert-warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-400);
    }
    
    [data-theme="dark"] .text-warning-700 {
        color: var(--warning-300);
    }
    
    [data-theme="dark"] .text-danger-500 {
        color: var(--danger-400);
    }
</style>

<!-- Popup Modal -->
<?php if ($showPopup): ?>
<link rel="stylesheet" href="css/popup-styles.css">
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Mensagem do Sistema',
        html: `
            <div style="text-align: center;"><?php echo $popupMessage; ?></div>
            <?php if (!empty($popupButtonText) && !empty($popupButtonUrl)): ?>
            <div style="margin-top: 20px;">
                <a href="<?php echo htmlspecialchars($popupButtonUrl); ?>" class="swal2-confirm swal2-styled" style="display: inline-block; margin: 0 auto;">
                    <?php echo htmlspecialchars($popupButtonText); ?>
                </a>
            </div>
            <?php endif; ?>
        `,
        showConfirmButton: <?php echo (empty($popupButtonText) || empty($popupButtonUrl)) ? 'true' : 'false'; ?>,
        confirmButtonText: 'Fechar',
        customClass: {
            container: 'modern-popup',
            popup: 'modern-popup-content',
            title: 'modern-popup-title',
            htmlContainer: 'modern-popup-body',
            confirmButton: 'modern-popup-confirm',
            backdrop: 'modern-popup-backdrop'
        },
        buttonsStyling: false
    });
});
</script>
<?php endif; ?>

<?php include "includes/footer.php"; ?>