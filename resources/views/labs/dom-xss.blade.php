
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>DOM-based XSS vulnerabilities arise when JavaScript takes data from an attacker-controllable source, such as the URL, and passes it to a sink that supports dynamic code execution — like <code>eval()</code> or <code>innerHTML</code>. This enables attackers to execute malicious JavaScript and typically allows them to hijack other users' accounts.</p>
        <p>To deliver a DOM-based XSS attack, place data into a source so that it propagates to a sink and causes execution of arbitrary JavaScript. The most common source is the URL, typically accessed via <code>window.location</code>. An attacker can construct a link to send a victim to a vulnerable page with a payload in the query string or fragment. In this lab, an admin bot checks submitted reports every 15 seconds.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Vulnerable Code Example</div>
    <pre><code class="language-php">@verbatim<?php
require_once 'auth_moderator_or_admin.php';
$userId = (int)($_GET['id'] ?? 0);
?>
<!doctype html>
<html>
  <body>
    <h2>User profile</h2>
    <div>Name: <?= $user['username_raw'] ?></div>
    <div>Email: <?= $user['email_raw'] ?></div>
  </body>
</html>@endverbatim</code></pre>
</div>
