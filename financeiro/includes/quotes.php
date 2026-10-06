<?php
/**
 * =====================================================
 * FinnMestre - Frases Motivacionais Diárias
 * Pool de 60+ frases rotacionadas por data
 * =====================================================
 */

/**
 * Pool de frases motivacionais financeiras
 */
function getFrasesMotivacionais()
{
    return [
        // Disciplina e Controle
        "Disciplina hoje compra liberdade amanhã.",
        "O controle do seu dinheiro é o controle da sua vida.",
        "Pequenas economias constantes vencem grandes gastos impulsivos.",
        "Quem controla o centavo, domina o milhão.",
        "Cada real economizado é um passo para a independência.",
        "A liberdade financeira começa com um 'não' para gastos desnecessários.",
        "Seu futuro financeiro está nas decisões de hoje.",
        "Controlar gastos não é privar-se, é priorizar.",
        "A riqueza não vem do quanto você ganha, mas do quanto você guarda.",
        "Disciplina financeira é um músculo que fortalece com a prática.",

        // Consciência e Reflexão
        "Cada compra tem um custo: seu tempo de vida.",
        "Antes de comprar, pergunte: isso vale X horas da minha vida?",
        "Gastar menos é valorizar mais o seu próprio trabalho.",
        "O dinheiro que você tem representa horas que você já viveu.",
        "Pense em reais como horas de trabalho — muda tudo.",
        "Consciência financeira é saber o peso real de cada escolha.",
        "Gaste com propósito, não por impulso.",
        "O verdadeiro luxo é não precisar se preocupar com dinheiro.",
        "Cada gasto é uma troca: seu tempo por algo. Vale a pena?",
        "Reflita antes de gastar: necessidade ou desejo?",

        // Metas e Objetivos
        "Metas claras transformam sonhos em planos.",
        "Um objetivo sem prazo é apenas um desejo.",
        "Progresso constante vence velocidade sem direção.",
        "Grandes conquistas começam com pequenos depósitos.",
        "Sua meta financeira merece sua atenção diária.",
        "Foco em metas, não em obstáculos.",
        "Cada aporte é um tijolo na construção do seu futuro.",
        "Celebre os pequenos progressos — eles somam.",
        "A jornada de mil reais começa com o primeiro real.",
        "Paciência e persistência realizam mais que pressa.",

        // Hábitos e Rotina
        "Bons hábitos financeiros são invisíveis, mas seus resultados não.",
        "Automatize o bom comportamento: pague-se primeiro.",
        "A rotina de controle evita surpresas desagradáveis.",
        "Consistência supera intensidade no longo prazo.",
        "Pequenas ações diárias constroem grandes mudanças.",
        "O hábito de registrar é o primeiro passo para controlar.",
        "Faça do controle financeiro um ritual, não uma obrigação.",
        "Cuide do seu dinheiro como cuida da sua saúde.",
        "Hábitos saudáveis hoje, tranquilidade amanhã.",
        "A excelência financeira é um hábito, não um evento.",

        // Mentalidade e Atitude
        "Rico não é quem tem mais, é quem precisa de menos.",
        "Sua relação com dinheiro reflete sua relação com você mesmo.",
        "Mentalidade de escassez gera escassez; abundância gera abundância.",
        "O dinheiro é uma ferramenta — aprenda a usá-la bem.",
        "Investir em conhecimento financeiro paga os melhores juros.",
        "Você é capaz de construir a vida financeira que deseja.",
        "Assuma o controle ou o dinheiro controlará você.",
        "Prosperidade é escolha, não sorte.",
        "Sua situação financeira atual não define seu futuro.",
        "Acredite no seu potencial de transformação.",

        // Sabedoria e Aprendizado
        "Erros financeiros são lições, não sentenças.",
        "Aprenda com cada gasto — bom ou ruim.",
        "A educação financeira é o melhor investimento.",
        "Quem para de aprender, para de crescer.",
        "O autoconhecimento financeiro é libertador.",
        "Perguntar 'por quê?' antes de gastar é sabedoria.",
        "Cada mês é uma chance de melhorar.",
        "O passado financeiro não define o futuro financeiro.",
        "Simplicidade é a sofisticação suprema — também nas finanças.",
        "Menos é mais: menos gastos, mais liberdade.",

        // Extras
        "Hoje é o melhor dia para começar a mudar.",
        "Seu eu do futuro agradecerá suas escolhas de hoje.",
        "Dinheiro guardado é opção; dinheiro gasto é decisão tomada.",
        "Organize suas finanças, organize sua mente.",
        "Clareza financeira traz paz mental.",
    ];
}

/**
 * Obtém a frase do dia baseada na data
 * Usa a data como seed para garantir mesma frase o dia todo
 */
function getFraseDoDia()
{
    $frases = getFrasesMotivacionais();
    $totalFrases = count($frases);

    // Usar a data como seed
    $dataHoje = date('Y-m-d');
    $seed = crc32($dataHoje);

    // Usar seed para selecionar índice
    mt_srand($seed);
    $indice = mt_rand(0, $totalFrases - 1);
    mt_srand(); // Reset seed

    return $frases[$indice];
}

/**
 * Obtém saudação baseada no horário
 */
function getSaudacao($nome = '')
{
    $hora = (int) date('H');

    if ($hora >= 5 && $hora < 12) {
        $saudacao = 'Bom dia';
    } elseif ($hora >= 12 && $hora < 18) {
        $saudacao = 'Boa tarde';
    } else {
        $saudacao = 'Boa noite';
    }

    if (!empty($nome)) {
        $saudacao .= ", {$nome}";
    }

    return $saudacao . '!';
}

/**
 * Obtém emoji baseado no horário/contexto
 */
function getEmojiSaudacao()
{
    $hora = (int) date('H');

    if ($hora >= 5 && $hora < 12) {
        return '☀️';
    } elseif ($hora >= 12 && $hora < 18) {
        return '🌤️';
    } else {
        return '🌙';
    }
}
