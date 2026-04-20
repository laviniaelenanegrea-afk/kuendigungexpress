document.getElementById('captureForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var email   = document.getElementById('captureEmail').value.trim();
  var consent = document.getElementById('captureConsent').checked;
  if (!email || !consent) return;
  fetch('/save_email.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'email=' + encodeURIComponent(email)
  }).then(function(response) {
    if (!response.ok) { throw new Error('server error'); }
    document.getElementById('captureForm').style.display = 'none';
    document.querySelector('.email-capture-consent').style.display = 'none';
    document.getElementById('captureSuccess').style.display = 'block';
  }).catch(function() {
    var el = document.getElementById('captureSuccess');
    el.textContent = 'Fehler – bitte versuche es erneut.';
    el.style.color = '#DC2626';
    el.style.display = 'block';
  });
});