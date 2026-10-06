/**
 * =====================================================
 * FinnMestre - JavaScript Comportamental
 * Funções para os cards de psicologia financeira
 * =====================================================
 */

/**
 * Formata valor em moeda brasileira
 */
function formatarMoedaBR(valor) {
    return 'R$ ' + valor.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Atualiza as projeções de micro-poupança
 */
function atualizarMicroPoupanca(valorDiario) {
    valorDiario = parseFloat(valorDiario) || 15;

    const em7dias = valorDiario * 7;
    const em30dias = valorDiario * 30;
    const em1ano = valorDiario * 365;

    // Atualizar valores na interface
    document.getElementById('micro7dias').textContent = formatarMoedaBR(em7dias);
    document.getElementById('micro30dias').textContent = formatarMoedaBR(em30dias);
    document.getElementById('micro1ano').textContent = formatarMoedaBR(em1ano);

    // Atualizar sugestão
    let sugestao = '';
    if (em30dias >= 500) {
        sugestao = 'Isso paga: emergência básica ou um investimento inicial';
    } else if (em30dias >= 300) {
        sugestao = 'Isso paga: uma conta mensal completa';
    } else if (em30dias >= 200) {
        sugestao = 'Isso paga: água + luz';
    } else if (em30dias >= 150) {
        sugestao = 'Isso paga: conta de luz';
    } else if (em30dias >= 100) {
        sugestao = 'Isso paga: internet';
    } else {
        sugestao = 'Pequenos valores somam grandes resultados';
    }

    document.getElementById('microSugestao').textContent = sugestao;
}

/**
 * Ativa meta de micro-poupança
 */
function ativarMetaMicroPoupanca() {
    const valorDiario = document.getElementById('microValorDiario').value || 15;

    if (confirm(`Deseja ativar uma meta de economia de R$ ${valorDiario}/dia?\n\nIsso criará uma meta mensal de ${formatarMoedaBR(valorDiario * 30)}.`)) {
        // Fazer requisição para ativar
        fetch('api/ativar_micro_poupanca.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ valor_diario: valorDiario })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Meta de micro-poupança ativada com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Erro ao ativar meta: ' + (data.error || 'Tente novamente'), 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showToast('Erro de conexão. Tente novamente.', 'error');
            });
    }
}

/**
 * Exibe toast de notificação
 */
function showToast(message, type = 'info') {
    // Remover toast existente
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) existingToast.remove();

    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(toast);

    // Animar entrada
    setTimeout(() => toast.classList.add('show'), 10);

    // Remover após 3 segundos
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Adiciona estilos do toast dinamicamente
 */
(function addToastStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .toast-notification {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 16px 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 10000;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
        }
        
        .toast-notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .toast-notification.success {
            border-color: var(--success);
        }
        
        .toast-notification.success i {
            color: var(--success);
        }
        
        .toast-notification.error {
            border-color: var(--danger);
        }
        
        .toast-notification.error i {
            color: var(--danger);
        }
    `;
    document.head.appendChild(style);
})();

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function () {
    // Atualizar micro-poupança inicial se existir o campo
    const microInput = document.getElementById('microValorDiario');
    if (microInput) {
        microInput.addEventListener('input', function () {
            atualizarMicroPoupanca(this.value);
        });
    }
});
