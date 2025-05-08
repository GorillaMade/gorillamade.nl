<?php
session_start();
header("Content-Type: text/html; charset=UTF-8");

// Configuratie
$domain = "gorillamade.nl";
$to = "hallo@$domain";
$from_email = "contact@$domain";

// Spambeveiliging via honeypot
if (!empty($_POST["honeypot_field"])) {  // We controleren het nieuwe veld 'honeypot_field'
    http_response_code(400);
    echo "<p class='error'>Spam gedetecteerd.</p>";
    exit;
}

// Timer instellen (bijvoorbeeld 60 seconden tussen formulierverzendingen)
$time_limit = 1800;

// Controleer of de gebruiker het formulier te snel probeert te versturen
if (isset($_SESSION['last_email_time']) && time() - $_SESSION['last_email_time'] < $time_limit) {
    http_response_code(429); // Zet de statuscode voor rate-limiting
    echo "<p class='error'>Je kunt dit formulier slechts ייn keer per $time_limit seconden verzenden.</p>"; // Foutmelding voor rate-limiting
    exit;
}

// Verplichte velden controleren
if (empty($_POST["name"]) || empty($_POST["email"]) || empty($_POST["message"])) {
    http_response_code(400);
    echo "<p class='error'>Vul alle verplichte velden in.</p>";
    exit;
}

// E-mailbericht opstellen
$subject = $_POST["_subject"] ?? "Nieuw bericht via website";
$fields = $_POST;
unset($fields["_subject"], $fields["_next"], $fields["honeypot_field"]);  // Verwijder honeypot uit het veld

$message = "Nieuw bericht:\n\n";
foreach ($fields as $key => $value) {
    $message .= ucfirst($key) . ": " . trim($value) . "\n";
}

// E-mail headers
$reply_to = $fields["email"] ?? $to;
$headers = "From: $from_email\r\n";
$headers .= "Reply-To: $reply_to\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8";

// Versturen van de e-mail
if (mail($to, $subject, $message, $headers)) {
    // Sla de tijd op van de laatste e-mailverzending
    $_SESSION['last_email_time'] = time();

    // Succesbericht tonen via HTMX
    echo "<p class='success'>Uw bericht is succesvol verzonden. We nemen snel contact met u op!</p>";
} else {
    http_response_code(500);
    echo "<p class='error'>Er ging iets mis bij het verzenden van uw bericht.</p>";
}
?>