
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>Stored XSS (also called Persistent or Type-II XSS) occurs when the injected script is permanently stored on the target server — in a database, message forum, comment field, or visitor log. The victim retrieves the malicious script from the server whenever they request the stored information.</p>
        <p>Unlike reflected XSS, stored XSS does not require tricking the victim into clicking a crafted link — anyone who views the page containing the stored payload is affected. In this lab, an admin bot checks the messages page every 15 seconds.</p>
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
