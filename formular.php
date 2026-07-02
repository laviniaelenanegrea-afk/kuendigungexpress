<?php
declare(strict_types=1);

$type = $_GET['type'] ?? ($_POST['type'] ?? 'fitness');
$type = in_array($type, ['fitness', 'handy', 'kfz', 'bank'], true) ? $type : 'fitness';
$anbieter = isset($_GET['anbieter']) ? trim($_GET['anbieter']) : (isset($_POST['anbieter']) ? trim($_POST['anbieter']) : '');

// Pre-fill din POST (back from preview) SAU din Token JSON (anulare plată Stripe)
$prefillFromPost = [];

if (isset($_POST['back_from_preview'])) {
    $prefillFromPost = [
        'firstName' => trim((string)($_POST['firstName'] ?? '')),
        'lastName' => trim((string)($_POST['lastName'] ?? '')),
        'street' => trim((string)($_POST['street'] ?? '')),
        'zip' => trim((string)($_POST['zip'] ?? '')),
        'city' => trim((string)($_POST['city'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'studioStreet' => trim((string)($_POST['studioStreet'] ?? '')),
        'studioZip' => trim((string)($_POST['studioZip'] ?? '')),
        'studioCity' => trim((string)($_POST['studioCity'] ?? '')),
        'terminationMode' => trim((string)($_POST['terminationMode'] ?? '')),
        'terminationDate' => trim((string)($_POST['terminationDate'] ?? '')),
        'contractNo' => trim((string)($_POST['contractNo'] ?? '')),
        'plate' => trim((string)($_POST['plate'] ?? '')),
        'ziel_iban' => trim((string)($_POST['ziel_iban'] ?? '')),
        'sendEmail' => trim((string)($_POST['sendEmail'] ?? '')),
        'providerEmail' => trim((string)($_POST['providerEmail'] ?? '')),
        'trustpilotConsent' => trim((string)($_POST['trustpilotConsent'] ?? '')),
    ];
} elseif (isset($_GET['cancelled']) && $_GET['cancelled'] === '1' && !empty($_GET['t']) && preg_match('/^[a-f0-9]{32}$/', $_GET['t'])) {
    // Dacă utilizatorul anulează din Stripe, citim token-ul și readucem datele din fișierul temporar
    $tokenFile = __DIR__ . '/_data/' . $_GET['t'] . '.json';
    if (file_exists($tokenFile)) {
        $savedData = json_decode(file_get_contents($tokenFile), true);
        if (is_array($savedData)) {
            $prefillFromPost = [
                'firstName' => trim((string)($savedData['firstName'] ?? '')),
                'lastName' => trim((string)($savedData['lastName'] ?? '')),
                'street' => trim((string)($savedData['street'] ?? '')),
                'zip' => trim((string)($savedData['zip'] ?? '')),
                'city' => trim((string)($savedData['city'] ?? '')),
                'email' => trim((string)($savedData['email'] ?? '')),
                'studioStreet' => trim((string)($savedData['studioStreet'] ?? '')),
                'studioZip' => trim((string)($savedData['studioZip'] ?? '')),
                'studioCity' => trim((string)($savedData['studioCity'] ?? '')),
                'terminationMode' => trim((string)($savedData['terminationMode'] ?? '')),
                'terminationDate' => trim((string)($savedData['terminationDate'] ?? '')),
                'contractNo' => trim((string)($savedData['contractNo'] ?? '')),
                'plate' => trim((string)($savedData['plate'] ?? '')),
                'ziel_iban' => trim((string)($savedData['ziel_iban'] ?? '')),
                'sendEmail' => trim((string)($savedData['sendEmail'] ?? '')),
                'providerEmail' => trim((string)($savedData['providerEmail'] ?? '')),
                'trustpilotConsent' => trim((string)($savedData['trustpilotConsent'] ?? '')),
            ];
            if (!empty($savedData['anbieter'])) {
                $anbieter = trim($savedData['anbieter']);
            }
        }
    }
}

$isRSG = in_array(strtolower($anbieter), ['mcfit', 'john reed', 'john-reed', 'johnreed', 'high five'], true);

/* Provider address pre-fill mapping */
$providerAddresses = [
    // KFZ-Versicherung
    'allianz versicherungs-ag' => ['Postanschrift','10900','Berlin'],
    'huk-coburg' => ['Postanschrift','96444','Coburg'],
    'huk24' => ['HUK-COBURG-Platz 1','96444','Coburg'],
    'ergo versicherung ag' => ['ERGO-Platz 1','40477','Düsseldorf'],
    'axa versicherung ag' => ['Colonia-Allee 10-20','51067','Köln'],
    'devk allgemeine versicherungs-ag' => ['Riehler Str. 190','50735','Köln'],
    'adac autoversicherung ag' => ['Hansastraße 19','80686','München'],
    'generali deutschland versicherung ag' => ['Adenauerring 7','81737','München'],
    'hdi versicherung ag' => ['HDI-Platz 1','30659','Hannover'],
    'r+v allgemeine versicherung ag' => ['Raiffeisenplatz 1','65189','Wiesbaden'],
    'cosmosdirekt' => ['Halbergstraße 50-60','66121','Saarbrücken'],
    'lvm versicherung' => ['Kolde-Ring 21','48126','Münster'],
    'vhv allgemeine versicherung ag' => ['VHV-Platz 1','30177','Hannover'],
    'württembergische versicherung ag' => ['W&W-Platz 1','70806','Kornwestheim'],
    'da deutsche allgemeine versicherung ag' => ['Platz der Einheit 2','60327','Frankfurt am Main'],
    'verti versicherung ag' => ['Rheinstraße 7A ','14513','Teltow'],
    'nürnberger allgemeine versicherungs-ag' => ['Ostendstraße 100','90334','Nürnberg'],
    'gothaer allgemeine versicherung ag' => ['Arnoldiplatz 1','50969','Köln'],
    'zurich gruppe deutschland' => ['Platz der Einheit 2','60327','Frankfurt am Main'],
    'provinzial versicherung ag' => ['Provinzialplatz 1','40591','Düsseldorf'],
    'signal iduna allgemeine versicherung ag' => ['Joseph-Scherer-Straße 3','44139','Dortmund'],
    'sv sparkassenversicherung holding ag' => ['Löwentorstraße 65','70376','Stuttgart'],
    'alte leipziger versicherung ag' => ['Alte Leipziger-Platz 1','61406','Oberursel'],
    'wgv-versicherung ag' => ['Tübinger Str. 55','70164','Stuttgart'],
    'itzehoer versicherung/brandgilde von 1691 versicherungsverein ag' => ['Itzehoer Platz','25521','Itzehoe'],
    'öffentliche versicherung braunschweig' => ['Theodor-Heuss-Str. 10','38122','Braunschweig'],
'continentale sachversicherung ag' => ['Continentale-Allee 1','44269','Dortmund'],
'kravag-versicherungen' => ['Heidenkampsweg 102', '20097', 'Hamburg'],
    'kravag' => ['Heidenkampsweg 102', '20097', 'Hamburg'],
'europa versicherung ag' => ['Piusstraße 137', '50931', 'Köln'],
'allianz direct versicherungs-ag' => ['Königinstraße 28', '80802', 'München'],
'admiraldirekt' => ['Itzehoer Platz', '25521', 'Itzehoe'],
'bavariadirekt versicherung ag' => ['Am Karlsbad 4-5', '10785', 'Berlin'],
'neodigital versicherung ag' => ['Heinz-Kettler-Str. 1', '66386', 'St. Ingbert'],
'barmenia allgemeine versicherungs-ag' => ['Barmenia-Allee 1', '42119', 'Wuppertal'],
'baloise sachversicherung ag' => ['Basler Straße 4', '61352', 'Bad Homburg v. d. Höhe'],
'vgh versicherungen' => ['Schiffgraben 4', '30159', 'Hannover'],
    
    // Mobilfunk
    'vodafone' => ['Vodafone GmbH (Kundenbetreuung)', '40875', 'Ratingen'],
    'telekom' => ['Telekom Deutschland GmbH (Kundenservice)', '53171', 'Bonn'],
    'o2' => ['Telefónica Germany GmbH & Co. OHG, Kundenbetreuung','90345','Nürnberg'],
    '1&1 telecom gmbh' => ['Elgendorfer Straße 57','56410','Montabaur'],
    'congstar' => ['congstar GmbH, Postfach 1165', '53001', 'Bonn'],
    'freenet' => ['freenet DLS GmbH, Postfach 90 02 65', '99105', 'Erfurt'],
    'otelo' => ['Vodafone GmbH (otelo Kundenbetreuung), Postfach 10 02 54', '33502', 'Bielefeld'],
    'mobilcom-debitel' => ['freenet DLS GmbH, Postfach 90 02 65', '99105', 'Erfurt'],
    'drillisch online gmbh' => ['Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'winsim' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'smartmobil' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'simplytel' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'premiumsim' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'maxxim' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'sim.de' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'yourfone' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'deutschlandsim' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'discotel' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'aldi talk' => ['Telefónica Germany GmbH & Co. OHG (ALDI TALK), Postfach 730301', '90280', 'Nürnberg'],
    'ortel mobile' => ['Telefónica Germany GmbH & Co. OHG (Ortel Mobile), Postfach 730301', '90280', 'Nürnberg'],
    'ay yildiz' => ['Telefónica Germany GmbH & Co. OHG (Ay Yildiz), Postfach 730301', '90280', 'Nürnberg'],
    'norma connect' => ['Telekom Deutschland Multibrand GmbH, Postfach 1511','48105','Münster'],
    'tchibo mobil' => ['Tchibo Mobilfunk GmbH & Co. KG, Kundenservice, Postfach 601402','22214','Hamburg'],
    'super select' => ['Telefónica Germany GmbH & Co. OHG (Super Select), Postfach 730301','90280','Nürnberg'],
    'blau' => ['Telefónica Germany GmbH & Co. OHG (Blau)','90345','Nürnberg'],
    'fonic' => ['Telefónica Germany GmbH & Co. OHG (FONIC)','90345','Nürnberg'],
    'high mobile' => ['mobilezone GmbH - Kundenbetreuung, Porschestraße 7','44809','Bochum'],
    'klarmobil' => ['klarmobil GmbH - Kundenservice','25436','Uetersen'],
    'kaufland mobil' => ['Telekom Deutschland Multibrand GmbH, Postfach 1511','48105','Münster'],
    'lidl connect' => ['Vodafone GmbH - Lidl Connect Kundenbetreuung, Postfach 100254','33502','Bielefeld'],
    'lebara' => ['Lebara Germany Limited, Zollhof 17','40213','Düsseldorf'],
    'lycamobile' => ['Mainzer Landstraße 349', '60326', 'Frankfurt am Main'],
    'edeka mobil' => ['Vodafone GmbH (otelo), Kundenbetreuung', '40875', 'Ratingen'],
    'edeka smart' => ['Telekom Deutschland Multibrand GmbH, Landgrabenweg 151', '53227', 'Bonn'],
    'ja! mobil' => ['congstar Services GmbH (ja! mobil), Weinsbergstraße 70','50823','Köln'],
    'fraenk' => ['congstar Services GmbH (fraenk), Weinsbergstraße 70','50823','Köln'],
'drillisch online gmbh - handyvertrag.de' => ['Wilhelm-Röntgen-Str. 1-5','63477','Maintal'],
'vodafone gmbh - simon mobile' => ['Ferdinand-Braun-Platz 1','40549','Düsseldorf'],
'web.de mobilfunk' => ['1&1 Telecommunication SE, Elgendorfer Str. 57', '56410', 'Montabaur'],
'gmx mobilfunk' => ['1&1 Telecommunication SE, Elgendorfer Str. 57', '56410', 'Montabaur'],
'freenet funk' => ['freenet DLS GmbH, Postfach 90 02 65', '99105', 'Erfurt'],
'freenet flex' => ['freenet DLS GmbH, Postfach 90 02 65', '99105', 'Erfurt'],
      
    // Fitnessstudio - Nur zentrale Ketten
    'mcfit' => ['RSG Group GmbH, Tannenberg 4', '96132', 'Schlüsselfeld'],
    'fitx' => ['FitX Deutschland GmbH, Essener Straße 4-5', '45141', 'Essen'],
    'fitness first' => ['Fitness First Germany GmbH, Hanauer Landstraße 148a', '60314', 'Frankfurt am Main'],
    'holmes place' => ['Holmes Place Health Clubs GmbH, Charlottenstraße 65', '10117', 'Berlin'],
    'john reed' => ['RSG Group GmbH, Tannenberg 4', '96132', 'Schlüsselfeld'],
    'pfitzenmeier' => ['Unternehmensgruppe Pfitzenmeier, Essener Straße 12', '68723', 'Schwetzingen'],
    'body + soul' => ['body + soul Group, Wilhelm-Keim-Straße 25', '82031', 'Grünwald'],
    'high five' => ['RSG Group GmbH, Tannenberg 4', '96132', 'Schlüsselfeld'],
    'fitness express' => ['Fitness Express, Hanns-Martin-Schleyer-Str. 9', '71063', 'Sindelfingen'],
    'urban sports club' => ['Urban Sports GmbH, Michaelkirchstraße 20', '10179', 'Berlin'],
'xtrafit' => ['XTRAFIT Mitgliederservice, Postfach 320135', '50795', 'Köln'],
'prime time fitness gmbh' => ['Clemensstr. 9', '60487', 'Frankfurt am Main'],
'm-fitnesscenter olympia' => ['Coubertinplatz 1', '80809', 'München'],
'superfit' => ['EAST BANK CLUB the fitness factory gmbh, Postfach 410108', '12111', 'Berlin'],
'american fitness' => ['Hermannplatz 10', '10967', 'Berlin'],
'meridian spa & fitness deutschland gmbh' => ['Wandsbeker Zollstr. 87-89', '22041', 'Hamburg'],
'david lloyd clubs deutschland gmbh' => ['Niederstedter Weg 12', '61348', 'Bad Homburg'],
'elbgym gmbh bei lifefit group' => ['Hanauer Landstraße 148a', '60314', 'Frankfurt am Main'],
'just fit verwaltungs gmbh & co. kg' => ['Ernst-Heinrich-Geist-Str. 3-5', '50226', 'Frechen'],
    // Banken / Girokonto (Pilot)
    'ing' => ['ING-DiBa AG', '60628', 'Frankfurt am Main'],
    'dkb' => ['Deutsche Kreditbank AG, Bereich Privatkunden, Postfach 11 02 68', '10832', 'Berlin'],
    'n26' => ['N26 Bank GmbH, Klosterstraße 62', '10179', 'Berlin'],
];

/* Provider Email pre-fill mapping */
$providerEmails = [
    // KFZ-Versicherung
    'allianz versicherungs-ag' => 'sachversicherung@allianz.de',
    'huk-coburg' => 'info@huk-coburg.de',
    'huk24' => 'info@huk24.de',
    'ergo versicherung ag' => 'service@ergo.de',
    'axa versicherung ag' => 'service@axa.de',
    'devk allgemeine versicherungs-ag' => 'info@devk.de',
    'adac autoversicherung ag' => 'adac@adac.de',
    'generali deutschland versicherung ag' => 'service@generali.de',
    'hdi versicherung ag' => 'info@hdi.de',
    'r+v allgemeine versicherung ag' => 'ruv@ruv.de',
    'cosmosdirekt' => 'info@cosmosdirekt.de',
    'lvm versicherung' => 'info@lvm.de',
    'vhv allgemeine versicherung ag' => 'info@vhv.de',
    'verti versicherung ag' => 'vertrag@verti.de',
    'gothaer allgemeine versicherung ag' => 'info@gothaer.de',
    'zurich gruppe deutschland' => 'vertrag@zurich.com',
    'signal iduna allgemeine versicherung ag' => 'info@signal-iduna.de',
    'württembergische versicherung ag' => 'info@wuerttembergische.de',
'da deutsche allgemeine versicherung ag' => 'vertragsservice@da-direkt.de',
    'nürnberger allgemeine versicherungs-ag' => 'info@nuernberger-automobil.de',
    'provinzial versicherung ag' => 'service@provinzial.com',
    'sv sparkassenversicherung holding ag' => 'service@sv.de',
    'alte leipziger versicherung ag' => 'sach@alte-leipziger.de',
    'wgv-versicherung ag' => 'kundenservice@wgv.de',
    'itzehoer versicherung/brandgilde von 1691 versicherungsverein ag' => 'info@itzehoer.de',
    'öffentliche versicherung braunschweig' => 'service@oeffentliche.de',
'continentale sachversicherung ag' => 'info@continentale.de',
'kravag-versicherungen' => 'info@kravag.de',
    'kravag' => 'info@kravag.de',
'europa versicherung ag' => 'info@europa.de',
'allianz direct versicherungs-ag' => 'service@allianzdirect.de',
'admiraldirekt' => 'info@admiraldirekt.de',
'bavariadirekt versicherung ag' => 'info@bavariadirekt.de',
'neodigital versicherung ag' => 'info@neodigital.de',
'barmenia allgemeine versicherungs-ag' => 'info@barmenia.de',
'baloise sachversicherung ag' => 'info@baloise.de',
'vgh versicherungen' => 'service@vgh.de',
    
    // Mobilfunk
    'vodafone' => 'kundenservice@vodafone.com',
    'telekom' => 'kundenservice@telekom.de',
    'o2' => 'service@o2.de',
    '1&1 telecom gmbh' => 'kuendigung@1und1.de',
    'congstar' => 'kundenservice@congstar.de',
    'freenet' => 'info@freenet-mobilfunk.de',
    'otelo' => 'kontakt@otelo.de',
    'mobilcom-debitel' => 'info@freenet-mobilfunk.de',
    'drillisch online gmbh' => 'kontakt@drillisch-online.de',
    'winsim' => 'kontakt@winsim.de',
    'smartmobil' => 'kontakt@smartmobil.de',
    'simplytel' => 'kontakt@simplytel.de',
    'premiumsim' => 'kontakt@premiumsim.de',
    'maxxim' => 'kontakt@maxxim.de',
    'sim.de' => 'kontakt@sim.de',
    'yourfone' => 'kontakt@yourfone.de',
    'deutschlandsim' => 'kontakt@drillisch-online.de',
    'discotel' => 'kontakt@drillisch-online.de',
    'ortel mobile' => 'info@ortelmobile.de',
    'ay yildiz' => 'service@ayyildiz.de',
    'tchibo mobil' => 'info@tchibo-mobil.de',
    'blau' => 'service@blau.de',
    'fonic' => 'service@fonic.de',
    'klarmobil' => 'info@klarmobil.de',
    'kaufland mobil' => 'kundenbetreuung@kaufland-mobil.de',
    'lidl connect' => 'info@lidl-connect.de',
    'lebara' => 'halolebara@lebara.com',
    'fraenk' => 'impressum@fraenk.de',
    'norma connect' => 'kundenbetreuung@norma-connect.de',
    'super select' => 'service@superselect.de',
    'high mobile' => 'service@high-mobile.de',
    'lycamobile' => 'cs@lycamobile.de',
    'edeka mobil' => 'kontakt@otelo.de',
    'edeka smart' => 'kundenbetreuung@edeka-smart.de',
    'ja! mobil' => 'kundenservice@jamobil.de',
    'drillisch online gmbh - handyvertrag.de' => 'kontakt@handyvertrag.de',
'vodafone gmbh - simon mobile' => 'service@simonmobile.de',
'web.de mobilfunk' => 'kuendigung@1und1.de',
'gmx mobilfunk' => 'kuendigung@1und1.de',
'freenet funk' => 'help@freenet-funk.de',
'freenet flex' => 'info@freenet-mobilfunk.de',


    // Fitnessstudio
    'fitx' => 'mitglied@fitx.de',
    'fitness first' => 'service@fitnessfirst.de',
    'holmes place' => 'info@holmesplace.de',
    'urban sports club' => 'hello@urbansportsclub.com',
    'pfitzenmeier' => 'info@pfitzenmeier.de',
    'body + soul' => 'info@bodyandsoul.de',
    'fitness express' => 'info@fitness-express.de',
'xtrafit' => 'service@xtrafit.de',
'prime time fitness gmbh' => 'member@primetime-fitness.de',
'm-fitnesscenter olympia' => 'olympia-schwimmhalle@m-fitnesscenter.de',
'superfit' => 'member@superfit.club',
'meridian spa & fitness deutschland gmbh' => 'info@meridianspa.de',
'david lloyd clubs deutschland gmbh' => 'badhomburg@davidlloyd.de',
'elbgym gmbh bei lifefit group' => 'info@elbgym.de',
'just fit verwaltungs gmbh & co. kg' => 'mitgliederverwaltung@justfit-clubs.de',

];

// LISTA FRANCIZELOR (Aici nu facem precompletare, doar informam)
$franchises = ['clever fit', 'kieser training', 'injoy', 'mrs. sporty', 'bodystreet', 'fitnessking', 'fitseveneleven', 'sparkasse', 'volksbank'];

$pfKey = mb_strtolower(trim($anbieter), 'UTF-8');
$pf = $providerAddresses[$pfKey] ?? null;
$pfEmail = $providerEmails[$pfKey] ?? '';
$isFranchise = in_array($pfKey, $franchises, true);

$isHandy = $type === 'handy';
$isKfz   = $type === 'kfz';
$isBank  = $type === 'bank';
$ctaText  = $isBank ? 'Girokonto kündigen – kostenlos' : ($isKfz ? 'KFZ-Versicherung kündigen – kostenlos' : ($isHandy ? 'Handyvertrag kündigen – kostenlos' : 'Fitness kündigen – kostenlos'));
$title    = $isBank ? 'Girokonto kündigen' : ($isKfz ? 'KFZ-Versicherung kündigen' : ($isHandy ? 'Handyvertrag kündigen' : 'Fitnessstudio kündigen'));
?>
<!doctype html>
<html lang="de" data-type="<?= htmlspecialchars($type) ?>">
<head>
<meta charset="utf-8">
<meta name="color-scheme" content="light">
<link rel="preconnect" href="https://www.clarity.ms">
<link rel="preconnect" href="https://a.check24.net">
<link rel="preconnect" href="https://www.awin1.com">
<link rel="dns-prefetch" href="//www.clarity.ms">
<link rel="dns-prefetch" href="//a.check24.net">
<link rel="dns-prefetch" href="//www.awin1.com">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#16A34A">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="robots" content="noindex, nofollow">
<title><?= $isBank ? 'Girokonto kündigen – Kostenlos als PDF' : ($isKfz ? 'KFZ-Versicherung kündigen – Kostenlos als PDF' : ($isHandy ? 'Handyvertrag kündigen – Kostenlos als PDF' : 'Fitnessstudio kündigen – Kostenlos als PDF')) ?> | KündigungExpress</title>
<meta name="description" content="<?= $isBank ? 'Girokonto kündigen: Daten eingeben, PDF sofort im DIN-Format herunterladen. Kein Abo, ohne E-Mail, ohne Anmeldung.' : ($isKfz ? 'KFZ-Versicherung kündigen: Daten eingeben, PDF sofort im DIN-Format herunterladen. Kein Abo, ohne E-Mail, ohne Anmeldung.' : ($isHandy ? 'Handyvertrag kündigen: Daten eingeben, PDF sofort im DIN-Format herunterladen. Kein Abo, ohne E-Mail, ohne Anmeldung.' : 'Fitnessstudio kündigen: Daten eingeben, PDF sofort im DIN-Format herunterladen. Kein Abo, ohne E-Mail, ohne Anmeldung.')) ?>">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<style>
:root {
  --bg: #F7F9FC;
  --card: #FFFFFF;
  --text: #0F172A;
  --muted: #475569;
  --border: #E2E8F0;
  --primary: #16A34A;
  --soft: #F1F5F9;
  --focus: rgba(22,163,74,0.3);
}
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: Arial, sans-serif;
  background: var(--bg);
  color: var(--text);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.wrap {
  max-width: 800px;
  margin: 0 auto;
  padding: 28px 24px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Header */
.wrap > header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}
header a { text-decoration: none; color: inherit; }
.brand { font-weight: 900; font-size: 20px; }
nav a { margin-left: 16px; color: var(--muted); font-size: 14px; text-decoration: none; }

/* Progress bar */
.progress-bar {
  display: flex;
  align-items: center;
  gap: 0;
  margin-bottom: 28px;
}
.prog-step {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--muted);
}
.prog-step.active { color: var(--primary); font-weight: 700; }
.prog-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--soft);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  color: var(--muted);
  flex-shrink: 0;
}
.prog-step.active .prog-dot {
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
}
.prog-step.done .prog-dot {
  background: #DCFCE7;
  border-color: var(--primary);
  color: var(--primary);
}
.prog-line {
  flex: 1;
  height: 2px;
  background: var(--border);
  margin: 0 8px;
}

/* Kein Haken bar */
.kein-haken{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:10px 16px;margin-bottom:16px;font-size:13px;font-weight:700;color:#15803D}
.kein-haken span{display:flex;align-items:center;gap:5px}

/* Social proof */
.social-proof-form {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  margin-top: 10px;
  font-size: 14px;
  color: #475569;
}
.social-proof-form strong {
  color: #16A34A;
  font-weight: 900;
}

/* Page title */
.page-title {
  margin-bottom: 20px;
  text-align: center;
}
.page-title h1 {
  font-size: clamp(22px, 4vw, 30px);
  font-weight: 900;
  line-height: 1.2;
  margin-bottom: 12px;
}
.page-title .sub {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.page-title .sub span {
  background: #DCFCE7;
  border: 1px solid #BBF7D0;
  border-radius: 999px;
  padding: 4px 12px;
  font-size: 13px;
  font-weight: 700;
  color: #15803D;
}

/* Form */
form {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 24px;
  flex-grow: 1;
}

.section {
  margin-bottom: 24px;
}
.section:last-of-type { margin-bottom: 0; }

.section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border);
}
.section-icon {
  width: 32px;
  height: 32px;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  pointer-events: none;
  user-select: none;
  cursor: default;
}
.section-header h2 {
  font-size: 16px;
  font-weight: 800;
  color: var(--text);
}
.section-header .opt-badge {
  margin-left: auto;
  font-size: 11px;
  color: var(--muted);
  background: var(--soft);
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 2px 8px;
}

/* Field grid & Validation Styles */
.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.full { grid-column: 1 / -1; }

.field { display: flex; flex-direction: column; gap: 4px; position: relative; }
label {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: .04em;
}

input:not([type="checkbox"]):not([type="radio"]), select {
  width: 100%;
  padding: 12px 14px; 
  border: 1px solid var(--border);
  border-radius: 10px;
  font-size: 16px;
  background: #fff;
  color: var(--text);
  transition: border-color .15s ease, box-shadow .15s ease;
  font-family: inherit;
  -webkit-appearance: none;
  appearance: none;
}
input:not([type="checkbox"]):not([type="radio"]):focus, select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--focus);
}
select {
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 16px;
  padding-right: 40px;
}

/* Inline Validation UI */
.input-wrapper { position: relative; width: 100%; display: block; }
.check-icon {
  display: none;
  position: absolute;
  top: 50%;
  right: 14px;
  transform: translateY(-50%);
  color: #16A34A;
  font-weight: 900;
  font-size: 14px;
  pointer-events: none;
}
.is-valid + .check-icon { display: block; }
.is-valid { border-color: #16A34A !important; padding-right: 36px !important; }

.hint {
  font-size: 12px;
  color: #94A3B8;
  line-height: 1.5;
}

/* Optional section */
.optional-section {
  background: var(--soft);
  border: 1px dashed var(--border);
  border-radius: 14px;
  padding: 16px;
}

/* Checkbox row */
.checkbox-row {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}
.checkbox-row input[type="checkbox"] {
  width: 20px;
  height: 20px;
  margin-top: 2px;
  flex-shrink: 0;
  accent-color: var(--primary);
  -webkit-appearance: auto;
  appearance: auto;
  cursor: pointer;
}
.checkbox-label b { font-size: 14px; display: block; margin-bottom: 3px; }
.checkbox-label .hint { margin-top: 0; }

/* Summary box */
.summary-box {
  background: linear-gradient(160deg, #F0FDF4 0%, #fff 100%);
  border: 1px solid rgba(22,163,74,0.25);
  border-radius: 16px;
  padding: 20px;
  margin: 20px 0 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-height: 72px;
}
.summary-price {
  font-size: 22px;
  font-weight: 900;
  color: var(--text);
  margin: 0;
  text-align: center;
}
.summary-pills {
  display: flex;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
}
.summary-pills span {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 4px 12px;
  font-size: 12px;
  color: #334155;
}

/* Invalid field highlight */
input.ke-invalid, select.ke-invalid, textarea.ke-invalid {
  border-color: #DC2626 !important;
  background: #FEF2F2 !important;
  box-shadow: 0 0 0 3px rgba(220,38,38,0.20) !important;
}
.optional-section.is-active {
  background: #F0FDF4;
  border: 2px solid #86EFAC;
  transition: background .2s, border-color .2s;
}
.optional-section.is-active .hint { color: #166534; }

/* Submit button */
.btn-submit {
  width: 100%;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 14px;
  padding: 16px;
  font-size: 17px;
  font-weight: 900;
  cursor: pointer;
  transition: filter .15s ease, transform .15s ease, background .2s;
  box-shadow: 0 4px 14px rgba(22,163,74,0.25);
}
.btn-submit:hover {
  filter: brightness(.95);
  transform: translateY(-1px);
}
.secure-note {
  text-align: center;
  font-size: 12px;
  color: #94A3B8;
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

/* Mobile Sticky Submit */
@media (max-width: 640px) {
  .mobile-sticky-submit {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #ffffff;
      padding: 12px 16px;
      padding-bottom: calc(12px + env(safe-area-inset-bottom));
      box-shadow: 0 -4px 16px rgba(15,23,42,0.1);
      z-index: 1000;
      border-top: 1px solid var(--border);
      transition: transform 0.2s ease-out;
  }
  .mobile-sticky-submit .btn-submit {
      border-radius: 12px;
      padding: 14px;
  }
}

/* Footer */
footer {
  margin-top: auto;
  padding: 18px 0 12px;
  font-size: 12px;
  color: #94A3B8;
  text-align: center;
}
footer a { color: inherit; text-decoration: none; }
footer p { margin-top: 0 !important; margin-bottom: 3px !important; font-size: 12px; color: #94A3B8; line-height: 1.5; }
footer p:last-child { margin-bottom: 0 !important; }
footer a:hover { color: #334155; }

/* Mobile Resets */
.site-header, .mobile-cta { display: none; }

@media (max-width: 700px) {
  .grid { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
  body { display: block; overflow-x: hidden; padding-top: 56px; padding-bottom: 76px; } 
  .wrap { display: block; padding: 0; } 

  .field label { display: flex; flex-direction: column; align-items: flex-start !important; gap: 6px; }
  
  /* Elementele Hero centrate sus */
  .kein-haken { justify-content: center; gap: 6px 10px; padding: 12px 16px; font-size: 12px; margin: 0 16px 16px; }
  .kein-haken span { white-space: nowrap; }

  .social-proof-form { padding: 0 16px; font-size: 14px; margin-top: 8px; margin-bottom: 10px; justify-content: center; }

  .page-title { padding: 0 16px; text-align: center; }
  .page-title h1 { text-align: center; font-size: 26px; margin-bottom: 8px;}
  /* ----------------------------- */

  .wrap > header { display: none; }
  .site-header {
    display: flex; justify-content: space-between; align-items: center; position: fixed; top: 0; left: 0; right: 0; height: 56px; z-index: 1000; background: #fff; border-bottom: 1px solid var(--border); padding: 0 16px;
  }
  .site-header .brand a { font-size: 18px; font-weight: 900; color: var(--text); text-decoration: none; }
  .site-header a.header-hilfe { font-size: 13px; font-weight: 700; color: var(--primary); background: #F0FDF4; padding: 6px 12px; border-radius: 8px; border: 1px solid #BBF7D0; text-decoration: none; white-space: nowrap; flex-shrink: 0; }

  .progress-bar { margin: 16px 16px 20px; }
  .prog-step span { display: none; }

  form { border-radius: 0; border-left: none; border-right: none; padding: 20px 16px; margin-bottom: 0;}
  
  .summary-box { margin: 16px 0 14px; align-items: flex-start; text-align: left; padding: 18px; }
  .summary-price { font-size: 20px; text-align: left; }
  .summary-pills { justify-content: flex-start; }

  .secure-note { align-items: flex-start; text-align: left; padding-top: 8px; }

  footer { margin-top: 0; padding: 16px 16px 16px; background: var(--bg);}
  .brief-preview-wrap { margin: 0 16px 16px; border-radius: 10px; }
  #_rsgDynWarn { padding: 0 16px; }
}

/* =========================================
   MODAL INTERSTITIAL & LOADING CSS
   ========================================= */
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(5px);
    z-index: 99999;
    display: none;
    align-items: center; justify-content: center;
    padding: 20px;
}
.modal-box {
    background: #fff;
    border-radius: 24px;
    width: 100%; max-width: 440px;
    padding: 32px 24px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    animation: modalPop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes modalPop {
    0% { transform: scale(0.9); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.modal-icon { font-size: 40px; margin-bottom: 16px; line-height: 1; }
.modal-title { font-size: 20px; font-weight: 900; color: #0F172A; margin-bottom: 12px; }
.modal-text { font-size: 15px; color: #475569; line-height: 1.6; margin-bottom: 24px; }
.modal-cta {
    display: block; width: 100%; color: #fff; text-decoration: none;
    font-weight: 900; font-size: 16px; padding: 16px; border-radius: 14px;
    margin-bottom: 16px; transition: transform 0.15s, filter 0.15s;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.modal-cta:hover { filter: brightness(0.95); transform: translateY(-1px); }
.modal-skip {
    background: none; border: none; color: #64748B; font-size: 14px;
    font-weight: 600; cursor: pointer; text-decoration: underline; padding: 8px;
    transition: color 0.15s;
}
.modal-skip:hover { color: #334155; }

/* ===== Footer unificat (consistent cu preview și succes) ===== */
.ke-trust-strip {
  max-width: 880px;
  margin: 0 auto;
  padding: 16px 24px 8px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px 20px;
  font-size: 12px;
  color: #64748B;
  text-align: center;
}
.ke-trust-strip span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}
.ke-footer {
  max-width: 880px;
  margin: 0 auto;
  padding: 8px 24px 24px;
  text-align: center;
}
.ke-footer p {
  font-size: 12px;
  color: #94A3B8;
  line-height: 1.6;
  margin: 0 0 4px;
  text-align: center;
}
.ke-footer a {
  color: #64748B;
  text-decoration: none;
}
.ke-footer a:hover {
  color: #0F172A;
  text-decoration: underline;
}
@media (max-width: 720px) {
  .ke-trust-strip {
    padding: 14px 16px 4px;
    gap: 8px 14px;
    font-size: 11px;
  }
  .ke-footer {
    padding: 4px 16px 20px;
  }
  .ke-footer p { font-size: 11.5px; }
}

/* ===== Brief-Vorschau "Peek" — IDENTIC cu PDF-ul real, tăiat vertical =====
   Mirror exact al template-urilor PDF (handy/kfz/fitness-kuendigung.html).
   Static (nu live update). Tăiat după prima frază cu fade-out alb.
   Hook psihologic: userul vede cum arată exact brief-ul → e convins → completează. */
.brief-preview-wrap {
  background: #F3F4F6;
  border-radius: 16px;
  padding: 18px 18px 0;
  margin-bottom: 20px;
}
.brief-preview-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 10px;
}
.brief-preview-title {
  font-size: 12px; font-weight: 700; color: #475569;
  text-transform: uppercase; letter-spacing: 0.04em;
}
.brief-preview-badge {
  display: inline-flex; align-items: center; gap: 4px;
  background: #F0FDF4; color: #15803D;
  font-size: 11px; font-weight: 700;
  padding: 4px 10px; border-radius: 99px;
  border: 1px solid #BBF7D0;
}

/* The "peek" — exact mirror of PDF templates, truncated */
.bp-peek {
  background: #FFFFFF;
  border-radius: 6px 6px 0 0;
  padding: 28px 32px 0;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 12.5px;
  line-height: 1.45;
  color: #1f2937;
  box-shadow: 0 -4px 14px rgba(15,23,42,0.04);
  position: relative;
  max-height: 520px;
  overflow: hidden;
}
.bp-peek::after {
  content: '';
  position: absolute;
  left: 0; right: 0; bottom: 0;
  height: 110px;
  background: linear-gradient(to bottom, rgba(255,255,255,0) 0%, #FFFFFF 75%);
  pointer-events: none;
}

/* Letterhead (mirror PDF template) */
.bp-letterhead { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom: 2px; }
.bp-brand { font-size: 18px; font-weight: 900; color: #16a34a; letter-spacing: -0.5px; line-height: 1; }
.bp-tag { text-align: right; line-height: 1.2; }
.bp-tag-line1 { display:block; font-size: 9px; color: #9ca3af; }
.bp-tag-line2 { display:block; font-size: 11px; font-weight: 700; color: #1f2937; letter-spacing: 0.2px; }
.bp-letterhead-rule { height:0; border:0; border-top: 2px solid #16a34a; margin: 5px 0 14px 0; }

/* Sender line (mirror) */
.bp-sender {
  font-size: 10px; color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 5px; margin-bottom: 14px;
}
.bp-sender.bp-placeholder { color: #A8A29E; font-style: italic; }

/* Recipient (mirror) */
.bp-recipient { margin-bottom: 14px; }
.bp-recipient-label {
  font-size: 9px; color: #9ca3af; text-transform: uppercase;
  letter-spacing: 1.2px; font-weight: 700; margin-bottom: 5px;
}
.bp-recipient-name { font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 3px; }
.bp-recipient-addr { color: #4b5563; line-height: 1.45; }
.bp-recipient-addr .bp-line { display: block; }
.bp-recipient-addr.bp-placeholder { color: #A8A29E; font-style: italic; }

/* Meta row (date + email) (mirror) */
.bp-meta { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom: 14px; gap: 12px; }
.bp-meta-email { font-size: 11.5px; color: #6b7280; }
.bp-meta-date { font-size: 13px; color: #1f2937; font-weight: 600; text-align: right; white-space: nowrap; }

/* Subject + rule (mirror) */
.bp-subject { font-weight: 700; font-size: 15px; color: #111827; margin-bottom: 3px; }
.bp-subject-rule { height: 0; border: 0; border-top: 1px solid #d1d5db; margin: 0 0 14px 0; }

/* Contract box (mirror) */
.bp-contract {
  background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #16a34a;
  padding: 9px 14px; margin-bottom: 14px;
  font-size: 11.5px; color: #14532d; border-radius: 2px;
}
.bp-contract-label { font-weight: 700; color: #15803d; margin-right: 4px; }

/* Body (mirror) */
.bp-body p { margin: 0 0 9px; text-align: justify; line-height: 1.5; }
.bp-body strong { color: #111827; }

.brief-preview-foot {
  padding: 14px 4px 4px;
  font-size: 13px; color: #475569; text-align: center;
}
.brief-preview-foot strong { color: #0F172A; }

@media (max-width: 720px) {
  .brief-preview-wrap { padding: 12px 12px 0; border-radius: 12px; }
  .bp-peek { padding: 20px 18px 0; font-size: 11.5px; max-height: 460px; }
  .bp-brand { font-size: 16px; }
  .bp-subject { font-size: 14px; }
  .bp-recipient-name { font-size: 13px; }
  .brief-preview-foot { font-size: 12px; padding: 10px 4px 4px; }
}
@media (max-width: 400px) {
  .bp-meta { flex-direction: column; align-items: flex-start; gap: 4px; }
  .bp-meta-date { text-align: left; white-space: normal; }
}

/* ===== Accordion "Weitere Angaben" — make it OBVIOUS clickable ===== */
.section.accordion-section {
  padding: 0;
  background: #FFFFFF;
  border: 2px dashed #CBD5E1;
  border-radius: 14px;
  transition: border-color 0.15s, background 0.15s;
  margin-bottom: 32px !important; /* override .section:last-of-type margin:0 când accordion e ultimul <details> */
}
.accordion-section[open] {
  border-style: solid;
  border-color: var(--primary);
  background: #FFFFFF;
}
.accordion-section[open] .accordion-summary .accordion-chev {
  transform: rotate(45deg);
  background: var(--primary);
  color: #fff;
  border-color: var(--primary);
}
.accordion-summary {
  list-style: none; cursor: pointer; padding: 16px 18px;
  display: flex; align-items: center; gap: 12px;
  user-select: none;
  border-radius: 12px;
}
.accordion-summary::-webkit-details-marker { display: none; }
.accordion-summary:hover { background: #F8FAFC; }
.accordion-summary h2 { margin: 0; font-size: 16px; }
.accordion-summary .accordion-hint {
  flex: 1; font-size: 12px; color: #64748B; font-weight: 500;
}
.accordion-chev {
  width: 28px; height: 28px; border-radius: 8px;
  border: 2px solid #CBD5E1; background: #F8FAFC;
  color: #64748B; font-size: 18px; font-weight: 700;
  display: inline-flex; align-items: center; justify-content: center;
  transition: transform 0.2s ease, background 0.15s, color 0.15s, border-color 0.15s;
  flex-shrink: 0;
}
.accordion-body { padding: 4px 18px 20px; border-top: 1px solid #F1F5F9; margin: 0 18px; }
@media (max-width: 720px) {
  .accordion-summary { padding: 14px; }
  .accordion-body { padding: 4px 14px 16px; margin: 0 14px; }
  .accordion-summary h2 { font-size: 15px; }
}
</style>
<script src="/formular.js" defer></script>
    
    <script src="/cookie-consent.js" defer></script> <script src="/affiliate-tracking.js" defer></script>

<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
  'event': 'funnel_step',
  'step_name': '1_form_view',
  'contract_type': '<?= htmlspecialchars($type, ENT_QUOTES) ?>',
  'provider': '<?= htmlspecialchars($anbieter, ENT_QUOTES) ?>'
});
</script>
</head>

<body>
<header class="site-header">
  <div class="brand"><a href="/">KündigungExpress</a></div>
  <a href="/hilfe.html" class="header-hilfe">Hilfe / FAQ</a>
</header>

<div class="wrap">
  <header>
    <div class="brand"><a href="/">KündigungExpress</a></div>
    <nav>
      <a href="/hilfe.html">Hilfe / FAQ</a>
      <a href="/impressum.html">Impressum</a>
      <a href="/datenschutz.html">Datenschutz</a>
    </nav>
  </header>

  <div class="progress-bar">
    <div class="prog-step done">
      <div class="prog-dot">✓</div>
      <span>Vertragsart</span>
    </div>
    <div class="prog-line"></div>
    <div class="prog-step active">
      <div class="prog-dot">2</div>
      <span>Ihre Daten</span>
    </div>
    <div class="prog-line"></div>
    <div class="prog-step">
      <div class="prog-dot">3</div>
      <span>Fertiges Dokument</span>
    </div>
  </div>
<?php if (isset($_GET['cancelled']) && $_GET['cancelled'] === '1'): ?>
  <style>
    /* Wrapper-ul ne asigură că pe mobil alerta are padding-ul perfect de 16px lateral */
    .cancel-alert-wrap { padding: 0; margin-bottom: 24px; }
    .cancel-alert-box { background: #FEF2F2; border: 1px solid #FEE2E2; border-left: 4px solid #EF4444; border-radius: 12px; padding: 14px 16px; width: 100%; box-sizing: border-box; }
    @media (max-width: 640px) { .cancel-alert-wrap { padding: 0 16px; margin-bottom: 16px; } }
  </style>
  <div class="cancel-alert-wrap">
      <div class="cancel-alert-box">
          <p style="margin: 0 0 6px 0; font-weight: 700; font-size: 14px; color: #991B1B; display: flex; align-items: center; gap: 8px;">
              ❌ <span>Zahlung abgebrochen</span>
          </p>
          <p style="margin: 0; font-size: 13px; color: #B91C1C; line-height: 1.5;">
              Der Zahlungsvorgang wurde abgebrochen. Ihre Kündigung wurde <strong>nicht</strong> per Post versendet. Wir haben Ihre Daten für Sie wiederhergestellt. Sie können den Vorgang bei Bedarf erneut starten.
          </p>
      </div>
  </div>
  <script>
    // Resetăm butonul ca să arate normal
    document.addEventListener("DOMContentLoaded", function() {
        var submitBtn = document.querySelector('button[type="submit"]') || document.querySelector('.btn-submit') || document.querySelector('#mainSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = "1";
            submitBtn.style.pointerEvents = "auto";
            submitBtn.innerHTML = 'Vorschau anzeigen →';
        }
    });
  </script>
<?php endif; ?>  
<div class="page-title">
    <h1><?= $isBank ? '🏦 Girokonto kündigen' : ($isKfz ? '🚗 KFZ-Versicherung kündigen' : ($isHandy ? '📱 Handyvertrag kündigen' : '🏋️ Fitnessstudio kündigen')) ?></h1>
    <div class="social-proof-form">
      <span>👥</span>
      <span><strong><?php $c=file_exists(__DIR__.'/_counter.txt')?(int)file_get_contents(__DIR__.'/_counter.txt'):1247; echo number_format($c,0,',','.'); ?></strong> Kündigungen bereits erstellt</span>
    </div>
  </div>

  <div class="kein-haken">
    <span>🔓 Kostenlos</span>
    <span>📧 Ohne E-Mail</span>
    <span>🚫 Kein Abo</span>
    <span>✌️ Kein Haken</span>
  </div>

  <?php
    /* Brief-Vorschau "Peek": mirror EXACT al PDF-ului real, tăiat după prima frază cu fade-out. */
    if ($isKfz) {
      $bpSubject  = 'Ordentliche Kündigung meiner KFZ-Versicherung';
      $bpFirstLine = 'hiermit kündige ich die oben genannte KFZ-Versicherung (Haftpflicht, ggf. Teil-/Vollkasko) <strong>zum nächstmöglichen Zeitpunkt</strong> ordentlich zum Ablauf des laufenden Versicherungsjahres.';
      $bpContractDefault = 'Versicherungsschein-Nr. wird nachgereicht';
    } elseif ($isHandy) {
      $bpSubject  = 'Ordentliche Kündigung meines Mobilfunkvertrags';
      $bpFirstLine = 'hiermit kündige ich meinen oben genannten Mobilfunkvertrag <strong>zum nächstmöglichen Zeitpunkt</strong> gemäß <strong>§ 56 TKG</strong>.';
      $bpContractDefault = 'Vertragsnummer wird nachgereicht';
    } elseif ($isBank) {
      $bpSubject  = 'Kündigung meines Girokontos';
      $bpFirstLine = 'hiermit kündige ich mein oben genanntes Girokonto <strong>zum nächstmöglichen Zeitpunkt</strong> ordentlich gemäß <strong>§ 675h BGB</strong>.';
      $bpContractDefault = 'IBAN / Kontonummer wird nachgereicht';
    } else {
      $bpSubject  = 'Ordentliche Kündigung meiner Mitgliedschaft';
      $bpFirstLine = 'hiermit kündige ich meine oben genannte Fitnessstudio-Mitgliedschaft <strong>zum nächstmöglichen Zeitpunkt</strong> gemäß <strong>§ 621 BGB</strong> in Verbindung mit den vereinbarten Vertragsbedingungen.';
      $bpContractDefault = 'Mitgliedsnummer wird nachgereicht';
    }
  ?>
  <div class="brief-preview-wrap" aria-label="Vorschau Ihres Kündigungsschreibens">
    <div class="brief-preview-header">
      <div class="brief-preview-title">📄 So sieht Ihr Brief aus</div>
      <div class="brief-preview-badge">✅ DIN-Format</div>
    </div>

    <div class="bp-peek">
      <div class="bp-letterhead">
        <div class="bp-brand">KündigungExpress</div>
        <div class="bp-tag">
          <span class="bp-tag-line1">Rechtssichere Kündigungen</span>
          <span class="bp-tag-line2">kuendigungexpress.de</span>
        </div>
      </div>
      <hr class="bp-letterhead-rule">

      <div class="bp-sender bp-placeholder">Ihr Name · Ihre Adresse (wird unten ergänzt)</div>

      <div class="bp-recipient">
        <div class="bp-recipient-label">Empfänger</div>
        <div class="bp-recipient-name"><?= htmlspecialchars($anbieter !== '' ? $anbieter : 'Ihr Anbieter') ?></div>
        <?php if ($pf): ?>
        <div class="bp-recipient-addr">
          <span class="bp-line"><?= htmlspecialchars($pf[0]) ?></span>
          <span class="bp-line"><?= htmlspecialchars(trim($pf[1] . ' ' . $pf[2])) ?></span>
        </div>
        <?php else: ?>
        <div class="bp-recipient-addr bp-placeholder">
          <span class="bp-line">Adresse wird unten ergänzt</span>
        </div>
        <?php endif; ?>
      </div>

      <div class="bp-meta">
        <div class="bp-meta-email"></div>
        <div class="bp-meta-date"><?= date('d.m.Y') ?></div>
      </div>

      <div class="bp-subject"><?= htmlspecialchars($bpSubject) ?></div>
      <hr class="bp-subject-rule">

      <div class="bp-contract">
        <span class="bp-contract-label">Vertragsangaben:</span>
        <span><?= htmlspecialchars($bpContractDefault) ?></span>
      </div>

      <div class="bp-body">
        <p>Sehr geehrte Damen und Herren,</p>
        <p><?= $bpFirstLine /* HTML intentionally not escaped — conține <strong> */ ?></p>
      </div>
    </div>

    <div class="brief-preview-foot">
      👇 <strong>Jetzt ausfüllen</strong> — Brief wird automatisch erstellt
    </div>
  </div>

  <form id="keForm" method="post" action="/generate.php">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

    <!-- Honeypot anti-bot: ascuns vizual, ignorat de utilizatori reali.
         Boții completează câmpul "website" → respins server-side (M4a). -->
    <div aria-hidden="true" style="position:absolute; left:-9999px; top:-9999px; width:1px; height:1px; overflow:hidden;">
      <label>Website (bitte leer lassen)
        <input type="text" name="website" tabindex="-1" autocomplete="off" value="">
      </label>
    </div>

    <div class="section">
      <div style="display:inline-flex; align-items:center; gap:6px; background:#F0FDF4; color:#15803D; font-size:11px; font-weight:700; padding:4px 10px; border-radius:99px; border:1px solid #BBF7D0; margin-bottom: 12px;">
         🔒 SSL-Verschlüsselt & 100% DSGVO-konform
      </div>
      <div class="section-header" style="margin-top: 0;">
        <div class="section-icon"><?= $isBank ? '🏦' : ($isKfz ? '🚗' : ($isHandy ? '📡' : '🏋️')) ?></div>
        <h2><?= $isBank ? 'Bank' : ($isKfz ? 'Versicherer' : ($isHandy ? 'Mobilfunkanbieter' : 'Fitnessstudio')) ?></h2>
      </div>
      <div class="grid">
        <div class="field full">
          <label><?= $isBank ? 'Name der Bank *' : ($isKfz ? 'Versicherer *' : ($isHandy ? 'Anbieter *' : 'Fitnessstudio *')) ?></label>
          <input
            id="anbieterInput"
            type="text"
            name="anbieter"
            value="<?= htmlspecialchars($anbieter) ?>"
            placeholder="<?= $isBank ? 'z. B. ING, DKB, Sparkasse' : ($isKfz ? 'z. B. Allianz, HUK-COBURG, ERGO' : ($isHandy ? 'z. B. Vodafone, Telekom, O2' : 'z. B. McFit, FitX, clever fit')) ?>"
            required
            data-is-rsg="<?= $isRSG ? '1' : '0' ?>"
          >
        </div>
        <div class="field full optional-section" style="display:block;">
          <div style="font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Adresse des <?= $isBank ? 'Bankinstituts' : ($isKfz ? 'Versicherers' : ($isHandy ? 'Anbieters' : 'Studios')) ?> <?= ($pf || $isFranchise) ? '' : '<span style="font-weight:400;text-transform:none">(optional)</span>' ?></div>

          <?php if ($pf): ?>
          <div class="hint" style="margin-bottom:10px; color: #16A34A;"><strong>Automatisch ausgefüllt</strong> – die Adresse wurde anhand Ihrer Anbieterauswahl hinterlegt.</div>
          <?php elseif ($isFranchise): ?>
          <div class="hint" style="margin-bottom:10px; color: #D97706;"><strong>Wichtig:</strong> <?= htmlspecialchars($anbieter) ?> wird <?= $isBank ? 'von vielen rechtlich eigenständigen Instituten betrieben. Bitte geben Sie die Adresse Ihrer Filiale ein (siehe Kontoauszug/Vertrag)' : 'im Franchise-System betrieben. Bitte geben Sie die Adresse Ihres Studios ein (siehe Rechnung/Vertrag)' ?>, falls zur Hand.</div>
          <?php else: ?>
          <div class="hint" style="margin-bottom:10px; color: #16A34A;"><strong>Nicht zwingend erforderlich:</strong> Lassen Sie die Felder leer, falls Sie die Adresse nicht kennen. Das Kündigungsschreiben ist auch ohne diese Angabe zu 100% rechtsgültig.</div>
          <?php endif; ?>

          <div class="grid" style="margin:0;">
            <div class="field full">
              <input name="studioStreet" placeholder="Straße &amp; Hausnummer" value="<?= $pf ? htmlspecialchars($pf[0]) : '' ?>">
            </div>
            <div class="field">
              <input name="studioZip" inputmode="numeric" maxlength="5" placeholder="PLZ" value="<?= $pf ? htmlspecialchars($pf[1]) : '' ?>">
            </div>
            <div class="field">
              <input name="studioCity" placeholder="Ort" value="<?= $pf ? htmlspecialchars($pf[2]) : '' ?>">
            </div>
          </div>
        </div>
      </div>
    </div>


<div id="_rsgDynWarn" style="display:none; margin-bottom: 8px;">
  <div class="section" style="margin-bottom: 0;">
    <div class="section-header" style="margin-top: 0;">
      <div class="section-icon">⚠️</div>
      <h2>Kündigung per E-Mail nicht mehr möglich</h2>
    </div>
    <div style="background:#FEF9C3;border:1px solid #FDE68A;border-left:4px solid #F59E0B;border-radius:12px;padding:16px 20px;">
      <p style="margin:0 0 10px;font-weight:700;font-size:14px;color:#92400E;">RSG Group akzeptiert keine E-Mail-Kündigungen mehr</p>
      <p style="margin:0 0 12px;font-size:13px;color:#78350F;line-height:1.65;">Die RSG Group (McFit &amp; John Reed) hat die E-Mail-Adresse für Kündigungen abgeschaltet. Kündigungen müssen über das offizielle Kontaktformular eingereicht werden.</p>
      <a id="_rsgDynLink" href="https://hilfe.mcfit.com/hc/de" target="_blank" rel="nofollow noopener" style="display:inline-block;background:#DC2626;color:#FFFFFF;font-size:13px;font-weight:700;padding:11px 20px;border-radius:10px;text-decoration:none;">McFit Kontaktformular öffnen →</a>
      <p style="margin:10px 0 0;font-size:12px;color:#78350F;">Ihr PDF können Sie trotzdem herunterladen und dort hochladen oder per Post versenden.</p>
    </div>
  </div>
</div>

    <div class="section">
      <div class="section-header" style="margin-top: 0;">
        <div class="section-icon">👤</div>
        <h2>Absender (Ihre Daten)</h2>
      </div>
      <div class="grid">
        <div class="field">
          <label>Vorname *</label>
          <input name="firstName" required autocomplete="given-name">
        </div>
        <div class="field">
          <label>Nachname *</label>
          <input name="lastName" required autocomplete="family-name">
        </div>
        <div class="field full">
          <label>Straße &amp; Hausnummer *</label>
          <input name="street" required autocomplete="street-address">
        </div>
        <div class="field">
          <label>PLZ *</label>
          <input name="zip" inputmode="numeric" maxlength="5" required autocomplete="postal-code" placeholder="z. B. 63073">
        </div>
        <div class="field">
          <label>Ort *</label>
          <input name="city" required autocomplete="address-level2">
        </div>
        <div class="field full">
          <label>Ihre E-Mail <span style="font-weight:400;text-transform:none;letter-spacing:0" id="emailLabelHint">— für Versandbestätigung & Tracking</span></label>
          <input name="email" type="email" id="userEmailField" placeholder="z. B. ihre@email.com" autocomplete="email">
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-header">
        <div class="section-icon">📅</div>
        <h2>Kündigungstermin</h2>
        <span class="opt-badge">Optional</span>
      </div>
      <div class="field" style="margin-bottom:10px;">
        <select id="terminationMode" name="terminationMode">
          <option value="next_possible" selected>Zum nächstmöglichen Zeitpunkt</option>
          <option value="specific_date">Zu einem bestimmten Datum</option>
        </select>
        <div class="hint">Unsicher? Wählen Sie „nächstmöglichen Zeitpunkt" – das ist am sichersten.</div>
      </div>
      <div class="field" id="terminationDateWrap" style="display:none;">
        <label>Datum *</label>
        <input id="terminationDate" name="terminationDate" type="date">
      </div>
    </div>

    <details class="section accordion-section">
      <summary class="accordion-summary">
        <div class="section-icon">🔢</div>
        <h2>Weitere Angaben</h2>
        <span class="accordion-hint"><?= $isBank ? 'Kontodaten' : 'Vertragsnummer' ?><?= $isKfz ? ', Kennzeichen' : '' ?> — klicken zum Ausklappen</span>
        <span class="accordion-chev" aria-hidden="true">+</span>
      </summary>
      <div class="accordion-body">
      <div class="field">
        <label style="display:flex; justify-content:space-between; align-items:flex-end; width: 100%;">
          <span><?= $isBank ? 'IBAN / Kontonummer' : ($isKfz ? 'Versicherungsschein-Nr.' : ($isHandy ? 'Vertragsnummer/Rufnummer' : 'Vertragsnummer')) ?></span>
          <span style="color:var(--primary); font-size:11px; cursor:pointer; text-transform:none; font-weight:600; padding: 4px 0;" onclick="var inp = document.querySelector('input[name=\'contractNo\']'); inp.value = (inp.value === 'Wird nachgereicht') ? '' : 'Wird nachgereicht'; inp.dispatchEvent(new Event('input'));">Gerade nicht zur Hand?</span>
        </label>
        <input name="contractNo" data-iban="<?php echo $isBank ? '1' : '0'; ?>" placeholder="<?= $isBank ? 'z. B. DE12 3456 7890 1234 5678 90' : ($isKfz ? 'z. B. VS-123456789' : 'z. B. 12345678') ?>" onfocus="if(this.value === 'Wird nachgereicht') { this.value = ''; this.dispatchEvent(new Event('input')); }">
        <div class="hint"><?= $isBank ? 'Ihre IBAN steht auf Ihrer Bankkarte oder im Online-Banking. Optional — ohne Nummer ist die Kündigung trotzdem gültig.' : ($isKfz ? 'Steht auf Ihrem Versicherungsschein. Optional — ohne Nummer ist die Kündigung trotzdem gültig.' : 'Steht auf Ihrer Rechnung oben rechts. Optional — ohne Nummer ist die Kündigung trotzdem gültig.') ?></div>
      </div>
      <?php if ($isKfz): ?>
      <div class="field" style="margin-top:12px;">
        <label>Kennzeichen <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>
        <input name="plate" placeholder="z. B. OF-KE 123">
        <div class="hint">Das amtliche Kennzeichen Ihres Fahrzeugs. Hilft bei der eindeutigen Zuordnung.</div>
      </div>
      <?php endif; ?>
      <?php if ($isBank): ?>
      <div class="field" style="margin-top:12px;">
        <label>Ziel-IBAN für Restguthaben <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>
        <input name="ziel_iban" placeholder="z. B. DE12 3456 7890 1234 5678 90">
        <div class="hint">Konto, auf das ein etwaiges Restguthaben überwiesen werden soll.</div>
      </div>
      <?php endif; ?>
      </div>
    </details>
<?php if ($isRSG): ?>
    <div class="section">
      <div class="section-header">
        <div class="section-icon">⚠️</div>
        <h2>Kündigung per E-Mail nicht mehr möglich</h2>
      </div>
      <div style="background:#FEF9C3;border:1px solid #FDE68A;border-left:4px solid #F59E0B;border-radius:12px;padding:16px 20px;">
        <p style="margin:0 0 10px;font-weight:700;font-size:14px;color:#92400E;">RSG Group akzeptiert keine E-Mail-Kündigungen mehr</p>
        <p style="margin:0 0 12px;font-size:13px;color:#78350F;line-height:1.65;">Die RSG Group (McFit &amp; John Reed) hat die E-Mail-Adresse für Kündigungen abgeschaltet. Kündigungen müssen ab sofort über das offizielle Kontaktformular eingereicht werden.</p>
        <?php if (stripos($anbieter, 'john') !== false): ?>
        <a href="https://help.johnreed.fitness/hc/de" target="_blank" rel="nofollow noopener" style="display:inline-block;background:#DC2626;color:#FFFFFF;font-size:13px;font-weight:700;padding:11px 20px;border-radius:10px;text-decoration:none;">John Reed Kontaktformular öffnen →</a>
        <?php else: ?>
        <a href="https://hilfe.mcfit.com/hc/de" target="_blank" rel="nofollow noopener" style="display:inline-block;background:#DC2626;color:#FFFFFF;font-size:13px;font-weight:700;padding:11px 20px;border-radius:10px;text-decoration:none;">McFit Kontaktformular öffnen →</a>
        <?php endif; ?>
        <p style="margin:10px 0 0;font-size:12px;color:#78350F;">Ihr PDF können Sie trotzdem herunterladen und dort hochladen oder per Post versenden.</p>
      </div>
    </div>
    
    <?php elseif (trim($anbieter) === 'American Fitness'): ?>
    <div class="section">
      <div class="section-header">
        <div class="section-icon">⚠️</div>
        <h2>Kündigung per E-Mail nicht möglich</h2>
      </div>
      <div style="background:#FEF9C3;border:1px solid #FDE68A;border-left:4px solid #F59E0B;border-radius:12px;padding:16px 20px;">
        <p style="margin:0 0 10px;font-weight:700;font-size:14px;color:#92400E;">American Fitness akzeptiert keine E-Mail-Kündigungen</p>
        <p style="margin:0 0 12px;font-size:13px;color:#78350F;line-height:1.65;">Laut Ziffer 3.2 der Allgemeinen Mitgliedschaftsbedingungen bedarf jede Kündigung zwingend der Schriftform. Eine Kündigung per E-Mail ist vertraglich ausgeschlossen und rechtlich unwirksam.</p>
        <p style="margin:0;font-size:12px;color:#78350F;">Laden Sie Ihr PDF herunter, drucken Sie es aus und versenden Sie es per Post (am besten per Einschreiben) oder geben Sie es persönlich im Studio ab.</p>
      </div>
    </div>

    <?php elseif (!$isBank): ?>
    <div class="section">
      <div class="section-header">
        <div class="section-icon">✉️</div>
        <h2>Direkt per E-Mail senden</h2>
        <span class="opt-badge">Optional</span>
      </div>
      <div class="optional-section" id="emailSection">
        <div class="checkbox-row" style="margin-bottom:12px;">
          <input id="sendEmail" name="sendEmail" type="checkbox" value="1">
          <div class="checkbox-label">
            <b>Kündigung direkt ans <?= $isKfz ? 'Versicherungsunternehmen' : ($isHandy ? 'Unternehmen' : 'Fitnessstudio') ?> senden</b>
            <div class="hint">Das PDF bleibt immer zusätzlich als Download verfügbar.</div>
          </div>
        </div>
        
        <div class="field">
          <label>E-Mail des <?php echo $isKfz ? 'Versicherers' : ($isHandy ? 'Anbieters' : 'Studios'); ?> <span id="providerReq" style="color:var(--primary);display:none;">*</span></label>
          <input id="providerEmail" name="providerEmail" type="email" placeholder="<?php echo $isKfz ? 'service@versicherung.de' : ($isHandy ? 'service@anbieter.de' : 'service@studio.de'); ?>" value="">          
          <?php if (!empty($pfEmail)): ?>
          <div class="hint" id="providerHint" style="color: #16A34A; font-weight: 600;">✓ E-Mail für <?php echo htmlspecialchars($anbieter, ENT_QUOTES, 'UTF-8'); ?> automatisch erkannt.</div>
          
          <div style="margin-top: 8px; font-size: 11px; line-height: 1.5; color: #475569; background: #F8FAFC; border-left: 3px solid #94A3B8; padding: 8px 12px; border-radius: 4px;">
            <strong>Wichtiger rechtlicher Hinweis:</strong> Bei einer Kündigung per E-Mail tragen Sie die Beweislast. Verlangen Sie immer eine Eingangsbestätigung. Sollten Sie nach 7 Tagen keine Bestätigung erhalten, empfehlen wir dringend, das heruntergeladene PDF zusätzlich per Einwurf-Einschreiben zu versenden.
          </div>
          
          <script>
            document.addEventListener('DOMContentLoaded', function() {
                var emailInput = document.getElementById('providerEmail');
                var sendCheckbox = document.getElementById('sendEmail');
                var defaultEmail = "<?php echo addslashes($pfEmail); ?>";
                
                if (emailInput && defaultEmail !== '') {
                    setTimeout(function() {
                        if (emailInput.value === '') {
                            emailInput.value = defaultEmail;
                        }
                    }, 150);
                    
                    if (sendCheckbox) {
                        sendCheckbox.addEventListener('change', function() {
                            if (!this.checked) {
                                setTimeout(function() {
                                    if (emailInput.value === '') {
                                        emailInput.value = defaultEmail;
                                    }
                                }, 50);
                            }
                        });
                    }
                }
            });
          </script>
          
          <?php else: ?>
          <div class="hint" id="providerHint" style="color: #D97706; background: #FFFBEB; border: 1px solid #FDE68A; padding: 12px 16px; border-radius: 10px; margin-top: 8px; line-height: 1.5;">
            <strong>Profi-Tipp:</strong> Wir haben für <em><?php echo htmlspecialchars($anbieter !== '' ? $anbieter : 'diesen Anbieter', ENT_QUOTES, 'UTF-8'); ?></em> keine spezielle Kündigungs-E-Mail hinterlegt.<br><br>
            <strong>So geht's trotzdem:</strong> Suchen Sie einfach auf der Website des Anbieters im <strong>Impressum</strong> nach der allgemeinen E-Mail-Adresse (z. B. info@...) und tragen Sie diese hier ein. Kündigungen an die offizielle Impressum-Adresse sind rechtlich absolut gültig. 
          </div>
          <?php endif; ?>
        </div>
        
      </div>
    </div>
    <?php endif; ?>

    <div class="summary-box">
      <div class="summary-price">Kostenlos · Sofort als PDF</div>
      <div class="summary-pills">
        <span>✓ Ohne Anmeldung</span>
        <span>✓ DIN-Format</span>
        <span>✓ Kein Abo</span>
      </div>
    </div>

    <div class="field full" style="margin: 8px 0 16px;">
      <div class="checkbox-row">
        <input id="trustpilotConsent" name="trustpilotConsent" type="checkbox" value="1">
        <div class="checkbox-label">
          <b>Bewertungs-Einladung erhalten</b>
          <div class="hint">Nur falls Sie oben Ihre E-Mail angegeben haben. Ich möchte nach Erhalt eine einmalige Einladung zur Bewertung auf Trustpilot bekommen. Freiwillig und jederzeit widerrufbar.</div>
        </div>
      </div>
    </div>

    <input type="hidden" name="step" value="preview">
    <div class="mobile-sticky-submit">
        <button class="btn-submit" type="submit" id="mainSubmitBtn">Vorschau anzeigen →</button>
    </div>
    
    <div class="secure-note" style="flex-direction: column; gap: 6px;">
      <div style="color: #64748B; font-size: 11px; line-height: 1.5;">
        Falls Sie eine eigene E-Mail-Adresse angegeben haben, erhalten Sie Ihr PDF zusätzlich als Kopie. Eine Einladung zur Bewertung auf Trustpilot senden wir nur, wenn Sie dem oben ausdrücklich zugestimmt haben.
      </div>
    </div>

  </form>

  </div> <div class="ke-trust-strip">
    <span>🔒 SSL-verschlüsselt</span>
    <span>🛡️ DSGVO-konform</span>
    <span>⚖️ Muster-Vorlage, keine Rechtsberatung</span>
  </div>

  <footer class="ke-footer">
    <p>© 2026 KündigungExpress · <a href="/impressum.html">Impressum</a> · <a href="/datenschutz.html">Datenschutz</a> · <a href="/agb.html">AGB</a> · <a href="/hilfe.html">Hilfe</a> · <a href="#" onclick="return keResetConsent(event)">Cookie-Einstellungen</a></p>
    </footer>

  <script>
  function keResetConsent(e) {
    if (e && e.preventDefault) e.preventDefault();
    try {
      localStorage.removeItem('cookieConsent');
      document.cookie = 'cookieConsent=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    } catch(err) {}
    location.reload();
    return false;
  }
  </script>

<div class="modal-overlay" id="upsellModal" style="display:none !important">
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        if (typeof window.__kePrefillFromPost === 'object' && window.__kePrefillFromPost) {
            var form = document.getElementById('keForm');
            var data = window.__kePrefillFromPost;
            if (form) {
                Object.keys(data).forEach(function(name) {
                    var el = form.querySelector('[name="' + name + '"]');
                    if (!el || data[name] === '' || data[name] == null) return;
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        if (el.value === data[name]) el.checked = true;
                    } else if (el.tagName === 'SELECT') {
                        el.value = data[name];
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (el.type !== 'submit' && el.type !== 'button' && el.type !== 'hidden') {
                        el.value = data[name];
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            }
        }
    } catch (err) { console.warn('[KE] Pre-fill failed:', err); }

    function keIbanMod97(v){ var r=v.slice(4)+v.slice(0,4), e="", i, c; for(i=0;i<r.length;i++){ c=r.charCodeAt(i); e += (c>=65&&c<=90)?(c-55).toString():r.charAt(i); } var rem=0; for(i=0;i<e.length;i++){ rem=(rem*10+(e.charCodeAt(i)-48))%97; } return rem; }
    function isValidGermanIban(v){ return /^DE\d{20}$/.test(v) && keIbanMod97(v)===1; }
    const inputs = document.querySelectorAll('input[required], input[name="zip"], input[name="email"], input[name="contractNo"]');
    
    inputs.forEach(input => {
        if (!input.parentNode.classList.contains('input-wrapper')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'input-wrapper';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            
            const check = document.createElement('span');
            check.className = 'check-icon';
            check.innerHTML = '✓';
            wrapper.appendChild(check);
        }

        input.addEventListener('input', function() {
            let isValid = false;
            let val = this.value.trim();
            
            if (this.name === 'zip') {
                isValid = /^\d{5}$/.test(val);
            } else if (this.type === 'email') {
                isValid = val === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
                if (val === '' && this.required) isValid = false;
            } else if (this.name === 'contractNo' && this.getAttribute('data-iban') === '1') {
                var ibanRaw = val.replace(/\s+/g, '').toUpperCase();
                var ibanBad = false;
                if (val === 'Wird nachgereicht') { isValid = true; }
                else if (val === '') { isValid = false; }
                else if (isValidGermanIban(ibanRaw)) { isValid = true; }
                else { isValid = false; ibanBad = true; }
                if (ibanBad) {
                    this.classList.add('ke-invalid');
                    this.style.setProperty('border-color', '#DC2626', 'important');
                    this.style.setProperty('background', '#FEF2F2', 'important');
                    this.style.setProperty('box-shadow', '0 0 0 3px rgba(220,38,38,0.25)', 'important');
                } else {
                    this.classList.remove('ke-invalid');
                    this.style.removeProperty('border-color');
                    this.style.removeProperty('background');
                    this.style.removeProperty('box-shadow');
                }
            } else {
                isValid = val.length > 1;
            }
            
            if (isValid && val !== '') {
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });
    });

    const stickySubmit = document.querySelector('.mobile-sticky-submit');
    if (stickySubmit && window.innerWidth <= 640) {
        const allFormInputs = document.querySelectorAll('input, select');
        allFormInputs.forEach(inp => {
            inp.addEventListener('focus', () => { 
                stickySubmit.style.transform = 'translateY(150%)'; 
            });
            inp.addEventListener('blur', () => { 
                setTimeout(() => { 
                    stickySubmit.style.transform = 'translateY(0)'; 
                }, 200); 
            });
        });
    }

    const keForm = document.getElementById('keForm');
    const submitBtns = document.querySelectorAll('.btn-submit');
    const modal = document.getElementById('upsellModal');
    let formIsSubmitting = false;

    if (keForm) {
        keForm.addEventListener('submit', function(e) {
            if (formIsSubmitting) {
                e.preventDefault();
                return;
            }
            e.preventDefault(); 
            formIsSubmitting = true;

            submitBtns.forEach(btn => {
                btn.style.pointerEvents = 'none';
                btn.style.background = '#475569';
                btn.style.boxShadow = 'none';
                btn.innerHTML = '🔄 Daten werden geprüft...';
            });

            setTimeout(() => {
                submitBtns.forEach(btn => {
                    btn.innerHTML = '📄 Vorschau wird erstellt...';
                });
            }, 300);

            setTimeout(() => {
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    'event': 'funnel_step',
                    'step_name': '2_preview_submit',
                    'contract_type': (document.querySelector('[name="type"]') || {}).value || '',
                    'provider': (document.querySelector('[name="anbieter"]') || {}).value || ''
                });
                HTMLFormElement.prototype.submit.call(keForm);
            }, 600);
        });
    }

    const modalMainCta = document.getElementById('modalMainCta');
    const modalSkip = document.getElementById('modalSkip');

    if (modal) {
        var _keModalShown = false;
        var _keModalObserver = new MutationObserver(function() {
            var style = modal.getAttribute('style') || '';
            var classList = modal.className || '';
            var isVisible = !style.includes('display:none') && !style.includes('display: none') ||
                            style.includes('display:flex') || style.includes('display: flex') ||
                            style.includes('display:block') || style.includes('display: block') ||
                            classList.includes('active') || classList.includes('open');
            if (isVisible && !_keModalShown) {
                _keModalShown = true;
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ 'event': 'funnel_step', 'step_name': '3_modal_view' });
            }
            if (!isVisible) { _keModalShown = false; }
        });
        _keModalObserver.observe(modal, { attributes: true, attributeFilter: ['style', 'class'] });
    }

    if (modalMainCta) {
        modalMainCta.addEventListener('click', function() {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                'event': 'funnel_step',
                'step_name': '3_modal_cta_click',
                'tier': 'einschreiben'
            });
            window.dataLayer.push({
                'event': 'begin_checkout',
                'currency': 'EUR',
                'value': 8.99,
                'items': [{ 'item_id': 'einschreiben', 'item_name': 'Einwurfeinschreiben', 'price': 8.99, 'quantity': 1 }]
            });
        });
    }

    if (modalSkip) {
        modalSkip.addEventListener('click', function() {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                'event': 'funnel_step',
                'step_name': '3_modal_skip',
                'tier': 'standard'
            });
            window.dataLayer.push({
                'event': 'begin_checkout',
                'currency': 'EUR',
                'value': 3.99,
                'items': [{ 'item_id': 'standard', 'item_name': 'Standardbrief', 'price': 3.99, 'quantity': 1 }]
            });
        });
    }
});
</script>

<script>
/* Fix #1 — Intercept Einschreiben CTA: dacă email lipsește, oprește propagarea
   și îndrumă userul să completeze email-ul înainte de checkout.
   Capture phase (al 3-lea argument = true) rulează ÎNAINTEA oricărui
   bubble-handler din formular.js, deci blochează navigarea la Stripe. */
document.addEventListener('click', function(e) {
    var btn = e.target.closest('#modalMainCta');
    if (!btn) return;

    var emailField = document.getElementById('userEmailField');
    if (!emailField || emailField.value.trim() !== '') return;

    // Email lipsește — blocăm click-ul
    e.stopPropagation();
    e.preventDefault();

    // Arătăm warning în modal (dacă nu există deja)
    if (!document.getElementById('_keEmailWarn')) {
        var warn = document.createElement('div');
        warn.id = '_keEmailWarn';
        warn.style.cssText = [
            'background:#FEF3C7',
            'border:1px solid #FDE68A',
            'border-radius:10px',
            'padding:11px 15px',
            'margin-bottom:14px',
            'font-size:13px',
            'color:#92400E',
            'font-weight:600',
            'text-align:left',
            'line-height:1.5'
        ].join(';');
        warn.innerHTML = '📧 <strong>E-Mail-Adresse fehlt.</strong> Für den Einschreiben-Tracking-Link benötigen wir Ihre E-Mail.'
            + ' <a href="#" id="_keEmailWarnLink" style="color:#D97706;font-weight:700;text-decoration:underline;">Jetzt oben eingeben →</a>';
        btn.parentNode.insertBefore(warn, btn);

        document.getElementById('_keEmailWarnLink').addEventListener('click', function(ev) {
            ev.preventDefault();
            // Modal schließen
            var modal = document.getElementById('upsellModal');
            if (modal) {
                modal.style.cssText = 'display:none !important';
                modal.classList.remove('active', 'open');
            }
            // Zum Email-Feld scrollen + fokussieren
            var ef = document.getElementById('userEmailField');
            if (ef) {
                ef.scrollIntoView({ behavior: 'smooth', block: 'center' });
                ef.style.borderColor = '#F59E0B';
                ef.style.boxShadow = '0 0 0 3px rgba(245,158,11,0.25)';
                setTimeout(function() { ef.focus(); }, 350);
                ef.addEventListener('input', function resetStyle() {
                    ef.style.borderColor = '';
                    ef.style.boxShadow = '';
                    var w = document.getElementById('_keEmailWarn');
                    if (w) w.remove();
                    ef.removeEventListener('input', resetStyle);
                }, { once: true });
            }
        });
    }
}, true /* capture phase */);
</script>

<?php if (!empty($prefillFromPost)): ?>
<script>window.__kePrefillFromPost = <?= json_encode($prefillFromPost, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php endif; ?>

<script>
/* Fix #5 — Brief-preview live update
   Fix #6 — RSG provider detection live în câmpul anbieter */
(function () {
  var bpSender    = document.querySelector('.bp-sender');
  var bpRecipName = document.querySelector('.bp-recipient-name');
  var anbieterInp = document.getElementById('anbieterInput');
  var rsgWarn     = document.getElementById('_rsgDynWarn');
  var rsgLink     = document.getElementById('_rsgDynLink');
  var initialRsg  = anbieterInp && anbieterInp.dataset.isRsg === '1';

  // --- Fix #5: Live sender line ---
  function fieldVal(name) {
    var el = document.querySelector('input[name="' + name + '"]');
    return el ? el.value.trim() : '';
  }

  function updateSender() {
    if (!bpSender) return;
    var fn     = fieldVal('firstName');
    var ln     = fieldVal('lastName');
    var street = fieldVal('street');
    var zip    = fieldVal('zip');
    var city   = fieldVal('city');
    var name = [fn, ln].filter(Boolean).join(' ');
    var addr = [street, [zip, city].filter(Boolean).join(' ')].filter(Boolean).join(', ');
    var full = [name, addr].filter(Boolean).join(' · ');
    bpSender.textContent = full || 'Ihr Name · Ihre Adresse (wird unten ergänzt)';
    bpSender.classList.toggle('bp-placeholder', !full);
  }

  ['firstName', 'lastName', 'street', 'zip', 'city'].forEach(function (n) {
    var el = document.querySelector('input[name="' + n + '"]');
    if (el) el.addEventListener('input', updateSender);
  });
  updateSender();

  // --- Fix #6: Live RSG detection + Fix #5: Live recipient name ---
  var RSG_LIST = ['mcfit', 'john reed', 'john-reed', 'johnreed', 'high five'];

  function matchesRsg(v) {
    v = v.trim().toLowerCase();
    if (v.length < 3) return false;
    return RSG_LIST.some(function (p) {
      return v === p || p.startsWith(v) || v.startsWith(p);
    });
  }

  function checkAnbieter() {
    var v = anbieterInp ? anbieterInp.value : '';

    // Fix #5: update preview recipient name live
    if (bpRecipName) {
      bpRecipName.textContent = v.trim() || 'Ihr Anbieter';
    }

    // Fix #6: show/hide RSG warning — numai dacă PHP nu a randat deja blocul
    if (!rsgWarn || initialRsg) return;
    if (matchesRsg(v)) {
      var isJohn = /john|reed/i.test(v);
      if (rsgLink) {
        rsgLink.href = isJohn
          ? 'https://help.johnreed.fitness/hc/de'
          : 'https://hilfe.mcfit.com/hc/de';
        rsgLink.textContent = (isJohn ? 'John Reed' : 'McFit') + ' Kontaktformular öffnen →';
      }
      rsgWarn.style.display = 'block';
    } else {
      rsgWarn.style.display = 'none';
    }
  }

  if (anbieterInp) {
    anbieterInp.addEventListener('input', checkAnbieter);
    checkAnbieter(); // run on page load (handles pre-filled anbieter)
  }
})();
</script>
</body>
</html>