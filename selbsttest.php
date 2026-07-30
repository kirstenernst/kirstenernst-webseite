<?php
/**
 * selbsttest.php
 * Verarbeitet den Selbsttest (Lead-Magnet auf der Startseite).
 *  1) Benachrichtigungs-Mail an Kirsten
 *  2) Automatische Ergebnis-Mail an die Interessentin (inkl. Event-Einladung)
 * Antwortet als JSON (wird per fetch/JavaScript aufgerufen).
 */

/* =====================  EINSTELLUNGEN  ===================== */
$empfaenger    = 'kirsten@kirstenernst.com';   // Lead-Benachrichtigung
$absender      = 'kirsten@kirstenernst.com';   // MUSS eine Adresse der eigenen Domain sein
$absender_name = 'Kirsten Ernst';
/* ========================================================== */

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'error' => 'Method not allowed'));
    exit;
}

// Spam-Schutz (Honeypot)
if (!empty($_POST['website'])) {
    echo json_encode(array('ok' => true)); // Bot ins Leere laufen lassen
    exit;
}

function feld($k) { return isset($_POST[$k]) ? trim((string) $_POST[$k]) : ''; }
function clean($s) { return str_replace(array("\r", "\n", "%0a", "%0d"), '', $s); }
function mimebetreff($s) { return '=?UTF-8?B?' . base64_encode($s) . '?='; }

$name      = feld('name');
$email     = feld('email');
$punkte    = feld('punkte');
$titel     = feld('ergebnis_titel');
$text      = feld('ergebnis_text');
$antworten = feld('antworten');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'Name oder E-Mail fehlt/ungültig.'));
    exit;
}

/* --- Mail 1: Benachrichtigung an Kirsten --- */
$betreff1 = 'Neuer Selbsttest-Lead über die Website';
$body1  = "Eine neue Person hat den Selbsttest ausgefüllt:\n\n";
$body1 .= "Name:     " . $name . "\n";
$body1 .= "E-Mail:   " . $email . "\n";
$body1 .= "Punkte:   " . $punkte . " von 20\n";
$body1 .= "Ergebnis: " . $titel . "\n\n";
$body1 .= "Antworten:\n" . $antworten . "\n";
$body1 .= "\n---\nGesendet am " . date('d.m.Y H:i') . " Uhr";

$headers1  = 'From: ' . $absender_name . ' <' . $absender . '>' . "\r\n";
$headers1 .= 'Reply-To: ' . clean($name) . ' <' . clean($email) . '>' . "\r\n";
$headers1 .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$headers1 .= 'MIME-Version: 1.0' . "\r\n";

$ok1 = @mail($empfaenger, mimebetreff($betreff1), $body1, $headers1, '-f' . $absender);

/* --- Mail 2: Ergebnis-Mail an die Interessentin --- */
$betreff2 = 'Dein Ergebnis: ' . $titel;
$body2  = "Hallo " . $name . ",\n\n";
$body2 .= "danke, dass du dir einen ehrlichen Moment für dich genommen hast.\n\n";
$body2 .= "DEIN ERGEBNIS: " . $titel . "\n\n";
$body2 .= $text . "\n\n";
$body2 .= "Als nächsten Schritt lade ich dich herzlich zu meinem kostenfreien Online-Event\n";
$body2 .= "„Ein Abend für dein Herz“ ein – ein sanfter Raum zum Ankommen bei dir:\n";
$body2 .= "https://www.kirstenernst.com/kostenfreies-event.html\n\n";
$body2 .= "Von Herzen\n";
$body2 .= "Kirsten Ernst\n";
$body2 .= "Coaching & Seelenheilung\n";
$body2 .= "https://www.kirstenernst.com/";

$headers2  = 'From: ' . $absender_name . ' <' . $absender . '>' . "\r\n";
$headers2 .= 'Reply-To: ' . $absender_name . ' <' . $absender . '>' . "\r\n";
$headers2 .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$headers2 .= 'MIME-Version: 1.0' . "\r\n";

@mail($email, mimebetreff($betreff2), $body2, $headers2, '-f' . $absender);

/* --- Antwort --- */
if ($ok1) {
    echo json_encode(array('ok' => true));
} else {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'Mail konnte nicht gesendet werden.'));
}
exit;
