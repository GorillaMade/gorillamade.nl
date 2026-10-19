<?php
declare(strict_types=1);

// Plaats dit bestand in de webroot: /public_html/form.php
// Vereist PHP met een werkende mail()-configuratie.
ini_set('display_errors', '0');
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$domain = 'gorillamade.nl';
$forms = [
    'contact' => [
        'from' => 'contact@' . $domain,
        'to' => 'hallo@' . $domain,
        'subject' => 'Nieuw contactbericht | GorillaMade',
        'redirect' => '/contact/',
        'required' => ['naam', 'email', 'bericht', 'toestemming'],
        'labels' => [
            'naam' => 'Naam',
            'email' => 'E-mailadres',
            'telefoon' => 'Telefoonnummer',
            'onderwerp' => 'Onderwerp',
            'bericht' => 'Bericht',
            'toestemming' => 'Privacyverklaring gelezen',
        ],
        'cooldown' => 120,
    ],
    'offerte' => [
        'from' => 'offerte@' . $domain,
        'to' => 'hallo@' . $domain,
        'subject' => 'Nieuwe offerteaanvraag | GorillaMade',
        'redirect' => '/offerte/',
        'required' => ['name', 'email', 'project', 'goal', 'audience', 'features', 'content', 'budget', 'timing', 'consent'],
        'labels' => [
            'name' => 'Naam',
            'email' => 'E-mailadres',
            'phone' => 'Telefoon',
            'company' => 'Bedrijf',
            'project' => 'Projecttype',
            'goal' => 'Doel',
            'audience' => 'Doelgroep',
            'currentSite' => 'Huidige website',
            'pages' => 'Aantal pagina’s',
            'features' => 'Functionaliteiten',
            'content' => 'Beschikbare content',
            'examples' => 'Inspiratie',
            'design' => 'Designwensen',
            'avoid' => 'Niet gewenst',
            'budget' => 'Budget',
            'timing' => 'Planning',
            'hosting' => 'Hosting en onderhoud',
            'notes' => 'Opmerkingen',
            'consent' => 'Privacyverklaring gelezen',
        ],
        'cooldown' => 180,
    ],
];

// Het bestaande form-submit.js stuurt HX-Request: true en verwacht
// .form-success of .form-error in de HTML-respons.
$isAjax = strtolower((string) ($_SERVER['HTTP_HX_REQUEST'] ?? '')) === 'true';

function esc(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function respond(int $status, string $message, bool $ajax, string $redirect = '/'): never
{
    http_response_code($status);
    $success = $status >= 200 && $status < 300;
    $html = $success
        ? '<section class="form-success" role="status" aria-live="polite"><h2>BEDANKT VOOR JE BERICHT.</h2><p>' . esc($message) . '</p></section>'
        : '<p class="form-error" role="alert">' . esc($message) . '</p>';

    if ($ajax) {
        echo $html;
        exit;
    }

    echo '<!doctype html><html lang="nl"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Formulier | GorillaMade</title></head><body><main>';
    echo $html;
    echo '<p><a href="' . esc($redirect) . '">Terug naar het formulier</a></p>';
    echo '</main></body></html>';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, 'Alleen POST-verzoeken zijn toegestaan.', $isAjax);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (is_string($origin) && $origin !== '') {
    $host = parse_url($origin, PHP_URL_HOST);
    if (!is_string($host) || !in_array(strtolower($host), [$domain, 'www.' . $domain, 'localhost', '127.0.0.1'], true)) {
        respond(403, 'Ongeldige formulierherkomst.', $isAjax);
    }
}

$type = $_POST['_form'] ?? null;
if (!is_string($type) || !isset($forms[$type])) {
    respond(400, 'Ongeldig formulier.', $isAjax);
}
$config = $forms[$type];
$redirect = $config['redirect'];

// Bots krijgen geen aanwijzing over de honeypot.
if (!empty($_POST['honeypot_field'])) {
    respond(200, 'Bedankt voor je bericht.', $isAjax, $redirect);
}

$lastSent = (int) ($_SESSION['last_sent_' . $type] ?? 0);
if ($lastSent > 0 && time() - $lastSent < $config['cooldown']) {
    respond(429, 'Je hebt onlangs al een bericht verzonden. Probeer het later opnieuw.', $isAjax, $redirect);
}

$fields = [];
foreach ($config['labels'] as $key => $label) {
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        respond(422, 'Een formulierwaarde is ongeldig.', $isAjax, $redirect);
    }
    $value = trim($value);
    if (strlen($value) > 20000) {
        respond(422, 'Een ingevuld veld is te lang.', $isAjax, $redirect);
    }
    $fields[$key] = $value;
}

foreach ($config['required'] as $key) {
    if ($fields[$key] === '') {
        respond(422, 'Vul alle verplichte velden in.', $isAjax, $redirect);
    }
}

$email = $fields['email'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
    respond(422, 'Vul een geldig e-mailadres in.', $isAjax, $redirect);
}

$consentKey = $type === 'contact' ? 'toestemming' : 'consent';
if ($fields[$consentKey] !== 'ja') {
    respond(422, 'Bevestig dat je de privacyverklaring hebt gelezen.', $isAjax, $redirect);
}

$lines = [];
if ($type === 'offerte') {
    $services = $_POST['services'] ?? [];
    if (!is_array($services) || count($services) < 1 || count($services) > 12) {
        respond(422, 'Selecteer minimaal één dienst.', $isAjax, $redirect);
    }
    $cleanServices = [];
    foreach ($services as $service) {
        if (!is_string($service) || trim($service) === '' || strlen($service) > 120) {
            respond(422, 'Een geselecteerde dienst is ongeldig.', $isAjax, $redirect);
        }
        $cleanServices[] = trim($service);
    }
    $lines[] = 'Diensten: ' . implode(', ', array_unique($cleanServices));
}

foreach ($config['labels'] as $key => $label) {
    $lines[] = $label . ': ' . ($fields[$key] !== '' ? $fields[$key] : '-');
}
$body = "Nieuw bericht via GorillaMade\n\n" . implode("\n", $lines) . "\n";

$headers = [
    'From' => $config['from'],
    'Reply-To' => $email,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
];

try {
    // Zelfde eenvoudige mail()-aanpak als het werkende referentieproject.
    $sent = mail($config['to'], $config['subject'], $body, $headers);
} catch (Throwable $exception) {
    error_log('GorillaMade form.php mail exception (' . $type . '): ' . $exception->getMessage());
    $sent = false;
}

if (!$sent) {
    error_log('GorillaMade form.php: mail() failed for ' . $type);
    respond(500, 'E-mail versturen is momenteel niet mogelijk. Probeer het later opnieuw.', $isAjax, $redirect);
}

$_SESSION['last_sent_' . $type] = time();
respond(200, 'Bedankt! Je bericht is verzonden. Ik neem zo snel mogelijk contact met je op.', $isAjax, $redirect);
