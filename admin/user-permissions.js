let USERS_ROWS = [];
let ROLES_ROWS = [];
let PERMISSIONS_ROWS = [];
let CURRENT_USER_ID = null;

function usersSetStatus(type, message) {
  const box = document.getElementById('usersPermissionsStatus');
  if (!box) return;

  box.className = `status-box show ${type}`;
  box.textContent = message;
}

function usersClearStatus() {
  const box = document.getElementById('usersPermissionsStatus');
  if (!box) return;

  box.className = 'status-box';
  box.textContent = '';
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

async function fetchJson(url, options = {}) {
  const res = await fetch(url, {
    credentials: 'same-origin',
    cache: 'no-store',
    ...options
  });

  const raw = await res.text();
  let data = null;

  try {
    data = JSON.parse(raw);
  } catch (e) {
    throw new Error(raw || 'Unexpected server response');
  }

  return { res, data };
}

function renderPermissionChips(codes) {
  if (!Array.isArray(codes) || codes.length === 0) {
    return `<span class="permission-chip">No direct permissions</span>`;
  }

  return codes.map(code => `<span class="permission-chip">${escapeHtml(code)}</span>`).join('');
}

function buildRoleOptions(user) {
  return ROLES_ROWS.map(role => {
    const selected = String(user.role_id || '') === String(role.id) ? ' selected' : '';
    return `<option value="${role.id}"${selected}>${escapeHtml(role.display_name || role.name)}</option>`;
  }).join('');
}

function renderUsersTable() {
  const tbody = document.getElementById('usersPermissionsTableBody');
  if (!tbody) return;

  if (!Array.isArray(USERS_ROWS) || USERS_ROWS.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9"><div class="empty-box">لا يوجد مستخدمون مطابقون.</div></td></tr>`;
    return;
  }

  tbody.innerHTML = USERS_ROWS.map((user, index) => {
    return `
      <tr>
        <td>${index + 1}</td>
        <td>${escapeHtml(user.username)}</td>
        <td>${escapeHtml(user.full_name || '-')}</td>
        <td>${escapeHtml(user.email || '-')}</td>
        <td>
          <select class="user-role-select" data-user-id="${user.id}">
            <option value="">No Role</option>
            ${buildRoleOptions(user)}
          </select>
        </td>
        <td>
          <span class="badge ${user.is_active ? 'active' : 'inactive'}">
            ${user.is_active ? 'Active' : 'Inactive'}
          </span>
        </td>
        <td>
          <div class="permission-list">
            ${renderPermissionChips(user.direct_permission_codes || [])}
          </div>
        </td>
        <td>${escapeHtml(user.last_login_at || '-')}</td>
        <td>
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button type="button" class="btn-primary" onclick="saveUserRole(${user.id})">Save Role</button>
            <button type="button" class="btn-primary" onclick="toggleUserStatus(${user.id}, ${user.is_active ? 0 : 1})">
              ${user.is_active ? 'Disable' : 'Enable'}
            </button>
            <button type="button" class="btn-success" onclick="openUserPermissionsModal(${user.id})">Permissions</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function buildPermissionsGroups(selectedCodes = []) {
  const groups = {};

  PERMISSIONS_ROWS.forEach(permission => {
    const group = permission.group_name || 'general';
    if (!groups[group]) groups[group] = [];
    groups[group].push(permission);
  });

  return Object.keys(groups).sort().map(groupName => {
    const options = groups[groupName].map(permission => {
      const checked = selectedCodes.includes(permission.code) ? ' checked' : '';
      return `
        <label class="permission-option">
          <input type="checkbox" class="user-permission-checkbox" value="${escapeHtml(permission.code)}"${checked}>
          <span>${escapeHtml(permission.display_name || permission.code)}</span>
        </label>
      `;
    }).join('');

    return `
      <div class="permission-group">
        <h4>${escapeHtml(groupName)}</h4>
        ${options}
      </div>
    `;
  }).join('');
}

async function loadUsersDashboard() {
  const search = document.getElementById('usersSearchInput')?.value.trim() || '';
  const query = new URLSearchParams();
  if (search) query.set('search', search);

  usersSetStatus('info', 'جاري تحميل المستخدمين والصلاحيات...');

  try {
    const { data } = await fetchJson(`api/get-users-permissions-dashboard.php?${query.toString()}`);

    if (!data.ok) {
      usersSetStatus('error', data.message || 'Failed to load users.');
      return;
    }

    USERS_ROWS = Array.isArray(data.users) ? data.users : [];
    ROLES_ROWS = Array.isArray(data.roles) ? data.roles : [];
    PERMISSIONS_ROWS = Array.isArray(data.permissions) ? data.permissions : [];

    renderUsersTable();
    usersSetStatus('success', 'تم تحميل المستخدمين بنجاح.');
  } catch (e) {
    usersSetStatus('error', e.message || 'حدث خطأ أثناء تحميل المستخدمين.');
  }
}

window.saveUserRole = async function (userId) {
  const select = document.querySelector(`.user-role-select[data-user-id="${userId}"]`);
  const user = USERS_ROWS.find(row => Number(row.id) === Number(userId));

  if (!select || !user) {
    usersSetStatus('error', 'تعذر العثور على المستخدم.');
    return;
  }

  usersSetStatus('info', 'جاري حفظ دور المستخدم...');

  try {
    const { data } = await fetchJson('api/save-user-role.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: userId,
        role_id: Number(select.value || 0)
      })
    });

    if (!data.ok) {
      usersSetStatus('error', data.message || 'Failed to save user role.');
      return;
    }

    usersSetStatus('success', data.message || 'تم حفظ دور المستخدم بنجاح.');
    await loadUsersDashboard();
  } catch (e) {
    usersSetStatus('error', e.message || 'حدث خطأ أثناء حفظ دور المستخدم.');
  }
};

window.toggleUserStatus = async function (userId, isActive) {
  usersSetStatus('info', 'جاري تحديث حالة المستخدم...');

  try {
    const { data } = await fetchJson('api/save-user-role.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: userId,
        is_active: isActive
      })
    });

    if (!data.ok) {
      usersSetStatus('error', data.message || 'Failed to update user status.');
      return;
    }

    usersSetStatus('success', data.message || 'تم تحديث حالة المستخدم بنجاح.');
    await loadUsersDashboard();
  } catch (e) {
    usersSetStatus('error', e.message || 'حدث خطأ أثناء تحديث حالة المستخدم.');
  }
};

window.openUserPermissionsModal = function (userId) {
  const user = USERS_ROWS.find(row => Number(row.id) === Number(userId));
  const modal = document.getElementById('userPermissionsModal');
  const title = document.getElementById('userPermissionsModalTitle');
  const groups = document.getElementById('userPermissionsGroups');

  if (!user || !modal || !title || !groups) {
    usersSetStatus('error', 'تعذر فتح صلاحيات المستخدم.');
    return;
  }

  CURRENT_USER_ID = userId;
  title.textContent = `User Permissions - ${user.username}`;
  groups.innerHTML = buildPermissionsGroups(user.direct_permission_codes || []);
  modal.classList.add('active');
};

function closeUserPermissionsModal() {
  const modal = document.getElementById('userPermissionsModal');
  if (modal) {
    modal.classList.remove('active');
  }
  CURRENT_USER_ID = null;
}

async function saveCurrentUserPermissions() {
  if (!CURRENT_USER_ID) {
    usersSetStatus('error', 'No user selected.');
    return;
  }

  const selected = Array.from(document.querySelectorAll('.user-permission-checkbox:checked'))
    .map(checkbox => checkbox.value);

  usersSetStatus('info', 'جاري حفظ صلاحيات المستخدم...');

  try {
    const { data } = await fetchJson('api/save-user-permissions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: CURRENT_USER_ID,
        permission_codes: selected
      })
    });

    if (!data.ok) {
      usersSetStatus('error', data.message || 'Failed to save user permissions.');
      return;
    }

    usersSetStatus('success', data.message || 'تم حفظ صلاحيات المستخدم بنجاح.');
    closeUserPermissionsModal();
    await loadUsersDashboard();
  } catch (e) {
    usersSetStatus('error', e.message || 'حدث خطأ أثناء حفظ صلاحيات المستخدم.');
  }
}

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('loadUsersPermissionsBtn')?.addEventListener('click', loadUsersDashboard);
  document.getElementById('saveUserPermissionsBtn')?.addEventListener('click', saveCurrentUserPermissions);
  document.getElementById('userPermissionsCloseBtn')?.addEventListener('click', closeUserPermissionsModal);

  const modal = document.getElementById('userPermissionsModal');
  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        closeUserPermissionsModal();
      }
    });
  }

  loadUsersDashboard();
});
