/**
 * affiliate-tracking.js
 * Tracking pentru affiliate clicks fără să blocheze navigarea.
 * 
 * Cum se folosește pe pagini:
 *   <a href="https://www.check24.de/..." 
 *      class="aff-link" 
 *      data-partner="check24" 
 *      rel="sponsored noopener" 
 *      target="_blank">Tarife vergleichen</a>
 * 
 * Partner values acceptate:
 *   check24, tariffuxx, telekom_awin, plankpad_awin, tarifcheck24, crash
 * 
 * Include pe pagini: <script src="/affiliate-tracking.js" defer></script>
 */
(function() {
    'use strict';

    function trackClick(partner) {
        if (!partner) return;
        const data = new FormData();
        data.append('partner', partner);
        data.append('page', window.location.pathname);
        // sendBeacon nu blochează navigarea — perfect pentru click pe link
        if (navigator.sendBeacon) {
            navigator.sendBeacon('/affiliate-track.php', data);
        } else {
            // Fallback pentru browsere foarte vechi
            try {
                fetch('/affiliate-track.php', {
                    method: 'POST',
                    body: data,
                    keepalive: true
                });
            } catch (e) { /* silent fail — tracking nu trebuie să blocheze */ }
        }
    }

    function attachListeners() {
        document.querySelectorAll('a.aff-link[data-partner]').forEach(function(link) {
            if (link.dataset.affTracked) return; // evita dublu-attach
            link.dataset.affTracked = '1';
            link.addEventListener('click', function() {
                trackClick(this.dataset.partner);
            }, { passive: true });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachListeners);
    } else {
        attachListeners();
    }
})();
