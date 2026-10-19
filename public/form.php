<?php
declare(strict_types=1);

ini_set('display_errors', '0');
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$domain = 'gorillamade.nl';
$recipient = 'hallo@gorillamade.nl';

$forms = [
    'contact' => [
        'from' => 'contact@' . $domain,
        'subject' => 'Nieuw contactbericht | GorillaMade',
        'redirect' => '/contact/',
        'target' => '#contact-feedback',
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
        'subject' => 'Nieuwe offerteaanvraag | GorillaMade',
        'redirect' => '/offerte/',
        'target' => '#offerte-feedback',
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

$isHtmx = strtolower((string) ($_SERVER['HTTP_HX_REQUEST'] ?? '')) === 'true';

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function feedback(
    int $status,
    string $message,
    bool $isHtmx,
    string $redirect = '/',
    string $errorTarget = '#contact-feedback'
): never {
    $success = $status >= 200 && $status < 300;
    $safeMessage = escapeHtml($message);

    if ($isHtmx) {
        // Preserve the real HTTP status for client-side error handling.
        http_response_code($status);

        if ($success) {
            echo '<section class="form-success" role="status" aria-live="polite">';
            echo '<h2>BEDANKT VOOR JE BERICHT.</h2>';
            echo '<p>' . $safeMessage . '</p>';
            echo '</section>';
        } else {
            header('HX-Retarget: ' . $errorTarget);
            header('HX-Reswap: innerHTML');
            echo '<p class="form-error" role="alert">' . $safeMessage . '</p>';
        }

        exit;
    }

    http_response_code($status);
    $heading = $success ? 'Bedankt voor je bericht' : 'Verzenden mislukt';
    $url = escapeHtml($redirect);

    echo '<!doctype html><html lang="nl"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . escapeHtml($heading) . ' | GorillaMade</title>';
    echo '</head><body><main>';
    echo '<h1>' . escapeHtml($heading) . '</h1>';
    echo '<p>' . $safeMessage . '</p>';
    echo '<p><a href="' . $url . '">Terug naar het formulier</a></p>';
    echo '</main></body></html>';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    feedback(405, 'Alleen formulierinzendingen zijn toegestaan.', $isHtmx);
}

// Controleer het herkomstdomein als de browser een Origin-header meestuurt.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (is_string($origin) && $origin !== '') {
    $originHost = parse_url($origin, PHP_URL_HOST);
    $allowedHosts = [$domain, 'www.' . $domain, 'localhost', '127.0.0.1'];

    if (!is_string($originHost) || !in_array(strtolower($originHost), $allowedHosts, true)) {
        feedback(403, 'Ongeldige formulierherkomst.', $isHtmx);
    }
}

$type = $_POST['_form'] ?? null;
if (!is_string($type) || !isset($forms[$type])) {
    feedback(400, 'Ongeldig formulier.', $isHtmx);
}

$config = $forms[$type];
$redirect = $config['redirect'];
$errorTarget = $config['target'];

// Honeypot: bots geen e-mail laten versturen, maar ook geen fout tonen.
if (!empty($_POST['honeypot_field'])) {
    feedback(200, 'Je bericht is ontvangen.', $isHtmx, $redirect, $errorTarget);
}

$lastSent = (int) ($_SESSION['last_sent_' . $type] ?? 0);
if (time() - $lastSent < $config['cooldown']) {
    feedback(429, 'Je hebt onlangs al een bericht verstuurd. Probeer het later opnieuw.', $isHtmx, $redirect, $errorTarget);
}

$fields = [];
foreach ($config['labels'] as $key => $label) {
    $value = $_POST[$key] ?? '';

    if (!is_string($value)) {
        feedback(422, 'Een formulierwaarde is ongeldig.', $isHtmx, $redirect, $errorTarget);
    }

    $value = trim($value);
    if (strlen($value) > 20000) {
        feedback(422, 'Een ingevuld veld is te lang.', $isHtmx, $redirect, $errorTarget);
    }

    $fields[$key] = $value;
}

foreach ($config['required'] as $key) {
    if ($fields[$key] === '') {
        feedback(422, 'Vul alle verplichte velden in.', $isHtmx, $redirect, $errorTarget);
    }
}

$email = $fields['email'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
    feedback(422, 'Vul een geldig e-mailadres in.', $isHtmx, $redirect, $errorTarget);
}

$consentKey = $type === 'contact' ? 'toestemming' : 'consent';
if ($fields[$consentKey] !== 'ja') {
    feedback(422, 'Bevestig eerst dat je de privacyverklaring hebt gelezen.', $isHtmx, $redirect, $errorTarget);
}

$lines = [];
if ($type === 'offerte') {
    $services = $_POST['services'] ?? [];

    if (!is_array($services) || count($services) < 1 || count($services) > 12) {
        feedback(422, 'Selecteer minimaal één dienst.', $isHtmx, $redirect, $errorTarget);
    }

    $cleanServices = [];
    foreach ($services as $service) {
        if (!is_string($service) || trim($service) === '' || strlen($service) > 120) {
            feedback(422, 'Een geselecteerde dienst is ongeldig.', $isHtmx, $redirect, $errorTarget);
        }
        $cleanServices[] = trim($service);
    }

    $lines[] = 'Diensten: ' . implode(', ', array_unique($cleanServices));
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

try {
    $sent = mail($recipient, $config['subject'], $body, $headers);
} catch (Throwable $exception) {
    error_log('GorillaMade form mail failure: ' . $exception->getMessage());
    $sent = false;
}

if (!$sent) {
    feedback(500, 'Verzenden is momenteel niet mogelijk. Probeer het later opnieuw.', $isHtmx, $redirect, $errorTarget);
}

$_SESSION['last_sent_' . $type] = time();
feedback(200, 'Bedankt! Je bericht is verzonden. Ik neem zo snel mogelijk contact met je op.', $isHtmx, $redirect, $errorTarget);
