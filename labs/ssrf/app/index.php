<?php
// ---------- SSRF proxy ----------
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/proxy') {
    $url = $_GET['url'] ?? '';

    $method = $_SERVER['REQUEST_METHOD'];

    $headers = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if ($contentType !== '') {
        $headers[] = 'Content-Type: ' . $contentType;
    }

    $opts = [
        'http' => [
            'method'  => $method,
            'header'  => implode("\r\n", $headers),
            'timeout' => 5,
        ],
    ];

    if ($method === 'POST') {
        $opts['http']['content'] = file_get_contents('php://input');
    }

    $ctx = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);

    if ($body === false) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Proxy error";
        exit;
    }

    // clickable admin page
    $encUrl = htmlspecialchars('/proxy?url=' . urlencode($url), ENT_QUOTES);
    $body = str_replace('<form method="post">', '<form method="post" action="'.$encUrl.'">', $body);

    header('Content-Type: text/html; charset=utf-8');
    echo $body;
    exit;
}
// ---------- SSRF proxy ----------


// ---------- main lab page ----------
$images = [
    'cats'    => ['label' => 'Cats',    'file' => 'cats.jpg',    'count' => 3],
    'dogs'    => ['label' => 'Dogs',    'file' => 'dogs.jpg',    'count' => 2],
    'shrimps' => ['label' => 'Shrimps', 'file' => 'shrimps.jpg', 'count' => 9],
];

$selectedKey = $_GET['image'] ?? 'cats';
if (!isset($images[$selectedKey])) $selectedKey = 'cats';
$selected = $images[$selectedKey];

$defaultImageUrl = 'static/' . $selected['file'];
$imageUrl = $_GET['imageUrl'] ?? $defaultImageUrl;

$result = null;
$showAdminFrame = false;
$adminFrameSrc = '';

if (isset($_GET['action']) && $_GET['action'] === 'count') {
    // SSRF point (GET):
    $data = @file_get_contents($imageUrl);

    if ($data === false) {
        $result = 'Failed to load image';
    } else {
        // hardcoded values
        $result = 'Found: ' . $selected['count'];

        // iframe in case of url
        if (strpos($imageUrl, 'static/') !== 0 && (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://'))) {
            $showAdminFrame = true;
            $adminFrameSrc = '/proxy?url=' . urlencode($imageUrl);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super AI</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        header { display:flex; justify-content:space-between; align-items:center; }
        .admin-link { font-size:14px; color:#777; text-decoration:none; border:1px solid #ccc; padding:6px 10px; border-radius:4px; }
        .container { margin-top: 40px; max-width: 900px; }
        img { max-width: 100%; margin: 20px 0; border: 1px solid #ddd; }
        iframe { width: 100%; height: 520px; border: 1px solid #ddd; margin: 20px 0; }
        button { padding: 10px 16px; font-size: 14px; background:#2563eb; color:#fff; border-color:#1e4db7; }
        .result { margin-top: 20px; font-weight:bold; white-space:pre-wrap; }
    </style>
</head>
<body>

<header>
    <h1>Super AI</h1>
    <a class="admin-link" href="http://ssrf_admin:9090">Admin panel</a>
</header>

<div class="container">
    <form method="get">
        <label for="image">Choose image:</label><br><br>

        <select name="image" id="image" onchange="syncUrl()">
            <?php foreach ($images as $key => $info): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= $key === $selectedKey ? 'selected' : '' ?>>
                    <?= htmlspecialchars($info['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="hidden" name="imageUrl" id="imageUrl" value="<?= htmlspecialchars($defaultImageUrl) ?>">

        <br><br>

        <?php if ($showAdminFrame): ?>
            <iframe src="<?= htmlspecialchars($adminFrameSrc) ?>"></iframe>
        <?php else: ?>
            <img id="preview" src="<?= htmlspecialchars($defaultImageUrl) ?>" alt="preview">
        <?php endif; ?>

        <br><br>

        <button type="submit" name="action" value="count">Show how many animals are on picture</button>
    </form>

    <?php if ($result !== null): ?>
        <div class="result">Result: <?= htmlspecialchars($result) ?></div>
    <?php endif; ?>
</div>

<script>
    function syncUrl() {
        const map = { cats:'static/cats.jpg', dogs:'static/dogs.jpg', shrimps:'static/shrimps.jpg' };
        const sel = document.getElementById('image').value;
        const url = map[sel] || 'static/cats.jpg';
        document.getElementById('imageUrl').value = url;
        const img = document.getElementById('preview');
        if (img) img.src = url;
    }
</script>

</body>
</html>