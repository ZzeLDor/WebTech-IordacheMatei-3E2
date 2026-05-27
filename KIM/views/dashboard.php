<div class="card">
    <h2>Panou de Control</h2>
    <p>Bine ai venit in aplicatia KIM, <strong><?= isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : '' ?></strong>!</p>
    <br>
    <div class="form-row">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="/users" class="btn btn-primary">Gestiune Utilizatori</a>
        <?php endif; ?>
        <a href="/requests" class="btn btn-outline">Vezi Cereri Private</a>
        <a href="/sessions" class="btn btn-outline">Vezi Sesiuni</a>
        <a href="/scholarly.html" class="btn btn-outline" target="_blank">Scholarly HTML</a>
    </div>
</div>
