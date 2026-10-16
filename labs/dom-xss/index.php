<?php

declare(strict_types=1);

use DOM\Helpers\SessionManager;

require_once __DIR__ . "/router.php";


$session = new SessionManager();
$session->login();

?>

<body>

<!-- TODO: hook up /roleChange to the admin settings panel — takes user_id + role via POST body -->

<!--Links to views-->
<style>
    .btn { margin:0 6px; padding:8px 12px; border-radius:6px; border:1px solid #ccc; background:#f5f5f5; cursor:pointer; }
    .btn.primary { background:#2563eb; color:#fff; border-color:#1e4db7; }
</style>

<p>
    <button class="btn primary" type="button" onclick="window.open('/flag','_blank')">Get the Flag!</button>
    <button class="btn"          type="button" onclick="window.open('/facts/cat','_blank')">Cats facts</button>
    <button class="btn"          type="button" onclick="window.open('/facts/shrimp')">Shrimp facts</button>
    <button class="btn"          type="button" onclick="window.open('/facts/dog','_blank')">Dogs facts</button>
</p>
<p style="margin-top:20px;">
    <strong>Found a page with an issue?</strong> Please, report it here:
    <button class="btn" type="button" onclick="window.open('/form','_blank')">Report a page</button>
</p>



</body>
