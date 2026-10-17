
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>Server-Side Request Forgery (SSRF) is a vulnerability that allows an attacker to cause the server to make requests to unintended locations. In a typical SSRF attack, the attacker causes the server to connect to internal-only services within the organisation's infrastructure, or to arbitrary external systems — potentially leaking sensitive data such as credentials or flags.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Vulnerable Code Example</div>
    <pre><code class="language-php">@verbatim// Main page: the server fetches whatever imageUrl points to
$imageUrl = $_GET['imageUrl'] ?? $defaultImageUrl;
if (($_GET['action'] ?? '') === 'count') {
    $data = @file_get_contents($imageUrl);
}

// Separate endpoint: GET /proxy?url=...
$body = @file_get_contents($_GET['url'] ?? '', false, $ctx);
echo $body;@endverbatim</code></pre>
</div>
