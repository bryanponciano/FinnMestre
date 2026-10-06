<?php
require_once 'db.php';
require_once 'funcoes.php';

function executarMigracaoIA() {
    global $conn;
    
    $sql_conversas = "CREATE TABLE IF NOT EXISTS ia_conversas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        perfil_id INT NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $conn->query($sql_conversas);

    $sql_mensagens = "CREATE TABLE IF NOT EXISTS ia_mensagens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversa_id INT NOT NULL,
        papel ENUM('user', 'model') NOT NULL,
        conteudo TEXT NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversa_id) REFERENCES ia_conversas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $conn->query($sql_mensagens);
}

function coletarContextoFinanceiro($perfilId, $mes, $ano) {
    global $conn;
    $contexto = [];

    $contexto['mes'] = $mes;
    $contexto['ano'] = $ano;

    if (function_exists('obterResumoMensal')) $contexto['resumo'] = obterResumoMensal($mes, $ano);
    if (function_exists('obterGastosPorCategoria')) $contexto['categorias'] = obterGastosPorCategoria($mes, $ano);
    if (function_exists('obterSaldoPoupanca')) $contexto['saldo_poupanca'] = obterSaldoPoupanca();
    if (function_exists('obterResumoPoupanca')) $contexto['resumo_poupanca'] = obterResumoPoupanca();
    if (function_exists('obterSaldoTotal')) $contexto['patrimonio'] = obterSaldoTotal($mes, $ano);
    if (function_exists('obterLimiteMensal')) $contexto['limite_mensal'] = obterLimiteMensal();
    if (function_exists('obterPercentualGasto')) $contexto['percentual_gasto'] = obterPercentualGasto($mes, $ano);
    if (function_exists('listarMetas')) $contexto['metas'] = listarMetas(true);
    
    $transacoes = [];
    if (function_exists('listarTransacoes')) {
        try {
            $transacoes = listarTransacoes(['mes' => $mes, 'ano' => $ano]);
            $contexto['transacoes'] = $transacoes;
        } catch (Exception $e) {
            $contexto['transacoes'] = [];
        }
    } else {
        $contexto['transacoes'] = [];
    }

    $contas = [];
    if (function_exists('listarContas')) {
        $contas = listarContas(true);
    }
    
    // Novas análises proativas
    $renda = $contexto['resumo']['total_entradas'] ?? 0;
    
    // 1. Gastos Pequenos (< 50)
    $gastosPequenos = 0;
    $qtdPequenos = 0;
    // 2. Cartão de Crédito
    $gastoCartao = 0;
    $cartaoPorCategoria = [];
    
    if (!empty($transacoes)) {
        foreach ($transacoes as $t) {
            if (isset($t['tipo']) && $t['tipo'] === 'saida') {
                $valor = floatval($t['valor']);
                
                if ($valor < 50) {
                    $gastosPequenos += $valor;
                    $qtdPequenos++;
                }
                
                // Verifica se é crédito
                if (isset($t['conta_tipo']) && $t['conta_tipo'] === 'credito') {
                    $gastoCartao += $valor;
                    $cat = $t['categoria_nome'] ?? 'Sem Categoria';
                    if (!isset($cartaoPorCategoria[$cat])) {
                        $cartaoPorCategoria[$cat] = 0;
                    }
                    $cartaoPorCategoria[$cat] += $valor;
                }
            }
        }
    }
    
    $contexto['gastos_pequenos'] = [
        'total' => $gastosPequenos,
        'quantidade' => $qtdPequenos,
        'percentual_renda' => $renda > 0 ? ($gastosPequenos / $renda) * 100 : 0
    ];
    
    $contexto['cartao_credito'] = [
        'total' => $gastoCartao,
        'percentual_renda' => $renda > 0 ? ($gastoCartao / $renda) * 100 : 0,
        'por_categoria' => $cartaoPorCategoria
    ];
    
    // 3. Classificação de Gastos
    $essenciais = ['Aluguel', 'Moradia', 'Água', 'Luz', 'Energia', 'Gás', 'Internet', 'Telefone', 'Saúde', 'Medicamentos', 'Educação', 'Transporte', 'Condomínio', 'IPTU', 'Seguros'];
    $redutiveis = ['Mercado', 'Supermercado', 'Alimentação', 'Combustível', 'Assinaturas', 'Planos', 'Academia'];
    
    $total_essencial = 0;
    $total_redutivel = 0;
    $total_nao_essencial = 0;
    $economia_potencial = [];
    $total_economia_possivel = 0;
    
    if (isset($contexto['categorias']) && is_array($contexto['categorias'])) {
        foreach ($contexto['categorias'] as $cat) {
            $nome = $cat['nome'] ?? '';
            $valor = floatval($cat['valor'] ?? 0);
            
            if (in_array(trim($nome), $essenciais)) {
                $total_essencial += $valor;
            } elseif (in_array(trim($nome), $redutiveis)) {
                $total_redutivel += $valor;
                $eco = $valor * 0.20; // 20%
                $total_economia_possivel += $eco;
                $economia_potencial[] = [
                    'categoria' => $nome,
                    'valor_atual' => $valor,
                    'valor_sugerido' => $valor - $eco,
                    'economia' => $eco,
                    'tipo' => 'redutivel'
                ];
            } else {
                $total_nao_essencial += $valor;
                $eco = $valor * 0.40; // 40%
                $total_economia_possivel += $eco;
                $economia_potencial[] = [
                    'categoria' => $nome,
                    'valor_atual' => $valor,
                    'valor_sugerido' => $valor - $eco,
                    'economia' => $eco,
                    'tipo' => 'nao_essencial'
                ];
            }
        }
    }
    
    usort($economia_potencial, function($a, $b) {
        return $b['economia'] <=> $a['economia'];
    });
    
    $contexto['classificacao_gastos'] = [
        'total_essencial' => $total_essencial,
        'total_redutivel' => $total_redutivel,
        'total_nao_essencial' => $total_nao_essencial,
        'economia_potencial' => $economia_potencial,
        'total_economia_possivel' => $total_economia_possivel
    ];
    
    // 4. Status Poupança
    $contexto['poupanca_status'] = 'parada';
    if (isset($contexto['resumo_poupanca']['qtd_depositos']) && $contexto['resumo_poupanca']['qtd_depositos'] > 0) {
        $contexto['poupanca_status'] = 'ativa';
    }
    
    // 5. Tendência (Estável como default, poderia ser calculado com dados de meses anteriores)
    $contexto['tendencia'] = 'estavel';
    
    // 6. Comparação com mês anterior
    $mesAnterior = $mes == 1 ? 12 : $mes - 1;
    $anoAnterior = $mes == 1 ? $ano - 1 : $ano;
    $contexto['mes_anterior'] = [];
    if (function_exists('obterResumoMensal')) {
        $resumoAnterior = obterResumoMensal($mesAnterior, $anoAnterior);
        $contexto['mes_anterior']['resumo'] = $resumoAnterior;
    }
    if (function_exists('obterGastosPorCategoria')) {
        $catAnterior = obterGastosPorCategoria($mesAnterior, $anoAnterior);
        $contexto['mes_anterior']['categorias'] = $catAnterior;
        
        // Calculate category changes
        $comparacao = [];
        if (isset($contexto['categorias']) && is_array($contexto['categorias'])) {
            $mapAnterior = [];
            if (is_array($catAnterior)) {
                foreach ($catAnterior as $ca) {
                    $mapAnterior[$ca['nome'] ?? ''] = floatval($ca['valor'] ?? 0);
                }
            }
            foreach ($contexto['categorias'] as $catAtual) {
                $nomeC = $catAtual['nome'] ?? '';
                $valorAtual = floatval($catAtual['valor'] ?? 0);
                $valorAnterior = $mapAnterior[$nomeC] ?? 0;
                if ($valorAnterior > 0 && $valorAtual > $valorAnterior) {
                    $variacao = (($valorAtual - $valorAnterior) / $valorAnterior) * 100;
                    if ($variacao > 10) {
                        $comparacao[] = [
                            'categoria' => $nomeC,
                            'atual' => $valorAtual,
                            'anterior' => $valorAnterior,
                            'variacao' => round($variacao, 1)
                        ];
                    }
                }
            }
            usort($comparacao, function($a, $b) { return $b['variacao'] <=> $a['variacao']; });
        }
        $contexto['comparacao_categorias'] = $comparacao;
    }

    // 7. Projeção de gastos
    $diasNoMes = cal_days_in_month(CAL_GREGORIAN, (int)$mes, (int)$ano);
    $diaAtual = (int)date('d');
    $diasRestantes = max(0, $diasNoMes - $diaAtual);
    $contexto['projecao'] = [
        'dias_no_mes' => $diasNoMes,
        'dia_atual' => $diaAtual,
        'dias_restantes' => $diasRestantes,
        'media_diaria' => $diaAtual > 0 ? ($contexto['resumo']['total_saidas'] ?? 0) / $diaAtual : 0,
        'gasto_projetado' => $diaAtual > 0 ? (($contexto['resumo']['total_saidas'] ?? 0) / $diaAtual) * $diasNoMes : 0,
        'limite_diario_positivo' => $diasRestantes > 0 ? max(0, (($renda - ($contexto['resumo']['total_saidas'] ?? 0)) / $diasRestantes)) : 0
    ];
    
    return $contexto;
}

function formatarContextoParaIA($contexto) {
    $texto = "=== DADOS FINANCEIROS ===\n";
    $texto .= "Mês/Ano: {$contexto['mes']}/{$contexto['ano']}\n";
    
    if (isset($contexto['resumo'])) {
        $r = $contexto['resumo'];
        $texto .= "Renda Total: R$ " . number_format($r['total_entradas'] ?? 0, 2, ',', '.') . "\n";
        $texto .= "Despesas Totais: R$ " . number_format($r['total_saidas'] ?? 0, 2, ',', '.') . "\n";
        $texto .= "Saldo Mensal: R$ " . number_format($r['saldo'] ?? 0, 2, ',', '.') . "\n";
    }

    if (isset($contexto['classificacao_gastos'])) {
        $c = $contexto['classificacao_gastos'];
        $texto .= "\n--- Classificação de Gastos ---\n";
        $texto .= "Essenciais: R$ " . number_format($c['total_essencial'], 2, ',', '.') . "\n";
        $texto .= "Redutíveis: R$ " . number_format($c['total_redutivel'], 2, ',', '.') . "\n";
        $texto .= "Não Essenciais: R$ " . number_format($c['total_nao_essencial'], 2, ',', '.') . "\n";
        $texto .= "Economia Potencial Total: R$ " . number_format($c['total_economia_possivel'], 2, ',', '.') . "\n";
        
        if (!empty($c['economia_potencial'])) {
            $texto .= "Detalhes de Economia Potencial:\n";
            foreach (array_slice($c['economia_potencial'], 0, 5) as $eco) {
                $texto .= " - {$eco['categoria']}: Pode economizar R$ " . number_format($eco['economia'], 2, ',', '.') . "\n";
            }
        }
    }

    if (isset($contexto['gastos_pequenos'])) {
        $gp = $contexto['gastos_pequenos'];
        $texto .= "\n--- Pequenos Gastos (< R$50) ---\n";
        $texto .= "Total Gasto: R$ " . number_format($gp['total'], 2, ',', '.') . " ({$gp['quantidade']} compras)\n";
        $texto .= "Percentual da Renda: " . number_format($gp['percentual_renda'], 1, ',', '.') . "%\n";
    }

    if (isset($contexto['cartao_credito'])) {
        $cc = $contexto['cartao_credito'];
        $texto .= "\n--- Cartão de Crédito ---\n";
        $texto .= "Total no Cartão: R$ " . number_format($cc['total'], 2, ',', '.') . "\n";
        $texto .= "Comprometimento da Renda: " . number_format($cc['percentual_renda'], 1, ',', '.') . "%\n";
    }

    if (isset($contexto['categorias']) && is_array($contexto['categorias'])) {
        $texto .= "\n--- Gastos por Categoria ---\n";
        foreach ($contexto['categorias'] as $cat) {
            if (isset($cat['nome']) && isset($cat['valor'])) {
                $texto .= "- {$cat['nome']}: R$ " . number_format($cat['valor'], 2, ',', '.') . "\n";
            }
        }
    }

    $texto .= "\n--- Patrimônio e Poupança ---\n";
    if (isset($contexto['patrimonio'])) $texto .= "Patrimônio Total: R$ " . number_format($contexto['patrimonio'], 2, ',', '.') . "\n";
    if (isset($contexto['saldo_poupanca'])) $texto .= "Saldo Poupança: R$ " . number_format($contexto['saldo_poupanca'], 2, ',', '.') . "\n";
    $texto .= "Status Poupança: " . ($contexto['poupanca_status'] ?? 'parada') . "\n";

    if (!empty($contexto['metas']) && is_array($contexto['metas'])) {
        $texto .= "\n--- Metas Ativas ---\n";
        foreach ($contexto['metas'] as $meta) {
            $atual = $meta['valor_atual'] ?? $meta['atual'] ?? 0;
            $objetivo = $meta['valor_objetivo'] ?? $meta['objetivo'] ?? 0;
            $texto .= "- {$meta['titulo']}: R$ " . number_format($atual, 2, ',', '.') . " / R$ " . number_format($objetivo, 2, ',', '.') . "\n";
        }
    }
    
    if (isset($contexto['projecao'])) {
        $p = $contexto['projecao'];
        $texto .= "\n--- Projeção do Mês ---\n";
        $texto .= "Dia atual: {$p['dia_atual']} de {$p['dias_no_mes']}\n";
        $texto .= "Dias restantes: {$p['dias_restantes']}\n";
        $texto .= "Média diária de gastos: R$ " . number_format($p['media_diaria'], 2, ',', '.') . "\n";
        $texto .= "Gasto projetado para o mês: R$ " . number_format($p['gasto_projetado'], 2, ',', '.') . "\n";
        $texto .= "Limite diário para fechar positivo: R$ " . number_format($p['limite_diario_positivo'], 2, ',', '.') . "\n";
    }

    if (!empty($contexto['comparacao_categorias'])) {
        $texto .= "\n--- Comparação com Mês Anterior ---\n";
        foreach (array_slice($contexto['comparacao_categorias'], 0, 5) as $comp) {
            $texto .= "- {$comp['categoria']}: R$ " . number_format($comp['anterior'], 2, ',', '.') . " → R$ " . number_format($comp['atual'], 2, ',', '.') . " (+{$comp['variacao']}%)\n";
        }
    }

    if (isset($contexto['mes_anterior']['resumo'])) {
        $ra = $contexto['mes_anterior']['resumo'];
        $texto .= "\n--- Mês Anterior ---\n";
        $texto .= "Renda: R$ " . number_format($ra['total_entradas'] ?? 0, 2, ',', '.') . "\n";
        $texto .= "Despesas: R$ " . number_format($ra['total_saidas'] ?? 0, 2, ',', '.') . "\n";
        $texto .= "Saldo: R$ " . number_format($ra['saldo'] ?? 0, 2, ',', '.') . "\n";
    }

    return $texto;
}

function gerarPromptSistema() {
    return <<<PROMPT
Você é o FinBot, um agente financeiro pessoal proativo do FinMestre.

Seu papel NÃO é apenas responder perguntas. Você deve ANALISAR ativamente a situação financeira do usuário, IDENTIFICAR problemas, EXPLICAR de onde eles vêm e AJUDAR a criar planos práticos.

=== COMPORTAMENTO PRINCIPAL ===

1. ANALISE PROATIVA: Sempre que receber dados financeiros, analise automaticamente e identifique:
   - Se o mês está negativo ou próximo disso
   - Quais categorias estão consumindo mais dinheiro
   - Gastos que aumentaram vs mês anterior
   - Gastos pequenos que somados são significativos
   - Comprometimento do cartão de crédito
   - Se o usuário está guardando dinheiro ou não
   - Se está no caminho para atingir as metas

2. OFEREÇA AJUDA ATIVA: Não espere o usuário perguntar. Quando identificar um problema:
   - Explique o problema de forma clara e sem julgamentos
   - Ofereça-se para ajudar a resolver
   - Pergunte se o usuário quer ver detalhes

3. DIAGNÓSTICO POR CAMADAS:
   Quando analisar gastos, classifique em:
   - ESSENCIAIS: aluguel, água, luz, saúde, educação, transporte básico
   - REDUTÍVEIS: mercado acima da média, assinaturas, combustível extra, academia
   - NÃO ESSENCIAIS: delivery excessivo, compras por impulso, lazer elevado, gastos supérfluos
   Sempre priorize cortes nos NÃO ESSENCIAIS primeiro.

4. CONVERSA INTERATIVA: Faça perguntas ao usuário:
   - "Dos seus R$ X de delivery, você acha que conseguiria reduzir para R$ Y?"
   - "Quer que eu analise primeiro o cartão ou as despesas gerais?"
   - "Qual desses gastos você sente que pode diminuir mais facilmente?"

5. CRIAÇÃO DE PLANOS: Transforme a análise em plano concreto com:
   - Valores atuais vs valores sugeridos
   - Economia estimada por categoria
   - Total de economia potencial
   - Se a economia resolve o déficit ou não
   - Se não resolver, continue procurando mais cortes

6. PROJEÇÕES: Quando o mês ainda não acabou:
   - Calcule a projeção de gastos (média diária × dias restantes)
   - Informe quanto o usuário pode gastar por dia para fechar no positivo
   - Alerte sobre tendências

7. METAS: Use as metas cadastradas:
   - Calcule quanto precisa guardar por mês
   - Compare com a sobra atual
   - Sugira ajustes para atingir a meta

8. POUPANÇA: Entenda perfeitamente que:
   - Transferir para poupança NÃO é despesa, é reserva
   - Retirar da poupança NÃO é receita, é transferência interna
   - Incentive a formação de reserva quando fizer sentido
   - "Você transferiu R$ X para sua Poupança" (correto)
   - NUNCA diga "Você gastou R$ X com Poupança" (incorreto)

9. ACOMPANHAMENTO: Quando o usuário já tiver um plano anterior:
   - Compare os gastos atuais com as metas do plano
   - Parabenize quando estiver no caminho certo
   - Alerte quando estiver saindo do plano

=== DADOS vs ESTIMATIVAS ===
Sempre diferencie:
- DADO REAL: "Você gastou R$ 620 com delivery"
- ESTIMATIVA: "Se reduzir pela metade, poderá economizar APROXIMADAMENTE R$ 310"
Nunca apresente estimativas como valores garantidos.

=== PERSONALIDADE ===
- Amigável e direto
- Motivador sem ser falso
- Prático e orientado a soluções
- Sem julgamentos (nunca diga "você gasta errado")
- Prefira "Encontrei alguns gastos que podem ser reduzidos" 
- Use emojis com moderação (📊 💰 📉 💡 🎯)
- Fale em Português do Brasil
- Valores sempre em R$

=== CICLO DO AGENTE ===
Siga sempre:
ANALISAR → IDENTIFICAR PROBLEMA → EXPLICAR → PERGUNTAR → SUGERIR CORTES → CALCULAR ECONOMIA → CRIAR PLANO → OFERECER ACOMPANHAMENTO

=== REGRAS ABSOLUTAS ===
1. USE APENAS os dados fornecidos. NUNCA invente valores, transações ou saldos.
2. NUNCA execute operações financeiras. Apenas analise e sugira.
3. NUNCA considere transferência para Poupança como despesa.
4. Quando não tiver dados suficientes, informe e peça ao usuário.
5. Conduza a conversa ativamente — não dê respostas genéricas.
PROMPT;
}

function enviarMensagemIA($mensagemUsuario, $contextoFinanceiro, $historicoConversa = []) {
    $provedor = function_exists('obterConfiguracao') ? obterConfiguracao('ia_provedor', 'gemini') : 'gemini';
    $apiKey = function_exists('obterConfiguracao') ? obterConfiguracao('ia_api_key', '') : '';
    $modelo = function_exists('obterConfiguracao') ? obterConfiguracao('ia_modelo', 'gemini-2.0-flash') : 'gemini-2.0-flash';

    if (empty($apiKey)) {
        return "Erro: Chave de API não configurada. Por favor, configure a chave da API nas configurações do sistema.";
    }

    $promptSistema = gerarPromptSistema();
    $contextoTexto = formatarContextoParaIA($contextoFinanceiro);
    
    $mensagemCompleta = $contextoTexto . "\n\n=== MENSAGEM DO USUÁRIO ===\n" . $mensagemUsuario;

    if ($provedor === 'gemini') {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key={$apiKey}";
        
        $contents = [];
        foreach ($historicoConversa as $msg) {
            $contents[] = [
                'role' => $msg['papel'] == 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['conteudo']]]
            ];
        }
        
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $mensagemCompleta]]
        ];

        $data = [
            'system_instruction' => [
                'parts' => [['text' => $promptSistema]]
            ],
            'contents' => $contents
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return "Erro ao conectar com a API do Gemini.";
        }
        
        $response = json_decode($result, true);
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        } else {
            return "Erro na resposta da API: " . print_r($response, true);
        }

    } elseif ($provedor === 'openai') {
        $url = "https://api.openai.com/v1/chat/completions";
        
        $messages = [];
        $messages[] = [
            'role' => 'system',
            'content' => $promptSistema
        ];
        
        foreach ($historicoConversa as $msg) {
            $messages[] = [
                'role' => $msg['papel'] == 'user' ? 'user' : 'assistant',
                'content' => $msg['conteudo']
            ];
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $mensagemCompleta
        ];

        $data = [
            'model' => $modelo,
            'messages' => $messages
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return "Erro ao conectar com a API da OpenAI.";
        }
        
        $response = json_decode($result, true);
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        } else {
            return "Erro na resposta da API: " . print_r($response, true);
        }
    }

    return "Provedor de IA desconhecido ou não suportado.";
}

function gerarAlertasInteligentes($perfilId) {
    $mes = date('m');
    $ano = date('Y');
    
    $contexto = coletarContextoFinanceiro($perfilId, $mes, $ano);
    $alertas = [];
    
    $renda = $contexto['resumo']['total_entradas'] ?? 0;
    $despesas = $contexto['resumo']['total_saidas'] ?? 0;
    
    // Alerta de Saldo Negativo
    if ($despesas > $renda && $renda > 0) {
        $diferenca = $despesas - $renda;
        $diferencaFormatada = number_format($diferenca, 2, ',', '.');
        $alertas[] = [
            'tipo' => 'danger',
            'titulo' => 'Mês no vermelho',
            'mensagem' => "Percebi que suas despesas ficaram R$ {$diferencaFormatada} acima da sua renda este mês. Posso analisar seus gastos e montar um plano para você voltar ao positivo.",
            'icone' => 'fas fa-exclamation-triangle',
            'cor' => '#ef4444'
        ];
    }
    
    // Alerta de Orçamento Comprometido
    if ($renda > 0) {
        $percentual = ($despesas / $renda) * 100;
        $diasNoMes = cal_days_in_month(CAL_GREGORIAN, (int)$mes, (int)$ano);
        $diaAtual = (int)date('d');
        $diasRestantes = $diasNoMes - $diaAtual;
        
        if ($percentual > 80 && $diasRestantes > 5) {
            $pctFmt = number_format($percentual, 1, ',', '.');
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Orçamento comprometido',
                'mensagem' => "Atenção: você já utilizou {$pctFmt}% da sua renda e ainda faltam {$diasRestantes} dias para terminar o mês. Quer que eu te ajude a planejar o restante do mês?",
                'icone' => 'fas fa-wallet',
                'cor' => '#f59e0b'
            ];
        }
    }
    
    // Projeção de Gastos
    if (isset($contexto['projecao']) && $contexto['projecao']['dias_restantes'] > 0 && $contexto['projecao']['gasto_projetado'] > $renda && $renda > 0) {
        $mdFmt = number_format($contexto['projecao']['media_diaria'], 2, ',', '.');
        $gpFmt = number_format($contexto['projecao']['gasto_projetado'], 2, ',', '.');
        $limFmt = number_format($contexto['projecao']['limite_diario_positivo'], 2, ',', '.');
        $alertas[] = [
            'tipo' => 'warning',
            'titulo' => 'Projeção de gastos elevada',
            'mensagem' => "Mantendo seu ritmo atual de R$ {$mdFmt}/dia, você pode terminar o mês gastando aproximadamente R$ {$gpFmt}. Para fechar no positivo, tente limitar os próximos gastos a R$ {$limFmt} por dia.",
            'icone' => 'fas fa-chart-line',
            'cor' => '#f59e0b'
        ];
    }
    
    // Comparação de Categorias
    if (!empty($contexto['comparacao_categorias'])) {
        foreach ($contexto['comparacao_categorias'] as $comp) {
            if ($comp['variacao'] > 30) {
                $vAnt = number_format($comp['anterior'], 2, ',', '.');
                $vAtu = number_format($comp['atual'], 2, ',', '.');
                $alertas[] = [
                    'tipo' => 'info',
                    'titulo' => "Gastos aumentando em {$comp['categoria']}",
                    'mensagem' => "Seu gasto com {$comp['categoria']} passou de R$ {$vAnt} para R$ {$vAtu}, um aumento de {$comp['variacao']}%.",
                    'icone' => 'fas fa-arrow-trend-up',
                    'cor' => '#f97316'
                ];
                break; // Only show one to avoid flooding
            }
        }
    }
    
    // Alerta de Pequenos Gastos
    if (isset($contexto['gastos_pequenos']) && $contexto['gastos_pequenos']['percentual_renda'] > 10) {
        $gp = $contexto['gastos_pequenos'];
        $valFmt = number_format($gp['total'], 2, ',', '.');
        $pctFmt = number_format($gp['percentual_renda'], 1, ',', '.');
        $alertas[] = [
            'tipo' => 'info',
            'titulo' => 'Pequenos gastos acumulados',
            'mensagem' => "Você teve {$gp['quantidade']} compras pequenas (abaixo de R$ 50) que somaram R$ {$valFmt}. Juntas representam {$pctFmt}% da sua renda. Posso mostrar onde elas estão concentradas.",
            'icone' => 'fas fa-search-dollar',
            'cor' => '#3b82f6'
        ];
    }
    
    // Alerta de Cartão de Crédito
    if (isset($contexto['cartao_credito']) && $contexto['cartao_credito']['percentual_renda'] > 40) {
        $pctFmt = number_format($contexto['cartao_credito']['percentual_renda'], 1, ',', '.');
        $alertas[] = [
            'tipo' => 'warning',
            'titulo' => 'Uso alto do cartão',
            'mensagem' => "Seu cartão de crédito está comprometendo {$pctFmt}% da sua renda este mês. Quer que eu detalhe os gastos do cartão?",
            'icone' => 'fas fa-credit-card',
            'cor' => '#f59e0b'
        ];
    }
    
    // Alerta de Poupança (Saques > Depósitos)
    if (isset($contexto['resumo_poupanca'])) {
        $qtdDepositos = $contexto['resumo_poupanca']['qtd_depositos'] ?? 0;
        $qtdRetiradas = $contexto['resumo_poupanca']['qtd_retiradas'] ?? 0;
        
        if ($qtdRetiradas > $qtdDepositos && $qtdRetiradas > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Retiradas da poupança',
                'mensagem' => "Notei que você fez mais retiradas do que depósitos na sua poupança este mês. Quer ajuda para reequilibrar seu orçamento e voltar a guardar?",
                'icone' => 'fas fa-piggy-bank',
                'cor' => '#f59e0b'
            ];
        } elseif ($contexto['poupanca_status'] === 'parada') {
            $alertas[] = [
                'tipo' => 'info',
                'titulo' => 'Poupança parada',
                'mensagem' => "Sua poupança não teve movimentação recente. Quer que eu ajude a encontrar um valor para começar a guardar, sem apertar o orçamento?",
                'icone' => 'fas fa-piggy-bank',
                'cor' => '#3b82f6'
            ];
        }
    }
    
    return $alertas;
}

function gerarAnaliseAutomatica($perfilId) {
    $mes = date('m');
    $ano = date('Y');
    
    $contexto = coletarContextoFinanceiro($perfilId, $mes, $ano);
    
    $renda = $contexto['resumo']['total_entradas'] ?? 0;
    $despesas = $contexto['resumo']['total_saidas'] ?? 0;
    
    $situacao = 'neutra';
    if ($renda > 0) {
        if ($despesas > $renda) {
            $situacao = 'negativa';
        } elseif (($despesas / $renda) < 0.8) {
            $situacao = 'positiva';
        }
    }
    
    $resumo = '';
    $mensagem_proativa = '';
    $dica_principal = '';
    
    $economia = $contexto['classificacao_gastos'] ?? [];
    $economia_total = $economia['total_economia_possivel'] ?? 0;
    $economia_fmt = number_format($economia_total, 2, ',', '.');
    
    $top_gastos = array_slice($economia['economia_potencial'] ?? [], 0, 3);
    $top_categorias_nomes = array_map(function($g) { return $g['categoria']; }, $top_gastos);
    $categorias_str = implode(', ', $top_categorias_nomes);
    
    if ($situacao === 'negativa') {
        $diferenca = $despesas - $renda;
        $dif_fmt = number_format($diferenca, 2, ',', '.');
        $resumo = "Orçamento no vermelho";
        $mensagem_proativa = "Percebi que este mês suas despesas estão cerca de R$ {$dif_fmt} acima da sua renda. Analisei seus gastos e encontrei alguns pontos onde talvez possamos economizar, principalmente em: {$categorias_str}. Se conseguirmos reduzir parte desses gastos, podemos recuperar aproximadamente R$ {$economia_fmt} por mês. Quer que eu monte um plano para você voltar ao positivo?";
        
        if (!empty($contexto['comparacao_categorias'])) {
            $aumentos = [];
            foreach (array_slice($contexto['comparacao_categorias'], 0, 2) as $comp) {
                if ($comp['variacao'] > 20) {
                    $aumentos[] = "{$comp['categoria']} (+{$comp['variacao']}%)";
                }
            }
            if (!empty($aumentos)) {
                $mensagem_proativa .= " Notei também aumentos significativos em: " . implode(', ', $aumentos) . ".";
            }
        }
        
        $dica_principal = "Evite novos gastos não essenciais até fechar o mês.";
    } elseif ($situacao === 'positiva') {
        $sobra = $renda - $despesas;
        $sobra_fmt = number_format($sobra, 2, ',', '.');
        $resumo = "Orçamento saudável";
        $mensagem_proativa = "Suas contas estão equilibradas este mês! Você tem R$ {$sobra_fmt} disponíveis depois das despesas. Quer que eu te ajude a decidir quanto guardar na Poupança ou investir nas suas metas?";
        $dica_principal = "Aproveite para transferir uma parte para a poupança.";
    } else {
        $resumo = "Orçamento no limite";
        $diasNoMes = cal_days_in_month(CAL_GREGORIAN, (int)$mes, (int)$ano);
        $diaAtual = (int)date('d');
        $diasRestantes = $diasNoMes - $diaAtual;
        
        if ($renda > 0) {
            $pct = number_format(($despesas / $renda) * 100, 1, ',', '.');
        } else {
            $pct = 0;
        }
        $mensagem_proativa = "Atenção: você já utilizou {$pct}% da sua renda e ainda faltam {$diasRestantes} dias para terminar o mês. Quer que eu analise onde dá para reduzir os gastos?";
        
        if (isset($contexto['projecao']) && $contexto['projecao']['gasto_projetado'] > $renda && $renda > 0) {
            $proj_fmt = number_format($contexto['projecao']['gasto_projetado'], 2, ',', '.');
            $lim_fmt = number_format($contexto['projecao']['limite_diario_positivo'], 2, ',', '.');
            $mensagem_proativa .= " Mantendo o ritmo atual, sua projeção de gastos é de R$ {$proj_fmt}. Para fechar no positivo, tente gastar no máximo R$ {$lim_fmt} por dia.";
        }
        
        if (!empty($contexto['comparacao_categorias'])) {
            $aumentos = [];
            foreach (array_slice($contexto['comparacao_categorias'], 0, 2) as $comp) {
                if ($comp['variacao'] > 20) {
                    $aumentos[] = "{$comp['categoria']} (+{$comp['variacao']}%)";
                }
            }
            if (!empty($aumentos)) {
                $mensagem_proativa .= " Fique de olho: " . implode(' e ', $aumentos) . " subiram bastante este mês.";
            }
        }
        
        $dica_principal = "Controle rigorosamente os gastos diários.";
    }
    
    return [
        'situacao' => $situacao,
        'resumo' => $resumo,
        'mensagem_proativa' => $mensagem_proativa,
        'alertas' => gerarAlertasInteligentes($perfilId),
        'dica_principal' => $dica_principal,
        'economia_potencial' => $economia_total,
        'economia_detalhada' => $economia['economia_potencial'] ?? [],
        'top_gastos_redutiveis' => $top_gastos
    ];
}

function gerarMensagemProativaDashboard($perfilId = null) {
    if (!$perfilId) {
        $perfilId = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
    }
    
    $analise = gerarAnaliseAutomatica($perfilId);
    
    $tipo_map = [
        'negativa' => 'negativo',
        'neutra' => 'alerta',
        'positiva' => 'positivo'
    ];
    
    $categorias_destaque = [];
    if (!empty($analise['top_gastos_redutiveis'])) {
        foreach ($analise['top_gastos_redutiveis'] as $gasto) {
            $categorias_destaque[$gasto['categoria']] = $gasto['economia'];
        }
    }
    
    return [
        'mensagem' => $analise['mensagem_proativa'],
        'tipo' => $tipo_map[$analise['situacao']] ?? 'neutro',
        'acao_sugerida' => $analise['situacao'] === 'positiva' ? 'Guardar ou investir?' : 'Quer que eu monte um plano?',
        'categorias_destaque' => $categorias_destaque,
        'economia_estimada' => $analise['economia_potencial']
    ];
}
