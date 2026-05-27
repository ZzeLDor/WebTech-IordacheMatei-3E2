<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<style>
     
    .tabs-container {
        border-bottom: 2px solid var(--border);
        margin-bottom: 30px;
    }
    .tab-buttons {
        display: flex;
        gap: 10px;
    }
    .tab-btn {
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .tab-btn:hover {
        color: var(--primary);
    }
    .tab-btn.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }

     
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }
    .chart-container {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        text-align: center;
    }
    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .chart-header h3 {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .chart-wrapper {
        position: relative;
        height: 250px;
        margin-bottom: 15px;
    }

     
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #fee2e2; color: #991b1b; }

     
    .admin-form-box {
        background: #f9fafb;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 30px;
    }
</style>

<div class="header-flex">
    <h1>Administrare Resurse & Statistici</h1>
</div>

 
<div class="tabs-container">
    <div class="tab-buttons">
        <button class="tab-btn active" onclick="switchTab('tab-stats')">📈 Diagrame & Statistici</button>
        <button class="tab-btn" onclick="switchTab('tab-rooms')">🏛️ Sali & Zone</button>
        <button class="tab-btn" onclick="switchTab('tab-equipment')">⚙️ Echipamente</button>
        <button class="tab-btn" onclick="switchTab('tab-import')">📂 Import & Export Date</button>
    </div>
</div>

 
<div id="tab-stats" class="tab-content active">
    <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin-bottom: 5px;">Exporta Rapoarte Complexe</h3>
                <p class="text-muted" style="font-size: 0.9rem; margin: 0;">Descarca datele statistice brute ale sistemului KIM.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="/api/stats/export/csv" class="btn btn-primary">📁 Descarca CSV</a>
                <a href="/api/stats/export/xml" class="btn btn-outline">📄 Descarca XML</a>
            </div>
        </div>
    </div>

     
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 25px;">
        <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 20px;">
            <div>
                <span class="text-muted" style="font-size: 0.85rem; font-weight: 600;">UTILIZATORI TOTALI</span>
                <h2 id="metric-total-users" style="font-size: 2rem; margin-top: 5px; color: var(--primary);">0</h2>
            </div>
            <div style="font-size: 2.5rem; color: var(--border);">👥</div>
        </div>
        <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 20px;">
            <div>
                <span class="text-muted" style="font-size: 0.85rem; font-weight: 600;">UTILIZATORI ACTIVI (ABONAMENT)</span>
                <h2 id="metric-active-users" style="font-size: 2rem; margin-top: 5px; color: #10b981;">0</h2>
            </div>
            <div style="font-size: 2.5rem; color: var(--border);">⚡</div>
        </div>
    </div>

    <div class="stats-grid">
         
        <div class="chart-container">
            <div class="chart-header">
                <h3>Distributie Abonamente</h3>
                <div style="display:flex; gap:5px;">
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartSubs', 'png')">PNG</button>
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartSubs', 'webp')">WebP</button>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartSubs"></canvas>
            </div>
        </div>

         
        <div class="chart-container">
            <div class="chart-header">
                <h3>Rezervari pe Categorii</h3>
                <div style="display:flex; gap:5px;">
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartBooks', 'png')">PNG</button>
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartBooks', 'webp')">WebP</button>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartBooks"></canvas>
            </div>
        </div>

         
        <div class="chart-container">
            <div class="chart-header">
                <h3>Grad Utilizare Sali</h3>
                <div style="display:flex; gap:5px;">
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartRooms', 'png')">PNG</button>
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartRooms', 'webp')">WebP</button>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartRooms"></canvas>
            </div>
        </div>

         
        <div class="chart-container">
            <div class="chart-header">
                <h3>Sesiuni Rezervate</h3>
                <div style="display:flex; gap:5px; align-items: center;">
                    <select id="periodTypeSelect" onchange="switchPeriodType()" style="padding: 4px 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 0.8rem; background: var(--surface);">
                        <option value="day">Zilnic (Ultimele 7 zile)</option>
                        <option value="week">Saptamanal (Ultimele 6 sapt)</option>
                        <option value="month">Lunar (Ultimele 6 luni)</option>
                    </select>
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartPeriod', 'png')">PNG</button>
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartPeriod', 'webp')">WebP</button>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartPeriod"></canvas>
            </div>
        </div>

         
        <div class="chart-container">
            <div class="chart-header">
                <h3>Top Antrenori (dupa sesiuni)</h3>
                <div style="display:flex; gap:5px;">
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartTrainers', 'png')">PNG</button>
                    <button class="btn btn-outline btn-sm" onclick="downloadChart('chartTrainers', 'webp')">WebP</button>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartTrainers"></canvas>
            </div>
        </div>
    </div>
</div>

 
<div id="tab-rooms" class="tab-content">
    <div class="admin-form-box">
        <h3 id="room-form-title" style="margin-bottom: 15px;">Adauga o sala / zona noua</h3>
        <form id="room-form" onsubmit="saveRoom(event)">
            <input type="hidden" id="room-id" value="">
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label for="room-name">Nume sala / zona</label>
                    <input type="text" id="room-name" placeholder="Ex: Sala de Forta, Zona Cardio, Cabinet Kineto" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="room-capacity">Capacitate maxima</label>
                    <input type="number" id="room-capacity" min="1" placeholder="Ex: 20" required>
                </div>
            </div>
            <div class="form-group">
                <label for="room-description">Descriere</label>
                <input type="text" id="room-description" placeholder="Detalii suplimentare despre sala...">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Salveaza</button>
                <button type="button" class="btn btn-outline" onclick="resetRoomForm()">Anuleaza / Reset</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Sali si Zone Inregistrate</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nume Sala</th>
                        <th>Capacitate</th>
                        <th>Descriere</th>
                        <th style="width: 150px; text-align: right;">Actiuni</th>
                    </tr>
                </thead>
                <tbody id="rooms-table-body">
                    <tr><td colspan="4">Se incarca salile...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

 
<div id="tab-equipment" class="tab-content">
    <div class="admin-form-box">
        <h3 id="equip-form-title" style="margin-bottom: 15px;">Adauga echipament nou</h3>
        <form id="equip-form" onsubmit="saveEquipment(event)">
            <input type="hidden" id="equip-id" value="">
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label for="equip-name">Nume Echipament</label>
                    <input type="text" id="equip-name" placeholder="Ex: Banda de Alergat TechnoGym" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="equip-sn">Cod Serial (Unic)</label>
                    <input type="text" id="equip-sn" placeholder="Ex: SN-12345" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="equip-status">Stare de functionare</label>
                    <select id="equip-status">
                        <option value="functional">Functional</option>
                        <option value="maintenance">In Mentenanta</option>
                        <option value="broken">Defect</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="equip-room">Sala alocata</label>
                    <select id="equip-room">
                        <option value="">Fara sala (Depozit)</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Salveaza</button>
                <button type="button" class="btn btn-outline" onclick="resetEquipForm()">Anuleaza / Reset</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Echipamente din Inventar</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nume Echipament</th>
                        <th>Cod Serial</th>
                        <th>Stare</th>
                        <th>Sala Unde Se Afla</th>
                        <th style="width: 150px; text-align: right;">Actiuni</th>
                    </tr>
                </thead>
                <tbody id="equip-table-body">
                    <tr><td colspan="5">Se incarca echipamentele...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

 
<div id="tab-import" class="tab-content">
    <div class="card" style="margin-bottom: 30px;">
        <h2>Importa Specialisti (Antrenori / Terapeuti)</h2>
        <p class="text-muted" style="margin-bottom: 20px;">Incarca un fisier in format <strong>CSV</strong> sau <strong>XML</strong> pentru a importa sau actualiza lista specialistilor si specializarile acestora in mod automat.</p>

        <form id="import-form" onsubmit="importSpecialists(event)" enctype="multipart/form-data">
            <div class="form-group" style="max-width: 500px;">
                <label for="import-file">Selecteaza fisierul (CSV sau XML)</label>
                <input type="file" id="import-file" accept=".csv,.xml" required style="border: 1px dashed var(--border); padding: 20px; text-align: center;">
            </div>
            <button type="submit" class="btn btn-primary">Importa Specialisti</button>
        </form>

        <div style="margin-top: 25px; background: #f3f4f6; padding: 15px; border-radius: var(--radius); font-size: 0.9rem;">
            <strong style="display: block; margin-bottom: 5px;">Formate Acceptate:</strong>
            <ul style="margin-left: 20px; color: var(--text-muted);">
                <li><strong>CSV</strong>: Trebuie sa contina antetul exact <code>nume,email,rol,specializare</code> (Parola initiala implicita va fi setata la <code>KimUser123!</code>).</li>
                <li><strong>XML</strong>: Trebuie sa aiba structura exacta <code>&lt;specialisti&gt;&lt;specialist&gt;&lt;nume&gt;...&lt;/nume&gt;&lt;email&gt;...&lt;/email&gt;&lt;rol&gt;...&lt;/rol&gt;&lt;specializare&gt;...&lt;/specializare&gt;&lt;/specialist&gt;&lt;/specialisti&gt;</code>.</li>
            </ul>
        </div>
    </div>

    <div class="card">
        <h2>Export Date Specialisti</h2>
        <p class="text-muted" style="margin-bottom: 20px;">Descarca baza de date completa a antrenorilor si terapeutilor inregistrati.</p>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.location.href='/api/export/csv'" class="btn btn-outline">📥 Exporta Specialisti CSV</button>
            <button onclick="window.location.href='/api/export/xml'" class="btn btn-outline">📄 Exporta Specialisti XML</button>
        </div>
    </div>
</div>

<script>
    
    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.getAttribute('onclick').includes(tabId));
        if (activeBtn) activeBtn.classList.add('active');

        const activeContent = document.getElementById(tabId);
        if (activeContent) activeContent.classList.add('active');

        
        if (tabId === 'tab-rooms' || tabId === 'tab-equipment') {
            loadResourcesAndEquipment();
        } else if (tabId === 'tab-stats') {
            renderStatsCharts();
        }
    }

    
    let subsChartInstance = null;
    let booksChartInstance = null;
    let roomsChartInstance = null;
    let periodChartInstance = null;
    let trainersChartInstance = null;
    let globalPeriodData = null;

    
    function switchPeriodType() {
        const select = document.getElementById('periodTypeSelect');
        if (!select || !globalPeriodData) return;
        renderPeriodChart(select.value);
    }

    function renderPeriodChart(type) {
        const dataArr = globalPeriodData[type] || [];
        const labels = dataArr.map(d => d.period);
        const values = dataArr.map(d => d.cnt);

        if (periodChartInstance) periodChartInstance.destroy();
        
        let labelText = 'Rezervari zilnice';
        let bg = 'rgba(59, 130, 246, 0.2)';
        let border = '#3b82f6';
        if (type === 'week') {
            labelText = 'Rezervari saptamanale';
            bg = 'rgba(16, 185, 129, 0.2)';
            border = '#10b981';
        } else if (type === 'month') {
            labelText = 'Rezervari lunare';
            bg = 'rgba(245, 158, 11, 0.2)';
            border = '#f59e0b';
        }

        periodChartInstance = new Chart(document.getElementById('chartPeriod'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: labelText,
                    data: values,
                    backgroundColor: bg,
                    borderColor: border,
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    
    async function renderStatsCharts() {
        try {
            const res = await fetch('/api/stats/json');
            const data = await res.json();
            if (data.status !== 'ok') return;

            
            if (data.activeUsers) {
                document.getElementById('metric-total-users').innerText = data.activeUsers.total;
                document.getElementById('metric-active-users').innerText = data.activeUsers.active;
            }

            
            const subLabels = data.subscriptions.map(s => s.type.toUpperCase());
            const subValues = data.subscriptions.map(s => s.cnt);

            if (subsChartInstance) subsChartInstance.destroy();
            subsChartInstance = new Chart(document.getElementById('chartSubs'), {
                type: 'doughnut',
                data: {
                    labels: subLabels,
                    datasets: [{
                        data: subValues,
                        backgroundColor: ['#111827', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            
            const bookLabels = data.bookings.map(b => b.category.toUpperCase());
            const bookValues = data.bookings.map(b => b.cnt);

            if (booksChartInstance) booksChartInstance.destroy();
            booksChartInstance = new Chart(document.getElementById('chartBooks'), {
                type: 'bar',
                data: {
                    labels: bookLabels,
                    datasets: [{
                        label: 'Rezervari active',
                        data: bookValues,
                        backgroundColor: '#3b82f6',
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            
            const roomLabels = data.resources.map(r => r.room_name);
            const roomValues = data.resources.map(r => r.cnt);

            if (roomsChartInstance) roomsChartInstance.destroy();
            roomsChartInstance = new Chart(document.getElementById('chartRooms'), {
                type: 'bar',
                data: {
                    labels: roomLabels,
                    datasets: [{
                        label: 'Sesiuni Alocate',
                        data: roomValues,
                        backgroundColor: '#10b981',
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            
            globalPeriodData = data.bookingsPeriod;
            const selectEl = document.getElementById('periodTypeSelect');
            if (selectEl) {
                renderPeriodChart(selectEl.value);
            } else {
                renderPeriodChart('day');
            }

            
            const trainerLabels = data.topTrainers.map(t => t.name);
            const trainerValues = data.topTrainers.map(t => t.cnt);

            if (trainersChartInstance) trainersChartInstance.destroy();
            trainersChartInstance = new Chart(document.getElementById('chartTrainers'), {
                type: 'bar',
                data: {
                    labels: trainerLabels,
                    datasets: [{
                        label: 'Numar Sesiuni',
                        data: trainerValues,
                        backgroundColor: '#f59e0b',
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

        } catch (e) {
            console.error("Failed to load statistics charts", e);
        }
    }

    
    function downloadChart(canvasId, format) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const url = canvas.toDataURL('image/' + format, 1.0);
        const a = document.createElement('a');
        a.href = url;
        a.download = canvasId + '_' + new Date().toISOString().slice(0, 10) + '.' + format;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    
    let globalRoomsList = [];

    
    async function loadResourcesAndEquipment() {
        try {
            const res = await fetch('/api/admin/resources');
            const data = await res.json();
            if (data.status !== 'ok') return;

            globalRoomsList = data.rooms;

            
            const selectRoom = document.getElementById('equip-room');
            selectRoom.innerHTML = '<option value="">Fara sala (Depozit)</option>';
            data.rooms.forEach(r => {
                selectRoom.innerHTML += `<option value="${r.id}">${r.name} (Capacitate: ${r.capacity})</option>`;
            });

            
            const roomsBody = document.getElementById('rooms-table-body');
            if (data.rooms.length === 0) {
                roomsBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Nu exista sali inregistrate.</td></tr>';
            } else {
                roomsBody.innerHTML = '';
                data.rooms.forEach(r => {
                    roomsBody.innerHTML += `
                        <tr>
                            <td><strong>${escapeHtml(r.name)}</strong></td>
                            <td>${r.capacity} locuri</td>
                            <td style="color:var(--text-muted); font-size:0.9rem;">${escapeHtml(r.description || '—')}</td>
                            <td style="text-align: right;">
                                <button class="btn btn-outline btn-sm" onclick="editRoom(${JSON.stringify(r).replace(/"/g, '&quot;')})" style="margin-right: 5px;">Editeaza</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteRoom(${r.id})">Sterge</button>
                            </td>
                        </tr>
                    `;
                });
            }

            // Populate Equipment Table
            const equipBody = document.getElementById('equip-table-body');
            if (data.equipment.length === 0) {
                equipBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Nu exista echipamente inregistrate in inventar.</td></tr>';
            } else {
                equipBody.innerHTML = '';
                data.equipment.forEach(e => {
                    let badgeClass = 'badge-success';
                    let badgeText = 'Functional';
                    if (e.status === 'maintenance') {
                        badgeClass = 'badge-warning';
                        badgeText = 'Mentenanta';
                    } else if (e.status === 'broken') {
                        badgeClass = 'badge-danger';
                        badgeText = 'Defect';
                    }

                    equipBody.innerHTML += `
                        <tr>
                            <td><strong>${escapeHtml(e.name)}</strong></td>
                            <td><code>${escapeHtml(e.serial_number)}</code></td>
                            <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                            <td>${escapeHtml(e.room_name || 'In Depozit')}</td>
                            <td style="text-align: right;">
                                <button class="btn btn-outline btn-sm" onclick="editEquipment(${JSON.stringify(e).replace(/"/g, '&quot;')})" style="margin-right: 5px;">Editeaza</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteEquipment(${e.id})">Sterge</button>
                            </td>
                        </tr>
                    `;
                });
            }

        } catch (e) {
            console.error("Error loading resources data", e);
        }
    }

    
    async function saveRoom(e) {
        e.preventDefault();
        const id = document.getElementById('room-id').value;
        const name = document.getElementById('room-name').value;
        const capacity = document.getElementById('room-capacity').value;
        const description = document.getElementById('room-description').value;

        try {
            const res = await fetch('/api/admin/rooms', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name, capacity, description })
            });
            const data = await res.json();
            if (data.status === 'ok') {
                alert(data.mesaj);
                resetRoomForm();
                loadResourcesAndEquipment();
            } else {
                alert("Eroare: " + data.eroare);
            }
        } catch (err) {
            alert("Eroare de conexiune.");
        }
    }

    
    async function deleteRoom(id) {
        if (!confirm("Sigur vrei sa stergi aceasta sala? Sesiunile alocate in aceasta sala vor fi afectate!")) return;
        try {
            const res = await fetch('/api/admin/rooms/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.status === 'ok') {
                alert(data.mesaj);
                loadResourcesAndEquipment();
            } else {
                alert("Eroare: " + data.eroare);
            }
        } catch (err) {
            alert("Eroare la stergere.");
        }
    }

    function editRoom(room) {
        document.getElementById('room-form-title').innerText = "Editeaza sala: " + room.name;
        document.getElementById('room-id').value = room.id;
        document.getElementById('room-name').value = room.name;
        document.getElementById('room-capacity').value = room.capacity;
        document.getElementById('room-description').value = room.description || '';
        document.getElementById('room-name').scrollIntoView({ behavior: 'smooth' });
    }

    function resetRoomForm() {
        document.getElementById('room-form-title').innerText = "Adauga o sala / zona noua";
        document.getElementById('room-id').value = '';
        document.getElementById('room-form').reset();
    }

    
    async function saveEquipment(e) {
        e.preventDefault();
        const id = document.getElementById('equip-id').value;
        const name = document.getElementById('equip-name').value;
        const serial_number = document.getElementById('equip-sn').value;
        const status = document.getElementById('equip-status').value;
        const resource_id = document.getElementById('equip-room').value;

        try {
            const res = await fetch('/api/admin/equipment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name, serial_number, status, resource_id })
            });
            const data = await res.json();
            if (data.status === 'ok') {
                alert(data.mesaj);
                resetEquipForm();
                loadResourcesAndEquipment();
            } else {
                alert("Eroare: " + data.eroare);
            }
        } catch (err) {
            alert("Eroare de conexiune.");
        }
    }

    
    async function deleteEquipment(id) {
        if (!confirm("Sigur vrei sa stergi acest echipament din inventar?")) return;
        try {
            const res = await fetch('/api/admin/equipment/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.status === 'ok') {
                alert(data.mesaj);
                loadResourcesAndEquipment();
            } else {
                alert("Eroare: " + data.eroare);
            }
        } catch (err) {
            alert("Eroare la stergere.");
        }
    }

    function editEquipment(eq) {
        document.getElementById('equip-form-title').innerText = "Editeaza echipamentul: " + eq.name;
        document.getElementById('equip-id').value = eq.id;
        document.getElementById('equip-name').value = eq.name;
        document.getElementById('equip-sn').value = eq.serial_number;
        document.getElementById('equip-status').value = eq.status;
        document.getElementById('equip-room').value = eq.resource_id || '';
        document.getElementById('equip-name').scrollIntoView({ behavior: 'smooth' });
    }

    function resetEquipForm() {
        document.getElementById('equip-form-title').innerText = "Adauga echipament nou";
        document.getElementById('equip-id').value = '';
        document.getElementById('equip-form').reset();
    }

    
    async function importSpecialists(e) {
        e.preventDefault();
        const fileInput = document.getElementById('import-file');
        if (fileInput.files.length === 0) return;

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);

        try {
            const res = await fetch('/api/admin/import/trainers', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'ok') {
                alert(data.mesaj);
                fileInput.value = '';
            } else {
                alert("Eroare import: " + data.eroare);
            }
        } catch (err) {
            alert("Eroare la importarea datelor.");
        }
    }

    
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Initialize stats page charts
    window.addEventListener('DOMContentLoaded', () => {
        renderStatsCharts();
    });
</script>
<?php else: ?>
<div class="alert alert-error">Nu ai permisiunea de a accesa aceasta pagina.</div>
<?php endif; ?>
