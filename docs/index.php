<?php

require_once __DIR__ . '/../includes/app.php';

use App\Utils\Documentation;

$action = $_GET['action'] ?? 'home';
$component = $_GET['component'] ?? null;
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

$data = [
    'stats' => Documentation::stats(),
    'categories' => Documentation::categories(),
    'docs' => Documentation::list()
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docs - Infinity Framework</title>
    <link rel="icon" type="image/svg+xml" href="/resources/view/assets/favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --bg: #0b0f1a;
            --bg-accent: #131926;
            --card: rgba(30, 41, 59, 0.5);
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --secondary: #a855f7;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --glass: rgba(255, 255, 255, 0.02);
            --glass-border: rgba(255, 255, 255, 0.07);
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg);
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(168, 85, 247, 0.15) 0%, transparent 40%);
            background-attachment: fixed;
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        header {
            text-align: center;
            margin-bottom: 5rem;
            position: relative;
        }

        .brand-logo {
            width: 120px;
            height: 60px;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 25px rgba(99, 102, 241, 0.5));
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        .search-bar {
            max-width: 600px;
            margin: 3rem auto 0;
            position: relative;
        }

        .search-bar i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.2rem;
        }

        .search-bar input {
            width: 100%;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--glass-border);
            padding: 18px 20px 18px 50px;
            border-radius: 20px;
            color: white;
            font-size: 1.1rem;
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            background: rgba(0,0,0,0.5);
        }

        .btn-outline {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-outline:hover, .btn-outline.active {
            background: rgba(99, 102, 241, 0.1);
            border-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 4rem;
        }

        .stat-card {
            background: var(--card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            padding: 2rem;
            border-radius: 24px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--primary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .nav-tabs {
            display: flex;
            gap: 12px;
            padding-top: 1rem;
            margin-bottom: 3rem;
            overflow-x: auto;
            justify-content: center;
            padding-bottom: 10px;
        }

        .docs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .doc-card {
            background: var(--card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            padding: 2.5rem;
            border-radius: 24px;
            text-decoration: none;
            color: inherit;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .doc-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        }

        .doc-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .doc-card h3 {
            color: #fff;
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }

        .doc-card p {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.6;
            flex: 1;
            margin-bottom: 2rem;
        }

        .doc-card .meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--glass-border);
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .badge {
            background: rgba(99, 102, 241, 0.1);
            color: #818cf8;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Detail Page */
        .doc-detail {
            background: var(--card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            padding: 4rem;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 2rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--primary);
        }

        h2 { font-size: 2.8rem; margin-bottom: 2rem; color: white; }
        h3 { font-size: 1.6rem; margin-top: 3rem; margin-bottom: 1.5rem; color: #fff; border-left: 4px solid var(--primary); padding-left: 15px; }

        .code-block {
            background: #0f1117;
            color: #e2e8f0;
            padding: 2rem;
            border-radius: 16px;
            overflow-x: auto;
            margin: 1.5rem 0;
            font-family: 'Fira Code', monospace;
            border: 1px solid var(--glass-border);
            font-size: 0.95rem;
            line-height: 1.6;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }

        .methods-list { list-style: none; }
        .method-item {
            background: rgba(255,255,255,0.02);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--glass-border);
            transition: border-color 0.3s;
        }
        
        .method-item:hover { border-color: rgba(99, 102, 241, 0.3); }
        .method-item code { color: #a5b4fc; font-weight: 700; font-size: 1.1rem; font-family: monospace; }
        .method-item p { margin-top: 1rem; color: var(--text-muted); font-size: 1rem; }

        footer {
            margin-top: auto;
            padding: 4rem 0;
            text-align: center;
            color: var(--text-muted);
            border-top: 1px solid var(--glass-border);
        }

        .animate { animation: fadeIn 0.8s ease forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container animate">
        <header>
            <a href="?action=home" style="display: inline-block;">
                <svg class="brand-logo" viewBox="0 0 100 50">
                    <defs>
                        <linearGradient id="doc-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#a855f7;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <path d="M50,25 C35,10 15,10 15,25 C15,40 35,40 50,25 C65,10 85,10 85,25 C85,40 65,40 50,25" fill="none" stroke="url(#doc-gradient)" stroke-width="6" stroke-linecap="round" />
                </svg>
            </a>
            <h1 class="page-title">Documentação</h1>
            <p style="color: var(--text-muted); font-size: 1.1rem;">Explore o universo do Infinity Framework</p>
            
            <div class="search-bar">
                <i class='bx bx-search'></i>
                <input type="text" id="searchInput" placeholder="Pesquisar componentes, métodos...">
            </div>
        </header>

        <?php if ($action === 'home'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= $data['stats']['total_components'] ?></h3>
                    <p>Componentes</p>
                </div>
                <div class="stat-card">
                    <h3><?= $data['stats']['total_methods'] ?></h3>
                    <p>Métodos</p>
                </div>
                <div class="stat-card">
                    <h3><?= $data['stats']['total_categories'] ?></h3>
                    <p>Categorias</p>
                </div>
            </div>

            <div class="nav-tabs">
                <a href="?action=home" class="btn-outline active">Todos</a>
                <?php foreach ($data['categories'] as $cat): ?>
                    <a href="?action=category&category=<?= urlencode($cat) ?>" class="btn-outline">
                        <?= $cat ?>
                    </a>
                <?php endforeach; ?>
                <a href="export.php?format=json" class="btn-outline"><i class='bx bx-download' style="margin-right:5px;"></i> JSON</a>
            </div>

            <div class="docs-grid">
                <?php foreach ($data['docs'] as $name => $doc): ?>
                    <a href="?action=component&component=<?= urlencode($name) ?>" class="doc-card">
                        <h3><?= htmlspecialchars($doc['title']) ?></h3>
                        <p><?= htmlspecialchars($doc['description']) ?></p>
                        <div class="meta">
                            <span class="badge"><?= $doc['category'] ?></span>
                            <span><i class='bx bx-code-alt'></i> <?= count($doc['methods']) ?> métodos</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php elseif ($action === 'component' && $component): ?>
            <?php $doc = Documentation::get($component); ?>
            <?php if ($doc): ?>
                <a href="?action=home" class="back-link"><i class='bx bx-left-arrow-alt' style="font-size: 1.4rem; margin-right: 5px;"></i> Voltar para a lista</a>
                <div class="doc-detail">
                    <h2><?= htmlspecialchars($doc['title']) ?></h2>
                    <div style="display: flex; gap: 10px; margin-bottom: 2rem;">
                        <span class="badge" style="background: var(--primary); color: white;">Versão <?= $doc['version'] ?></span>
                        <span class="badge"><?= $doc['category'] ?></span>
                    </div>

                    <p style="font-size: 1.25rem; line-height: 1.7; color: var(--text-muted); font-weight: 300;"><?= nl2br(htmlspecialchars($doc['description'])) ?></p>

                    <?php if (!empty($doc['usage'])): ?>
                        <h3><i class='bx bx-terminal' style="margin-right: 10px;"></i> Uso Básico</h3>
                        <div class="code-block"><?= htmlspecialchars($doc['usage']) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($doc['example'])): ?>
                        <h3><i class='bx bx-code-block' style="margin-right: 10px;"></i> Exemplo Completo</h3>
                        <div class="code-block"><?= htmlspecialchars($doc['example']) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($doc['methods'])): ?>
                        <h3><i class='bx bx-list-ul' style="margin-right: 10px;"></i> Métodos Disponíveis</h3>
                        <div class="methods-list">
                            <?php foreach ($doc['methods'] as $method): ?>
                                <div class="method-item">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <code><?= $method['name'] ?>()</code>
                                        <span style="font-size: 0.8rem; color: var(--primary); background: rgba(99, 102, 241, 0.1); padding: 4px 10px; border-radius: 8px;"><?= $method['return'] ?></span>
                                    </div>
                                    <p><?= htmlspecialchars($method['description']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($action === 'category' && $category): ?>
            <?php $docs = Documentation::listByCategory($category); ?>
            <a href="?action=home" class="back-link"><i class='bx bx-left-arrow-alt' style="font-size: 1.4rem; margin-right: 5px;"></i> Voltar</a>
            <div class="nav-tabs">
                <a href="?action=home" class="btn-outline">Todos</a>
                <?php foreach ($data['categories'] as $cat): ?>
                    <a href="?action=category&category=<?= urlencode($cat) ?>" class="btn-outline <?= $category === $cat ? 'active' : '' ?>">
                        <?= $cat ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="docs-grid">
                <?php foreach ($docs as $name => $doc): ?>
                    <a href="?action=component&component=<?= urlencode($name) ?>" class="doc-card">
                        <h3><?= htmlspecialchars($doc['title']) ?></h3>
                        <p><?= htmlspecialchars($doc['description']) ?></p>
                        <div class="meta">
                            <span class="badge"><?= $doc['category'] ?></span>
                            <span><i class='bx bx-code-alt'></i> v<?= $doc['version'] ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <footer>
            <div style="opacity: 0.7; margin-bottom: 20px;">
                <svg style="width: 40px; height: 20px;" viewBox="0 0 100 50">
                    <path d="M50,25 C35,10 15,10 15,25 C15,40 35,40 50,25 C65,10 85,10 85,25 C85,40 65,40 50,25" fill="none" stroke="#6366f1" stroke-width="4" stroke-linecap="round" />
                </svg>
            </div>
            <p>&copy; 2025 Infinity Framework. Documentação Offline-First de Alta Performance.</p>
        </footer>
    </div>

    <script>
        function performSearch() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.doc-card');
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(query) ? 'flex' : 'none';
            });
        }
        document.getElementById('searchInput').addEventListener('keyup', performSearch);
    </script>
</body>
</html>
