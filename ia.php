<?php
session_start();
require 'db.php';
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ia_query') {
    $pergunta = strtolower(trim($_POST['pergunta']));
    $response = ['html' => '', 'handled' => false];
    
    if (!str_starts_with($pergunta, '/')) {
        $response['html'] = "<b>Erro:</b> Utilize a barra '/' antes do comando (Ex: /ajuda).";
        $response['handled'] = true;
    }
    elseif (preg_match('/^\/adicionar\s+(\d+)\s+(.+)/i', $pergunta, $matches)) {
        $qtd = (int)$matches[1];
        $produto_busca = trim($matches[2]);
        $stmt = $pdo->prepare("SELECT id, nome, quantidade FROM produtos WHERE nome LIKE ?");
        $stmt->execute(["%$produto_busca%"]);
        $produtos = $stmt->fetchAll();
        if (count($produtos) === 1) {
            $p = $produtos[0];
            $nova_qtd = $p['quantidade'] + $qtd;
            $status = $nova_qtd > 10 ? 'Normal' : ($nova_qtd > 0 ? 'Baixo' : 'Zerado');
            $upd = $pdo->prepare("UPDATE produtos SET quantidade = ?, status = ? WHERE id = ?");
            $upd->execute([$nova_qtd, $status, $p['id']]);
            $mov = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, usuario) VALUES (?, 'Entrada', ?, ?)");
            $mov->execute([$p['id'], $qtd, $_SESSION['usuario_nome']]);
            $response['html'] = "<b><i class='fa fa-check-circle' style='color:#10b981;'></i> Sucesso!</b> Adicionei $qtd unidade(s) de <b>{$p['nome']}</b>. O estoque atual agora é de $nova_qtd unidades.";
        } elseif (count($produtos) > 1) {
            $response['html'] = "Encontrei mais de um produto para '$produto_busca'. Seja mais específico.";
        } else {
            $response['html'] = "Produto '$produto_busca' não encontrado.";
        }
        $response['handled'] = true;
    }
    elseif (preg_match('/^\/remover\s+(\d+)\s+(.+)/i', $pergunta, $matches)) {
        $qtd = (int)$matches[1];
        $produto_busca = trim($matches[2]);
        $stmt = $pdo->prepare("SELECT id, nome, quantidade FROM produtos WHERE nome LIKE ?");
        $stmt->execute(["%$produto_busca%"]);
        $produtos = $stmt->fetchAll();
        if (count($produtos) === 1) {
            $p = $produtos[0];
            if ($p['quantidade'] >= $qtd) {
                $nova_qtd = $p['quantidade'] - $qtd;
                $status = $nova_qtd > 10 ? 'Normal' : ($nova_qtd > 0 ? 'Baixo' : 'Zerado');
                $upd = $pdo->prepare("UPDATE produtos SET quantidade = ?, status = ? WHERE id = ?");
                $upd->execute([$nova_qtd, $status, $p['id']]);
                $mov = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, usuario) VALUES (?, 'Saida', ?, ?)");
                $mov->execute([$p['id'], $qtd, $_SESSION['usuario_nome']]);
                $response['html'] = "<b><i class='fa fa-check-circle' style='color:#10b981;'></i> Baixa registrada!</b> Removi $qtd unidade(s) de <b>{$p['nome']}</b>. Restam $nova_qtd no estoque.";
            } else {
                $response['html'] = "<b><i class='fa fa-exclamation-circle' style='color:#ef4444;'></i> Erro:</b> Só há {$p['quantidade']} no estoque de {$p['nome']}.";
            }
        } else {
            $response['html'] = "Produto '$produto_busca' não encontrado ou inespecífico.";
        }
        $response['handled'] = true;
    }
    elseif (preg_match('/^\/banir\s+(.+)/i', $pergunta, $matches)) {
        $termo = trim($matches[1]);
        $stmt = $pdo->prepare("SELECT id, nome, status FROM usuarios WHERE id = ? OR email = ? OR nome LIKE ? LIMIT 1");
        $stmt->execute([$termo, $termo, "%$termo%"]);
        $user = $stmt->fetch();
        if ($user) {
            if ($user['id'] == $_SESSION['usuario_id']) {
                $response['html'] = "<b>Erro:</b> Você não pode dar ban em si mesmo.";
            } else {
                $pdo->prepare("UPDATE usuarios SET status = 'banido' WHERE id = ?")->execute([$user['id']]);
                
                $detalhes = "Baniu o usuário ID {$user['id']} ({$user['nome']}) via Terminal IA";
                $pdo->prepare("INSERT INTO logs_atividades (usuario_id, acao, detalhes) VALUES (?, 'Banir Usuário', ?)")
                    ->execute([$_SESSION['usuario_id'], $detalhes]);
                
                $response['html'] = "<b><i class='fa fa-gavel' style='color:#ef4444;'></i> BAN HAMMER!</b> O usuário <b>{$user['nome']}</b> foi banido.";
            }
        } else {
            $response['html'] = "Usuário '$termo' não encontrado.";
        }
        $response['handled'] = true;
    }
    elseif (preg_match('/^\/desbanir\s+(.+)/i', $pergunta, $matches)) {
        $termo = trim($matches[1]);
        $stmt = $pdo->prepare("SELECT id, nome, status FROM usuarios WHERE id = ? OR email = ? OR nome LIKE ? LIMIT 1");
        $stmt->execute([$termo, $termo, "%$termo%"]);
        $user = $stmt->fetch();
        if ($user) {
            $pdo->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?")->execute([$user['id']]);
            
            $detalhes = "Desbaniu o usuário ID {$user['id']} ({$user['nome']}) via Terminal IA";
            $pdo->prepare("INSERT INTO logs_atividades (usuario_id, acao, detalhes) VALUES (?, 'Desbanir Usuário', ?)")
                ->execute([$_SESSION['usuario_id'], $detalhes]);
                
            $response['html'] = "<b><i class='fa fa-unlock' style='color:#10b981;'></i> UNBANNED!</b> O usuário <b>{$user['nome']}</b> foi desbanido.";
        } else {
            $response['html'] = "Usuário '$termo' não encontrado.";
        }
        $response['handled'] = true;
    }
    elseif (preg_match('/^\/promover\s+(.+)/i', $pergunta, $matches)) {
        $termo = trim($matches[1]);
        $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id = ? OR email = ? OR nome LIKE ? LIMIT 1");
        $stmt->execute([$termo, $termo, "%$termo%"]);
        $user = $stmt->fetch();
        if ($user) {
            $pdo->prepare("UPDATE usuarios SET nivel_acesso = 'admin' WHERE id = ?")->execute([$user['id']]);
            
            $detalhes = "Alterou nível do ID {$user['id']} ({$user['nome']}) para admin via Terminal IA";
            $pdo->prepare("INSERT INTO logs_atividades (usuario_id, acao, detalhes) VALUES (?, 'Mudar Nível', ?)")
                ->execute([$_SESSION['usuario_id'], $detalhes]);
                
            $response['html'] = "<b><i class='fa fa-star' style='color:#f59e0b;'></i> PROMOVIDO!</b> O usuário <b>{$user['nome']}</b> agora é Admin.";
        } else {
            $response['html'] = "Usuário '$termo' não encontrado.";
        }
        $response['handled'] = true;
    }
    elseif (preg_match('/^\/rebaixar\s+(.+)/i', $pergunta, $matches)) {
        $termo = trim($matches[1]);
        $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id = ? OR email = ? OR nome LIKE ? LIMIT 1");
        $stmt->execute([$termo, $termo, "%$termo%"]);
        $user = $stmt->fetch();
        if ($user) {
            if ($user['id'] == $_SESSION['usuario_id']) {
                $response['html'] = "Você não pode rebaixar a si mesmo.";
            } else {
                $pdo->prepare("UPDATE usuarios SET nivel_acesso = 'comum' WHERE id = ?")->execute([$user['id']]);
                
                $detalhes = "Alterou nível do ID {$user['id']} ({$user['nome']}) para comum via Terminal IA";
                $pdo->prepare("INSERT INTO logs_atividades (usuario_id, acao, detalhes) VALUES (?, 'Mudar Nível', ?)")
                    ->execute([$_SESSION['usuario_id'], $detalhes]);
                    
                $response['html'] = "<b><i class='fa fa-arrow-down' style='color:#ef4444;'></i> REBAIXADO!</b> O usuário <b>{$user['nome']}</b> voltou a ser Usuário Comum.";
            }
        } else {
            $response['html'] = "Usuário '$termo' não encontrado.";
        }
        $response['handled'] = true;
    }
    elseif (strpos($pergunta, '/usuarios') === 0) {
        $stmt = $pdo->query("SELECT id, nome, email, nivel_acesso, status FROM usuarios");
        $users = $stmt->fetchAll();
        $html = "<b><i class='fa fa-users'></i> Jogadores no Servidor:</b><br><ul style='margin-top:10px; padding-left:20px;'>";
        foreach($users as $u) {
            $corStatus = $u['status'] == 'banido' ? '#ef4444' : '#10b981';
            $nivel = $u['nivel_acesso'] == 'admin' ? '[ADMIN]' : '[COMUM]';
            $emailInfo = !empty($u['email']) ? $u['email'] : 'Sem email';
            $html .= "<li style='margin-bottom:5px;'><span style='color:#a8c7fa'>$nivel</span> <b>{$u['nome']}</b> <small style='color:#94a3b8;'>(ID: {$u['id']} | $emailInfo)</small> - <span style='color:$corStatus; font-size:0.8rem; font-weight:bold;'>".strtoupper($u['status'])."</span></li>";
        }
        $html .= "</ul>";
        $response['html'] = $html;
        $response['handled'] = true;
    }
    elseif (preg_match('/^\/deletar\s+(.+)/i', $pergunta, $matches)) {
        $nome = trim($matches[1]);
        $stmt = $pdo->prepare("SELECT id, nome FROM produtos WHERE nome LIKE ?");
        $stmt->execute(["%$nome%"]);
        $produtos = $stmt->fetchAll();
        if (count($produtos) == 1) {
            $p = $produtos[0];
            $pdo->prepare("DELETE FROM movimentacoes WHERE produto_id = ?")->execute([$p['id']]);
            $pdo->prepare("DELETE FROM produtos WHERE id = ?")->execute([$p['id']]);
            
            $detalhes = "Excluiu o produto ID {$p['id']} ({$p['nome']}) do banco de dados via Terminal IA";
            $pdo->prepare("INSERT INTO logs_atividades (usuario_id, acao, detalhes) VALUES (?, 'Excluir Produto', ?)")
                ->execute([$_SESSION['usuario_id'], $detalhes]);
                
            $response['html'] = "<b><i class='fa fa-trash' style='color:#ef4444;'></i> ITEM DELETADO!</b> O produto <b>{$p['nome']}</b> foi vaporizado.";
        } else {
            $response['html'] = "Produto '$nome' não encontrado ou inespecífico.";
        }
        $response['handled'] = true;
    }
    elseif (strpos($pergunta, '/alertas') === 0) {
        $stmt = $pdo->query("SELECT nome, quantidade, status FROM produtos WHERE status IN ('Baixo', 'Zerado') ORDER BY quantidade ASC");
        $alertas = $stmt->fetchAll();
        if (count($alertas) > 0) {
            $html = "<b><i class='fa fa-exclamation-triangle' style='color:#ef4444;'></i> Alertas de Estoque:</b><br><ul style='margin-top:10px; padding-left:20px;'>";
            foreach($alertas as $a) {
                $cor = $a['status'] == 'Zerado' ? '#ef4444' : '#f59e0b';
                $html .= "<li style='margin-bottom:5px;'>{$a['nome']} - <span style='color:$cor; font-weight:bold;'>{$a['quantidade']} unidades ({$a['status']})</span></li>";
            }
            $html .= "</ul>";
            $response['html'] = $html;
        } else {
            $response['html'] = "<b><i class='fa fa-check-circle' style='color:#10b981;'></i> Tudo OK!</b> Não há produtos com estoque baixo ou zerado no momento.";
        }
        $response['handled'] = true;
    }
    elseif (preg_match('/^\/historico\s+(.+)/i', $pergunta, $matches)) {
        $produto = trim($matches[1]);
        $stmt = $pdo->prepare("SELECT id, nome FROM produtos WHERE nome LIKE ? LIMIT 1");
        $stmt->execute(["%$produto%"]);
        $p = $stmt->fetch();
        if ($p) {
            $movs = $pdo->prepare("SELECT tipo, quantidade, usuario, data_movimentacao FROM movimentacoes WHERE produto_id = ? ORDER BY data_movimentacao DESC LIMIT 5");
            $movs->execute([$p['id']]);
            $resultados = $movs->fetchAll();
            
            if (count($resultados) > 0) {
                $html = "Histórico recente de <b>{$p['nome']}</b>:<br><br>";
                foreach($resultados as $m) {
                    $cor = $m['tipo'] == 'Entrada' ? '#10b981' : '#ef4444';
                    $sinal = $m['tipo'] == 'Entrada' ? '+' : '-';
                    $data = date('d/m/Y H:i', strtotime($m['data_movimentacao']));
                    $html .= "<div style='background:rgba(255,255,255,0.05); padding:10px; border-radius:8px; border-left:3px solid $cor; margin-bottom:8px;'><b>$sinal{$m['quantidade']}x</b> via {$m['usuario']}<br><small style='color:#a8c7fa;'>em $data</small></div>";
                }
                $response['html'] = $html;
            } else {
                $response['html'] = "O produto <b>{$p['nome']}</b> ainda não possui movimentações.";
            }
        } else {
            $response['html'] = "Produto '$produto' não encontrado.";
        }
        $response['handled'] = true;
    }
    elseif (strpos($pergunta, '/status') === 0) {
        $totalProd = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
        $totalValor = $pdo->query("SELECT SUM(quantidade * preco) FROM produtos")->fetchColumn();
        $totalBaixo = $pdo->query("SELECT COUNT(*) FROM produtos WHERE status = 'Baixo'")->fetchColumn();
        $totalZerado = $pdo->query("SELECT COUNT(*) FROM produtos WHERE status = 'Zerado'")->fetchColumn();
        
        $response['html'] = "<b><i class='fa fa-server' style='color:#a8c7fa;'></i> Status do Sistema:</b><br><br>
        • <b>Total de Produtos:</b> $totalProd cadastrados<br>
        • <b>Valor em Estoque:</b> R$ " . number_format($totalValor ?: 0, 2, ',', '.') . "<br>
        • <b>Estoque Baixo:</b> <span style='color:#f59e0b;'>$totalBaixo item(ns)</span><br>
        • <b>Estoque Zerado:</b> <span style='color:#ef4444;'>$totalZerado item(ns)</span><br>";
        $response['handled'] = true;
    }
    elseif (strpos($pergunta, '/vendidos') === 0) {
        $stmt = $pdo->query("SELECT SUM(quantidade) FROM movimentacoes WHERE tipo = 'Saida'");
        $total = $stmt->fetchColumn();
        $response['html'] = "Até o momento, um total de <b>" . ($total ?: 0) . " itens</b> já saíram do estoque.";
        $response['handled'] = true;
    }
    elseif (strpos($pergunta, '/valor') === 0) {
        $stmt = $pdo->query("SELECT SUM(quantidade * preco) as total FROM produtos");
        $total = $stmt->fetchColumn();
        $response['html'] = "O valor total do seu estoque atualmente é de <b>R$ " . number_format($total ?: 0, 2, ',', '.') . "</b>.";
        $response['handled'] = true;
    }
    elseif (preg_match('/^\/estoque\s+(.+)/i', $pergunta, $matches)) {
        $produto = trim($matches[1]);
        $stmt = $pdo->prepare("SELECT nome, quantidade, status FROM produtos WHERE nome LIKE ?");
        $stmt->execute(["%$produto%"]);
        $produtos = $stmt->fetchAll();
        if (count($produtos) > 0) {
            $html = "Resultados para <b>$produto</b>:<br><ul style='margin-top:10px; padding-left:20px;'>";
            foreach($produtos as $p) {
                $corStatus = $p['quantidade'] > 10 ? '#10b981' : ($p['quantidade'] > 0 ? '#f59e0b' : '#ef4444');
                $html .= "<li style='margin-bottom:5px;'>{$p['nome']}: <b>{$p['quantidade']} unidades</b> <span style='font-size:0.75rem; padding:2px 6px; border-radius:4px; background:rgba(255,255,255,0.1); color:$corStatus;'>{$p['status']}</span></li>";
            }
            $html .= "</ul>";
            $response['html'] = $html;
        } else {
            $response['html'] = "Produto <b>$produto</b> não encontrado no estoque.";
        }
        $response['handled'] = true;
    }
    elseif (strpos($pergunta, '/fornecedores') === 0) {
        $stmt = $pdo->query("SELECT nome, contato FROM fornecedores LIMIT 10");
        $forn = $stmt->fetchAll();
        if (count($forn) > 0) {
            $html = "Seus fornecedores:<br><ul style='margin-top:10px; padding-left:20px;'>";
            foreach($forn as $f) { $html .= "<li style='margin-bottom:5px;'><b>{$f['nome']}</b> (Contato: {$f['contato']})</li>"; }
            $html .= "</ul>";
            $response['html'] = $html;
        } else {
            $response['html'] = "Você não possui fornecedores cadastrados.";
        }
        $response['handled'] = true;
    }
    elseif (strpos($pergunta, '/saidas') === 0) {
        $stmt = $pdo->query("SELECT p.nome, m.quantidade, m.data_movimentacao, m.usuario FROM movimentacoes m JOIN produtos p ON m.produto_id = p.id WHERE m.tipo = 'Saida' ORDER BY m.data_movimentacao DESC LIMIT 5");
        $movs = $stmt->fetchAll();
        if (count($movs) > 0) {
            $html = "Últimas 5 saídas:<br><br>";
            foreach($movs as $m) {
                $data = date('d/m/Y H:i', strtotime($m['data_movimentacao']));
                $html .= "<div style='background:rgba(255,255,255,0.05); padding:10px; border-radius:8px; border-left:3px solid #ef4444; margin-bottom:8px;'><b>-{$m['quantidade']}x {$m['nome']}</b><br><small style='color:#a8c7fa;'>Por {$m['usuario']} em $data</small></div>";
            }
            $response['html'] = $html;
        } else { $response['html'] = "Nenhuma saída registrada."; }
        $response['handled'] = true;
    }
    elseif (strpos($pergunta, '/entradas') === 0) {
        $stmt = $pdo->query("SELECT p.nome, m.quantidade, m.data_movimentacao, m.usuario FROM movimentacoes m JOIN produtos p ON m.produto_id = p.id WHERE m.tipo = 'Entrada' ORDER BY m.data_movimentacao DESC LIMIT 5");
        $movs = $stmt->fetchAll();
        if (count($movs) > 0) {
            $html = "Últimas 5 entradas:<br><br>";
            foreach($movs as $m) {
                $data = date('d/m/Y H:i', strtotime($m['data_movimentacao']));
                $html .= "<div style='background:rgba(255,255,255,0.05); padding:10px; border-radius:8px; border-left:3px solid #10b981; margin-bottom:8px;'><b>+{$m['quantidade']}x {$m['nome']}</b><br><small style='color:#a8c7fa;'>Por {$m['usuario']} em $data</small></div>";
            }
            $response['html'] = $html;
        } else { $response['html'] = "Nenhuma entrada registrada."; }
        $response['handled'] = true;
    }

    if ($_POST['action'] === 'ia_query') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

$stmtComandos = $pdo->query("SELECT comando as cmd, descricao as `desc`, icone as icon, cor FROM ia_comandos ORDER BY comando ASC");
$comandos_db = $stmtComandos->fetchAll(PDO::FETCH_ASSOC);
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
            padding-left: 250px;
        }
        .sidebar {
            width: 250px;
            background: #1e1f20;
            padding: 20px;
            border-right: 1px solid rgba(255,255,255,0.05);
            z-index: 10;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
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
            position: relative;
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
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333537; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #4a4d50; }

        .suggestions-box {
            position: absolute;
            bottom: calc(100% + 15px);
            left: 24px;
            background: #2b2d31;
            border: 1px solid #1e1f22;
            border-radius: 8px;
            width: calc(100% - 100px);
            max-height: 350px;
            overflow-y: auto;
            z-index: 9999;
            box-shadow: 0 8px 24px rgba(0,0,0,0.6);
            display: none;
            flex-direction: column;
            padding: 8px 0;
        }
        .suggestions-header {
            padding: 8px 16px;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: bold;
            color: #b5bac1;
            letter-spacing: 0.05em;
        }
        .suggestion-item {
            padding: 10px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: 0.1s;
            border-left: 2px solid transparent;
        }
        .suggestion-item:hover, .suggestion-item.active {
            background: #3f4147;
            border-left: 2px solid #5865F2;
        }
        .suggestion-icon {
            width: 32px;
            height: 32px;
            background: #1e1f22;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #dbdee1;
        }
        .suggestion-details {
            display: flex;
            flex-direction: column;
        }
        .suggestion-cmd {
            color: #dbdee1;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 2px;
        }
        .suggestion-desc {
            color: #b5bac1;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo"><h2>ALMOX</h2></div>
        <ul class="menu">
            <li><a href="telainicial.php"><i class="fa fa-house"></i> Início</a></li>
            <li><a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
            <li><a href="produtos.php"><i class="fa fa-box"></i> Produtos</a></li>
            <li><a href="estoque.php"><i class="fa fa-warehouse"></i> Estoque</a></li>
            <li><a href="fornecedores.php"><i class="fa fa-truck"></i> Fornecedores</a></li>
            <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'admin'): ?>
            <li><a href="usuarios.php"><i class="fa fa-users"></i> Usuários</a></li>
            <li><a href="relatorios.php"><i class="fa fa-file-alt"></i> Relatórios</a></li>
            <li><a href="configuracoes.php"><i class="fa fa-cog"></i> Configurações</a></li>
            <?php endif; ?>
            <li><a href="ia.php" class="active"><i class="fa fa-sparkles"></i> IA Global (Gemini)</a></li>
        </ul>
    </aside>
    <main class="main">
        <header class="header-ia">
            <h1><i class="fa-solid fa-terminal"></i> Terminal do Almoxarifado</h1>
            <div class="ai-status">
                <div class="pulse"></div>
                Otimizado para Comandos (/)
            </div>
        </header>

        <div class="ai-chat-section">
            <div class="chat-messages" id="chatMessages">
                <div class="message-wrapper ai">
                    <div class="ai-avatar" style="width:36px; height:36px; font-size:1rem; flex-shrink:0;"><i class="fa fa-terminal"></i></div>
                    <div class="message ai">
                        <b>Terminal Iniciado.</b><br><br>
                        Para máxima performance, eu não farei pesquisas a menos que você solicite usando <code>/pesquisar [termo]</code> ou <code>/comprar [produto]</code>.<br><br>
                        Digite <b>/</b> no campo abaixo para ver as sugestões de comandos de banco de dados.
                    </div>
                </div>
                <div class="typing-indicator" id="typingIndicator">
                    <div class="ai-avatar" style="width:36px; height:36px; font-size:1rem; margin-right:10px;"><i class="fa fa-terminal"></i></div>
                    <div class="dot"></div><div class="dot"></div><div class="dot"></div>
                </div>
            </div>
            <div class="chat-input-area">
                <div id="commandSuggestions" class="suggestions-box" style="display: none;"></div>
                <div class="input-container">
                    <input type="text" id="userInput" class="chat-input" placeholder="Digite / para listar comandos..." autocomplete="off">
                </div>
                <button class="btn-send" onclick="sendMessage()"><i class="fa fa-paper-plane"></i></button>
            </div>
        </div>
    </main>
    <script>
        const COMMANDS = <?php echo json_encode($comandos_db, JSON_UNESCAPED_UNICODE); ?>;

        const input = document.getElementById('userInput');
        const chat = document.getElementById('chatMessages');
        const typing = document.getElementById('typingIndicator');
        const suggestionsBox = document.getElementById('commandSuggestions');

        let activeSuggestionIndex = -1;
        let currentSuggestions = [];

        input.addEventListener('input', function() {
            activeSuggestionIndex = -1;
            const val = this.value.trim().toLowerCase();
            if (val.startsWith('/')) {
                const match = val.split(' ')[0]; 
                currentSuggestions = COMMANDS.filter(c => c.cmd.startsWith(match));
                if (currentSuggestions.length > 0) {
                    renderSuggestions();
                    suggestionsBox.style.display = 'flex';
                } else {
                    suggestionsBox.style.display = 'none';
                }
            } else {
                suggestionsBox.style.display = 'none';
                currentSuggestions = [];
            }
        });

        function renderSuggestions() {
            let html = '<div class="suggestions-header">Comandos Disponíveis</div>';
            html += currentSuggestions.map((c, index) => `
                <div class="suggestion-item ${index === activeSuggestionIndex ? 'active' : ''}" onclick="selectCommand('${c.cmd}')" id="sugg-${index}">
                    <div class="suggestion-icon"><i class="fa ${c.icon}" style="color: ${c.color};"></i></div>
                    <div class="suggestion-details">
                        <span class="suggestion-cmd">${c.cmd}</span>
                        <span class="suggestion-desc">${c.desc}</span>
                    </div>
                </div>
            `).join('');
            suggestionsBox.innerHTML = html;
        }

        input.addEventListener('keydown', function(e) {
            if (suggestionsBox.style.display === 'flex' && currentSuggestions.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeSuggestionIndex = (activeSuggestionIndex + 1) % currentSuggestions.length;
                    renderSuggestions();
                    document.getElementById(`sugg-${activeSuggestionIndex}`).scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeSuggestionIndex = (activeSuggestionIndex - 1 + currentSuggestions.length) % currentSuggestions.length;
                    renderSuggestions();
                    document.getElementById(`sugg-${activeSuggestionIndex}`).scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter' || e.key === 'Tab') {
                    if (activeSuggestionIndex >= 0) {
                        e.preventDefault();
                        selectCommand(currentSuggestions[activeSuggestionIndex].cmd);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        suggestionsBox.style.display = 'none';
                        sendMessage();
                    }
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                suggestionsBox.style.display = 'none';
                sendMessage();
            }
        });

        function selectCommand(cmd) {
            input.value = cmd + ' ';
            input.focus();
            suggestionsBox.style.display = 'none';
            activeSuggestionIndex = -1;
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.chat-input-area')) {
                suggestionsBox.style.display = 'none';
            }
        });

        function sendMessage() {
            const text = input.value.trim();
            if(!text) return;
            suggestionsBox.style.display = 'none';
            addMessage(text, 'user');
            input.value = '';
            chat.appendChild(typing);
            typing.style.display = 'flex';
            chat.scrollTop = chat.scrollHeight;
            setTimeout(async () => {
                const resposta = await processarLogicaIA(text);
                typing.style.display = 'none';
                addMessage(resposta, 'ai');
            }, 500 + Math.random() * 500);
        }

        function addMessage(text, type) {
            const wrapper = document.createElement('div');
            wrapper.className = `message-wrapper ${type}`;
            let html = '';
            if (type === 'ai') {
                html += `<div class="ai-avatar" style="width:36px; height:36px; font-size:1rem; flex-shrink:0;"><i class="fa fa-terminal"></i></div>`;
            }
            html += `<div class="message ${type}">${text}</div>`;
            wrapper.innerHTML = html;
            chat.insertBefore(wrapper, typing);
            chat.scrollTop = chat.scrollHeight;
        }

        async function processarLogicaIA(perguntaOriginal) {
            const pergunta = perguntaOriginal.toLowerCase().trim();

            if (pergunta === '/limpar') {
                const msgs = chat.querySelectorAll('.message-wrapper:not(:first-child)');
                msgs.forEach(m => m.remove());
                return `Terminal limpo com sucesso.`;
            }

            if (pergunta === '/dashboard') {
                window.location.href = 'dashboard.php';
                return `Acessando o Dashboard...`;
            }

            if (pergunta === '/produtos') {
                window.location.href = 'produtos.php';
                return `Acessando a listagem de Produtos...`;
            }

            if (!pergunta.startsWith('/')) {
                return `Comando inválido. O sistema foi otimizado para funcionar estritamente via comandos. Digite <b>/ajuda</b> para ver a lista completa.`;
            }

            if (pergunta.startsWith('/ajuda')) {
                return `<b><i class="fa fa-terminal"></i> Terminal de Comandos ALMOX:</b><br><br>
                • <b>/adicionar [qtd] [produto]</b><br>
                • <b>/remover [qtd] [produto]</b><br>
                • <b>/banir [usuario]</b><br>
                • <b>/desbanir [usuario]</b><br>
                • <b>/promover [usuario]</b><br>
                • <b>/rebaixar [usuario]</b><br>
                • <b>/usuarios</b><br>
                • <b>/deletar [produto]</b><br>
                • <b>/estoque [produto]</b><br>
                • <b>/historico [produto]</b><br>
                • <b>/alertas</b> | <b>/status</b><br>
                • <b>/valor</b> | <b>/vendidos</b> | <b>/entradas</b> | <b>/saidas</b> | <b>/fornecedores</b><br>
                • <b>/comprar [produto]</b> (Mercado Livre)<br>
                • <b>/pesquisar [termo]</b> (Wikipedia)<br>`;
            }

            if (pergunta.startsWith('/comprar ')) {
                let searchTerm = pergunta.replace('/comprar ', '').trim();
                try {
                    const response = await fetch(`https://api.mercadolibre.com/sites/MLB/search?q=${encodeURIComponent(searchTerm)}&limit=5`);
                    const data = await response.json();
                    if (data.results && data.results.length > 0) {
                        let html = `Pesquisa no <b>Mercado Livre</b> para <b>"${searchTerm}"</b>:<br><br><div style="display:flex; flex-direction:column; gap:12px;">`;
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
                        return `Nenhum produto correspondente a "${searchTerm}" no Mercado Livre.`;
                    }
                } catch (e) {
                    return `Erro ao buscar produto: ${e.message}`;
                }
            }

            if (pergunta.startsWith('/pesquisar ')) {
                let searchTerm = pergunta.replace('/pesquisar ', '').trim();
                try {
                    const searchRes = await fetch(`https://pt.wikipedia.org/w/api.php?action=query&list=search&srsearch=${encodeURIComponent(searchTerm)}&utf8=&format=json&origin=*`);
                    const searchData = await searchRes.json();
                    if (searchData.query && searchData.query.search && searchData.query.search.length > 0) {
                        const title = searchData.query.search[0].title;
                        const summaryRes = await fetch(`https://pt.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(title)}`);
                        const data = await summaryRes.json();
                        let html = `Informação global sobre <b>${data.title}</b>:<br><br>`;
                        html += `<div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 16px; border-left: 4px solid #a8c7fa;">`;
                        if (data.thumbnail) {
                            html += `<img src="${data.thumbnail.source}" style="max-width:180px; border-radius:10px; margin-bottom:15px; float:right; margin-left:20px; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">`;
                        }
                        html += `<p style="color:#c4c7c5; line-height:1.7; font-size: 1rem;">${data.extract}</p>`;
                        html += `<div style="clear:both;"></div><br><a href="${data.content_urls.desktop.page}" target="_blank" style="color:#a8c7fa; text-decoration:none; font-weight:bold; display:inline-flex; align-items:center; gap:8px;"><i class="fa fa-external-link"></i> Acessar Registro Completo</a>`;
                        html += `</div>`;
                        return html;
                    } else {
                        return `Acessei o banco de dados global, mas não localizei registros para "<b>${searchTerm}</b>".`;
                    }
                } catch (e) {
                    return `Erro na pesquisa global.`;
                }
            }

            // INTERNAL API EXECUTION
            try {
                const fd = new FormData();
                fd.append('action', 'ia_query');
                fd.append('pergunta', perguntaOriginal);
                const res = await fetch('ia.php', { method: 'POST', body: fd });
                if (res.ok) {
                    const data = await res.json();
                    if (data.handled && data.html) {
                        return `<div style="padding: 5px;">${data.html}</div>`;
                    }
                }
            } catch (e) {
                console.error("Erro API Interna:", e);
            }

            return `Comando "<b>${perguntaOriginal}</b>" incorreto ou com parâmetros faltando. Digite <b>/ajuda</b>.`;
        }
    </script>
</body>
</html>
