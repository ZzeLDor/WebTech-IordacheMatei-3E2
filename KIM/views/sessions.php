<?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'trainer', 'therapist'])): ?>
 
<div id="session-modal" class="modal-overlay" style="display: none;">
    <div class="modal-card card">
        <div class="header-flex" style="margin-bottom: 15px;">
            <h2>Creare Sesiune</h2>
            <button class="modal-close" id="close-session-modal">&times;</button>
        </div>
        
        <form id="add-session-form">
            <div class="form-row">
                <div class="form-group" style="flex:2">
                    <label>Titlu sesiune (ex: Yoga)</label>
                    <input type="text" id="ses-title" required>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Categorie</label>
                    <select id="ses-category" required>
                        <option value="fitness">🏃 Fitness</option>
                        <option value="forta">💪 Forta</option>
                        <option value="kinetoterapie">🩺 Kinetoterapie</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Descriere</label>
                <textarea id="ses-desc" rows="2" placeholder="Descriere scurta a sesiunii..." style="resize:vertical;"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Data</label>
                    <input type="text" id="display-date" disabled style="background-color: var(--bg);">
                    <input type="hidden" id="ses-date">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Inceput</label>
                    <select id="ses-start-time" required></select>
                </div>
                <div class="form-group">
                    <label>Sfarsit</label>
                    <select id="ses-end-time" required></select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Sala</label>
                    <select id="ses-room" required></select>
                </div>
                <div class="form-group">
                    <label>Capacitate</label>
                    <input type="number" id="ses-cap" required min="1">
                </div>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Salveaza Sesiunea</button>
            </div>
        </form>
    </div>
</div>

 
<div id="edit-session-modal" class="modal-overlay" style="display: none;">
    <div class="modal-card card" style="max-width:520px;">
        <div class="header-flex" style="margin-bottom: 15px;">
            <h2>Editeaza Sesiunea</h2>
            <button class="modal-close" id="close-edit-session-modal">&times;</button>
        </div>
        <form id="edit-session-form">
            <input type="hidden" id="edit-ses-id">
            <div class="form-group">
                <label>Titlu sesiune</label>
                <input type="text" id="edit-ses-title" required>
            </div>
            <div class="form-group">
                <label>Descriere</label>
                <textarea id="edit-ses-desc" rows="2" style="resize:vertical;"></textarea>
            </div>
            <div class="form-group">
                <label>Categorie</label>
                <select id="edit-ses-category" required>
                    <option value="fitness">🏃 Fitness</option>
                    <option value="forta">💪 Forta</option>
                    <option value="kinetoterapie">🩺 Kinetoterapie</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Capacitate maxima</label>
                    <input type="number" id="edit-ses-cap" required min="1">
                </div>
                <div class="form-group">
                    <label>Sala</label>
                    <select id="edit-ses-room" required></select>
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px; justify-content:flex-end;">
                <button type="button" class="btn btn-cancel-session" id="edit-ses-cancel-btn">Anuleaza Sesiunea</button>
                <button type="submit" class="btn btn-primary">Salveaza Modificarile</button>
            </div>
        </form>
        <div class="form-group cancel-reason-field hidden" id="cancel-reason-wrap" style="margin-top:12px;">
            <label>Motiv anulare (optional, va fi trimis participantilor)</label>
            <textarea id="cancel-reason-text" rows="2" placeholder="ex: Antrenorul este indisponibil..." style="resize:vertical;"></textarea>
            <div style="display:flex; gap:10px; margin-top:8px; justify-content:flex-end;">
                <button type="button" class="btn btn-outline btn-sm" id="cancel-reason-back">Inapoi</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirm-cancel-btn">Confirma Anularea</button>
            </div>
        </div>
    </div>
</div>

 
<script>
    window.USER_ROLE = "<?php echo $_SESSION['role']; ?>";
    window.USER_ID   = <?php echo (int)$_SESSION['user_id']; ?>;
</script>
<?php endif; ?>


 
<div id="session-detail-modal" class="modal-overlay" style="display: none;">
    <div class="modal-card card" style="max-width: 550px;">
        <div class="header-flex" style="margin-bottom: 15px;">
            <div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h2 id="detail-title">-</h2>
                    <span id="detail-category-badge" class="category-badge"></span>
                </div>
                <p id="detail-subtitle" style="font-size:0.85rem; color:var(--text-muted); margin-top:2px;">-</p>
            </div>
            <button class="modal-close" id="close-detail-modal">&times;</button>
        </div>

        <div id="detail-description" style="margin-bottom:16px; color:var(--text-main); font-size:0.9rem; line-height:1.5; padding:12px; background:var(--bg); border-radius:var(--radius);"></div>

        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap;">
            <div class="detail-info-chip"><span>🕐</span> <span id="detail-time">-</span></div>
            <div class="detail-info-chip"><span>📍</span> <span id="detail-room">-</span></div>
            <div class="detail-info-chip"><span>👤</span> <span id="detail-trainer">-</span></div>
            <div class="detail-info-chip"><span>👥</span> <span id="detail-capacity">-</span></div>
        </div>

        <div style="margin-bottom: 16px;">
            <h3 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px; letter-spacing:0.5px;">Participanti inscrisi</h3>
            <div id="detail-participants-list" style="display:flex; flex-direction:column; gap:6px; max-height:160px; overflow-y:auto;"></div>
        </div>

        <div id="detail-action" style="text-align:right;"></div>
    </div>
</div>

<div class="card">
    <div class="header-flex">
        <h2>Orar Sesiuni</h2>
        <div class="week-nav">
            <button class="btn btn-outline btn-sm" id="prev-week">&lt; Saptamana Anterioara</button>
            <span id="current-week-label" style="font-weight: 600; font-size: 0.9rem;"></span>
            <button class="btn btn-outline btn-sm" id="next-week">Saptamana Urmatoare &gt;</button>
        </div>
    </div>

     
    <div class="category-legend">
        <span class="legend-item legend-fitness">🏃 Fitness</span>
        <span class="legend-item legend-forta">💪 Forta</span>
        <span class="legend-item legend-kinetoterapie">🩺 Kinetoterapie</span>
    </div>
    
    <div class="table-wrapper">
        <table class="timetable" id="timetable">
            <thead>
                <tr>
                    <th class="time-col">TIME</th>
                    <th>LUNI</th>
                    <th>MARTI</th>
                    <th>MIERCURI</th>
                    <th>JOI</th>
                    <th>VINERI</th>
                    <th>SAMBATA</th>
                    <th>DUMINICA</th>
                </tr>
            </thead>
            <tbody id="timetable-body">
                 
            </tbody>
        </table>
    </div>
</div>
