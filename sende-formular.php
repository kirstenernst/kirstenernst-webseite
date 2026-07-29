<?php
/**
 * sende-formular.php
 * Nimmt die Daten aus den Formularen (Kontakt & Event-Anmeldung) entgegen
 * und sendet sie per E-Mail an Kirsten. Läuft auf Hostinger (PHP).
 *
 * Es werden keine Daten gespeichert – nur als E-Mail weitergeleitet.
 */

/* =====================  EINSTELLUNGEN  ===================== */
$empfaenger    = 'kirsten@kirstenernst.com';   // Wohin die Formulardaten gehen
$absender      = 'kirsten@kirstenernst.com';   // MUSS eine Adresse der eigenen Domain sein (für Zustellbarkeit)
$absender_name = 'Website kirstenernst.com';
$danke_seite   = 'danke.html';                 // Weiterleitung nach erfolgreichem Versand
/* ========================================================== */


// Nur echte Formular-Absendungen (POST) zulassen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Spam-Schutz: verstecktes "Honeypot"-Feld. Menschen lassen es leer, Bots füllen es aus.
if (!empty($_POST['website'])) {
    header('Location: ' . $danke_seite); // Bot ins Leere laufen lassen (ohne Mail)
    exit;
}

/* --- Hilfsfunktionen --- */
function feld($key) {
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
}
function sauber_header($s) { // schützt vor E-Mail-Header-Injection
    return str_replace(array("\r", "\n", "%0a", "%0d"), '', $s);
}
function mime_betreff($s) {  // Umlaute im Betreff korrekt kodieren
    return '=?UTF-8?B?' . base64_encode($s) . '?=';
}

/* --- Eingaben einlesen --- */
$name      = feld('name');
$email     = feld('email');
$telefon   = feld('telefon');
$anliegen  = feld('anliegen');
$nachricht = feld('nachricht');
$formular  = feld('formular') !== '' ? feld('formular') : 'Formular';

/* --- Pflichtfelder prüfen --- */
$fehler = array();
if ($name === '') {
    $fehler[] = 'deinen Namen';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fehler[] = 'eine gültige E-Mail-Adresse';
}

if (!empty($fehler)) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Bitte prüfen</title></head>'
       . '<body style="font-family:sans-serif; max-width:520px; margin:80px auto; padding:0 24px; text-align:center; color:#3E4C5A;">'
       . '<h1 style="color:#6B2F7A;">Da fehlt noch etwas</h1>'
       . '<p>Bitte gib noch ' . htmlspecialchars(implode(' und ', $fehler)) . ' an.</p>'
       . '<p><a href="javascript:history.back()" style="color:#DD7E2E; font-weight:bold;">&larr; Zurück zum Formular</a></p>'
       . '</body></html>';
    exit;
}

/* --- E-Mail zusammenbauen --- */
$betreff = 'Neue Einsendung über die Website – ' . $formular;

$inhalt  = "Es ist eine neue Einsendung über kirstenernst.com eingegangen:\n\n";
$inhalt .= "Formular:  " . $formular . "\n";
$inhalt .= "Name:      " . $name . "\n";
$inhalt .= "E-Mail:    " . $email . "\n";
if ($telefon !== '')   { $inhalt .= "Telefon:   " . $telefon . "\n"; }
if ($anliegen !== '')  { $inhalt .= "Anliegen:  " . $anliegen . "\n"; }
if ($nachricht !== '') { $inhalt .= "\nNachricht:\n" . $nachricht . "\n"; }
$inhalt .= "\n---\nGesendet am " . date('d.m.Y \u\m H:i') . " Uhr";

/* --- Kopfzeilen --- */
$headers  = 'From: ' . $absender_name . ' <' . $absender . '>' . "\r\n";
$headers .= 'Reply-To: ' . sauber_header($name) . ' <' . sauber_header($email) . '>' . "\r\n";
$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";

/* --- Senden --- */
$erfolg = @mail($empfaenger, mime_betreff($betreff), $inhalt, $headers, '-f' . $absender);

if ($erfolg) {
    header('Location: ' . $danke_seite);
    exit;
}

// Falls der Versand fehlschlägt
http_response_code(500);
echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
   . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
   . '<title>Fehler beim Senden</title></head>'
   . '<body style="font-family:sans-serif; max-width:520px; margin:80px auto; padding:0 24px; text-align:center; color:#3E4C5A;">'
   . '<h1 style="color:#6B2F7A;">Ups, das hat gerade nicht geklappt</h1>'
   . '<p>Bitte schreib mir kurz direkt an '
   . '<a href="mailto:kirsten@kirstenernst.com" style="color:#DD7E2E; font-weight:bold;">kirsten@kirstenernst.com</a> '
   . '– ich melde mich schnellstmöglich bei dir.</p>'
   . '</body></html>';
exit;
