<?php

http_response_code(404);

$brokenPage = $_GET['broken_page'] ?? '';
$msg        = $_GET['msg'] ?? '';

//var_dump($_SESSION);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page not found</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #fafafa;
            color: #333;
        }
        h1 { color: #b91c1c; }
        .box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            max-width: 600px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        code {
            background: #f3f3f3;
            padding: 2px 4px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<h1>404 – Page not found</h1>

<div class="box">
    <p>
        We tried to load the page:
        <code><?php echo $brokenPage; ?></code>
    </p>

    <p>
        Error details:
        <strong><?php echo $msg; ?></strong>
    </p>

    <p>
        If you think this is a mistake, please report this URL via the “Report a page” form.
    </p>
</div>
</body>
</html>