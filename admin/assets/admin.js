/* ===== RESET ===== */
* {
  box-sizing: border-box;
}

:root {
  --bg: #081120;
  --bg2: #0b1220;
  --card: #111c34;
  --line: rgba(255,255,255,0.09);
  --text: #ffffff;
  --muted: #c8d4ea;
  --primary: #2563eb;
  --primary2: #1d4ed8;
  --success-bg: rgba(34,197,94,0.12);
  --success-line: rgba(34,197,94,0.26);
  --success-text: #cbf6d8;
  --error-bg: rgba(239,68,68,0.12);
  --error-line: rgba(239,68,68,0.26);
  --error-text: #ffd2d2;
  --info-bg: rgba(37,99,235,0.12);
  --info-line: rgba(37,99,235,0.26);
  --info-text: #d9e6ff;
  --radius: 22px;
  --shadow: 0 18px 40px rgba(0,0,0,0.35);
}

html, body {
  margin: 0;
  padding: 0;
  font-family: Arial, sans-serif;
  background:
    radial-gradient(circle at top, rgba(37,99,235,0.22), transparent 35%),
    linear-gradient(180deg, var(--bg) 0%, var(--bg2) 100%);
  color: var(--text);
  min-height: 100%;
}

.hidden {
  display: none !important;
}

.page-shell,
.page-standalone {
  min-height: 100vh;
  padding: 24px;
}

.auth-layout {
  width: min(1100px, 100%);
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.05fr 0.95fr;
  gap: 24px;
  align-items: stretch;
}

.panel {
  background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
  border: 1px solid var(--line);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  backdrop-filter: blur(8px);
  padding: 24px;
}

.panel-brand {
  min-height: 560px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.panel-form {
  min-height: 560px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.badge-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(37,99,235,0.14);
  border: 1px solid rgba(37,99,235,0.32);
  color: #cfe0ff;
  width: max-content;
  font-size: 13px;
  font-weight: bold;
}

.page-title {
  font-size: 44px;
  line-height: 1.15;
  margin: 18px 0 12px;
  font-weight: 800;
}

.page-desc,
.section-desc {
  color: var(--muted);
  line-height: 1.9;
  font-size: 15px;
}

.section-title {
  margin: 0 0 10px;
  font-size: 34px;
  font-weight: 800;
}

.no-margin {
  margin: 0;
}

.feature-grid,
.info-grid {
  display: grid;
  gap: 14px;
}

.feature-grid {
  grid-template-columns: repeat(2, 1fr);
  margin-top: 30px;
}

.feature-box,
.info-card,
.tip-box {
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--line);
  border-radius: 18px;
  padding: 16px;
}

.feature-box strong,
.info-card strong {
  display: block;
  margin-bottom: 8px;
  font-size: 15px;
}

.feature-box span,
.info-card span,
.tip-box {
  color: var(--muted);
  line-height: 1.8;
  font-size: 14px;
}

.brand-footer {
  margin-top: 26px;
  color: #d9e5ff;
  font-weight: bold;
  font-size: 15px;
}

.form-group {
  margin-bottom: 16px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 700;
  color: #dbe7fb;
}

input,
select {
  width: 100%;
  border: 1px solid rgba(255,255,255,0.12);
  background: #0b1326;
  color: #fff;
  border-radius: 15px;
  padding: 14px 15px;
  font-size: 15px;
  outline: none;
}

input:focus,
select:focus {
  border-color: rgba(37,99,235,0.7);
  box-shadow: 0 0 0 4px rgba(37,99,235,0.12);
}

.btn {
  border: 0;
  border-radius: 16px;
  padding: 14px 20px;
  font-size: 15px;
  font-weight: 800;
  color: #fff;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-block {
  width: 100%;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary2));
  box-shadow: 0 14px 28px rgba(37,99,235,0.22);
}

.btn-muted {
  background: rgba(255,255,255,0.08);
  border: 1px solid var(--line);
}

.status-box {
  margin-top: 18px;
  padding: 14px 16px;
  border-radius: 16px;
  font-size: 14px;
  line-height: 1.8;
  display: none;
  white-space: pre-wrap;
}

.status-box.show {
  display: block;
}

.status-box.success {
  background: var(--success-bg);
  border: 1px solid var(--success-line);
  color: var(--success-text);
}

.status-box.error {
  background: var(--error-bg);
  border: 1px solid var(--error-line);
  color: var(--error-text);
}

.status-box.info {
  background: var(--info-bg);
  border: 1px solid var(--info-line);
  color: var(--info-text);
}

.dashboard-shell {
  min-height: 100vh;
  padding: 24px;
}

.dashboard-wrap,
.standalone-wrap {
  width: min(1200px, 100%);
  margin: 0 auto;
}

.dashboard-head,
.topbar,
.button-row,
.dashboard-actions,
.check-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.dashboard-head,
.topbar {
  justify-content: space-between;
}

.info-grid {
  grid-template-columns: repeat(3, 1fr);
  margin-top: 18px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.full-col {
  grid-column: 1 / -1;
}

.preview-box {
  margin-top: 12px;
  border: 1px solid var(--line);
  border-radius: 18px;
  overflow: hidden;
  max-width: 340px;
  background: #091225;
}

.preview-box img {
  display: block;
  width: 100%;
  height: auto;
}

.mt-20 {
  margin-top: 20px;
}

/* ===== ADDED: DISABLED / PERMISSION STATES ===== */
.disabled {
  opacity: 0.5 !important;
  cursor: not-allowed !important;
  pointer-events: none;
}

button.disabled,
.btn.disabled {
  filter: grayscale(0.15);
  box-shadow: none !important;
}

input.disabled,
select.disabled,
textarea.disabled {
  background: rgba(255,255,255,0.04) !important;
  color: rgba(255,255,255,0.55) !important;
  border-color: rgba(255,255,255,0.08) !important;
}

button:disabled,
input:disabled,
select:disabled,
textarea:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

button:disabled {
  box-shadow: none !important;
}

/* ===== ADDED: ORDER HISTORY OVERRIDE STYLE ===== */
.admin-override-item {
  border: 1px solid rgba(245,158,11,0.35) !important;
  background: linear-gradient(180deg, rgba(245,158,11,0.10), rgba(255,255,255,0.03)) !important;
  box-shadow: 0 10px 22px rgba(245,158,11,0.08);
}

.admin-override-item .history-row strong,
.admin-override-item .history-meta strong {
  color: #fbbf24;
}

.admin-override-item .history-meta {
  color: #fde7b0;
}

/* ===== ADDED: HIDDEN PERMISSION HELPERS ===== */
[data-permission],
[data-panel-permission] {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

@media (max-width: 980px) {
  .auth-layout,
  .feature-grid,
  .info-grid,
  .form-grid {
    grid-template-columns: 1fr;
  }

  .page-title {
    font-size: 34px;
  }

  .section-title {
    font-size: 28px;
  }

  .panel-brand,
  .panel-form {
    min-height: auto;
  }
}


/* ===== Admin visual cleanup ===== */
.admin-main-tabs {
  grid-template-columns: repeat(5, minmax(160px, 1fr)) !important;
}

.add-product-toolbar {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 14px;
  align-items: center;
  margin-bottom: 18px;
}

.add-product-toolbar .btn {
  min-width: 150px;
}

.add-product-title {
  margin: 0;
  text-align: right;
  white-space: nowrap;
}

.add-product-inline-status {
  margin-top: 0;
  min-height: 54px;
  align-items: center;
}

.add-product-inline-status.show {
  display: flex;
}

#tab-add-product .panel-desc {
  display: none;
}

#tab-add-product .top-grid {
  align-items: stretch;
}

#tab-add-product .stack-gap {
  height: 100%;
}

#tab-add-product input[type="number"]::-webkit-outer-spin-button,
#tab-add-product input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

#tab-add-product input[type="number"] {
  -moz-appearance: textfield;
  appearance: textfield;
}

@media (max-width: 1100px) {
  .add-product-toolbar {
    grid-template-columns: 1fr;
  }

  .add-product-title {
    order: -1;
  }

  .add-product-toolbar .btn {
    width: 100%;
  }
}


/* ===== ADMIN VISUAL CLEANUP 20260414 ===== */
.admin-main-tabs {
  direction: ltr !important;
}
.page-desc,
.panel-desc,
.panel-note,
.helper-box,
.note-inline,
.helper-note,
.mini-note {
  display: none !important;
}
.placeholder-card span {
  display: none !important;
}
.panel-brand .page-desc,
.panel-form .section-desc {
  display: block !important;
}
input,
select,
textarea {
  direction: ltr;
  text-align: left;
  color-scheme: dark;
}
label,
.form-group label {
  direction: ltr;
  text-align: left;
}
select option,
select optgroup {
  background: #162340 !important;
  color: #ffffff !important;
}
.embedded-admin-frame {
  background: #0a1120;
}
