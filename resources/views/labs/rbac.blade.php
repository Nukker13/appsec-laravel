
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>Broken Access Control occurs when an application relies on client-side mechanisms to enforce authorization instead of validating permissions on the server. Role-Based Access Control (RBAC) must be verified server-side on every protected endpoint — hiding UI elements or restricting navigation in the browser is not a security boundary. An attacker can bypass these restrictions by navigating directly to protected URLs, inspecting the page source, or using a proxy to observe traffic.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">The Vulnerable Implementation</div>
    <pre><code class="language-php">@verbatim// VULNERABLE: no role check — any authenticated user reaches this branch
if ($uri === '/flag') {
    include __DIR__ . '/views/flag.php';  // flag served unconditionally
}@endverbatim</code></pre>
</div>

<div class="card">
    <div class="card-header">What It Should Look Like</div>
    <pre><code class="language-php">@verbatim// SECURE: role verified before serving the protected resource
if ($uri === '/flag') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    include __DIR__ . '/views/flag.php';
}@endverbatim</code></pre>
</div>
