<?php
/**
 * =====================================================
 * FinnMestre - Funções Comportamentais
 * Sistema de Finanças Psicológicas
 * =====================================================
 */

/**
 * Auto-migração: cria colunas se não existirem
 */
function executarMigracaoBehavioral()
{
    global $pdo;

    // Verificar se as colunas existem na tabela usuarios (legado)
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'salario_mensal'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN salario_mensal DECIMAL(12,2) DEFAULT NULL");
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN horas_trabalho_mes INT DEFAULT 176");
        }
    } catch (Exception $e) {
        // Silenciosamente ignora se falhar
    }

    // Adicionar colunas de salário na tabela perfis (per-profile)
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM perfis LIKE 'salario_mensal'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE perfis ADD COLUMN salario_mensal DECIMAL(12,2) DEFAULT NULL");
            $pdo->exec("ALTER TABLE perfis ADD COLUMN horas_trabalho_mes INT DEFAULT 176");
        }
    } catch (Exception $e) {
        // Silenciosamente ignora se falhar
    }

    // Verificar coluna validacao_social em categorias
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM categorias LIKE 'validacao_social'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE categorias ADD COLUMN validacao_social TINYINT(1) DEFAULT 0");
            // Marcar categorias de validação social
            $pdo->exec("UPDATE categorias SET validacao_social = 1 WHERE nome IN ('Lazer', 'Roupas', 'Restaurantes', 'Delivery', 'Assinaturas', 'Beleza', 'Presentes')");
        }
    } catch (Exception $e) {
        // Silenciosamente ignora se falhar
    }

    // Criar tabela micro_poupanca se não existir
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS micro_poupanca (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            valor_diario DECIMAL(10,2) NOT NULL,
            ativo TINYINT(1) DEFAULT 1,
            data_inicio DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $e) {
        // Silenciosamente ignora se falhar
    }
}

// Executar migração na primeira carga
executarMigracaoBehavioral();

/**
 * Obtém salário do perfil ativo (independente por perfil)
 */
function obterSalarioUsuario()
{
    global $pdo;
    $perfilId = $_SESSION['perfil_id'] ?? null;
    if (!$perfilId)
        return 0;

    try {
        $stmt = $pdo->prepare("SELECT salario_mensal FROM perfis WHERE id = ?");
        $stmt->execute([$perfilId]);
        $result = $stmt->fetch();
        if ($result && $result['salario_mensal'] !== null) {
            return (float) $result['salario_mensal'];
        }
    } catch (Exception $e) {
        // Coluna pode não existir ainda
    }

    return 0;
}

/**
 * Obtém horas de trabalho mensais do perfil ativo (independente por perfil)
 */
function obterHorasTrabalhoUsuario()
{
    global $pdo;
    $perfilId = $_SESSION['perfil_id'] ?? null;
    if (!$perfilId)
        return 176; // Padrão: 44h/semana * 4

    try {
        $stmt = $pdo->prepare("SELECT horas_trabalho_mes FROM perfis WHERE id = ?");
        $stmt->execute([$perfilId]);
        $result = $stmt->fetch();
        if ($result && $result['horas_trabalho_mes'] !== null) {
            return (int) $result['horas_trabalho_mes'];
        }
    } catch (Exception $e) {
        // Coluna pode não existir ainda
    }

    return 176;
}

/**
 * Salva configurações comportamentais no perfil ativo
 */
function salvarConfiguracoesBehavioral($salario, $horasTrabalho)
{
    global $pdo;
    $perfilId = $_SESSION['perfil_id'] ?? null;
    $userId = $_SESSION['usuario_id'] ?? null;
    if (!$userId)
        return false;

    // Salvar no perfil ativo
    if ($perfilId) {
        try {
            $stmt = $pdo->prepare("UPDATE perfis SET salario_mensal = ?, horas_trabalho_mes = ? WHERE id = ?");
            return $stmt->execute([$salario, $horasTrabalho, $perfilId]);
        } catch (Exception $e) {
            // Fallback para usuarios se a coluna não existir
        }
    }

    // Fallback: salvar no usuário (legado)
    $stmt = $pdo->prepare("UPDATE usuarios SET salario_mensal = ?, horas_trabalho_mes = ? WHERE id = ?");
    return $stmt->execute([$salario, $horasTrabalho, $userId]);
}

/**
 * Calcula percentual do salário ainda disponível
 * 
 * @param int $mes
 * @param int $ano
 * @return array ['percentual', 'salario', 'gasto', 'sobra', 'classe', 'mensagem']
 */
function calcularPercentualSalarioRestante($mes, $ano)
{
    $salario = obterSalarioUsuario();
    if ($salario <= 0) {
        return [
            'configurado' => false,
            'percentual' => 0,
            'mensagem' => 'Configure seu salário nas configurações para ver esta análise.'
        ];
    }

    $resumo = obterResumoMensal($mes, $ano);
    $totalGasto = $resumo['total_saidas'];
    $sobra = $salario - $totalGasto;
    $percentual = max(0, ($sobra / $salario) * 100);

    // Determinar classe e mensagem
    if ($percentual > 40) {
        $classe = 'safe';
        $mensagens = [
            "Você ainda tem " . number_format($percentual, 0) . "% do salário. Está no caminho certo!",
            "Excelente! Mais de 40% disponível. Continue assim.",
            "Ótimo controle! Você ainda pode respirar tranquilo."
        ];
    } elseif ($percentual >= 20) {
        $classe = 'warning';
        $mensagens = [
            "Atenção: resta apenas " . number_format($percentual, 0) . "% do seu salário.",
            "Zona de alerta! Cada gasto agora precisa ser pensado duas vezes.",
            "Cuidado: você está entrando na zona amarela."
        ];
    } else {
        $classe = 'danger';
        $mensagens = [
            "ALERTA: Apenas " . number_format($percentual, 0) . "% restante. Você está trabalhando só para pagar contas.",
            "Situação crítica! Abaixo de 20% você perde liberdade financeira.",
            "Zona vermelha! Cada centavo conta agora."
        ];
    }

    return [
        'configurado' => true,
        'salario' => $salario,
        'gasto' => $totalGasto,
        'sobra' => $sobra,
        'percentual' => $percentual,
        'classe' => $classe,
        'mensagem' => $mensagens[array_rand($mensagens)]
    ];
}

/**
 * Converte valor em horas de trabalho
 * 
 * @param float $valor
 * @return array ['horas', 'minutos', 'dias', 'mensagem']
 */
function converterValorEmHoras($valor)
{
    $salario = obterSalarioUsuario();
    $horasMes = obterHorasTrabalhoUsuario();

    if ($salario <= 0 || $horasMes <= 0) {
        return [
            'configurado' => false,
            'horas' => 0,
            'mensagem' => null
        ];
    }

    $valorHora = $salario / $horasMes;
    $horasNecessarias = $valor / $valorHora;
    $diasNecessarios = $horasNecessarias / 8; // 8h por dia

    // Calcular horas e minutos
    $horasInteiras = floor($horasNecessarias);
    $minutos = round(($horasNecessarias - $horasInteiras) * 60);

    // Gerar mensagem de impacto
    if ($horasNecessarias >= 40) {
        $mensagem = "Isso custa uma semana inteira de trabalho. Vale mesmo a pena?";
    } elseif ($horasNecessarias >= 16) {
        $mensagem = "Você trabalharia " . round($diasNecessarios) . " dias apenas para pagar isso.";
    } elseif ($horasNecessarias >= 8) {
        $mensagem = "Um dia inteiro de trabalho. Pense bem antes de gastar.";
    } elseif ($horasNecessarias >= 4) {
        $mensagem = "Meio dia de trabalho por isso. Necessário ou desejo?";
    } elseif ($horasNecessarias >= 1) {
        $mensagem = "Algumas horas da sua vida. Compensa?";
    } else {
        $mensagem = null;
    }

    return [
        'configurado' => true,
        'valor_hora' => $valorHora,
        'horas' => $horasInteiras,
        'minutos' => $minutos,
        'horas_total' => $horasNecessarias,
        'dias' => $diasNecessarios,
        'mensagem' => $mensagem,
        'texto_curto' => $horasInteiras > 0
            ? "{$horasInteiras}h" . ($minutos > 0 ? "{$minutos}min" : "")
            : "{$minutos}min"
    ];
}

/**
 * Verifica se uma categoria é de validação social
 */
function isCategoriValidacaoSocial($categoriaId)
{
    global $pdo;
    if (!$categoriaId)
        return false;

    $stmt = $pdo->prepare("SELECT validacao_social FROM categorias WHERE id = ?");
    $stmt->execute([$categoriaId]);
    $result = $stmt->fetch();
    return $result && $result['validacao_social'] == 1;
}

/**
 * Obtém mensagem de validação social para uma categoria
 */
function obterMensagemValidacaoSocial($categoriaId)
{
    if (!isCategoriValidacaoSocial($categoriaId))
        return null;

    $mensagens = [
        "Esse tipo de gasto normalmente serve mais para aparência do que necessidade. Você está comprando para você ou para impressionar alguém?",
        "Antes de pagar, pergunte: isso melhora minha vida de verdade ou só parece bom para os outros?",
        "Gastos assim costumam estar mais ligados a emoções do que a necessidades reais.",
        "Você realmente precisa disso ou só quer mostrar que tem?",
        "Pergunte-se: daqui a 1 mês, vou lembrar dessa compra com alegria?"
    ];

    return $mensagens[array_rand($mensagens)];
}

/**
 * Calcula projeção de micro-poupança
 * 
 * @param float $valorDiario
 * @return array
 */
function calcularMicroPoupanca($valorDiario = 15)
{
    $em7dias = $valorDiario * 7;
    $em30dias = $valorDiario * 30;
    $em1Ano = $valorDiario * 365;

    // Sugestões do que pode pagar
    $sugestoes30 = [];
    if ($em30dias >= 100)
        $sugestoes30[] = "internet";
    if ($em30dias >= 150)
        $sugestoes30[] = "luz";
    if ($em30dias >= 200)
        $sugestoes30[] = "água + luz";
    if ($em30dias >= 300)
        $sugestoes30[] = "uma conta mensal";
    if ($em30dias >= 500)
        $sugestoes30[] = "emergência básica";

    return [
        'valor_diario' => $valorDiario,
        'em_7_dias' => $em7dias,
        'em_30_dias' => $em30dias,
        'em_1_ano' => $em1Ano,
        'sugestao' => !empty($sugestoes30)
            ? "Isso paga: " . implode(' + ', array_slice($sugestoes30, 0, 2))
            : "Pequenos valores somam grandes resultados"
    ];
}

/**
 * Obtém configuração de micro-poupança ativa do usuário
 */
function obterMicroPoupancaAtiva()
{
    global $pdo;
    $userId = $_SESSION['usuario_id'] ?? null;
    if (!$userId)
        return null;

    $stmt = $pdo->prepare("SELECT * FROM micro_poupanca WHERE usuario_id = ? AND ativo = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Ativa meta de micro-poupança
 */
function ativarMicroPoupanca($valorDiario)
{
    global $pdo;
    $userId = $_SESSION['usuario_id'] ?? null;
    if (!$userId)
        return false;

    try {
        $pdo->beginTransaction();

        // Desativar anteriores
        $pdo->prepare("UPDATE micro_poupanca SET ativo = 0 WHERE usuario_id = ?")->execute([$userId]);

        // Criar nova
        $stmt = $pdo->prepare("INSERT INTO micro_poupanca (usuario_id, valor_diario, data_inicio) VALUES (?, ?, CURDATE())");
        $stmt->execute([$userId, $valorDiario]);

        // Criar meta financeira correspondente
        $projecaoMensal = $valorDiario * 30;
        $stmt = $pdo->prepare("INSERT INTO metas (titulo, descricao, valor_objetivo, data_inicio, cor, icone, prioridade) 
            VALUES (?, ?, ?, CURDATE(), '#10b981', 'fa-piggy-bank', 'alta')");
        $stmt->execute([
            "Micro-poupança: R$ " . number_format($valorDiario, 2, ',', '.') . "/dia",
            "Meta automática de economia diária",
            $projecaoMensal
        ]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

