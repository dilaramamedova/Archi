import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';

export default function init() {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  // --- Change password ---
  const pwdForm = document.getElementById('passwordForm');
  const pwdErr = document.getElementById('pwdErr');
  const pwdOk = document.getElementById('pwdOk');
  const pwdBtn = document.getElementById('pwdSubmit');

  if (pwdForm) {
    pwdForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      pwdErr.dataset.on = 'false';
      pwdOk.dataset.on = 'false';
      clearErrors(pwdForm);
      setLoading(pwdBtn, true);

      const res = await authFetch('/cabinet/password', {
        current_password: pwdForm.querySelector('[name="current_password"]').value,
        password: pwdForm.querySelector('[name="password"]').value,
        password_confirmation: pwdForm.querySelector('[name="password_confirmation"]').value,
      });

      setLoading(pwdBtn, false);

      if (res.ok) {
        pwdOk.textContent = res.data.message;
        pwdOk.dataset.on = 'true';
        pwdForm.reset();
      } else {
        showErrors(pwdForm, res.errors, 'pwdErr');
      }
    });
  }

  // --- Active sessions ---
  const sessionsContainer = document.getElementById('sessionsContainer');
  if (sessionsContainer) {
    loadSessions();
  }

  async function loadSessions() {
    try {
      const res = await fetch('/cabinet/sessions', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
      });
      const sessions = await res.json();
      renderSessions(sessions);
    } catch {
      sessionsContainer.innerHTML = '<p class="text-[14px] text-error">Failed to load sessions.</p>';
    }
  }

  function renderSessions(sessions) {
    sessionsContainer.innerHTML = '';
    sessions.forEach((s) => {
      const row = document.createElement('div');
      row.className = 'cab-row justify-between';

      const info = document.createElement('div');
      info.className = 'spsec-session-info';

      const titleDiv = document.createElement('div');
      titleDiv.className = 'spsec-session-title' + (s.is_current ? '' : ' nogap');

      const deviceP = document.createElement('p');
      deviceP.textContent = s.user_agent;
      titleDiv.appendChild(deviceP);

      if (s.is_current) {
        const badge = document.createElement('span');
        badge.className = 'ui-badge xs ok';
        badge.textContent = document.querySelector('[data-this-device]')?.textContent
          || (document.documentElement.lang === 'ru' ? 'Это устройство'
            : document.documentElement.lang === 'en' ? 'This device' : 'Bu cihaz');
        titleDiv.appendChild(badge);
      }

      info.appendChild(titleDiv);

      const meta = document.createElement('p');
      meta.className = 'spsec-session-sub';
      meta.textContent = `${s.ip_address} · ${s.last_activity}`;
      info.appendChild(meta);

      row.appendChild(info);

      if (!s.is_current) {
        const logoutBtn = document.createElement('button');
        logoutBtn.type = 'button';
        logoutBtn.className = 'spsec-logout';
        logoutBtn.textContent = document.querySelector('[data-logout-text]')?.textContent
          || (document.documentElement.lang === 'ru' ? 'Выйти'
            : document.documentElement.lang === 'en' ? 'Sign out' : 'Çıxış et');
        logoutBtn.addEventListener('click', async () => {
          logoutBtn.disabled = true;
          const res = await fetch('/cabinet/sessions', {
            method: 'DELETE',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ session_id: s.id }),
          });
          if (res.ok) {
            row.remove();
          } else {
            logoutBtn.disabled = false;
          }
        });
        row.appendChild(logoutBtn);
      }

      sessionsContainer.appendChild(row);
    });
  }

  // --- 2FA toggle (visual only for now) ---
  document.querySelectorAll('.ui-toggle').forEach((toggle) =>
    toggle.addEventListener('click', () => {
      const on = toggle.dataset.on !== 'true';
      toggle.dataset.on = String(on);
      toggle.setAttribute('aria-pressed', String(on));
    })
  );

  // --- Deactivate account ---
  const deactivateBtn = document.getElementById('deactivateBtn');
  const deactivateConfirm = document.getElementById('deactivateConfirm');
  const deactivateConfirmBtn = document.getElementById('deactivateConfirmBtn');
  const deactivateCancelBtn = document.getElementById('deactivateCancelBtn');
  const deactivateErr = document.getElementById('deactivateErr');

  if (deactivateBtn && deactivateConfirm) {
    deactivateBtn.addEventListener('click', () => {
      deactivateConfirm.classList.remove('hidden');
      deactivateBtn.classList.add('hidden');
    });

    deactivateCancelBtn?.addEventListener('click', () => {
      deactivateConfirm.classList.add('hidden');
      deactivateBtn.classList.remove('hidden');
      deactivateErr.dataset.on = 'false';
    });

    deactivateConfirmBtn?.addEventListener('click', async () => {
      deactivateErr.dataset.on = 'false';
      setLoading(deactivateConfirmBtn, true);

      const pwd = document.getElementById('deactivate-pwd')?.value;
      const res = await authFetch('/cabinet/deactivate', { password: pwd });

      setLoading(deactivateConfirmBtn, false);

      if (res.ok) {
        window.location.href = res.data.redirect || '/';
      } else {
        const msg = res.errors?.password?.[0] || res.message || 'Error';
        deactivateErr.textContent = msg;
        deactivateErr.dataset.on = 'true';
      }
    });
  }
}
