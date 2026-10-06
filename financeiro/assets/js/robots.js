/**
 * =====================================================
 * FinnMestre - FinBots 3D Controller
 * Sistema de robôs interativos para feedback financeiro
 * =====================================================
 */

class FinBot3D {
    constructor() {
        this.alertRobotActive = false;
        this.meritRobotActive = false;
        this.alertMessages = [];
        this.meritMessages = [];
        this.checkInterval = null;

        this.init();
    }

    init() {
        // Criar containers dos robôs
        this.createRobotContainers();

        // Iniciar verificação periódica de eventos
        this.startEventChecking();
    }

    createRobotContainers() {
        // Container do Robô de Alerta
        if (!document.getElementById('alertRobotContainer')) {
            const alertContainer = document.createElement('div');
            alertContainer.id = 'alertRobotContainer';
            alertContainer.className = 'finbot-3d-container alert-robot-container';
            alertContainer.style.display = 'none';
            alertContainer.innerHTML = this.getAlertRobotHTML();
            document.body.appendChild(alertContainer);
        }

        // Container do Robô de Mérito
        if (!document.getElementById('meritRobotContainer')) {
            const meritContainer = document.createElement('div');
            meritContainer.id = 'meritRobotContainer';
            meritContainer.className = 'finbot-3d-container merit-robot-container';
            meritContainer.style.display = 'none';
            meritContainer.innerHTML = this.getMeritRobotHTML();
            document.body.appendChild(meritContainer);
        }
    }

    getAlertRobotHTML() {
        return `
            <div class="finbot-3d" onclick="finBot3D.toggleAlertMessage()">
                <div class="alert-robot">
                    <div class="robot-alert-light"></div>
                    <div class="robot-head">
                        <div class="robot-eyebrow left"></div>
                        <div class="robot-eyebrow right"></div>
                        <div class="robot-eyes">
                            <div class="robot-eye"></div>
                            <div class="robot-eye"></div>
                        </div>
                        <div class="robot-mouth"></div>
                    </div>
                    <div class="robot-body"></div>
                    <div class="robot-arm left"></div>
                    <div class="robot-arm right"></div>
                    <div class="robot-leg left"></div>
                    <div class="robot-leg right"></div>
                </div>
            </div>
            <div class="robot-message" id="alertRobotMessage">
                <button class="robot-message-close" onclick="finBot3D.hideAlertMessage(event)">&times;</button>
                <span id="alertMessageText"></span>
            </div>
        `;
    }

    getMeritRobotHTML() {
        return `
            <div class="finbot-3d" onclick="finBot3D.toggleMeritMessage()">
                <div class="merit-robot">
                    <span class="robot-crown">👑</span>
                    <div class="confetti"></div>
                    <div class="confetti"></div>
                    <div class="confetti"></div>
                    <div class="confetti"></div>
                    <div class="robot-head">
                        <div class="robot-eyes">
                            <div class="robot-eye"></div>
                            <div class="robot-eye"></div>
                        </div>
                        <div class="robot-mouth"></div>
                    </div>
                    <div class="robot-body"></div>
                    <div class="robot-arm left"></div>
                    <div class="robot-arm right"></div>
                    <div class="robot-leg left"></div>
                    <div class="robot-leg right"></div>
                </div>
            </div>
            <div class="robot-message" id="meritRobotMessage">
                <button class="robot-message-close" onclick="finBot3D.hideMeritMessage(event)">&times;</button>
                <span id="meritMessageText"></span>
            </div>
        `;
    }

    // ==========================================
    // CONTROLE DE EXIBIÇÃO DOS ROBÔS
    // ==========================================

    showAlertRobot(message) {
        const container = document.getElementById('alertRobotContainer');
        if (!container) return;

        container.style.display = 'block';
        container.classList.remove('exiting');
        container.classList.add('entering');
        this.alertRobotActive = true;

        // Definir mensagem
        if (message) {
            document.getElementById('alertMessageText').textContent = message;
            setTimeout(() => {
                document.getElementById('alertRobotMessage').classList.add('visible');
            }, 800);
        }

        // Remover classe entering após animação
        setTimeout(() => {
            container.classList.remove('entering');
        }, 800);
    }

    hideAlertRobot() {
        const container = document.getElementById('alertRobotContainer');
        if (!container) return;

        container.classList.add('exiting');
        document.getElementById('alertRobotMessage').classList.remove('visible');

        setTimeout(() => {
            container.style.display = 'none';
            container.classList.remove('exiting');
            this.alertRobotActive = false;
        }, 500);
    }

    showMeritRobot(message) {
        const container = document.getElementById('meritRobotContainer');
        if (!container) return;

        container.style.display = 'block';
        container.classList.remove('exiting');
        container.classList.add('entering');
        this.meritRobotActive = true;

        // Definir mensagem
        if (message) {
            document.getElementById('meritMessageText').textContent = message;
            setTimeout(() => {
                document.getElementById('meritRobotMessage').classList.add('visible');
            }, 800);
        }

        // Remover classe entering após animação
        setTimeout(() => {
            container.classList.remove('entering');
        }, 800);
    }

    hideMeritRobot() {
        const container = document.getElementById('meritRobotContainer');
        if (!container) return;

        container.classList.add('exiting');
        document.getElementById('meritRobotMessage').classList.remove('visible');

        setTimeout(() => {
            container.style.display = 'none';
            container.classList.remove('exiting');
            this.meritRobotActive = false;
        }, 500);
    }

    // ==========================================
    // TOGGLE MENSAGENS
    // ==========================================

    toggleAlertMessage() {
        const msg = document.getElementById('alertRobotMessage');
        msg.classList.toggle('visible');
    }

    toggleMeritMessage() {
        const msg = document.getElementById('meritRobotMessage');
        msg.classList.toggle('visible');
    }

    hideAlertMessage(e) {
        e.stopPropagation();
        document.getElementById('alertRobotMessage').classList.remove('visible');
    }

    hideMeritMessage(e) {
        e.stopPropagation();
        document.getElementById('meritRobotMessage').classList.remove('visible');
    }

    // ==========================================
    // VERIFICAÇÃO DE EVENTOS FINANCEIROS
    // ==========================================

    startEventChecking() {
        // Verificar eventos a cada 30 segundos
        this.checkInterval = setInterval(() => {
            this.checkFinancialEvents();
        }, 30000);

        // Verificar imediatamente ao carregar
        setTimeout(() => {
            this.checkFinancialEvents();
        }, 2000);
    }

    stopEventChecking() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
    }

    async checkFinancialEvents() {
        try {
            const response = await fetch('api/verificar_eventos_robos.php');
            const data = await response.json();

            if (!data.sucesso) return;

            // Verificar se há alertas
            if (data.alertas && data.alertas.length > 0) {
                const alerta = data.alertas[0]; // Mostrar primeiro alerta
                this.showAlertRobot(alerta.mensagem);
            } else if (this.alertRobotActive) {
                this.hideAlertRobot();
            }

            // Verificar se há méritos
            if (data.meritos && data.meritos.length > 0) {
                const merito = data.meritos[0]; // Mostrar primeiro mérito
                this.showMeritRobot(merito.mensagem);
            } else if (this.meritRobotActive) {
                this.hideMeritRobot();
            }
        } catch (error) {
            console.log('FinBot: Erro ao verificar eventos', error);
        }
    }

    // ==========================================
    // MENSAGENS PRÉ-DEFINIDAS
    // ==========================================

    getAlertMessages() {
        return [
            "Ei! Seus gastos estão acima do limite este mês! 😤",
            "Atenção! Você está gastando muito em categorias supérfluas!",
            "Cuidado! Falta pouco para estourar o orçamento mensal!",
            "Os gastos com Lazer estão muito altos este mês! 🚨",
            "Hora de repensar os gastos! Você já usou 80% do limite!",
            "Alerta vermelho! Controle os gastos imediatamente!"
        ];
    }

    getMeritMessages() {
        return [
            "Parabéns! Você está abaixo do limite de gastos! 🎉",
            "Excelente! Meta de economia atingida! ⭐",
            "Incrível! Você economizou mais que o mês passado!",
            "Mandou bem! Sem gastos supérfluos esta semana! 🏆",
            "Fantástico! Todas as contas em dia! 👏",
            "Você está no caminho certo! Continue assim! 🌟"
        ];
    }

    // Mostrar robô de alerta com mensagem aleatória
    triggerRandomAlert() {
        const messages = this.getAlertMessages();
        const randomMsg = messages[Math.floor(Math.random() * messages.length)];
        this.showAlertRobot(randomMsg);
    }

    // Mostrar robô de mérito com mensagem aleatória
    triggerRandomMerit() {
        const messages = this.getMeritMessages();
        const randomMsg = messages[Math.floor(Math.random() * messages.length)];
        this.showMeritRobot(randomMsg);
    }
}

// Inicializar FinBot3D quando o DOM estiver pronto
let finBot3D;
document.addEventListener('DOMContentLoaded', () => {
    // Verificar se o FinBot está ativado antes de inicializar
    if (typeof FINBOT_3D_ENABLED === 'undefined' || FINBOT_3D_ENABLED) {
        finBot3D = new FinBot3D();
    }
});

// Expor para controle manual via console
window.finBot3D = finBot3D;
