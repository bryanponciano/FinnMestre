<?php
/**
 * =====================================================
 * FinnMestre - Sistema de Internacionalização (i18n)
 * Traduções e configurações de moeda/idioma
 * =====================================================
 */

// Moedas suportadas
function getMoedasDisponiveis()
{
    return [
        'BRL' => ['simbolo' => 'R$', 'nome' => 'Real Brasileiro', 'decimal' => ',', 'milhares' => '.'],
        'USD' => ['simbolo' => '$', 'nome' => 'US Dollar', 'decimal' => '.', 'milhares' => ','],
        'EUR' => ['simbolo' => '€', 'nome' => 'Euro', 'decimal' => ',', 'milhares' => '.']
    ];
}

// Idiomas suportados
function getIdiomasDisponiveis()
{
    return [
        'pt' => ['nome' => 'Português', 'bandeira' => '🇧🇷'],
        'en' => ['nome' => 'English', 'bandeira' => '🇺🇸'],
        'es' => ['nome' => 'Español', 'bandeira' => '🇪🇸']
    ];
}

// Obter moeda atual
function getMoedaAtual()
{
    $moeda = $_SESSION['moeda'] ?? obterConfiguracao('moeda', 'BRL');
    $moedas = getMoedasDisponiveis();
    return $moedas[$moeda] ?? $moedas['BRL'];
}

// Obter código da moeda
function getCodigoMoeda()
{
    return $_SESSION['moeda'] ?? obterConfiguracao('moeda', 'BRL');
}

// Obter idioma atual
function getIdiomaAtual()
{
    return $_SESSION['idioma'] ?? obterConfiguracao('idioma', 'pt');
}

// Formatar moeda com configuração atual
function formatarMoeda($valor)
{
    $moeda = getMoedaAtual();
    $valor = floatval($valor);

    $formatado = number_format(
        abs($valor),
        2,
        $moeda['decimal'],
        $moeda['milhares']
    );

    $prefixo = $valor < 0 ? '-' : '';
    return $prefixo . $moeda['simbolo'] . ' ' . $formatado;
}

// Traduções
function getTraducoes($idioma = null)
{
    $idioma = $idioma ?: getIdiomaAtual();

    $traducoes = [
        'pt' => [
            // Menu/Navegação
            'dashboard' => 'Dashboard',
            'transacoes' => 'Transações',
            'contas' => 'Contas',
            'metas' => 'Metas',
            'relatorios' => 'Relatórios',
            'configuracoes' => 'Configurações',
            'perfis' => 'Perfis',
            'sair' => 'Sair',
            'FinnMestre' => 'FinnMestre',
            'controle_financeiro' => 'Controle Financeiro Inteligente',

            // Dashboard
            'bem_vindo' => 'Bem-vindo ao FinnMestre',
            'resumo_mensal' => 'Resumo Mensal',
            'entradas' => 'Entradas',
            'saidas' => 'Saídas',
            'saldo' => 'Saldo',
            'saldo_mes' => 'Saldo do Mês',
            'patrimonio' => 'Patrimônio',
            'patrimonio_total' => 'Patrimônio Total',
            'ultimas_transacoes' => 'Últimas Transações',
            'gastos_categoria' => 'Gastos por Categoria',
            'gastos_diarios' => 'Gastos Diários',
            'entradas_mes' => 'Entradas do Mês',
            'saidas_mes' => 'Saídas do Mês',
            'ver_todas' => 'Ver Todas',
            'sem_transacoes' => 'Nenhuma transação registrada',
            'adicione_primeira' => 'Adicione sua primeira transação',
            'dia_do_mes' => 'Dia do Mês',
            'valor_reais' => 'Valor (R$)',
            'gasto_dia' => 'Gasto do Dia',

            // Transações
            'nova_transacao' => 'Nova Transação',
            'editar_transacao' => 'Editar Transação',
            'lista_transacoes' => 'Lista de Transações',
            'filtrar' => 'Filtrar',
            'mes' => 'Mês',
            'ano' => 'Ano',
            'todos' => 'Todos',
            'todas' => 'Todas',
            'transferencia' => 'Transferência',
            'transferencias' => 'Transferências',
            'parcelas' => 'Parcelas',
            'a_vista' => 'À vista',
            'conta_destino' => 'Conta Destino',
            'sem_categoria' => 'Sem categoria',
            'observacao' => 'Observação',
            'notas_adicionais' => 'Notas adicionais (opcional)',
            'total_entradas' => 'Total Entradas',
            'total_saidas' => 'Total Saídas',
            'balanco' => 'Balanço',
            'nenhuma_transacao' => 'Nenhuma transação encontrada',
            'ajuste_filtros' => 'Adicione uma nova transação ou ajuste os filtros',

            // Metas
            'nova_meta' => 'Nova Meta',
            'editar_meta' => 'Editar Meta',
            'titulo' => 'Título',
            'valor_objetivo' => 'Valor Objetivo',
            'valor_atual' => 'Valor Atual',
            'data_inicio' => 'Data Início',
            'data_limite' => 'Data Limite',
            'prioridade' => 'Prioridade',
            'alta' => 'Alta',
            'media' => 'Média',
            'baixa' => 'Baixa',
            'cor' => 'Cor',
            'icone' => 'Ícone',
            'contribuir' => 'Contribuir',
            'adicionar_contribuicao' => 'Adicionar Contribuição',
            'remover_valor' => 'Remover Valor',
            'contribuindo_para' => 'Contribuindo para',
            'progresso' => 'Progresso',
            'dias_restantes' => 'dias restantes',
            'sem_prazo' => 'Sem prazo',
            'vencida' => 'Vencida!',
            'dias_para_atingir' => 'dias para atingir',
            'baseado_media' => 'baseado na média de',
            'por_dia' => 'por dia',
            'concluida' => 'Concluída',
            'suas_metas' => 'Suas Metas Financeiras',
            'gerencie_metas' => 'Defina objetivos e acompanhe seu progresso',
            'nenhuma_meta' => 'Nenhuma meta encontrada',
            'crie_primeira_meta' => 'Crie sua primeira meta financeira',

            // Contas
            'nova_conta' => 'Nova Conta',
            'editar_conta' => 'Editar Conta',
            'nome' => 'Nome',
            'tipo_conta' => 'Tipo de Conta',
            'saldo_inicial' => 'Saldo Inicial',
            'banco' => 'Banco',
            'carteira' => 'Carteira',
            'cartao_credito' => 'Cartão de Crédito',
            'investimento' => 'Investimento',
            'poupanca' => 'Poupança',
            'suas_contas' => 'Suas Contas',
            'gerencie_contas' => 'Gerencie suas contas e carteiras',

            // Perfis
            'novo_perfil' => 'Novo Perfil',
            'editar_perfil' => 'Editar Perfil',
            'modo_visualizacao' => 'Modo de Visualização',
            'visualizacao_individual' => 'Visualização Individual',
            'ver_dados_perfil' => 'Ver dados apenas do perfil selecionado',
            'visualizacao_consolidada' => 'Visualização Consolidada',
            'ver_dados_combinados' => 'Ver dados de múltiplos perfis combinados',
            'perfis_consolidados' => 'Perfis consolidados',
            'desativar' => 'Desativar',
            'seus_perfis' => 'Seus Perfis',
            'tipo_perfil' => 'Tipo de Perfil',
            'pessoal' => 'Pessoal',
            'empresarial' => 'Empresarial',
            'familiar' => 'Familiar',
            'ativar_consolidado' => 'Ativar Consolidado',
            'selecione_perfis' => 'Selecione os perfis para ver os dados combinados',

            // Configurações
            'idioma' => 'Idioma',
            'moeda' => 'Moeda',
            'limite_mensal' => 'Limite Mensal de Gastos',
            'finbot_ativo' => 'FinBot Ativo',
            'tema' => 'Tema',
            'preferencias' => 'Preferências',
            'aparencia' => 'Aparência',
            'notificacoes' => 'Notificações',
            'alertas' => 'Alertas',
            'config_gerais' => 'Configurações Gerais',
            'personalize_sistema' => 'Personalize o sistema conforme suas preferências',
            'salvar_config' => 'Salvar Configurações',
            'config_salvas' => 'Configurações salvas com sucesso!',

            // Formulários gerais
            'salvar' => 'Salvar',
            'cancelar' => 'Cancelar',
            'excluir' => 'Excluir',
            'editar' => 'Editar',
            'adicionar' => 'Adicionar',
            'remover' => 'Remover',
            'novo' => 'Novo',
            'nova' => 'Nova',
            'descricao' => 'Descrição',
            'valor' => 'Valor',
            'data' => 'Data',
            'categoria' => 'Categoria',
            'conta' => 'Conta',
            'tipo' => 'Tipo',
            'entrada' => 'Entrada',
            'saida' => 'Saída',
            'selecione' => 'Selecione...',
            'opcional' => 'opcional',
            'obrigatorio' => 'obrigatório',
            'acoes' => 'Ações',

            // Mensagens
            'sucesso' => 'Sucesso!',
            'erro' => 'Erro!',
            'confirmar_exclusao' => 'Confirmar exclusão?',
            'nenhum_registro' => 'Nenhum registro encontrado',
            'operacao_sucesso' => 'Operação realizada com sucesso!',
            'erro_operacao' => 'Erro ao realizar operação',
            'tem_certeza' => 'Tem certeza?',
            'acao_irreversivel' => 'Esta ação não pode ser desfeita',

            // Meses
            'janeiro' => 'Janeiro',
            'fevereiro' => 'Fevereiro',
            'marco' => 'Março',
            'abril' => 'Abril',
            'maio' => 'Maio',
            'junho' => 'Junho',
            'julho' => 'Julho',
            'agosto' => 'Agosto',
            'setembro' => 'Setembro',
            'outubro' => 'Outubro',
            'novembro' => 'Novembro',
            'dezembro' => 'Dezembro',

            // Alertas FinBot
            'parabens' => 'Parabéns!',
            'atencao' => 'Atenção!',
            'cuidado' => 'Cuidado!',
            'dica' => 'Dica'
        ],

        'en' => [
            // Menu/Navigation
            'dashboard' => 'Dashboard',
            'transacoes' => 'Transactions',
            'contas' => 'Accounts',
            'metas' => 'Goals',
            'relatorios' => 'Reports',
            'configuracoes' => 'Settings',
            'perfis' => 'Profiles',
            'sair' => 'Logout',
            'FinnMestre' => 'FinnMestre',
            'controle_financeiro' => 'Smart Financial Control',

            // Dashboard
            'bem_vindo' => 'Welcome to FinnMestre',
            'resumo_mensal' => 'Monthly Summary',
            'entradas' => 'Income',
            'saidas' => 'Expenses',
            'saldo' => 'Balance',
            'saldo_mes' => 'Monthly Balance',
            'patrimonio' => 'Net Worth',
            'patrimonio_total' => 'Total Net Worth',
            'ultimas_transacoes' => 'Recent Transactions',
            'gastos_categoria' => 'Spending by Category',
            'gastos_diarios' => 'Daily Spending',
            'entradas_mes' => 'Monthly Income',
            'saidas_mes' => 'Monthly Expenses',
            'ver_todas' => 'View All',
            'sem_transacoes' => 'No transactions recorded',
            'adicione_primeira' => 'Add your first transaction',
            'dia_do_mes' => 'Day of Month',
            'valor_reais' => 'Amount',
            'gasto_dia' => 'Daily Spending',

            // Transactions
            'nova_transacao' => 'New Transaction',
            'editar_transacao' => 'Edit Transaction',
            'lista_transacoes' => 'Transaction List',
            'filtrar' => 'Filter',
            'mes' => 'Month',
            'ano' => 'Year',
            'todos' => 'All',
            'todas' => 'All',
            'transferencia' => 'Transfer',
            'transferencias' => 'Transfers',
            'parcelas' => 'Installments',
            'a_vista' => 'One-time',
            'conta_destino' => 'Destination Account',
            'sem_categoria' => 'No category',
            'observacao' => 'Notes',
            'notas_adicionais' => 'Additional notes (optional)',
            'total_entradas' => 'Total Income',
            'total_saidas' => 'Total Expenses',
            'balanco' => 'Balance',
            'nenhuma_transacao' => 'No transactions found',
            'ajuste_filtros' => 'Add a new transaction or adjust filters',

            // Goals
            'nova_meta' => 'New Goal',
            'editar_meta' => 'Edit Goal',
            'titulo' => 'Title',
            'valor_objetivo' => 'Target Amount',
            'valor_atual' => 'Current Amount',
            'data_inicio' => 'Start Date',
            'data_limite' => 'Deadline',
            'prioridade' => 'Priority',
            'alta' => 'High',
            'media' => 'Medium',
            'baixa' => 'Low',
            'cor' => 'Color',
            'icone' => 'Icon',
            'contribuir' => 'Contribute',
            'adicionar_contribuicao' => 'Add Contribution',
            'remover_valor' => 'Remove Amount',
            'contribuindo_para' => 'Contributing to',
            'progresso' => 'Progress',
            'dias_restantes' => 'days remaining',
            'sem_prazo' => 'No deadline',
            'vencida' => 'Overdue!',
            'dias_para_atingir' => 'days to reach',
            'baseado_media' => 'based on average of',
            'por_dia' => 'per day',
            'concluida' => 'Completed',
            'suas_metas' => 'Your Financial Goals',
            'gerencie_metas' => 'Set goals and track your progress',
            'nenhuma_meta' => 'No goals found',
            'crie_primeira_meta' => 'Create your first financial goal',

            // Accounts
            'nova_conta' => 'New Account',
            'editar_conta' => 'Edit Account',
            'nome' => 'Name',
            'tipo_conta' => 'Account Type',
            'saldo_inicial' => 'Initial Balance',
            'banco' => 'Bank',
            'carteira' => 'Wallet',
            'cartao_credito' => 'Credit Card',
            'investimento' => 'Investment',
            'poupanca' => 'Savings',
            'suas_contas' => 'Your Accounts',
            'gerencie_contas' => 'Manage your accounts and wallets',

            // Profiles
            'novo_perfil' => 'New Profile',
            'editar_perfil' => 'Edit Profile',
            'modo_visualizacao' => 'View Mode',
            'visualizacao_individual' => 'Individual View',
            'ver_dados_perfil' => 'View data from selected profile only',
            'visualizacao_consolidada' => 'Consolidated View',
            'ver_dados_combinados' => 'View combined data from multiple profiles',
            'perfis_consolidados' => 'Consolidated profiles',
            'desativar' => 'Disable',
            'seus_perfis' => 'Your Profiles',
            'tipo_perfil' => 'Profile Type',
            'pessoal' => 'Personal',
            'empresarial' => 'Business',
            'familiar' => 'Family',
            'ativar_consolidado' => 'Enable Consolidated',
            'selecione_perfis' => 'Select profiles to view combined data',

            // Settings
            'idioma' => 'Language',
            'moeda' => 'Currency',
            'limite_mensal' => 'Monthly Spending Limit',
            'finbot_ativo' => 'FinBot Active',
            'tema' => 'Theme',
            'preferencias' => 'Preferences',
            'aparencia' => 'Appearance',
            'notificacoes' => 'Notifications',
            'alertas' => 'Alerts',
            'config_gerais' => 'General Settings',
            'personalize_sistema' => 'Customize the system according to your preferences',
            'salvar_config' => 'Save Settings',
            'config_salvas' => 'Settings saved successfully!',

            // General forms
            'salvar' => 'Save',
            'cancelar' => 'Cancel',
            'excluir' => 'Delete',
            'editar' => 'Edit',
            'adicionar' => 'Add',
            'remover' => 'Remove',
            'novo' => 'New',
            'nova' => 'New',
            'descricao' => 'Description',
            'valor' => 'Amount',
            'data' => 'Date',
            'categoria' => 'Category',
            'conta' => 'Account',
            'tipo' => 'Type',
            'entrada' => 'Income',
            'saida' => 'Expense',
            'selecione' => 'Select...',
            'opcional' => 'optional',
            'obrigatorio' => 'required',
            'acoes' => 'Actions',

            // Messages
            'sucesso' => 'Success!',
            'erro' => 'Error!',
            'confirmar_exclusao' => 'Confirm deletion?',
            'nenhum_registro' => 'No records found',
            'operacao_sucesso' => 'Operation completed successfully!',
            'erro_operacao' => 'Error performing operation',
            'tem_certeza' => 'Are you sure?',
            'acao_irreversivel' => 'This action cannot be undone',

            // Months
            'janeiro' => 'January',
            'fevereiro' => 'February',
            'marco' => 'March',
            'abril' => 'April',
            'maio' => 'May',
            'junho' => 'June',
            'julho' => 'July',
            'agosto' => 'August',
            'setembro' => 'September',
            'outubro' => 'October',
            'novembro' => 'November',
            'dezembro' => 'December',

            // FinBot alerts
            'parabens' => 'Congratulations!',
            'atencao' => 'Attention!',
            'cuidado' => 'Warning!',
            'dica' => 'Tip'
        ],

        'es' => [
            // Menú/Navegación
            'dashboard' => 'Panel',
            'transacoes' => 'Transacciones',
            'contas' => 'Cuentas',
            'metas' => 'Metas',
            'relatorios' => 'Informes',
            'configuracoes' => 'Configuración',
            'perfis' => 'Perfiles',
            'sair' => 'Salir',
            'FinnMestre' => 'FinnMestre',
            'controle_financeiro' => 'Control Financiero Inteligente',

            // Dashboard
            'bem_vindo' => 'Bienvenido a FinnMestre',
            'resumo_mensal' => 'Resumen Mensual',
            'entradas' => 'Ingresos',
            'saidas' => 'Gastos',
            'saldo' => 'Saldo',
            'saldo_mes' => 'Saldo del Mes',
            'patrimonio' => 'Patrimonio',
            'patrimonio_total' => 'Patrimonio Total',
            'ultimas_transacoes' => 'Últimas Transacciones',
            'gastos_categoria' => 'Gastos por Categoría',
            'gastos_diarios' => 'Gastos Diarios',
            'entradas_mes' => 'Ingresos del Mes',
            'saidas_mes' => 'Gastos del Mes',
            'ver_todas' => 'Ver Todos',
            'sem_transacoes' => 'Sin transacciones registradas',
            'adicione_primeira' => 'Agregue su primera transacción',
            'dia_do_mes' => 'Día del Mes',
            'valor_reais' => 'Valor',
            'gasto_dia' => 'Gasto del Día',

            // Transacciones
            'nova_transacao' => 'Nueva Transacción',
            'editar_transacao' => 'Editar Transacción',
            'lista_transacoes' => 'Lista de Transacciones',
            'filtrar' => 'Filtrar',
            'mes' => 'Mes',
            'ano' => 'Año',
            'todos' => 'Todos',
            'todas' => 'Todas',
            'transferencia' => 'Transferencia',
            'transferencias' => 'Transferencias',
            'parcelas' => 'Cuotas',
            'a_vista' => 'Al contado',
            'conta_destino' => 'Cuenta Destino',
            'sem_categoria' => 'Sin categoría',
            'observacao' => 'Observación',
            'notas_adicionais' => 'Notas adicionales (opcional)',
            'total_entradas' => 'Total Ingresos',
            'total_saidas' => 'Total Gastos',
            'balanco' => 'Balance',
            'nenhuma_transacao' => 'No se encontraron transacciones',
            'ajuste_filtros' => 'Agregue una nueva transacción o ajuste los filtros',

            // Metas
            'nova_meta' => 'Nueva Meta',
            'editar_meta' => 'Editar Meta',
            'titulo' => 'Título',
            'valor_objetivo' => 'Valor Objetivo',
            'valor_atual' => 'Valor Actual',
            'data_inicio' => 'Fecha de Inicio',
            'data_limite' => 'Fecha Límite',
            'prioridade' => 'Prioridad',
            'alta' => 'Alta',
            'media' => 'Media',
            'baixa' => 'Baja',
            'cor' => 'Color',
            'icone' => 'Ícono',
            'contribuir' => 'Contribuir',
            'adicionar_contribuicao' => 'Agregar Contribución',
            'remover_valor' => 'Remover Valor',
            'contribuindo_para' => 'Contribuyendo para',
            'progresso' => 'Progreso',
            'dias_restantes' => 'días restantes',
            'sem_prazo' => 'Sin plazo',
            'vencida' => '¡Vencida!',
            'dias_para_atingir' => 'días para alcanzar',
            'baseado_media' => 'basado en promedio de',
            'por_dia' => 'por día',
            'concluida' => 'Completada',
            'suas_metas' => 'Tus Metas Financieras',
            'gerencie_metas' => 'Define objetivos y sigue tu progreso',
            'nenhuma_meta' => 'No se encontraron metas',
            'crie_primeira_meta' => 'Crea tu primera meta financiera',

            // Cuentas
            'nova_conta' => 'Nueva Cuenta',
            'editar_conta' => 'Editar Cuenta',
            'nome' => 'Nombre',
            'tipo_conta' => 'Tipo de Cuenta',
            'saldo_inicial' => 'Saldo Inicial',
            'banco' => 'Banco',
            'carteira' => 'Billetera',
            'cartao_credito' => 'Tarjeta de Crédito',
            'investimento' => 'Inversión',
            'poupanca' => 'Ahorro',
            'suas_contas' => 'Tus Cuentas',
            'gerencie_contas' => 'Gestiona tus cuentas y billeteras',

            // Perfiles
            'novo_perfil' => 'Nuevo Perfil',
            'editar_perfil' => 'Editar Perfil',
            'modo_visualizacao' => 'Modo de Visualización',
            'visualizacao_individual' => 'Visualización Individual',
            'ver_dados_perfil' => 'Ver datos solo del perfil seleccionado',
            'visualizacao_consolidada' => 'Visualización Consolidada',
            'ver_dados_combinados' => 'Ver datos combinados de múltiples perfiles',
            'perfis_consolidados' => 'Perfiles consolidados',
            'desativar' => 'Desactivar',
            'seus_perfis' => 'Tus Perfiles',
            'tipo_perfil' => 'Tipo de Perfil',
            'pessoal' => 'Personal',
            'empresarial' => 'Empresarial',
            'familiar' => 'Familiar',
            'ativar_consolidado' => 'Activar Consolidado',
            'selecione_perfis' => 'Seleccione los perfiles para ver datos combinados',

            // Configuración
            'idioma' => 'Idioma',
            'moeda' => 'Moneda',
            'limite_mensal' => 'Límite Mensual de Gastos',
            'finbot_ativo' => 'FinBot Activo',
            'tema' => 'Tema',
            'preferencias' => 'Preferencias',
            'aparencia' => 'Apariencia',
            'notificacoes' => 'Notificaciones',
            'alertas' => 'Alertas',
            'config_gerais' => 'Configuración General',
            'personalize_sistema' => 'Personaliza el sistema según tus preferencias',
            'salvar_config' => 'Guardar Configuración',
            'config_salvas' => '¡Configuración guardada con éxito!',

            // Formularios generales
            'salvar' => 'Guardar',
            'cancelar' => 'Cancelar',
            'excluir' => 'Eliminar',
            'editar' => 'Editar',
            'adicionar' => 'Agregar',
            'remover' => 'Remover',
            'novo' => 'Nuevo',
            'nova' => 'Nueva',
            'descricao' => 'Descripción',
            'valor' => 'Valor',
            'data' => 'Fecha',
            'categoria' => 'Categoría',
            'conta' => 'Cuenta',
            'tipo' => 'Tipo',
            'entrada' => 'Ingreso',
            'saida' => 'Gasto',
            'selecione' => 'Seleccione...',
            'opcional' => 'opcional',
            'obrigatorio' => 'obligatorio',
            'acoes' => 'Acciones',

            // Mensajes
            'sucesso' => '¡Éxito!',
            'erro' => '¡Error!',
            'confirmar_exclusao' => '¿Confirmar eliminación?',
            'nenhum_registro' => 'No se encontraron registros',
            'operacao_sucesso' => '¡Operación realizada con éxito!',
            'erro_operacao' => 'Error al realizar la operación',
            'tem_certeza' => '¿Está seguro?',
            'acao_irreversivel' => 'Esta acción no se puede deshacer',

            // Meses
            'janeiro' => 'Enero',
            'fevereiro' => 'Febrero',
            'marco' => 'Marzo',
            'abril' => 'Abril',
            'maio' => 'Mayo',
            'junho' => 'Junio',
            'julho' => 'Julio',
            'agosto' => 'Agosto',
            'setembro' => 'Septiembre',
            'outubro' => 'Octubre',
            'novembro' => 'Noviembre',
            'dezembro' => 'Diciembre',

            // Alertas FinBot
            'parabens' => '¡Felicitaciones!',
            'atencao' => '¡Atención!',
            'cuidado' => '¡Cuidado!',
            'dica' => 'Consejo'
        ]
    ];

    return $traducoes[$idioma] ?? $traducoes['pt'];
}

// Função de tradução
function __($chave, $idioma = null)
{
    $traducoes = getTraducoes($idioma);
    return $traducoes[$chave] ?? $chave;
}

// Definir idioma
function setIdioma($idioma)
{
    global $pdo;
    $idiomas = getIdiomasDisponiveis();

    if (isset($idiomas[$idioma])) {
        $_SESSION['idioma'] = $idioma;
        salvarConfiguracao('idioma', $idioma);
        return true;
    }
    return false;
}

// Definir moeda
function setMoeda($moeda)
{
    global $pdo;
    $moedas = getMoedasDisponiveis();

    if (isset($moedas[$moeda])) {
        $_SESSION['moeda'] = $moeda;
        salvarConfiguracao('moeda', $moeda);
        return true;
    }
    return false;
}
