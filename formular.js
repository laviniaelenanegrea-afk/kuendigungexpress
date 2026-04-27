document.addEventListener('DOMContentLoaded', function () {

  // ===== 1. Provider-E-Mail required toggle + visual feedback =====
  function toggleProviderEmail() {
    const cb      = document.getElementById('sendEmail');
    const inp     = document.getElementById('providerEmail');
    const req     = document.getElementById('providerReq');
    const hint    = document.getElementById('providerHint');
    const section = document.getElementById('emailSection');

    if (!cb || !inp || !req || !hint) return;

    if (cb.checked) {
      inp.required = true;
      req.style.display = 'inline';
      if (section) section.classList.add('is-active');
      const pageType = document.documentElement.dataset.type;
      if (pageType === 'kfz') {
        hint.textContent = 'Pflichtfeld: E-Mail-Adresse des Versicherers — die Kündigung wird dorthin gesendet.';
      } else if (pageType === 'handy') {
        hint.textContent = 'Pflichtfeld: E-Mail-Adresse des Anbieters — die Kündigung wird dorthin gesendet.';
      } else {
        hint.textContent = 'Pflichtfeld: E-Mail-Adresse des Fitnessstudios — die Kündigung wird dorthin gesendet.';
      }
    } else {
      inp.required = false;
      inp.value = '';
      req.style.display = 'none';
      if (section) section.classList.remove('is-active');
      hint.textContent = 'Optional: Aktivieren Sie oben, um die Kündigung auch per E-Mail zu senden.';
      inp.classList.remove('ke-invalid');
    }
  }

  // ===== 2. Termination date required toggle =====
  function toggleTerminationDate() {
    const mode = document.getElementById('terminationMode');
    const wrap = document.getElementById('terminationDateWrap');
    const inp  = document.getElementById('terminationDate');

    if (!mode || !wrap || !inp) return;

    if (mode.value === 'specific_date') {
      wrap.style.display = 'block';
      inp.required = true;
    } else {
      wrap.style.display = 'none';
      inp.required = false;
      inp.value = '';
      inp.classList.remove('ke-invalid');
    }
  }

  toggleProviderEmail();
  toggleTerminationDate();

  const cb = document.getElementById('sendEmail');
  if (cb) cb.addEventListener('change', toggleProviderEmail);

  const mode = document.getElementById('terminationMode');
  if (mode) mode.addEventListener('change', toggleTerminationDate);

  // ===== 3. Submit-fail feedback — highlight field + scroll (no tooltip, no banner) =====
  const form = document.getElementById('keForm');

  if (form) {
    // Suprim tooltip-ul nativ prin setCustomValidity('') + preventDefault pe invalid
    // Astfel singurul feedback vizibil ramane culoarea campului + scroll
    form.addEventListener('invalid', function(e) {
      e.preventDefault(); // suprim tooltip-ul nativ al browserului
      const field = e.target;
      if (field && field.classList) {
        field.classList.add('ke-invalid');
      }
    }, true); // capture phase — catches all invalid events from children

    form.addEventListener('submit', function() {
      document.querySelectorAll('.ke-invalid').forEach(function(el) {
        el.classList.remove('ke-invalid');
      });
    });

    const submitBtn = form.querySelector('.btn-submit');
    if (submitBtn) {
      submitBtn.addEventListener('click', function() {
        setTimeout(function() {
          const firstInvalid = form.querySelector('.ke-invalid');
          if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(function(){ firstInvalid.focus({ preventScroll: true }); }, 400);
          }
        }, 50);
      });
    }

    // Clear invalid state when user corrects the field
    form.addEventListener('input', function(e) {
      if (e.target.classList && e.target.classList.contains('ke-invalid') && e.target.checkValidity()) {
        e.target.classList.remove('ke-invalid');
      }
    });
  }
});
