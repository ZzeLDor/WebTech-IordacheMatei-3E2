


(function () {
    const ICONS = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    const TITLES = { success: 'Succes', error: 'Eroare', info: 'Informatie', warning: 'Atentie' };

    function getContainer() {
        let c = document.getElementById('toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'toast-container';
            document.body.appendChild(c);
        }
        return c;
    }

    window.showToast = function(message, type = 'info', duration = 4000) {
        const container = getContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${ICONS[type] || ICONS.info}</span>
            <div class="toast-body">
                <div class="toast-title">${TITLES[type] || 'Notificare'}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-dismiss" aria-label="Inchide">&times;</button>
        `;
        container.appendChild(toast);

        const dismiss = () => {
            toast.classList.add('toast--out');
            setTimeout(() => toast.remove(), 320);
        };
        toast.querySelector('.toast-dismiss').addEventListener('click', dismiss);
        if (duration > 0) setTimeout(dismiss, duration);
    };
}());

document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("login-form");
    const logoutBtn = document.getElementById("logout-btn");
    const eroareDiv = document.getElementById("mesaj-eroare");
    const succesDiv = document.getElementById("mesaj-succes");

    if (loginForm) {
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            
            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;

            try {
                const response = await fetch("/api/login", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    eroareDiv.classList.add("hidden");
                    succesDiv.classList.remove("hidden");
                    succesDiv.innerText = data.mesaj;
                    
                    setTimeout(() => {
                        window.location.href = "/dashboard";
                    }, 500);
                } else {
                    succesDiv.classList.add("hidden");
                    eroareDiv.classList.remove("hidden");
                    eroareDiv.innerText = data.eroare;
                }
            } catch (error) {
                console.error(error);
            }
        });
        
        
        const showRegisterBtn = document.getElementById("show-register");
        const showLoginBtn = document.getElementById("show-login");
        const loginContainer = document.getElementById("login-container");
        const registerContainer = document.getElementById("register-container");
        
        if (showRegisterBtn && showLoginBtn) {
            showRegisterBtn.addEventListener("click", (e) => {
                e.preventDefault();
                loginContainer.classList.add("hidden");
                registerContainer.classList.remove("hidden");
            });
            showLoginBtn.addEventListener("click", (e) => {
                e.preventDefault();
                registerContainer.classList.add("hidden");
                loginContainer.classList.remove("hidden");
            });
        }
        
        
        const registerForm = document.getElementById("register-form");
        if (registerForm) {
            registerForm.addEventListener("submit", async (e) => {
                e.preventDefault();
                
                const name = document.getElementById("reg-name").value;
                const email = document.getElementById("reg-email").value;
                const password = document.getElementById("reg-password").value;
                const regErr = document.getElementById("reg-mesaj-eroare");
                const regSucc = document.getElementById("reg-mesaj-succes");

                try {
                    const response = await fetch("/api/register", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ name, email, password })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        regErr.classList.add("hidden");
                        regSucc.classList.remove("hidden");
                        regSucc.innerText = data.mesaj + ". Acum te poti conecta.";
                        
                        setTimeout(() => {
                            showLoginBtn.click();
                            document.getElementById("email").value = email;
                            document.getElementById("password").value = "";
                        }, 2000);
                    } else {
                        regSucc.classList.add("hidden");
                        regErr.classList.remove("hidden");
                        regErr.innerText = data.eroare;
                    }
                } catch (error) {
                    console.error(error);
                }
            });
        }
    }

    if (logoutBtn) {
        logoutBtn.addEventListener("click", async () => {
            await fetch("/api/logout", { method: "POST" });
            location.reload();
        });
    }

    let allUsers = [];

    async function loadUsers(searchQuery = "") {
        const usersBody = document.getElementById("users-table-body");
        if (!usersBody) return;

        try {
            if (allUsers.length === 0 || searchQuery === "") {
                const response = await fetch("/api/users");
                if (response.ok) {
                    const data = await response.json();
                    allUsers = data.utilizatori;
                }
            }

            usersBody.innerHTML = "";
            const filtered = allUsers.filter(user => {
                const q = searchQuery.toLowerCase();
                return user.name.toLowerCase().includes(q) || user.email.toLowerCase().includes(q);
            });

            filtered.forEach(user => {
                const isSpecialist = ['trainer', 'therapist'].includes(user.role);
                
                let subBadge = '<span style="color:var(--text-muted); font-size:0.85rem;">Fara abonament</span>';
                let subId = null, subStatus = null;
                if (user.active_subscription) {
                    const parts = user.active_subscription.split('|');
                    subId     = parts[0];
                    const type     = parts[1];
                    subStatus      = parts[2];
                    const endDate  = parts[3];
                    let typeLabel  = type.charAt(0).toUpperCase() + type.slice(1);
                    if (type === 'kineto')   typeLabel = 'Kinetoterapie';
                    else if (type === 'strength') typeLabel = 'Forta';
                    else if (type === 'mixed')    typeLabel = 'Mixt';

                    const statusColor  = subStatus === 'active' ? 'var(--success)' : '#f59e0b';
                    const statusLabel  = subStatus === 'active' ? 'Activ' : 'Suspendat';
                    subBadge = `<span style="font-weight:600; color:${statusColor}; font-size:0.85rem;">${typeLabel}</span><br><small style="color:var(--text-muted);">Exp: ${endDate} &nbsp;·&nbsp; <span style="color:${statusColor};">${statusLabel}</span></small>`;
                }

                usersBody.innerHTML += `
                    <tr>
                        <td>${user.id}</td>
                        <td><strong>${user.name}</strong></td>
                        <td>${user.email}</td>
                        <td>
                            <select class="role-select" data-id="${user.id}" style="padding: 4px;">
                                <option value="member" ${user.role === 'member' ? 'selected' : ''}>Membru</option>
                                <option value="trainer" ${user.role === 'trainer' ? 'selected' : ''}>Antrenor</option>
                                <option value="therapist" ${user.role === 'therapist' ? 'selected' : ''}>Terapeut</option>
                                <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                            </select>
                        </td>
                        <td>
                            <select class="spec-select" data-id="${user.id}" ${isSpecialist ? '' : 'disabled'} style="padding: 4px;">
                                <option value="" ${!user.specialization ? 'selected' : ''}>-- Fara --</option>
                                <option value="fitness" ${user.specialization === 'fitness' ? 'selected' : ''}>Fitness</option>
                                <option value="forta" ${user.specialization === 'forta' ? 'selected' : ''}>Forta</option>
                                <option value="kinetoterapie" ${user.specialization === 'kinetoterapie' ? 'selected' : ''}>Kinetoterapie</option>
                            </select>
                        </td>
                        <td>${subBadge}</td>
                        <td>
                            <div style="display:flex; gap:5px;">
                                <button class="save-role btn btn-primary btn-sm" data-id="${user.id}">Salveaza Rol</button>
                                <button class="btn btn-outline btn-sm edit-sub-btn" data-id="${user.id}" data-name="${user.name}">Abonament</button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            
            document.querySelectorAll(".role-select").forEach(select => {
                select.addEventListener("change", (e) => {
                    const userId = e.target.getAttribute("data-id");
                    const specSelect = document.querySelector(`.spec-select[data-id='${userId}']`);
                    if (['trainer', 'therapist'].includes(e.target.value)) {
                        specSelect.disabled = false;
                    } else {
                        specSelect.disabled = true;
                        specSelect.value = "";
                    }
                });
            });

            document.querySelectorAll(".save-role").forEach(btn => {
                btn.addEventListener("click", async (e) => {
                    const userId = e.target.getAttribute("data-id");
                    const newRole = document.querySelector(`.role-select[data-id='${userId}']`).value;
                    const newSpec = document.querySelector(`.spec-select[data-id='${userId}']`).value;
                    
                    try {
                        
                        await fetch("/api/users/role", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ user_id: userId, rol_nou: newRole })
                        });

                        
                        if (['trainer', 'therapist'].includes(newRole)) {
                            await fetch("/api/users/specialization", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ user_id: userId, specialization: newSpec })
                            });
                        }
                        
                        showToast('Utilizator actualizat cu succes!', 'success');
                        allUsers = []; 
                        loadUsers(document.getElementById("user-search").value);
                    } catch (err) {
                        showToast('Eroare la actualizare.', 'error');
                    }
                });
            });

            
            document.querySelectorAll(".edit-sub-btn").forEach(btn => {
                btn.addEventListener("click", async (e) => {
                    const userId   = e.currentTarget.getAttribute("data-id");
                    const userName = e.currentTarget.getAttribute("data-name");

                    document.getElementById("sub-user-id").value          = userId;
                    document.getElementById("sub-modal-username").textContent = userName;
                    document.getElementById("current-sub-content").innerHTML = '<span style="color:var(--text-muted);font-size:0.9rem;">Se incarca...</span>';
                    document.getElementById("sub-history-content").innerHTML = '<span style="color:var(--text-muted);font-size:0.85rem;">Se incarca...</span>';
                    document.getElementById("subscription-modal").style.display = "flex";

                    
                    try {
                        const res = await fetch(`/api/subscriptions/history?user_id=${userId}`);
                        const data = await res.json();
                        const history = data.history || [];

                        
                        const current = history.find(s => s.status === 'active' || s.status === 'suspended');
                        const currentEl = document.getElementById('current-sub-content');

                        if (!current) {
                            currentEl.innerHTML = '<span style="color:var(--text-muted); font-size:0.9rem;">Niciun abonament activ sau suspendat.</span>';
                        } else {
                            const typeMap = { fitness: '🏃 Fitness', strength: '💪 Forta', kineto: '🩺 Kinetoterapie', mixed: '⚡ Mixt' };
                            const typeLabel   = typeMap[current.type] || current.type;
                            const isActive    = current.status === 'active';
                            const statusColor = isActive ? 'var(--success)' : '#f59e0b';
                            const statusLabel = isActive ? 'Activ' : 'Suspendat';
                            const actionBtn   = isActive
                                ? `<button class="btn btn-sm btn-cancel-session" id="sub-action-btn" data-sub-id="${current.id}" data-action="suspended">⏸ Suspenda</button>`
                                : `<button class="btn btn-sm btn-edit-session" id="sub-action-btn" data-sub-id="${current.id}" data-action="active">▶ Reactiveaza</button>`;

                            currentEl.innerHTML = `
                                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                                    <div>
                                        <div style="font-size:1.05rem; font-weight:700; color:var(--text-main);">${typeLabel}</div>
                                        <div style="font-size:0.83rem; color:var(--text-muted); margin-top:3px;">
                                            📅 ${current.start_date} → ${current.end_date}
                                        </div>
                                        <div style="margin-top:5px;">
                                            <span style="font-size:0.8rem; font-weight:600; color:${statusColor}; background:${isActive ? '#ecfdf5' : '#fffbeb'}; padding:2px 8px; border-radius:20px; border:1px solid ${statusColor};">${statusLabel}</span>
                                        </div>
                                    </div>
                                    <div>${actionBtn}</div>
                                </div>
                            `;

                            
                            document.getElementById('sub-action-btn').addEventListener('click', async (ev) => {
                                const subId     = ev.currentTarget.dataset.subId;
                                const newStatus = ev.currentTarget.dataset.action;
                                ev.currentTarget.disabled = true;
                                ev.currentTarget.textContent = 'Se proceseaza...';

                                const r = await fetch('/api/subscriptions/status', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ sub_id: parseInt(subId), status: newStatus })
                                });
                                const d = await r.json();
                                if (r.ok) {
                                    showToast(d.mesaj, 'success');
                                    allUsers = [];
                                    loadUsers(document.getElementById('user-search').value);
                                    
                                    document.getElementById('subscription-modal').style.display = 'none';
                                } else {
                                    showToast(d.eroare || 'Eroare la actualizare.', 'error');
                                    ev.currentTarget.disabled = false;
                                }
                            });
                        }

                        
                        const histEl = document.getElementById('sub-history-content');
                        if (!history.length) {
                            histEl.innerHTML = '<span style="color:var(--text-muted); font-size:0.85rem;">Niciun abonament in istoric.</span>';
                        } else {
                            const statusBadge = (s) => {
                                if (s === 'active')    return '<span style="color:var(--success); font-weight:600;">Activ</span>';
                                if (s === 'suspended') return '<span style="color:#f59e0b; font-weight:600;">Suspendat</span>';
                                return '<span style="color:var(--text-muted);">Expirat</span>';
                            };
                            const typeMap = { fitness: 'Fitness', strength: 'Forta', kineto: 'Kinetoterapie', mixed: 'Mixt' };
                            const rows = history.map(s => `
                                <tr>
                                    <td style="font-size:0.83rem;">${typeMap[s.type] || s.type}</td>
                                    <td style="font-size:0.83rem;">${s.start_date}</td>
                                    <td style="font-size:0.83rem;">${s.end_date}</td>
                                    <td style="font-size:0.83rem;">${statusBadge(s.status)}</td>
                                </tr>
                            `).join('');
                            histEl.innerHTML = `
                                <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--border);">
                                            <th style="text-align:left; padding:4px 6px; color:var(--text-muted); font-weight:600;">Tip</th>
                                            <th style="text-align:left; padding:4px 6px; color:var(--text-muted); font-weight:600;">Inceput</th>
                                            <th style="text-align:left; padding:4px 6px; color:var(--text-muted); font-weight:600;">Expira</th>
                                            <th style="text-align:left; padding:4px 6px; color:var(--text-muted); font-weight:600;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>${rows}</tbody>
                                </table>
                            `;
                        }

                    } catch (err) {
                        document.getElementById('current-sub-content').innerHTML = '<span style="color:var(--danger);">Eroare la incarcare.</span>';
                        console.error(err);
                    }
                });
            });

        } catch (error) {
            console.error("Eroare la incarcarea utilizatorilor", error);
        }
    }

    
    const searchInput = document.getElementById("user-search");
    if (searchInput) {
        searchInput.addEventListener("input", (e) => {
            loadUsers(e.target.value);
        });
    }

    
    const closeSubModal = document.getElementById("close-sub-modal");
    if (closeSubModal) {
        closeSubModal.addEventListener("click", () => {
            document.getElementById("subscription-modal").style.display = "none";
        });
    }

    
    const subForm = document.getElementById("add-sub-form");
    if (subForm) {
        subForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const userId = document.getElementById("sub-user-id").value;
            const type = document.getElementById("sub-type").value;
            const months = document.getElementById("sub-months").value;

            try {
                const res = await fetch("/api/subscriptions", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ user_id: userId, type: type, luni: months })
                });
                
                if (res.ok) {
                    showToast('Abonament creat/actualizat cu succes!', 'success');
                    document.getElementById("subscription-modal").style.display = "none";
                    allUsers = []; 
                    loadUsers(document.getElementById("user-search") ? document.getElementById("user-search").value : "");
                } else {
                    showToast('Eroare la crearea abonamentului.', 'error');
                }
            } catch(e) {
                showToast('Eroare de retea.', 'error');
            }
        });
    }

    if (document.getElementById("users-table-body")) loadUsers();

    
    
    

    const CELL_HEIGHT = 80; 
    const HOUR_START = 6;
    const HOUR_END = 22; 

    
    const CATEGORY_STYLES = {
        fitness:       { label: '🏃 Fitness',       color: '#2563eb', bg: '#eff6ff', border: '#2563eb' },
        forta:         { label: '💪 Forta',          color: '#dc2626', bg: '#fef2f2', border: '#dc2626' },
        kinetoterapie: { label: '🩺 Kinetoterapie', color: '#059669', bg: '#ecfdf5', border: '#059669' },
    };

    let currentWeekStart = null;
    window.allRooms = [];
    window.currentWeekSessions = [];

    function getMonday(d) {
        d = new Date(d);
        var day = d.getDay(),
            diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }

    function toMinutes(dateObj) {
        return dateObj.getHours() * 60 + dateObj.getMinutes();
    }

    function fmtTime(dateObj) {
        return dateObj.toLocaleTimeString("ro-RO", { hour: "2-digit", minute: "2-digit" });
    }

    function fmtDate(dateObj) {
        return dateObj.toLocaleDateString("ro-RO", { weekday: "long", day: "numeric", month: "long" });
    }

    async function loadRooms() {
        try {
            const res = await fetch("/api/resources");
            if (res.ok) {
                const data = await res.json();
                window.allRooms = data.resurse || [];
            }
        } catch (e) {}
    }

    function updateWeekLabel() {
        const label = document.getElementById("current-week-label");
        if (!label) return;
        let end = new Date(currentWeekStart);
        end.setDate(end.getDate() + 6);
        const opts = { month: "short", day: "numeric" };
        label.innerText = `${currentWeekStart.toLocaleDateString("ro-RO", opts)} – ${end.toLocaleDateString("ro-RO", opts)}`;
    }

    
    function renderTimetable(weekSessions) {
        const ttBody = document.getElementById("timetable-body");
        if (!ttBody) return;
        ttBody.innerHTML = "";

        const startOfWeek = new Date(currentWeekStart);
        startOfWeek.setHours(0, 0, 0, 0);

        const hours = [];
        for (let h = HOUR_START; h < HOUR_END; h++) {
            hours.push(h);
        }

        
        
        
        const dayMap = {}; 
        for (let i = 0; i < 7; i++) {
            dayMap[i] = [];
            let cellDate = new Date(startOfWeek);
            cellDate.setDate(cellDate.getDate() + i);
            let cellDateStr = cellDate.toLocaleDateString("ro-RO");

            weekSessions.forEach(s => {
                let sDate = new Date(s.start_time);
                if (sDate.toLocaleDateString("ro-RO") === cellDateStr) {
                    dayMap[i].push(s);
                }
            });
        }

        hours.forEach(h => {
            const tr = document.createElement("tr");
            const label = h.toString().padStart(2, "0") + ":00";
            tr.innerHTML = `<td class="time-col">${label}</td>`;

            for (let i = 0; i < 7; i++) {
                let cellDate = new Date(startOfWeek);
                cellDate.setDate(cellDate.getDate() + i);
                let isoDate = new Date(cellDate.getTime() - cellDate.getTimezoneOffset() * 60000)
                    .toISOString().split("T")[0];

                const td = document.createElement("td");
                td.dataset.date = isoDate;
                td.dataset.time = label;

                
                const slotSessions = dayMap[i].filter(s => {
                    let sDate = new Date(s.start_time);
                    return sDate.getHours() === h;
                });

                if (slotSessions.length > 0) {
                    td.classList.add("has-session");
                    
                    const count = slotSessions.length;
                    slotSessions.forEach((s, idx) => {
                        let sStart = new Date(s.start_time);
                        let sEnd = new Date(s.end_time);

                        
                        let durationH = (sEnd - sStart) / 3600000;

                        
                        let minuteOffset = sStart.getMinutes();
                        let topPct = (minuteOffset / 60) * 100;
                        let heightPx = durationH * CELL_HEIGHT;

                        
                        let widthPct = Math.floor(88 / count);
                        let leftPct = 2 + idx * (widthPct + 2);

                        const isFull = parseInt(s.current_bookings) >= parseInt(s.max_capacity);
                        const pill = isFull
                            ? `<span class="session-pill session-pill--full">Plin</span>`
                            : `<span class="session-pill">${s.current_bookings}/${s.max_capacity}</span>`;

                        const cat = s.category || 'fitness';
                        const catStyle = CATEGORY_STYLES[cat] || CATEGORY_STYLES.fitness;

                        const isPrivate = s.title.endsWith('(Privat)');
                        const bgStyle = isPrivate ? `repeating-linear-gradient(45deg, ${catStyle.bg}, ${catStyle.bg} 10px, rgba(0,0,0,0.03) 10px, rgba(0,0,0,0.03) 20px)` : catStyle.bg;
                        const titleBadge = isPrivate ? ' <span style="font-size:0.65rem; background:var(--primary); color:#fff; padding:1px 4px; border-radius:4px; vertical-align:middle;">PRIVAT</span>' : '';

                        const card = document.createElement("div");
                        card.className = `tt-session tt-session--${cat}`;
                        card.dataset.sessionId = s.id;
                        card.style.cssText = `
                            top: ${topPct}%;
                            left: ${leftPct}%;
                            width: ${widthPct}%;
                            height: ${heightPx - 4}px;
                            border-left-color: ${catStyle.border};
                            background: ${bgStyle};
                            ${isPrivate ? `border: 1px dashed ${catStyle.border}; border-left: 4px solid ${catStyle.border};` : ''}
                        `;
                        
                        card.innerHTML = `
                            <span class="tt-session-title">${s.title}${titleBadge}</span>
                            <span class="tt-session-trainer">${s.trainer_name}</span>
                            <span class="tt-session-meta">${fmtTime(sStart)}–${fmtTime(sEnd)}</span>
                            ${s.description ? `<span class="tt-session-desc">${s.description}</span>` : ''}
                            ${pill}
                        `;

                        card.addEventListener("click", (e) => {
                            e.stopPropagation();
                            openDetailModal(s.id);
                        });

                        td.appendChild(card);
                    });
                }

                
                if (window.USER_ROLE && ["admin", "trainer", "therapist"].includes(window.USER_ROLE)) {
                    td.addEventListener("click", () => {
                        openCreateModal(isoDate, label);
                    });
                }

                tr.appendChild(td);
            }
            ttBody.appendChild(tr);
        });
    }

    async function loadSessions() {
        const ttBody = document.getElementById("timetable-body");
        if (!ttBody) return;

        if (!currentWeekStart) {
            currentWeekStart = getMonday(new Date());
        }

        
        if (document.getElementById("prev-week") && !document.getElementById("prev-week").dataset.bound) {
            document.getElementById("prev-week").dataset.bound = "true";
            document.getElementById("prev-week").addEventListener("click", () => {
                currentWeekStart.setDate(currentWeekStart.getDate() - 7);
                loadSessions();
            });
            document.getElementById("next-week").addEventListener("click", () => {
                currentWeekStart.setDate(currentWeekStart.getDate() + 7);
                loadSessions();
            });
        }

        updateWeekLabel();

        try {
            const response = await fetch("/api/sessions");
            if (!response.ok) return;
            const data = await response.json();

            let endOfWeek = new Date(currentWeekStart);
            endOfWeek.setDate(endOfWeek.getDate() + 6);
            endOfWeek.setHours(23, 59, 59, 999);
            let startOfWeek = new Date(currentWeekStart);
            startOfWeek.setHours(0, 0, 0, 0);

            const weekSessions = data.sesiuni.filter(s => {
                let sDate = new Date(s.start_time);
                return sDate >= startOfWeek && sDate <= endOfWeek;
            });

            window.currentWeekSessions = weekSessions;
            renderTimetable(weekSessions);
        } catch (e) {
            console.error("loadSessions error:", e);
        }
    }

    
    const detailModal = document.getElementById("session-detail-modal");
    if (detailModal) {
        document.getElementById("close-detail-modal").addEventListener("click", () => {
            detailModal.style.display = "none";
        });
        detailModal.addEventListener("click", (e) => {
            if (e.target === detailModal) detailModal.style.display = "none";
        });
    }

    async function openDetailModal(sessionId) {
        if (!detailModal) return;
        detailModal.style.display = "flex";

        
        document.getElementById("detail-title").textContent = "Se incarca...";
        document.getElementById("detail-subtitle").textContent = "";
        document.getElementById("detail-description").textContent = "";
        document.getElementById("detail-time").textContent = "-";
        document.getElementById("detail-room").textContent = "-";
        document.getElementById("detail-trainer").textContent = "-";
        document.getElementById("detail-capacity").textContent = "-";
        document.getElementById("detail-participants-list").innerHTML = "";
        document.getElementById("detail-action").innerHTML = "";

        try {
            const res = await fetch(`/api/sessions/detail?id=${sessionId}`);
            if (!res.ok) return;
            const data = await res.json();
            const s = data.sesiune;
            const parti = data.participanti || [];
            const esteInscris = data.este_inscris;

            const sStart = new Date(s.start_time);
            const sEnd = new Date(s.end_time);
            const isFull = parseInt(s.current_bookings) >= parseInt(s.max_capacity);
            const canBook = data.poate_rezerva;

            const cat = s.category || 'fitness';
            const catStyle = CATEGORY_STYLES[cat] || CATEGORY_STYLES.fitness;
            const badge = document.getElementById('detail-category-badge');
            if (badge) {
                badge.textContent = catStyle.label;
                badge.style.setProperty('--cat-color', catStyle.color);
                badge.style.setProperty('--cat-bg', catStyle.bg);
            }

            document.getElementById("detail-title").textContent = s.title;
            document.getElementById("detail-subtitle").textContent = fmtDate(sStart);
            document.getElementById("detail-description").textContent = s.description || "Fara descriere.";
            document.getElementById("detail-time").textContent = `${fmtTime(sStart)} – ${fmtTime(sEnd)}`;
            document.getElementById("detail-room").textContent = s.room_name || "–";
            document.getElementById("detail-trainer").textContent = s.trainer_name;
            document.getElementById("detail-capacity").textContent = `${s.current_bookings} / ${s.max_capacity} locuri`;

            
            const listEl = document.getElementById("detail-participants-list");
            if (parti.length === 0) {
                listEl.innerHTML = `<span style="color:var(--text-muted);font-size:0.85rem;">Nicio persoana inscrisa inca.</span>`;
            } else {
                parti.forEach(p => {
                    listEl.innerHTML += `
                        <div class="participant-row">
                            <span class="participant-avatar">${p.name.charAt(0).toUpperCase()}</span>
                            <span>${p.name}</span>
                        </div>
                    `;
                });
            }

            
            const actionEl = document.getElementById("detail-action");
            let editBtns = '';
            const isStaff = window.USER_ROLE && ['admin', 'trainer', 'therapist'].includes(window.USER_ROLE);
            
            if (isStaff) {
                editBtns = `
                    <button class="btn btn-edit-session btn-sm" id="detail-edit-btn" data-session-id="${s.id}">✏️ Editeaza</button>
                `;
            }

            if (esteInscris) {
                actionEl.innerHTML = `${editBtns}<span style="color:var(--text-muted); font-size:0.85rem; margin-left: 8px;">✓ Esti deja inscris</span>`;
            } else if (isFull) {
                actionEl.innerHTML = `${editBtns}<button class="btn btn-outline" disabled style="margin-left: 8px;">Sesiune completa</button>`;
            } else if (!canBook) {
                const catLabel = catStyle.label;
                actionEl.innerHTML = `${editBtns}<span style="color:#b45309; font-size:0.85rem; background:#fef3c7; border:1px solid #f59e0b; border-radius:var(--radius); padding:6px 12px; display:inline-block; margin-left: 8px;">⚠️ Abonamentul tau nu include sesiuni de ${catLabel}. Contacteaza receptia.</span>`;
            } else {
                actionEl.innerHTML = `${editBtns}<button class="btn btn-primary" id="detail-book-btn" style="margin-left: 8px;">Inscrie-te</button>`;
                document.getElementById("detail-book-btn").addEventListener("click", async () => {
                    const bookBtn = document.getElementById('detail-book-btn');
                    bookBtn.disabled = true;
                    bookBtn.textContent = 'Se proceseaza...';
                    const res2 = await fetch("/api/bookings", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ session_id: sessionId })
                    });
                    const bData = await res2.json();
                    if (res2.ok) {
                        detailModal.style.display = "none";
                        showToast(bData.mesaj || 'Inscrierea a fost confirmata!', 'success');
                        loadSessions();
                    } else {
                        showToast(bData.eroare || 'Eroare la rezervare.', 'error');
                        bookBtn.disabled = false;
                        bookBtn.textContent = 'Inscrie-te';
                    }
                });
            }

            
            const editBtn = document.getElementById('detail-edit-btn');
            if (editBtn) {
                editBtn.addEventListener('click', () => {
                    detailModal.style.display = 'none';
                    openEditSessionModal(s);
                });
            }
        } catch (e) {
            console.error("openDetailModal error:", e);
        }
    }

    
    const createModal = document.getElementById("session-modal");
    if (createModal) {
        loadRooms();

        const closeBtn = document.getElementById("close-session-modal");
        const startSel = document.getElementById("ses-start-time");
        const endSel   = document.getElementById("ses-end-time");
        const roomSel  = document.getElementById("ses-room");

        
        const times = [];
        for (let h = HOUR_START; h <= HOUR_END; h++) {
            times.push(h.toString().padStart(2, "0") + ":00");
            times.push(h.toString().padStart(2, "0") + ":30");
        }
        const timeOptions = times.map(t => `<option value="${t}">${t}</option>`).join("");
        startSel.innerHTML = timeOptions;
        endSel.innerHTML   = timeOptions;

        function updateRooms() {
            const dateStr = document.getElementById("ses-date").value;
            const startT  = startSel.value;
            const endT    = endSel.value;
            if (!dateStr || !startT || !endT || !window.allRooms.length) return;

            const selStart = new Date(`${dateStr}T${startT}:00`);
            const selEnd   = new Date(`${dateStr}T${endT}:00`);

            const busyIds = (window.currentWeekSessions || [])
                .filter(s => {
                    const sS = new Date(s.start_time);
                    const sE = new Date(s.end_time);
                    return selStart < sE && selEnd > sS;
                })
                .map(s => parseInt(s.resource_id));

            roomSel.innerHTML = "";
            let any = false;
            window.allRooms.forEach(r => {
                if (parseInt(r.id) === 1 || !busyIds.includes(parseInt(r.id))) {
                    roomSel.innerHTML += `<option value="${r.id}">${r.name} (cap. ${r.capacity})</option>`;
                    any = true;
                }
            });
            if (!any) {
                roomSel.innerHTML = `<option value="" disabled selected>Nicio sala libera</option>`;
            }
        }

        startSel.addEventListener("change", updateRooms);
        endSel.addEventListener("change", updateRooms);

        closeBtn.addEventListener("click", () => { createModal.style.display = "none"; });
        createModal.addEventListener("click", (e) => {
            if (e.target === createModal) createModal.style.display = "none";
        });

        window.openCreateModal = function(dateStr, timeStr) {
            document.getElementById("ses-title").value = "";
            document.getElementById("ses-desc").value  = "";
            document.getElementById("ses-cap").value   = "";
            document.getElementById("ses-date").value  = dateStr;

            const d = new Date(dateStr + "T12:00:00");
            document.getElementById("display-date").value = fmtDate(d);

            startSel.value = timeStr;
            let [h, m] = timeStr.split(":");
            let endH = (parseInt(h) + 1).toString().padStart(2, "0");
            if (parseInt(endH) > HOUR_END) endH = HOUR_END.toString().padStart(2, "0");
            endSel.value = `${endH}:${m}`;

            updateRooms();
            createModal.style.display = "flex";
        };
    }

    
    const editSesModal = document.getElementById('edit-session-modal');
    if (editSesModal) {
        document.getElementById('close-edit-session-modal').addEventListener('click', () => {
            editSesModal.style.display = 'none';
        });
        editSesModal.addEventListener('click', (e) => {
            if (e.target === editSesModal) editSesModal.style.display = 'none';
        });

        
        document.getElementById('edit-ses-cancel-btn').addEventListener('click', () => {
            document.getElementById('cancel-reason-wrap').classList.remove('hidden');
            document.getElementById('edit-session-form').style.opacity = '0.4';
            document.getElementById('edit-session-form').style.pointerEvents = 'none';
        });

        document.getElementById('cancel-reason-back').addEventListener('click', () => {
            document.getElementById('cancel-reason-wrap').classList.add('hidden');
            document.getElementById('edit-session-form').style.opacity = '';
            document.getElementById('edit-session-form').style.pointerEvents = '';
        });

        document.getElementById('confirm-cancel-btn').addEventListener('click', async () => {
            const sessionId = document.getElementById('edit-ses-id').value;
            const reason    = document.getElementById('cancel-reason-text').value;
            const btn = document.getElementById('confirm-cancel-btn');
            btn.disabled = true;
            btn.textContent = 'Se proceseaza...';

            const res = await fetch('/api/sessions/cancel', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: parseInt(sessionId), reason })
            });
            const data = await res.json();

            if (res.ok) {
                editSesModal.style.display = 'none';
                showToast(data.mesaj, 'success', 6000);
                loadSessions();
            } else {
                showToast(data.eroare || 'Eroare la anulare.', 'error');
                btn.disabled = false;
                btn.textContent = 'Confirma Anularea';
            }
        });

        document.getElementById('edit-session-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const sessionId = document.getElementById('edit-ses-id').value;
            const payload = {
                session_id:   parseInt(sessionId),
                title:        document.getElementById('edit-ses-title').value,
                description:  document.getElementById('edit-ses-desc').value,
                category:     document.getElementById('edit-ses-category').value,
                max_capacity: parseInt(document.getElementById('edit-ses-cap').value),
                resource_id:  parseInt(document.getElementById('edit-ses-room').value),
            };

            const res = await fetch('/api/sessions/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (res.ok) {
                editSesModal.style.display = 'none';
                showToast(data.mesaj, 'success');
                loadSessions();
            } else {
                showToast(data.eroare || 'Eroare la salvare.', 'error');
            }
        });
    }

    window.openEditSessionModal = async function(session) {
        const modal = document.getElementById('edit-session-modal');
        if (!modal) return;

        
        document.getElementById('cancel-reason-wrap').classList.add('hidden');
        document.getElementById('edit-session-form').style.opacity = '';
        document.getElementById('edit-session-form').style.pointerEvents = '';
        document.getElementById('cancel-reason-text').value = '';

        
        document.getElementById('edit-ses-id').value           = session.id;
        document.getElementById('edit-ses-title').value        = session.title || '';
        document.getElementById('edit-ses-desc').value         = session.description || '';
        document.getElementById('edit-ses-category').value     = session.category || 'fitness';
        document.getElementById('edit-ses-cap').value          = session.max_capacity || 10;

        
        const roomSel = document.getElementById('edit-ses-room');
        roomSel.innerHTML = '';
        if (window.allRooms && window.allRooms.length) {
            window.allRooms.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name + ' (cap. ' + r.capacity + ')';
                if (parseInt(r.id) === parseInt(session.resource_id)) opt.selected = true;
                roomSel.appendChild(opt);
            });
        } else {
            try {
                const res = await fetch('/api/resources');
                if (res.ok) {
                    const d = await res.json();
                    window.allRooms = d.resurse || [];
                    window.allRooms.forEach(r => {
                        const opt = document.createElement('option');
                        opt.value = r.id;
                        opt.textContent = r.name + ' (cap. ' + r.capacity + ')';
                        if (parseInt(r.id) === parseInt(session.resource_id)) opt.selected = true;
                        roomSel.appendChild(opt);
                    });
                }
            } catch(e) {}
        }

        modal.style.display = 'flex';
    };

    const sesForm = document.getElementById("add-session-form");
    if (sesForm) {
        sesForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const dateStr = document.getElementById("ses-date").value;
            const startT  = document.getElementById("ses-start-time").value;
            const endT    = document.getElementById("ses-end-time").value;
            const roomId  = document.getElementById("ses-room").value;

            if (!roomId) {
                showToast('Te rugam sa selectezi o sala valida.', 'warning');
                return;
            }

            const payload = {
                title:       document.getElementById("ses-title").value,
                description: document.getElementById("ses-desc").value,
                category:    document.getElementById("ses-category").value,
                start_time:  `${dateStr} ${startT}:00`,
                end_time:    `${dateStr} ${endT}:00`,
                capacity:    document.getElementById("ses-cap").value,
                resource_id: roomId
            };

            const res = await fetch("/api/sessions", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });

            const cData = await res.json();
            if (res.ok) {
                if (createModal) createModal.style.display = "none";
                showToast('Sesiunea a fost creata cu succes!', 'success');
                loadSessions();
            } else {
                showToast(cData.eroare || 'Eroare la crearea sesiunii.', 'error');
            }
        });
    }

    
    
    
    async function loadStatsChart() {
        const ctx = document.getElementById('myChart');
        if (!ctx) return;

        try {
            const response = await fetch("/api/stats/json");
            if (response.ok) {
                const data = await response.json();
                
                const labels = data.date.map(item => item.type.toUpperCase());
                const values = data.date.map(item => item.cnt);

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Numar de abonamente',
                            data: values,
                            backgroundColor: '#28a745'
                        }]
                    },
                    options: {
                        scales: { y: { beginAtZero: true } }
                    }
                });

                document.getElementById('download-chart-png').addEventListener('click', function() {
                    const link = document.createElement('a');
                    link.download = 'grafic-abonamente.png';
                    link.href = document.getElementById('myChart').toDataURL('image/png');
                    link.click();
                });
            }
        } catch (e) {
            console.error("Eroare la incarcarea graficului");
        }
    }

    if (document.getElementById("users-table-body")) loadUsers();
    if (document.getElementById("subs-table-body")) loadSubscriptions();
    if (document.getElementById("timetable-body")) loadSessions();
    if (document.getElementById("myChart")) loadStatsChart();

    
    
    

    const CATEGORY_LABELS = {
        fitness:       { label: "Fitness",       color: "#2563eb", bg: "#eff6ff" },
        forta:         { label: "Forta",          color: "#dc2626", bg: "#fef2f2" },
        kinetoterapie: { label: "Kinetoterapie",  color: "#059669", bg: "#ecfdf5" },
    };

    const SUB_TYPE_LABELS = {
        fitness:       "🏃 Fitness",
        forta:         "💪 Forta",
        kinetoterapie: "🩺 Kinetoterapie",
        premium:       "⭐ Premium",
        medical:       "🏥 Medical",
        general:       "📋 General",
    };

    const ROLE_LABELS = {
        admin:     "Administrator",
        trainer:   "Antrenor",
        therapist: "Kinetoterapeut",
        member:    "Membru",
    };

    async function loadProfile() {
        if (!document.getElementById("prof-name")) return;

        try {
            const res = await fetch("/api/profile");
            if (!res.ok) return;
            const data = await res.json();

            const u = data.user;
            const sub = data.abonament;
            const activitati = data.activitati || [];

            
            const initials = u.name.split(" ").map(w => w[0]).join("").toUpperCase().slice(0, 2);
            document.getElementById("prof-avatar").textContent = initials;
            document.getElementById("prof-name").textContent = u.name;
            document.getElementById("prof-email").textContent = u.email;

            const roleEl = document.getElementById("prof-role");
            roleEl.textContent = ROLE_LABELS[u.role] || u.role;
            roleEl.className = `role-badge role-badge--${u.role}`;

            const joinDate = new Date(u.created_at).toLocaleDateString("ro-RO", { year: "numeric", month: "long", day: "numeric" });
            document.getElementById("prof-since").textContent = `Cont creat pe ${joinDate}`;

            
            const subEl = document.getElementById("prof-sub-content");
            if (subEl) {
                if (!sub) {
                    subEl.innerHTML = `<p style="color:var(--text-muted); font-size:0.88rem;">Niciun abonament activ.<br><a href="/subscriptions" style="color:var(--primary);">Vezi planurile disponibile →</a></p>`;
                } else {
                    const isActive = sub.status === "active" && new Date(sub.end_date) >= new Date();
                    const endDate = new Date(sub.end_date).toLocaleDateString("ro-RO", { year: "numeric", month: "long", day: "numeric" });
                    const startDate = new Date(sub.start_date).toLocaleDateString("ro-RO", { year: "numeric", month: "long", day: "numeric" });
                    const daysLeft = Math.ceil((new Date(sub.end_date) - new Date()) / 86400000);
                    const typeLabel = SUB_TYPE_LABELS[sub.type] || sub.type;

                    subEl.innerHTML = `
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap;">
                            <div>
                                <p style="font-size:1.1rem; font-weight:700; color:var(--text-main);">${typeLabel}</p>
                                <p style="font-size:0.82rem; color:var(--text-muted); margin-top:4px;">${startDate} → ${endDate}</p>
                            </div>
                            <span style="
                                font-size:0.78rem; font-weight:600; padding:4px 10px; border-radius:100px;
                                background:${isActive ? "#dcfce7" : "#fee2e2"};
                                color:${isActive ? "#15803d" : "#b91c1c"};
                            ">${isActive ? "Activ" : "Expirat"}</span>
                        </div>
                        ${isActive && daysLeft <= 30 ? `<p style="font-size:0.8rem; color:#b45309; margin-top:10px; background:#fef3c7; border-radius:var(--radius); padding:6px 10px;">⚠️ Expira in ${daysLeft} ${daysLeft === 1 ? "zi" : "zile"}.</p>` : ""}
                    `;
                }
            }

            
            const scheduleForm = document.getElementById("profile-schedule-form");
            if (scheduleForm) {
                const checkboxes = scheduleForm.querySelectorAll(".day-working-checkbox");
                checkboxes.forEach(cb => {
                    cb.addEventListener("change", () => {
                        const day = cb.dataset.day;
                        const wrap = document.getElementById(`time-wrap-${day}`);
                        if (cb.checked) {
                            wrap.style.opacity = "1";
                            wrap.style.pointerEvents = "auto";
                        } else {
                            wrap.style.opacity = "0.5";
                            wrap.style.pointerEvents = "none";
                        }
                    });
                });

                
                try {
                    const schedRes = await fetch("/api/profile/schedule");
                    if (schedRes.ok) {
                        const schedData = await schedRes.json();
                        const schedList = schedData.schedule || [];

                        
                        checkboxes.forEach(cb => {
                            cb.checked = false;
                            const day = cb.dataset.day;
                            const wrap = document.getElementById(`time-wrap-${day}`);
                            wrap.style.opacity = "0.5";
                            wrap.style.pointerEvents = "none";
                        });

                        
                        schedList.forEach(s => {
                            const cb = scheduleForm.querySelector(`.day-working-checkbox[data-day="${s.day_of_week}"]`);
                            if (cb) {
                                cb.checked = true;
                                const day = s.day_of_week;
                                const wrap = document.getElementById(`time-wrap-${day}`);
                                wrap.style.opacity = "1";
                                wrap.style.pointerEvents = "auto";

                                document.getElementById(`start-${day}`).value = s.start_time;
                                document.getElementById(`end-${day}`).value = s.end_time;
                            }
                        });
                    }
                } catch (err) {
                    console.error("Error loading profile schedule:", err);
                }

                
                scheduleForm.addEventListener("submit", async (e) => {
                    e.preventDefault();
                    const schedulePayload = [];

                    checkboxes.forEach(cb => {
                        const day = parseInt(cb.dataset.day);
                        const working = cb.checked;
                        const start = document.getElementById(`start-${day}`).value;
                        const end = document.getElementById(`end-${day}`).value;

                        schedulePayload.push({
                            day_of_week: day,
                            working: working,
                            start_time: start,
                            end_time: end
                        });
                    });

                    try {
                        const saveRes = await fetch("/api/profile/schedule", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ schedule: schedulePayload })
                        });
                        const saveData = await saveRes.json();
                        if (saveRes.ok) {
                            showToast(saveData.mesaj || "Program salvat cu succes!", "success");
                        } else {
                            showToast(saveData.eroare || "Eroare la salvarea programului.", "error");
                        }
                    } catch (err) {
                        showToast("Eroare de retea.", "error");
                    }
                });
            }

            
            const histEl = document.getElementById("prof-history");
            if (activitati.length === 0) {
                histEl.innerHTML = `<p style="color:var(--text-muted); font-size:0.88rem;">Nu ai participat la nicio sesiune inca.</p>`;
            } else {
                const pastFuture = activitati.reduce((acc, a) => {
                    const isPast = new Date(a.start_time) < new Date();
                    isPast ? acc.past.push(a) : acc.future.push(a);
                    return acc;
                }, { past: [], future: [] });

                let html = "";

                if (pastFuture.future.length > 0) {
                    html += `<p class="history-section-label">Viitoare</p>`;
                    pastFuture.future.forEach(a => { html += buildActivityRow(a, false); });
                }

                if (pastFuture.past.length > 0) {
                    html += `<p class="history-section-label" style="margin-top:16px;">Trecut</p>`;
                    pastFuture.past.forEach(a => { html += buildActivityRow(a, true); });
                }

                histEl.innerHTML = html;

                
                histEl.querySelectorAll(".activity-row").forEach(row => {
                    row.addEventListener("click", () => {
                        openProfDetailModal(parseInt(row.dataset.sessionId));
                    });
                });
            }

        } catch (e) {
            console.error("loadProfile error:", e);
        }
    }

    function buildActivityRow(a, isPast) {
        const cat = a.category || "fitness";
        const catMeta = CATEGORY_LABELS[cat] || CATEGORY_LABELS.fitness;
        const sStart = new Date(a.start_time);
        const sEnd = new Date(a.end_time);
        const dateStr = sStart.toLocaleDateString("ro-RO", { weekday: "short", day: "numeric", month: "short", year: "numeric" });
        const timeStr = `${fmtTime(sStart)} – ${fmtTime(sEnd)}`;
        const isFull = parseInt(a.current_bookings) >= parseInt(a.max_capacity);

        const isPrivate = a.title.endsWith('(Privat)');
        const bgStyle = isPrivate ? `background: repeating-linear-gradient(45deg, ${catMeta.bg}, ${catMeta.bg} 10px, rgba(0,0,0,0.03) 10px, rgba(0,0,0,0.03) 20px); border: 1px dashed ${catMeta.border};` : '';
        const titleBadge = isPrivate ? ' <span style="font-size:0.65rem; background:var(--primary); color:#fff; padding:1px 4px; border-radius:4px; vertical-align:middle;">PRIVAT</span>' : '';

        return `
            <div class="activity-row ${isPast ? "activity-row--past" : ""}" data-session-id="${a.session_id}" title="Click pentru detalii" style="${bgStyle}">
                <div class="activity-cat-stripe" style="background:${catMeta.color};"></div>
                <div class="activity-row-body">
                    <div class="activity-row-main">
                        <span class="activity-title">${a.title}${titleBadge}</span>
                        <span class="activity-date">${dateStr}</span>
                    </div>
                    <div class="activity-row-sub">
                        <span>🕐 ${timeStr}</span>
                        <span>👤 ${a.trainer_name}</span>
                        <span>🏠 ${a.room_name || "—"}</span>
                        <span style="
                            font-size:0.7rem; font-weight:600; padding:1px 8px; border-radius:100px;
                            background:${catMeta.bg}; color:${catMeta.color};
                        ">${catMeta.label}</span>
                    </div>
                </div>
                <span class="activity-arrow">›</span>
            </div>
        `;
    }

    
    const profDetailModal = document.getElementById("prof-detail-modal");
    if (profDetailModal) {
        document.getElementById("prof-close-detail").addEventListener("click", () => {
            profDetailModal.style.display = "none";
        });
        profDetailModal.addEventListener("click", e => {
            if (e.target === profDetailModal) profDetailModal.style.display = "none";
        });
    }

    async function openProfDetailModal(sessionId) {
        if (!profDetailModal) return;
        profDetailModal.style.display = "flex";

        
        ["prof-detail-title","prof-detail-subtitle","prof-detail-description",
         "prof-detail-time","prof-detail-room","prof-detail-trainer","prof-detail-capacity"].forEach(id => {
            document.getElementById(id).textContent = "Se incarca...";
        });
        document.getElementById("prof-detail-participants").innerHTML = "";

        try {
            const res = await fetch(`/api/sessions/detail?id=${sessionId}`);
            if (!res.ok) return;
            const data = await res.json();
            const s = data.sesiune;
            const parti = data.participanti || [];

            const sStart = new Date(s.start_time);
            const sEnd = new Date(s.end_time);
            const cat = s.category || "fitness";
            const catMeta = CATEGORY_LABELS[cat] || CATEGORY_LABELS.fitness;

            const badge = document.getElementById("prof-detail-cat-badge");
            if (badge) {
                badge.textContent = catMeta.label;
                badge.style.setProperty("--cat-color", catMeta.color);
                badge.style.setProperty("--cat-bg", catMeta.bg);
            }

            document.getElementById("prof-detail-title").textContent = s.title;
            document.getElementById("prof-detail-subtitle").textContent = fmtDate(sStart);
            document.getElementById("prof-detail-description").textContent = s.description || "Fara descriere.";
            document.getElementById("prof-detail-time").textContent = `${fmtTime(sStart)} – ${fmtTime(sEnd)}`;
            document.getElementById("prof-detail-room").textContent = s.room_name || "—";
            document.getElementById("prof-detail-trainer").textContent = s.trainer_name;
            document.getElementById("prof-detail-capacity").textContent = `${s.current_bookings} / ${s.max_capacity} locuri`;

            const listEl = document.getElementById("prof-detail-participants");
            if (parti.length === 0) {
                listEl.innerHTML = `<span style="color:var(--text-muted);font-size:0.85rem;">Nicio persoana inscrisa.</span>`;
            } else {
                parti.forEach(p => {
                    listEl.innerHTML += `
                        <div class="participant-row">
                            <span class="participant-avatar">${p.name.charAt(0).toUpperCase()}</span>
                            <span>${p.name}</span>
                        </div>
                    `;
                });
            }
        } catch(e) {
            console.error("openProfDetailModal error:", e);
        }
    }

    if (document.getElementById("prof-name")) loadProfile();

    
    
    
    
    async function loadRequests() {
        const tbody = document.getElementById("requests-table-body");
        if (!tbody) return;

        try {
            const res = await fetch("/api/requests");
            if (!res.ok) return;
            const data = await res.json();
            tbody.innerHTML = "";

            if (data.requests.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--text-muted);">Nu exista nicio cerere.</td></tr>`;
                return;
            }

            data.requests.forEach(req => {
                const dateStr = new Date(req.date).toLocaleDateString("ro-RO");
                const timeStr = `${fmtTime(new Date(req.start_time))} - ${fmtTime(new Date(req.end_time))}`;
                
                let statusBadge = '';
                if (req.status === 'pending') statusBadge = `<span class="role-badge role-badge--member">In asteptare</span>`;
                else if (req.status === 'accepted') statusBadge = `<span class="role-badge role-badge--therapist">Acceptata</span>`;
                else if (req.status === 'denied') statusBadge = `<span class="role-badge role-badge--admin">Respinsa</span>`;

                const trainerPref = req.trainer_name ? req.trainer_name : 'Oricare din categorie';
                const catMeta = CATEGORY_LABELS[req.category] || CATEGORY_LABELS.fitness;
                
                let actions = '';
                if (req.status === 'pending') {
                    if (document.getElementById("accept-request-modal")) {
                        actions = `
                            <button class="btn btn-sm" style="background:#16a34a;color:#fff;" onclick="openAcceptModal(${req.id})">Accepta</button>
                            <button class="btn btn-sm btn-outline" style="color:#dc2626;border-color:#dc2626;" onclick="denyRequest(${req.id})">Respinge</button>
                        `;
                    } else {
                        actions = '<span style="color:var(--text-muted);font-size:0.8rem;">In asteptare</span>';
                    }
                } else {
                    actions = `<span style="font-size:0.8rem;color:var(--text-muted);">Procesata de ${req.handler_name || '?'}</span>`;
                }

                tbody.innerHTML += `
                    <tr>
                        <td><strong>${req.title}</strong><br><small style="color:var(--text-muted);">${req.description || ''}</small></td>
                        <td><span style="font-size:0.75rem; font-weight:600; padding:2px 8px; border-radius:100px; background:${catMeta.bg}; color:${catMeta.color};">${catMeta.label}</span></td>
                        <td>${req.user_name}</td>
                        <td>${trainerPref}</td>
                        <td>${dateStr}<br><small style="color:var(--text-muted);">${timeStr}</small></td>
                        <td>${statusBadge}</td>
                        <td style="display:flex;gap:5px;">${actions}</td>
                    </tr>
                `;
            });
        } catch(e) {
            console.error("loadRequests error:", e);
        }
    }

    
    const reqModal = document.getElementById("request-modal");
    if (reqModal) {
        let allSpecialists = [];

        const checkTrainerAvailability = (trainer, dayOfWeek, startVal, endVal) => {
            const scheds = trainer.schedules || [];
            const dayScheds = scheds.filter(s => s.day_of_week === dayOfWeek);
            if (dayScheds.length === 0) return false;
            return dayScheds.some(s => {
                return startVal >= s.start_time && endVal <= s.end_time && startVal < endVal;
            });
        };

        const validateSchedule = () => {
            const dateVal = document.getElementById("req-date").value;
            const startVal = document.getElementById("req-start").value;
            const endVal = document.getElementById("req-end").value;
            const trainerSelect = document.getElementById("req-trainer");
            const trainerId = trainerSelect.value;
            const cat = document.getElementById("req-category").value;
            
            const warningEl = document.getElementById("schedule-warning");
            const submitBtn = document.getElementById("request-submit-btn");
            
            if (!warningEl || !submitBtn) return;

            warningEl.style.display = "none";
            warningEl.textContent = "";
            submitBtn.disabled = false;

            
            if (dateVal && startVal && endVal) {
                const parts = dateVal.split('-');
                const dObj = new Date(parts[0], parts[1] - 1, parts[2]);
                const dayOfWeek = dObj.getDay();
                
                Array.from(trainerSelect.options).forEach(opt => {
                    if (!opt.value) return; 
                    
                    const trainerObj = allSpecialists.find(u => u.id == opt.value);
                    if (trainerObj) {
                        const isAvailable = checkTrainerAvailability(trainerObj, dayOfWeek, startVal, endVal);
                        if (!isAvailable) {
                            opt.disabled = true;
                            if (!opt.textContent.includes(" (indisponibil)")) {
                                opt.textContent = trainerObj.name + " (indisponibil in acest interval)";
                            }
                        } else {
                            opt.disabled = false;
                            opt.textContent = trainerObj.name;
                        }
                    }
                });
            }

            
            if (dateVal && startVal && endVal) {
                const parts = dateVal.split('-');
                const dObj = new Date(parts[0], parts[1] - 1, parts[2]);
                const dayOfWeek = dObj.getDay();
                const zile = ["Duminica", "Luni", "Marti", "Miercuri", "Joi", "Vineri", "Sambata"];
                const numeZi = zile[dayOfWeek];

                if (trainerId) {
                    const trainerObj = allSpecialists.find(u => u.id == trainerId);
                    if (trainerObj) {
                        const scheds = trainerObj.schedules || [];
                        const dayScheds = scheds.filter(s => s.day_of_week === dayOfWeek);
                        
                        if (dayScheds.length === 0) {
                            warningEl.textContent = `Antrenorul ${trainerObj.name} nu lucreaza in aceasta zi (${numeZi}).`;
                            warningEl.style.display = "block";
                            submitBtn.disabled = true;
                            return;
                        }

                        const fits = dayScheds.some(s => {
                            return startVal >= s.start_time && endVal <= s.end_time && startVal < endVal;
                        });

                        if (!fits) {
                            const schedStrings = dayScheds.map(s => `${s.start_time}-${s.end_time}`).join(", ");
                            warningEl.textContent = `Antrenorul ${trainerObj.name} este disponibil doar in intervalul: ${schedStrings} in ziua de ${numeZi}.`;
                            warningEl.style.display = "block";
                            submitBtn.disabled = true;
                            return;
                        }
                    }
                } else {
                    
                    const catTrainers = allSpecialists.filter(u => u.specialization === cat);
                    if (catTrainers.length > 0) {
                        const anyAvailable = catTrainers.some(u => checkTrainerAvailability(u, dayOfWeek, startVal, endVal));
                        if (!anyAvailable) {
                            warningEl.textContent = `Niciun antrenor din categoria "${cat}" nu are program sau nu este disponibil in acest interval.`;
                            warningEl.style.display = "block";
                            submitBtn.disabled = true;
                            return;
                        }
                    }
                }
            }
        };

        const updateTrainerDropdown = () => {
            const cat = document.getElementById("req-category").value;
            const trainerSelect = document.getElementById("req-trainer");
            trainerSelect.innerHTML = '<option value="">Oricare din categorie</option>';
            
            
            const filtered = allSpecialists.filter(u => u.specialization === cat);
            filtered.forEach(u => {
                trainerSelect.innerHTML += `<option value="${u.id}">${u.name}</option>`;
            });
            validateSchedule();
        };

        document.getElementById("btn-open-request-modal").addEventListener("click", async () => {
            reqModal.style.display = "flex";
            
            
            try {
                const res = await fetch("/api/specialists");
                if (res.ok) {
                    const data = await res.json();
                    allSpecialists = data.specialisti;
                    updateTrainerDropdown();
                }
            } catch(e) { console.error(e); }
        });

        document.getElementById("req-category").addEventListener("change", () => {
            updateTrainerDropdown();
        });

        document.getElementById("req-date").addEventListener("input", validateSchedule);
        document.getElementById("req-start").addEventListener("input", validateSchedule);
        document.getElementById("req-end").addEventListener("input", validateSchedule);
        document.getElementById("req-trainer").addEventListener("change", validateSchedule);

        document.getElementById("close-request-modal").addEventListener("click", () => {
            reqModal.style.display = "none";
        });

        document.getElementById("request-form").addEventListener("submit", async (e) => {
            e.preventDefault();
            const payload = {
                title: document.getElementById("req-title").value,
                category: document.getElementById("req-category").value,
                description: document.getElementById("req-desc").value,
                preferred_trainer_id: document.getElementById("req-trainer").value || null,
                date: document.getElementById("req-date").value,
                start_time: document.getElementById("req-start").value,
                end_time: document.getElementById("req-end").value,
            };

            try {
                const res = await fetch("/api/requests", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) {
                    showToast(data.mesaj || 'Cererea a fost trimisa!', 'success');
                    reqModal.style.display = "none";
                    loadRequests();
                } else {
                    showToast('Eroare: ' + data.eroare, 'error');
                }
            } catch(e) { showToast('Eroare la trimiterea cererii.', 'error'); }
        });
    }

    
    const acceptModal = document.getElementById("accept-request-modal");
    if (acceptModal) {
        window.openAcceptModal = async function(reqId) {
            document.getElementById("accept-req-id").value = reqId;
            acceptModal.style.display = "flex";

            
            try {
                const res = await fetch("/api/resources");
                if (res.ok) {
                    const data = await res.json();
                    const roomSelect = document.getElementById("accept-req-room");
                    roomSelect.innerHTML = '';
                    data.resurse.forEach(r => {
                        if (!r.type || r.type === 'room') {
                            roomSelect.innerHTML += `<option value="${r.id}">${r.name} (Capacitate: ${r.capacity})</option>`;
                        }
                    });
                }
            } catch(e) { console.error(e); }
        };

        document.getElementById("close-accept-modal").addEventListener("click", () => {
            acceptModal.style.display = "none";
        });

        document.getElementById("accept-request-form").addEventListener("submit", async (e) => {
            e.preventDefault();
            const reqId = document.getElementById("accept-req-id").value;
            const roomId = document.getElementById("accept-req-room").value;

            try {
                const res = await fetch("/api/requests/accept", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ request_id: reqId, room_id: roomId })
                });
                const data = await res.json();
                if (res.ok) {
                    showToast(data.mesaj || 'Cererea a fost acceptata!', 'success');
                    acceptModal.style.display = "none";
                    loadRequests();
                } else {
                    showToast('Eroare: ' + data.eroare, 'error');
                }
            } catch(e) { showToast('Eroare la procesarea cererii.', 'error'); }
        });

        window.denyRequest = async function(reqId) {
            if (!confirm("Sigur vrei sa respingi aceasta cerere?")) return;
            try {
                const res = await fetch("/api/requests/deny", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ request_id: reqId })
                });
                const data = await res.json();
                if (res.ok) {
                    showToast(data.mesaj || 'Cererea a fost respinsa.', 'info');
                    loadRequests();
                } else {
                    showToast('Eroare: ' + data.eroare, 'error');
                }
            } catch(e) { showToast('Eroare la respingerea cererii.', 'error'); }
        };
    }

    if (document.getElementById("requests-table-body")) loadRequests();

});
