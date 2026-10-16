<?php
$flag = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flag = file_get_contents('flag.txt');
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin</title></head>
<body>
<h1>Admin panel</h1>

<form method="post">
    <button type="submit">Show Flag</button>
</form>

<?php if ($flag !== ''): ?>
    <pre><?= $flag ?></pre>
<?php endif; ?>

</body>
</html>