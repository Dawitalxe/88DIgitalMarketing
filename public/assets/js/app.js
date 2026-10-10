(() => {
  const state = { csrf: document.querySelector('meta[name="csrf-token"]')?.content || window.APP?.csrf || '' };
  const basePath = window.APP_BASE || '';
  const apiUrl = path => `${basePath}${path}`;

  async function api(url, options = {}) {
    const opts = { ...options, headers: { 'Accept': 'application/json', ...(options.headers || {}) } };
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes((opts.method || 'GET').toUpperCase())) {
      opts.headers['Content-Type'] = 'application/json';
      opts.headers['X-CSRF-Token'] = state.csrf;
    }
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({ ok: false, error: 'Invalid server response' }));
    if (!res.ok || data.ok === false) throw new Error(data.error || `HTTP ${res.status}`);
    return data;
  }
  window.api = api;

  async function bootLogin() {
    const form = document.getElementById('loginForm');
    if (!form) return;
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const body = Object.fromEntries(new FormData(form));
      try {
        const res = await fetch(apiUrl('/api/login.php'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': state.csrf },
          body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Sign-in failed');
        location.reload();
      } catch (err) { document.getElementById('loginError').textContent = err.message }
    });
  }

  const view = document.getElementById('view');
  const pages = {
    overview: async () => `<div class="page-title"><div><h1>Dashboard</h1><div class="muted">88 Digi Mark internal operations</div></div></div>
      <div class="grid grid-4">
        <div class="card"><div class="kpi-label">Open tasks</div><div id="kpiTasks" class="stat">—</div></div>
        <div class="card"><div class="kpi-label">Pending approvals</div><div id="kpiApprovals" class="stat">—</div></div>
        <div class="card"><div class="kpi-label">Active companies</div><div id="kpiCompanies" class="stat">—</div></div>
        <div class="card"><div class="kpi-label">System</div><div id="kpiMaintenance" class="stat">—</div></div>
      </div>
      <div class="grid grid-2" style="margin-top:18px">
        <div class="card"><h3>Performance</h3><canvas id="performanceChart"></canvas></div>
        <div class="card"><h3>Security events</h3><div id="securityList" class="muted">Loading…</div></div>
      </div>`,
    tasks: async () => `<div class="page-title"><div><h1>Tasks</h1>
    <div class="muted">Daily allocation and personal task status</div></div></div>
      <div class="card">
      <div class="table-wrap">
      <table class="table"><thead>
      <tr><th>Task</th><th>Company</th><th>Assignee</th><th>Status</th><th>Due</th></tr></thead><tbody id="taskRows"></tbody></table></div></div>`,
    approvals: async () => `<div class="page-title"><div><h1>Approval Queue</h1><div class="muted">Design, content, gate pass and permissions</div></div></div>
      <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Type</th><th>Title</th><th>Requester</th><th>Requested</th><th></th></tr></thead><tbody id="approvalRows"></tbody></table></div></div>`,
    companies: async () => `<div class="page-title"><div><h1>Companies</h1><div class="muted">CRUD and multi-company operations</div></div><button id="newCompany" class="btn btn-primary">New company</button></div>
      <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead><tbody id="companyRows"></tbody></table></div></div>`,
    hr: async () => `<div class="page-title"><div><h1>HR / Payroll</h1><div class="muted">Salary and attendance reporting</div></div></div>
      <div class="grid grid-2"><div class="card"><h3>Payroll</h3><div class="table-wrap"><table class="table"><thead><tr><th>Employee</th><th>Base</th><th>Bonus</th><th>Deduct.</th><th>Net</th></tr></thead><tbody id="salaryRows"></tbody></table></div></div>
      <div class="card"><h3>Attendance</h3><div class="table-wrap"><table class="table"><thead><tr><th>Employee</th><th>Date</th><th>In</th><th>Out</th><th>Hours</th></tr></thead><tbody id="attendanceRows"></tbody></table></div></div></div>`,
    reception: async () => `<div class="page-title"><div><h1>Reception</h1><div class="muted">Gate passes and inventory</div></div></div>
      <div class="grid grid-2"><div class="card"><h3>Gate Pass</h3><form id="gatePass"><label>Purpose<textarea name="purpose" required></textarea></label><div class="form-actions"><button class="btn btn-accent">Submit request</button></div></form></div>
      <div class="card"><h3>Inventory</h3><div id="inventoryRows" class="table-wrap"></div></div></div>`,
    client: async () => `<div class="page-title"><div><h1>Client Dispatch</h1><div class="muted">Executive analytics and creative feedback</div></div></div>
      <div class="card"><h3>Social engagement — 30 days</h3><canvas id="clientChart"></canvas></div>`,
    security: async () => `<div class="page-title"><div><h1>Security & Audit</h1><div class="muted">Recent audit trail and suspicious activity</div></div></div>
      <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>IP</th></tr></thead><tbody id="auditRows"></tbody></table></div></div>`
  };

  async function render(name) {
    if (!view) return;
    document.querySelectorAll('[data-view]').forEach(a => a.classList.toggle('active', a.dataset.view === name));
    view.innerHTML = await pages[name]();
    try { await hydrate(name); } catch (e) { view.insertAdjacentHTML('afterbegin', `<div class="alert">${escapeHtml(e.message)}</div>`); }
  }

  function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])); }
  async function hydrate(name) {
    if (name === 'tasks') {
      const d = await api(apiUrl('/api/tasks.php?action=list'));
      document.getElementById('taskRows').innerHTML = d.items.map(x => `<tr><td>${escapeHtml(x.title)}</td><td>${escapeHtml(x.company || '—')}</td><td>${escapeHtml(x.assignee || '—')}</td><td><select data-task="${x.id}" class="statusSelect">${['Pending', 'In Progress', 'Done'].map(s => `<option ${s === x.status ? 'selected' : ''}>${s}</option>`).join('')}</select></td><td>${escapeHtml(x.due_date || '—')}</td></tr>`).join('');
      document.querySelectorAll('.statusSelect').forEach(el => el.onchange = async () => api(apiUrl('/api/tasks.php?action=status'), { method: 'POST', body: JSON.stringify({ id: el.dataset.task, status: el.value }) }));
    }
    if (name === 'approvals') {
      const d = await api(apiUrl('/api/approvals.php?action=queue'));
      document.getElementById('approvalRows').innerHTML = d.items.map(x => `<tr><td>${escapeHtml(x.request_type)}</td><td>${escapeHtml(x.title)}</td><td>${escapeHtml(x.requester)}</td><td>${escapeHtml(x.requested_at)}</td><td><button class="btn btn-primary" data-approve="${x.id}">Review</button></td></tr>`).join('');
      document.querySelectorAll('[data-approve]').forEach(b => b.onclick = async () => { const decision = confirm('Approve this request?') ? 'Approved' : 'Rejected'; await api(apiUrl('/api/approvals.php?action=decision'), { method: 'POST', body: JSON.stringify({ id: b.dataset.approve, decision }) }); render('approvals'); });
    }
    if (name === 'companies') {
      const d = await api(apiUrl('/api/companies.php?action=list'));
      document.getElementById('companyRows').innerHTML = d.items.map(x => `<tr><td>${escapeHtml(x.name)}</td><td>${escapeHtml(x.code)}</td><td><span class="badge ${x.status === 'active' ? 'badge-green' : ''}">${escapeHtml(x.status)}</span></td><td><button class="btn btn-ghost" data-archive="${x.id}">Archive</button></td></tr>`).join('');
      document.querySelectorAll('[data-archive]').forEach(b => b.onclick = async () => { if (confirm('Archive company?')) { await api(apiUrl('/api/companies.php?action=delete'), { method: 'POST', body: JSON.stringify({ id: b.dataset.archive }) }); render('companies') } })
      document.getElementById('newCompany').onclick = async () => { const name = prompt('Company name'); if (!name) return; const code = prompt('Company code (A-Z, 0-9, _ or -)'); if (!code) return; await api(apiUrl('/api/companies.php?action=save'), { method: 'POST', body: JSON.stringify({ name, code, status: 'active' }) }); render('companies'); }
    }
    if (name === 'hr') {
      const [s, a] = await Promise.all([api(apiUrl('/api/hr.php?action=salary')), api(apiUrl('/api/hr.php?action=attendance'))]);
      document.getElementById('salaryRows').innerHTML = s.items.map(x => `<tr><td>${escapeHtml(x.full_name)}</td><td>${x.base_salary}</td><td>${x.bonus}</td><td>${x.deductions}</td><td><strong>${x.net_salary}</strong></td></tr>`).join('');
      document.getElementById('attendanceRows').innerHTML = a.items.map(x => `<tr><td>${escapeHtml(x.full_name)}</td><td>${escapeHtml(x.work_date)}</td><td>${escapeHtml(x.check_in || '—')}</td><td>${escapeHtml(x.check_out || '—')}</td><td>${x.work_hours ?? '—'}</td></tr>`).join('');
    }
    if (name === 'reception') {
      document.getElementById('gatePass').onsubmit = async e => { e.preventDefault(); const body = Object.fromEntries(new FormData(e.target)); await api(apiUrl('/api/reception.php?action=gate-pass'), { method: 'POST', body: JSON.stringify(body) }); e.target.reset(); alert('Gate pass submitted'); };
      const d = await api(apiUrl('/api/reception.php?action=inventory'));
      document.getElementById('inventoryRows').innerHTML = `<table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Status</th></tr></thead><tbody>${d.items.map(x => `<tr><td>${escapeHtml(x.name)}</td><td>${x.quantity} ${escapeHtml(x.unit)}</td><td>${escapeHtml(x.status)}</td></tr>`).join('')}</tbody></table>`;
    }
    if (name === 'client') {
      const d = await api(apiUrl('/api/client.php?action=analytics'));
      const labels = [...new Set(d.items.map(x => x.metric_date))];
      const engagement = labels.map(day => d.items.filter(x => x.metric_date === day).reduce((n, x) => n + Number(x.engagement || 0), 0));
      new Chart(document.getElementById('clientChart'), { type: 'line', data: { labels, datasets: [{ label: 'Engagement', data: engagement, tension: .2 }] }, options: { responsive: true } });
    }
    if (name === 'security') {
      const d = await api(apiUrl('/api/system.php?action=audit'));
      document.getElementById('auditRows').innerHTML = d.items.map(x => `<tr><td>${escapeHtml(x.created_at)}</td><td>${escapeHtml(x.full_name || 'System')}</td><td>${escapeHtml(x.action)}</td><td>${escapeHtml(x.ip_address || '—')}</td></tr>`).join('');
    }
    if (name === 'overview') {
      const [t, a, c, m, s] = await Promise.all([
        api(apiUrl('/api/tasks.php?action=list')), api(apiUrl('/api/approvals.php?action=queue')), api(apiUrl('/api/companies.php?action=list')),
        api(apiUrl('/api/system.php?action=maintenance')), api(apiUrl('/api/system.php?action=audit'))
      ]);
      document.getElementById('kpiTasks').textContent = t.items.filter(x => x.status !== 'Done').length;
      document.getElementById('kpiApprovals').textContent = a.items.length;
      document.getElementById('kpiCompanies').textContent = c.items.filter(x => x.status === 'active').length;
      document.getElementById('kpiMaintenance').textContent = m.maintenance ? 'OFF' : 'ON';
      document.getElementById('securityList').innerHTML = s.items.slice(0, 8).map(x => `<div style="padding:7px 0;border-bottom:1px solid var(--border)"><strong>${escapeHtml(x.action)}</strong><br><small class="muted">${escapeHtml(x.created_at)} · ${escapeHtml(x.ip_address || '—')}</small></div>`).join('');
      const labels = ['Tasks', 'Approvals', 'Companies'];
      new Chart(document.getElementById('performanceChart'), { type: 'bar', data: { labels, datasets: [{ label: 'Current', data: [t.items.length, a.items.length, c.items.length] }] }, options: { responsive: true } });
    }
  }

  document.querySelectorAll('[data-view]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); history.pushState({}, '', a.hash); render(a.dataset.view) }));
  window.addEventListener('popstate', () => render(location.hash.slice(1) || 'overview'));
  document.getElementById('logoutBtn')?.addEventListener('click', async () => { await api(apiUrl('/api/auth.php?action=logout'), { method: 'POST' }); location.reload() });
  bootLogin();
  if (view) render(location.hash.slice(1) || 'overview');
})();
