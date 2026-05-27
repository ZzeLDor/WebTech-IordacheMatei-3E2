<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="card">
    <div class="header-flex" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Gestiune Utilizatori</h2>
        <div>
            <input type="text" id="user-search" placeholder="Cauta dupa nume sau email..." style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 4px; width: 250px;">
        </div>
    </div>
    
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Specializare</th>
                    <th>Abonament Activ</th>
                    <th>Actiune</th>
                </tr>
            </thead>
            <tbody id="users-table-body">
                 
            </tbody>
        </table>
    </div>
</div>




<div class="modal-overlay" id="subscription-modal" style="display:none;">
    <div class="modal-card card" style="max-width: 560px; width: 95%;">

         
        <div class="header-flex" style="margin-bottom: 18px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem;">Gestioneaza Abonament</h3>
                <p style="margin: 3px 0 0; font-size: 0.82rem; color: var(--text-muted);" id="sub-modal-username-line">
                    Utilizator: <strong id="sub-modal-username"></strong>
                </p>
            </div>
            <button class="modal-close" id="close-sub-modal">&times;</button>
        </div>

         
        <div style="background: var(--bg); border: 1px solid var(--border); border-radius: 6px; padding: 14px 16px; margin-bottom: 18px;">
            <p style="margin: 0 0 10px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted); font-weight: 600;">Abonament Curent</p>
            <div id="current-sub-content">
                <span style="color: var(--text-muted); font-size: 0.9rem;">Se incarca...</span>
            </div>
        </div>

         
        <details id="add-sub-details" style="margin-bottom: 18px;">
            <summary style="cursor: pointer; font-size: 0.88rem; font-weight: 600; color: var(--text-main); padding: 6px 0; user-select: none;">
                ➕ Adauga / Reinnoieste Abonament
            </summary>
            <div style="margin-top: 12px;">
                <form id="add-sub-form">
                    <input type="hidden" id="sub-user-id">
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label>Tip Abonament</label>
                            <select id="sub-type" required>
                                <option value="fitness">🏃 Fitness</option>
                                <option value="strength">💪 Forta</option>
                                <option value="kineto">🩺 Kinetoterapie</option>
                                <option value="mixed">⚡ Mixt (toate categoriile)</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Luni valabilitate</label>
                            <input type="number" id="sub-months" required min="1" max="24" value="1">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 4px;">Salveaza Abonament</button>
                </form>
            </div>
        </details>

         
        <details id="sub-history-details">
            <summary style="cursor: pointer; font-size: 0.88rem; font-weight: 600; color: var(--text-main); padding: 6px 0; user-select: none;">
                📋 Istoric Abonamente
            </summary>
            <div id="sub-history-content" style="margin-top: 12px; max-height: 220px; overflow-y: auto;">
                <span style="color: var(--text-muted); font-size: 0.85rem;">Se incarca...</span>
            </div>
        </details>

    </div>
</div>

<?php else: ?>
<div class="alert alert-error">Nu ai permisiunea de a accesa aceasta pagina.</div>
<?php endif; ?>
