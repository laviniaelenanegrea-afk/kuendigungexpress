// generate.php success page — survey, tracking, share, email capture
// CSP-compliant: external file, no inline event handlers

(function() {
  // Provider context is exposed via data-attributes on body or a meta tag
  function getMeta(name) {
    var el = document.querySelector('meta[name="ke-' + name + '"]');
    return el ? el.getAttribute('content') : '';
  }
  var provider = getMeta('provider') || 'unknown';
  var type     = getMeta('type')     || 'unknown';

  // ===== Track download (PDF link click) =====
  var dlBtn = document.querySelector('.btn-download');
  if (dlBtn) {
    dlBtn.addEventListener('click', function() {
      if (typeof clarity === 'function') {
        clarity('event', 'pdf_downloaded');
        clarity('set', 'provider', provider);
        clarity('set', 'type', type);
      }
    });
  }

  // ===== Track affiliate clicks (any element with data-aff attribute) =====
  document.querySelectorAll('[data-aff]').forEach(function(a) {
    a.addEventListener('click', function() {
      if (typeof clarity === 'function') {
        clarity('event', 'affiliate_click');
        clarity('set', 'affiliate_target', a.getAttribute('data-aff'));
        clarity('set', 'provider', provider);
      }
    });
  });

  // ===== Survey =====
  document.querySelectorAll('.survey-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var answer = btn.getAttribute('data-answer');
      if (typeof clarity === 'function') {
        clarity('event', 'intent_survey');
        clarity('set', 'intent', answer);
        clarity('set', 'provider', provider);
      }
      var card = document.getElementById('intentSurvey');
      if (card) card.classList.add('answered');
      setTimeout(function() {
        var aff = document.querySelector('.affiliate-card');
        if (aff) aff.scrollIntoView({behavior: 'smooth', block: 'center'});
      }, 1400);
    });
  });

  // ===== Share button =====
  var shareBtn = document.querySelector('[data-action="share"]');
  if (shareBtn) {
    shareBtn.addEventListener('click', function() {
      var url = 'https://www.kuendigungexpress.de/';
      if (navigator.share) {
        navigator.share({
          title: 'KündigungExpress',
          text: 'Kostenlose Kündigungsschreiben als PDF – in 2 Minuten fertig.',
          url: url
        });
      } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
          var c = document.getElementById('shareCopied');
          if (c) {
            c.style.display = 'block';
            setTimeout(function() { c.style.display = 'none'; }, 2500);
          }
        });
      }
    });
  }

  // ===== Email capture =====
  var capForm = document.getElementById('captureForm');
  if (capForm) {
    capForm.addEventListener('submit', function(e) {
      e.preventDefault();
      var email = document.getElementById('captureEmail').value.trim();
      var consent = document.getElementById('captureConsent').checked;
      if (!email || !consent) return;
      fetch('/save_email.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'email=' + encodeURIComponent(email)
      }).then(function() {
        capForm.style.display = 'none';
        var consentLbl = document.querySelector('.capture-consent');
        if (consentLbl) consentLbl.style.display = 'none';
        var success = document.getElementById('captureSuccess');
        if (success) success.style.display = 'block';
      }).catch(function() {
        var success = document.getElementById('captureSuccess');
        if (success) {
          success.textContent = '✓ Eingetragen!';
          success.style.display = 'block';
        }
      });
    });
  }
})();
