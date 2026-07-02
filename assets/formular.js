document.addEventListener('DOMContentLoaded', function () {

  function toggleProviderEmail() {
    const cb   = document.getElementById('sendEmail');
    const inp  = document.getElementById('providerEmail');
    const req  = document.getElementById('providerReq');
    const hint = document.getElementById('providerHint');

    if (!cb || !inp || !req || !hint) return;

    if (cb.checked) {
      inp.required = true;
      req.style.display = 'inline';
      const pageType = document.documentElement.dataset.type;

hint.textContent =
  pageType === 'handy'
    ? 'Bitte E-Mail-Adresse des Anbieters eintragen (Pflichtfeld bei Versand).'
    : 'Bitte E-Mail-Adresse des Fitnessstudios eintragen (Pflichtfeld bei Versand).';

inp.focus();

    } else {
      inp.required = false;
      inp.value = '';
      req.style.display = 'none';
      hint.textContent = 'Optional: Du kannst die E-Mail-Adresse eintragen. Gesendet wird nur, wenn du oben aktivierst.';
    }
  }

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
    }
  }

  toggleProviderEmail();
  toggleTerminationDate();

  const cb = document.getElementById('sendEmail');
  if (cb) cb.addEventListener('change', toggleProviderEmail);

  const mode = document.getElementById('terminationMode');
  if (mode) mode.addEventListener('change', toggleTerminationDate);
});
