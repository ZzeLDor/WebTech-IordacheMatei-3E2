<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KIM - Kineto Web Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css?v=3">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php if ($isLoggedIn): ?>
        <nav class="topbar">
            <div class="topbar-brand">
                <h2>KIM Manager</h2>
            </div>
            <ul class="nav-links">
                <li><a href="/dashboard" class="<?= $requestUri === '/dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="/users" class="<?= $requestUri === '/users' ? 'active' : '' ?>">Utilizatori</a></li>
                <?php endif; ?>
                <li><a href="/sessions" class="<?= $requestUri === '/sessions' ? 'active' : '' ?>">Orar Sesiuni</a></li>
                <li><a href="/requests" class="<?= $requestUri === '/requests' ? 'active' : '' ?>">Cereri Private</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="/stats" class="<?= $requestUri === '/stats' ? 'active' : '' ?>">Statistici & Export</a></li>
                <?php endif; ?>
            </ul>
            <div class="topbar-user">
                <a href="/profile" class="profile-link <?= $requestUri === '/profile' ? 'active' : '' ?>">
                    <span class="profile-avatar"><?= isset($_SESSION['name']) ? htmlspecialchars(mb_substr($_SESSION['name'], 0, 1)) : '?' ?></span>
                    <span class="profile-name"><?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ucfirst($_SESSION['role'] ?? '') ?></span>
                </a>
                <button id="logout-btn" class="btn btn-outline btn-sm">Deconectare</button>
            </div>
        </nav>
        <main class="content">
            <?php require __DIR__ . '/' . $view; ?>
        </main>
    <?php else: ?>
        <main class="auth-layout">
            <?php require __DIR__ . '/' . $view; ?>
        </main>
    <?php endif; ?>
    
    <script src="/js/app.js?v=6"></script>
</body>
</html>
