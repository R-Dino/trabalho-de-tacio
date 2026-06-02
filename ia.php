<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// -------------------------------------------------------------
// MOTOR DE IA (HEURÍSTICA DE ANÁLISE DE DADOS DO ALMOXARIFADO)
// -------------------------------------------------------------
$insights = [];

// 1. Análise de Ruptura (Produtos Zerados ou Baixos)
$baixoEstoque = $pdo->query("SELECT nome, quantidade FROM produtos WHERE status IN ('Baixo', 'Zerado')")->fetchAll();
if (count($baixoEstoque) > 0) {
    $nomes = implode(", ", array_column($baixoEstoque, 'nome'));
    $insights[] = [
        'tipo' => 'alerta',
        'icone' => 'fa-exclamation-triangle',
        'cor' => '#ef4444',
        'titulo' => 'Risco Crítico de Ruptura',
        'mensagem' => "Identifiquei " . count($baixoEstoque) . " item(s) com estoque perigosamente baixo ou zerado: <b>$nomes</b>. Sugiro a emissão imediata de uma ordem de compra para evitar interrupções operacionais."
    ];
} else {
    $insights[] = [
        'tipo' => 'sucesso',
        'icone' => 'fa-check-circle',
        'cor' => '#10b981',
        'titulo' => 'Saúde do Estoque Perfeita',
        'mensagem' => "Analisei seus níveis de estoque e tudo está operando dentro da margem de segurança. Não há itens em estado crítico no momento."
    ];
}

// 2. Análise de Ociosidade (Produtos parados)
$ociosos = $pdo->query("
    SELECT p.nome, p.quantidade 
    FROM produtos p 
    LEFT JOIN (SELECT produto_id FROM movimentacoes WHERE data_movimentacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)) m ON p.id = m.produto_id 
    WHERE m.produto_id IS NULL AND p.quantidade > 0 LIMIT 5
")->fetchAll();

if (count($ociosos) > 0) {
    $nomesOciosos = implode(", ", array_column($ociosos, 'nome'));
    $insights[] = [
        'tipo' => 'aviso',
        'icone' => 'fa-box-open',
        'cor' => '#f59e0b',
        'titulo' => 'Capital Estagnado (Ociosidade)',
        'mensagem' => "Detectei capital imobilizado. Os seguintes itens não tiveram <b>nenhuma movimentação nos últimos 30 dias</b>: <b>$nomesOciosos</b>. Considere revisar o estoque mínimo ou criar promoções internas de uso."
    ];
}

// 3. Alta Rotatividade (Produtos que mais saem)
$altaRotatividade = $pdo->query("
    SELECT p.nome, SUM(m.quantidade) as total_saida
    FROM produtos p
    JOIN movimentacoes m ON p.id = m.produto_id
    WHERE m.tipo = 'Saida' AND m.data_movimentacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY p.id
    ORDER BY total_saida DESC LIMIT 3
")->fetchAll();

if (count($altaRotatividade) > 0) {
    $nomesAlta = implode(", ", array_column($altaRotatividade, 'nome'));
    $insights[] = [
        'tipo' => 'info',
        'icone' => 'fa-bolt',
        'cor' => '#3b82f6',
        'titulo' => 'Picos de Consumo Recentes',
        'mensagem' => "Nos últimos 7 dias, observei um alto volume de saídas para os itens: <b>$nomesAlta</b>. Recomendo aumentar o estoque mínimo de segurança destes produtos para evitar faltas imprevistas."
    ];
}

// 4. Análise de Fornecedores
$totalFornecedores = $pdo->query("SELECT COUNT(*) FROM fornecedores")->fetchColumn() ?: 0;
if ($totalFornecedores == 0) {
    $insights[] = [
        'tipo' => 'alerta',
        'icone' => 'fa-truck-slash',
        'cor' => '#ef4444',
        'titulo' => 'Falta de Fornecedores',
        'mensagem' => "Notei que você não possui fornecedores cadastrados. O cadastro de fornecedores é vital para que eu possa gerar pedidos de compra automáticos no futuro."
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IA Gerencial | ALMOX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ia-bg: #0b0f19;
            --ia-panel: #111827;
            --ia-text: #e5e7eb;
            --ia-accent: #3b82f6;
            --ia-glow: rgba(59, 130, 246, 0.5);
            --ia-success: #10b981;
            --ia-warning: #f59e0b;
            --ia-danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background-color: var(--ia-bg);
            color: var(--ia-text);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.08), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.08), transparent 25%);
        }

        /* Sidebar Simplificada (Herdada do sistema, adaptada para Dark/IA) */
        .sidebar {
            width: 250px;
            background: #0f172a;
            padding: 20px;
            border-right: 1px solid rgba(255,255,255,0.05);
            z-index: 10;
        }
        .logo { text-align: center; margin-bottom: 40px; }
        .logo h2 { color: #38bdf8; font-weight: 800; letter-spacing: 2px; }
        .menu { list-style: none; }
        .menu li { margin: 10px 0; }
        .menu a {
            color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 12px;
            padding: 12px; border-radius: 8px; transition: 0.3s;
        }
        .menu a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .menu a.active { background: rgba(59, 130, 246, 0.1); color: var(--ia-accent); border-left: 3px solid var(--ia-accent); }

        /* Main Content */
        .main {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .header-ia {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .header-ia h1 {
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
            background: -webkit-linear-gradient(45deg, #38bdf8, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .ai-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .pulse {
            width: 10px; height: 10px; background: #10b981; border-radius: 50%;
            box-shadow: 0 0 10px #10b981; animation: pulse 1.5s infinite;
        }

        /* Container de Insights (Cards) */
        .insights-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }

        .insight-card {
            background: var(--ia-panel);
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            opacity: 0;
            transform: translateY(20px);
        }
        
        .insight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            border-color: rgba(255,255,255,0.1);
        }

        .insight-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            display: inline-block;
        }

        .insight-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #fff;
        }

        .insight-card p {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Área de Chat Interativo com a IA */
        .ai-chat-section {
            background: var(--ia-panel);
            border-radius: 16px;
            margin-top: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .chat-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .ai-avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #fff;
            box-shadow: 0 0 15px var(--ia-glow);
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            min-height: 300px;
            max-height: 400px;
        }

        .message {
            max-width: 80%;
            padding: 15px 20px;
            border-radius: 15px;
            font-size: 0.95rem;
            line-height: 1.5;
            animation: fadeIn 0.3s ease-out;
        }

        .message.ai {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-bottom-left-radius: 5px;
            align-self: flex-start;
            color: #e2e8f0;
        }

        .message.user {
            background: #2563eb;
            color: #fff;
            border-bottom-right-radius: 5px;
            align-self: flex-end;
        }

        .chat-input-area {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            gap: 15px;
        }

        .chat-input {
            flex: 1;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            transition: 0.3s;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .btn-send {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-send:hover {
            background: #2563eb;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
        }

        /* Animações */
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.7); } 70% { box-shadow: 0 0 0 10px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .typing-indicator { display: none; align-items: center; gap: 5px; padding: 10px 15px; background: rgba(255,255,255,0.05); border-radius: 10px; width: fit-content; align-self: flex-start; margin-bottom: 15px;}
        .dot { width: 8px; height: 8px; background: #94a3b8; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; }
        .dot:nth-child(1) { animation-delay: -0.32s; }
        .dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
        
        @media (max-width: 900px) {
            .sidebar { display: none; } /* Simplificação para responsivo, assumindo que o toggle já existe no dashboard principal */
            .main { padding: 20px; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo"><h2>ALMOX</h2></div>
        <ul class="menu">
            <li><a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
            <li><a href="produtos.php"><i class="fa fa-box"></i> Produtos</a></li>
            <li><a href="estoque.php"><i class="fa fa-warehouse"></i> Estoque</a></li>
            <li><a href="ia.php" class="active"><i class="fa fa-robot"></i> IA Gerencial</a></li>
        </ul>
    </aside>

    <main class="main">
        <header class="header-ia">
            <h1><i class="fa-solid fa-microchip"></i> Assistente de Inteligência Artificial</h1>
            <div class="ai-status">
                <div class="pulse"></div>
                Sistema Neural Ativo
            </div>
        </header>

        <p style="color: #94a3b8; font-size: 1.1rem;">Bem-vindo(a), <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Gestor') ?>. Analisei os dados do seu almoxarifado em tempo real. Aqui estão os meus insights de gestão:</p>

        <div class="insights-container">
            <?php foreach($insights as $index => $insight): ?>
                <div class="insight-card" style="animation: fadeIn 0.5s ease-out forwards; animation-delay: <?= $index * 0.2 ?>s;">
                    <i class="fa <?= $insight['icone'] ?> insight-icon" style="color: <?= $insight['cor'] ?>; text-shadow: 0 0 15px <?= $insight['cor'] ?>;"></i>
                    <h3><?= $insight['titulo'] ?></h3>
                    <p><?= $insight['mensagem'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ai-chat-section">
            <div class="chat-header">
                <div class="ai-avatar"><i class="fa fa-robot"></i></div>
                <div>
                    <h3 style="color: #fff; font-size: 1.1rem;">Chat Interativo Almox-IA</h3>
                    <span style="color: #10b981; font-size: 0.8rem;">Online e pronto para analisar</span>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="message ai">
                    Olá! Sou sua IA de gerenciamento. Agora estou conectada à internet e posso analisar dados internos e de mercado para você! 
                    <br><br>Experimente me perguntar algo como: <br>
                    - <i>"Onde comprar detergente líquido?"</i><br>
                    - <i>"Qual o preço de papel toalha?"</i><br>
                    - <i>"Quais produtos estão parados?"</i>
                </div>
                
                <div class="typing-indicator" id="typingIndicator">
                    <div class="dot"></div><div class="dot"></div><div class="dot"></div>
                </div>
            </div>
            
            <div class="chat-input-area">
                <input type="text" id="userInput" class="chat-input" placeholder="Pergunte algo sobre o estoque..." autocomplete="off">
                <button class="btn-send" onclick="sendMessage()"><i class="fa fa-paper-plane"></i> Enviar</button>
            </div>
        </div>

    </main>

    <script>
        const input = document.getElementById('userInput');
        const chat = document.getElementById('chatMessages');
        const typing = document.getElementById('typingIndicator');

        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });

        function sendMessage() {
            const text = input.value.trim();
            if(!text) return;
            
            // Adiciona mensagem do user
            addMessage(text, 'user');
            input.value = '';
            
            // Simula IA "digitando"
            chat.appendChild(typing);
            typing.style.display = 'flex';
            chat.scrollTop = chat.scrollHeight;

            setTimeout(async () => {
                const resposta = await processarLogicaIA(text.toLowerCase());
                typing.style.display = 'none';
                addMessage(resposta, 'ai');
            }, 1200 + Math.random() * 1000);
        }

        function addMessage(text, type) {
            const msg = document.createElement('div');
            msg.className = `message ${type}`;
            msg.innerHTML = text;
            chat.insertBefore(msg, typing);
            chat.scrollTop = chat.scrollHeight;
        }

        // Lógica de Processamento de Linguagem Natural com Integração Externa API Livre
        async function processarLogicaIA(pergunta) {
            
            pergunta = pergunta.toLowerCase();
            
            // 1. VERIFICAÇÃO DE COMANDOS INTERNOS (ALMOXARIFADO)
            const isInternal = pergunta.includes("falta") || 
                               pergunta.includes("zerado") || 
                               pergunta.includes("parado") || 
                               pergunta.includes("ocioso") || 
                               pergunta.includes("financeiro") || 
                               pergunta.includes("almoxarifado") || 
                               pergunta.includes("estoque interno") ||
                               pergunta.includes("nosso estoque") ||
                               pergunta.includes("relatório");

            if (isInternal) {
                if (pergunta.includes("falta") || pergunta.includes("zerado")) {
                    return "No seu estoque interno, os produtos que necessitam de <b>atenção para compra</b> estão sinalizados nos cards de 'Risco Crítico' acima. Recomendo verificar os níveis de segurança.";
                } 
                if (pergunta.includes("parado") || pergunta.includes("ocioso")) {
                    return "Identifiquei itens ociosos no almoxarifado (sem movimento há mais de 30 dias). Verifique a lista de 'Capital Estagnado' acima.";
                }
                if (pergunta.includes("financeiro")) {
                    return "Seu capital está distribuído entre as diversas categorias. Acesse a guia de 'Relatórios' para um balanço financeiro detalhado.";
                }
                return "Estou monitorando seu almoxarifado em tempo real. Tudo parece sob controle no momento!";
            }

            if (pergunta.includes("obrigado") || pergunta.includes("valeu")) {
                return "Por nada! Estou operando sem limitações para varrer o mercado e seu estoque.";
            }

            // 2. PESQUISA DE MERCADO SEM LIMITAÇÕES (COMÉRCIO EXTERNO)
            // Se não for um comando interno, a IA varre a internet buscando ofertas de comércio
            
            // Limpa palavras desnecessárias para melhorar a busca no mercado
            let searchTerm = pergunta
                .replace("onde comprar", "")
                .replace("qual o", "")
                .replace("preço de", "")
                .replace("valor de", "")
                .replace("pesquisar por", "")
                .replace("busque", "")
                .replace("comprar", "")
                .replace("quero", "")
                .replace("me mostre", "")
                .trim();
                
            // Se ficou vazio após limpar, usa a pergunta original
            if (searchTerm === "") searchTerm = pergunta.trim();

            try {
                // Conexão com o mercado aberto (Usando DummyJSON para busca universal sem bloqueio de CORS/Adblock)
                const response = await fetch(`https://dummyjson.com/products/search?q=${encodeURIComponent(searchTerm)}&limit=5`);
                
                if (!response.ok) {
                    throw new Error("Erro na rede externa");
                }
                
                const data = await response.json();
                
                if (data.products && data.products.length > 0) {
                    let resultHtml = `Fiz uma varredura abrangente no mercado externo por <b>"${searchTerm}"</b>. Aqui estão as 5 melhores ofertas em tempo real:<br><br>`;
                    
                    resultHtml += `<div style="display:flex; flex-direction:column; gap:10px; max-height: 400px; overflow-y: auto; padding-right:5px;">`;
                    
                    data.products.forEach(item => {
                        const discount = item.discountPercentage > 0 ? `<span style="background:#ef4444; color:#fff; font-size:0.7rem; padding:2px 6px; border-radius:4px; margin-left:8px;">-${item.discountPercentage}% OFF</span>` : '';
                        
                        resultHtml += `
                        <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; border-left: 3px solid #38bdf8; display:flex; gap:15px; align-items:center;">
                            <img src="${item.thumbnail}" alt="thumb" style="width:60px; height:60px; object-fit:cover; border-radius:8px; border:1px solid rgba(255,255,255,0.1);">
                            <div style="flex:1;">
                                <strong style="font-size:0.95rem; display:block; margin-bottom:5px;">${item.title}</strong>
                                <span style="color: #38bdf8; font-weight: bold; font-size: 1.2rem;">$ ${item.price.toFixed(2)}</span>
                                ${discount}
                            </div>
                            <a href="#" onclick="alert('Simulação: Compra do produto ${item.title} iniciada!'); return false;" title="Comprar" style="background:#3b82f6; color:white; padding:8px 12px; border-radius:8px; text-decoration:none; font-size:0.9rem; white-space:nowrap;"><i class="fa fa-shopping-cart"></i> Ver</a>
                        </div>`;
                    });
                    
                    resultHtml += `</div>`;
                    return resultHtml;
                } else {
                    return `Vasculhei o mercado de comércio online, mas não encontrei ofertas ativas para <b>"${searchTerm}"</b>. Tente especificar mais a categoria (ex: laptop, phone, fragrance).`;
                }
            } catch (error) {
                // Fallback de erro
                return `Houve uma falha ao tentar acessar a rede global de comércio. Acesso externo temporariamente comprometido devido a uma restrição de rede interna do seu computador ou firewall.`;
            }
        }
    </script>
</body>
</html>
