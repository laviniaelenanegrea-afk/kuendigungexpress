/**
 * AI Visit Tracker v2 — KündigungExpress
 * Modificări față de v1:
 *  - Scos duckduckgo.com (nu e AI assistant, era fals pozitiv)
 *  - Adăugat bing.com (Copilot via Bing Chat trimite cu acest referrer)
 *  - Adăugat parametru `src` pentru a distinge utm_source vs referrer
 *  - Limitare cunoscută: Claude.ai cu rel="noreferrer" și ChatGPT Atlas
 *    rămân invizibili — nu există fix client-side pentru ele.
 */
(function() {
  try {
    var ref = (document.referrer || '').toLowerCase();
    var aiHosts = [
      'chatgpt.com',
      'chat.openai.com',
      'claude.ai',
      'copilot.microsoft.com',
      'copilot.com',
      'bing.com',
      'perplexity.ai',
      'gemini.google.com',
      'bard.google.com',
      'you.com',
      'phind.com'
    ];

    var matchedHost = '';
    for (var i = 0; i < aiHosts.length; i++) {
      if (ref.indexOf(aiHosts[i]) !== -1) {
        matchedHost = aiHosts[i];
        break;
      }
    }

    var urlParams = new URLSearchParams(window.location.search);
    var utmSource = (urlParams.get('utm_source') || '').toLowerCase();
    var utmAI = /^(chatgpt|claude|copilot|perplexity|gemini|chat\.openai|bard)/i.test(utmSource);

    if (matchedHost || utmAI) {
      var img = new Image();
      var params = new URLSearchParams({
        ai_visit: '1',
        page: window.location.pathname,
        src: utmAI ? ('utm:' + utmSource) : ('ref:' + matchedHost)
      });
      img.src = '/event.php?' + params.toString();
    }
  } catch (e) {
    // Silent fail
  }
})();
