# DSGVO Cookie Consent Implementation — KündigungExpress

**Erstellt: 14. Mai 2026**

## Was musst du machen — in dieser Reihenfolge

### Schritt 1: Upload neue Dateien

Upload în rădăcina site-ului (`/`):
- `cookie-consent.js` → noul cookie banner
- `consent-log.php` → backend pentru audit log
- `cleanup-old-data.php` → cron pentru ștergere date >90 zile
- `datenschutz.html` → înlocuiește versiunea existentă

### Schritt 2: Scoate scriptul Clarity vechi de pe TOATE paginile

Pe fiecare pagină HTML/PHP există acum:

```html
<!-- Microsoft Clarity -->
<script type="text/javascript">
(function(c,l,a,r,i,t,y){
  c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
  t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
  y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
})(window, document, "clarity", "script", "vz58ddxva5");
</script>
```

**ȘTERGE acest bloc complet** de pe toate paginile. În locul lui pune:

```html
<script src="/cookie-consent.js" defer></script>
```

Acest script:
- Detectează automat utilizatorii EWR (Germania + UE + UK + Elveția) prin timezone
- Pentru non-EWR: încarcă Clarity direct (regulamentul UE nu se aplică)
- Pentru EWR: afișează banner; Clarity se încarcă DOAR dacă utilizatorul acceptă

**Bulk replace cu Claude Code** (recomandat pentru cele 100+ pagini):
```bash
# În folderul site-ului, pentru fiecare pagină HTML:
# Caută blocul Clarity și înlocuiește-l cu noua referință
```

Sau manual la fiecare folder: Handy/, Fitness/, Kfz/, Hub Pages/, SEO Pages/, Gemischt/

### Schritt 3: Adaugă "Cookie-Einstellungen" în footer

Pe TOATE paginile, în footer-ul existent, adaugă link-ul de revocare. Caută în footer:

```html
<p class="brand-disclaimer">© 2026 KündigungExpress · <a href="/impressum.html"...>Impressum</a> · <a href="/datenschutz.html"...>Datenschutz</a> · <a href="/hilfe.html"...>Hilfe</a></p>
```

Schimbă în:

```html
<p class="brand-disclaimer">© 2026 KündigungExpress · <a href="/impressum.html"...>Impressum</a> · <a href="/datenschutz.html"...>Datenschutz</a> · <a href="/hilfe.html"...>Hilfe</a> · <a href="#" onclick="return keResetConsent(event)" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Cookie-Einstellungen</a></p>
```

`keResetConsent()` este expus global de `cookie-consent.js` — funcționează pe orice pagină.

### Schritt 4: Asigură-te că AWIN-linkurile sunt marcate

Cookie-consent.js detectează automat linkurile către `awin1.com` și `awin.com` și adaugă `&cons=0` sau `&cons=1`. Verifică pe paginile cu AWIN (Telekom, Plankpad) că linkurile pointează către astea domenii. Dacă nu, trebuie să le adaptezi.

**Pentru Check24, Tariffuxx, Tarifcheck24, CRASH**: cookie-consent.js NU le modifică. Acești parteneri folosesc sisteme proprii. Aceste sisteme presupun by default că ai obținut consent — deci pe pagina ta, banner-ul vede de asta.

### Schritt 5: Activează AVV cu Microsoft

1. Intră pe https://clarity.microsoft.com
2. Settings → Data Settings → verifică că AVV (Auftragsverarbeitungsvertrag) e acceptat
3. Dacă nu, accept-l. Asta e cerință legală (Art. 28 DSGVO).

### Schritt 6: Configurează Cron Job pentru cleanup

Două opțiuni:

**A. Ionos Cron Job (recomandat):**
- Login Ionos Control Panel
- Hosting → Cronjobs / Geplante Aufgaben
- Adaugă: zilnic la 03:00, comandă: `/usr/bin/php /kunden/homepages/XX/XXXXXX/htdocs/cleanup-old-data.php`
- (calea exactă variază — ia-o din Ionos)

**B. Manual via wget (fallback):**
- Modifică `cleanup-old-data.php` linia: `$TOKEN = 'change-this-to-...'` → pune un token random lung
- Apoi rulează săptămânal: `https://www.kuendigungexpress.de/cleanup-old-data.php?token=YOUR_TOKEN`

### Schritt 7: Testare după upload

Test pe Chrome incognito:
1. Deschide `https://www.kuendigungexpress.de/` → banner trebuie să apară jos
2. Apăsă "Ablehnen" → banner dispare, deschide DevTools → Application → Cookies → NU trebuie să existe `_clck`, `_clsk`, `MUID`
3. Click pe footer "Cookie-Einstellungen" → reload + banner apare din nou
4. Apăsă "Akzeptieren" → banner dispare, Cookies → APAR `_clck`, `_clsk` (după 5-10 secunde)
5. Click pe un link Check24 / Telekom → linkul ar trebui să aibă `&cons=1` în URL (pentru AWIN)

### Schritt 8: După deployment

Verifică în primele zile:
- `/_data/consent_log.txt` — primii utilizatori care decid (accepted vs rejected count)
- `/_data/cleanup_log.txt` — că cron-ul rulează zilnic

---

## Ce am acoperit juridic

✓ Microsoft Clarity acum cu Einwilligung (Art. 6 Abs. 1 lit. a) — corect  
✓ Datentransfer USA menționat explicit + EU-US Data Privacy Framework  
✓ Toate cookies Clarity listate (_clck, _clsk, MUID, CLID, MR, ANONCHK)  
✓ Speicherdauer PDF-uri: 90 zile + cron de ștergere  
✓ Toate drepturile Art. 15-22 DSGVO listate  
✓ Aufsichtsbehörde Hessen menționat  
✓ Trustpilot menționat cu rechtsgrundlage  
✓ Server-Logs (IONOS) menționate cu rechtsgrundlage + Speicherdauer  
✓ Toți partenerii afiliați cu adresa juridică + link la propria privacy  
✓ AWIN Consent Signal implementat (cons=0/1)  
✓ Toate cookies non-essential gated prin banner  
✓ Posibilitate revocare (footer link)  

## Ce NU am acoperit (te las să decizi)

- **AVV cu Trustpilot** — dacă ai cont activ, verifică în setări. Trustpilot face DPA automat la onboarding, dar verifică.
- **Verarbeitungsverzeichnis intern** (Art. 30 DSGVO) — document intern obligatoriu. Pentru Einzelunternehmer ești scutită dacă ai <250 angajați ȘI prelucrarea nu e de rutină. Tu ești sub prag, dar e recomandat să-l ai oricum.
- **Datenschutzbeauftragter** (DPO) — NU e obligatoriu pentru tine (Einzelunternehmer, <20 persoane care prelucrează date regulat).

---

## Cost și complexitate

| Aspect | Status |
|---|---|
| Cost recurring | 0 € |
| Implementation timp | ~30-45 minute upload + bulk edit |
| Maintenance | doar la schimbare partner afiliat |
| Audit dovezi | `/_data/consent_log.txt` + `/_data/cleanup_log.txt` |
| Risc juridic | redus drastic de la "kritisch" la "mic" |
