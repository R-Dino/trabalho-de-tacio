<?php
$files = [
    'index.php', 'telainicial.php', 'dashboard.php', 'produtos.php', 
    'estoque.php', 'fornecedores.php', 'relatorios.php', 'usuarios.php'
];

$css = <<<CSS

        /* MODO ESCURO GLOBAL */
        body.dark-mode { background: #0f172a; color: #f1f5f9; }
        body.dark-mode .topbar, body.dark-mode .card, body.dark-mode .table-container, body.dark-mode .form-container, body.dark-mode .report-card, body.dark-mode .chart-box, body.dark-mode .activity-box { background: #1e293b; box-shadow: none; color: #f1f5f9; }
        body.dark-mode .topbar h1, body.dark-mode .form-container h2, body.dark-mode .table-container h2 { color: #f1f5f9; }
        body.dark-mode .card h3 { color: #94a3b8; }
        body.dark-mode input, body.dark-mode select { background: #334155 !important; border: 1px solid #475569 !important; color: white !important; }
        body.dark-mode table th { background: #0f172a !important; color: #f1f5f9; border-bottom: 1px solid #334155;}
        body.dark-mode table td, body.dark-mode tr { border-bottom: 1px solid #334155 !important; color: #cbd5e1; }
        body.dark-mode .activity-item { border-bottom: 1px solid #334155; }
        body.dark-mode .activity-item p { color: #94a3b8; }
        
        /* Ajustes extras para Tela de Login */
        body.dark-mode .auth-card { background: #1e293b; box-shadow: none; }
        body.dark-mode header { background: #0f172a; border-bottom: 1px solid #334155; }
        body.dark-mode .tabs { background: #1e293b; border-bottom: 1px solid #334155; }
        body.dark-mode .tab-btn { color: #94a3b8; }
        body.dark-mode .tab-btn.active { background: #1e293b; color: #38bdf8; border-bottom: 3px solid #38bdf8; }
        body.dark-mode .field label { color: #cbd5e1; }
        body.dark-mode .form-utils { color: #94a3b8; }
        body.dark-mode .alert-error { background: #450a0a; border-color: #7f1d1d; color: #fca5a5; }
        body.dark-mode .alert-success { background: #052e16; border-color: #14532d; color: #86efac; }
CSS;

$js = <<<JS

        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem("darkMode") === "true") {
                document.body.classList.add("dark-mode");
            }
        });
JS;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Inject CSS
    if (strpos($content, '/* MODO ESCURO GLOBAL */') === false) {
        $content = str_replace('</style>', $css . "\n</style>", $content);
    }
    
    // Inject JS
    if (strpos($content, 'localStorage.getItem("darkMode")') === false) {
        if (strpos($content, '</script>') !== false) {
            // Se já tem script, insere antes de fechar a ultima ocorrencia
            $pos = strrpos($content, '</script>');
            $content = substr_replace($content, $js . "\n</script>", $pos, strlen('</script>'));
        } else {
            // Se nao tem script, adiciona no final do body
            $content = str_replace('</body>', "<script>\n" . $js . "\n</script>\n</body>", $content);
        }
    }
    
    file_put_contents($file, $content);
}
echo "Patch aplicado com sucesso.";
?>
