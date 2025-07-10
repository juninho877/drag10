<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

// Incluir funções necessárias
require_once 'includes/banner_functions.php';

// Obter dados reais dos jogos
$jogos = obterJogosDeHoje();
$totalJogosHoje = count($jogos);

// Função para determinar o status do jogo
function getGameStatus($jogo) {
    $status = $jogo['status'] ?? '';
    $horario = $jogo['horario'] ?? '';
    
    // Se tem status específico na API, usar ele
    if (!empty($status)) {
        switch (strtoupper($status)) {
            case 'ADIADO':
                return ['text' => 'ADIADO', 'class_suffix' => 'warning', 'icon' => 'fa-pause'];
            case 'CANCELADO':
                return ['text' => 'CANCELADO', 'class_suffix' => 'danger', 'icon' => 'fa-times'];
            case 'FINALIZADO':
                return ['text' => 'FINALIZADO', 'class_suffix' => 'info', 'icon' => 'fa-flag-checkered']; // Usando 'info' para finalizado como no dashboard
            case 'AO_VIVO':
            case 'LIVE':
                return ['text' => 'AO VIVO', 'class_suffix' => 'danger', 'icon' => 'fa-circle live-pulse']; // Adicionado live-pulse
            case 'INTERVALO':
                return ['text' => 'INTERVALO', 'class_suffix' => 'info', 'icon' => 'fa-pause-circle'];
        }
    }
    
    // Se não tem status específico, determinar baseado no horário
    if (!empty($horario)) {
        $agora = new DateTime();
        $horaJogo = DateTime::createFromFormat('H:i', $horario);
        
        if ($horaJogo) {
            $horaJogo->setDate($agora->format('Y'), $agora->format('m'), $agora->format('d'));
            
            $diffMinutos = ($agora->getTimestamp() - $horaJogo->getTimestamp()) / 60;
            
            if ($diffMinutos < -30) {
                return ['text' => 'AGENDADO', 'class_suffix' => 'primary', 'icon' => 'fa-clock'];
            } elseif ($diffMinutos >= -30 && $diffMinutos < 0) {
                return ['text' => 'EM BREVE', 'class_suffix' => 'warning', 'icon' => 'fa-hourglass-half'];
            } elseif ($diffMinutos >= 0 && $diffMinutos < 120) { // Considera "ao vivo" por um período
                return ['text' => 'AO VIVO', 'class_suffix' => 'danger', 'icon' => 'fa-circle live-pulse'];
            } else {
                return ['text' => 'FINALIZADO', 'class_suffix' => 'info', 'icon' => 'fa-flag-checkered'];
            }
        }
    }
    
    return ['text' => 'AGENDADO', 'class_suffix' => 'primary', 'icon' => 'fa-clock'];
}

// Função para encontrar o próximo jogo
function getNextGame($jogos) {
    $agora = new DateTime();
    $proximoJogo = null;
    $menorDiferenca = PHP_INT_MAX;
    
    foreach ($jogos as $jogo) {
        $horario = $jogo['horario'] ?? '';
        $status = $jogo['status'] ?? '';
        
        // Pular jogos adiados, cancelados ou finalizados
        if (in_array(strtoupper($status), ['ADIADO', 'CANCELADO', 'FINALIZADO'])) {
            continue;
        }
        
        if (!empty($horario)) {
            $horaJogo = DateTime::createFromFormat('H:i', $horario);
            if ($horaJogo) {
                $horaJogo->setDate($agora->format('Y'), $agora->format('m'), $agora->format('d'));
                
                // Se o jogo é no futuro
                $diferenca = $horaJogo->getTimestamp() - $agora->getTimestamp();
                if ($diferenca > 0 && $diferenca < $menorDiferenca) {
                    $menorDiferenca = $diferenca;
                    $proximoJogo = $jogo;
                }
            }
        }
    }
    
    return $proximoJogo;
}

$pageTitle = "Jogos de Hoje";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-futbol text-success-500 mr-3"></i>
        Jogos de Hoje
    </h1>
    <p class="page-subtitle">
        <?php if ($totalJogosHoje > 0): ?>
            <?php echo $totalJogosHoje; ?> jogos disponíveis - Atualizado em <span id="last-update-time"><?php echo date('H:i'); ?></span>
        <?php else: ?>
            Nenhum jogo programado para hoje
        <?php endif; ?>
    </p>
</div>

<div class="flex justify-between items-center mb-6">
    <div class="flex gap-3">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Voltar ao Dashboard
        </a>
        <button id="refreshBtn" class="btn btn-secondary" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i>
            Atualizar Jogos
        </button>
    </div>
    <?php if ($totalJogosHoje > 0): ?>
    <a href="futbanner.php" class="btn btn-primary">
        <i class="fas fa-magic"></i>
        Gerar Banners
    </a>
    <?php endif; ?>
</div>

<?php if ($totalJogosHoje > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6"> <div class="stat-card stat-card-primary">
            <div class="stat-card-body">
                <i class="fas fa-futbol stat-card-icon"></i>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Total de Jogos</p>
                        <p class="text-2xl font-bold text-primary"><?php echo $totalJogosHoje; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                        <i class="fas text-primary-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Calcular estatísticas dos jogos
        $ligas = [];
        $jogosComCanais = 0;
        $jogosAoVivo = 0;
        $proximoJogo = getNextGame($jogos);

        foreach ($jogos as $jogo) {
            $liga = $jogo['competicao'] ?? 'Liga';
            $ligas[$liga] = ($ligas[$liga] ?? 0) + 1;
            
            if (!empty($jogo['canais'])) {
                $jogosComCanais++;
            }
            
            $status = getGameStatus($jogo);
            if ($status['class_suffix'] === 'danger' && strpos($status['icon'], 'live-pulse') !== false) {
                $jogosAoVivo++;
            }
        }
        
        $ligasCount = count($ligas);
        $ligaPrincipal = $ligasCount > 0 ? array_keys($ligas, max($ligas))[0] : 'N/A';
        ?>

        <div class="stat-card stat-card-success">
            <div class="stat-card-body">
                <i class="fas fa-trophy stat-card-icon"></i>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Ligas/Competições</p>
                        <p class="text-2xl font-bold text-success-500"><?php echo $ligasCount; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                        <i class="fas text-success-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-success-600 font-medium">
                        Principal: <?php echo htmlspecialchars(substr($ligaPrincipal, 0, 15)); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-danger">
            <div class="stat-card-body">
                <i class="fas fa-circle stat-card-icon live-pulse"></i>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Jogos ao Vivo</p>
                        <p class="text-2xl font-bold text-danger-500"><?php echo $jogosAoVivo; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-danger-50 rounded-lg flex items-center justify-center">
                        <i class="fas text-danger-500 live-pulse"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-danger-600 font-medium">
                        <?php echo $jogosComCanais; ?> com transmissão
                    </span>
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-info">
            <div class="stat-card-body">
                <i class="fas fa-clock stat-card-icon"></i>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Próximo Jogo</p>
                        <p class="text-2xl font-bold text-info-500"><?php echo $proximoJogo ? $proximoJogo['horario'] : '--:--'; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-info-50 rounded-lg flex items-center justify-center">
                        <i class="fas text-info-500"></i>
                    </div>
                </div>
                <?php if ($proximoJogo): ?>
                <div class="mt-2">
                    <span class="text-xs text-info-600 font-medium">
                        <?php echo htmlspecialchars(substr($proximoJogo['time1'] . ' vs ' . $proximoJogo['time2'], 0, 20)); ?>
                    </span>
                </div>
                <?php else: ?>
                <div class="mt-2">
                    <span class="text-xs text-muted font-medium">
                        Nenhum jogo pendente
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Todos os Jogos de Hoje</h3>
            <p class="card-subtitle">Acompanhe o status em tempo real de cada partida</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($jogos as $index => $jogo): 
                    $time1 = $jogo['time1'] ?? 'Time 1';
                    $time2 = $jogo['time2'] ?? 'Time 2';
                    $liga = $jogo['competicao'] ?? 'Liga';
                    $hora = $jogo['horario'] ?? '';
                    $canais = $jogo['canais'] ?? [];
                    $temCanais = !empty($canais);
                    $placar1 = $jogo['placar_time1'] ?? '';
                    $placar2 = $jogo['placar_time2'] ?? '';
                    $temPlacar = !empty($placar1) || !empty($placar2);
                    $status = getGameStatus($jogo); // Usa a função para obter o status e classe
                    $liga_img = $jogo['img_competicao_url'] ?? '';
                ?>
                    <div class="game-card"> <div class="game-card-header"> <div class="flex items-center"> <?php if (!empty($liga_img)): ?>
                                <div class="league-logo mr-1"> <img src="<?php echo htmlspecialchars($liga_img); ?>" alt="<?php echo htmlspecialchars($liga); ?>" loading="lazy">
                                </div>
                                <?php endif; ?>
                                <span class="game-league"><?php echo htmlspecialchars($liga); ?></span> <span class="status-badge status-badge-<?php echo $status['class_suffix']; ?> ml-2"> <i class="fas <?php echo $status['icon']; ?>"></i>
                                    <?php echo $status['text']; ?>
                                </span>
                            </div>
                            <span class="game-time"><?php echo htmlspecialchars($hora); ?></span> </div>
                        
                        <div class="game-teams"> <div class="team"> <?php if (!empty($jogo['img_time1_url'])): ?>
                                    <div class="team-logo">
                                        <img src="<?php echo htmlspecialchars($jogo['img_time1_url']); ?>" alt="<?php echo htmlspecialchars($time1); ?>" loading="lazy">
                                    </div>
                                <?php endif; ?>
                                <span class="team-name"><?php echo htmlspecialchars($time1); ?></span> <?php if ($temPlacar): ?>
                                    <span class="team-score"><?php echo htmlspecialchars($placar1 ?: '0'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="vs"> <?php if ($temPlacar): ?>
                                    <span class="score-separator">×</span>
                                <?php else: ?>
                                    VS
                                <?php endif; ?>
                            </div>
                            
                            <div class="team"> <?php if (!empty($jogo['img_time2_url'])): ?>
                                    <div class="team-logo">
                                        <img src="<?php echo htmlspecialchars($jogo['img_time2_url']); ?>" alt="<?php echo htmlspecialchars($time2); ?>" loading="lazy">
                                    </div>
                                <?php endif; ?>
                                <span class="team-name"><?php echo htmlspecialchars($time2); ?></span>
                                <?php if ($temPlacar): ?>
                                    <span class="team-score"><?php echo htmlspecialchars($placar2 ?: '0'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($temCanais): ?>
                        <div class="game-channels"> <?php 
                            $canaisLimitados = array_slice($canais, 0, 3); // Limitar a 3 canais para o card
                            foreach ($canaisLimitados as $canal): 
                            ?>
                                <span class="channel-badge">
                                    <?php if (!empty($canal['img_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($canal['img_url']); ?>" alt="<?php echo htmlspecialchars($canal['nome'] ?? 'Canal'); ?>" class="channel-logo">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($canal['nome'] ?? 'Canal'); ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($canais) > 3): ?>
                                <span class="channel-badge more">+<?php echo count($canais) - 3; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-12">
            <div class="mb-4">
                <i class="fas fa-futbol text-6xl text-gray-300"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Nenhum jogo disponível</h3>
            <p class="text-muted mb-6">Não há jogos programados para hoje no momento.</p>
            <div class="flex gap-4 justify-center">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Voltar ao Dashboard
                </a>
                <button onclick="location.reload()" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i>
                    Verificar Novamente
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    /* CSS Variables for Theme Management - DO NOVO TEMA */
    :root {
        /* Light Theme */
        --bg-primary: #ffffff;
        --bg-secondary: #f8fafc;
        --bg-tertiary: #f1f5f9;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --text-muted: #94a3b8;
        --border-color: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        
        /* Brand Colors */
        --primary-50: #f0f9ff;
        --primary-100: #e0f2fe;
        --primary-200: #bae6fd;
        --primary-300: #7dd3fc;
        --primary-400: #38bdf8;
        --primary-500: #0ea5e9;
        --primary-600: #0284c7;
        --primary-700: #0369a1;
        --primary-800: #075985;
        --primary-900: #0c4a6e;
        
        /* Status Colors */
        --success-50: #f0fdf4;
        --success-100: #dcfce7;
        --success-200: #bbf7d0;
        --success-300: #86efac;
        --success-400: #4ade80;
        --success-500: #22c55e;
        --success-600: #16a34a;
        --success-700: #15803d;
        --success-800: #166534;
        --success-900: #14532d;
        
        --danger-50: #fef2f2;
        --danger-100: #fee2e2;
        --danger-200: #fecaca;
        --danger-300: #fca5a5;
        --danger-400: #f87171;
        --danger-500: #ef4444;
        --danger-600: #dc2626;
        --danger-700: #b91c1c;
        --danger-800: #991b1b;
        --danger-900: #7f1d1d;
        
        --warning-50: #fffbeb;
        --warning-100: #fef3c7;
        --warning-200: #fde68a;
        --warning-300: #fcd34d;
        --warning-400: #fbbf24;
        --warning-500: #f59e0b;
        --warning-600: #d97706;
        --warning-700: #b45309;
        --warning-800: #92400e;
        --warning-900: #78350f;
        
        --info-50: #eff6ff; /* Mantive a cor info do seu original para não quebrar */
        --info-100: #e0f2fe;
        --info-200: #bae6fd;
        --info-300: #7dd3fc;
        --info-400: #38bdf8;
        --info-500: #3b82f6; /* Mantive a cor info do seu original para não quebrar */
        --info-600: #2563eb; /* Mantive a cor info do seu original para não quebrar */
        --info-700: #0369a1;
        --info-800: #075985;
        --info-900: #0c4a6e;
        
        --indigo-50: #eef2ff;
        --indigo-100: #e0e7ff;
        --indigo-200: #c7d2fe;
        --indigo-300: #a5b4fc;
        --indigo-400: #818cf8;
        --indigo-500: #6366f1;
        --indigo-600: #4f46e5;
        --indigo-700: #4338ca;
        --indigo-800: #3730a3;
        --indigo-900: #312e81;
        
        /* Layout - Ajustado para usar as variáveis de radius e transition */
        --border-radius: 12px;
        --border-radius-sm: 8px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Dark Theme - DO NOVO TEMA */
    [data-theme="dark"] {
        --bg-primary: #0f172a;
        --bg-secondary: #1e293b;
        --bg-tertiary: #334155;
        --text-primary: #f8fafc;
        --text-secondary: #cbd5e1;
        --text-muted: #64748b;
        --border-color: #334155;
        
        /* Dark Theme Primary overrides (ajustes para as cores ficarem harmoniosas no tema escuro) */
        --primary-50: #1a365d;
        --primary-100: #1e4b8f;
        --primary-200: #2563eb;
        --primary-300: #3b82f6;
        --primary-400: #60a5fa;
        --primary-500: #3b82f6;
        --primary-600: #2563eb;
        --primary-700: #1d4ed8;
        --primary-800: #1e40af;
        --primary-900: #1e3a8a;

        /* Dark Theme Info overrides (ajustes para as cores ficarem harmoniosas no tema escuro) */
        --info-50: #0a1f33;
        --info-500: #60a5fa;
        --info-600: #3b82f6;
    }

    /* Reset and Base Styles - DO NOVO TEMA (necessário para as variáveis funcionarem) */
    body {
        background-color: var(--bg-secondary);
        color: var(--text-primary);
        transition: var(--transition);
        /* Font family e line height já devem estar no seu header/body principal */
    }

    /* Cards - DO NOVO TEMA */
    .card {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        overflow: hidden;
    }

    .card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    @media (max-width: 640px) {
        .card-header,
        .card-body {
            padding: 1rem;
        }
    }

    .card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }

    .card-subtitle {
        color: var(--text-secondary);
        font-size: 0.875rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Stats Cards - COMBINADO do novo tema e do seu original */
    .stat-card { /* Nova classe introduzida para o novo design */
        position: relative;
        overflow: hidden;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color); /* Padrão do novo tema */
        transition: all 0.3s ease;
        background: var(--bg-primary); /* Base para todos os stat-cards */
    }

    /* Pseudo-elemento da barra lateral do stat-card */
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: currentColor; /* Usa a cor do stat-card (primary, success, etc.) */
    }

    .stat-card:hover {
        border-color: currentColor; /* Borda da cor do stat-card no hover */
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg); /* Shadow mais forte no hover */
    }

    /* Cores para os stat cards - DO NOVO TEMA */
    .stat-card-primary { color: var(--primary-500); }
    .stat-card-success { color: var(--success-500); }
    .stat-card-warning { color: var(--warning-500); }
    .stat-card-info { color: var(--info-500); }
    .stat-card-indigo { color: var(--indigo-500); }
    .stat-card-danger { color: var(--danger-500); }

    /* Estilos para os ícones e textos dentro dos stat cards */
    .stat-card-body {
        padding: 1.5rem;
        background: var(--bg-primary); /* Fundo padrão do card */
    }
    .stat-card-icon {
        position: absolute;
        top: 1rem;
        right: 1rem;
        opacity: 0.1;
        font-size: 4rem;
    }
    .stat-card-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-primary); /* Usa a cor de texto principal */
    }
    @media (max-width: 768px) {
        .stat-card-value {
            font-size: 1.5rem;
        }
    }
    .stat-card-title {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }
    .stat-card-footer {
        font-size: 0.75rem;
        color: currentColor; /* Usa a cor do stat-card */
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    /* Game Cards - DO NOVO TEMA, substituindo .game-card-detailed */
    .game-card { /* Esta classe é agora a principal para os cards de jogo */
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-sm);
        overflow: hidden;
        transition: var(--transition);
        position: relative;
        padding: 1rem; /* Adicionado padding para corresponder ao novo design */
    }

    .game-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    
    .game-card-header { /* Substitui .game-header-detailed */
        display: flex;
        justify-content: space-between;
        align-items: flex-start; /* Mantido flex-start para alinhamento superior da liga/status */
        margin-bottom: 1rem;
        gap: 0.5rem;
    }
    .league-info { /* Mantido para conter logo/nome/status */
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column; /* Coluna para liga e status */
    }
    .league-logo { /* Estilo da logo da liga */
        width: 24px;
        height: 24px;
        margin-bottom: 0.25rem; /* Adicionado para espaçamento */
        object-fit: contain; /* Para garantir que a imagem não distorça */
    }
    .league-logo img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .game-league { /* Substitui .league-name-detailed */
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--primary-600);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: block;
        margin-bottom: 0.5rem; /* Adicionado espaçamento */
    }
    [data-theme="dark"] .game-league {
        color: var(--primary-400);
    }
    .game-time { /* Substitui .game-time-detailed */
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-primary);
        background: var(--bg-primary);
        padding: 0.375rem 0.75rem;
        border-radius: var(--border-radius-sm);
        white-space: nowrap;
    }

    .game-teams { /* Substitui .game-teams-detailed */
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .team { /* Substitui .team-detailed */
        flex: 1;
        text-align: center;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }
    .team-logo { /* Mantido */
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.25rem;
    }
    .team-logo img { /* Mantido */
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .team-name { /* Substitui .team-name-detailed */
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-primary);
        display: block;
        word-wrap: break-word;
        line-height: 1.2;
    }
    .team-score { /* Mantido */
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-600);
        background: var(--primary-50);
        padding: 0.25rem 0.5rem;
        border-radius: var(--border-radius-sm);
        min-width: 2rem;
        text-align: center;
        margin-top: 0.25rem; /* Adicionado para espaçamento com o nome */
    }
    [data-theme="dark"] .team-score {
        background: rgba(59, 130, 246, 0.1); /* Dark theme para score background */
        color: var(--primary-400);
    }
    .vs { /* Substitui .vs-detailed */
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        background: var(--bg-primary); /* Fundo branco */
        padding: 0.375rem 0.75rem;
        border-radius: 50%;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--border-color);
    }
    .score-separator { /* Mantido */
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-600);
    }
    [data-theme="dark"] .score-separator {
        color: var(--primary-400);
    }
    .game-channels { /* Substitui .channels-info e .channels-list */
        padding-top: 0.75rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem; /* Ajustado para 0.375rem como no dashboard */
    }
    .channel-badge { /* Mantido */
        font-size: 0.625rem;
        font-weight: 500;
        color: var(--success-600);
        background: var(--success-50);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        border: 1px solid var(--success-200);
        display: flex; /* Adicionado para alinhar logo e texto do canal */
        align-items: center;
        gap: 0.25rem;
    }
    .channel-logo { /* Logo dentro do badge de canal */
        width: 16px;
        height: 16px;
        object-fit: contain;
    }
    .channel-badge.more { /* Mantido */
        color: var(--text-muted);
        background: var(--bg-tertiary);
        border-color: var(--border-color);
    }

    /* Status dos jogos - Agora usando as classes status-badge com sufixo */
    .status-badge { /* Nova classe genérica para badges de status */
        font-size: 0.625rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    /* Cores para os status-badges (mapeadas para as suas cores originais) */
    .status-badge-primary { color: var(--primary-600); background: var(--primary-50); border: 1px solid var(--primary-200); }
    .status-badge-success { color: var(--success-600); background: var(--success-50); border: 1px solid var(--success-200); }
    .status-badge-warning { color: var(--warning-600); background: var(--warning-50); border: 1px solid var(--warning-200); }
    .status-badge-danger { color: var(--danger-600); background: var(--danger-50); border: 1px solid var(--danger-200); }
    .status-badge-info { color: var(--info-600); background: var(--info-50); border: 1px solid var(--info-200); }
    .status-badge-indigo { color: var(--indigo-600); background: var(--indigo-50); border: 1px solid var(--indigo-200); }

    /* Dark theme para os status badges */
    [data-theme="dark"] .status-badge-primary { background: rgba(59, 130, 246, 0.1); color: var(--primary-400); border-color: rgba(59, 130, 246, 0.2); }
    [data-theme="dark"] .status-badge-success { background: rgba(34, 197, 94, 0.1); color: var(--success-400); border-color: rgba(34, 197, 94, 0.2); }
    [data-theme="dark"] .status-badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning-400); border-color: rgba(245, 158, 11, 0.2); }
    [data-theme="dark"] .status-badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger-400); border-color: rgba(239, 68, 68, 0.2); }
    [data-theme="dark"] .status-badge-info { background: rgba(59, 130, 246, 0.1); color: var(--info-400); border-color: rgba(59, 130, 246, 0.2); }


    /* Animações - Mantidas do seu original */
    .live-pulse { animation: livePulse 2s infinite; }
    @keyframes livePulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* Utilitários Tailwind-like - Refinado para o novo tema */
    .flex { display: flex; }
    .items-center { align-items: center; }
    .items-start { align-items: flex-start; }
    .justify-between { justify-content: space-between; }
    .justify-center { justify-content: center; }
    .text-center { text-align: center; }

    .text-sm { font-size: 0.875rem; }
    .text-xs { font-size: 0.75rem; }
    .text-xl { font-size: 1.25rem; }
    .text-2xl { font-size: 1.5rem; }
    .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
    .text-6xl { font-size: 3.75rem; line-height: 1; } /* Para o ícone de "Nenhum jogo" */

    .font-medium { font-weight: 500; }
    .font-semibold { font-weight: 600; }
    .font-bold { font-weight: 700; }

    /* Cores de texto */
    .text-primary { color: var(--text-primary); }
    .text-muted { color: var(--text-muted); }
    .text-primary-500 { color: var(--primary-500); }
    .text-primary-600 { color: var(--primary-600); }
    .text-success-500 { color: var(--success-500); }
    .text-success-600 { color: var(--success-600); }
    .text-warning-500 { color: var(--warning-500); }
    .text-warning-600 { color: var(--warning-600); }
    .text-info-500 { color: var(--info-500); }
    .text-info-600 { color: var(--info-600); }
    .text-danger-500 { color: var(--danger-500); }
    .text-danger-600 { color: var(--danger-600); }
    .text-gray-300 { color: #d1d5db; } /* Cor padrão para gray-300 */
    [data-theme="dark"] .text-gray-300 { color: var(--text-muted); } /* Dark theme para gray-300 */

    /* Cores de background para ícones */
    .bg-primary-50 { background-color: var(--primary-50); }
    .bg-success-50 { background-color: var(--success-50); }
    .bg-warning-50 { background-color: var(--warning-50); }
    .bg-info-50 { background-color: var(--info-50); }
    .bg-danger-50 { background-color: var(--danger-50); }

    /* Border Radius */
    .rounded-lg { border-radius: var(--border-radius); }
    .rounded-full { border-radius: 9999px; }

    /* Espaçamentos */
    .mb-2 { margin-bottom: 0.5rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mb-6 { margin-bottom: 1.5rem; }
    .mt-2 { margin-top: 0.5rem; }
    .mt-3 { margin-top: 0.75rem; }
    .mt-4 { margin-top: 1rem; }
    .mt-6 { margin-top: 1.5rem; }
    .mr-1 { margin-right: 0.25rem; }
    .mr-2 { margin-right: 0.5rem; }
    .mr-3 { margin-right: 0.75rem; } /* Do seu original */
    .ml-2 { margin-left: 0.5rem; }
    .py-12 { padding-top: 3rem; padding-bottom: 3rem; }

    /* Media Queries para Grids e Layout */
    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .gap-4 { gap: 1rem; }
    .gap-6 { gap: 1.5rem; }

    @media (min-width: 768px) {
        .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .md\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (min-width: 1280px) {
        .xl\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    
    /* Responsive adjustments for game cards (mantido e adaptado do seu original) */
    @media (max-width: 768px) {
        .game-teams { /* Substitui game-teams-detailed */
            flex-direction: column;
            gap: 0.75rem;
        }
        .vs { /* Substitui vs-detailed */
            order: 2;
            margin: 0.5rem 0;
        }
        .team:first-child { /* Substitui team-detailed */
            order: 1;
        }
        .team:last-child { /* Substitui team-detailed */
            order: 3;
        }
        .team { /* Substitui team-detailed */
            flex-direction: row;
            justify-content: space-between;
            width: 100%;
        }
    }
</style>

<script>
    // Theme Management - Copiado do "novo tema"
    const body = document.body;
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    body.setAttribute('data-theme', savedTheme);

    // Auto-refresh a cada 2 minutos para manter dados atualizados
    setTimeout(() => {
        location.reload();
    }, 120000); // 2 minutos

    // Mostrar horário da última atualização
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('pt-BR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        // Atualizar subtitle com horário atual se não foi definido no PHP
        const updateTimeSpan = document.getElementById('last-update-time');
        if (updateTimeSpan) {
            updateTimeSpan.textContent = timeString;
        }
    });
</script>

<?php include "includes/footer.php"; ?>