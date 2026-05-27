<?php   ?>

<div class="card">
    <div class="header-flex">
        <h2>Cereri Sesiuni Private</h2>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'member'): ?>
            <button id="btn-open-request-modal" class="btn btn-primary">Creeaza Cerere</button>
        <?php endif; ?>
    </div>

    <div class="table-wrapper" style="margin-top:20px;">
        <table>
            <thead>
                <tr>
                    <th>Titlu</th>
                    <th>Categorie</th>
                    <th>Utilizator</th>
                    <th>Preferinta Antrenor</th>
                    <th>Data & Ora</th>
                    <th>Status</th>
                    <th>Actiuni</th>
                </tr>
            </thead>
            <tbody id="requests-table-body">
                 
            </tbody>
        </table>
    </div>
</div>

 
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'member'): ?>
<div class="modal-overlay" id="request-modal" style="display:none;">
    <div class="modal-card card">
        <div class="header-flex" style="margin-bottom:15px;">
            <h3>Creeaza Cerere Privata</h3>
            <button class="modal-close" id="close-request-modal">&times;</button>
        </div>
        <form id="request-form">
            <div class="form-group">
                <label>Titlu sesiune</label>
                <input type="text" id="req-title" required>
            </div>
            <div class="form-group">
                <label>Categorie</label>
                <select id="req-category" required>
                    <option value="fitness">Fitness</option>
                    <option value="forta">Forta</option>
                    <option value="kinetoterapie">Kinetoterapie</option>
                </select>
            </div>
            <div class="form-group">
                <label>Descriere (ce iti doresti)</label>
                <textarea id="req-desc" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Preferinta Antrenor</label>
                <select id="req-trainer">
                    <option value="">Oricare din categorie</option>
                     
                </select>
            </div>
            <div class="form-group">
                <label>Data dorita</label>
                <input type="date" id="req-date" required>
            </div>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label>Ora Start</label>
                    <input type="time" id="req-start" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Ora Sfarsit</label>
                    <input type="time" id="req-end" required>
                </div>
            </div>
            <div id="schedule-warning" style="display:none; color:#ef4444; font-size:0.85rem; margin-top:10px; padding:8px; border-radius:4px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); font-weight:500;"></div>
            <button type="submit" id="request-submit-btn" class="btn btn-primary" style="width:100%; margin-top:10px;">Trimite Cererea</button>
        </form>
    </div>
</div>
<?php endif; ?>

 
<?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['trainer', 'therapist', 'admin'])): ?>
<div class="modal-overlay" id="accept-request-modal" style="display:none;">
    <div class="modal-card card">
        <div class="header-flex" style="margin-bottom:15px;">
            <h3>Accepta Cerere Privata</h3>
            <button class="modal-close" id="close-accept-modal">&times;</button>
        </div>
        <form id="accept-request-form">
            <input type="hidden" id="accept-req-id">
            <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:15px;">
                Pentru a accepta cererea si a programa sesiunea, te rugam sa aloci o sala:
            </p>
            <div class="form-group">
                <label>Alege Sala</label>
                <select id="accept-req-room" required>
                     
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; background-color:#16a34a;">Confirma & Creeaza Sesiunea</button>
        </form>
    </div>
</div>
<?php endif; ?>
