/**
 * =====================================================
 * FORMATADOR DE INPUT MONETÁRIO - PADRÃO BRASILEIRO
 * Formata valores em tempo real: 1.234,56
 * =====================================================
 */

const DEFAULT_MOEDA_CONFIG = {
    separadorMilhares: '.',
    separadorDecimal: ',',
    casasDecimais: 2,
    prefixo: '',
    sufixo: ''
};

/**
 * Inicializa a máscara monetária em um input
 * @param {HTMLInputElement} input - Elemento input a ser formatado
 * @param {Object} options - Opções de configuração
 */
function inicializarInputMonetario(input, options = {}) {
    if (!input) return;

    const config = {
        separadorMilhares: options.separadorMilhares || (window.MOEDA_CONFIG ? window.MOEDA_CONFIG.milhares : '.'),
        separadorDecimal: options.separadorDecimal || (window.MOEDA_CONFIG ? window.MOEDA_CONFIG.decimal : ','),
        casasDecimais: options.casasDecimais !== undefined ? options.casasDecimais : 2,
        prefixo: options.prefixo || '',
        sufixo: options.sufixo || ''
    };

    // Armazena configuração no elemento
    input._configMoeda = config;

    // Evita duplicar listeners no mesmo input
    if (input._moedaInicializado) return;
    input._moedaInicializado = true;

    // Se tiver valor inicial numérico bruto (ex: "10000" ou "49.90") ou formatado
    if (input.value && input.value.trim() !== '') {
        const valNum = obterValorNumerico(input);
        if (valNum > 0) {
            definirValorMonetario(input, valNum);
        }
    }

    // Event listeners
    input.addEventListener('input', handleInput);
    input.addEventListener('keydown', handleKeydown);
    input.addEventListener('paste', handlePaste);
    input.addEventListener('focus', handleFocus);
    input.addEventListener('blur', handleBlur);

    /**
     * Handler principal de input
     */
    function handleInput(e) {
        const valAtual = input.value;
        let apenasNumeros = valAtual.replace(/\D/g, '');

        if (!apenasNumeros) {
            input.value = '';
            return;
        }

        // Formata o valor
        const valorFormatado = formatarValorMonetario(apenasNumeros, input._configMoeda || config);
        input.value = valorFormatado;

        // Manter cursor no final para digitação fluida
        requestAnimationFrame(() => {
            const posEnd = valorFormatado.length - (config.sufixo ? config.sufixo.length : 0);
            try {
                input.setSelectionRange(posEnd, posEnd);
            } catch (err) {
                // Ignore se type="number" ou não suportado
            }
        });
    }

    /**
     * Previne teclas inválidas mas permite controle e pontuação (vírgula e ponto)
     */
    function handleKeydown(e) {
        const teclasPermitidas = [
            'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
            'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
            'Home', 'End', '.', ','
        ];

        // Permite teclas de controle
        if (teclasPermitidas.includes(e.key)) return;

        // Permite Ctrl/Cmd com qualquer tecla (Copy, Paste, Select All, etc.)
        if (e.ctrlKey || e.metaKey) return;

        // Bloqueia apenas se não for dígito numérico
        if (!/^\d$/.test(e.key)) {
            e.preventDefault();
        }
    }

    /**
     * Trata colagem de valores
     */
    function handlePaste(e) {
        e.preventDefault();
        const textoColar = (e.clipboardData || window.clipboardData).getData('text') || '';
        let valorLimpo = textoColar.trim();

        const cfg = input._configMoeda || config;
        
        // Remove símbolos de moeda
        if (cfg.prefixo) valorLimpo = valorLimpo.replace(cfg.prefixo, '');
        if (cfg.sufixo) valorLimpo = valorLimpo.replace(cfg.sufixo, '');

        // Trata vírgulas e pontos para converter para float
        if (valorLimpo.includes(cfg.separadorDecimal)) {
            const sepMilharEscaped = (cfg.separadorMilhares || '.').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            valorLimpo = valorLimpo.replace(new RegExp(sepMilharEscaped, 'g'), '').replace(cfg.separadorDecimal, '.');
        } else if (valorLimpo.includes('.')) {
            valorLimpo = valorLimpo.replace(/\./g, '');
        }

        const numero = parseFloat(valorLimpo.replace(/[^\d.]/g, ''));
        if (!isNaN(numero)) {
            definirValorMonetario(input, numero);
        }
    }

    /**
     * Seleciona todo o conteúdo ao focar
     */
    function handleFocus(e) {
        requestAnimationFrame(() => {
            try {
                input.select();
            } catch (err) {}
        });
    }

    /**
     * Garante formatação ao sair do campo
     */
    function handleBlur(e) {
        const cfg = input._configMoeda || config;
        if (input.value === '' || input.value === cfg.prefixo + cfg.sufixo) {
            input.value = formatarValorMonetario('0', cfg);
        }
    }
}

/**
 * Formata uma string de números para o padrão monetário
 * @param {string} numeros - String contendo apenas números
 * @param {Object} config - Configurações de formatação
 * @returns {string} Valor formatado
 */
function formatarValorMonetario(numeros, config = {}) {
    const cfg = { ...DEFAULT_MOEDA_CONFIG, ...config };
    const minDigitos = cfg.casasDecimais + 1;

    numeros = (numeros || '0').replace(/\D/g, '');
    numeros = numeros.padStart(minDigitos, '0');

    // Remove zeros à esquerda excessivos
    while (numeros.length > minDigitos && numeros[0] === '0') {
        numeros = numeros.substring(1);
    }

    // Separa parte inteira e decimal
    const posicaoDecimal = numeros.length - cfg.casasDecimais;
    let parteInteira = numeros.substring(0, posicaoDecimal);
    const parteDecimal = numeros.substring(posicaoDecimal);

    // Adiciona separador de milhares
    const sepMilhar = cfg.separadorMilhares || '.';
    parteInteira = parteInteira.replace(/\B(?=(\d{3})+(?!\d))/g, sepMilhar);

    // Monta o valor final
    const sepDecimal = cfg.separadorDecimal || ',';
    let resultado = parteInteira + sepDecimal + parteDecimal;

    // Adiciona prefixo e sufixo
    if (cfg.prefixo) resultado = cfg.prefixo + resultado;
    if (cfg.sufixo) resultado = resultado + cfg.sufixo;

    return resultado;
}

/**
 * Obtém o valor numérico real de um input monetário
 * @param {HTMLInputElement|string} input - Elemento input ou string
 * @returns {number} Valor numérico (ex: 1234.56)
 */
function obterValorNumerico(input) {
    if (!input) return 0;

    let valor = typeof input === 'string' ? input : (input.value || '');
    if (!valor || valor.trim() === '') return 0;

    const config = { ...DEFAULT_MOEDA_CONFIG, ...((input && input._configMoeda) || {}) };

    // Remove prefixo e sufixo
    if (config.prefixo) valor = valor.replace(config.prefixo, '');
    if (config.sufixo) valor = valor.replace(config.sufixo, '');

    valor = valor.trim();
    if (!valor) return 0;

    // Se já é um número puro em string (ex: "10000" ou "10000.50")
    if (/^\d+(\.\d+)?$/.test(valor)) {
        return parseFloat(valor);
    }

    // Se tem vírgula como decimal (padrão brasileiro "10.000,00" ou "100,00")
    if (valor.includes(config.separadorDecimal)) {
        const sepMilharEscaped = (config.separadorMilhares || '.').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        valor = valor.replace(new RegExp(sepMilharEscaped, 'g'), '');
        valor = valor.replace(config.separadorDecimal, '.');
    } else if (valor.includes('.')) {
        // Se só tem pontos
        const partes = valor.split('.');
        if (partes.length > 2) {
            valor = valor.replace(/\./g, '');
        } else if (partes.length === 2 && partes[1].length === 3) {
            valor = valor.replace(/\./g, '');
        }
    }

    // Limpar quaisquer caracteres restantes que não sejam números ou ponto decimal
    valor = valor.replace(/[^\d.]/g, '');

    return parseFloat(valor) || 0;
}

/**
 * Define o valor de um input monetário programaticamente
 * @param {HTMLInputElement} input - Elemento input
 * @param {number} valor - Valor numérico a definir
 */
function definirValorMonetario(input, valor) {
    if (!input) return;

    const config = { ...DEFAULT_MOEDA_CONFIG, ...((input && input._configMoeda) || {}) };
    input._configMoeda = config;

    const num = parseFloat(valor) || 0;
    const centavos = Math.round(num * Math.pow(10, config.casasDecimais)).toString();
    input.value = formatarValorMonetario(centavos, config);
}

// Expor funções globalmente para uso em outros scripts
window.inicializarInputMonetario = inicializarInputMonetario;
window.formatarValorMonetario = formatarValorMonetario;
window.obterValorNumerico = obterValorNumerico;
window.definirValorMonetario = definirValorMonetario;

