
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>Server-Side Request Forgery (SSRF) is a vulnerability that allows an attacker to cause the server to make requests to unintended locations. In a typical SSRF attack, the attacker causes the server to connect to internal-only services within the organisation's infrastructure, or to arbitrary external systems — potentially leaking sensitive data such as credentials or flags.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Vulnerable Code Example</div>
    <pre><code class="language-php">@verbatim// Public app — image proxy endpoint
$imageUrl = $_GET['url'] ?? '';

// VULNERABLE: user-supplied URL is fetched server-side without restriction
$data = file_get_contents($imageUrl);

header('Content-Type: image/jpeg');
echo $data;@endverbatim</code></pre>
</div>
