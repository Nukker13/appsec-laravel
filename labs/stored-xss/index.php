<?php

use Stored\Helpers\SessionManager;

require_once __DIR__ . "/router.php";


$session = new SessionManager();
$session->login();


//echo "<pre>";
//var_dump($_SESSION);

?>

<body>
<!--Message form-->
<form action="/message" method="post">
    <textarea id="message" name="message" rows="4" cols="60"></textarea>
    <button type="submit">Send</button>
</form>


<!--Links to admin views-->

<style>
    .btn { margin:0 6px; padding:8px 12px; border-radius:6px; border:1px solid #ccc; background:#f5f5f5; cursor:pointer; }
    .btn.primary { background:#2563eb; color:#fff; border-color:#1e4db7; }
</style>

<p>
    <button class="btn primary" type="button" onclick="window.open('/flag','_blank')">Get the Flag!</button>
    <button class="btn"          type="button" onclick="window.open('/student','_blank')">Read the messages here</button>
</p>


</body>
