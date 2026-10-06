/**
 * =====================================================
 * FinnMestre - Formatação Automática de Valores
 * =====================================================
 */

// Configuração de moeda (será passado do PHP)
const MOEDA_CONFIG = window.MOEDA_CONFIG || {
    simbolo: 'R$',
    decimal: ',',
    milhares: '.'
};

/**
 * Formata input de valor monetário automaticamente
 */
function formatarInputMoeda(input) {
    let valor = input.value.replace(/\D/g, '');

    if (valor.length === 0) {
        input.value = '';
        return;
    }

    // Converter para número com decimais
    valor = (parseInt(valor) / 100).toFixed(2);

    // Separar parte inteira e decimal
    let [inteiro, decimal] = valor.split('.');

    // Adicionar separador de milhares
    inteiro = inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, MOEDA_CONFIG.milhares);

    // Montar valor formatado
    input.value = inteiro + MOEDA_CONFIG.decimal + decimal;
}

/**
 * Converter valor formatado para número
 */
function valorParaNumero(valorFormatado) {
    if (!valorFormatado) return 0;

    // Remover símbolo da moeda
    let valor = valorFormatado.replace(MOEDA_CONFIG.simbolo, '').trim();

    // Trocar separadores
    valor = valor.replace(new RegExp('\\' + MOEDA_CONFIG.milhares, 'g'), '');
    valor = valor.replace(MOEDA_CONFIG.decimal, '.');

    return parseFloat(valor) || 0;
}

/**
 * Formatar número para exibição
 */
function formatarValor(numero) {
    const valor = parseFloat(numero).toFixed(2);
    let [inteiro, decimal] = valor.split('.');

    inteiro = inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, MOEDA_CONFIG.milhares);

    const sinal = numero < 0 ? '-' : '';
    return sinal + MOEDA_CONFIG.simbolo + ' ' + Math.abs(parseInt(inteiro)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, MOEDA_CONFIG.milhares) + MOEDA_CONFIG.decimal + decimal;
}

/**
 * Inicializar formatação automática em inputs de valor
 */
function initFormatacaoMoeda() {
    const inputs = document.querySelectorAll('input[data-moeda], input.input-moeda, #valor');

    inputs.forEach(input => {
        // Preferir o formatador avançado (moeda-input.js) quando disponível
        if (typeof inicializarInputMonetario === 'function') {
            inicializarInputMonetario(input);
            return;
        }

        // Se já foi inicializado, pular
        if (input._configMoeda || input._moedaInit || input._moedaInicializado) return;
        input._moedaInit = true;

        // Fallback: formatador simples
        input.addEventListener('keypress', function (e) {
            if (!/[\d]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            formatarInputMoeda(this);
        });

        input.addEventListener('focus', function () {
            if (this.value) {
                const numero = valorParaNumero(this.value);
                if (numero > 0) {
                    this.value = (numero * 100).toFixed(0);
                    formatarInputMoeda(this);
                }
            }
        });
    });
}

// Inicializar quando DOM carregar
document.addEventListener('DOMContentLoaded', initFormatacaoMoeda);

// Expor funções globalmente
window.formatarInputMoeda = formatarInputMoeda;
window.valorParaNumero = valorParaNumero;
window.initFormatacaoMoeda = initFormatacaoMoeda;

