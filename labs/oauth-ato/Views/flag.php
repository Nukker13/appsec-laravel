<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flag</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 60px auto; max-width: 500px; text-align: center; }
        .flag-box {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 24px;
            font-family: monospace;
            font-size: 1.1rem;
            word-break: break-all;
            color: #1b5e20;
            margin-top: 20px;
        }
        h2 { color: #2e7d32; }
    </style>
</head>
<body>
<h2>Congratulations, Admin!</h2>
<p>You have successfully taken over the admin account via OAuth state hijacking.</p>
<?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
    <div class="flag-box"><?= htmlspecialchars(file_get_contents(__DIR__ . '/../flag.txt')) ?></div>
<?php else: ?>
    <p style="color:red;">Admins only!</p>
<?php endif; ?>
<p style="margin-top:20px;"><a href="/">← Back to dashboard</a></p>
</body>
</html>
