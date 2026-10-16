
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>Reflected XSS occurs when the injected script is reflected off the web server — in an error message, search result, or any response that includes input sent as part of the request. Attacks are delivered to victims via another route: a malicious link in email or another website.</p>
        <p>When a user clicks a malicious link, the injected code travels to the vulnerable server, which reflects it back to the browser. The browser executes it because it came from a "trusted" server. Reflected XSS is also called Non-Persistent or Type-I XSS — the attack is carried out through a single request/response cycle. In this lab, an admin bot checks submitted reports every 15 seconds.</p>
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
