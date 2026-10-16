
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>Path Traversal (also known as directory traversal) is a vulnerability that allows an attacker to access files outside the web root by manipulating file path parameters with sequences such as <code>../</code>. When the application also accepts unrestricted file uploads, the attack escalates to Local File Inclusion (LFI) — an attacker can upload a malicious file and then force the server to execute it by including it through the same vulnerable parameter.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">The Vulnerable Implementation</div>
    <pre><code class="language-php">@verbatim// VULNERABLE: $_GET['f'] is passed directly into include — no sanitization
if (isset($_GET['f'])) {
    if (str_contains($_GET['f'], 'flag')) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
    include __DIR__ . '/images/' . $_GET['f'];  // path traversal here
    exit;
}@endverbatim</code></pre>
</div>

<div class="card">
    <div class="card-header">What It Should Look Like</div>
    <pre><code class="language-php">@verbatim// SECURE: restrict to known filenames from the images directory
$allowed = array_diff(scandir(__DIR__ . '/images/'), ['.', '..', '.gitkeep']);
$f = basename($_GET['f']);
if (!in_array($f, $allowed, true)) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}@endverbatim</code></pre>
</div>
