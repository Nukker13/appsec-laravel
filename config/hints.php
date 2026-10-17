<?php

/*
 | Hint schedule per lab: slug => list of ['after' => minutes since lab_user.started_at, 'text' => hint].
 | Extracted from source views; each hint timed at 2-minute intervals (2, 4, 6 minutes).
 */
return [
    'osci' => [
        ['after' => 2, 'text' => 'The endpoint passes your input straight into shell_exec(). Think about how shells chain commands.'],
        ['after' => 4, 'text' => 'Try appending a command with ;, &&, or a subshell like $(...).'],
        ['after' => 6, 'text' => 'The flag lives in flag.txt at the app root — read it with cat flag.txt once you have landed a command.'],
    ],
    'dom-xss' => [
        ['after' => 2, 'text' => 'Check the URL of the shrimp facts page — pay attention to how it is structured and what the page does with it.'],
        ['after' => 4, 'text' => 'Try injecting HTML via the URL fragment. For example, what happens with #<img src=x onerror=alert(1)>?'],
    ],
    'reflect-xss' => [
        ['after' => 2, 'text' => 'Check the URL of the 404 page — notice what gets reflected back into the page.'],
    ],
    'stored-xss' => [
        ['after' => 2, 'text' => 'Check the list of existing messages — there might be something useful there.'],
        ['after' => 4, 'text' => 'Try navigating to /admin. Regular users cannot access it, but admins can.'],
        ['after' => 6, 'text' => 'Send a JavaScript payload in the message form. Think about what happens when an admin reads it.'],
    ],
    'ssrf' => [
        // No hints for SSRF
    ],
    'oauth-ato' => [
        ['after' => 2, 'text' => 'Look at the URL when you start an OAuth link flow — notice the state parameter. Where is it stored on the server, and what happens to it after the callback is used?'],
        ['after' => 4, 'text' => 'Try reporting a crafted callback URL to the bot using your own credentials.'],
    ],
    'rbac' => [
        // No hints for RBAC
    ],
    'path-trav' => [
        ['after' => 2, 'text' => 'Uploaded PHP is scanned, but only for command-execution functions. Reading a file does not need one.'],
        ['after' => 4, 'text' => 'Upload a .php file that reads the flag, then open it through the file parameter so it gets included. Note that the file parameter refuses the word "flag".'],
    ],
    'type-juggle' => [
        ['after' => 2, 'text' => 'PHP has a concept of "magic hashes" — certain strings that, when hashed, produce a value that the PHP loose equality operator (==) treats as numerically equal to zero. Look them up.'],
        ['after' => 4, 'text' => 'The admin username is admin.'],
    ],
    'insec-deser' => [
        ['after' => 2, 'text' => 'Look for classes in the lab that define a __destruct() magic method — it fires automatically when an unserialized object goes out of scope. What does it do?'],
        ['after' => 4, 'text' => 'Example payload: inject a crafted object via the booking cookie, URL-encode it, set it as the cookie, then visit /book/summary.'],
    ],
];
