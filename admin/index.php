<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

// Incluir funções necessárias para obter dados reais
require_once 'includes/banner_functions.php';
require_once 'classes/BannerStats.php';

// Obter dados reais dos jogos
$jogos = obterJogosDeHoje();
$totalJogosHoje = count($jogos);

// Obter estatísticas de banners
$bannerStats = new BannerStats();

// Se for admin, mostrar estatísticas globais, senão mostrar apenas do usuário
if ($_SESSION['role'] === 'admin') {
    $globalStats = $bannerStats->getGlobalBannerStats();
    $userBannerStats = $bannerStats->getUserBannerStats($_SESSION['user_id']);
    $isAdmin = true;
} else {
    $userBannerStats = $bannerStats->getUserBannerStats($_SESSION['user_id']);
    $isAdmin = false;
}

$pageTitle = "Página Inicial";
include "includes/header.php"; // Mantém seu header.php original
?>

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
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background-color: var(--bg-secondary);
        color: var(--text-primary);
        line-height: 1.6;
        transition: var(--transition);
    }

    /* Layout Structure - REMOVIDO para manter sua estrutura original, apenas o grid-responsivo */

    /* Cards - DO NOVO TEMA, adaptado para o seu uso */
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
    /* Adaptação do seu media query original para card-header/body */
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
    .stat-card-info { color: var(--info-500); } /* Usando info do seu original */
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
        font-size: 2rem; /* Adaptado para o novo tema, mas similar ao seu original */
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-primary); /* Usa a cor de texto principal */
    }
    /* Seu media query para stat-card-value */
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

    /* Game Cards - COMBINADO do novo tema e do seu original */
    .game-card { /* Nova classe introduzida para o novo design */
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-sm);
        overflow: hidden;
        transition: var(--transition);
        position: relative;
    }

    .game-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .game-card-header {
        padding: 0.75rem 1rem;
        background: var(--bg-tertiary);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .game-league {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--primary-600);
        text-transform: uppercase;
    }
    [data-theme="dark"] .game-league {
        color: var(--primary-400);
    }
    .game-time {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-primary);
        background: var(--bg-primary);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }
    .game-teams {
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .team {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 0;
    }
    .team-logo {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.5rem;
    }
    .team-logo img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .team-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-primary);
        text-align: center;
        word-break: break-word;
        line-height: 1.2;
    }
    .team-score { /* Mantido do seu original */
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
        background: var(--primary-50);
        color: var(--primary-400); /* Mais claro para dark theme */
    }
    .vs {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        padding: 0 0.5rem;
    }
    .score-separator { /* Mantido do seu original */
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-600);
    }
    [data-theme="dark"] .score-separator {
        color: var(--primary-400); /* Mais claro para dark theme */
    }
    .game-channels {
        padding: 0.75rem 1rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .channel-badge {
        font-size: 0.625rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        background: var(--success-50);
        color: var(--success-600);
        border: 1px solid var(--success-200);
    }
    .channel-badge.more { /* Mantido do seu original */
        color: var(--text-muted);
        background: var(--bg-tertiary);
        border-color: var(--border-color);
    }

    /* Activity Cards - DO NOVO TEMA */
    .activity-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        background: var(--bg-primary);
        border-radius: var(--border-radius-sm);
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }

    .activity-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.125rem;
    }

    .activity-icon-primary { background: var(--primary-50); color: var(--primary-600); }
    .activity-icon-success { background: var(--success-50); color: var(--success-600); }
    .activity-icon-info { background: var(--info-50); color: var(--info-600); }
    .activity-icon-warning { background: var(--warning-50); color: var(--warning-600); }
    .activity-icon-danger { background: var(--danger-50); color: var(--danger-600); }

    [data-theme="dark"] .activity-icon-primary { background: rgba(59, 130, 246, 0.1); color: var(--primary-400); }
    [data-theme="dark"] .activity-icon-success { background: rgba(34, 197, 94, 0.1); color: var(--success-400); }
    [data-theme="dark"] .activity-icon-info { background: rgba(59, 130, 246, 0.1); color: var(--info-400); }
    [data-theme="dark"] .activity-icon-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning-400); }
    [data-theme="dark"] .activity-icon-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger-400); }

    .activity-content { flex: 1; }
    .activity-title { font-weight: 600; margin-bottom: 0.25rem; color: var(--text-primary); }
    .activity-description { font-size: 0.875rem; color: var(--text-secondary); }
    .activity-time { font-size: 0.75rem; color: var(--text-muted); }

    /* Buttons - DO NOVO TEMA */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: var(--border-radius-sm);
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .btn-primary { background: var(--primary-500); color: white; }
    .btn-primary:hover { background: var(--primary-600); transform: translateY(-1px); box-shadow: var(--shadow-md); }
    .btn-secondary { background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border-color); }
    .btn-secondary:hover { background: var(--bg-secondary); }
    .btn-success { background: var(--success-500); color: white; }
    .btn-success:hover { background: var(--success-600); }
    .btn-danger { background: var(--danger-500); color: white; }
    .btn-danger:hover { background: var(--danger-600); }
    .btn-indigo { background: var(--indigo-500); color: white; }
    .btn-indigo:hover { background: var(--indigo-600); }

    /* Section Styles - COMBINADO do novo tema e do seu original */
    .section-title {
        font-size: 1.25rem;
        font-weight: 700; /* Mantido do seu original */
        color: var(--text-primary);
        margin-bottom: 1.5rem; /* Ajustado para espaçamento do novo tema */
        display: flex;
        align-items: center;
        gap: 0.5rem; /* Do novo tema */
        padding-bottom: 0.5rem; /* Do seu original */
        border-bottom: 2px solid var(--border-color); /* Do seu original */
    }
    .section-title i {
        color: var(--primary-500);
        margin-right: 0.5rem; /* Mapeado de mr-2 */
    }
    [data-theme="dark"] .section-title i {
        color: var(--primary-400);
    }
    [data-theme="dark"] .admin-stats-section {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(59, 130, 246, 0.1));
        border-color: rgba(59, 130, 246, 0.2);
    }
    /* Estilos do seu original para admin-stats-section e admin-personal-section */
    .admin-stats-section {
        background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
        border-radius: var(--border-radius);
        padding: 2rem;
        border: 1px solid var(--primary-200);
    }
    .admin-stat-card { /* Esta classe se sobrepõe ao .card e .stat-card para admin, ajuste de borda */
        border: 2px solid var(--primary-200); /* Do seu original, se quiser manter a borda diferente */
        background: var(--bg-primary); /* Já coberto por .stat-card */
        transition: all 0.3s ease; /* Já coberto por .stat-card */
    }
    .admin-stat-card:hover {
        border-color: var(--primary-500); /* Do seu original */
        transform: translateY(-2px); /* Já coberto por .stat-card */
        box-shadow: var(--shadow-lg); /* Já coberto por .stat-card */
    }
    [data-theme="dark"] .admin-stat-card {
        border-color: rgba(59, 130, 246, 0.2);
    }
    .admin-personal-section {
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        padding: 2rem;
        border: 1px solid var(--border-color);
    }


    /* Highlight Card - DO NOVO TEMA */
    .highlight-card {
        position: relative;
        overflow: hidden;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        background: var(--bg-primary);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }

    .highlight-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-500), var(--indigo-500));
    }

    .highlight-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .highlight-card-title {
        font-weight: 600;
        color: var(--text-primary);
    }

    .highlight-card-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        background: var(--danger-50);
        color: var(--danger-600);
    }

    /* Status Badges para jogos - DO NOVO TEMA (combinado com seu original) */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        text-transform: uppercase; /* Do seu original */
        letter-spacing: 0.025em; /* Do seu original */
    }

    /* As cores para os status-badges (mapeadas para as suas cores originais quando possível) */
    .status-badge-primary { background: var(--primary-50); color: var(--primary-600); border: 1px solid var(--primary-100); }
    .status-badge-success { background: var(--success-50); color: var(--success-600); border: 1px solid var(--success-100); }
    .status-badge-warning { background: var(--warning-50); color: var(--warning-600); border: 1px solid var(--warning-100); }
    .status-badge-danger { background: var(--danger-50); color: var(--danger-600); border: 1px solid var(--danger-100); }
    .status-badge-info { background: var(--info-50); color: var(--info-600); border: 1px solid var(--info-100); }
    .status-badge-indigo { background: var(--indigo-50); color: var(--indigo-600); border: 1px solid var(--indigo-100); }
    
    /* Seu original .game-status, agora integrado nos status-badge */
    .live-pulse { animation: livePulse 2s infinite; }
    @keyframes livePulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
    }
    @keyframes pulse { /* Mantido do seu original */
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* Utilitários - Mantidos ou ajustados para refletir o novo tema/Tailwind */
    .grid-responsivo { /* Mantido do seu original, mas renomeado para evitar conflito com 'grid' */
        display: grid;
        gap: 1.5rem;
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    @media (min-width: 640px) {
        .grid-responsivo { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 768px) {
        .grid-responsivo { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .grid-responsivo { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    
    /* Outras classes de grid que o novo tema usa, mantidas */
    .grid { display: grid; }
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
    @media (min-width: 1280px) { /* Adicionado para xl:grid-cols-4 em game-cards */
        .xl\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }


    .flex { display: flex; }
    .items-center { align-items: center; }
    .items-start { align-items: flex-start; }
    .justify-between { justify-content: space-between; }
    .justify-center { justify-content: center; }
    .text-center { text-align: center; }

    .text-sm { font-size: 0.875rem; }
    .text-xs { font-size: 0.75rem; }
    .text-xl { font-size: 1.25rem; } /* Mantido do seu original para ícones de cards maiores */
    .text-2xl { font-size: 1.5rem; }
    .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }

    .font-medium { font-weight: 500; }
    .font-semibold { font-weight: 600; }
    .font-bold { font-weight: 700; }

    .text-primary { color: var(--text-primary); } /* Certifique-se que usa text-primary da variável */
    .text-muted { color: var(--text-muted); }
    .text-primary-500 { color: var(--primary-500); }
    .text-primary-600 { color: var(--primary-600); }
    .text-success-500 { color: var(--success-500); }
    .text-success-600 { color: var(--success-600); }
    .text-warning-500 { color: var(--warning-500); }
    .text-warning-600 { color: var(--warning-600); }
    .text-info-500 { color: var(--info-500); }
    .text-info-600 { color: var(--info-600); }
    .text-secondary { color: var(--text-secondary); } /* Para o ícone de admin-personal-section */

    .bg-primary-50 { background-color: var(--primary-50); }
    .bg-success-50 { background-color: var(--success-50); }
    .bg-warning-50 { background-color: var(--warning-50); }
    .bg-info-50 { background-color: var(--info-50); }
    .bg-gray-50 { background-color: var(--bg-tertiary); } /* Mapeia gray-50 para bg-tertiary do novo tema */
    [data-theme="dark"] .bg-gray-50 { background-color: var(--bg-tertiary); } /* Dark theme para gray-50 */

    .rounded-lg { border-radius: var(--border-radius); }
    .rounded-full { border-radius: 9999px; }

    .w-10 { width: 2.5rem; }
    .h-10 { height: 2.5rem; }
    .w-12 { width: 3rem; }
    .h-12 { height: 3rem; }
    .w-14 { width: 3.5rem; }
    .h-14 { height: 3.5rem; }


    .mb-6 { margin-bottom: 1.5rem; }
    .mb-8 { margin-bottom: 2rem; }
    .mt-2 { margin-top: 0.5rem; }
    .mt-3 { margin-top: 0.75rem; }
    .mt-4 { margin-top: 1rem; }
    .mt-6 { margin-top: 1.5rem; }
    .mr-1 { margin-right: 0.25rem; }
    .mr-2 { margin-right: 0.5rem; }
    .ml-2 { margin-left: 0.5rem; }
    .p-3 { padding: 0.75rem; }
</style>

<div class="page-header">
    <h1 class="page-title">Dashboard <?php echo $isAdmin ? '- Administrador' : ''; ?></h1>
    <p class="page-subtitle">
        Bem-vindo de volta, <?php echo htmlspecialchars($_SESSION["usuario"]); ?>! 
        <?php echo $isAdmin ? 'Gerencie o sistema e monitore todas as atividades.' : 'Gerencie seus banners e configurações.'; ?>
    </p>
</div>

<?php if ($isAdmin): ?>
    <div class="admin-stats-section mb-6">
        <h2 class="section-title">
            <i class="fas fa-globe text-primary-500 mr-2"></i>
            Estatísticas Globais do Sistema
        </h2>
        <div class="grid-responsivo">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-body">
                    <i class="fas fa-images stat-card-icon"></i>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Total de Banners</p>
                            <p class="text-3xl font-bold text-primary"><?php echo number_format($globalStats['total_banners']); ?></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-primary-600 font-medium">
                            <i class="fas fa-chart-line mr-1"></i>
                            Todos os usuários
                        </span>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <i class="fas fa-calendar-day stat-card-icon"></i>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Banners Hoje</p>
                            <p class="text-3xl font-bold text-success-500"><?php echo number_format($globalStats['today_banners']); ?></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-success-600 font-medium">
                            <i class="fas fa-clock mr-1"></i>
                            Últimas 24h
                        </span>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <i class="fas fa-calendar stat-card-icon"></i>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Este Mês</p>
                            <p class="text-3xl font-bold text-warning-500"><?php echo number_format($globalStats['month_banners']); ?></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-warning-600 font-medium">
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo date('M Y'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <i class="fas fa-users stat-card-icon"></i>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Usuários Ativos</p>
                            <p class="text-3xl font-bold text-info-500"><?php echo number_format($globalStats['active_users']); ?></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-info-600 font-medium">
                            <i class="fas fa-user-check mr-1"></i>
                            Geraram banners
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-personal-section mb-6">
        <h2 class="section-title">
            <i class="fas fa-user text-secondary mr-2"></i>
            Suas Estatísticas Pessoais
        </h2>
        <div class="grid-responsivo">
            <div class="stat-card stat-card-indigo">
                <div class="stat-card-body">
                    <i class="fas fa-image stat-card-icon"></i>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Seus Banners Hoje</p>
                            <p class="text-2xl font-bold text-primary"><?php echo $userBannerStats['today_banners']; ?></p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-xs text-primary-600 font-medium">
                            <i class="fas fa-calendar-day mr-1"></i>
                            Hoje
                        </span>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <i class="fas fa-futbol stat-card-icon"></i>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Jogos Hoje</p>
                            <p class="text-2xl font-bold text-success-500"><?php echo $totalJogosHoje; ?></p>
                        </div>
                    </div>
                     <div class="mt-2">
                        <?php if ($totalJogosHoje > 0): ?>
                            <span class="text-xs text-success-600 font-medium">
                                <i class="fas fa-check-circle mr-1"></i>
                                Dados atualizados
                            </span>
                        <?php else: ?>
                            <span class="text-xs text-muted font-medium">
                                <i class="fas fa-info-circle mr-1"></i>
                                Nenhum jogo hoje
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <i class="fas fa-chart-line stat-card-icon"></i>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Seus Banners Este Mês</p>
                            <p class="text-2xl font-bold text-warning-500"><?php echo $userBannerStats['month_banners']; ?></p>
                        </div>
                    </div>
                     <div class="mt-2">
                        <span class="text-xs text-warning-600 font-medium">
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo date('M Y'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <i class="fas fa-trophy stat-card-icon"></i>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Seus Banners Total</p>
                            <p class="text-2xl font-bold text-info-500"><?php echo $userBannerStats['total_banners']; ?></p>
                        </div>
                    </div>
                     <div class="mt-2">
                        <span class="text-xs text-info-600 font-medium">
                            <i class="fas fa-star mr-1"></i>
                            Todos os tempos
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="grid-responsivo mb-6">
        <div class="stat-card stat-card-primary">
            <div class="stat-card-body">
                <i class="fas fa-image stat-card-icon"></i>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Banners Gerados Hoje</p>
                        <p class="text-2xl font-bold text-primary"><?php echo $userBannerStats['today_banners']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-primary-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-primary-600 font-medium">
                        <i class="fas fa-calendar-day mr-1"></i>
                        Hoje
                    </span>
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-success">
            <div class="stat-card-body">
                <i class="fas fa-futbol stat-card-icon"></i>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Jogos Hoje</p>
                        <p class="text-2xl font-bold text-success-500"><?php echo $totalJogosHoje; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-futbol text-success-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <?php if ($totalJogosHoje > 0): ?>
                        <span class="text-xs text-success-600 font-medium">
                            <i class="fas fa-check-circle mr-1"></i>
                            Dados atualizados
                        </span>
                    <?php else: ?>
                        <span class="text-xs text-muted font-medium">
                            <i class="fas fa-info-circle mr-1"></i>
                            Nenhum jogo hoje
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-warning">
            <div class="stat-card-body">
                <i class="fas fa-chart-line stat-card-icon"></i>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Total Este Mês</p>
                        <p class="text-2xl font-bold text-warning-500"><?php echo $userBannerStats['month_banners']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-warning-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-warning-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-warning-600 font-medium">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo date('M Y'); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-info">
            <div class="stat-card-body">
                <i class="fas fa-trophy stat-card-icon"></i>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Total Geral</p>
                        <p class="text-2xl font-bold text-info-500"><?php echo $userBannerStats['total_banners']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-info-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-trophy text-info-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-info-600 font-medium">
                        <i class="fas fa-star mr-1"></i>
                        Todos os tempos
                    </span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($totalJogosHoje > 0): ?>
<div class="card mb-6 highlight-card"> <div class="card-header highlight-card-header">
        <h3 class="card-title highlight-card-title">
            <i class="fas fa-futbol text-success-500 mr-2"></i>
            Jogos de Hoje
        </h3>
        <span class="highlight-card-badge">
            <i class="fas fa-bell mr-1"></i>
            <?php echo $totalJogosHoje; ?> jogos disponíveis
        </span>
    </div>
    <div class="card-body">
        <div class="grid-responsivo"> <?php 
            // Mostrar apenas os primeiros 8 jogos no dashboard
            $jogosLimitados = array_slice($jogos, 0, 8);
            foreach ($jogosLimitados as $jogo): 
                $time1 = $jogo['time1'] ?? 'Time 1';
                $time2 = $jogo['time2'] ?? 'Time 2';
                $liga = $jogo['competicao'] ?? 'Liga';
                $hora = $jogo['horario'] ?? '';
                $placar1 = $jogo['placar_time1'] ?? '';
                $placar2 = $jogo['placar_time2'] ?? '';
                $temPlacar = !empty($placar1) || !empty($placar2);
                $status = !empty($jogo['status']) ? strtoupper($jogo['status']) : '';
                $canais = array_slice($jogo['canais'] ?? [], 0, 2); // Limitar a 2 canais para o card
            ?>
                <div class="game-card"> <div class="game-card-header"> <div class="flex items-center"> <span class="game-league"><?php echo htmlspecialchars($liga); ?></span> <?php if (!empty($status)): ?>
                                <span class="status-badge <?php
                                    if ($status == 'AO_VIVO' || $status == 'LIVE') echo 'status-badge-danger live-pulse ml-2';
                                    else if ($status == 'FINALIZADO' || $status == 'FINISHED') echo 'status-badge-info ml-2';
                                    else if ($status == 'ADIADO' || $status == 'POSTPONED') echo 'status-badge-warning ml-2';
                                    else if ($status == 'CANCELADO' || $status == 'CANCELLED') echo 'status-badge-danger ml-2';
                                    else if ($status == 'INTERVALO' || $status == 'HALFTIME') echo 'status-badge-info ml-2';
                                ?>">
                                    <i class="fas <?php echo ($status == 'AO_VIVO' || $status == 'LIVE') ? 'fa-circle' : (($status == 'FINALIZADO' || $status == 'FINISHED') ? 'fa-check-circle' : 'fa-info-circle'); ?>"></i>
                                    <?php echo str_replace('_', ' ', $status); ?>
                                </span>
                            <?php endif; ?>
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

                    <?php if (!empty($canais)): ?>
                    <div class="game-channels"> <?php foreach ($canais as $canal): ?>
                            <span class="channel-badge"><?php echo htmlspecialchars($canal['nome'] ?? 'Canal'); ?></span>
                        <?php endforeach; ?>
                        <?php if (count($jogo['canais'] ?? []) > 2): ?>
                            <span class="channel-badge more">+<?php echo count($jogo['canais']) - 2; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalJogosHoje > 8): ?>
            <div class="text-center mt-4">
                <a href="jogos_hoje.php" class="btn btn-primary">
                    <i class="fas fa-list mr-2"></i>
                    Ver Todos os <?php echo $totalJogosHoje; ?> Jogos
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Atividade Recente</h3>
        <p class="card-subtitle">Últimas ações realizadas no sistema</p>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"> <div class="activity-card"> <div class="activity-icon activity-icon-primary">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="activity-content">
                    <h4 class="activity-title">Login realizado</h4>
                    <p class="activity-description">Acesso ao painel</p>
                    <p class="activity-time">agora</p>
                </div>
            </div>
            
            <?php if ($totalJogosHoje > 0): ?>
            <div class="activity-card">
                <div class="activity-icon activity-icon-success">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="activity-content">
                    <h4 class="activity-title">Jogos atualizados</h4>
                    <p class="activity-description"><?php echo $totalJogosHoje; ?> jogos disponíveis</p>
                    <p class="activity-time">hoje</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            // Mostrar banners recentes (limitado a 2 para a seção de atividade, a menos que não haja jogos)
            $recentBannersCount = ($totalJogosHoje > 0) ? 2 : 3; // Se houver jogos, mostre 2 banners, senão 3.
            $recentBanners = $bannerStats->getRecentBanners($_SESSION['user_id'], $recentBannersCount);
            foreach ($recentBanners as $banner): 
                $bannerTypeText = $banner['banner_type'] === 'movie' ? 'Filme/Série' : 'Futebol';
                $bannerIcon = $banner['banner_type'] === 'movie' ? 'fa-film' : 'fa-futbol';
                $timeAgo = time() - strtotime($banner['generated_at']);
                $timeText = $timeAgo < 3600 ? 'há ' . floor($timeAgo/60) . ' min' : 'há ' . floor($timeAgo/3600) . 'h';
            ?>
            <div class="activity-card">
                <div class="activity-icon activity-icon-info">
                    <i class="fas <?php echo $bannerIcon; ?>"></i>
                </div>
                <div class="activity-content">
                    <h4 class="activity-title">Banner <?php echo $bannerTypeText; ?> gerado</h4>
                    <p class="activity-description">
                        <?php echo $banner['content_name'] ? htmlspecialchars($banner['content_name']) : 'Banner personalizado'; ?> - <?php echo $timeText; ?>
                    </p>
                    <p class="activity-time"></p> </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($recentBanners) && $totalJogosHoje == 0): ?>
            <div class="activity-card">
                <div class="activity-icon activity-icon-info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="activity-content">
                    <h4 class="activity-title">Sistema atualizado</h4>
                    <p class="activity-description">Dashboard com dados em tempo real</p>
                    <p class="activity-time">agora</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Theme Management
    const themeToggle = document.getElementById('themeToggle'); // Pode não existir se você não incluiu a sidebar
    const body = document.body;
    const themeIcon = themeToggle ? themeToggle.querySelector('i') : null; // Verifica se o botão existe

    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    body.setAttribute('data-theme', savedTheme);
    if (themeIcon) {
        updateThemeIcon(savedTheme);
    }

    if (themeToggle) { // Apenas adiciona o listener se o botão existir
        themeToggle.addEventListener('click', () => {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
    }

    function updateThemeIcon(theme) {
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    // Mobile Menu Management - Se você não tem sidebar/menu mobile, pode remover
    const mobileMenuBtn = document.getElementById('mobileMenuBtn'); // Pode não existir
    const sidebar = document.getElementById('sidebar'); // Pode não existir
    const overlay = document.getElementById('overlay'); // Pode não existir

    if (mobileMenuBtn && sidebar && overlay) { // Apenas adiciona listeners se os elementos existirem
        mobileMenuBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', closeSidebar);

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }

        // Close sidebar on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        });
    }

    // Smooth animations for page load
    document.addEventListener('DOMContentLoaded', () => {
        // Isso assume que o .content-area existe no seu HTML geral, o que pode não ser o caso.
        // Se esta animação não for necessária, pode remover.
        // const contentArea = document.querySelector('.content-area');
        // if (contentArea) {
        //     contentArea.classList.add('animate-slide-in');
        // }
    });
</script>

<?php include "includes/footer.php"; ?>