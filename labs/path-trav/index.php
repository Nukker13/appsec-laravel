<?php
declare(strict_types=1);

$imagesDir = __DIR__ . '/images/';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $filename = basename($_FILES['file']['name']);
    $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        $content  = file_get_contents($_FILES['file']['tmp_name']);
        $blocked  = ['exec(', 'shell_exec(', 'system(', 'passthru(', 'popen(', 'proc_open(', 'pcntl_exec('];
        foreach ($blocked as $fn) {
            if (str_contains($content, $fn)) {
                http_response_code(400);
                echo '<h2>Don\'t hack me bro!</h2>';
                exit;
            }
        }
    }
    move_uploaded_file($_FILES['file']['tmp_name'], $imagesDir . $filename);
    header('Location: /');
    exit;
}

// Handle file open — VULNERABLE: no path sanitization
if (isset($_GET['f'])) {
    if (str_contains($_GET['f'], 'flag')) {
        http_response_code(403);
        echo '<h2>Access denied.</h2>';
        exit;
    }
    $path = $imagesDir . $_GET['f'];
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    if (in_array($ext, $imageExts, true)) {
        $mime = match($ext) {
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'svg'         => 'image/svg+xml',
            'webp'        => 'image/webp',
        };
        header('Content-Type: ' . $mime);
        readfile($path);
    } else {
        include $path;
    }
    exit;
}

// List files for selector
$files = array_values(array_filter(
    scandir($imagesDir),
    fn($f) => !in_array($f, ['.', '..', '.gitkeep'])
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shrimp Gallery</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f4f8; color: #333; }
        header {
            background: #d4480a;
            color: #fff;
            padding: 0 32px;
            height: 56px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        header h1 { font-size: 20px; letter-spacing: 1px; }
        .container { max-width: 700px; margin: 48px auto; padding: 0 16px; display: flex; flex-direction: column; gap: 24px; }
        .card {
            background: #fff;
            border-radius: 8px;
            padding: 24px 28px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .card h2 { font-size: 15px; color: #555; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 16px; }
        select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 12px;
            background: #fafafa;
        }
        input[type="file"] {
            font-size: 14px;
            margin-bottom: 12px;
        }
        button, input[type="submit"] {
            background: #d4480a;
            color: #fff;
            border: none;
            padding: 9px 22px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        button:hover, input[type="submit"]:hover { background: #b33d08; }
        footer { text-align: center; color: #aaa; font-size: 12px; margin: 40px 0 20px; }
    </style>
</head>
<body>
<header>
    <h1>🦐 SHRIMP GALLERY</h1>
</header>

<div class="container">
    <div class="card">
        <h2>Open image</h2>
        <form method="get" action="/">
            <select name="f">
                <?php foreach ($files as $file): ?>
                    <option value="<?= htmlspecialchars($file) ?>"><?= htmlspecialchars($file) ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <button type="submit" formtarget="_blank">Open</button>
        </form>
    </div>

    <div class="card">
        <h2>Upload image</h2>
        <form method="post" action="/" enctype="multipart/form-data">
            <input type="file" name="file">
            <br>
            <input type="submit" value="Upload">
        </form>
    </div>
</div>

<footer>Shrimp Gallery &copy; 2026</footer>
</body>
</html>
