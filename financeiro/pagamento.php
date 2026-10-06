<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/database.php';

// Se não selecionou plano, redireciona
if (!isset($_GET['plano'])) {
    header('Location: vendas.php');
    exit;
}

$plano = $_GET['plano'];
$planoInfo = [
    'mensal' => ['nome' => 'Mensal', 'valor' => 39.90, 'parcelas' => 1],
    'semestral' => ['nome' => 'Semestral', 'valor' => 197.90, 'parcelas' => 6, 'parcela_valor' => 32.98],
    'anual' => ['nome' => 'Anual', 'valor' => 297.90, 'parcelas' => 12, 'parcela_valor' => 24.83]
];

$info = $planoInfo[$plano] ?? $planoInfo['mensal'];
$metodo = $_GET['metodo'] ?? 'cartao';

// Processar pagamento simulado
$erro = '';
$sucesso = false;
$valorFinBot = 49.90;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo_pagamento = $_POST['metodo'] ?? 'cartao';
    $adicionarFinBot = isset($_POST['add_finbot']) && $_POST['add_finbot'] == '1';
    
    $valorTotal = $info['valor'];
    if ($adicionarFinBot) {
        $valorTotal += $valorFinBot;
    }

    if ($metodo_pagamento === 'cartao') {
        $card_token = $_POST['card_token'] ?? '';
        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $parcelas = intval($_POST['parcelas'] ?? 1);

        if (empty($card_token)) {
            $erro = 'Erro ao processar o cartão. Tente novamente.';
        } elseif (empty($cpf) || strlen($cpf) !== 11) {
            $erro = 'CPF inválido.';
        }
    }

    if (!$erro) {
        // Salvar token e método para a próxima fase (checkout.php)
        $_SESSION['pagamento_aprovado'] = true; // Flag permitindo seguir
        $_SESSION['plano_selecionado'] = $plano;
        $_SESSION['metodo_pagamento'] = $metodo_pagamento;
        $_SESSION['valor_pagamento'] = $valorTotal;
        $_SESSION['parcelas_pagamento'] = $parcelas ?? 1;
        $_SESSION['com_finbot'] = $adicionarFinBot;
        if ($metodo_pagamento === 'cartao') {
            $_SESSION['card_token'] = $card_token;
            $_SESSION['cpf_comprador'] = $cpf;
        }

        header('Location: checkout.php?plano=' . $plano);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento — FinnMestre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-light: #a78bfa;
            --secondary: #06d6a0;
            --accent: #06b6d4;
            --bg-dark: #0a0a0f;
            --bg-card: #12121a;
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --gradient-primary: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 40px 24px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .logo i,
        .logo span {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .header p {
            color: var(--text-secondary);
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
        }

        .payment-methods {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 32px;
        }

        .method-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
        }

        .method-tab {
            flex: 1;
            padding: 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .method-tab:hover {
            border-color: var(--primary);
        }

        .method-tab.active {
            background: rgba(139, 92, 246, 0.15);
            border-color: var(--primary);
            color: var(--text-primary);
        }

        .method-tab i {
            font-size: 1.25rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(139, 92, 246, 0.1);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .btn-submit {
            width: 100%;
            padding: 18px 24px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 24px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.4);
        }

        /* PIX */
        .pix-container {
            text-align: center;
            display: none;
        }

        .pix-container.active {
            display: block;
        }

        .pix-qr {
            width: 200px;
            height: 200px;
            background: white;
            border-radius: 16px;
            margin: 24px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .pix-qr i {
            font-size: 4rem;
            color: var(--bg-dark);
        }

        .pix-code {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            font-family: monospace;
            font-size: 0.85rem;
            word-break: break-all;
            margin-bottom: 16px;
        }

        .btn-copy {
            padding: 12px 24px;
            background: rgba(6, 214, 160, 0.15);
            border: 1px solid var(--secondary);
            color: var(--secondary);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-copy:hover {
            background: var(--secondary);
            color: #000;
        }

        /* Order Summary */
        .order-summary {
            background: var(--bg-card);
            border: 2px solid var(--primary);
            border-radius: 20px;
            padding: 32px;
            height: fit-content;
            position: sticky;
            top: 40px;
        }

        .order-summary h3 {
            font-size: 1.25rem;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-summary h3 i {
            color: var(--secondary);
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .order-item:last-of-type {
            border-bottom: none;
        }

        .order-item .label {
            color: var(--text-secondary);
        }

        .order-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-total .label {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .order-total .value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--secondary);
        }

        .security-badges {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .security-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .security-badge i {
            color: var(--secondary);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover {
            color: var(--primary-light);
        }

        /* Card form */
        .card-form {
            display: block;
        }

        .card-form.hidden {
            display: none;
        }

        select option {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        @media (max-width: 900px) {
            .payment-grid {
                grid-template-columns: 1fr;
            }

            .order-summary {
                order: -1;
                position: static;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
                <span>FinnMestre</span>
            </div>
            <h1>Finalize seu pagamento</h1>
            <p>Escolha a forma de pagamento</p>
        </div>

        <div class="payment-grid">
            <div class="payment-methods">
                <div class="method-tabs">
                    <button type="button" class="method-tab active" onclick="selectMethod('cartao')">
                        <i class="fas fa-credit-card"></i>
                        Cartão de Crédito
                    </button>
                    <button type="button" class="method-tab" onclick="selectMethod('pix')">
                        <i class="fas fa-qrcode"></i>
                        PIX
                    </button>
                </div>

                <?php if ($erro): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <!-- Cartão Form -->
                <form method="POST" class="card-form" id="cardForm">
                    <input type="hidden" name="metodo" value="cartao">
                    <input type="hidden" name="card_token" id="card_token">

                    <div class="form-group">
                        <label>Número do Cartão</label>
                        <input type="text" name="numero_cartao" placeholder="0000 0000 0000 0000" maxlength="19"
                            oninput="formatCardNumber(this)" required>
                    </div>

                    <div class="form-group">
                        <label>Titular do Cartão</label>
                        <input type="text" name="nome_cartao" placeholder="NOME COMO ESTÁ NO CARTÃO" required>
                    </div>

                    <div class="form-group">
                        <label>CPF do Titular</label>
                        <input type="text" name="cpf" id="cpf_input" placeholder="000.000.000-00" maxlength="14" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Validade</label>
                            <input type="text" name="validade" id="validade_input" placeholder="MM/AA" maxlength="5"
                                oninput="formatExpiry(this)" required>
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="text" name="cvv" id="cvv_input" placeholder="000" maxlength="4" required>
                        </div>
                    </div>

                    <?php if ($info['parcelas'] > 1): ?>
                        <div class="form-group">
                            <label>Parcelamento</label>
                            <select name="parcelas"
                                style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: var(--text-primary); font-size: 1rem;">
                                <option value="1">1x de R$
                                    <?= number_format($info['valor'], 2, ',', '.') ?> (à vista)
                                </option>
                                <?php for ($i = 2; $i <= $info['parcelas']; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $info['parcelas'] ? 'selected' : '' ?>>
                                        <?= $i ?>x de R$
                                        <?= number_format($info['valor'] / $i, 2, ',', '.') ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Order Bump: FinBot -->
                    <div class="order-bump" style="background: rgba(139, 92, 246, 0.08); border: 2px dashed var(--primary); border-radius: 16px; padding: 20px; margin-top: 24px; margin-bottom: 24px;">
                        <div style="display: flex; gap: 15px; align-items: flex-start; text-align: left;">
                            <div style="flex-shrink: 0; background: #25D366; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);">
                                <i class="fab fa-whatsapp" style="font-size: 1.5rem; color: white;"></i>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="font-size: 0.95rem; margin-bottom: 4px; color: var(--text-primary); display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                                    <span style="background: #06d6a0; color: #000; padding: 2px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase;">OFERTA ÚNICA</span>
                                    FinBot AI p/ WhatsApp
                                </h4>
                                <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.4; margin-bottom: 12px;">
                                    Registre gastos por <b>voz, foto ou texto</b> no WhatsApp. Nossa IA salva tudo automaticamente.
                                </p>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                    <div>
                                        <span style="text-decoration: line-through; font-size: 0.75rem; color: var(--text-secondary);">De R$ 149,90</span>
                                        <span style="font-size: 1rem; font-weight: 800; color: #06d6a0; margin-left: 4px;">R$ 49,90</span>
                                    </div>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; background: var(--primary); color: white; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; transition: all 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                        <input type="checkbox" name="add_finbot" id="add_finbot" value="1" style="width: 16px; height: 16px; accent-color: #06d6a0;" onchange="updateTotal()">
                                        ADICIONAR
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-lock"></i>
                        Pagar <span id="btnTotalText">R$ <?= number_format($info['valor'], 2, ',', '.') ?></span>
                    </button>
                </form>

                <!-- PIX -->
                <div class="pix-container hidden" id="pixContainer">
                    <!-- Método: PIX -->
                    <div class="method-container">
                        <div class="pix-info">
                            <div style="text-align: center; margin-bottom: 24px;">
                                <i class="fas fa-qrcode" style="font-size: 4rem; color: var(--fm-primary); margin-bottom: 16px;"></i>
                                <h3 style="margin-bottom: 8px;">Pagamento instantâneo</h3>
                                <p style="color: var(--text-secondary); font-size: 0.95rem;">
                                    Para gerar seu código PIX, precisamos antes apenas dos seus dados de acesso.<br>
                                    Ao prosseguir, você criará a conta e o QR Code será exibido na página final.
                                </p>
                            </div>

                            <form method="POST" style="margin-top: 24px;">
                                <input type="hidden" name="metodo" value="pix">
                                <button type="submit" class="btn-submit" style="width: 100%;">
                                    Criar Conta e Gerar PIX <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="security-badges">
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        Pagamento Seguro
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-lock"></i>
                        Criptografia SSL
                    </div>
                </div>
            </div>

            <div class="order-summary">
                <h3><i class="fas fa-receipt"></i> Resumo do Pedido</h3>

                <div class="order-item">
                    <span class="label">Plano</span>
                    <span>
                        <?= htmlspecialchars($info['nome']) ?>
                    </span>
                </div>

                <div class="order-item">
                    <span class="label">Acesso</span>
                    <span>Imediato</span>
                </div>

                <div class="order-item">
                    <span class="label">Garantia</span>
                    <span>7 dias</span>
                </div>

                <div class="order-total">
                    <span class="label">Total</span>
                    <span class="value" id="orderTotalValue">R$
                        <?= number_format($info['valor'], 2, ',', '.') ?>
                    </span>
                </div>

                <div id="finbotSummaryItem" style="display: none; margin-top: 10px; padding: 10px; background: rgba(6, 214, 160, 0.1); border-radius: 8px; font-size: 0.85rem; color: var(--secondary); text-align: center;">
                    <i class="fab fa-whatsapp"></i> + FinBot AI Adicionado!
                </div>

                <a href="vendas.php#planos" class="back-link">
                    <i class="fas fa-arrow-left"></i> Trocar plano
                </a>
            </div>
        </div>
    </div>

    <!-- Mercado Pago SDK -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script>
        const mp = new MercadoPago('APP_USR-797cb425-13fb-4330-820c-108c7f35af90', {
            locale: 'pt-BR'
        });

        document.getElementById('cardForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('.btn-submit');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            btn.disabled = true;

            const cardNumber = this.numero_cartao.value.replace(/\D/g, '');
            const cardholderName = this.nome_cartao.value;
            const cpf = document.getElementById('cpf_input').value.replace(/\D/g, '');
            const validade = document.getElementById('validade_input').value;
            const [cardExpirationMonth, cardExpirationYear] = validade.split('/');
            const securityCode = document.getElementById('cvv_input').value;

            try {
                const token = await mp.createCardToken({
                    cardNumber,
                    cardholderName,
                    cardExpirationMonth,
                    cardExpirationYear: cardExpirationYear ? '20' + cardExpirationYear : '',
                    securityCode,
                    identificationType: 'CPF',
                    identificationNumber: cpf
                });

                if (token.id) {
                    document.getElementById('card_token').value = token.id;
                    this.submit(); // Envia o form com o token gerado
                } else {
                    alert('Erro ao processar cartão. Verifique os dados fornecidos.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                let errString = error.message ? error.message : JSON.stringify(error);
                alert('Ocorreu um erro ao processar seu cartão. Detalhes MP: ' + errString);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        function selectMethod(method) {
            document.querySelectorAll('.method-tab').forEach(tab => tab.classList.remove('active'));
            event.target.closest('.method-tab').classList.add('active');

            if (method === 'cartao') {
                document.getElementById('cardForm').classList.remove('hidden');
                document.getElementById('pixContainer').classList.remove('active');
            } else {
                document.getElementById('cardForm').classList.add('hidden');
                document.getElementById('pixContainer').classList.add('active');
            }
        }

        // Formatação do CPF
        document.getElementById('cpf_input').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            if (value.length > 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            e.target.value = value;
        });

        function formatCardNumber(input) {
            let value = input.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            input.value = value.substring(0, 19);
        }

        function formatExpiry(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            input.value = value.substring(0, 5);
        }

        // Lógica de Atualização do Total
        const valorPlanoBase = <?= $info['valor'] ?>;
        const valorFinBot = 49.90;

        function updateTotal() {
            const hasFinBot = document.getElementById('add_finbot').checked;
            const total = hasFinBot ? (valorPlanoBase + valorFinBot) : valorPlanoBase;
            const totalFormatado = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            
            // Atualizar textos
            document.getElementById('btnTotalText').textContent = totalFormatado;
            document.getElementById('orderTotalValue').textContent = totalFormatado;
            
            // Mostrar/Esconder resumo
            document.getElementById('finbotSummaryItem').style.display = hasFinBot ? 'block' : 'none';
        }
    </script>
</body>

</html>