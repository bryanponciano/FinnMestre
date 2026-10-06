# FinnMestre - Sistema de Gestão Financeira

Sistema completo de gestão financeira pessoal com disciplina e clareza.

## 🚀 Recursos

- **Dashboard Inteligente**: Visão completa do mês com saudação personalizada e frase motivacional diária
- **Multi-Contas**: Gerencie várias contas bancárias e cartões
- **Transações**: CRUD completo com categorização e formatação de moeda PT-BR
- **Metas Financeiras**: Defina objetivos com aportes mensais planejados
- **Contas Programadas**: Automação de contas fixas com lançamento automático
- **Custo em Horas de Vida**: Veja quanto cada gasto custa em tempo de trabalho
- **Site Público**: Landing page, página de vendas, checkout e obrigado
- **Pagamentos**: Integração com Mercado Pago (PIX, Cartão, Boleto)

## 📋 Requisitos

- PHP 8.0 ou superior
- MySQL 5.7 ou superior
- Extensões PHP: PDO, cURL, JSON
- Servidor web (Apache/Nginx) ou PHP built-in server

## 🛠️ Instalação

1. **Clone ou copie os arquivos**
```bash
cd c:\Users\Bryan\Documents\projetos\conferencia\financeiro
```

2. **Configure o banco de dados**
- Crie um banco MySQL chamado `financeiro`
- Edite `includes/db.php` com suas credenciais

3. **Configure as variáveis de ambiente**
Crie um arquivo `.env` ou defina as variáveis:
```env
MERCADOPAGO_ACCESS_TOKEN=seu_token_aqui
MERCADOPAGO_PUBLIC_KEY=sua_public_key_aqui
```

4. **Inicie o servidor**
```bash
php -S localhost:8000
```

5. **Acesse o sistema**
- Site público: http://localhost:8000/home.php
- Login: http://localhost:8000/login.php

## 📁 Estrutura de Arquivos

```
financeiro/
├── api/                    # Endpoints da API
│   ├── mercadopago-webhook.php
│   ├── contas-programadas.php
│   └── ...
├── assets/
│   ├── css/
│   │   ├── style.css      # Estilos do dashboard
│   │   └── public.css     # Estilos do site público
│   └── js/
├── includes/
│   ├── db.php             # Conexão com banco
│   ├── auth.php           # Autenticação
│   ├── functions.php      # Funções auxiliares
│   ├── behavioral.php     # Finanças comportamentais
│   ├── quotes.php         # Frases motivacionais
│   ├── assinaturas.php    # Gestão de assinaturas
│   ├── mercadopago.php    # SDK Mercado Pago
│   └── ...
├── logs/                   # Logs de webhook
├── home.php               # Landing page
├── vendas.php             # Página de vendas
├── checkout.php           # Checkout
├── obrigado.php           # Página de obrigado
├── login.php              # Login
├── index.php              # Dashboard principal
├── transacoes.php         # Gerenciar transações
├── contas.php             # Gerenciar contas
├── metas.php              # Gerenciar metas
├── contas-programadas.php # Contas fixas
├── assinatura.php         # Minha assinatura
├── configuracoes.php      # Configurações
└── README.md              # Este arquivo
```

## 💳 Integração de Pagamentos

### Mercado Pago

1. Crie uma conta de desenvolvedor em [developers.mercadopago.com](https://www.mercadopago.com.br/developers)
2. Obtenha suas credenciais (Access Token e Public Key)
3. Configure no `.env` ou diretamente em `includes/mercadopago.php`

### Formas de Pagamento

| Forma | Liberação de Acesso |
|-------|---------------------|
| PIX | Imediato |
| Cartão de Crédito | Imediato |
| Boleto Bancário | Até 1 dia útil |

### Webhook

Configure a URL do webhook no painel do Mercado Pago:
```
https://seu-dominio.com/api/mercadopago-webhook.php
```

## 🌐 Hospedagem Recomendada

### Railway (~R$ 20/mês)
- **URL**: [railway.app](https://railway.app)
- **Suporte**: PHP, MySQL nativo
- **Preço**: $5/mês (~R$ 25) ou uso gratuito limitado
- **Vantagens**: Deploy automático via Git, MySQL incluso

### Outras Opções

| Serviço | Preço/mês | Observações |
|---------|-----------|-------------|
| [Hostinger](https://www.hostinger.com.br) | ~R$ 10 | Hospedagem compartilhada, bom custo-benefício |
| [DigitalOcean](https://www.digitalocean.com) | ~R$ 25 ($5) | VPS completo, mais controle |
| [Vercel](https://vercel.com) | Grátis | Apenas para frontend estático |
| [Render](https://render.com) | ~R$ 25 ($5) | Similar ao Railway |

### Como fazer deploy no Railway

1. Crie conta em railway.app
2. Conecte seu repositório Git
3. Adicione MySQL como serviço
4. Configure variáveis de ambiente
5. Deploy automático!

## 🔐 Segurança

- Senhas hasheadas com `password_hash()` (bcrypt)
- Prepared statements para prevenção de SQL injection
- Sessões PHP para autenticação
- HTTPS recomendado em produção

## 📱 Responsivo

O sistema é 100% responsivo, funcionando em:
- Desktop
- Tablet
- Mobile

## 🎨 Personalização

### Cores
Edite as variáveis CSS em `assets/css/style.css`:
```css
:root {
    --fm-primary: #8b5cf6;
    --fm-secondary: #06b6d4;
    --fm-success: #10b981;
    --fm-warning: #f59e0b;
    --fm-danger: #ef4444;
}
```

### Frases Motivacionais
Edite o array em `includes/quotes.php` para adicionar ou modificar frases.

## 📧 Suporte

- Email: suporte@finnmestre.com.br
- Documentação: Este README

## 📝 Licença

Uso privado - Todos os direitos reservados.

---

**FinnMestre** — Gestão financeira com disciplina e clareza.
