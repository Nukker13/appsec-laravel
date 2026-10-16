
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>Insecure Deserialization occurs when an application deserializes attacker-controlled data without validation. In PHP, <code>unserialize()</code> reconstructs a full object from a string — and automatically invokes lifecycle magic methods on it. This allows an attacker to inject a crafted object of any class available in the application, turning existing code into a gadget chain that produces unintended side effects such as arbitrary file writes or code execution.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">The Vulnerable Call</div>
    <pre><code class="language-php">@verbatim// In /book/summary — the booking cookie is unserialized without any validation
$booking = unserialize(urldecode($_COOKIE['booking']));@endverbatim</code></pre>
</div>

<div class="card">
    <div class="card-header">Secure Alternative</div>
    <pre><code class="language-php">@verbatim// SECURE: sign the serialized data and verify before unserializing
$secret = getenv('APP_SECRET');

// On save:
$serialized = serialize($booking);
$mac = hash_hmac('sha256', $serialized, $secret);
setcookie('booking', $mac . '|' . base64_encode($serialized));

// On load:
[$mac, $encoded] = explode('|', $_COOKIE['booking'], 2);
$serialized = base64_decode($encoded);
if (!hash_equals(hash_hmac('sha256', $serialized, $secret), $mac)) {
    die('Tampered cookie.');
}
$booking = unserialize($serialized);@endverbatim</code></pre>
</div>
