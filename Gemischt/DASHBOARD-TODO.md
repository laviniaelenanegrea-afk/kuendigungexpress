# Dashboard Enhancement — TODO Pentru Viitor

## 1. Adaugă 4 câmpuri în generate.php (când scrii JSON)

Costă 0 timp dar deblochează analytics serios. Localizează în generate.php locul unde se scrie JSON-ul (probabil `file_put_contents($dataDir.'/'.$id.'.json', json_encode($data))`) și adaugă în array-ul `$data`:

```php
$data = [
    'createdAt' => time(),
    'type' => $type,
    'provider' => $provider,
    'emailSent' => $emailSent,
    'hasContract' => $hasContract,
    'hasEmail' => $hasEmail,
    // NOI — adaugă acestea:
    'userAgent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
    'referer'   => substr($_SERVER['HTTP_REFERER'] ?? '', 0, 200),
    'sourcePage' => substr($_SERVER['HTTP_REFERER'] ?? '', 0, 100), // de pe ce pagina vine
    'utmSource' => substr($_GET['utm_source'] ?? $_POST['utm_source'] ?? '', 0, 50),
];
```

**ATENȚIE DSGVO**: User Agent + Referer sunt date personale. Trebuie:
1. Menționate în Datenschutz (deja folosești Clarity, similar)
2. Nu salva IP-uri sau date identificatoare
3. Șterge JSON-urile vechi după X luni (cron job lunar)

## 2. Activează Affiliate Click Tracking

**Pași:**

1. Upload `affiliate-track.php` în rădăcina site-ului
2. Upload `affiliate-tracking.js` în rădăcina site-ului
3. Pe toate paginile cu butoane afiliate, adaugă în `<head>` sau înainte de `</body>`:
   ```html
   <script src="/affiliate-tracking.js" defer></script>
   ```
4. Modifică linkurile afiliate adăugând `class="aff-link"` + `data-partner="..."`:
   ```html
   <!-- Înainte: -->
   <a href="https://www.check24.de/?pid=1169420" rel="sponsored">Tarife</a>

   <!-- După: -->
   <a href="https://www.check24.de/?pid=1169420" 
      class="aff-link" 
      data-partner="check24" 
      rel="sponsored noopener" 
      target="_blank">Tarife</a>
   ```

**Partner values disponibile:**
- `check24`
- `tariffuxx`
- `telekom_awin`
- `plankpad_awin`
- `tarifcheck24`
- `crash`

5. Verifică log-ul după primele click-uri:
   ```bash
   tail -f /_data/affiliate_clicks.log
   ```

## 3. Dashboard arată automat dacă tracking-ul e activ

Dacă log-ul există → cifrele de affiliate clicks apar peste tot.
Dacă log-ul nu există → vezi warning galben + "—" în loc de cifre.

## 4. Bulk update pe pagini cu Claude Code

Pentru a adăuga `class="aff-link" data-partner="..."` pe toate paginile:

```bash
# Pe paginile Check24 (hub Handy):
grep -rl 'check24' . --include='*.html' | while read f; do
  # Modifică manual sau cu sed, depinde de structura exactă a linkurilor
done
```

Recomand să faci asta cu Claude Code pe lot mare, nu manual.
