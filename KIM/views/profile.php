<?php   ?>

<div class="page-header">
    <h1>Profilul meu</h1>
</div>

<div class="profile-layout">

     
    <div class="profile-sidebar">

         
        <div class="card profile-hero">
            <div class="profile-hero-avatar" id="prof-avatar">?</div>
            <div class="profile-hero-info">
                <h2 id="prof-name">—</h2>
                <span class="role-badge" id="prof-role">—</span>
                <p id="prof-email" style="font-size:0.83rem; color:var(--text-muted); margin-top:4px;">—</p>
                <p id="prof-since" style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;"></p>
            </div>
        </div>

         
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'member'): ?>
        <div class="card">
            <h3 style="margin-bottom:14px; font-size:0.9rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted);">Abonament activ</h3>
            <div id="prof-sub-content">
                <div class="skeleton-line"></div>
                <div class="skeleton-line" style="width:60%"></div>
            </div>
        </div>
        <?php endif; ?>

    </div>

     
    <div class="profile-main">
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['trainer', 'therapist'])): ?>
        <div class="card" id="schedule-editor-card">
            <h3 style="margin-bottom:16px; font-size:0.9rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted);">Programul meu de lucru</h3>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:20px;">
                Configureaza zilele in care esti disponibil si intervalul orar in care poti fi solicitat pentru sesiuni private:
            </p>
            <form id="profile-schedule-form">
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <?php 
                    $zile = [
                        1 => 'Luni',
                        2 => 'Marti',
                        3 => 'Miercuri',
                        4 => 'Joi',
                        5 => 'Vineri',
                        6 => 'Sambata',
                        0 => 'Duminica'
                    ];
                    foreach ($zile as $num => $nume): 
                    ?>
                    <div class="schedule-day-row" style="display:flex; align-items:center; justify-content:space-between; padding:10px; background:var(--bg); border-radius:var(--radius); border:1px solid var(--border); flex-wrap:wrap; gap:10px;">
                        <div style="display:flex; align-items:center; gap:10px; min-width:120px;">
                            <input type="checkbox" id="working-<?= $num ?>" class="day-working-checkbox" data-day="<?= $num ?>" style="width:18px; height:18px; cursor:pointer;">
                            <label for="working-<?= $num ?>" style="margin-bottom:0; font-weight:600; font-size:0.95rem; cursor:pointer;"><?= $nume ?></label>
                        </div>
                        <div class="time-inputs-wrap" id="time-wrap-<?= $num ?>" style="display:flex; align-items:center; gap:8px; opacity:0.5; pointer-events:none; transition:opacity 0.2s;">
                            <input type="time" id="start-<?= $num ?>" class="day-start-time" value="08:00" style="width:100px; padding:6px 8px;">
                            <span style="font-size:0.85rem; color:var(--text-muted);">pana la</span>
                            <input type="time" id="end-<?= $num ?>" class="day-end-time" value="16:00" style="width:100px; padding:6px 8px;">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:20px; text-align:right;">
                    <button type="submit" class="btn btn-primary" id="btn-save-schedule" style="padding: 10px 24px;">Salveaza programul</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom:16px; font-size:0.9rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted);">Istoric activitate</h3>
            <div id="prof-history">
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line" style="width:70%"></div>
            </div>
        </div>
    </div>

</div>

 
<div class="modal-overlay" id="prof-detail-modal" style="display:none;">
    <div class="modal-card card" style="max-width:550px;">
        <div class="header-flex" style="margin-bottom:15px;">
            <div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h2 id="prof-detail-title">—</h2>
                    <span id="prof-detail-cat-badge" class="category-badge"></span>
                </div>
                <p id="prof-detail-subtitle" style="font-size:0.85rem; color:var(--text-muted); margin-top:2px;">—</p>
            </div>
            <button class="modal-close" id="prof-close-detail">&times;</button>
        </div>

        <p id="prof-detail-description" style="font-size:0.88rem; color:var(--text-muted); margin-bottom:16px; line-height:1.6;">—</p>

        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px;">
            <span class="detail-info-chip">🕐 <span id="prof-detail-time">—</span></span>
            <span class="detail-info-chip">🏠 <span id="prof-detail-room">—</span></span>
            <span class="detail-info-chip">👤 <span id="prof-detail-trainer">—</span></span>
            <span class="detail-info-chip">👥 <span id="prof-detail-capacity">—</span></span>
        </div>

        <div>
            <p style="font-size:0.8rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px;">Participanti inscrisi</p>
            <div id="prof-detail-participants" style="display:flex; flex-direction:column; gap:6px;"></div>
        </div>
    </div>
</div>
