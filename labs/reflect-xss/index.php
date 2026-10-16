<?php

use Reflect\Helpers\sessionManager;

require_once __DIR__ . "/router.php";


$session = new SessionManager();
$session->login();

//echo "<pre>";
//var_dump($_SESSION);

?>

<body>

<!--Links to views-->
<style>
    .btn { margin:0 6px; padding:8px 12px; border-radius:6px; border:1px solid #ccc; background:#f5f5f5; cursor:pointer; }
    .btn.primary { background:#2563eb; color:#fff; border-color:#1e4db7; }
</style>

<p>
    <button class="btn primary" type="button" onclick="window.open('/flag','_blank')">Get the Flag!</button>
    <button class="btn"          type="button" onclick="window.open('/facts/cat','_blank')">Cats facts</button>
    <button class="btn"          type="button" onclick="window.open('/facts/404?broken_page=/facts/shrimp&msg=Page not Found','_blank')">Shrimp facts</button>
    <button class="btn"          type="button" onclick="window.open('/facts/dog','_blank')">Dogs facts</button>
</p>
<p style="margin-top:20px;">
    <strong>Found a page with an issue?</strong> Please, report it here:
    <button class="btn" type="button" onclick="window.open('/form','_blank')">Report a page</button>
</p>



</body>
