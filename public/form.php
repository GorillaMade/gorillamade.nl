<?php
declare(strict_types=1);

ini_set('display_errors', '0');
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
session_start();

$domain = 'gorillamade.nl';
$recipient = 'hallo@gorillamade.nl';
$forms = [
    'contact' => [
        'from' => 'contact@' . $domain,
        'subject' => 'Nieuw contactbericht | GorillaMade',
        'redirect' => '/contact/',
        'required' => [
            'naam',
            'email',
            'bericht',
            'toestemming',
        ],
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
        'subject' => 'Nieuwe offerteaanvraag | GorillaMade',
        'redirect' => '/offerte/',
        'required' => ['name', 'email', 'project', 'goal', 'audience', 'features', 'content', 'budget', 'timing', 'consent'],
        'labels' => [
            'name' => 'Naam', 'email' => 'E-mailadres', 'phone' => 'Telefoon',
            'company' => 'Bedrijf', 'project' => 'Projecttype', 'goal' => 'Doel',
            'audience' => 'Doelgroep', 'currentSite' => 'Huidige website',
            'pages' => 'Aantal pagina’s', 'features' => 'Functionaliteiten',
            'content' => 'Beschikbare content', 'examples' => 'Inspiratie',
            'design' => 'Designwensen', 'avoid' => 'Niet gewenst',
            'budget' => 'Budget', 'timing' => 'Planning',
            'hosting' => 'Hosting en onderhoud', 'notes' => 'Opmerkingen',
            'consent' => 'Privacyverklaring gelezen',
        ],
        'cooldown' => 180,
    ],
];

$isHtmx = ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';
function feedback(int $status, string $message, bool $isHtmx, string $redirect = '/'): never
{
    http_response_code($isHtmx ? 200 : $status);
    $kind = $status < 400 ? 'success' : 'error';
    $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '<div class="form-feedback__message form-feedback__message--' . $kind . '" role="alert">' . $safe . '</div>';
    if ($isHtmx) { echo $html; exit; }
    $url = htmlspecialchars($redirect, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Formulierstatus | GorillaMade</title></head><body><main><h1>Formulierstatus</h1>' . $html . '<p><a href="' . $url . '">Terug naar het formulier</a></p></main></body></html>';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    feedback(405, 'Alleen formulierinzendingen zijn toegestaan.', $isHtmx);
}
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $originHost = parse_url($origin, PHP_URL_HOST);
    if (!in_array($originHost, [$domain, 'www.' . $domain, 'localhost'], true)) {
        feedback(403, 'Ongeldige formulierherkomst.', $isHtmx);
    }
}
$type = $_POST['_form'] ?? null;
if (!is_string($type) || !isset($forms[$type])) {
    feedback(400, 'Ongeldig formulier.', $isHtmx);
}
$config = $forms[$type];
$redirect = $config['redirect'];
if (!empty($_POST['honeypot_field'])) {
    feedback(200, 'Bedankt! Je aanvraag is ontvangen.', $isHtmx, $redirect);
}
if (time() - (int) ($_SESSION['last_sent_' . $type] ?? 0) < $config['cooldown']) {
    feedback(429, 'Je hebt onlangs al een bericht verstuurd. Probeer het later opnieuw.', $isHtmx, $redirect);
}

$fields = [];
foreach ($config['labels'] as $key => $label) {
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        feedback(400, 'Een formulierwaarde is ongeldig.', $isHtmx, $redirect);
    }
    $value = trim($value);
    if (strlen($value) > 20000) {
        feedback(422, 'Een ingevuld veld is te lang.', $isHtmx, $redirect);
    }
    $fields[$key] = $value;
}
foreach ($config['required'] as $key) {
    if ($fields[$key] === '') {
        feedback(422, 'Vul alle verplichte velden in.', $isHtmx, $redirect);
    }
}
$email = $fields['email'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
    feedback(422, 'Vul een geldig e-mailadres in.', $isHtmx, $redirect);
}
$consentKey = $type === 'contact' ? 'toestemming' : 'consent';
if ($fields[$consentKey] !== 'ja') {
    feedback(422, 'Bevestig eerst dat je de privacyverklaring hebt gelezen.', $isHtmx, $redirect);
}

$lines = [];
if ($type === 'offerte') {
    $services = $_POST['services'] ?? [];
    if (!is_array($services) || count($services) === 0 || count($services) > 4) {
        feedback(422, 'Selecteer minimaal één geldige dienst.', $isHtmx, $redirect);
    }
    $allowedServices = ['Webdesign', 'Development', 'Hosting', 'Onderhoud'];
    foreach ($services as $service) {
        if (!is_string($service) || !in_array($service, $allowedServices, true)) {
            feedback(422, 'Een geselecteerde dienst is ongeldig.', $isHtmx, $redirect);
        }
    }
    $lines[] = 'Diensten: ' . implode(', ', array_unique($services));
}
foreach ($config['labels'] as $key => $label) {
    $lines[] = $label . ': ' . ($fields[$key] !== '' ? $fields[$key] : '-');
}
$body = "Nieuw bericht via GorillaMade\n\n" . implode("\n\n", $lines) . "\n";
$headers = [
    'From' => $config['from'],
    'Reply-To' => $email,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
];
if (!mail($recipient, $config['subject'], $body, $headers)) {
    feedback(500, 'Verzenden is momenteel niet mogelijk. Probeer het later opnieuw.', $isHtmx, $redirect);
}
$_SESSION['last_sent_' . $type] = time();
feedback(200, 'Bedankt! Je bericht is succesvol verzonden.', $isHtmx, $redirect);
