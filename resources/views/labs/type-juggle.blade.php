
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>PHP Type Juggling is a vulnerability that arises from PHP's loose comparison operator (<code>==</code>), which performs type coercion before comparing values. When hash strings are compared with <code>==</code> instead of <code>===</code>, an attacker can craft an input whose hash evaluates as equal to the stored hash under PHP's coercion rules — without knowing the original value — effectively bypassing authentication.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">The Vulnerable Implementation</div>
    <pre><code class="language-php">@verbatim// Admin password hash stored in the database:
// 0e830400451993494058024219903391  (MD5 — loose-equals any other 0e magic hash)

function computeHash(string $algo, string $password): string
{
    return match($algo) {
        'md5'    =&gt; md5($password),
        'sha1'   =&gt; sha1($password),
        'sha256' =&gt; hash('sha256', $password),
        default  =&gt; md5($password),
    };
}

// VULNERABLE: == instead of ===
$computed = computeHash($user['algorithm'], $password);
if ($computed == $user['password_hash']) {
    // login success
}@endverbatim</code></pre>
</div>

<div class="card">
    <div class="card-header">What It Should Look Like</div>
    <pre><code class="language-php">@verbatim// SECURE: strict comparison — no type coercion
if ($computed === $user['password_hash']) {
    // login success
}@endverbatim</code></pre>
</div>
