/**
 * assistente.js
 * JavaScript logic for AI Financial Assistant Chat
 */

document.addEventListener('DOMContentLoaded', () => {
    let conversaAtual = null;
    let carregando = false;

    const chatInput = document.getElementById('chatInput');
    const btnSend = document.getElementById('btnSend');
    const chatMessages = document.getElementById('chatMessages');
    const btnNovaConversa = document.getElementById('btnNovaConversa');
    const conversationSelector = document.getElementById('conversationSelector');

    // Inicializa carregando conversas
    carregarListaConversas();

    // Auto-resize textarea
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if (this.value.trim() === '') {
            this.style.height = 'auto';
        }
    });

    // Enter para enviar
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            enviarMensagem();
        }
    });

    // Click no botão enviar
    btnSend.addEventListener('click', () => enviarMensagem());

    // Click nova conversa
    btnNovaConversa.addEventListener('click', novaConversa);

    // Mudar conversa
    conversationSelector.addEventListener('change', function() {
        const id = this.value;
        if (id) {
            carregarConversa(id);
        } else {
            novaConversa();
        }
    });

    window.enviarSugestao = function(texto) {
        enviarMensagem(texto);
    };

    async function enviarMensagem(texto = null) {
        const mensagem = texto || chatInput.value.trim();
        
        if (!mensagem || carregando) return;

        chatInput.value = '';
        chatInput.style.height = 'auto';
        
        adicionarMensagem(mensagem, 'user');
        mostrarIndicadorDigitando();
        
        carregando = true;
        chatInput.disabled = true;
        btnSend.disabled = true;

        try {
            const response = await fetch('api/ia_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mensagem: mensagem,
                    conversa_id: conversaAtual
                })
            });

            const data = await response.json();
            
            removerIndicadorDigitando();

            if (data.sucesso) {
                adicionarMensagem(data.resposta, 'assistant');
                if (data.conversa_id && conversaAtual !== data.conversa_id) {
                    conversaAtual = data.conversa_id;
                    carregarListaConversas();
                }
            } else {
                adicionarMensagem(data.erro || 'Ocorreu um erro ao processar sua mensagem.', 'assistant');
            }
        } catch (error) {
            removerIndicadorDigitando();
            adicionarMensagem('Erro de conexão. Tente novamente mais tarde.', 'assistant');
            console.error('Erro:', error);
        } finally {
            carregando = false;
            if (!chatInput.hasAttribute('data-disabled-by-api')) {
                chatInput.disabled = false;
                btnSend.disabled = false;
                chatInput.focus();
            }
        }
    }

    function adicionarMensagem(texto, role, timestamp = null) {
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${role}`;
        
        const content = document.createElement('div');
        content.className = 'bubble-content';
        
        if (role === 'assistant') {
            content.innerHTML = formatarMensagemIA(texto);
        } else {
            content.textContent = texto;
        }
        
        const time = document.createElement('div');
        time.className = 'bubble-time';
        
        if (!timestamp) {
            const now = new Date();
            timestamp = now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0');
        }
        time.textContent = timestamp;
        
        bubble.appendChild(content);
        bubble.appendChild(time);
        
        chatMessages.appendChild(bubble);
        scrollToBottom();
    }

    function mostrarIndicadorDigitando() {
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble assistant id-typing';
        bubble.id = 'typingIndicator';
        
        bubble.innerHTML = `
            <div class="bubble-content">
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        
        chatMessages.appendChild(bubble);
        scrollToBottom();
    }

    function removerIndicadorDigitando() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) {
            indicator.remove();
        }
    }

    async function novaConversa() {
        if (carregando) return;
        
        try {
            const response = await fetch('api/ia_chat.php?acao=nova_conversa', {
                method: 'POST'
            });
            const data = await response.json();
            
            if (data.sucesso) {
                conversaAtual = null;
                chatMessages.innerHTML = `
                    <div class="chat-bubble assistant initial-message">
                        <div class="bubble-content">
                            ${document.querySelector('.initial-message .bubble-content')?.dataset?.originalMessage || 'Olá! Sou seu assistente financeiro. Analise seus gastos, encontre oportunidades de economia e crie planos financeiros.'}
                        </div>
                        <div class="bubble-time">${formatCurrentTime()}</div>
                    </div>
                `;
                conversationSelector.value = '';
                carregarListaConversas();
            }
        } catch (error) {
            console.error('Erro ao criar nova conversa', error);
        }
    }

    async function carregarConversa(id) {
        if (carregando) return;
        
        chatMessages.innerHTML = '<div class="text-center p-3 text-muted">Carregando mensagens...</div>';
        
        try {
            const response = await fetch(`api/ia_chat.php?acao=conversa&id=${id}`);
            const data = await response.json();
            
            if (data.sucesso) {
                chatMessages.innerHTML = '';
                conversaAtual = id;
                
                if (data.mensagens && data.mensagens.length > 0) {
                    data.mensagens.forEach(msg => {
                        const role = msg.papel === 'usuario' ? 'user' : 'assistant';
                        adicionarMensagem(msg.conteudo, role, msg.data_hora_formatada);
                    });
                } else {
                    novaConversa();
                }
            }
        } catch (error) {
            chatMessages.innerHTML = '<div class="text-center p-3 text-danger">Erro ao carregar conversa.</div>';
            console.error('Erro ao carregar conversa', error);
        }
    }

    async function carregarListaConversas() {
        try {
            const response = await fetch('api/ia_chat.php?acao=historico');
            const data = await response.json();
            
            if (data.sucesso && data.conversas) {
                // Keep the first option
                const firstOption = conversationSelector.options[0];
                conversationSelector.innerHTML = '';
                conversationSelector.appendChild(firstOption);
                
                data.conversas.forEach(conv => {
                    const option = document.createElement('option');
                    option.value = conv.id;
                    option.textContent = conv.titulo || `Conversa de ${conv.data_criacao_formatada}`;
                    if (conv.id == conversaAtual) {
                        option.selected = true;
                    }
                    conversationSelector.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Erro ao carregar lista de conversas', error);
        }
    }

    function formatarMensagemIA(texto) {
        if (!texto) return '';
        
        // Sanitize first to prevent XSS
        let html = texto.replace(/</g, "&lt;").replace(/>/g, "&gt;");
        
        // Code blocks (```...```)
        html = html.replace(/```([\s\S]*?)```/g, function(match, code) {
            return '<div class="ia-code-block">' + code.trim() + '</div>';
        });
        
        // Headings (### > ## > #)
        html = html.replace(/^### (.*$)/gim, '<h4 class="ia-heading">$1</h4>');
        html = html.replace(/^## (.*$)/gim, '<h3 class="ia-heading">$1</h3>');
        html = html.replace(/^# (.*$)/gim, '<h3 class="ia-heading ia-heading-main">$1</h3>');
        
        // Bold + Italic
        html = html.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>');
        // Bold
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Italic
        html = html.replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
        // Strikethrough
        html = html.replace(/~~(.*?)~~/g, '<del>$1</del>');
        
        // Horizontal rule
        html = html.replace(/^---+$/gim, '<hr class="ia-divider">');
        
        // Arrow notation (→) for plan comparisons: "R$ 420 → R$ 200"
        html = html.replace(/→/g, '<span class="ia-arrow">→</span>');
        
        // Negative currency (preceded by - or "negativo"): highlight red
        html = html.replace(/-\s*(R\$ [\d\.,]+)/g, '<span class="ia-valor-negativo">-$1</span>');
        
        // Positive currency: highlight green
        html = html.replace(/(?<![\-\w])(R\$ [\d\.,]+)/g, '<span class="ia-valor">$1</span>');
        
        // Percentage values
        html = html.replace(/([\d\.,]+%)/g, '<span class="ia-percentual">$1</span>');
        
        // Numbered lists (1. 2. 3.)
        html = html.replace(/^(\d+)\. (.*$)/gim, '<div class="ia-list-item ia-list-numbered"><span class="ia-list-num">$1.</span> $2</div>');
        
        // Unordered lists (- item or * item or • item)
        html = html.replace(/^[\-\*•] (.*$)/gim, '<div class="ia-list-item"><span class="ia-list-bullet">•</span> $1</div>');

        // Wrap consecutive list items in a container
        html = html.replace(/((?:<div class="ia-list-item[^"]*">.*?<\/div>\n?)+)/g, '<div class="ia-list">$1</div>');
        
        // Line breaks (but not after block elements)
        html = html.replace(/\n(?!<)/g, '<br>');
        // Clean up double <br> after block elements
        html = html.replace(/(<\/div>|<\/h[34]>|<hr[^>]*>)<br>/g, '$1');
        
        return html;
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function formatCurrentTime() {
        const now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' + 
               now.getMinutes().toString().padStart(2, '0');
    }
});
