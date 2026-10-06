/**
 * =====================================================
 * FinnMestre - JavaScript Principal
 * =====================================================
 */

// ==========================================
// UTILIDADES GLOBAIS
// ==========================================

/**
 * Formata valor como moeda brasileira
 */
function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

/**
 * Formata data para exibição
 */
function formatarData(data) {
    if (!data) return '';
    const d = new Date(data + 'T00:00:00');
    return d.toLocaleDateString('pt-BR');
}

/**
 * Debounce para eventos de input
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ==========================================
// TOASTS / NOTIFICAÇÕES
// ==========================================

const toast = {
    container: null,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'success', duration = 4000) {
        this.init();

        const colors = {
            success: { bg: 'rgba(16, 185, 129, 0.95)', icon: 'check-circle' },
            error: { bg: 'rgba(239, 68, 68, 0.95)', icon: 'times-circle' },
            warning: { bg: 'rgba(245, 158, 11, 0.95)', icon: 'exclamation-triangle' },
            info: { bg: 'rgba(59, 130, 246, 0.95)', icon: 'info-circle' }
        };

        const config = colors[type] || colors.info;

        const toastEl = document.createElement('div');
        toastEl.style.cssText = `
            background: ${config.bg};
            color: white;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            max-width: 400px;
            animation: slideIn 0.3s ease;
            backdrop-filter: blur(10px);
        `;

        toastEl.innerHTML = `
            <i class="fas fa-${config.icon}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; margin-left: auto; opacity: 0.7;">
                <i class="fas fa-times"></i>
            </button>
        `;

        this.container.appendChild(toastEl);

        setTimeout(() => {
            toastEl.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => toastEl.remove(), 300);
        }, duration);
    },

    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'error'); },
    warning(message) { this.show(message, 'warning'); },
    info(message) { this.show(message, 'info'); }
};

// ==========================================
// LOADING OVERLAY
// ==========================================

const loading = {
    overlay: null,

    show(message = 'Carregando...') {
        if (!this.overlay) {
            this.overlay = document.createElement('div');
            this.overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(15, 23, 42, 0.9);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                backdrop-filter: blur(5px);
            `;
        }

        this.overlay.innerHTML = `
            <div style="width: 50px; height: 50px; border: 3px solid rgba(16, 185, 129, 0.2); border-top-color: #10b981; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <p style="margin-top: 20px; color: #94a3b8;">${message}</p>
        `;

        document.body.appendChild(this.overlay);
    },

    hide() {
        if (this.overlay && this.overlay.parentNode) {
            this.overlay.remove();
        }
    }
};

// ==========================================
// CONFIRMAÇÃO DE AÇÕES
// ==========================================

function confirmar(mensagem, callback) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.style.zIndex = '9999';

    overlay.innerHTML = `
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle" style="color: var(--warning);"></i> Confirmar</h3>
            </div>
            <div class="modal-body">
                <p>${mensagem}</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">Cancelar</button>
                <button class="btn btn-danger" id="btnConfirmar">Confirmar</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    overlay.querySelector('#btnConfirmar').onclick = () => {
        overlay.remove();
        callback();
    };
}

// Inicializar automaticamente todos os inputs com classe .input-moeda usando o formatador principal
document.addEventListener('DOMContentLoaded', function() {
    if (typeof inicializarInputMonetario === 'function') {
        document.querySelectorAll('.input-moeda').forEach(input => {
            inicializarInputMonetario(input);
        });
    }
});

// ==========================================
// ANIMAÇÕES DE SCROLL
// ==========================================

const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.animate-in').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    observer.observe(el);
});

// ==========================================
// ATALHOS DE TECLADO
// ==========================================

document.addEventListener('keydown', function (e) {
    // ESC fecha modais
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(el => {
            el.classList.remove('active');
        });
        document.body.style.overflow = '';
    }

    // Ctrl + N = Nova transação (se existir o botão)
    if (e.ctrlKey && e.key === 'n') {
        const btnNovo = document.querySelector('[onclick*="novaTransacao"]');
        if (btnNovo) {
            e.preventDefault();
            btnNovo.click();
        }
    }
});

// ==========================================
// TEMA (preparado para futuro)
// ==========================================

const theme = {
    current: localStorage.getItem('theme') || 'dark',

    toggle() {
        this.current = this.current === 'dark' ? 'light' : 'dark';
        document.body.setAttribute('data-theme', this.current);
        localStorage.setItem('theme', this.current);
    },

    init() {
        document.body.setAttribute('data-theme', this.current);
    }
};

// ==========================================
// INICIALIZAÇÃO
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar tema
    theme.init();

    // Adicionar estilos de animação
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(100px); }
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

    // Log de inicialização
    console.log('🤖 FinnMestre carregado com sucesso!');
});

// ==========================================
// API HELPER
// ==========================================

const api = {
    async get(url) {
        const response = await fetch(url);
        return response.json();
    },

    async post(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return response.json();
    }
};

// ==========================================
// EXPORTAR PARA GLOBAL
// ==========================================

window.FinnMestre = {
    toast,
    loading,
    confirmar,
    formatarMoeda,
    formatarData,
    api,
    theme
};
