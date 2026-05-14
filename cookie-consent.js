/**
 * cookie-banner-nou.js — DSGVO Cookie Consent (Mobile Optimized & Cache-Busted)
 */
(function() {
    'use strict';

    var CONSENT_KEY = 'ke_consent_v1';
    var CLARITY_ID = 'vz58ddxva5';
    
    var EWR_TIMEZONES = [
        'Europe/Berlin', 'Europe/Vienna', 'Europe/Zurich', 'Europe/Paris',
        'Europe/Madrid', 'Europe/Rome', 'Europe/Amsterdam', 'Europe/Brussels',
        'Europe/Lisbon', 'Europe/Dublin', 'Europe/London', 'Europe/Athens',
        'Europe/Bucharest', 'Europe/Sofia', 'Europe/Warsaw', 'Europe/Prague',
        'Europe/Budapest', 'Europe/Copenhagen', 'Europe/Stockholm', 'Europe/Oslo',
        'Europe/Helsinki', 'Europe/Tallinn', 'Europe/Riga', 'Europe/Vilnius',
        'Europe/Luxembourg', 'Europe/Malta', 'Europe/Nicosia', 'Europe/Ljubljana',
        'Europe/Bratislava', 'Europe/Zagreb', 'Europe/Reykjavik', 'Europe/Vaduz',
        'Atlantic/Madeira', 'Atlantic/Azores', 'Atlantic/Canary'
    ];

    function isEWR() {
        try {
            var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            return EWR_TIMEZONES.indexOf(tz) !== -1;
        } catch(e) {
            return true; 
        }
    }

    function getConsent() {
        try { return localStorage.getItem(CONSENT_KEY); } catch(e) { return null; }
    }

    function setConsent(value) {
        try { localStorage.setItem(CONSENT_KEY, value + '|' + Date.now()); } catch(e) {}
        try {
            var data = new FormData();
            data.append('decision', value);
            data.append('page', location.pathname);
            if (navigator.sendBeacon) navigator.sendBeacon('/consent-log.php', data);
        } catch(e) {}
    }

    function hasAccepted() {
        var v = getConsent(); return v && v.indexOf('accepted') === 0;
    }

    function hasRejected() {
        var v = getConsent(); return v && v.indexOf('rejected') === 0;
    }

    function loadClarity() {
        if (window._claritySent) return;
        window._claritySent = true;
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", CLARITY_ID);
    }

    function updateAwinLinks() {
        var consent = hasAccepted() ? '1' : '0';
        var links = document.querySelectorAll('a[href*="awin1.com"], a[href*="awin.com"]');
        links.forEach(function(link) {
            var url = link.href;
            url = url.replace(/[?&]cons=[01]/, '');
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'cons=' + consent;
            link.href = url;
        });
    }

    function applyConsent() {
        if (hasAccepted()) loadClarity();
        updateAwinLinks();
    }

    function showBanner() {
        if (document.getElementById('ke-cookie-banner-wrapper')) return;

        var html =
            '<div id="ke-cookie-banner-wrapper">' +
            '<style>' +
            '  #ke-cookie-banner { position: fixed !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 2147483647 !important; background: #FFFFFF !important; border-top: 1px solid #E2E8F0 !important; box-shadow: 0 -8px 24px rgba(15,23,42,0.08) !important; padding: 16px 20px !important; box-sizing: border-box !important; font-family: Arial, sans-serif !important; margin: 0 !important; }' +
            '  #ke-cc-inner { max-width: 900px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; }' +
            '  #ke-cc-text { flex: 1; min-width: 240px; font-size: 13px; color: #475569; line-height: 1.5; }' +
            '  #ke-cc-title { color: #0F172A; display: block; margin-bottom: 4px; font-size: 14px; }' +
            '  #ke-cc-btns { display: flex; gap: 10px; flex-shrink: 0; }' +
            '  .ke-cc-btn { padding: 12px 22px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; font-family: inherit; transition: opacity 0.2s ease; text-align: center; }' +
            '  .ke-cc-btn:hover { opacity: 0.85; }' +
            '  #ke-cc-reject { background: #F1F5F9; color: #0F172A; border: 1px solid #E2E8F0; }' +
            '  #ke-cc-accept { background: #16A34A; color: #fff; border: none; }' +
            '  @media (max-width: 640px) {' +
            '    #ke-cookie-banner { padding: 16px !important; }' +
            '    #ke-cc-inner { flex-direction: column; align-items: stretch; gap: 16px; }' +
            '    #ke-cc-text { min-width: 100%; text-align: left; }' +
            '    #ke-cc-btns { width: 100%; flex-direction: row; justify-content: center; }' +
            '    .ke-cc-btn { flex: 1; padding: 12px 10px; font-size: 14px; }' +
            '  }' +
            '</style>' +
            '<div id="ke-cookie-banner" role="dialog" aria-labelledby="ke-cc-title" aria-describedby="ke-cc-desc">' +
              '<div id="ke-cc-inner">' +
                '<div id="ke-cc-text">' +
                  '<strong id="ke-cc-title">Cookies & Datenschutz</strong>' +
                  '<span id="ke-cc-desc">Wir nutzen Microsoft Clarity zur Verbesserung der Website und setzen Tracking-Cookies bei Klick auf Partnerlinks. Beides ist optional. ' +
                  '<a href="/datenschutz.html" style="color:#16A34A;font-weight:600;text-decoration:underline;">Mehr erfahren</a></span>' +
                '</div>' +
                '<div id="ke-cc-btns">' +
                  '<button id="ke-cc-reject" class="ke-cc-btn" type="button">Ablehnen</button>' +
                  '<button id="ke-cc-accept" class="ke-cc-btn" type="button">Akzeptieren</button>' +
                '</div>' +
              '</div>' +
            '</div>' +
            '</div>';

        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        document.body.appendChild(wrap.firstChild);

        document.getElementById('ke-cc-accept').addEventListener('click', function() {
            setConsent('accepted'); hideBanner(); applyConsent();
        });
        document.getElementById('ke-cc-reject').addEventListener('click', function() {
            setConsent('rejected'); hideBanner(); updateAwinLinks();
        });
    }

    function hideBanner() {
        var b = document.getElementById('ke-cookie-banner-wrapper');
        if (b) b.parentNode.removeChild(b);
    }

    window.keResetConsent = function(e) {
        if (e) e.preventDefault();
        try { localStorage.removeItem(CONSENT_KEY); } catch(e) {}
        location.reload();
        return false;
    };

    function init() {
        if (!isEWR()) { loadClarity(); updateAwinLinks(); return; }
        if (hasAccepted() || hasRejected()) { applyConsent(); return; }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', showBanner);
        } else {
            showBanner();
        }
    }

    init();
})();