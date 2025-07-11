/* Estilos modernos para o popup do sistema */
.modern-popup {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    text-align: center;
}

/* Container principal do popup */
.modern-popup .swal2-popup {
    padding: 0;
    text-align: center;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(226, 232, 240, 0.5);
    overflow: hidden;
    width: 32em;
    max-width: 95vw;
    text-align: center;
}

/* Cabeçalho do popup */
.modern-popup .swal2-title {
    margin: 0;
    padding: 1.5rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    text-align: center;
    position: relative;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

/* Ícone no cabeçalho */
.modern-popup .swal2-title::before {
    content: '\f0a1';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    margin-right: 0.75rem;
    font-size: 1.25rem;
}

/* Conteúdo do popup */
.modern-popup .swal2-html-container {
    margin: 0 !important;
    text-align: center !important;
    padding: 2rem;
    font-size: 1rem;
    color: #1e293b;
    line-height: 1.6;
}

/* Botão de confirmação */
.modern-popup .swal2-confirm {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: none;
    box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
    border-radius: 8px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
    margin-top: 0;
    margin-bottom: 1.5rem;
}

.modern-popup .swal2-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 8px -2px rgba(59, 130, 246, 0.5);
}

.modern-popup .swal2-confirm:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3), 0 4px 6px -1px rgba(59, 130, 246, 0.4);
}

/* Botão personalizado para links */
.modern-popup .custom-button {
    display: inline-block;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: none;
    box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
    border-radius: 8px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
    margin-top: 1rem;
    margin-bottom: 1.5rem;
    text-align: center;
}

.modern-popup .custom-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 8px -2px rgba(59, 130, 246, 0.5);
}

/* Área de botões */
.modern-popup .swal2-actions {
    margin: 0 !important;
    display: flex !important;
    justify-content: center !important;
    padding: 0 2rem 2rem;
}

/* Tema escuro */
[data-theme="dark"] .modern-popup .swal2-html-container {
    color: #f1f5f9;
}

[data-theme="dark"] .modern-popup .swal2-popup {
    background: #1e293b;
    border-color: rgba(51, 65, 85, 0.5);
}

/* Animação de entrada */
.modern-popup.swal2-show {
    animation: modernPopupIn 0.3s ease-out;
}

@keyframes modernPopupIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Overlay com blur */
.modern-popup-backdrop {
    backdrop-filter: blur(5px);
    background-color: rgba(15, 23, 42, 0.7) !important;
}

/* Botão de fechar */
.modern-popup .swal2-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.5rem;
    transition: all 0.2s ease;
}

.modern-popup .swal2-close:hover {
    color: #fff;
    transform: scale(1.1);
    background: transparent;
}

[data-theme="dark"] .modern-popup .swal2-close {
    color: rgba(255, 255, 255, 0.6);
}

[data-theme="dark"] .modern-popup .swal2-close:hover {
    color: rgba(255, 255, 255, 0.9);
    background: transparent;
}