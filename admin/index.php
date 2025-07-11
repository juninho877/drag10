<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'classes/BannerStats.php';
require_once 'classes/AdminSettings.php';

$bannerStats = new BannerStats();
$adminSettings = new AdminSettings();

// Obter estatísticas do usuário atual
$userId = $_SESSION['user_id'];
$userStats = $bannerStats->getUserBannerStats($userId);

// Obter estatísticas globais (apenas para admin)
$globalStats = null;
if ($_SESSION["role"] === 'admin') {
    $globalStats = $bannerStats->getGlobalBannerStats();
}

// Obter banners recentes do usuário
$recentBanners = $bannerStats->getRecentBanners($userId, 5);

// Carregar configurações do popup
$popupEnabled = $adminSettings->getSetting('popup_enabled', '0') === '1';
$popupMessage = $adminSettings->getSetting('popup_message', '');
$popupTitle = $adminSettings->getSetting('popup_title', 'Novidades & Atualizações');
$popupButtonText = $adminSettings->getSetting('popup_button_text', '');
$popupButtonUrl = $adminSettings->getSetting('popup_button_url', '');

$pageTitle = "Dashboard";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-tachometer-alt text-primary-500 mr-3"></i>
        Dashboard
    </h1>
    <p class="page-subtitle">Bem-vindo, <?php echo htmlspecialchars($_SESSION["usuario"]); ?>!</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Banners Hoje</p>
                    <p class="text-2xl font-bold text-primary"><?php echo $userStats['today_banners']; ?></p>
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
                    <p class="text-sm font-medium text-muted">Banners Este Mês</p>
                    <p class="text-2xl font-bold text-success-500"><?php echo $userStats['month_banners']; ?></p>
                </div>
                <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-check text-success-500"></i>
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

<!-- Main Content -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Quick Access -->
    <div class="md:col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Acesso Rápido</h3>
                <p class="card-subtitle">Escolha o tipo de banner que deseja criar</p>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="futbanner.php" class="quick-access-card">
                        <div class="quick-access-icon bg-warning-50">
                            <i class="fas fa-futbol text-warning-500"></i>
                        </div>
                        <div class="quick-access-content">
                            <h4>Banner de Futebol</h4>
                            <p>Crie banners com os jogos do dia</p>
                        </div>
                        <div class="quick-access-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                    
                    <a href="painel.php" class="quick-access-card">
                        <div class="quick-access-icon bg-info-50">
                            <i class="fas fa-film text-info-500"></i>
                        </div>
                        <div class="quick-access-content">
                            <h4>Banner de Filmes/Séries</h4>
                            <p>Crie banners de filmes e séries</p>
                        </div>
                        <div class="quick-access-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                    
                    <a href="jogos_hoje.php" class="quick-access-card">
                        <div class="quick-access-icon bg-success-50">
                            <i class="fas fa-calendar-day text-success-500"></i>
                        </div>
                        <div class="quick-access-content">
                            <h4>Jogos de Hoje</h4>
                            <p>Veja todos os jogos disponíveis</p>
                        </div>
                        <div class="quick-access-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                    
                    <a href="telegram.php" class="quick-access-card">
                        <div class="quick-access-icon bg-primary-50">
                            <i class="fab fa-telegram-plane text-primary-500"></i>
                        </div>
                        <div class="quick-access-content">
                            <h4>Configurar Telegram</h4>
                            <p>Envie banners automaticamente</p>
                        </div>
                        <div class="quick-access-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Banners -->
        <div class="card mt-6">
            <div class="card-header">
                <h3 class="card-title">Banners Recentes</h3>
                <p class="card-subtitle">Seus últimos banners gerados</p>
            </div>
            <div class="card-body">
                <?php if (empty($recentBanners)): ?>
                    <div class="empty-state">
                        <i class="fas fa-images text-muted"></i>
                        <p>Você ainda não gerou nenhum banner</p>
                        <a href="futbanner.php" class="btn btn-primary mt-4">
                            <i class="fas fa-plus"></i>
                            Gerar Meu Primeiro Banner
                        </a>
                    </div>
                <?php else: ?>
                    <div class="recent-banners">
                        <?php foreach ($recentBanners as $banner): ?>
                            <div class="recent-banner-item">
                                <div class="banner-type-icon">
                                    <i class="fas fa-<?php echo $banner['banner_type'] === 'football' ? 'futbol' : 'film'; ?>"></i>
                                </div>
                                <div class="banner-info">
                                    <h4><?php echo htmlspecialchars($banner['content_name'] ?? ($banner['banner_type'] === 'football' ? 'Banner de Futebol' : 'Banner de Filme/Série')); ?></h4>
                                    <p>
                                        <span class="banner-theme">Tema: <?php echo htmlspecialchars($banner['banner_theme'] ?? 'Padrão'); ?></span>
                                        <span class="banner-date"><?php echo date('d/m/Y H:i', strtotime($banner['generated_at'])); ?></span>
                                    </p>
                                </div>
                                <div class="banner-type-badge <?php echo $banner['banner_type'] === 'football' ? 'football' : 'movie'; ?>">
                                    <?php echo $banner['banner_type'] === 'football' ? 'Futebol' : 'Filme/Série'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- User Profile -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Seu Perfil</h3>
            </div>
            <div class="card-body">
                <div class="flex items-center gap-3 mb-4">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION["usuario"], 0, 2)); ?>
                    </div>
                    <div>
                        <h4 class="font-semibold"><?php echo htmlspecialchars($_SESSION["usuario"]); ?></h4>
                        <p class="text-sm text-muted">
                            <?php 
                            if ($_SESSION["role"] === 'admin') {
                                echo 'Administrador';
                            } elseif ($_SESSION["role"] === 'master') {
                                echo 'Master';
                            } else {
                                echo 'Usuário';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <div class="user-actions">
                    <a href="setting.php" class="btn btn-outline w-full">
                        <i class="fas fa-cog"></i>
                        Configurações da Conta
                    </a>
                    <a href="logout.php" class="btn btn-danger w-full mt-2">
                        <i class="fas fa-sign-out-alt"></i>
                        Sair
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Tips -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dicas Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="tips-list">
                    <div class="tip-item">
                        <div class="tip-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="tip-content">
                            <h4>Personalize seus Logos</h4>
                            <p>Adicione seus próprios logos para personalizar seus banners</p>
                            <a href="logo.php" class="tip-link">Configurar Logos</a>
                        </div>
                    </div>
                    
                    <div class="tip-item">
                        <div class="tip-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="tip-content">
                            <h4>Envio Automático</h4>
                            <p>Configure o Telegram para receber banners automaticamente</p>
                            <a href="telegram.php" class="tip-link">Configurar Telegram</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($_SESSION["role"] === 'admin'): ?>
        <!-- Admin Stats -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Estatísticas do Sistema</h3>
            </div>
            <div class="card-body">
                <?php if ($globalStats): ?>
                <div class="admin-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total de Banners</span>
                        <span class="stat-value"><?php echo $globalStats['total_banners']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Banners Hoje</span>
                        <span class="stat-value"><?php echo $globalStats['today_banners']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Usuários Ativos</span>
                        <span class="stat-value"><?php echo $globalStats['active_users']; ?></span>
                    </div>
                </div>
                
                <a href="user_management.php" class="btn btn-primary w-full mt-4">
                    <i class="fas fa-users"></i>
                    Gerenciar Usuários
                </a>
                <?php else: ?>
                <div class="empty-state">
                    <p>Estatísticas não disponíveis</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .quick-access-card {
        display: flex;
        align-items: center;
        padding: 1rem;
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        transition: var(--transition);
        text-decoration: none;
        color: var(--text-primary);
        gap: 1rem;
    }
    
    .quick-access-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        background: var(--bg-tertiary);
    }
    
    .quick-access-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--border-radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    
    .quick-access-content {
        flex: 1;
    }
    
    .quick-access-content h4 {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .quick-access-content p {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }
    
    .quick-access-arrow {
        color: var(--text-muted);
        transition: var(--transition);
    }
    
    .quick-access-card:hover .quick-access-arrow {
        transform: translateX(4px);
        color: var(--primary-500);
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
    
    .recent-banners {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .recent-banner-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        gap: 1rem;
    }
    
    .banner-type-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--bg-tertiary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
    }
    
    .banner-info {
        flex: 1;
    }
    
    .banner-info h4 {
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .banner-info p {
        font-size: 0.75rem;
        color: var(--text-muted);
        display: flex;
        gap: 1rem;
    }
    
    .banner-type-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-weight: 500;
    }
    
    .banner-type-badge.football {
        background: var(--warning-50);
        color: var(--warning-600);
    }
    
    .banner-type-badge.movie {
        background: var(--info-50);
        color: var(--info-600);
    }
    
    .user-avatar {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .btn-outline {
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .btn-outline:hover {
        background: var(--bg-tertiary);
    }
    
    .tips-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .tip-item {
        display: flex;
        gap: 1rem;
    }
    
    .tip-icon {
        width: 32px;
        height: 32px;
        background: var(--primary-50);
        color: var(--primary-500);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .tip-content h4 {
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .tip-content p {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }
    
    .tip-link {
        font-size: 0.75rem;
        color: var(--primary-500);
        text-decoration: none;
        font-weight: 500;
    }
    
    .tip-link:hover {
        text-decoration: underline;
    }
    
    .admin-stats {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .stat-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem;
        background: var(--bg-tertiary);
        border-radius: var(--border-radius-sm);
    }
    
    .stat-label {
        color: var(--text-secondary);
        font-size: 0.875rem;
    }
    
    .stat-value {
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .mt-2 {
        margin-top: 0.5rem;
    }
    
    .mt-4 {
        margin-top: 1rem;
    }
    
    .mt-6 {
        margin-top: 1.5rem;
    }
    
    .mb-4 {
        margin-bottom: 1rem;
    }
    
    .mb-6 {
        margin-bottom: 1.5rem;
    }
    
    .w-full {
        width: 100%;
    }
    
    /* Dark theme adjustments */
    [data-theme="dark"] .banner-type-badge.football {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-400);
    }
    
    [data-theme="dark"] .banner-type-badge.movie {
        background: rgba(59, 130, 246, 0.1);
        color: var(--info-400);
    }
    
    [data-theme="dark"] .tip-icon {
        background: rgba(59, 130, 246, 0.1);
    }
</style>

<link rel="stylesheet" href="css/popup-styles.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($popupEnabled && !empty($popupMessage)): ?>
    // Mostrar popup de novidades
    Swal.fire({
        title: <?php echo json_encode($popupTitle); ?>,
        html: `
            <?php echo $popupMessage; ?>
            <?php if (!empty($popupButtonText) && !empty($popupButtonUrl)): ?>
            <a href="<?php echo htmlspecialchars($popupButtonUrl); ?>" class="custom-button">
                <?php echo htmlspecialchars($popupButtonText); ?>
            </a>
            <?php endif; ?>
        `,
        showConfirmButton: <?php echo empty($popupButtonText) ? 'true' : 'false'; ?>,
        showCloseButton: true,
        confirmButtonText: 'Fechar',
        position: 'center',
        customClass: {
            container: 'modern-popup',
            popup: 'modern-popup',
            title: 'swal2-title',
            htmlContainer: 'swal2-html-container',
            confirmButton: 'swal2-confirm',
            closeButton: 'swal2-close',
            backdrop: 'modern-popup-backdrop'
        },
        buttonsStyling: false,
    });
    <?php endif; ?>
});
</script>

<?php include "includes/footer.php"; ?>