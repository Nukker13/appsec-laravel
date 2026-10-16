<?php

declare(strict_types=1);

global $allowed;

function filter_exec($input): string
{
    $allowed = ['ls', 'cat', 'pwd', 'find', 'grep', 'echo', 'flag.txt' ];

    if (preg_match('/[;&|><]/', $input)) {
        return 'Dont hack me bro';
    }

    $command = explode(' ', $input)[0];

    if (!in_array($command, $allowed, true)) {
        return 'Command not allowed';
    }

    return shell_exec($input);
}

if (isset($_GET['iframe'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['oauthToken'])) {

        $input = trim($_POST['oauthToken']);

        echo filter_exec($input);
        exit;
    }
    else {
        echo 'Wrong token!';
        exit;
    }

}

?>

<body>
<!-- Input form -->
<form method="post" action="?iframe=1" target="response">
<label for="oauthToken">Send your OAUTH Token:</label>
<input type="text" name="oauthToken">
</form>
<!-- Response iframe -->
<p>Your response:</p>
<iframe name="response"></iframe>
</body>
