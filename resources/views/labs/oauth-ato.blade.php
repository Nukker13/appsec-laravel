
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>OAuth Login CSRF occurs when an application fails to properly bind an OAuth flow to the initiating user's session. The <code>state</code> parameter acts as a CSRF token — it should be generated, stored in the user's session, and verified on callback. When state is stored globally instead of per-session, a valid callback can be replayed by a different actor, allowing an attacker to hijack an OAuth identity and gain elevated access. In this lab, an admin bot checks submitted reports every 15 seconds.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Test accounts</div>
    <div class="card-body">
        <p>To connect an account through the provider login, use these student accounts (they belong to this lab only):</p>
        <ul>
            <li>Google: <code>student@gmail.com</code> / <code>google123</code></li>
            <li>Apple: <code>student@icloud.com</code> / <code>apple123</code></li>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header">The Vulnerable Implementation</div>
    <pre><code class="language-php">@verbatim// VULNERABLE: state stored globally, not in session
$state = bin2hex(random_bytes(16));
file_put_contents('/tmp/oauth_state', $state);
header("Location: /oauth/provider?state=$state");@endverbatim</code></pre>
</div>

<div class="card">
    <div class="card-header">How It Should Work</div>
    <pre><code class="language-php">@verbatim// SECURE: state stored in session (tied to this user)
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
header("Location: /oauth/provider?state=$state");@endverbatim</code></pre>
</div>
