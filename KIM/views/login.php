<div class="card login-box" id="login-container">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="margin-bottom: 5px; color: var(--primary);">KIM</h2>
        <p style="color: var(--text-muted);">Autentificare in sistem</p>
    </div>
    
    <div id="mesaj-eroare" class="alert alert-error hidden"></div>
    <div id="mesaj-succes" class="alert alert-success hidden"></div>
    
    <form id="login-form">
        <div class="form-group">
            <label for="email">Adresa Email</label>
            <input type="email" id="email" placeholder="nume@exemplu.ro" required>
        </div>
        <div class="form-group">
            <label for="password">Parola</label>
            <input type="password" id="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Conectare</button>
        
        <p style="text-align:center; margin-top:15px; font-size:0.85rem; color:var(--text-muted);">
            Nu ai cont? <a href="#" id="show-register" style="color:var(--primary); text-decoration:none; font-weight:600;">Inregistreaza-te</a>
        </p>
    </form>
</div>

<div class="card login-box hidden" id="register-container">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="margin-bottom: 5px; color: var(--primary);">KIM</h2>
        <p style="color: var(--text-muted);">Creare cont nou</p>
    </div>
    
    <div id="reg-mesaj-eroare" class="alert alert-error hidden"></div>
    <div id="reg-mesaj-succes" class="alert alert-success hidden"></div>
    
    <form id="register-form">
        <div class="form-group">
            <label for="reg-name">Nume Complet</label>
            <input type="text" id="reg-name" placeholder="Ex: Popescu Ion" required>
        </div>
        <div class="form-group">
            <label for="reg-email">Adresa Email</label>
            <input type="email" id="reg-email" placeholder="nume@exemplu.ro" required>
        </div>
        <div class="form-group">
            <label for="reg-password">Parola</label>
            <input type="password" id="reg-password" placeholder="••••••••" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Inregistrare</button>
        
        <p style="text-align:center; margin-top:15px; font-size:0.85rem; color:var(--text-muted);">
            Ai deja cont? <a href="#" id="show-login" style="color:var(--primary); text-decoration:none; font-weight:600;">Conecteaza-te</a>
        </p>
    </form>
</div>
