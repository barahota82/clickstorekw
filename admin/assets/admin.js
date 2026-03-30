function adminSetStatus(id, type, message) {
  const box = document.getElementById(id);
  if (!box) return;

  box.className = 'status-box show ' + type;
  box.textContent = message;
}

function adminClearStatus(id) {
  const box = document.getElementById(id);
  if (!box) return;

  box.className = 'status-box';
  box.textContent = '';
}
