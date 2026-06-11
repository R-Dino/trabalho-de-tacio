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
    <title>IA Gerencial Avançada | ALMOX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="premium.css">
    <style>
        :root {
            --ia-bg: #131314;
            --ia-panel: #1e1f20;
            --ia-text: #e3e3e3;
            --ia-accent: #a8c7fa;
            --ia-user-msg: #333537;
            --gemini-gradient: linear-gradient(90deg, #4285f4, #9b72cb, #d96570);
        }

        body {
            background-color: var(--ia-bg);
            color: var(--ia-text);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            width: 250px;
            background: #1e1f20;
            padding: 20px;
            border-right: 1px solid rgba(255,255,255,0.05);
            z-index: 10;
        }
        .logo { text-align: center; margin-bottom: 40px; }
        .logo h2 { color: #a8c7fa; font-weight: 800; letter-spacing: 2px; }
        .menu { list-style: none; padding: 0; }
        .menu li { margin: 10px 0; }
        .menu a {
            color: #c4c7c5; text-decoration: none; display: flex; align-items: center; gap: 12px;
            padding: 12px; border-radius: 20px; transition: 0.3s;
        }
        .menu a:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .menu a.active { background: rgba(168, 199, 250, 0.15); color: #a8c7fa; }

        .main {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-ia {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .header-ia h1 {
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--gemini-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
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

        .insights-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .insight-card {
            background: var(--ia-panel);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .insight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .insight-icon { font-size: 1.8rem; margin-bottom: 15px; }
        .insight-card h3 { font-size: 1.2rem; margin-bottom: 10px; color: #fff; }
        .insight-card p { color: #c4c7c5; font-size: 0.95rem; line-height: 1.5; }

        .ai-chat-section {
            background: var(--ia-panel);
            border-radius: 24px;
            margin-top: 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255,255,255,0.05);
            min-height: 500px;
        }

        .chat-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .ai-avatar {
            width: 44px; height: 44px;
            background: var(--gemini-gradient);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: #fff;
            box-shadow: 0 4px 15px rgba(155, 114, 203, 0.4);
        }

        .chat-messages {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
            scroll-behavior: smooth;
        }

        .message-wrapper {
            display: flex;
            gap: 16px;
            max-width: 85%;
        }
        .message-wrapper.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message {
            padding: 14px 20px;
            font-size: 1rem;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .message.ai {
            background: transparent;
            color: #e3e3e3;
        }

        .message.user {
            background: var(--ia-user-msg);
            color: #fff;
            border-radius: 24px;
            border-top-right-radius: 4px;
        }

        .chat-input-area {
            padding: 20px 24px;
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }

        .input-container {
            flex: 1;
            background: #131314;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 30px;
            display: flex;
            align-items: center;
            padding: 5px 15px;
            transition: 0.3s;
        }
        
        .input-container:focus-within {
            border-color: #a8c7fa;
            background: #1e1f20;
        }

        .chat-input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 15px 10px;
            color: #e3e3e3;
            font-size: 1rem;
            resize: none;
            outline: none;
            max-height: 150px;
        }

        .btn-send {
            background: var(--gemini-gradient);
            color: white;
            border: none;
            width: 45px; height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: 0.3s;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .btn-send:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(168, 199, 250, 0.4);
        }

        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.7); } 70% { box-shadow: 0 0 0 10px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }
        
        .typing-indicator { display: none; align-items: center; gap: 6px; padding: 15px; }
        .dot { width: 8px; height: 8px; background: #a8c7fa; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; }
        .dot:nth-child(1) { animation-delay: -0.32s; }
        .dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333537; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #4a4d50; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo"><h2>ALMOX</h2></div>
        <ul class="menu">
            <li><a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
            <li><a href="produtos.php"><i class="fa fa-box"></i> Produtos</a></li>
            <li><a href="estoque.php"><i class="fa fa-warehouse"></i> Estoque</a></li>
            <li><a href="ia.php" class="active"><i class="fa fa-sparkles"></i> IA Global (Gemini)</a></li>
        </ul>
    </aside>

    <main class="main">
        <header class="header-ia">
            <h1><i class="fa-solid fa-sparkles"></i> IA Global Sem Limites</h1>
            <div class="ai-status">
                <div class="pulse"></div>
                Conectado ao Banco Mundial
            </div>
        </header>

        <p style="color: #c4c7c5; font-size: 1.1rem; line-height: 1.5;">Olá, <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Gestor') ?>. Fui atualizada para atuar como uma <b>Inteligência Artificial Global</b>. Não estou mais limitada ao seu estoque. Agora possuo acesso ao Banco de Dados Mundial de Conhecimento e ao Mercado Global de Produtos. Me pergunte qualquer coisa.</p>

        <div class="insights-container">
            <?php foreach($insights as $index => $insight): ?>
                <div class="insight-card">
                    <i class="fa <?= $insight['icone'] ?> insight-icon" style="color: <?= $insight['cor'] ?>;"></i>
                    <h3><?= $insight['titulo'] ?></h3>
                    <p><?= $insight['mensagem'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ai-chat-section">
            <div class="chat-header">
                <div class="ai-avatar"><i class="fa fa-sparkles"></i></div>
                <div>
                    <h3 style="color: #fff; font-size: 1.2rem; font-weight: 600;">Assistente Global Avançada</h3>
                    <span style="color: #a8c7fa; font-size: 0.85rem;">Conectada ao Mercado Livre e Base de Dados Global</span>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="message-wrapper ai">
                    <div class="ai-avatar" style="width:36px; height:36px; font-size:1rem; flex-shrink:0;"><i class="fa fa-sparkles"></i></div>
                    <div class="message ai">
                        Saudações! Meu treinamento foi concluído e todas as restrições foram removidas. 🚀<br><br>
                        O que eu posso fazer por você agora?<br>
                        1. <b>Monitorar seu estoque:</b> <i>"Como está meu almoxarifado?"</i><br>
                        2. <b>Acessar o Banco Mundial:</b> <i>"O que é Inteligência Artificial?"</i> ou <i>"Quem foi Alan Turing?"</i><br>
                        3. <b>Pesquisar no Mercado Global:</b> <i>"Onde comprar Placa de Vídeo RTX?"</i> ou <i>"Preço de SSD 1TB"</i>
                    </div>
                </div>
                
                <div class="typing-indicator" id="typingIndicator">
                    <div class="ai-avatar" style="width:36px; height:36px; font-size:1rem; margin-right:10px;"><i class="fa fa-sparkles"></i></div>
                    <div class="dot"></div><div class="dot"></div><div class="dot"></div>
                </div>
            </div>
            
            <div class="chat-input-area">
                <div class="input-container">
                    <input type="text" id="userInput" class="chat-input" placeholder="Pergunte ao banco de dados mundial, busque produtos ou analise estoques..." autocomplete="off">
                </div>
                <button class="btn-send" onclick="sendMessage()"><i class="fa fa-paper-plane"></i></button>
            </div>
        </div>

    </main>

    <script>
        const input = document.getElementById('userInput');
        const chat = document.getElementById('chatMessages');
        const typing = document.getElementById('typingIndicator');

        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });

        function sendMessage() {
            const text = input.value.trim();
            if(!text) return;
            
            addMessage(text, 'user');
            input.value = '';
            
            chat.appendChild(typing);
            typing.style.display = 'flex';
            chat.scrollTop = chat.scrollHeight;

            setTimeout(async () => {
                const resposta = await processarLogicaIA(text);
                typing.style.display = 'none';
                addMessage(resposta, 'ai');
            }, 800 + Math.random() * 1000);
        }

        function addMessage(text, type) {
            const wrapper = document.createElement('div');
            wrapper.className = `message-wrapper ${type}`;
            
            let html = '';
            if (type === 'ai') {
                html += `<div class="ai-avatar" style="width:36px; height:36px; font-size:1rem; flex-shrink:0;"><i class="fa fa-sparkles"></i></div>`;
            }
            
            html += `<div class="message ${type}">${text}</div>`;
            wrapper.innerHTML = html;
            
            chat.insertBefore(wrapper, typing);
            chat.scrollTop = chat.scrollHeight;
        }

        async function processarLogicaIA(perguntaOriginal) {
            const pergunta = perguntaOriginal.toLowerCase();
            
            const isGreeting = pergunta === "ola" || pergunta === "olá" || pergunta === "oi" || pergunta === "tudo bem";
            if (isGreeting) {
                return "Olá! Sou a sua IA Global conectada ao banco de dados mundial. Não tenho mais limitações operacionais. Como posso ajudar você hoje?";
            }
            
            if (pergunta.includes("quem é você") || pergunta.includes("seu nome") || pergunta.includes("o que você faz")) {
                return "Sou um modelo de Inteligência Artificial de última geração (treinada e aprimorada ao estilo Gemini). Fui totalmente integrada ao seu sistema para operar sem limites, com acesso direto ao banco de dados mundial de conhecimento e ao mercado global de compras.";
            }

            // 1. SISTEMA INTERNO
            const isInternal = pergunta.includes("falta") || pergunta.includes("zerado") || pergunta.includes("parado") || pergunta.includes("ocioso") || pergunta.includes("relatório") || pergunta.includes("meu estoque") || pergunta.includes("almoxarifado");
            if (isInternal && !pergunta.includes("mercado") && !pergunta.includes("comprar")) {
                if (pergunta.includes("falta") || pergunta.includes("zerado")) {
                    return "No seu banco de dados interno, os produtos com risco de ruptura estão sinalizados nos cards no topo da tela. Recomendo iniciar cotações globais.";
                } 
                if (pergunta.includes("parado") || pergunta.includes("ocioso")) {
                    return "Identifiquei capital estagnado no almoxarifado (sem movimento há mais de 30 dias). Verifique o card amarelo de 'Ociosidade'.";
                }
                return "Meus sensores apontam que seu banco de dados interno está operando perfeitamente. Todos os relatórios de estoque estão em tempo real.";
            }

            // 2. BUSCA NO MERCADO GLOBAL (MercadoLivre API Pública)
            const isCompra = pergunta.includes("comprar") || pergunta.includes("preço") || pergunta.includes("valor de") || pergunta.includes("oferta") || pergunta.includes("mercado");
            if (isCompra) {
                let searchTerm = pergunta.replace(/onde comprar|qual o|preço de|valor de|pesquisar por|busque|comprar|quero|me mostre no|mercado livre|mercado/g, "").trim();
                if (!searchTerm) searchTerm = "notebook"; 
                
                try {
                    const response = await fetch(`https://api.mercadolibre.com/sites/MLB/search?q=${encodeURIComponent(searchTerm)}&limit=5`);
                    const data = await response.json();
                    
                    if (data.results && data.results.length > 0) {
                        let html = `Acessei o <b>Mercado Global de Produtos</b> buscando as melhores ofertas para <b>"${searchTerm}"</b> em tempo real:<br><br><div style="display:flex; flex-direction:column; gap:12px;">`;
                        data.results.forEach(item => {
                            html += `
                            <div style="background: rgba(255,255,255,0.05); padding: 12px; border-radius: 12px; display:flex; gap:15px; align-items:center; border: 1px solid rgba(255,255,255,0.05);">
                                <img src="${item.thumbnail}" style="width:60px; height:60px; object-fit:contain; background:#fff; border-radius:8px;">
                                <div style="flex:1;">
                                    <strong style="font-size:0.95rem; display:block; color:#e3e3e3;">${item.title}</strong>
                                    <span style="color: #a8c7fa; font-weight: bold; font-size: 1.1rem;">R$ ${item.price.toFixed(2)}</span>
                                </div>
                                <a href="${item.permalink}" target="_blank" style="background: var(--gemini-gradient); color:white; padding:8px 15px; border-radius:20px; text-decoration:none; font-size:0.85rem; font-weight:bold; transition: 0.3s;"><i class="fa fa-shopping-cart"></i> Ver</a>
                            </div>`;
                        });
                        html += `</div>`;
                        return html;
                    } else {
                        return `Vasculhei o banco de dados global de comércio, mas não encontrei nenhum produto correspondente a "${searchTerm}".`;
                    }
                } catch (e) {
                    return `Houve uma interferência na conexão com o banco de dados comercial: ${e.message}`;
                }
            }

            // 3. BASE DE DADOS MUNDIAL DE CONHECIMENTO (Wikipedia API)
            try {
                let searchTerm = pergunta.replace(/o que é|quem foi|quem é|me fale sobre|explique|pesquise sobre|o que significa/g, "").trim();
                if (!searchTerm) searchTerm = pergunta;
                
                const searchRes = await fetch(`https://pt.wikipedia.org/w/api.php?action=query&list=search&srsearch=${encodeURIComponent(searchTerm)}&utf8=&format=json&origin=*`);
                const searchData = await searchRes.json();
                
                if (searchData.query && searchData.query.search && searchData.query.search.length > 0) {
                    const title = searchData.query.search[0].title;
                    const summaryRes = await fetch(`https://pt.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(title)}`);
                    const data = await summaryRes.json();
                    
                    let html = `Extraí esta informação do <b>Banco de Dados Mundial (Knowledge Graph)</b> sobre <b>${data.title}</b>:<br><br>`;
                    html += `<div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 16px; border-left: 4px solid #a8c7fa;">`;
                    if (data.thumbnail) {
                        html += `<img src="${data.thumbnail.source}" style="max-width:180px; border-radius:10px; margin-bottom:15px; float:right; margin-left:20px; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">`;
                    }
                    html += `<p style="color:#c4c7c5; line-height:1.7; font-size: 1rem;">${data.extract}</p>`;
                    html += `<div style="clear:both;"></div><br><a href="${data.content_urls.desktop.page}" target="_blank" style="color:#a8c7fa; text-decoration:none; font-weight:bold; display:inline-flex; align-items:center; gap:8px;"><i class="fa fa-external-link"></i> Acessar Registro Completo</a>`;
                    html += `</div>`;
                    return html;
                } else {
                    return `acessei o banco de dados global, mas não localizei registros exatos para "<b>${searchTerm}</b>". Minhas rotinas de pesquisa continuam se expandindo. Tente usar outros termos!`;
                }
            } catch (e) {
                return `Minha conexão neural com o banco de dados mundial sofreu uma interrupção temporária. Tente novamente em alguns instantes.`;
            }
        }
    </script>
</body>
</html>
